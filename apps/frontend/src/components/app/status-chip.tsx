import { cn } from '@/lib/utils';

type StatusVariant = 'active' | 'expiring';

interface StatusChipProps {
  variant: StatusVariant;
  className?: string;
}

const config: Record<StatusVariant, { bg: string; border: string; dot: string; text: string; label: string }> = {
  active: {
    bg: 'bg-success-subtle',
    border: 'border-success-border',
    dot: 'bg-success-indicator',
    text: 'text-success-text',
    label: 'Active',
  },
  expiring: {
    bg: 'bg-warning-subtle',
    border: 'border-warning-indicator/40',
    dot: 'bg-warning-indicator',
    text: 'text-warning-text',
    label: 'Expiring',
  },
};

export function StatusChip({ variant, className }: StatusChipProps) {
  const { bg, border, dot, text, label } = config[variant];
  return (
    <span
      className={cn(
        'inline-flex items-center gap-[5px] rounded-full border px-[10px] py-1',
        bg,
        border,
        className,
      )}
    >
      <span className={cn('h-1.5 w-1.5 rounded-full', dot, variant === 'active' && 'animate-pulse-dot')} />
      <span className={cn('font-sans text-xs font-medium', text)}>{label}</span>
    </span>
  );
}
