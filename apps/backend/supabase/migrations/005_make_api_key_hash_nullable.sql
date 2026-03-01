-- api_key_hash is no longer used for auth; license key handles everything
ALTER TABLE sites ALTER COLUMN api_key_hash DROP NOT NULL;
ALTER TABLE sites ALTER COLUMN api_key_hash SET DEFAULT NULL;

-- Performance indexes for new auth lookup pattern
CREATE INDEX IF NOT EXISTS idx_license_keys_key ON license_keys(key);
CREATE INDEX IF NOT EXISTS idx_sites_license_key_active ON sites(license_key_id, is_active);
