/**
 * UsageService
 *
 * In-memory token usage tracking. Records per-site usage data and exposes
 * methods to retrieve usage stats.
 *
 * In production, replace the in-memory store with a persistent backend
 * (Postgres, Redis, etc.).
 */

import { Injectable } from '@nestjs/common';

export interface SiteUsage {
  total_input_tokens: number;
  total_output_tokens: number;
  requests: number;
  monthly_input_tokens: number;
  monthly_output_tokens: number;
  month: string; // YYYY-MM
}

@Injectable()
export class UsageService {
  private readonly store = new Map<string, SiteUsage>();

  /**
   * Record token usage for a site after an LLM response.
   */
  recordUsage(
    siteId: string,
    inputTokens: number,
    outputTokens: number,
  ): void {
    if (!this.store.has(siteId)) {
      this.store.set(siteId, {
        total_input_tokens: 0,
        total_output_tokens: 0,
        requests: 0,
        monthly_input_tokens: 0,
        monthly_output_tokens: 0,
        month: this.currentMonth(),
      });
    }

    const usage = this.store.get(siteId)!;
    const currentMonth = this.currentMonth();

    // Reset monthly counters if the month rolled over
    if (usage.month !== currentMonth) {
      usage.monthly_input_tokens = 0;
      usage.monthly_output_tokens = 0;
      usage.month = currentMonth;
    }

    usage.total_input_tokens += inputTokens || 0;
    usage.total_output_tokens += outputTokens || 0;
    usage.monthly_input_tokens += inputTokens || 0;
    usage.monthly_output_tokens += outputTokens || 0;
    usage.requests += 1;
  }

  /**
   * Retrieve usage stats for a site. Returns zero-value stats if not found.
   */
  getUsage(siteId: string): SiteUsage & { site_id: string } {
    const usage = this.store.get(siteId);
    if (!usage) {
      return {
        site_id: siteId,
        total_input_tokens: 0,
        total_output_tokens: 0,
        requests: 0,
        monthly_input_tokens: 0,
        monthly_output_tokens: 0,
        month: this.currentMonth(),
      };
    }
    return { site_id: siteId, ...usage };
  }

  private currentMonth(): string {
    return new Date().toISOString().slice(0, 7); // YYYY-MM
  }
}
