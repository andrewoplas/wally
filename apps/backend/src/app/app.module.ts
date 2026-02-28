import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import configuration from '../config/configuration.js';
import { ChatModule } from '../chat/chat.module.js';
import { LicenseModule } from '../license/license.module.js';
import { UsageModule } from '../usage/usage.module.js';
import { HealthModule } from '../health/health.module.js';

@Module({
  imports: [
    ConfigModule.forRoot({
      isGlobal: true,
      load: [configuration],
      envFilePath: ['.env.local', '.env'],
    }),
    ChatModule,
    LicenseModule,
    UsageModule,
    HealthModule,
  ],
})
export class AppModule {}
