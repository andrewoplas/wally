'use client';

import { useState, useEffect } from 'react';
import { Eye, EyeOff, Trash2 } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { createClient } from '@/lib/supabase/client';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { AuthFormInput } from '@/components/auth/auth-form-input';
import { Input } from '@/components/ui/input';

const accountSchema = z
  .object({
    firstName: z.string().min(1, 'First name is required'),
    lastName: z.string().min(1, 'Last name is required'),
    email: z.email({ message: 'Enter a valid email address' }),
    currentPassword: z.string().optional(),
    newPassword: z.string().optional(),
  })
  .refine(
    (data) => !data.newPassword || !!data.currentPassword,
    { message: 'Current password is required to set a new one', path: ['currentPassword'] },
  )
  .refine(
    (data) => !data.newPassword || data.newPassword.length >= 8,
    { message: 'New password must be at least 8 characters', path: ['newPassword'] },
  );

type AccountFormValues = z.infer<typeof accountSchema>;

export function AccountForm() {
  const [showCurrent, setShowCurrent] = useState(false);
  const [showNew, setShowNew] = useState(false);
  const [serverError, setServerError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [userDisplay, setUserDisplay] = useState({ name: '', email: '', initials: '' });

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<AccountFormValues>({
    resolver: zodResolver(accountSchema),
  });

  useEffect(() => {
    const supabase = createClient();
    supabase.auth.getUser().then(({ data }) => {
      if (data.user) {
        const first = data.user.user_metadata?.['first_name'] ?? '';
        const last = data.user.user_metadata?.['last_name'] ?? '';
        const email = data.user.email ?? '';
        reset({ firstName: first, lastName: last, email });
        const name = first || last ? `${first} ${last}`.trim() : email;
        const initials = first && last
          ? `${first[0]}${last[0]}`.toUpperCase()
          : (email[0] ?? '?').toUpperCase();
        setUserDisplay({ name, email, initials });
      }
    });
  }, [reset]);

  async function onSubmit(data: AccountFormValues) {
    setServerError(null);
    setSuccessMessage(null);
    const supabase = createClient();

    const updates: Parameters<typeof supabase.auth.updateUser>[0] = {
      data: { first_name: data.firstName, last_name: data.lastName },
    };

    if (data.email) updates.email = data.email;
    if (data.newPassword) updates.password = data.newPassword;

    const { error } = await supabase.auth.updateUser(updates);

    if (error) {
      setServerError(error.message);
      return;
    }

    setSuccessMessage('Changes saved successfully.');
  }

  return (
    <div className="overflow-hidden rounded-xl border border-border bg-card">
      {/* Card header — avatar + user info */}
      <div className="flex items-center gap-3.5 px-6 py-5">
        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary">
          <span className="font-sans text-lg font-semibold text-primary-foreground">{userDisplay.initials}</span>
        </div>
        <div className="flex flex-col gap-0.5">
          <span className="font-heading text-[15px] font-semibold text-foreground">{userDisplay.name}</span>
          <span className="font-sans text-[13px] text-muted-foreground">{userDisplay.email}</span>
        </div>
      </div>

      {/* Divider */}
      <div className="h-px bg-surface-divider" />

      {/* Form body */}
      <form onSubmit={handleSubmit(onSubmit)} noValidate>
        <div className="flex flex-col gap-5 p-6">
          {/* Name row */}
          <div className="flex flex-col gap-4 sm:flex-row">
            <AuthFormInput
              id="firstName"
              label="First name"
              type="text"
              autoComplete="given-name"
              className="flex-1"
              error={errors.firstName?.message}
              {...register('firstName')}
            />
            <AuthFormInput
              id="lastName"
              label="Last name"
              type="text"
              autoComplete="family-name"
              className="flex-1"
              error={errors.lastName?.message}
              {...register('lastName')}
            />
          </div>

          {/* Email */}
          <AuthFormInput
            id="email"
            label="Email address"
            type="email"
            autoComplete="email"
            error={errors.email?.message}
            {...register('email')}
          />

          {/* Divider */}
          <div className="h-px bg-surface-divider" />

          {/* Password section */}
          <div className="flex flex-col gap-5">
            <span className="font-heading text-sm font-semibold text-foreground">
              Password change
            </span>

            {/* Current password */}
            <div className="flex flex-col gap-1.5">
              <label htmlFor="currentPassword" className="font-sans text-sm font-semibold text-foreground">
                Current password
              </label>
              <div className="relative">
                <Input
                  id="currentPassword"
                  type={showCurrent ? 'text' : 'password'}
                  placeholder="Leave blank to keep unchanged"
                  autoComplete="current-password"
                  className="pr-11"
                  error={!!errors.currentPassword}
                  {...register('currentPassword')}
                />
                <button
                  type="button"
                  onClick={() => setShowCurrent((v) => !v)}
                  className="absolute right-4 top-1/2 -translate-y-1/2 text-disabled-foreground transition-opacity hover:opacity-70"
                >
                  {showCurrent ? <EyeOff size={16} /> : <Eye size={16} />}
                </button>
              </div>
              {errors.currentPassword && (
                <p className="text-xs text-destructive font-sans">{errors.currentPassword.message}</p>
              )}
            </div>

            {/* New password */}
            <div className="flex flex-col gap-1.5">
              <label htmlFor="newPassword" className="font-sans text-sm font-semibold text-foreground">
                New password
              </label>
              <div className="relative">
                <Input
                  id="newPassword"
                  type={showNew ? 'text' : 'password'}
                  placeholder="New password"
                  autoComplete="new-password"
                  className="pr-11"
                  error={!!errors.newPassword}
                  {...register('newPassword')}
                />
                <button
                  type="button"
                  onClick={() => setShowNew((v) => !v)}
                  className="absolute right-4 top-1/2 -translate-y-1/2 text-disabled-foreground transition-opacity hover:opacity-70"
                >
                  {showNew ? <EyeOff size={16} /> : <Eye size={16} />}
                </button>
              </div>
              {errors.newPassword && (
                <p className="text-xs text-destructive font-sans">{errors.newPassword.message}</p>
              )}
            </div>
          </div>
        </div>

        {/* Divider */}
        <div className="h-px bg-surface-divider" />

        {/* Card footer */}
        <div className="flex flex-col gap-2 px-6 py-4">
          {serverError && (
            <p className="text-xs text-destructive font-sans">{serverError}</p>
          )}
          {successMessage && (
            <p className="text-xs text-green-600 font-sans">{successMessage}</p>
          )}
          <div className="flex items-center justify-between">
            <button type="button" className="flex items-center gap-1.5 transition-opacity hover:opacity-70">
              <Trash2 size={13} className="text-destructive-text" />
              <span className="font-sans text-[13px] text-destructive-text">Delete account</span>
            </button>
            <button
              type="submit"
              disabled={isSubmitting}
              className="flex h-10 items-center rounded-lg bg-foreground px-5 transition-opacity hover:opacity-90 disabled:opacity-50"
            >
              <span className="font-sans text-[13px] font-semibold text-primary-foreground">
                {isSubmitting ? 'Saving…' : 'Save changes'}
              </span>
            </button>
          </div>
        </div>
      </form>
    </div>
  );
}
