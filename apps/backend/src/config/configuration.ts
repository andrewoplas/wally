export interface ModelConfig {
  provider: 'anthropic' | 'openai';
  modelId: string;
}

export interface WallyConfig {
  port: number;
  nodeEnv: string;
  anthropicApiKey: string;
  openaiApiKey: string;
  rateLimitPerSitePerMinute: number;
  rateLimitPerSitePerDay: number;
  skipLicenseValidation: boolean;
  models: Record<string, ModelConfig>;
}

export default (): WallyConfig => ({
  port: parseInt(process.env['PORT'] ?? '3000', 10),
  nodeEnv: process.env['NODE_ENV'] ?? 'development',

  anthropicApiKey: process.env['ANTHROPIC_API_KEY'] ?? '',
  openaiApiKey: process.env['OPENAI_API_KEY'] ?? '',

  rateLimitPerSitePerMinute: parseInt(
    process.env['RATE_LIMIT_PER_SITE_PER_MINUTE'] ?? '30',
    10,
  ),
  rateLimitPerSitePerDay: parseInt(
    process.env['RATE_LIMIT_PER_SITE_PER_DAY'] ?? '1000',
    10,
  ),

  skipLicenseValidation: process.env['SKIP_LICENSE_VALIDATION'] === 'true',

  models: {
    'claude-sonnet-4-6': { provider: 'anthropic', modelId: 'claude-sonnet-4-6' },
    'claude-haiku-4-5': {
      provider: 'anthropic',
      modelId: 'claude-haiku-4-5-20251001',
    },
    'gpt-4o': { provider: 'openai', modelId: 'gpt-4o' },
    'gpt-4o-mini': { provider: 'openai', modelId: 'gpt-4o-mini' },
  },
});
