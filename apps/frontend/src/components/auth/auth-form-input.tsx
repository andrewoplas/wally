import { cn } from '@/lib/utils';
import { forwardRef, InputHTMLAttributes } from 'react';
import { Input } from '@/components/ui/input';

interface AuthFormInputProps extends InputHTMLAttributes<HTMLInputElement> {
  label: string;
  error?: string;
  className?: string;
}

export const AuthFormInput = forwardRef<HTMLInputElement, AuthFormInputProps>(
  ({ label, error, className, id, ...props }, ref) => {
    return (
      <div className={cn('flex flex-col gap-1.5', className)}>
        <label htmlFor={id} className="text-sm font-semibold text-foreground font-sans">
          {label}
        </label>
        <Input id={id} error={!!error} ref={ref} {...props} />
        {error && (
          <p className="text-xs text-destructive font-sans">{error}</p>
        )}
      </div>
    );
  }
);

AuthFormInput.displayName = 'AuthFormInput';
