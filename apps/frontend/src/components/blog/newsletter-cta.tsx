'use client';

import { cn } from '@/lib/utils';

interface NewsletterCtaProps {
  variant: 'light' | 'dark';
}

export function NewsletterCta({ variant }: NewsletterCtaProps) {
  const isDark = variant === 'dark';

  return (
    <section
      className={cn(
        'flex flex-col items-center gap-7 px-6 py-16 md:gap-8 md:px-20 md:py-20 lg:px-[120px]',
        isDark
          ? 'border-y border-[#1E1A32] bg-[#14112A]'
          : 'bg-[#F5F3FF]'
      )}
    >
      <h2
        className={cn(
          'text-center font-heading text-2xl font-bold md:text-3xl lg:text-4xl',
          isDark ? 'text-[#E8E4F0] lg:text-[32px]' : 'text-[#18181B] lg:text-[36px]'
        )}
      >
        Stay in the loop
      </h2>

      <p
        className={cn(
          'max-w-[550px] text-center text-[15px] leading-[1.6] md:text-base lg:text-[17px]',
          isDark
            ? 'max-w-[500px] text-[#6B6580] lg:text-base'
            : 'text-[#71717A]'
        )}
      >
        Get the latest Wally tips, product updates, and WordPress insights
        delivered to your inbox.
      </p>

      <form
        onSubmit={(e) => e.preventDefault()}
        className="flex flex-col items-center gap-3 sm:flex-row"
      >
        <input
          type="email"
          placeholder="Enter your email address"
          className={cn(
            'w-full rounded-pill px-5 py-3.5 text-[15px] outline-none transition-colors sm:w-[340px]',
            isDark
              ? 'border border-[#2B2840] bg-[#1A1730] text-[#E8E4F0] placeholder-[#5E5870] focus:border-[#3D3650]'
              : 'border border-[#E4E4E7] bg-white text-[#18181B] placeholder-[#A1A1AA] focus:border-[#A78BFA]'
          )}
        />
        <button
          type="submit"
          className="w-full rounded-pill bg-primary px-6 py-3.5 text-[15px] font-semibold text-white transition-colors hover:bg-primary/90 sm:w-auto"
        >
          Subscribe
        </button>
      </form>

      <p
        className={cn(
          'text-[13px]',
          isDark ? 'text-[#4A4458]' : 'text-[#A1A1AA]'
        )}
      >
        No spam. Unsubscribe anytime.
      </p>
    </section>
  );
}
