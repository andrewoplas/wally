'use client';

import Link from 'next/link';
import { useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { AuthLeftPanel } from '@/components/auth/auth-left-panel';
import { AuthFormInput } from '@/components/auth/auth-form-input';
import { AuthDivider } from '@/components/auth/auth-divider';
import { GoogleButton } from '@/components/auth/google-button';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { createClient } from '@/lib/supabase/client';

const passwordRules = z
  .string()
  .min(6, 'Password must be at least 6 characters')
  .regex(/[a-z]/, 'Password must contain a lowercase letter')
  .regex(/[A-Z]/, 'Password must contain an uppercase letter')
  .regex(/[0-9]/, 'Password must contain a digit');

const registerSchema = z
  .object({
    firstName: z.string().min(1, 'First name is required'),
    lastName: z.string().min(1, 'Last name is required'),
    email: z.email({ message: 'Enter a valid email address' }),
    password: passwordRules,
    confirmPassword: z.string().min(1, 'Please confirm your password'),
    terms: z.literal(true, { message: 'You must accept the terms' }),
  })
  .refine((data) => data.password === data.confirmPassword, {
    message: 'Passwords do not match',
    path: ['confirmPassword'],
  });

type RegisterFormValues = z.infer<typeof registerSchema>;

const registerSteps = [
  {
    badge: <span className="font-heading text-sm font-bold text-white">1</span>,
    badgeOpacity: 'high' as const,
    title: 'Create your account',
    description: 'Sign up with your email in seconds',
  },
  {
    badge: <span className="font-heading text-sm font-bold" style={{ color: '#B8B0D0' }}>2</span>,
    title: 'Connect your WordPress site',
    titleMuted: true,
    description: 'Install the Wally plugin — takes under a minute',
    descriptionMuted: true,
  },
  {
    badge: <span className="font-heading text-sm font-bold" style={{ color: '#B8B0D0' }}>3</span>,
    title: 'Start chatting with Wally',
    titleMuted: true,
    description: 'Manage your site through natural language',
    descriptionMuted: true,
  },
];

export default function RegisterPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const next = searchParams.get('next') || '/app/license';
  const [serverError, setServerError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<RegisterFormValues>({
    resolver: zodResolver(registerSchema),
  });

  async function onSubmit(data: RegisterFormValues) {
    setServerError(null);
    const supabase = createClient();
    const { error } = await supabase.auth.signUp({
      email: data.email,
      password: data.password,
      options: {
        data: {
          first_name: data.firstName,
          last_name: data.lastName,
        },
      },
    });

    if (error) {
      setServerError(error.message);
      return;
    }

    router.push(next);
    router.refresh();
  }

  return (
    <div className="flex h-screen">
      {/* Left: branded panel */}
      <AuthLeftPanel
        headline={'Start managing WordPress\nthe smart way'}
        subheadline="Set up in minutes and let Wally handle posts, plugins, settings and more."
        items={registerSteps}
      />

      {/* Right: form panel */}
      <div className="flex w-1/2 items-center justify-center overflow-y-auto bg-background px-8 py-12">
        <form
          onSubmit={handleSubmit(onSubmit)}
          className="flex w-full max-w-[400px] flex-col gap-7"
          noValidate
        >
          {/* Form header */}
          <div className="flex flex-col gap-2">
            <h2 className="font-heading text-[32px] font-bold text-foreground">Create your account</h2>
            <p className="font-sans text-sm text-muted-foreground">Start managing WordPress with AI today</p>
          </div>

          {/* Form fields */}
          <div className="flex flex-col gap-3.5">
            {/* Name row */}
            <div className="flex gap-3">
              <AuthFormInput
                id="first-name"
                label="First name"
                type="text"
                autoComplete="given-name"
                className="flex-1"
                error={errors.firstName?.message}
                {...register('firstName')}
              />
              <AuthFormInput
                id="last-name"
                label="Last name"
                type="text"
                autoComplete="family-name"
                className="flex-1"
                error={errors.lastName?.message}
                {...register('lastName')}
              />
            </div>

            <AuthFormInput
              id="email"
              label="Email address"
              type="email"
              autoComplete="email"
              error={errors.email?.message}
              {...register('email')}
            />
            <AuthFormInput
              id="password"
              label="Password"
              type="password"
              autoComplete="new-password"
              error={errors.password?.message}
              {...register('password')}
            />
            <AuthFormInput
              id="confirm-password"
              label="Confirm password"
              type="password"
              autoComplete="new-password"
              error={errors.confirmPassword?.message}
              {...register('confirmPassword')}
            />

            {/* Terms */}
            <div className="flex flex-col gap-1">
              <div className="flex items-start gap-2.5">
                <Checkbox id="terms" className="mt-0.5" {...register('terms')} />
                <span className="text-[13px] text-muted-foreground font-sans leading-snug">
                  I agree to the{' '}
                  <Link href="/terms" className="text-primary hover:underline">Terms of Service</Link>
                  {' '}and{' '}
                  <Link href="/privacy" className="text-primary hover:underline">Privacy Policy</Link>
                </span>
              </div>
              {errors.terms && (
                <p className="text-xs text-destructive font-sans">{errors.terms.message}</p>
              )}
            </div>
          </div>

          {/* Server error */}
          {serverError && (
            <p className="text-sm text-destructive font-sans -mt-3">{serverError}</p>
          )}

          {/* Create account CTA */}
          <Button
            type="submit"
            variant="solid-primary"
            size="md"
            className="w-full justify-center rounded-[14px]"
            disabled={isSubmitting}
          >
            {isSubmitting ? 'Creating account…' : 'Create account'}
          </Button>

          <AuthDivider />

          <GoogleButton next={next} />

          {/* Sign in prompt */}
          <div className="flex items-center justify-center gap-1">
            <span className="text-sm text-muted-foreground font-sans">Already have an account?</span>
            <Link
              href={next !== '/app/license' ? `/login?next=${encodeURIComponent(next)}` : '/login'}
              className="text-sm font-medium text-primary font-heading hover:underline"
            >
              Sign in
            </Link>
          </div>
        </form>
      </div>
    </div>
  );
}
