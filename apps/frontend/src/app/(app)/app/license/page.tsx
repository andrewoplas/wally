import { LicenseCard } from '@/components/app/license-card';
import { ActivatedSitesCard } from '@/components/app/activated-sites-card';
import { UpgradeBannerPage } from '@/components/app/upgrade-banner-page';

export default function LicensePage() {
  return (
    <div className="flex flex-col gap-8">
      {/* Page header */}
      <div className="flex flex-col gap-1">
        <h1 className="font-heading text-[28px] font-bold text-foreground">License</h1>
        <p className="font-sans text-sm leading-[1.5] text-muted-foreground">
          Manage your Wally license key and download the plugin.
        </p>
      </div>

      <UpgradeBannerPage />
      <LicenseCard />
      <ActivatedSitesCard />
    </div>
  );
}
