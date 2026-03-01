'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';
import { MessageCircle, KeyRound, CreditCard, CircleUser, LogOut, HelpCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import { UpgradeBanner } from './upgrade-banner';
import { createClient } from '@/lib/supabase/client';
import type { User } from '@supabase/supabase-js';

export const NAV_ITEMS = [
  { label: 'License', href: '/app/license', icon: KeyRound },
  { label: 'Subscription', href: '/app/subscriptions', icon: CreditCard },
  { label: 'Account', href: '/app/account', icon: CircleUser },
  { label: 'FAQ', href: '/app/faq', icon: HelpCircle },
];

interface AppSidebarProps {
  showUpgradeBanner?: boolean;
}

export function AppSidebar({ showUpgradeBanner = true }: AppSidebarProps) {
  const pathname = usePathname();
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);

  useEffect(() => {
    const supabase = createClient();
    supabase.auth.getUser().then(({ data }) => setUser(data.user));
  }, []);

  async function handleSignOut() {
    const supabase = createClient();
    await supabase.auth.signOut();
    router.push('/login');
    router.refresh();
  }

  const firstName = user?.user_metadata?.['first_name'] ?? '';
  const lastName = user?.user_metadata?.['last_name'] ?? '';
  const displayName = firstName || lastName ? `${firstName} ${lastName}`.trim() : (user?.email ?? '');
  const initials = firstName && lastName
    ? `${firstName[0]}${lastName[0]}`.toUpperCase()
    : (user?.email?.[0] ?? '?').toUpperCase();

  return (
    <aside className="flex h-screen w-[240px] shrink-0 flex-col justify-between border-r border-sidebar-border bg-sidebar px-5 py-7">
      <div className="flex flex-col gap-6">
        {/* Logo */}
        <div className="flex items-center gap-2.5">
          <div className="flex h-8 w-8 items-center justify-center rounded-[10px] bg-primary">
            <MessageCircle size={16} className="text-primary-foreground" />
          </div>
          <span className="font-heading text-base font-bold text-foreground">Wally</span>
        </div>

        {/* Nav */}
        <nav className="flex flex-col gap-0.5">
          {NAV_ITEMS.map(({ label, href, icon: Icon }) => {
            const isActive = pathname === href || pathname.startsWith(href + '/');
            return (
              <Link
                key={href}
                href={href}
                className={cn(
                  'flex items-center gap-2.5 rounded-lg px-3 py-[10px] text-sm transition-colors',
                  isActive
                    ? 'bg-sidebar-accent font-semibold text-sidebar-accent-foreground'
                    : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                )}
              >
                <Icon
                  size={16}
                  className={isActive ? 'text-primary' : 'text-sidebar-foreground'}
                />
                {label}
              </Link>
            );
          })}
        </nav>
      </div>

      <div className="flex flex-col gap-3">
        {/* Upgrade banner */}
        {showUpgradeBanner && <UpgradeBanner />}

        <div className="h-px bg-sidebar-border" />

        {/* User row */}
        <div className="flex items-center gap-2.5">
          <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary">
            <span className="font-sans text-xs font-semibold text-primary-foreground">{initials}</span>
          </div>
          <div className="flex min-w-0 flex-1 flex-col">
            <span className="truncate font-sans text-[13px] font-semibold text-foreground">
              {displayName}
            </span>
            <span className="truncate font-sans text-xs text-disabled">{user?.email ?? ''}</span>
          </div>
        </div>

        {/* Logout */}
        <button
          onClick={handleSignOut}
          className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm transition-colors hover:bg-sidebar-accent"
        >
          <LogOut size={15} className="text-disabled" />
          <span className="font-sans text-[13px] text-muted-foreground">Log out</span>
        </button>
      </div>
    </aside>
  );
}
