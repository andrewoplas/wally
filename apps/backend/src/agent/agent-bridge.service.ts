/**
 * AgentBridgeService
 *
 * Orchestrates the full chat lifecycle using the Claude Agent SDK:
 *  1. Builds the system prompt
 *  2. Creates a per-request MCP server (WordPress tools via bridge EventEmitter)
 *  3. Runs query() and streams tokens + tool events via SSE to the WP plugin
 *  4. Cleans up on disconnect
 */

import { Injectable, Logger } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import EventEmitter from 'events';
import type { Response } from 'express';
import { query } from '@anthropic-ai/claude-agent-sdk';
import type { SDKMessage, SDKResultMessage } from '@anthropic-ai/claude-agent-sdk';
import { ToolCallbackStore } from './tool-callback.store.js';
import { McpToolFactory } from './mcp-tool.factory.js';
import { PromptBuilderService } from '../knowledge/prompt-builder.service.js';
import type { SiteProfile, ConversationMessage } from '../knowledge/prompt-builder.service.js';
import { UsageService } from '../usage/usage.service.js';
import type { WallyConfig } from '../config/configuration.js';

const MAX_HISTORY_CONTENT = 4_000;
const MAX_TURNS = 15;

// ─── Public Params Type ───────────────────────────────────────────────────────

export interface ChatBridgeParams {
  siteId: string;
  message: string;
  model?: string;
  conversation_history?: unknown[] | null;
  site_profile?: unknown;
  tool_definitions?: unknown[];
  custom_system_prompt?: string | null;
  recent_actions?: string[] | null;
}

// ─── Service ──────────────────────────────────────────────────────────────────

@Injectable()
export class AgentBridgeService {
  private readonly logger = new Logger(AgentBridgeService.name);

  constructor(
    private readonly callbackStore: ToolCallbackStore,
    private readonly mcpToolFactory: McpToolFactory,
    private readonly promptBuilder: PromptBuilderService,
    private readonly usageService: UsageService,
    private readonly config: ConfigService<WallyConfig>,
  ) {}

  /**
   * Run a full chat round-trip, streaming SSE events to `res`.
   *
   * SSE events emitted (backward-compatible with existing plugin frontend):
   *   { type: 'token', content: string }          — text delta from Claude
   *   { type: 'tool_call', tool_call_id, tool, input, requires_confirmation, status }
   *   { type: 'done', stop_reason: string }        — final message
   *   { type: 'error', message: string }           — error condition
   */
  async runChat(params: ChatBridgeParams, res: Response): Promise<void> {
    const {
      siteId,
      message,
      conversation_history,
      site_profile,
      tool_definitions,
      custom_system_prompt,
      recent_actions,
    } = params;
    const modelId = params.model ?? this.config.get<string>('defaultModel') ?? 'claude-sonnet-4-6';

    // ── 1. Build system prompt ───────────────────────────────────────────────
    const baseSystemPrompt = this.promptBuilder.buildSystemPrompt(
      site_profile as SiteProfile | undefined,
      custom_system_prompt,
      message,
      conversation_history as ConversationMessage[] | undefined,
      recent_actions,
    );
    const systemPrompt = this.buildSystemPromptWithHistory(baseSystemPrompt, conversation_history);

    // ── 2. Per-request EventEmitter ──────────────────────────────────────────
    const bridge = new EventEmitter();
    const pendingCallIds = new Set<string>();

    // ── 3. Create MCP server ─────────────────────────────────────────────────
    const mcpServer = this.mcpToolFactory.createMcpServer(
      tool_definitions ?? [],
      bridge,
      this.callbackStore,
    );

    // ── 4. Set up SSE headers ────────────────────────────────────────────────
    res.setHeader('Content-Type', 'text/event-stream');
    res.setHeader('Cache-Control', 'no-cache');
    res.setHeader('Connection', 'keep-alive');
    res.setHeader('X-Accel-Buffering', 'no');
    res.flushHeaders();

    // ── 5. Forward tool_execute events as SSE tool_call events ───────────────
    bridge.on('tool_execute', (event: {
      callId: string;
      tool: string;
      input: unknown;
      requires_confirmation: boolean;
      category: string;
      action: string;
    }) => {
      pendingCallIds.add(event.callId);
      this.sseWrite(res, {
        type: 'tool_call',
        tool_call_id: event.callId,
        tool: event.tool,
        input: event.input,
        requires_confirmation: event.requires_confirmation,
        status: event.requires_confirmation ? 'pending_confirmation' : 'executing',
        category: event.category,
        action: event.action,
      });
    });

    // ── 6. AbortController + disconnect cleanup ───────────────────────────────
    const abortController = new AbortController();
    const onClose = (): void => {
      this.logger.debug('Client disconnected — aborting Agent SDK query');
      abortController.abort();
      for (const callId of pendingCallIds) {
        this.callbackStore.reject(callId, 'Client disconnected before tool result was received');
      }
      pendingCallIds.clear();
    };
    res.once('close', onClose);

    // ── 7. Run Agent SDK query ───────────────────────────────────────────────
    try {
      for await (const sdkMessage of query({
        prompt: message,
        options: {
          systemPrompt,
          mcpServers: { wordpress: mcpServer },
          includePartialMessages: true,
          maxTurns: MAX_TURNS,
          model: modelId,
          // Adaptive thinking lets Claude reason through complex WordPress tasks
          // before acting. Default for supported models, explicit here for clarity.
          // Prompt caching (cache_control on system prompt) is not yet exposed by
          // the Agent SDK — revisit when SDK adds support.
          thinking: { type: 'adaptive' as const },
          permissionMode: 'bypassPermissions',
          allowDangerouslySkipPermissions: true,
          abortController,
        },
      })) {
        this.handleSdkMessage(sdkMessage, res);

        if (sdkMessage.type === 'result') {
          void this.recordUsage(siteId, sdkMessage as SDKResultMessage);
          res.end();
          break;
        }
      }
    } catch (err) {
      const error = err as Error;
      if (error.name === 'AbortError') {
        // Client disconnected — nothing to write
        return;
      }
      this.logger.error(`Agent SDK query failed: ${error.message}`, error.stack);
      if (!res.writableEnded) {
        this.sseWrite(res, {
          type: 'error',
          message: 'An error occurred processing your request.',
        });
        res.end();
      }
    } finally {
      res.off('close', onClose);
      bridge.removeAllListeners();
    }
  }

