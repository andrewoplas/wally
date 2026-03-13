import { Module } from '@nestjs/common';
import { ToolCallbackStore } from './tool-callback.store.js';
import { ToolCallbackController } from './tool-callback.controller.js';
import { McpToolFactory } from './mcp-tool.factory.js';
import { AgentBridgeService } from './agent-bridge.service.js';
import { KnowledgeModule } from '../knowledge/knowledge.module.js';
import { UsageModule } from '../usage/usage.module.js';
import { AuthGuard } from '../common/guards/auth.guard.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';

@Module({
  imports: [KnowledgeModule, UsageModule],
  controllers: [ToolCallbackController],
  providers: [ToolCallbackStore, McpToolFactory, AgentBridgeService, AuthGuard, WallyLoggerService],
  exports: [ToolCallbackStore, McpToolFactory, AgentBridgeService],
})
export class AgentModule {}
