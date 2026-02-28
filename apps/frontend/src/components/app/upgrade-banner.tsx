import { Zap } from 'lucide-react';
import Link from 'next/link';

export function UpgradeBanner() {
  return (
    <div className="flex flex-col gap-2 rounded-[10px] border border-primary-100 bg-primary-50 p-3.5">
      <span className="font-sans text-xs font-semibold text-primary-700">
        You&apos;re on the Free plan
      </span>
      <p className="font-sans text-[11px] leading-[1.5] text-primary-600">
        Upgrade for unlimited messages and more sites.
      </p>
      <Link
        href="/app/subscriptions"
        className="flex w-full items-center justify-center gap-1.5 rounded-lg bg-primary-500 px-3 py-2 transition-opacity hover:opacity-90"
      >
        <Zap size={13} className="text-white" />
        <span className="font-sans text-xs font-semibold text-white">Upgrade to Pro</span>
      </Link>
    </div>
  );
}
