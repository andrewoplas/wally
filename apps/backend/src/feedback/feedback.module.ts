import { Module } from '@nestjs/common';
import { FeedbackController } from './feedback.controller.js';
import { FeedbackService } from './feedback.service.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';

@Module({
  controllers: [FeedbackController],
  providers: [FeedbackService, WallyLoggerService],
})
export class FeedbackModule {}
