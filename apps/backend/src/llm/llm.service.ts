/**
 * LlmService
 *
 * Unified service for sending requests to LLM providers (Anthropic / OpenAI).
 *
 * - Routes requests to the correct provider based on the model config.
 * - Streams token events as SSE to the Express response object.
 * - Normalises the Anthropic and OpenAI response shapes into a single
 *   `LlmResponse` format consumed by the controllers.
 */

import { Injectable, Logger } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import Anthropic from '@anthropic-ai/sdk';
import OpenAI from 'openai';
import type { Response } from 'express';
import { ToolDefinitionsService } from '../tools/tool-definitions.service.js';
import type { WallyConfig } from '../config/configuration.js';

// ─── Response Types ───────────────────────────────────────────────────────────

export interface LlmContentBlock {
  type: 'text' | 'tool_use' | 'thinking';
  text?: string;
  id?: string;
  name?: string;
  input?: Record<string, unknown>;
  thinking?: string;
}

export interface LlmUsage {
  input_tokens: number;
  output_tokens: number;
}

export interface LlmResponse {
  content: LlmContentBlock[];
  model: string;
  usage: LlmUsage | null;
  stop_reason: string;
}

// ─── Extended Thinking Config ─────────────────────────────────────────────────

const ENABLE_THINKING = false;
const THINKING_BUDGET = 6000;
const modelSupportsThinking = (modelId: string): boolean =>
  ENABLE_THINKING && !modelId.includes('haiku');

// ─── Service ──────────────────────────────────────────────────────────────────

@Injectable()
export class LlmService {
  private readonly logger = new Logger(LlmService.name);
  private anthropicClient: Anthropic | null = null;
  private openaiClient: OpenAI | null = null;

  constructor(
    private readonly config: ConfigService<WallyConfig>,
    private readonly toolDefinitions: ToolDefinitionsService,
  ) {}

  // ─── Public API ─────────────────────────────────────────────────────────────

  /**
   * Send a chat request to the LLM and stream the response.
   *
   * @param model        Model key from config (e.g. 'claude-sonnet-4-6')
   * @param systemPrompt Complete system prompt
   * @param messages     Conversation history in Anthropic message format
   * @param res          Express response object for SSE streaming
   */
  async sendToLLM(options: {
    model: string;
    systemPrompt: string;
    messages: Array<{ role: 'user' | 'assistant'; content: string | unknown[] }>;
    res: Response;
  }): Promise<LlmResponse> {
    const { model, systemPrompt, messages, res } = options;
    const models = this.config.get<WallyConfig['models']>('models') ?? {};
    const modelConfig = models[model];

    if (!modelConfig) {
      const available = Object.keys(models).join(', ');
      throw new Error(`Unknown model: ${model}. Available: ${available}`);
    }

    const { provider, modelId } = modelConfig;

    if (provider === 'anthropic') {
      return this.sendToAnthropic({ modelId, systemPrompt, messages, res });
    }

    if (provider === 'openai') {
      return this.sendToOpenAI({ modelId, systemPrompt, messages, res });
    }

    throw new Error(`Unknown provider: ${provider}`);
  }

  // ─── Anthropic ──────────────────────────────────────────────────────────────

  private async sendToAnthropic(options: {
    modelId: string;
    systemPrompt: string;
    messages: Array<{ role: 'user' | 'assistant'; content: string | unknown[] }>;
    res: Response;
  }): Promise<LlmResponse> {
    const { modelId, systemPrompt, messages, res } = options;

    const client = this.getAnthropicClient();
    if (!client) throw new Error('Anthropic API key not configured');

    const tools = this.toolDefinitions.getToolsForProvider('anthropic') as Anthropic.Tool[];
    const withThinking = modelSupportsThinking(modelId);
    const maxTokens = withThinking ? THINKING_BUDGET + 4096 : 4096;

    this.logger.log('LLM request (Anthropic)', {
      model: modelId,
      thinking: withThinking,
      maxTokens,
      systemPromptChars: systemPrompt.length,
      messageCount: messages.length,
      toolCount: tools.length,
    });

    const stream = client.messages.stream({
      model: modelId,
      max_tokens: maxTokens,
      system: systemPrompt,
      messages: messages as Anthropic.MessageParam[],
      tools,
      ...(withThinking && {
        thinking: { type: 'enabled', budget_tokens: THINKING_BUDGET },
      }),
    });

    let inThinkingBlock = false;

    for await (const event of stream) {
      if (event.type === 'content_block_start') {
        if (event.content_block.type === 'thinking') {
          inThinkingBlock = true;
          this.sseWrite(res, { type: 'thinking_start' });
        } else if (event.content_block.type === 'text') {
          inThinkingBlock = false;
        }
      } else if (event.type === 'content_block_delta') {
        const delta = event.delta as { type: string; thinking?: string; text?: string };
        if (delta.type === 'thinking_delta' && delta.thinking) {
          this.sseWrite(res, { type: 'thinking', content: delta.thinking });
        } else if (delta.type === 'text_delta' && delta.text) {
          this.sseWrite(res, { type: 'token', content: delta.text });
        }
      } else if (event.type === 'content_block_stop') {
        if (inThinkingBlock) {
          this.sseWrite(res, { type: 'thinking_end' });
          inThinkingBlock = false;
        }
      }
    }

    const finalMessage = await stream.finalMessage();

    return {
      content: finalMessage.content as LlmContentBlock[],
      model: modelId,
      usage: finalMessage.usage
        ? {
            input_tokens: finalMessage.usage.input_tokens,
            output_tokens: finalMessage.usage.output_tokens,
          }
        : null,
      stop_reason: finalMessage.stop_reason ?? 'end_turn',
    };
  }

