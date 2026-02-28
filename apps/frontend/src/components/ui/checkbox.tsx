'use client';

import { cn } from '@/lib/utils';
import { Check } from 'lucide-react';
import { InputHTMLAttributes, forwardRef } from 'react';

interface CheckboxProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  className?: string;
}

export const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(
  ({ className, id, checked, defaultChecked, onChange, ...props }, ref) => {
    const isControlled = checked !== undefined;

    return (
      <label
        htmlFor={id}
        className={cn('relative inline-flex h-4 w-4 shrink-0 cursor-pointer', className)}
      >
        <input
          ref={ref}
          id={id}
          type="checkbox"
          checked={isControlled ? checked : undefined}
          defaultChecked={!isControlled ? defaultChecked : undefined}
          onChange={onChange}
          className="peer sr-only"
          {...props}
        />
        {/* Unchecked state */}
        <span className="absolute inset-0 rounded-xs border border-input bg-background transition-colors peer-checked:border-primary peer-checked:bg-primary" />
        {/* Check icon */}
        <Check
          size={10}
          strokeWidth={3}
          className="absolute left-[3px] top-[3px] hidden text-primary-foreground peer-checked:block"
        />
      </label>
    );
  },
);

Checkbox.displayName = 'Checkbox';
