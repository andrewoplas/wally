import { cn } from '@/lib/utils';
import { InputHTMLAttributes, forwardRef } from 'react';

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  error?: boolean;
  className?: string;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ className, error, ...props }, ref) => {
    return (
      <input
        ref={ref}
        className={cn(
          'h-12 w-full rounded-[14px] border bg-white px-[18px] text-sm text-foreground',
          'outline-none transition-colors',
          error
            ? 'border-destructive focus:border-destructive'
            : 'border-border focus:border-primary',
          className,
        )}
        {...props}
      />
    );
  },
);

Input.displayName = 'Input';
