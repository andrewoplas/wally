/**
 * LicenseController
 *
 * POST /v1/license/validate  — validate a license key; returns tier/features/expiry
 * POST /v1/license/activate  — activate or re-activate a site under a license key
 *
 * Both endpoints are public (no AuthGuard) — activation IS the auth setup step.
 */

import {
  Controller,
  Post,
  Body,
  HttpCode,
  HttpStatus,
  HttpException,
} from '@nestjs/common';
import { ApiTags, ApiOperation, ApiBody, ApiResponse } from '@nestjs/swagger';
import { ConfigService } from '@nestjs/config';
import { ValidateLicenseDto, LicenseResponseDto } from './dto/validate-license.dto.js';
import { ActivateLicenseDto, ActivateResponseDto } from './dto/activate-license.dto.js';
import { SupabaseService, type LicenseKeyRow } from '../supabase/supabase.service.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';
import type { WallyConfig } from '../config/configuration.js';

const DEFAULT_FEATURES: Record<string, Record<string, unknown>> = {
  free: {
    max_messages_per_day: 50,
    models_available: ['claude-haiku-4-5'],
    tool_categories: ['content', 'site'],
  },
  pro: {
    max_messages_per_day: 1000,
    models_available: ['claude-sonnet-4-6', 'claude-haiku-4-5', 'gpt-4o', 'gpt-4o-mini'],
    tool_categories: ['content', 'site', 'plugins', 'search', 'elementor'],
  },
  enterprise: {
    max_messages_per_day: 10000,
    models_available: ['claude-sonnet-4-6', 'claude-haiku-4-5', 'gpt-4o', 'gpt-4o-mini'],
    tool_categories: ['content', 'site', 'plugins', 'search', 'elementor'],
  },
};

@ApiTags('license')
@Controller('v1/license')
export class LicenseController {
  constructor(
    private readonly config: ConfigService<WallyConfig>,
    private readonly supabase: SupabaseService,
    private readonly logger: WallyLoggerService,
  ) {}

  @ApiOperation({ summary: 'Validate a license key' })
  @ApiBody({ type: ValidateLicenseDto })
  @ApiResponse({ status: 200, type: LicenseResponseDto })
  @ApiResponse({ status: 400, description: 'Missing license_key' })
  @ApiResponse({ status: 403, description: 'Invalid or expired license' })
  @Post('validate')
  @HttpCode(HttpStatus.OK)
  async validate(@Body() body: ValidateLicenseDto): Promise<LicenseResponseDto> {
    const { license_key } = body;

    if (!license_key) {
      throw new HttpException(
        { error: 'bad_request', message: 'Missing license_key' },
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

    const license = await this.lookupLicense(license_key);
    const features = DEFAULT_FEATURES[license.tier] ?? DEFAULT_FEATURES['free'];

    this.logger.logWithMeta('info', 'License validated', { tier: license.tier });

    return {
      valid: true,
      tier: license.tier,
      features,
      expires_at: license.expires_at,
    };
  }

  @ApiOperation({ summary: 'Activate or re-activate a site under a license key' })
  @ApiBody({ type: ActivateLicenseDto })
  @ApiResponse({ status: 200, type: ActivateResponseDto })
  @ApiResponse({ status: 400, description: 'Missing required fields' })
  @Post('activate')
  @HttpCode(HttpStatus.OK)
  async activate(@Body() body: ActivateLicenseDto): Promise<ActivateResponseDto> {
    const { license_key, site_id, domain } = body;

    if (!license_key || !site_id) {
      throw new HttpException(
        { error: 'bad_request', message: 'Missing license_key or site_id' },
        HttpStatus.BAD_REQUEST,
      );
    }

    const skipValidation = this.config.get<boolean>('skipLicenseValidation');
    if (skipValidation) {
      return {
        valid: true,
        tier: 'pro',
        features: DEFAULT_FEATURES['pro'] as ActivateResponseDto['features'],
        expires_at: null,
        site_count: 1,
        max_sites: 999,
      };
    }

    // Step 1: Look up and validate the license key
    const license = await this.lookupLicense(license_key);

    const features = DEFAULT_FEATURES[license.tier] ?? DEFAULT_FEATURES['free'];

    // Step 2: Count currently active sites for this license
    const { count: activeCount } = await this.supabase.client
      .from('sites')
      .select('id', { count: 'exact', head: true })
      .eq('license_key_id', license.id)
      .eq('is_active', true);

    const currentActiveCount = activeCount ?? 0;

    // Step 3: Look up existing site record
    const { data: existingSite } = await this.supabase.client
      .from('sites')
      .select('id, is_active')
      .eq('id', site_id)
      .eq('license_key_id', license.id)
      .single();

    if (existingSite) {
      if (existingSite.is_active) {
        // Already active — return success immediately
        this.logger.logWithMeta('info', 'Site already activated', { site_id });
        return {
          valid: true,
          tier: license.tier,
          features: features as ActivateResponseDto['features'],
          expires_at: license.expires_at,
          site_count: currentActiveCount,
          max_sites: license.max_sites,
        };
      }

      // Site exists but is deactivated — re-activate if under limit
      if (currentActiveCount >= license.max_sites) {
        return {
          valid: false,
          error: 'max_sites_reached',
          site_count: currentActiveCount,
          max_sites: license.max_sites,
        };
      }

      await this.supabase.client
        .from('sites')
        .update({ is_active: true, activated_at: new Date().toISOString() })
        .eq('id', site_id);

      this.logger.logWithMeta('info', 'Site re-activated', { site_id, tier: license.tier });
      return {
        valid: true,
        tier: license.tier,
        features: features as ActivateResponseDto['features'],
        expires_at: license.expires_at,
        site_count: currentActiveCount + 1,
        max_sites: license.max_sites,
      };
    }

    // Step 4: New site — insert if under limit
    if (currentActiveCount >= license.max_sites) {
      return {
        valid: false,
        error: 'max_sites_reached',
        site_count: currentActiveCount,
        max_sites: license.max_sites,
      };
    }

    await this.supabase.client.from('sites').insert({
      id: site_id,
      site_id,
      license_key_id: license.id,
      domain: domain ?? null,
      is_active: true,
      activated_at: new Date().toISOString(),
      license_tier: license.tier,
      license_expires_at: license.expires_at,
      features: {},
    });

    this.logger.logWithMeta('info', 'Site activated', { site_id, tier: license.tier });
    return {
      valid: true,
      tier: license.tier,
      features: features as ActivateResponseDto['features'],
      expires_at: license.expires_at,
      site_count: currentActiveCount + 1,
      max_sites: license.max_sites,
    };
  }

  private async lookupLicense(licenseKey: string): Promise<LicenseKeyRow> {
    const { data: license, error } = await this.supabase.client
      .from('license_keys')
      .select('*')
      .eq('key', licenseKey)
      .single<LicenseKeyRow>();

    if (error || !license) {
      throw new HttpException(
        { error: 'invalid_key', message: 'License key not found' },
        HttpStatus.FORBIDDEN,
      );
    }

    if (license.status === 'cancelled') {
      throw new HttpException(
        { error: 'license_cancelled', message: 'License has been cancelled' },
        HttpStatus.FORBIDDEN,
      );
    }

    if (license.expires_at && new Date(license.expires_at) < new Date()) {
      throw new HttpException(
        { error: 'license_expired', message: 'License has expired' },
        HttpStatus.FORBIDDEN,
      );
    }

    return license;
  }
}
