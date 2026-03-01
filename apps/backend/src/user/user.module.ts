import { Module } from '@nestjs/common';
import { UserController } from './user.controller.js';
import { UserAuthGuard } from '../common/guards/user-auth.guard.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';

@Module({
  controllers: [UserController],
  providers: [UserAuthGuard, WallyLoggerService],
  // SupabaseService is injected via the global SupabaseModule
})
export class UserModule {}
