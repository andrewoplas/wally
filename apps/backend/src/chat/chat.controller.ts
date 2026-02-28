/**
 * ChatController
 *
 * POST /v1/chat
 *
 * Receives a user message + site context from the WP plugin.
 * Builds the LLM prompt, streams the response via SSE, and emits
 * `tool_call` events for any tools the LLM wants to invoke.
 */

import {
  Controller,
  Post,
  Body,
  Req,
  Res,
  HttpCode,
  UseGuards,
  UsePipes,
  ValidationPipe,
  HttpStatus,
} from '@nestjs/common';
import type { Request, Response } from 'express';
import { AuthGuard } from '../common/guards/auth.guard.js';
import { RateLimiterGuard } from '../common/guards/rate-limiter.guard.js';
import { LlmService } from '../llm/llm.service.js';
import { PromptBuilderService } from '../knowledge/prompt-builder.service.js';
import { ToolDefinitionsService } from '../tools/tool-definitions.service.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';
import { ChatRequestDto } from './dto/chat.dto.js';
import type { SiteProfile } from '../knowledge/prompt-builder.service.js';

const MAX_HISTORY_CONTENT = 4_000; // chars per history entry

@Controller('v1/chat')
@UseGuards(AuthGuard, RateLimiterGuard)
export class ChatController {
  constructor(
    private readonly llm: LlmService,
    private readonly promptBuilder: PromptBuilderService,
    private readonly toolDefinitions: ToolDefinitionsService,
    private readonly logger: WallyLoggerService,
  ) {}

  @Post()
  @HttpCode(HttpStatus.OK)
  @UsePipes(new ValidationPipe({ whitelist: true, transform: true }))
  async chat(
    @Body() body: ChatRequestDto,
    @Req() req: Request,
    @Res() res: Response,
  ): Promise<void> {
    const { model, message, conversation_history, site_profile, custom_system_prompt } =
      body;

    // Set up SSE headers
    res.setHeader('Content-Type', 'text/event-stream');
    res.setHeader('Cache-Control', 'no-cache');
    res.setHeader('Connection', 'keep-alive');
    res.setHeader('X-Accel-Buffering', 'no'); // Disable nginx buffering
    res.flushHeaders();

    try {
      const systemPrompt = this.promptBuilder.buildSystemPrompt(
        site_profile as SiteProfile | undefined,
        custom_system_prompt,
        message,
        conversation_history as Array<{ role: string; content: string }> | undefined,
      );

      // Build messages array: conversation history + new user message
      const messages: Array<{ role: 'user' | 'assistant'; content: string }> = [];

      if (Array.isArray(conversation_history)) {
        for (const msg of conversation_history as Array<{
          role: string;
          content: unknown;
        }>) {
          const content =
            typeof msg.content === 'string'
              ? msg.content.slice(0, MAX_HISTORY_CONTENT)
              : '';
          messages.push({
            role: msg.role === 'user' ? 'user' : 'assistant',
            content,
          });
        }
      }
      messages.push({ role: 'user', content: message });

      const response = await this.llm.sendToLLM({
        model,
        systemPrompt,
        messages,
        res,
      });

      // Emit tool_call events for each tool the LLM wants to use
      const toolUseBlocks = response.content.filter((b) => b.type === 'tool_use');
      const allTools = this.toolDefinitions.getAllTools();

      for (const toolCall of toolUseBlocks) {
        const toolDef = allTools.find((t) => t.name === toolCall.name);
        const requiresConfirmation = toolDef?.requires_confirmation ?? false;

        res.write(
          `data: ${JSON.stringify({
            type: 'tool_call',
            tool_call_id: toolCall.id,
            tool: toolCall.name,
            input: toolCall.input,
            requires_confirmation: requiresConfirmation,
            status: requiresConfirmation ? 'pending_confirmation' : 'execute',
          })}\n\n`,
        );
      }

      if (response.usage) {
        res.write(
          `data: ${JSON.stringify({
            type: 'usage',
            input_tokens: response.usage.input_tokens,
            output_tokens: response.usage.output_tokens,
          })}\n\n`,
        );
      }

      res.write(
        `data: ${JSON.stringify({ type: 'done', stop_reason: response.stop_reason })}\n\n`,
      );
      res.end();
    } catch (err) {
      const error = err as Error;
      this.logger.logWithMeta('error', 'Chat request failed', {
        error: error.message,
        siteId: (req as Request & { siteId?: string }).siteId,
      });
      res.write(
        `data: ${JSON.stringify({ type: 'error', message: 'An error occurred processing your request.' })}\n\n`,
      );
      res.end();
    }
  }
}
