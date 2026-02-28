/**
 * ToolResultController
 *
 * POST /v1/tool-result
 *
 * After the WP plugin executes a tool locally, it sends the result here.
 * The backend feeds the tool result back to the LLM to continue the
 * tool-use loop or generate the final text response.
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
import { MessageBuilderService } from '../common/message-builder.service.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';
import { ToolResultRequestDto } from './dto/tool-result.dto.js';
import type { SiteProfile } from '../knowledge/prompt-builder.service.js';

const MAX_HISTORY_CONTENT = 4_000;

@Controller('v1/tool-result')
@UseGuards(AuthGuard, RateLimiterGuard)
export class ToolResultController {
  constructor(
    private readonly llm: LlmService,
    private readonly promptBuilder: PromptBuilderService,
    private readonly messageBuilder: MessageBuilderService,
    private readonly logger: WallyLoggerService,
  ) {}

  @Post()
  @HttpCode(HttpStatus.OK)
  @UsePipes(new ValidationPipe({ whitelist: true, transform: true }))
  async toolResult(
    @Body() body: ToolResultRequestDto,
    @Req() req: Request,
    @Res() res: Response,
  ): Promise<void> {
    const {
      model,
      conversation_history,
      site_profile,
      custom_system_prompt,
      tool_results,
      pending_tool_calls,
    } = body;

    // Set up SSE headers
    res.setHeader('Content-Type', 'text/event-stream');
    res.setHeader('Cache-Control', 'no-cache');
    res.setHeader('Connection', 'keep-alive');
    res.setHeader('X-Accel-Buffering', 'no');
    res.flushHeaders();

    try {
      // Extract the last user message from history for intent classification
      const history = (conversation_history ?? []) as Array<{
        role: string;
        content: unknown;
      }>;
      const lastUserMessage =
        history
          .filter((m) => m.role === 'user')
          .map((m) =>
            typeof m.content === 'string'
              ? m.content.slice(0, MAX_HISTORY_CONTENT)
              : '',
          )
          .pop() ?? '';

      const systemPrompt = this.promptBuilder.buildSystemPrompt(
        site_profile as SiteProfile | undefined,
        custom_system_prompt,
        lastUserMessage || null,
        conversation_history as Array<{ role: string; content: string }> | undefined,
      );

      const messages = this.messageBuilder.buildToolResultMessages(
        conversation_history as Array<{ role: string; content: unknown; tool_call_id?: string; is_error?: boolean }> | undefined,
        pending_tool_calls as Array<{ tool_call_id: string; tool: string; input: unknown }> | undefined,
        tool_results as Array<{ tool_call_id: string; tool_name: string; result: unknown; is_error?: boolean }>,
      );

      const response = await this.llm.sendToLLM({
        model,
        systemPrompt,
        messages: messages as Array<{ role: 'user' | 'assistant'; content: string | unknown[] }>,
        res,
      });

      // Continue tool-use loop if the LLM wants more tools
      const toolUseBlocks = response.content.filter((b) => b.type === 'tool_use');
      for (const toolCall of toolUseBlocks) {
        res.write(
          `data: ${JSON.stringify({
            type: 'tool_call',
            tool_call_id: toolCall.id,
            tool: toolCall.name,
            input: toolCall.input,
            status: 'execute',
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
      this.logger.logWithMeta('error', 'Tool result processing failed', {
        error: error.message,
        siteId: (req as Request & { siteId?: string }).siteId,
      });
      res.write(
        `data: ${JSON.stringify({ type: 'error', message: 'An error occurred processing the tool result.' })}\n\n`,
      );
      res.end();
    }
  }
}
