import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import configuration from '../config/configuration.js';
import { SupabaseModule } from '../supabase/supabase.module.js';
import { ChatModule } from '../chat/chat.module.js';
import { LicenseModule } from '../license/license.module.js';
import { UsageModule } from '../usage/usage.module.js';
import { HealthModule } from '../health/health.module.js';
import { UserModule } from '../user/user.module.js';

@Module({
  imports: [
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
  ],
})
export class AppModule {}
