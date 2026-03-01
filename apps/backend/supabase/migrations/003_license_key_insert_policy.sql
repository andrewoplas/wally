-- Allow users to create their own license key (fallback if trigger fails or for existing users)
CREATE POLICY "Users can insert own license key"
  ON license_keys FOR INSERT
  WITH CHECK (auth.uid() = user_id);
