import { Zap } from 'lucide-react';
import Link from 'next/link';

export function UpgradeBannerPage() {
  return (
    <div className="flex w-full flex-col gap-3 rounded-xl border border-primary-100 bg-primary-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
      {/* Left: icon + text */}
      <div className="flex items-center gap-3.5">
        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-primary-500">
          <Zap size={16} className="text-white" />
        </div>
        <div className="flex flex-col gap-0.5">
          <span className="font-sans text-sm font-bold text-primary-800">
            You&apos;re on the Free plan
          </span>
          <p className="font-sans text-[13px] leading-[1.4] text-primary-700">
            Upgrade to Pro to get your license key, plugin downloads, and multi-site activation.
          </p>
        </div>
      </div>

      {/* CTA */}
      <Link
        href="/app/subscriptions"
        className="flex h-[38px] w-full items-center justify-center gap-1.5 rounded-full bg-primary-500 px-5 font-sans text-[13px] font-semibold text-white transition-opacity hover:opacity-90 sm:w-auto"
      >
        <Zap size={13} className="text-white" />
        Upgrade to Pro
      </Link>
    </div>
  );
}
