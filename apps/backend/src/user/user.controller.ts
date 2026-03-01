import {
  Controller,
  Delete,
  Get,
  HttpCode,
  HttpStatus,
  InternalServerErrorException,
  Param,
  Req,
  UseGuards,
} from '@nestjs/common';
import { SupabaseService } from '../supabase/supabase.service.js';
import { UserAuthGuard, type UserAuthenticatedRequest } from '../common/guards/user-auth.guard.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';
import type { UserLicenseResponseDto } from './dto/user-license.response.dto.js';

@Controller('v1/user')
@UseGuards(UserAuthGuard)
export class UserController {
  constructor(
    private readonly supabase: SupabaseService,
    private readonly logger: WallyLoggerService,
  ) {}

  /** GET /v1/user/license — returns license + activated sites for the authenticated user */
  @Get('license')
  async getLicense(@Req() req: UserAuthenticatedRequest): Promise<UserLicenseResponseDto> {
    const userId = req.userId;

    let { data: license } = await this.supabase.client
      .from('license_keys')
      .select('*')
      .eq('user_id', userId)
      .maybeSingle();

    if (!license) {
      const newKey = `wally_live_sk_${crypto.randomUUID().replace(/-/g, '')}`;
      const { data: created, error } = await this.supabase.client
        .from('license_keys')
        .insert({ user_id: userId, key: newKey, tier: 'free', max_sites: 1 })
        .select()
        .single();

      if (error) {
        this.logger.logWithMeta('error', 'Failed to create free license key', { userId, error: error.message });
        throw new InternalServerErrorException('Failed to initialize license');
      }

      license = created;
    }

    if (!license) {
      return {
        id: null, key: null, tier: 'free', max_sites: 1,
        expires_at: null, status: 'active', activated_count: 0, sites: [],
      };
    }

    const { data: sites } = await this.supabase.client
      .from('sites')
      .select('id, domain, is_active, activated_at, license_expires_at')
      .eq('license_key_id', license.id)
      .eq('is_active', true)
      .order('activated_at', { ascending: false });

    const activeSites = sites ?? [];

    return {
      id: license.id,
      key: license.key,
      tier: license.tier,
      max_sites: license.max_sites,
      expires_at: license.expires_at,
      status: license.status,
      activated_count: activeSites.length,
      sites: activeSites,
    };
  }

  /** DELETE /v1/user/sites/:siteId — deactivate a site (scoped to user_id) */
  @Delete('sites/:siteId')
  @HttpCode(HttpStatus.OK)
  async deactivateSite(
    @Req() req: UserAuthenticatedRequest,
    @Param('siteId') siteId: string,
  ): Promise<{ success: boolean }> {
    const userId = req.userId;

    const { error } = await this.supabase.client
      .from('sites')
      .update({ is_active: false })
      .eq('id', siteId)
      .eq('user_id', userId);

    if (error) {
      this.logger.logWithMeta('error', 'Failed to deactivate site', { userId, siteId, error: error.message });
      throw new InternalServerErrorException('Failed to deactivate site');
    }

    return { success: true };
  }
}
