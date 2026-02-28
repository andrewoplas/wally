import { Module } from '@nestjs/common';
import { LicenseController } from './license.controller.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';

@Module({
  controllers: [LicenseController],
  providers: [WallyLoggerService],
})
export class LicenseModule {}
