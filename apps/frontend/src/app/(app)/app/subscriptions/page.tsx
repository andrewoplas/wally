'use client';

import { Check, X, ChevronDown, ChevronUp } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';

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

const FAQS = [
  { q: 'Can I cancel anytime?', a: null },
  {
    q: 'What happens to my sites if I downgrade?',
    a: 'Your activated sites will be deactivated and the plugin will stop responding to commands. It remains installed and no data is deleted — resubscribe anytime to reactivate.',
    defaultOpen: true,
  },
  { q: 'Is my WordPress data safe?', a: null },
  { q: 'Which AI models does Wally use?', a: null },
  { q: 'Do I need technical skills to use Wally?', a: null },
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

function FaqCard({
  q,
  a,
  defaultOpen = false,
}: {
  q: string;
  a: string | null;
  defaultOpen?: boolean;
}) {
  const [open, setOpen] = useState(defaultOpen);
  return (
    <div className="overflow-hidden rounded-[var(--radius,12px)] border border-border bg-card">
      <button
        onClick={() => setOpen((v) => !v)}
        className="flex w-full items-center justify-between px-6 py-5 text-left"
      >
        <span className="font-sans text-sm font-semibold leading-[1.5] text-foreground">{q}</span>
        {open ? (
          <ChevronUp size={18} className="shrink-0 text-muted-foreground" />
        ) : (
          <ChevronDown size={18} className="shrink-0 text-muted-foreground" />
        )}
      </button>
      {open && a && (
        <>
          <div className="h-px bg-border" />
          <p className="px-6 pb-5 font-sans text-[13px] leading-[1.6] text-muted-foreground">{a}</p>
        </>
      )}
    </div>
  );
}

const ctaBtn =
  'flex w-full items-center justify-center rounded-full py-2.5 px-4 font-sans text-[13px] font-semibold transition-opacity';

export default function SubscriptionsPage() {
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
          </div>
          <div className="flex flex-1 flex-col items-center justify-center gap-0.5 bg-[#EDE5FF]">
            <span className="font-heading text-sm font-bold text-primary">Pro</span>
            <span className="font-sans text-xs font-medium text-primary">$12/mo</span>
          </div>
          <div className="flex flex-1 flex-col items-center justify-center gap-0.5">
            <span className="font-heading text-sm font-bold text-foreground">Agency</span>
            <span className="font-sans text-xs font-medium text-muted-foreground">$49/mo</span>
          </div>
          <div className="flex flex-1 flex-col items-center justify-center gap-0.5">
            <span className="font-heading text-sm font-bold text-foreground">Enterprise</span>
            <span className="font-sans text-xs font-medium text-muted-foreground">$149/mo</span>
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
          <div className="flex flex-1 items-center justify-center px-4">
            <button className={cn(ctaBtn, 'bg-muted text-foreground hover:opacity-90')}>
              Get Started
            </button>
          </div>
          <div className="flex flex-1 items-center justify-center bg-[#F0EAFF] px-4">
            <button className={cn(ctaBtn, 'bg-primary text-primary-foreground hover:opacity-90')}>
              Start Free Trial
            </button>
          </div>
          <div className="flex flex-1 items-center justify-center px-4">
            <button className={cn(ctaBtn, 'bg-muted text-foreground hover:opacity-90')}>
              Start Free Trial
            </button>
          </div>
          <div className="flex flex-1 items-center justify-center px-4">
            <button
              className={cn(
                ctaBtn,
                'border border-border bg-transparent text-foreground hover:opacity-80',
              )}
            >
              Contact Sales
            </button>
          </div>
        </div>
      </div>
      </div>

      {/* FAQ */}
      <div className="flex flex-col gap-5">
        <div className="flex flex-col gap-1">
          <h2 className="font-heading text-lg font-bold text-foreground">
            Frequently Asked Questions
          </h2>
          <p className="font-sans text-[13px] text-muted-foreground">
            Everything you need to know about Wally
          </p>
        </div>
        <div className="flex flex-col gap-3">
          {FAQS.map((faq) => (
            <FaqCard key={faq.q} q={faq.q} a={faq.a} defaultOpen={faq.defaultOpen} />
          ))}
        </div>
      </div>
    </div>
  );
}
