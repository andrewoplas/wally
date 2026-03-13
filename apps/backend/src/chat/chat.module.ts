import { Module } from '@nestjs/common';
import { ChatController } from './chat.controller.js';
import { AgentModule } from '../agent/agent.module.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';
import { AuthGuard } from '../common/guards/auth.guard.js';
import { RateLimiterGuard } from '../common/guards/rate-limiter.guard.js';

@Module({
  imports: [AgentModule],
  controllers: [ChatController],
  providers: [WallyLoggerService, AuthGuard, RateLimiterGuard],
})
export class ChatModule {}
