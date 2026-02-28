import { Module } from '@nestjs/common';
import { LlmService } from './llm.service.js';
import { ToolDefinitionsService } from '../tools/tool-definitions.service.js';

@Module({
  providers: [LlmService, ToolDefinitionsService],
  exports: [LlmService],
})
export class LlmModule {}
