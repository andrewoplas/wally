-- Fix handle_new_user trigger:
-- 1. SET search_path = public so the function can resolve public.license_keys
-- 2. EXCEPTION block so a key-creation failure never blocks user registration
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