  // ─── OpenAI ─────────────────────────────────────────────────────────────────

  private async sendToOpenAI(options: {
    modelId: string;
    systemPrompt: string;
    messages: Array<{ role: 'user' | 'assistant'; content: string | unknown[] }>;
    res: Response;
  }): Promise<LlmResponse> {
    const { modelId, systemPrompt, messages, res } = options;

    const client = this.getOpenAIClient();
    if (!client) throw new Error('OpenAI API key not configured');

    const tools = this.toolDefinitions.getToolsForProvider('openai') as OpenAI.Chat.ChatCompletionTool[];

    // Convert Anthropic-style messages to OpenAI format
    const openaiMessages: OpenAI.Chat.ChatCompletionMessageParam[] = [
      { role: 'system', content: systemPrompt },
      ...messages.map((msg) => ({
        role: (msg.role === 'assistant' ? 'assistant' : 'user') as 'user' | 'assistant',
        content:
          typeof msg.content === 'string'
            ? msg.content
            : JSON.stringify(msg.content),
      })),
    ];

    const stream = await client.chat.completions.create({
      model: modelId,
      messages: openaiMessages,
      tools: tools.length > 0 ? tools : undefined,
      stream: true,
    });

    let fullContent = '';
    const toolCallsMap: Map<
      number,
      { id: string; type: string; function: { name: string; arguments: string } }
    > = new Map();

    for await (const chunk of stream) {
      const delta = chunk.choices[0]?.delta;
      if (!delta) continue;

      if (delta.content) {
        fullContent += delta.content;
        this.sseWrite(res, { type: 'token', content: delta.content });
      }

      if (delta.tool_calls) {
        for (const tc of delta.tool_calls) {
          if (tc.index !== undefined) {
            if (!toolCallsMap.has(tc.index)) {
              toolCallsMap.set(tc.index, {
                id: tc.id ?? '',
                type: 'function',
                function: { name: '', arguments: '' },
              });
            }
            const existing = toolCallsMap.get(tc.index)!;
            if (tc.function?.name) existing.function.name = tc.function.name;
            if (tc.function?.arguments)
              existing.function.arguments += tc.function.arguments;
          }
        }
      }
    }

    // Normalise to Anthropic-like content blocks
    const content: LlmContentBlock[] = [];

    if (fullContent) {
      content.push({ type: 'text', text: fullContent });
    }

    for (const tc of toolCallsMap.values()) {
      let parsedInput: Record<string, unknown> = {};
      try {
        parsedInput = JSON.parse(tc.function.arguments || '{}') as Record<string, unknown>;
      } catch {
        this.logger.warn('Failed to parse tool call arguments; using empty input', {
          tool: tc.function.name,
        });
      }
      content.push({
        type: 'tool_use',
        id: tc.id,
        name: tc.function.name,
        input: parsedInput,
      });
    }

    return {
      content,
      model: modelId,
      usage: null, // OpenAI streaming doesn't return usage in chunks
      stop_reason: toolCallsMap.size > 0 ? 'tool_use' : 'end_turn',
    };
  }

  // ─── Helpers ─────────────────────────────────────────────────────────────────

  private getAnthropicClient(): Anthropic | null {
    if (!this.anthropicClient) {
      const apiKey = this.config.get<string>('anthropicApiKey');
      if (apiKey) this.anthropicClient = new Anthropic({ apiKey });
    }
    return this.anthropicClient;
  }

  private getOpenAIClient(): OpenAI | null {
    if (!this.openaiClient) {
      const apiKey = this.config.get<string>('openaiApiKey');
      if (apiKey) this.openaiClient = new OpenAI({ apiKey });
    }
    return this.openaiClient;
  }

  private sseWrite(res: Response, data: Record<string, unknown>): void {
    res.write(`data: ${JSON.stringify(data)}\n\n`);
  }
}
