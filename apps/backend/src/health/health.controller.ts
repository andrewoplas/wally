/**
 * HealthController
 *
 * GET /health
 *
 * Simple health check endpoint. No authentication required.
 * Used by load balancers and monitoring tools.
 */

import { Controller, Get } from '@nestjs/common';

@Controller('health')
export class HealthController {
  @Get()
  check(): { status: string; timestamp: string } {
    return {
      status: 'ok',
      timestamp: new Date().toISOString(),
    };
  }
}
