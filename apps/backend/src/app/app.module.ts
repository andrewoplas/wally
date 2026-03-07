import { Module } from '@nestjs/common';
import { APP_FILTER } from '@nestjs/core';
import { ConfigModule } from '@nestjs/config';
import { SentryModule } from '@sentry/nestjs/setup';
import { SentryGlobalFilter } from '@sentry/nestjs/setup';
import configuration from '../config/configuration.js';
import { SupabaseModule } from '../supabase/supabase.module.js';
import { ChatModule } from '../chat/chat.module.js';
import { LicenseModule } from '../license/license.module.js';
import { UsageModule } from '../usage/usage.module.js';
import { HealthModule } from '../health/health.module.js';
import { UserModule } from '../user/user.module.js';
import { FeedbackModule } from '../feedback/feedback.module.js';

@Module({
  imports: [
    SentryModule.forRoot(),
    ConfigModule.forRoot({
      isGlobal: true,
      load: [configuration],
      envFilePath: ['.env.local', '.env'],
    }),
    SupabaseModule,
    ChatModule,
    LicenseModule,
    UsageModule,
    HealthModule,
    UserModule,
    FeedbackModule,
  ],
  providers: [
    {
      provide: APP_FILTER,
      useClass: SentryGlobalFilter,
    },
  ],
})
export class AppModule {}
