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
import { ApiTags, ApiOperation, ApiParam, ApiResponse } from '@nestjs/swagger';
import type { Request } from 'express';
import { AuthGuard } from '../common/guards/auth.guard.js';
import { UsageService } from './usage.service.js';
import { UsageResponseDto } from './dto/usage-response.dto.js';

@ApiTags('usage')
@Controller('v1/usage')
@UseGuards(AuthGuard)
export class UsageController {
  constructor(private readonly usageService: UsageService) {}

  @ApiOperation({ summary: 'Get token usage stats for a site' })
  @ApiParam({ name: 'site_id', description: 'Site identifier' })
  @ApiResponse({ status: 200, type: UsageResponseDto })
  @ApiResponse({ status: 403, description: 'Cannot view another site usage' })
  @Get(':site_id')
  async getUsage(
    @Param('site_id') siteId: string,
    @Req() req: Request,
  ): Promise<UsageResponseDto> {
    const requestingSiteId = (req as Request & { siteId?: string }).siteId;

    // Sites can only view their own usage
    if (requestingSiteId !== siteId) {
      throw new ForbiddenException('Cannot view usage for another site');
    }

    return this.usageService.getUsage(siteId);
  }
}
