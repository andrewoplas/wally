import { Injectable } from '@nestjs/common';
import { SupabaseService } from '../supabase/supabase.service.js';
import { WallyLoggerService } from '../common/logger/wally-logger.service.js';

export interface FeedbackInsert {
  type: 'rating' | 'general';
  rating?: string | null;
  message?: string | null;
  category?: string | null;
  email?: string | null;
  name?: string | null;
  source: 'plugin' | 'website';
  site_id?: string | null;
  conversation_id?: string | null;
  message_id?: string | null;
}

@Injectable()
export class FeedbackService {
  constructor(
    private readonly supabase: SupabaseService,
    private readonly logger: WallyLoggerService,
  ) {}

  async submit(data: FeedbackInsert): Promise<{ success: boolean }> {
    const { error } = await this.supabase.client
      .from('feedback')
      .insert({
        type: data.type,
        rating: data.rating ?? null,
        message: data.message ?? null,
        category: data.category ?? null,
        email: data.email ?? null,
        name: data.name ?? null,
        source: data.source,
        site_id: data.site_id ?? null,
        conversation_id: data.conversation_id ?? null,
        message_id: data.message_id ?? null,
      });

    if (error) {
      this.logger.logWithMeta('error', 'Failed to insert feedback', {
        error: error.message,
        type: data.type,
        source: data.source,
      });
      throw new Error('Failed to save feedback');
    }

    this.logger.logWithMeta('info', 'Feedback submitted', {
      type: data.type,
      source: data.source,
    });

    return { success: true };
  }
}
