/**
 * UsageController
 *
 * GET /v1/usage/:site_id
 *
 * Returns token usage stats for the requesting site.
 * Sites can only view their own usage (enforced by AuthGuard + site isolation).
 */

import {
  Controller,
  Get,
  Param,
  Req,
  UseGuards,
  ForbiddenException,
} from '@nestjs/common';
import type { Request } from 'express';
import { AuthGuard } from '../common/guards/auth.guard.js';
import { UsageService } from './usage.service.js';

@Controller('v1/usage')
@UseGuards(AuthGuard)
export class UsageController {
  constructor(private readonly usageService: UsageService) {}

  @Get(':site_id')
  getUsage(
    @Param('site_id') siteId: string,
    @Req() req: Request,
  ): Record<string, unknown> {
    const requestingSiteId = (req as Request & { siteId?: string }).siteId;

    // Sites can only view their own usage
    if (requestingSiteId !== siteId) {
      throw new ForbiddenException('Cannot view usage for another site');
    }

    return this.usageService.getUsage(siteId);
  }
}
