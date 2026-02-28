import { cn } from '@/lib/utils';
import { type LucideIcon } from 'lucide-react';

interface SectionBadgeProps {
  icon: LucideIcon;
  children: React.ReactNode;
  variant?: 'light' | 'dark' | 'white';
}

export function SectionBadge({
  icon: Icon,
  children,
  variant = 'light',
}: SectionBadgeProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-full px-5 py-2 text-[13px] font-semibold',
        variant === 'light' && 'bg-primary/[0.12] text-primary',
        variant === 'dark' &&
          'border border-white/20 bg-white/[0.06] text-lp-purple-light',
        variant === 'white' &&
          'bg-white/20 text-white/80'
      )}
    >
      <Icon className="h-3.5 w-3.5" />
      {children}
    </span>
  );
}
