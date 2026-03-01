import { Injectable } from '@nestjs/common';
import { SupabaseService } from '../supabase/supabase.service.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';

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
  constructor(
    private readonly supabase: SupabaseService,
    private readonly logger: WallyLoggerService,
  ) {}

  /**
   * Record token usage for a site after an LLM response.
   * Calls the increment_usage RPC to atomically upsert the monthly row.
   */
  async recordUsage(
    siteId: string,
    inputTokens: number,
    outputTokens: number,
  ): Promise<void> {
    const { error } = await this.supabase.client.rpc('increment_usage', {
      p_site_id: siteId,
      p_month: this.currentMonth(),
      p_input_tokens: inputTokens || 0,
      p_output_tokens: outputTokens || 0,
    });

    if (error) {
      this.logger.logWithMeta('error', 'Failed to record usage', {
        siteId,
        error: error.message,
      });
    }
  }

  /**
   * Retrieve monthly usage stats for a site. Returns zero-value stats if not found.
   */
  async getUsage(siteId: string): Promise<SiteUsage & { site_id: string }> {
    const month = this.currentMonth();
    const { data } = await this.supabase.client
      .from('usage')
      .select('input_tokens, output_tokens, requests')
      .eq('site_id', siteId)
      .eq('month', month)
      .single();

    if (!data) {
      return {
        site_id: siteId,
        total_input_tokens: 0,
        total_output_tokens: 0,
        requests: 0,
        monthly_input_tokens: 0,
        monthly_output_tokens: 0,
        month,
      };
    }

    return {
      site_id: siteId,
      total_input_tokens: data.input_tokens,
      total_output_tokens: data.output_tokens,
      requests: data.requests,
      monthly_input_tokens: data.input_tokens,
      monthly_output_tokens: data.output_tokens,
      month,
    };
  }

  private currentMonth(): string {
    return new Date().toISOString().slice(0, 7); // YYYY-MM
  }
}
