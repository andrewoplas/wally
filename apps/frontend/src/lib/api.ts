export interface LicenseSite {
  id: string;
  domain: string | null;
  is_active: boolean;
  activated_at: string | null;
  license_expires_at: string | null;
}

export interface UserLicense {
  id: string | null;
  key: string | null;
  tier: string;
  max_sites: number;
  expires_at: string | null;
  status: string;
  activated_count: number;
  sites: LicenseSite[];
}

/** GET /api/user/license — fetch the current user's license info */
export async function getUserLicense(): Promise<UserLicense> {
  const res = await fetch('/api/user/license');
  if (!res.ok) throw new Error(`Failed to fetch license: ${res.status}`);
  return res.json();
}

/** DELETE /api/user/sites/:siteId — deactivate a site */
export async function deactivateSite(siteId: string): Promise<{ success: boolean }> {
  const res = await fetch(`/api/user/sites/${siteId}`, { method: 'DELETE' });
  if (!res.ok) throw new Error(`Failed to deactivate site: ${res.status}`);
  return res.json();
}
