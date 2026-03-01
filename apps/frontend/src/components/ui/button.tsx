import { cn } from '@/lib/utils';

type ButtonVariant =
  | 'solid-white'
  | 'ghost-dark'
  | 'outline-dark'
  | 'solid-primary'
  | 'secondary'
  | 'outline';

type ButtonSize = 'sm' | 'md' | 'lg';

interface ButtonProps {
  href?: string;
  variant: ButtonVariant;
  size?: ButtonSize;
  icon?: React.ReactNode;
  className?: string;
  children: React.ReactNode;
  onClick?: () => void;
  type?: 'button' | 'submit' | 'reset';
  disabled?: boolean;
}

const variantClasses: Record<ButtonVariant, string> = {
  'solid-white':   'bg-white text-primary font-bold hover:bg-white/90',
  'ghost-dark':    'border border-white/40 bg-white/20 text-white font-semibold hover:bg-white/30',
  'outline-dark':  'border border-white/30 text-white font-semibold hover:bg-white/10',
  'solid-primary': 'bg-primary text-white font-bold hover:bg-primary/90',
  'secondary':     'bg-muted text-foreground font-semibold hover:bg-border',
  'outline':       'border border-border text-foreground font-semibold hover:bg-muted',
};

const sizeClasses: Record<ButtonSize, string> = {
  sm: 'px-4 py-2 text-sm',
  md: 'px-6 py-3 text-sm',
  lg: 'px-8 py-[18px] text-base',
};

export function Button({
  href,
  variant,
  size = 'lg',
  icon,
  className,
  children,
  onClick,
  type = 'button',
  disabled,
}: ButtonProps) {
  const classes = cn(
    'inline-flex items-center gap-2 rounded-pill font-heading transition-colors',
    variantClasses[variant],
    sizeClasses[size],
    className,
  );

  if (href !== undefined) {
    return (
      <a href={href} className={classes}>
        {icon}
        {children}
      </a>
    );
  }

  return (
    <button type={type} className={classes} onClick={onClick} disabled={disabled}>
      {icon}
      {children}
    </button>
  );
}
