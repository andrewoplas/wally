/**
 * ChatController
 *
 * POST /v1/chat
 *
 * Receives a user message + site context from the WP plugin and delegates
 * the full chat lifecycle (Agent SDK loop + SSE streaming) to AgentBridgeService.
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
import { ApiTags, ApiExcludeEndpoint } from '@nestjs/swagger';
import type { Request, Response } from 'express';
import { AuthGuard } from '../common/guards/auth.guard.js';
import { RateLimiterGuard } from '../common/guards/rate-limiter.guard.js';
import { AgentBridgeService } from '../agent/agent-bridge.service.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';
import { ChatRequestDto } from './dto/chat.dto.js';

@ApiTags('chat')
@Controller('v1/chat')
@UseGuards(AuthGuard, RateLimiterGuard)
export class ChatController {
  constructor(
    private readonly agentBridge: AgentBridgeService,
    private readonly logger: WallyLoggerService,
  ) {}

  @ApiExcludeEndpoint()
  @Post()
  @HttpCode(HttpStatus.OK)
  @UsePipes(new ValidationPipe({ whitelist: true, transform: true }))
  async chat(
    @Body() body: ChatRequestDto,
    @Req() req: Request,
    @Res() res: Response,
  ): Promise<void> {
    const { message, model, conversation_history, site_profile, tool_definitions, custom_system_prompt, recent_actions } =
      body;

    try {
      const siteId = (req as Request & { siteId?: string }).siteId ?? '';
      await this.agentBridge.runChat(
        { siteId, message, model, conversation_history, site_profile, tool_definitions, custom_system_prompt, recent_actions },
        res,
      );
    } catch (err) {
      const error = err as Error;
      this.logger.logWithMeta('error', 'Chat request failed', {
        error: error.message,
        siteId: (req as Request & { siteId?: string }).siteId,
      });
      if (!res.writableEnded) {
        res.write(
          `data: ${JSON.stringify({ type: 'error', message: 'An error occurred processing your request.' })}\n\n`,
        );
        res.end();
      }
    }
  }
}
