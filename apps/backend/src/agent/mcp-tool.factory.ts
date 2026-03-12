/**
 * McpToolFactory
 *
 * Converts raw WordPress tool definitions (JSON Schema) into live MCP tools
 * that the Claude Agent SDK can call. Each tool handler:
 *  1. Emits a `tool_execute` SSE event to the WordPress plugin via the bridge EventEmitter
 *  2. Blocks on a Promise until the plugin POSTs the result to /api/v1/tool-callback
 *  3. Returns the result to the Agent SDK to continue the agentic loop
 */

import { Injectable, Logger } from '@nestjs/common';
import EventEmitter from 'events';
import { randomUUID } from 'crypto';
import { tool, createSdkMcpServer } from '@anthropic-ai/claude-agent-sdk';
import { z } from 'zod';
import { ToolCallbackStore } from './tool-callback.store.js';
import type { McpSdkServerConfigWithInstance } from '@anthropic-ai/claude-agent-sdk';

// ─── Raw tool definition shape (from WP plugin) ───────────────────────────────

interface RawToolProperty {
  type?: string;
  description?: string;
  enum?: string[];
  items?: { type?: string };
  properties?: Record<string, RawToolProperty>;
  required?: string[];
}

interface RawToolDefinition {
  name: string;
  description: string;
  category?: string;
  action?: string;
  requires_confirmation?: boolean;
  parameters?: {
    type?: string;
    properties?: Record<string, RawToolProperty> | unknown[];
    required?: string[];
  };
}

// ─── Service ──────────────────────────────────────────────────────────────────

@Injectable()
export class McpToolFactory {
  private readonly logger = new Logger(McpToolFactory.name);

  /**
   * Build an MCP server from raw WordPress tool definitions.
   *
   * @param rawTools      Raw tool_definitions array from the WP plugin request body
   * @param bridge        Per-request EventEmitter; receives `tool_execute` events
   * @param callbackStore Singleton store; used to await the plugin's callback POST
   */
  createMcpServer(
    rawTools: unknown[],
    bridge: EventEmitter,
    callbackStore: ToolCallbackStore,
  ): McpSdkServerConfigWithInstance {
    const normalized = this.normalizeToolDefinitions(rawTools);

    const mcpTools = normalized.map((toolDef) => {
      const shape = this.jsonSchemaToZodShape(
        toolDef.parameters?.properties ?? {},
        toolDef.parameters?.required ?? [],
      );

      return tool(
        toolDef.name,
        toolDef.description,
        shape,
        async (args) => {
          const callId = randomUUID();

          this.logger.debug(`Emitting tool_execute: ${toolDef.name} (callId: ${callId})`);

          // Signal the WordPress plugin via SSE; it will execute the tool
          // and POST the result back to /api/v1/tool-callback.
          bridge.emit('tool_execute', {
            callId,
            tool: toolDef.name,
            input: args,
            requires_confirmation: toolDef.requires_confirmation ?? false,
            category: toolDef.category ?? 'content',
            action: toolDef.action ?? 'read',
          });

          // Block the Agent SDK loop until the plugin sends the result back.
          const result = await callbackStore.register(callId);

          return {
            content: [{ type: 'text' as const, text: JSON.stringify(result) }],
          };
        },
      );
    });

    this.logger.debug(`Created MCP server with ${mcpTools.length} tools`);

    return createSdkMcpServer({
      name: 'wordpress',
      version: '1.0.0',
      tools: mcpTools,
    });
  }

  // ─── Helpers ─────────────────────────────────────────────────────────────────

  /**
   * Normalise raw plugin tool definitions:
   *  - PHP json_encode serialises empty arrays as [] instead of {}
   *  - Missing `type` defaults to 'object'
   */
  private normalizeToolDefinitions(raw: unknown[]): RawToolDefinition[] {
    const result: RawToolDefinition[] = [];

    for (const item of raw) {
      const t = item as Record<string, unknown>;
      if (typeof t['name'] !== 'string' || typeof t['description'] !== 'string') continue;

      const params = (t['parameters'] as Record<string, unknown>) ?? {};

      // PHP serialises empty object as [], normalize to {}
      const properties = params['properties'];
      if (!properties || Array.isArray(properties)) {
        params['properties'] = {};
      }
      if (!params['type']) {
        params['type'] = 'object';
      }

      result.push({
        name: t['name'],
        description: t['description'],
        category: typeof t['category'] === 'string' ? t['category'] : 'content',
        action: typeof t['action'] === 'string' ? t['action'] : 'read',
        requires_confirmation: t['requires_confirmation'] === true,
        parameters: params as RawToolDefinition['parameters'],
      });
    }

    return result;
  }

  /**
   * Convert a JSON Schema `properties` map + `required` array into a Zod shape
   * (a plain object of Zod types, NOT a ZodObject — this is what `tool()` expects).
   */
  private jsonSchemaToZodShape(
    properties: Record<string, RawToolProperty> | unknown[],
    required: string[],
  ): Record<string, z.ZodTypeAny> {
    // Guard against PHP empty-array serialisation
    if (Array.isArray(properties) || !properties || typeof properties !== 'object') {
      return {};
    }

    const shape: Record<string, z.ZodTypeAny> = {};
    const requiredSet = new Set(required);

    for (const [key, prop] of Object.entries(properties as Record<string, RawToolProperty>)) {
      let zodType = this.jsonSchemaPropertyToZod(prop);

      if (prop.description) {
        zodType = zodType.describe(prop.description);
      }

      // Fields not in `required` are optional
      if (!requiredSet.has(key)) {
        zodType = zodType.optional();
      }

      shape[key] = zodType;
    }

    return shape;
  }

  /**
   * Convert a single JSON Schema property to a Zod type (without .describe/.optional).
   */
  private jsonSchemaPropertyToZod(prop: RawToolProperty): z.ZodTypeAny {
    const type = prop.type;

    // Enum always wins over type
    if (prop.enum && prop.enum.length > 0) {
      return z.enum(prop.enum as [string, ...string[]]);
    }

    switch (type) {
      case 'string':
        return z.string();

      case 'number':
        return z.number();

      case 'integer':
        return z.number().int();

      case 'boolean':
        return z.boolean();

      case 'array': {
        const itemType = prop.items?.type
          ? this.jsonSchemaPropertyToZod({ type: prop.items.type })
          : z.unknown();
        return z.array(itemType);
      }

      case 'object': {
        const nestedShape = this.jsonSchemaToZodShape(
          prop.properties ?? {},
          prop.required ?? [],
        );
        return z.object(nestedShape);
      }

      default:
        // Unknown type — accept anything
        return z.unknown();
    }
  }
}
