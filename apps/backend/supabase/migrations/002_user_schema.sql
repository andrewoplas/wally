-- 1. license_keys: one per user, holds their subscription/plan info
CREATE TABLE IF NOT EXISTS license_keys (
  id         UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id    UUID        NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  key        TEXT        UNIQUE NOT NULL,
  tier       TEXT        NOT NULL DEFAULT 'free', -- free | pro | agency | enterprise
  max_sites  INT         NOT NULL DEFAULT 1,
  expires_at TIMESTAMPTZ,
  status     TEXT        NOT NULL DEFAULT 'active', -- active | expired | cancelled
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- 2. Extend sites table with user ownership
ALTER TABLE sites
  ADD COLUMN IF NOT EXISTS user_id        UUID REFERENCES auth.users(id),
  ADD COLUMN IF NOT EXISTS license_key_id UUID REFERENCES license_keys(id),
  ADD COLUMN IF NOT EXISTS activated_at   TIMESTAMPTZ DEFAULT now();

-- 3. updated_at trigger for license_keys
CREATE TRIGGER license_keys_updated_at
  BEFORE UPDATE ON license_keys
  FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- 4. RLS
ALTER TABLE license_keys ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view own license key"
  ON license_keys FOR SELECT
  USING (auth.uid() = user_id);

CREATE POLICY "Users can update own license key"
  ON license_keys FOR UPDATE
  USING (auth.uid() = user_id);

-- 5. RLS on sites for user-facing reads
CREATE POLICY "Users can view own sites"
  ON sites FOR SELECT
  USING (auth.uid() = user_id);

CREATE POLICY "Users can update own sites"
  ON sites FOR UPDATE
  USING (auth.uid() = user_id);

-- 6. Auto-create free license key when a new user registers
CREATE OR REPLACE FUNCTION handle_new_user()
RETURNS TRIGGER AS $$
DECLARE
  new_key TEXT;
BEGIN
  new_key := 'wally_live_sk_' || replace(gen_random_uuid()::text, '-', '');
  INSERT INTO public.license_keys (user_id, key, tier, max_sites)
  VALUES (NEW.id, new_key, 'free', 1);
  RETURN NEW;
EXCEPTION WHEN OTHERS THEN
  -- Never block user creation if license key insert fails
  RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER SET search_path = public;

CREATE OR REPLACE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW EXECUTE FUNCTION handle_new_user();
