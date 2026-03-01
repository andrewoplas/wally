'use client';

import { useState } from 'react';
import { Copy, Check, Download } from 'lucide-react';
import { StatusChip } from './status-chip';

interface LicenseCardProps {
  licenseKey: string;
  tier: string;
  expiresAt: string | null;
  activatedCount: number;
  maxSites: number;
  status: string;
}

function formatDate(iso: string | null): string {
  if (!iso) return 'Never';
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
  });
}

export function LicenseCard({
  licenseKey,
  tier,
  expiresAt,
  activatedCount,
  maxSites,
  status,
}: LicenseCardProps) {
  const [copied, setCopied] = useState(false);

  const handleCopy = () => {
    navigator.clipboard.writeText(licenseKey);
    setCopied(true);
    setTimeout(() => setCopied(false), 1500);
  };

  const chipVariant = status === 'active' ? 'active' : 'expiring';

  return (
    <div className="overflow-hidden rounded-xl border border-border bg-card">
      {/* Top section */}
      <div className="flex flex-col gap-5 px-7 py-6">
        {/* Status row */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2.5">
            <span className="font-heading text-[18px] font-bold text-foreground">Wally</span>
            <span className="rounded-full bg-primary px-[10px] py-[3px] font-sans text-[11px] font-bold text-primary-foreground">
              {tier.toUpperCase()}
            </span>
          </div>
          <StatusChip variant={chipVariant} />
        </div>

        {/* License key row */}
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-2.5">
          <span className="shrink-0 font-sans text-[13px] font-semibold text-foreground">
            License Key
          </span>
          <div className="flex min-w-0 flex-1 items-center gap-2.5">
            <div className="flex h-11 min-w-0 flex-1 items-center rounded-lg border border-border bg-surface-subtle px-[14px]">
              <span className="truncate font-sans text-[13px] text-foreground">{licenseKey}</span>
            </div>
            <button
              onClick={handleCopy}
              className="flex h-11 shrink-0 items-center gap-1.5 rounded-lg bg-foreground px-[18px] transition-opacity hover:opacity-90"
            >
              {copied ? (
                <Check size={14} className="text-primary-foreground" />
              ) : (
                <Copy size={14} className="text-primary-foreground" />
              )}
              <span className="font-sans text-[13px] font-semibold text-primary-foreground">
                {copied ? 'Copied!' : 'Copy key'}
              </span>
            </button>
          </div>
        </div>

        {/* Meta row */}
        <div className="flex gap-8">
          <div className="flex flex-col gap-[3px]">
            <span className="font-sans text-xs text-disabled">Expires</span>
            <span className="font-sans text-[13px] font-medium text-foreground">
              {formatDate(expiresAt)}
            </span>
          </div>
          <div className="flex flex-col gap-[3px]">
            <span className="font-sans text-xs text-disabled">Activations</span>
            <span className="font-sans text-[13px] font-medium text-foreground">
              {activatedCount} of {maxSites} sites
            </span>
          </div>
        </div>
      </div>

      {/* Divider */}
      <div className="h-px bg-surface-divider" />

      {/* Download section */}
      <div className="flex flex-col gap-3 px-7 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex flex-col gap-0.5">
          <span className="font-sans text-[13px] font-semibold text-foreground">
            Download Plugin
          </span>
          <span className="font-sans text-xs text-disabled">
            Latest version · v1.2.0 · Released Feb 20, 2026
          </span>
        </div>
        <button className="flex h-10 w-full items-center justify-center gap-[7px] rounded-lg bg-primary px-[18px] transition-opacity hover:opacity-90 sm:w-auto">
          <Download size={14} className="text-primary-foreground" />
          <span className="font-sans text-[13px] font-semibold text-primary-foreground">
            Download .zip
          </span>
        </button>
      </div>
    </div>
  );
}
