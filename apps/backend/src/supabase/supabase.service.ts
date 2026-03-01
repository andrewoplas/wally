import { Injectable, OnModuleInit } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { createClient, SupabaseClient } from '@supabase/supabase-js';
import type { WallyConfig } from '../config/configuration.js';

export interface LicenseKeyRow {
  id: string;
  user_id: string;
  key: string;
  tier: string;
  max_sites: number;
  expires_at: string | null;
  status: string;
  created_at: string;
  updated_at: string;
}

export interface SiteRow {
  id: string;
  site_id: string;
  api_key_hash?: string | null;
  license_key_id?: string;
  domain: string | null;
  license_tier: string;
  license_expires_at: string | null;
  features: Record<string, unknown>;
  is_active: boolean;
  activated_at?: string;
}

export interface UsageRow {
  site_id: string;
  month: string;
  input_tokens: number;
  output_tokens: number;
  requests: number;
}

@Injectable()
export class SupabaseService implements OnModuleInit {
  private _client!: SupabaseClient;

  constructor(private readonly config: ConfigService<WallyConfig>) {}

  onModuleInit(): void {
    const url = this.config.get<string>('supabase.url' as never) ?? '';
    const key = this.config.get<string>('supabase.serviceRoleKey' as never) ?? '';
    this._client = createClient(url, key, {
      auth: { persistSession: false },
    });
  }

  get client(): SupabaseClient {
    return this._client;
  }
}
