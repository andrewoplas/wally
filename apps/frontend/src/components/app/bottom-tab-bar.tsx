'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { cn } from '@/lib/utils';
import { NAV_ITEMS } from './app-sidebar';

export function BottomTabBar() {
  const pathname = usePathname();

  return (
    <nav className="flex h-20 items-center justify-around border-t border-sidebar-border bg-sidebar px-2 pb-4 pt-0 md:hidden">
      {NAV_ITEMS.map(({ label, href, icon: Icon }) => {
        const isActive = pathname === href || pathname.startsWith(href + '/');
        return (
          <Link
            key={href}
            href={href}
            className={cn(
              'flex flex-1 flex-col items-center gap-1 px-4 py-1.5 text-xs transition-colors',
              isActive ? 'text-primary' : 'text-muted-foreground',
            )}
          >
            <Icon size={20} />
            <span className={cn('font-sans text-[10px]', isActive ? 'font-semibold' : 'font-normal')}>
              {label}
            </span>
          </Link>
        );
      })}
    </nav>
  );
}
