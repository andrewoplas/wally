import {
  CanActivate,
  ExecutionContext,
  Injectable,
  UnauthorizedException,
  ForbiddenException,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { Request } from 'express';
import { WallyLoggerService } from '../logger/wally-logger.service.js';

interface AuthenticatedRequest extends Request {
  siteId: string;
  apiKey: string;
}

/**
 * Validates X-Site-ID and X-API-Key on every protected request.
 *
 * In development (SKIP_LICENSE_VALIDATION=true), any non-empty credentials are accepted.
 * In production, this is the point where a real license check would happen.
 */
@Injectable()
export class AuthGuard implements CanActivate {
  constructor(
    private readonly configService: ConfigService,
    private readonly logger: WallyLoggerService,
  ) {}

  canActivate(context: ExecutionContext): boolean {
    const req = context.switchToHttp().getRequest<AuthenticatedRequest>();

    const siteId =
      (req.headers['x-site-id'] as string | undefined) ?? req.body?.site_id;
    const apiKey =
      (req.headers['x-api-key'] as string | undefined) ?? req.body?.api_key;

    if (!siteId || !apiKey) {
      this.logger.logWithMeta('warn', 'Auth failed: missing site_id or api_key', {
        ip: req.ip,
      });
      throw new UnauthorizedException({
        error: 'unauthorized',
        message: 'Missing site_id or api_key',
      });
    }

    const skipLicenseValidation = this.configService.get<boolean>(
      'skipLicenseValidation',
      false,
    );

    if (skipLicenseValidation) {
      req.siteId = siteId;
      req.apiKey = apiKey;
      return true;
    }

    // Production: validate against license service
    // TODO: Query license database or external service
    this.logger.logWithMeta(
      'warn',
      'License validation enabled but not implemented',
      { siteId },
    );
    throw new ForbiddenException({
      error: 'license_invalid',
      message: 'License validation is not yet configured',
    });
  }
}