  // ─── Private Helpers ─────────────────────────────────────────────────────────

  /** Handle a single SDKMessage from the query() async generator. */
  private handleSdkMessage(sdkMessage: SDKMessage, res: Response): void {
    if (sdkMessage.type === 'stream_event') {
      const event = sdkMessage.event;
      if (event.type === 'content_block_delta') {
        const delta = event.delta as { type: string; text?: string };
        if (delta.type === 'text_delta' && delta.text) {
          this.sseWrite(res, { type: 'token', content: delta.text });
        }
      }
      return;
    }

    if (sdkMessage.type === 'result') {
      const result = sdkMessage as SDKResultMessage;
      this.sseWrite(res, {
        type: 'usage',
        input_tokens: result.usage.input_tokens,
        output_tokens: result.usage.output_tokens,
      });

      if (result.subtype === 'success') {
        this.sseWrite(res, { type: 'done', stop_reason: 'end_turn' });
      } else {
        const errMessage =
          (result as { errors?: string[] }).errors?.[0] ??
          `Query stopped: ${result.subtype}`;
        this.sseWrite(res, { type: 'error', message: errMessage });
      }
    }
  }

  /** Fire-and-forget: record token usage to Supabase after query completes. */
  private async recordUsage(siteId: string, result: SDKResultMessage): Promise<void> {
    try {
      await this.usageService.recordUsage(
        siteId,
        result.usage.input_tokens,
        result.usage.output_tokens,
      );
    } catch (err) {
      this.logger.error(`Failed to record usage for site ${siteId}`, (err as Error).stack);
    }
  }

  /**
   * Appends the conversation history to the system prompt so the Agent SDK
   * has full context of prior turns (it manages its own session internally).
   */
  private buildSystemPromptWithHistory(
    baseSystemPrompt: string,
    conversationHistory?: unknown[] | null,
  ): string {
    if (!Array.isArray(conversationHistory) || conversationHistory.length === 0) {
      return baseSystemPrompt;
    }

    const lines: string[] = ['', '## Conversation History'];

    for (const msg of conversationHistory as Array<{ role?: string; content?: unknown }>) {
      if (!msg || typeof msg !== 'object') continue;
      const role = msg.role === 'user' ? 'User' : 'Assistant';
      const content =
        typeof msg.content === 'string'
          ? msg.content.slice(0, MAX_HISTORY_CONTENT)
          : '';
      if (content) {
        lines.push(`[${role}]: ${content}`);
      }
    }

    return lines.length > 2 ? baseSystemPrompt + lines.join('\n') : baseSystemPrompt;
  }

  private sseWrite(res: Response, data: Record<string, unknown>): void {
    if (!res.writableEnded) {
      res.write(`data: ${JSON.stringify(data)}\n\n`);
    }
  }
}
