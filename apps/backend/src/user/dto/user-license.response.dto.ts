export interface SiteDto {
  id: string;
  domain: string | null;
  is_active: boolean;
  activated_at: string | null;
  license_expires_at: string | null;
}

export interface UserLicenseResponseDto {
  id: string | null;
  key: string | null;
  tier: string;
  max_sites: number;
  expires_at: string | null;
  status: string;
  activated_count: number;
  sites: SiteDto[];
}
