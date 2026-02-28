import {
  CanActivate,
  ExecutionContext,
  Injectable,
  HttpException,
  HttpStatus,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { Request } from 'express';
import { WallyLoggerService } from '../logger/wally-logger.service.js';

interface AuthenticatedRequest extends Request {
  siteId: string;
}

interface SiteWindow {
  requests: number[];
}

const ONE_MINUTE_MS = 60_000;
const ONE_DAY_MS = 86_400_000;

/**
 * In-memory sliding-window rate limiter keyed by site_id.
 * Enforces per-minute and per-day limits from config.
 *
 * Note: For multi-instance deployments, replace with Redis-backed counters.
 */
@Injectable()
export class RateLimiterGuard implements CanActivate {
  private readonly windows = new Map<string, SiteWindow>();
  private readonly evictionInterval: NodeJS.Timeout;

  constructor(
    private readonly configService: ConfigService,
    private readonly logger: WallyLoggerService,
  ) {
    // Evict stale windows every hour to prevent unbounded Map growth.
    this.evictionInterval = setInterval(() => this.evictStaleWindows(), 3_600_000);
  }

  canActivate(context: ExecutionContext): boolean {
    const req = context.switchToHttp().getRequest<AuthenticatedRequest>();
    const siteId = req.siteId;

    // Skip rate limiting if siteId not set (auth guard handles that)
    if (!siteId) return true;

    const now = Date.now();
    const window = this.getWindow(siteId);
    this.pruneOldRequests(window, now);

    const perMinuteLimit = this.configService.get<number>('rateLimitPerSitePerMinute', 30);
    const perDayLimit = this.configService.get<number>('rateLimitPerSitePerDay', 1000);

    const oneMinuteAgo = now - ONE_MINUTE_MS;
    const requestsLastMinute = window.requests.filter((ts) => ts > oneMinuteAgo).length;
    const requestsLastDay = window.requests.length;

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

    if (requestsLastDay >= perDayLimit) {
      this.logger.logWithMeta('warn', 'Rate limit exceeded (per-day)', {
        siteId,
        count: requestsLastDay,
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

    window.requests.push(now);
    return true;
  }

  private getWindow(siteId: string): SiteWindow {
    if (!this.windows.has(siteId)) {
      this.windows.set(siteId, { requests: [] });
    }
    return this.windows.get(siteId)!;
  }

  private pruneOldRequests(window: SiteWindow, now: number): void {
    const oneDayAgo = now - ONE_DAY_MS;
    window.requests = window.requests.filter((ts) => ts > oneDayAgo);
  }

  private evictStaleWindows(): void {
    const cutoff = Date.now() - ONE_DAY_MS;
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
