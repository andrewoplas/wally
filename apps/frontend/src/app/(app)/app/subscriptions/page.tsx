'use client';

import { Check, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';
import { getUserLicense } from '@/lib/api';

type CellValue = boolean | string;

interface FeatureRow {
  label: string;
  free: CellValue;
  pro: CellValue;
  agency: CellValue;
  enterprise: CellValue;
}

const FEATURES: FeatureRow[] = [
  { label: 'Bring your own API key', free: true, pro: true, agency: true, enterprise: true },
  { label: 'Content management tools', free: true, pro: true, agency: true, enterprise: true },
  {
    label: 'Messages per day',
    free: '50',
    pro: 'Unlimited',
    agency: 'Unlimited',
    enterprise: 'Unlimited',
  },
  { label: 'All WordPress tools', free: false, pro: true, agency: true, enterprise: true },
  { label: 'Action log & audit trail', free: false, pro: true, agency: true, enterprise: true },
  { label: 'Priority support', free: false, pro: true, agency: true, enterprise: true },
  { label: 'White-label branding', free: false, pro: false, agency: true, enterprise: true },
  { label: 'Team permissions', free: false, pro: false, agency: true, enterprise: true },
  { label: 'SSO authentication', free: false, pro: false, agency: false, enterprise: true },
  { label: 'Dedicated SLA', free: false, pro: false, agency: false, enterprise: true },
  {
    label: 'Number of sites',
    free: '1 site',
    pro: '1 site',
    agency: '10 sites',
    enterprise: 'Unlimited',
  },
];


function CellContent({ value, isPro }: { value: CellValue; isPro?: boolean }) {
  if (typeof value === 'boolean') {
    if (value) {
      return <Check size={18} className="text-primary" />;
    }
    return <X size={16} className="text-zinc-300" />;
  }
  return (
    <span
      className={cn(
        'text-center font-sans text-[13px]',
        isPro ? 'font-semibold text-primary' : 'font-medium text-muted-foreground',
      )}
    >
      {value}
    </span>
  );
}

const ctaBtn =
  'flex w-full items-center justify-center rounded-full py-2.5 px-4 font-sans text-[13px] font-semibold transition-opacity';

export default function SubscriptionsPage() {
  const [currentTier, setCurrentTier] = useState<string>('free');

  useEffect(() => {
    getUserLicense().then((d) => setCurrentTier(d.tier));
  }, []);

  return (
    <div className="flex flex-col gap-8">
      {/* Page header */}
      <div className="flex flex-col gap-1.5">
        <h1 className="font-heading text-[28px] font-bold text-foreground">Choose your plan</h1>
        <p className="font-sans text-sm leading-[1.5] text-muted-foreground">
          Start free, upgrade when you&apos;re ready.
        </p>
      </div>

      {/* Comparison table */}
      <div className="overflow-x-auto rounded-2xl">
      <div className="min-w-[700px] overflow-hidden rounded-2xl border border-border">
        {/* Header row */}
        <div className="flex h-16 bg-[#F8F7FF]">
          <div className="flex w-[280px] shrink-0 items-center px-6">
            <span className="font-heading text-[13px] font-bold text-foreground">Features</span>
          </div>
          <div className="flex flex-1 flex-col items-center justify-center gap-0.5">
            <span className="font-heading text-sm font-bold text-foreground">Free</span>
            <span className="font-sans text-xs font-medium text-muted-foreground">$0/mo</span>
            {currentTier === 'free' && (
              <span className="font-sans text-[10px] text-primary font-medium">Your plan</span>
            )}
          </div>
          <div className="flex flex-1 flex-col items-center justify-center gap-0.5 bg-[#EDE5FF]">
            <span className="font-heading text-sm font-bold text-primary">Pro</span>
            <span className="font-sans text-xs font-medium text-primary">$12/mo</span>
            {currentTier === 'pro' && (
              <span className="font-sans text-[10px] text-primary font-medium">Your plan</span>
            )}
          </div>
          <div className="flex flex-1 flex-col items-center justify-center gap-0.5">
            <span className="font-heading text-sm font-bold text-foreground">Agency</span>
            <span className="font-sans text-xs font-medium text-muted-foreground">$49/mo</span>
            {currentTier === 'agency' && (
              <span className="font-sans text-[10px] text-primary font-medium">Your plan</span>
            )}
          </div>
          <div className="flex flex-1 flex-col items-center justify-center gap-0.5">
            <span className="font-heading text-sm font-bold text-foreground">Enterprise</span>
            <span className="font-sans text-xs font-medium text-muted-foreground">$149/mo</span>
            {currentTier === 'enterprise' && (
              <span className="font-sans text-[10px] text-primary font-medium">Your plan</span>
            )}
          </div>
        </div>

        {/* Divider */}
        <div className="h-px bg-border" />

        {/* Feature rows */}
        {FEATURES.map((row, i) => (
          <div key={row.label}>
            <div className={cn('flex h-[52px]', i % 2 !== 0 && 'bg-[#FAFAFA]')}>
              <div className="flex w-[280px] shrink-0 items-center px-6">
                <span className="font-sans text-[13px] font-medium text-foreground">
                  {row.label}
                </span>
              </div>
              <div className="flex flex-1 items-center justify-center">
                <CellContent value={row.free} />
              </div>
              <div className="flex flex-1 items-center justify-center bg-[#F0EAFF]">
                <CellContent value={row.pro} isPro />
              </div>
              <div className="flex flex-1 items-center justify-center">
                <CellContent value={row.agency} />
              </div>
              <div className="flex flex-1 items-center justify-center">
                <CellContent value={row.enterprise} />
              </div>
            </div>
            <div className="h-px bg-border" />
          </div>
        ))}

        {/* CTA row */}
        <div className="flex h-20 bg-[#FAFAFA]">
          <div className="flex w-[280px] shrink-0 items-center px-6">
            <span className="font-heading text-[13px] font-bold text-foreground">Get started</span>
          </div>
          {/* Free */}
          <div className="flex flex-1 items-center justify-center px-4">
            {currentTier === 'free' ? (
              <button disabled className={cn(ctaBtn, 'bg-muted text-muted-foreground cursor-default opacity-60')}>
                Current Plan
              </button>
            ) : (
              <button className={cn(ctaBtn, 'bg-muted text-foreground hover:opacity-90')}>
                Get Started
              </button>
            )}
          </div>
          {/* Pro */}
          <div className="flex flex-1 items-center justify-center bg-[#F0EAFF] px-4">
            {currentTier === 'pro' ? (
              <button disabled className={cn(ctaBtn, 'bg-primary/50 text-primary-foreground cursor-default opacity-70')}>
                Current Plan
              </button>
            ) : (
              <button className={cn(ctaBtn, 'bg-primary text-primary-foreground hover:opacity-90')}>
                Start Free Trial
              </button>
            )}
          </div>
          {/* Agency */}
          <div className="flex flex-1 items-center justify-center px-4">
            {currentTier === 'agency' ? (
              <button disabled className={cn(ctaBtn, 'bg-muted text-muted-foreground cursor-default opacity-60')}>
                Current Plan
              </button>
            ) : (
              <button className={cn(ctaBtn, 'bg-muted text-foreground hover:opacity-90')}>
                Start Free Trial
              </button>
            )}
          </div>
          {/* Enterprise */}
          <div className="flex flex-1 items-center justify-center px-4">
            {currentTier === 'enterprise' ? (
              <button disabled className={cn(ctaBtn, 'border border-border bg-transparent text-muted-foreground cursor-default opacity-60')}>
                Current Plan
              </button>
            ) : (
              <button className={cn(ctaBtn, 'border border-border bg-transparent text-foreground hover:opacity-80')}>
                Contact Sales
              </button>
            )}
          </div>
        </div>
      </div>
      </div>

    </div>
  );
}
