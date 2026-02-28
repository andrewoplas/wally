'use client';

import { useState } from 'react';
import { Globe, Power } from 'lucide-react';
import * as Dialog from '@radix-ui/react-dialog';
import { StatusChip } from './status-chip';

type SiteStatus = 'active' | 'expiring';

interface Site {
  url: string;
  activatedAt: string;
  status: SiteStatus;
}

const SITES: Site[] = [
  { url: 'goodtime.io', activatedAt: 'Jan 12, 2026', status: 'active' },
  { url: 'staging.goodtime.io', activatedAt: 'Jan 15, 2026', status: 'active' },
  { url: 'myclientsite.com', activatedAt: 'Feb 3, 2026', status: 'expiring' },
];

export function ActivatedSitesCard() {
  const [pendingSite, setPendingSite] = useState<string | null>(null);

  const handleConfirmDeactivate = () => {
    // TODO: call deactivate API
    setPendingSite(null);
  };

  return (
    <div className="overflow-hidden rounded-xl border border-border bg-card">
      {/* Header */}
      <div className="flex items-center justify-between px-6 py-[18px]">
        <div className="flex flex-col gap-0.5">
          <span className="font-heading text-[15px] font-semibold text-foreground">
            Activated Sites
          </span>
          <span className="font-sans text-xs text-disabled">
            Sites where this license key is active.
          </span>
        </div>
        <span className="rounded-full bg-muted px-[10px] py-[3px] font-sans text-xs font-medium text-muted-foreground">
          3 / 5
        </span>
      </div>

      {/* Table header */}
      <div className="flex items-center border-b border-surface-divider bg-surface-subtle px-6 py-[10px]">
        <span className="flex-1 font-sans text-xs font-semibold text-muted-foreground">
          Site URL
        </span>
        <span className="hidden w-[140px] font-sans text-xs font-semibold text-muted-foreground sm:block">
          Activated
        </span>
        <span className="w-[90px] font-sans text-xs font-semibold text-muted-foreground">
          Status
        </span>
        <span className="w-9 sm:w-[90px]" />
      </div>

      {/* Rows */}
      {SITES.map((site) => (
        <div
          key={site.url}
          className="flex items-center border-t border-surface-divider px-6 py-[14px]"
        >
          <div className="flex min-w-0 flex-1 items-center gap-2.5">
            <Globe size={14} className="shrink-0 text-disabled" />
            <span className="truncate font-sans text-[13px] font-medium text-foreground">{site.url}</span>
          </div>
          <span className="hidden w-[140px] font-sans text-[13px] text-muted-foreground sm:block">
            {site.activatedAt}
          </span>
          <div className="w-[90px]">
            <StatusChip variant={site.status} />
          </div>
          <div className="flex w-9 items-center sm:w-[90px]">
            <button
              onClick={() => setPendingSite(site.url)}
              className="flex items-center gap-[5px] transition-opacity hover:opacity-70"
            >
              <Power size={13} className="text-disabled" />
              <span className="hidden font-sans text-xs text-disabled sm:inline">Deactivate</span>
            </button>
          </div>
        </div>
      ))}

      {/* Deactivate confirmation dialog */}
      <Dialog.Root open={pendingSite !== null} onOpenChange={(open) => !open && setPendingSite(null)}>
        <Dialog.Portal>
          <Dialog.Overlay className="fixed inset-0 bg-black/40 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" />
          <Dialog.Content className="fixed left-1/2 top-1/2 w-full max-w-[400px] -translate-x-1/2 -translate-y-1/2 rounded-xl border border-border bg-card p-6 shadow-lg focus:outline-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95">
            <Dialog.Title className="font-heading text-[15px] font-semibold text-foreground">
              Deactivate site?
            </Dialog.Title>
            <Dialog.Description className="mt-1.5 font-sans text-[13px] leading-[1.6] text-muted-foreground">
              This will remove{' '}
              <span className="font-medium text-foreground">{pendingSite}</span> from your active
              sites. You can reactivate it at any time.
            </Dialog.Description>

            <div className="mt-6 flex justify-end gap-2.5">
              <Dialog.Close asChild>
                <button className="flex h-9 items-center rounded-lg border border-border bg-card px-4 font-sans text-[13px] font-medium text-foreground transition-opacity hover:opacity-80">
                  Cancel
                </button>
              </Dialog.Close>
              <button
                onClick={handleConfirmDeactivate}
                className="flex h-9 items-center rounded-lg bg-destructive px-4 font-sans text-[13px] font-semibold text-destructive-foreground transition-opacity hover:opacity-90"
              >
                Deactivate
              </button>
            </div>
          </Dialog.Content>
        </Dialog.Portal>
      </Dialog.Root>
    </div>
  );
}
