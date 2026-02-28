import { Module } from '@nestjs/common';
import { ChatController } from './chat.controller.js';
import { ToolResultController } from './tool-result.controller.js';
import { LlmModule } from '../llm/llm.module.js';
import { KnowledgeModule } from '../knowledge/knowledge.module.js';
import { MessageBuilderService } from '../common/message-builder.service.js';
import { ToolDefinitionsService } from '../tools/tool-definitions.service.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';
import { AuthGuard } from '../common/guards/auth.guard.js';
import { RateLimiterGuard } from '../common/guards/rate-limiter.guard.js';

@Module({
  imports: [LlmModule, KnowledgeModule],
  controllers: [ChatController, ToolResultController],
  providers: [
    MessageBuilderService,
    ToolDefinitionsService,
    WallyLoggerService,
    AuthGuard,
    RateLimiterGuard,
  ],
})
export class ChatModule {}
