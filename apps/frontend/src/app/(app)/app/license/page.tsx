import { createClient } from '@/lib/supabase/server';
import { LicenseCard } from '@/components/app/license-card';
import { ActivatedSitesCard } from '@/components/app/activated-sites-card';
import { UpgradeBannerPage } from '@/components/app/upgrade-banner-page';

export default async function LicensePage() {
  const supabase = await createClient();
  const { data: { session } } = await supabase.auth.getSession();

  if (!session) {
    return null;
  }

  const res = await fetch(`${process.env.BACKEND_URL}/v1/user/license`, {
    headers: { Authorization: `Bearer ${session.access_token}` },
  });

  const licenseData = res.ok ? await res.json() : null;
  const { sites, ...license } = licenseData ?? {};

  const isFree = !license || license.tier === 'free';

  return (
    <div className="flex flex-col gap-8">
      <div className="flex flex-col gap-1">
        <h1 className="font-heading text-[28px] font-bold text-foreground">License</h1>
        <p className="font-sans text-sm leading-[1.5] text-muted-foreground">
          Manage your Wally license key and download the plugin.
        </p>
      </div>

      {isFree && <UpgradeBannerPage />}

      <LicenseCard
        licenseKey={license?.key ?? ''}
        tier={license?.tier ?? 'free'}
        expiresAt={license?.expires_at ?? null}
        activatedCount={sites?.length ?? 0}
        maxSites={license?.max_sites ?? 1}
        status={license?.status ?? 'active'}
      />

      <ActivatedSitesCard
        sites={sites ?? []}
        maxSites={license?.max_sites ?? 1}
        licenseKeyId={license?.id ?? null}
      />
    </div>
  );
}
