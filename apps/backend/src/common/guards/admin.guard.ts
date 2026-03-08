import {
  CanActivate,
  ExecutionContext,
  Injectable,
  UnauthorizedException,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { Request } from 'express';
import { timingSafeEqual } from 'crypto';

@Injectable()
export class AdminGuard implements CanActivate {
  constructor(private readonly configService: ConfigService) {}

  canActivate(context: ExecutionContext): boolean {
    const req = context.switchToHttp().getRequest<Request>();
    const providedKey = req.headers['x-admin-key'] as string | undefined;
    const expectedKey = this.configService.get<string>('adminApiKey', '');

    if (!providedKey || !expectedKey) {
      throw new UnauthorizedException({ error: 'unauthorized', message: 'Missing admin key' });
    }

    const a = Buffer.from(providedKey);
    const b = Buffer.from(expectedKey);

    if (a.length !== b.length || !timingSafeEqual(a, b)) {
      throw new UnauthorizedException({ error: 'unauthorized', message: 'Invalid admin key' });
    }

    return true;
  }
}
