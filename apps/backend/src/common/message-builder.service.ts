/**
 * MessageBuilderService
 *
 * Builds the Anthropic-formatted `messages` array for a tool-result
 * continuation request. Converts the WP plugin's conversation history
 * into Anthropic API message format and appends the new tool results.
 */

import { Injectable } from '@nestjs/common';

// ─── Types ────────────────────────────────────────────────────────────────────

export interface ConversationHistoryEntry {
  role: string;
  content: string | unknown;
  tool_call_id?: string;
  is_error?: boolean;
}

export interface ToolResult {
  tool_call_id: string;
  tool_name: string;
  result: string | unknown;
  is_error?: boolean;
}

export interface PendingToolCall {
  tool_call_id: string;
  tool: string;
  input: Record<string, unknown> | unknown;
}

export interface AnthropicMessage {
  role: 'user' | 'assistant';
  content: string | AnthropicContentBlock[];
}

export interface AnthropicContentBlock {
  type: string;
  [key: string]: unknown;
}

// ─── Service ──────────────────────────────────────────────────────────────────

@Injectable()
export class MessageBuilderService {
  /**
   * Build the messages array for a tool-result continuation request.
   *
   * @param conversationHistory  Prior messages from the plugin
   * @param pendingToolCalls     Tool calls the plugin is reporting results for
   * @param toolResults          Tool execution results from the plugin
   */
  buildToolResultMessages(
    conversationHistory: ConversationHistoryEntry[] | null | undefined,
    pendingToolCalls: PendingToolCall[] | null | undefined,
    toolResults: ToolResult[],
  ): AnthropicMessage[] {
    const messages: AnthropicMessage[] = [];

    if (conversationHistory && Array.isArray(conversationHistory)) {
      for (const msg of conversationHistory) {
        if (msg.role === 'tool_result') {
          // Anthropic format: tool results are user messages with tool_result content blocks
          messages.push({
            role: 'user',
            content: [
              {
                type: 'tool_result',
                tool_use_id: msg.tool_call_id,
                content:
                  typeof msg.content === 'string'
                    ? msg.content
                    : JSON.stringify(msg.content),
                is_error: msg.is_error ?? false,
              },
            ],
          });
        } else {
          messages.push({
            role: msg.role === 'user' ? 'user' : 'assistant',
            content:
              typeof msg.content === 'string'
                ? msg.content
                : JSON.stringify(msg.content),
          });
        }
      }
    }

    // Inject the assistant message with tool_use blocks (required by Anthropic API
    // before tool_result). PHP decodes empty JSON objects as [] (array), so we
    // normalize any non-plain-object inputs to {}.
    const toSafeInput = (
      v: unknown,
    ): Record<string, unknown> =>
      v !== null && typeof v === 'object' && !Array.isArray(v)
        ? (v as Record<string, unknown>)
        : {};

    const toolUseSources =
      pendingToolCalls &&
      Array.isArray(pendingToolCalls) &&
      pendingToolCalls.length > 0
        ? pendingToolCalls.map((tc) => ({
            id: tc.tool_call_id,
            name: tc.tool,
            input: toSafeInput(tc.input),
          }))
        : toolResults.map((tr) => ({
            id: tr.tool_call_id,
            name: tr.tool_name,
            input: {},
          }));

    messages.push({
      role: 'assistant',
      content: toolUseSources.map((tc) => ({
        type: 'tool_use',
        id: tc.id,
        name: tc.name,
        input: tc.input,
      })),
    });

    // Append new tool results as a user message
    const toolResultContent = toolResults.map((tr) => ({
      type: 'tool_result',
      tool_use_id: tr.tool_call_id,
      content:
        typeof tr.result === 'string' ? tr.result : JSON.stringify(tr.result),
      is_error: tr.is_error ?? false,
    }));

    messages.push({ role: 'user', content: toolResultContent });

    return messages;
  }
}
