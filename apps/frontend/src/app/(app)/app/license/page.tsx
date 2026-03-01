import { createClient } from '@/lib/supabase/server';
import { LicenseCard } from '@/components/app/license-card';
import { ActivatedSitesCard } from '@/components/app/activated-sites-card';
import { UpgradeBannerPage } from '@/components/app/upgrade-banner-page';

export default async function LicensePage() {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  let { data: license } = user
    ? await supabase
        .from('license_keys')
        .select('*')
        .eq('user_id', user.id)
        .maybeSingle()
    : { data: null };

  // Fallback: create a free key if none exists (handles existing users + trigger failures)
  if (!license && user) {
    const newKey = `wally_live_sk_${crypto.randomUUID().replace(/-/g, '')}`;
    const { data: created } = await supabase
      .from('license_keys')
      .insert({ user_id: user.id, key: newKey, tier: 'free', max_sites: 1 })
      .select()
      .single();
    license = created;
  }

  const { data: sites } = license
    ? await supabase
        .from('sites')
        .select('id, domain, is_active, activated_at, license_expires_at')
        .eq('license_key_id', license.id)
        .eq('is_active', true)
        .order('activated_at', { ascending: false })
    : { data: [] };

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
