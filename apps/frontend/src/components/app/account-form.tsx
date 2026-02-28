'use client';

import { useState } from 'react';
import { Eye, EyeOff, Trash2 } from 'lucide-react';
import { useForm } from 'react-hook-form';
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

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<AccountFormValues>({
    resolver: zodResolver(accountSchema),
    defaultValues: {
      firstName: 'John',
      lastName: 'Doe',
      email: 'john.doe@example.com',
    },
  });

  function onSubmit(data: AccountFormValues) {
    console.log(data);
  }

  return (
    <div className="overflow-hidden rounded-xl border border-border bg-card">
      {/* Card header — avatar + user info */}
      <div className="flex items-center gap-3.5 px-6 py-5">
        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary">
          <span className="font-sans text-lg font-semibold text-primary-foreground">JD</span>
        </div>
        <div className="flex flex-col gap-0.5">
          <span className="font-heading text-[15px] font-semibold text-foreground">John Doe</span>
          <span className="font-sans text-[13px] text-muted-foreground">john.doe@example.com</span>
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
        <div className="flex items-center justify-between px-6 py-4">
          <button type="button" className="flex items-center gap-1.5 transition-opacity hover:opacity-70">
            <Trash2 size={13} className="text-destructive-text" />
            <span className="font-sans text-[13px] text-destructive-text">Delete account</span>
          </button>
          <button type="submit" className="flex h-10 items-center rounded-lg bg-foreground px-5 transition-opacity hover:opacity-90">
            <span className="font-sans text-[13px] font-semibold text-primary-foreground">
              Save changes
            </span>
          </button>
        </div>
      </form>
    </div>
  );
}
