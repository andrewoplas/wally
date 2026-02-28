import { Module } from '@nestjs/common';
import { KnowledgeLoaderService } from './knowledge-loader.service.js';
import { PromptBuilderService } from './prompt-builder.service.js';
import { IntentClassifierService } from '../intent/intent-classifier.service.js';

@Module({
  providers: [KnowledgeLoaderService, PromptBuilderService, IntentClassifierService],
  exports: [KnowledgeLoaderService, PromptBuilderService],
})
export class KnowledgeModule {}
