'use client';

import Link from 'next/link';
import { Zap, ShieldCheck, Sparkles } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { AuthLeftPanel } from '@/components/auth/auth-left-panel';
import { AuthFormInput } from '@/components/auth/auth-form-input';
import { AuthDivider } from '@/components/auth/auth-divider';
import { GoogleButton } from '@/components/auth/google-button';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';

const loginSchema = z.object({
  email: z.email({ message: 'Enter a valid email address' }),
  password: z.string().min(1, 'Password is required'),
  remember: z.boolean().optional(),
});

type LoginFormValues = z.infer<typeof loginSchema>;

const loginFeatures = [
  {
    badge: <Zap size={18} className="text-[#A78BFA]" />,
    title: 'Natural language control',
    description: 'Just type what you want — Wally handles the rest',
  },
  {
    badge: <ShieldCheck size={18} className="text-[#A78BFA]" />,
    title: 'Secure by design',
    description: 'Your credentials stay encrypted, always',
  },
  {
    badge: <Sparkles size={18} className="text-[#A78BFA]" />,
    title: 'Powered by Claude AI',
    description: 'State-of-the-art AI built for real tasks',
  },
];

export default function LoginPage() {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
  });

  function onSubmit(data: LoginFormValues) {
    console.log(data);
  }

  return (
    <div className="flex h-screen">
      <AuthLeftPanel
        headline={'Your AI-powered\nWordPress assistant'}
        subheadline="Manage your entire site through natural conversation — no code, no complexity."
        items={loginFeatures}
      />

      {/* Right panel */}
      <div className="flex w-1/2 items-center justify-center bg-background px-8 py-12">
        <form
          onSubmit={handleSubmit(onSubmit)}
          className="flex w-full max-w-[400px] flex-col gap-8"
          noValidate
        >
          {/* Form header */}
          <div className="flex flex-col gap-2">
            <h2 className="font-heading text-[32px] font-bold text-foreground">Welcome back</h2>
            <p className="font-sans text-sm text-muted-foreground">Sign in to your Wally account</p>
          </div>

          {/* Form fields */}
          <div className="flex flex-col gap-4">
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
              autoComplete="current-password"
              error={errors.password?.message}
              {...register('password')}
            />

            {/* Remember me + Forgot password */}
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Checkbox id="remember" {...register('remember')} />
                <label htmlFor="remember" className="cursor-pointer text-sm text-foreground font-sans">Remember me</label>
              </div>
              <Link
                href="/forgot-password"
                className="text-sm font-medium text-primary font-heading hover:underline"
              >
                Forgot password?
              </Link>
            </div>
          </div>

          {/* Sign in CTA */}
          <Button type="submit" variant="solid-primary" size="md" className="w-full justify-center rounded-[14px]">
            Sign in
          </Button>

          <AuthDivider />

          <GoogleButton />

          {/* Sign up prompt */}
          <div className="flex items-center justify-center gap-1">
            <span className="text-sm text-muted-foreground font-sans">Don&apos;t have an account?</span>
            <Link
              href="/register"
              className="text-sm font-medium text-primary font-heading hover:underline"
            >
              Create one
            </Link>
          </div>
        </form>
      </div>
    </div>
  );
}
