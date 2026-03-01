import { Module } from '@nestjs/common';
import { LicenseController } from './license.controller.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';

@Module({
  controllers: [LicenseController],
  providers: [WallyLoggerService],
  // SupabaseService is injected via the global SupabaseModule
})
export class LicenseModule {}
