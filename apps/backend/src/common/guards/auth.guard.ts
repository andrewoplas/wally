import {
  CanActivate,
  ExecutionContext,
  Injectable,
  UnauthorizedException,
  ForbiddenException,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { Request } from 'express';
import { SupabaseService, type LicenseKeyRow, type SiteRow } from '../../supabase/supabase.service.js';
import { WallyLoggerService } from '../logger/wally-logger.service.js';

export interface LicenseInfo {
  tier: string;
  features: Record<string, unknown>;
  expiresAt: string | null;
}

interface AuthenticatedRequest extends Request {
  siteId: string;
  licenseKey: string;
  licenseInfo?: LicenseInfo;
}

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

/**
 * Validates X-License-Key + X-Site-ID on every protected request.
 *
 * Flow:
 *  1. Read X-License-Key header (body fallback: license_key)
 *  2. SKIP_LICENSE_VALIDATION=true → mock pro tier, return true
 *  3. Look up license_keys WHERE key = licenseKey → validate status + expiration
 *  4. Look up sites WHERE id = siteId AND license_key_id = license.id
 *     → site.is_active=false → 403 "site_deactivated"
 *     → site not found      → 403 "site_not_activated"
 *     → site.is_active=true → attach licenseInfo and proceed
 */
@Injectable()
export class AuthGuard implements CanActivate {
  constructor(
    private readonly configService: ConfigService,
    private readonly supabase: SupabaseService,
    private readonly logger: WallyLoggerService,
  ) {}

  async canActivate(context: ExecutionContext): Promise<boolean> {
    const req = context.switchToHttp().getRequest<AuthenticatedRequest>();

    const siteId =
      (req.headers['x-site-id'] as string | undefined) ?? req.body?.site_id;
    const licenseKey =
      (req.headers['x-license-key'] as string | undefined) ?? req.body?.license_key;

    if (!siteId || !licenseKey) {
      this.logger.logWithMeta('warn', 'Auth failed: missing site_id or license_key', {
        ip: req.ip,
      });
      throw new UnauthorizedException({
        error: 'unauthorized',
        message: 'Missing site_id or license_key',
      });
    }

    const skipLicenseValidation = this.configService.get<boolean>(
      'skipLicenseValidation',
      false,
    );

    if (skipLicenseValidation) {
      req.siteId = siteId;
      req.licenseKey = licenseKey;
      req.licenseInfo = {
        tier: 'pro',
        features: DEFAULT_FEATURES['pro'],
        expiresAt: null,
      };
      return true;
    }

    const { license, site } = await this.validateLicenseAndSite(siteId, licenseKey);

    req.siteId = siteId;
    req.licenseKey = licenseKey;
    req.licenseInfo = {
      tier: license.tier,
      features: DEFAULT_FEATURES[license.tier] ?? DEFAULT_FEATURES['free'],
      expiresAt: license.expires_at,
    };

    // Suppress unused var warning — site is validated above
    void site;

    return true;
  }

  private async validateLicenseAndSite(
    siteId: string,
    licenseKey: string,
  ): Promise<{ license: LicenseKeyRow; site: SiteRow }> {
    // Step 1: Look up license key
    const { data: license, error: licenseError } = await this.supabase.client
      .from('license_keys')
      .select('*')
      .eq('key', licenseKey)
      .single<LicenseKeyRow>();

    if (licenseError || !license) {
      this.logger.logWithMeta('warn', 'Auth failed: license key not found', { siteId });
      throw new ForbiddenException({
        error: 'license_invalid',
        message: 'Invalid license key',
      });
    }

    // Step 2: Validate license status and expiration
    if (license.status !== 'active') {
      this.logger.logWithMeta('warn', 'Auth failed: license cancelled', { siteId });
      throw new ForbiddenException({
        error: 'license_cancelled',
        message: 'License has been cancelled',
      });
    }

    if (license.expires_at && new Date(license.expires_at) < new Date()) {
      this.logger.logWithMeta('warn', 'Auth failed: license expired', { siteId });
      throw new ForbiddenException({
        error: 'license_expired',
        message: 'License has expired',
      });
    }

    // Step 3: Look up site associated with this license
    const { data: site, error: siteError } = await this.supabase.client
      .from('sites')
      .select('*')
      .eq('id', siteId)
      .eq('license_key_id', license.id)
      .single<SiteRow>();

    if (siteError || !site) {
      this.logger.logWithMeta('warn', 'Auth failed: site not activated', { siteId });
      throw new ForbiddenException({
        error: 'site_not_activated',
        message: 'Site is not activated. Save your license key in plugin settings to activate.',
      });
    }

    if (!site.is_active) {
      this.logger.logWithMeta('warn', 'Auth failed: site deactivated', { siteId });
      throw new ForbiddenException({
        error: 'site_deactivated',
        message: 'Site has been deactivated. Re-save your license key in plugin settings to reactivate.',
      });
    }

    return { license, site };
  }
}
