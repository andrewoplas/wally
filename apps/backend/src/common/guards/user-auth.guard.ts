import {
  CanActivate,
  ExecutionContext,
  Injectable,
  UnauthorizedException,
} from '@nestjs/common';
import { Request } from 'express';
import { SupabaseService } from '../../supabase/supabase.service.js';

export interface UserAuthenticatedRequest extends Request {
  userId: string;
}

/**
 * Validates the user JWT token from the Authorization header.
 *
 * Flow:
 *  1. Read Authorization: Bearer <token> header
 *  2. Call supabase.auth.getUser(token) to validate JWT
 *  3. Attach req.userId on success
 *  4. Throw UnauthorizedException if missing or invalid
 */
@Injectable()
export class UserAuthGuard implements CanActivate {
  constructor(private readonly supabase: SupabaseService) {}

  async canActivate(context: ExecutionContext): Promise<boolean> {
    const req = context.switchToHttp().getRequest<UserAuthenticatedRequest>();

    const authHeader = req.headers['authorization'] as string | undefined;
    if (!authHeader?.startsWith('Bearer ')) {
      throw new UnauthorizedException('Missing or invalid Authorization header');
    }

    const token = authHeader.slice(7);
    const { data: { user }, error } = await this.supabase.client.auth.getUser(token);

    if (error || !user) {
      throw new UnauthorizedException('Invalid or expired token');
    }

    req.userId = user.id;
    return true;
  }
}
