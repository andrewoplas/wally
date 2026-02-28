'use client';

import { cn } from '@/lib/utils';
import { InputHTMLAttributes, forwardRef } from 'react';

interface SwitchProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'size'> {
  label?: string;
  className?: string;
}

export const Switch = forwardRef<HTMLInputElement, SwitchProps>(
  ({ className, id, label, ...props }, ref) => {
    return (
      <label
        htmlFor={id}
        className={cn('group inline-flex cursor-pointer items-center gap-3', className)}
      >
        <input
          ref={ref}
          id={id}
          type="checkbox"
          role="switch"
          className="peer sr-only"
          {...props}
        />
        {/* Track — peer-checked changes bg; group-has changes thumb position */}
        <span className="relative inline-flex h-5 w-10 shrink-0 rounded-pill bg-input transition-colors duration-200 peer-checked:bg-primary">
          <span className="absolute left-[3px] top-[3px] h-[14px] w-5 rounded-pill bg-white shadow-sm transition-transform duration-200 group-has-[input:checked]:translate-x-[14px]" />
        </span>
        {label && (
          <span className="font-sans text-base text-foreground">{label}</span>
        )}
      </label>
    );
  },
);

Switch.displayName = 'Switch';
