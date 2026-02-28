/**
 * LicenseController
 *
 * POST /v1/license/validate
 *
 * Validates a site's license or API key and returns tier information.
 * Called by the plugin on activation and periodically for key validation.
 */

import {
  Controller,
  Post,
  Body,
  HttpCode,
  HttpStatus,
  HttpException,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';
import type { WallyConfig } from '../config/configuration.js';

@Controller('v1/license')
export class LicenseController {
  constructor(
    private readonly config: ConfigService<WallyConfig>,
    private readonly logger: WallyLoggerService,
  ) {}

  @Post('validate')
  @HttpCode(HttpStatus.OK)
  validate(
    @Body() body: { site_id?: string; api_key?: string },
  ): Record<string, unknown> {
    const { site_id, api_key } = body;

    if (!site_id || !api_key) {
      throw new HttpException(
        { error: 'bad_request', message: 'Missing site_id or api_key' },
        HttpStatus.BAD_REQUEST,
      );
    }

    const skipValidation = this.config.get<boolean>('skipLicenseValidation');
    if (skipValidation) {
      const rateLimitPerDay = this.config.get<number>('rateLimitPerSitePerDay') ?? 1000;
      const models = this.config.get<WallyConfig['models']>('models') ?? {};

      return {
        valid: true,
        tier: 'pro',
        features: {
          max_messages_per_day: rateLimitPerDay,
          models_available: Object.keys(models),
          tool_categories: ['content', 'site', 'plugins', 'search', 'elementor'],
        },
        expires_at: null,
      };
    }

    // Production: query license database
    // TODO: Implement license lookup against database or external service
    this.logger.logWithMeta('info', 'License validation request', { site_id });
    throw new HttpException(
      {
        error: 'not_implemented',
        message: 'License validation is not yet configured for production',
      },
      HttpStatus.NOT_IMPLEMENTED,
    );
  }
}
