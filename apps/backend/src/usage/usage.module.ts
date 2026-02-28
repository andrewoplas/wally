import { Module } from '@nestjs/common';
import { UsageController } from './usage.controller.js';
import { UsageService } from './usage.service.js';
import { AuthGuard } from '../common/guards/auth.guard.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';

@Module({
  controllers: [UsageController],
  providers: [UsageService, AuthGuard, WallyLoggerService],
  exports: [UsageService],
})
export class UsageModule {}
