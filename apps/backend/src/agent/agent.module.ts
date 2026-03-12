import { Module } from '@nestjs/common';
import { ToolCallbackStore } from './tool-callback.store.js';
import { ToolCallbackController } from './tool-callback.controller.js';
import { McpToolFactory } from './mcp-tool.factory.js';
import { AuthGuard } from '../common/guards/auth.guard.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';

@Module({
  controllers: [ToolCallbackController],
  providers: [ToolCallbackStore, McpToolFactory, AuthGuard, WallyLoggerService],
  exports: [ToolCallbackStore, McpToolFactory],
})
export class AgentModule {}
