-- Wally Backend: Initial Schema
-- Run this in the Supabase SQL editor.

-- ── sites ─────────────────────────────────────────────────────────────────────
-- Each row represents a registered WordPress site.
-- api_key_hash stores a bcrypt hash of the site's API key.
CREATE TABLE IF NOT EXISTS sites (
  id                 UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
  site_id            TEXT        UNIQUE NOT NULL,
  api_key_hash       TEXT        NOT NULL,
  domain             TEXT,
  license_tier       TEXT        NOT NULL DEFAULT 'free', -- free | pro | enterprise
  license_expires_at TIMESTAMPTZ,
  features           JSONB       NOT NULL DEFAULT '{}',
  is_active          BOOLEAN     NOT NULL DEFAULT true,
  created_at         TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at         TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- ── usage ─────────────────────────────────────────────────────────────────────
-- Monthly token usage per site. Upserted after every LLM response.
CREATE TABLE IF NOT EXISTS usage (
  id            UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
  site_id       TEXT        NOT NULL REFERENCES sites(site_id) ON DELETE CASCADE,
  month         TEXT        NOT NULL, -- YYYY-MM
  input_tokens  BIGINT      NOT NULL DEFAULT 0,
  output_tokens BIGINT      NOT NULL DEFAULT 0,
  requests      INT         NOT NULL DEFAULT 0,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  UNIQUE (site_id, month)
);

-- ── rate_limits ───────────────────────────────────────────────────────────────
-- Daily request counts per site. Used for cross-instance per-day enforcement.
CREATE TABLE IF NOT EXISTS rate_limits (
  id      UUID    PRIMARY KEY DEFAULT gen_random_uuid(),
  site_id TEXT    NOT NULL REFERENCES sites(site_id) ON DELETE CASCADE,
  date    TEXT    NOT NULL, -- YYYY-MM-DD
  count   INT     NOT NULL DEFAULT 0,
  UNIQUE (site_id, date)
);

-- ── increment_usage RPC ───────────────────────────────────────────────────────
-- Atomically upserts a usage row and increments token counters.
CREATE OR REPLACE FUNCTION increment_usage(
  p_site_id      TEXT,
  p_month        TEXT,
  p_input_tokens BIGINT,
  p_output_tokens BIGINT
) RETURNS VOID AS $$
BEGIN
  INSERT INTO usage (site_id, month, input_tokens, output_tokens, requests)
  VALUES (p_site_id, p_month, p_input_tokens, p_output_tokens, 1)
  ON CONFLICT (site_id, month) DO UPDATE SET
    input_tokens  = usage.input_tokens  + EXCLUDED.input_tokens,
    output_tokens = usage.output_tokens + EXCLUDED.output_tokens,
    requests      = usage.requests      + 1,
    updated_at    = now();
END;
$$ LANGUAGE plpgsql;

-- ── increment_rate_limit RPC ──────────────────────────────────────────────────
-- Atomically upserts a rate_limit row and increments the daily count.
-- Returns the count AFTER incrementing.
CREATE OR REPLACE FUNCTION increment_rate_limit(
  p_site_id TEXT,
  p_date    TEXT
) RETURNS INT AS $$
DECLARE
  new_count INT;
BEGIN
  INSERT INTO rate_limits (site_id, date, count)
  VALUES (p_site_id, p_date, 1)
  ON CONFLICT (site_id, date) DO UPDATE SET
    count = rate_limits.count + 1
  RETURNING count INTO new_count;
  RETURN new_count;
END;
$$ LANGUAGE plpgsql;

-- ── updated_at trigger ────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = now();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER sites_updated_at
  BEFORE UPDATE ON sites
  FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER usage_updated_at
  BEFORE UPDATE ON usage
  FOR EACH ROW EXECUTE FUNCTION set_updated_at();
