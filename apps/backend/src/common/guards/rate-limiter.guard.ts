import {
  CanActivate,
  ExecutionContext,
  Injectable,
  HttpException,
  HttpStatus,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { Request } from 'express';
import { SupabaseService } from '../../supabase/supabase.service.js';
import { WallyLoggerService } from '../logger/wally-logger.service.js';

interface AuthenticatedRequest extends Request {
  siteId: string;
}

interface SiteWindow {
  requests: number[];
}

const ONE_MINUTE_MS = 60_000;

/**
 * Hybrid rate limiter:
 * - Per-minute: in-memory sliding window (fast, acceptable loss on restart)
 * - Per-day: Supabase-backed counter (persistent, works across instances)
 */
@Injectable()
export class RateLimiterGuard implements CanActivate {
  private readonly windows = new Map<string, SiteWindow>();
  private readonly evictionInterval: NodeJS.Timeout;

  constructor(
    private readonly configService: ConfigService,
    private readonly supabase: SupabaseService,
    private readonly logger: WallyLoggerService,
  ) {
    // Evict stale per-minute windows every hour to prevent unbounded Map growth.
    this.evictionInterval = setInterval(() => this.evictStaleWindows(), 3_600_000);
  }

  async canActivate(context: ExecutionContext): Promise<boolean> {
    const req = context.switchToHttp().getRequest<AuthenticatedRequest>();
    const siteId = req.siteId;

    // Skip rate limiting if siteId not set (auth guard handles that)
    if (!siteId) return true;

    const perMinuteLimit = this.configService.get<number>('rateLimitPerSitePerMinute', 30);
    const perDayLimit = this.configService.get<number>('rateLimitPerSitePerDay', 1000);

    // ── Per-minute check (in-memory) ─────────────────────────────────────────
    const now = Date.now();
    const window = this.getWindow(siteId);
    this.pruneOldRequests(window, now);

    const oneMinuteAgo = now - ONE_MINUTE_MS;
    const requestsLastMinute = window.requests.filter((ts) => ts > oneMinuteAgo).length;

    if (requestsLastMinute >= perMinuteLimit) {
      this.logger.logWithMeta('warn', 'Rate limit exceeded (per-minute)', {
        siteId,
        count: requestsLastMinute,
      });
      throw new HttpException(
        {
          error: 'rate_limit_exceeded',
          message: `Rate limit exceeded: ${perMinuteLimit} requests per minute`,
          retry_after: 60,
        },
        HttpStatus.TOO_MANY_REQUESTS,
      );
    }

    // ── Per-day check (Supabase) ──────────────────────────────────────────────
    const today = new Date().toISOString().slice(0, 10); // YYYY-MM-DD

    const { data: currentCount } = await this.supabase.client
      .from('rate_limits')
      .select('count')
      .eq('site_id', siteId)
      .eq('date', today)
      .single<{ count: number }>();

    if (currentCount && currentCount.count >= perDayLimit) {
      this.logger.logWithMeta('warn', 'Rate limit exceeded (per-day)', {
        siteId,
        count: currentCount.count,
      });
      throw new HttpException(
        {
          error: 'rate_limit_exceeded',
          message: `Daily rate limit exceeded: ${perDayLimit} requests per day`,
          retry_after: 3600,
        },
        HttpStatus.TOO_MANY_REQUESTS,
      );
    }

    // ── Record the request ────────────────────────────────────────────────────
    window.requests.push(now);

    // Increment day counter asynchronously (fire-and-forget; don't block the request)
    this.supabase.client
      .rpc('increment_rate_limit', { p_site_id: siteId, p_date: today })
      .then(({ error }) => {
        if (error) {
          this.logger.logWithMeta('error', 'Failed to increment rate limit counter', {
            siteId,
            error: error.message,
          });
        }
      });

    return true;
  }

  private getWindow(siteId: string): SiteWindow {
    if (!this.windows.has(siteId)) {
      this.windows.set(siteId, { requests: [] });
    }
    return this.windows.get(siteId)!;
  }

  private pruneOldRequests(window: SiteWindow, now: number): void {
    const oneMinuteAgo = now - ONE_MINUTE_MS;
    window.requests = window.requests.filter((ts) => ts > oneMinuteAgo);
  }

  private evictStaleWindows(): void {
    const cutoff = Date.now() - ONE_MINUTE_MS;
    for (const [siteId, win] of this.windows) {
      if (
        win.requests.length === 0 ||
        win.requests[win.requests.length - 1]! < cutoff
      ) {
        this.windows.delete(siteId);
      }
    }
  }

  /** Clean up the eviction timer when the module is destroyed. */
  onModuleDestroy(): void {
    clearInterval(this.evictionInterval);
  }
}
