/**
 * HealthController
 *
 * GET /health
 *
 * Simple health check endpoint. No authentication required.
 * Used by load balancers and monitoring tools.
 */

import { Controller, Get } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse, ApiProperty } from '@nestjs/swagger';

class HealthResponseDto {
  @ApiProperty({ example: 'ok' })
  status!: string;

  @ApiProperty({ example: '2026-03-01T00:00:00.000Z' })
  timestamp!: string;
}

@ApiTags('health')
@Controller('health')
export class HealthController {
  @ApiOperation({ summary: 'Health check' })
  @ApiResponse({ status: 200, type: HealthResponseDto })
  @Get()
  check(): { status: string; timestamp: string } {
    return {
      status: 'ok',
      timestamp: new Date().toISOString(),
    };
  }
}
