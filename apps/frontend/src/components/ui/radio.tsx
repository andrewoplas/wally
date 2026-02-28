'use client';

import { cn } from '@/lib/utils';
import { InputHTMLAttributes, forwardRef } from 'react';

interface RadioProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  className?: string;
}

export const Radio = forwardRef<HTMLInputElement, RadioProps>(
  ({ className, id, ...props }, ref) => {
    return (
      <label
        htmlFor={id}
        className={cn('relative inline-flex h-4 w-4 shrink-0 cursor-pointer', className)}
      >
        <input
          ref={ref}
          id={id}
          type="radio"
          className="peer sr-only"
          {...props}
        />
        {/* Circle track */}
        <span className="absolute inset-0 rounded-full border border-input bg-background transition-colors peer-checked:border-primary peer-checked:bg-primary" />
        {/* Inner dot */}
        <span className="absolute left-1/2 top-1/2 hidden h-1 w-1 -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary-foreground peer-checked:block" />
      </label>
    );
  },
);

Radio.displayName = 'Radio';
