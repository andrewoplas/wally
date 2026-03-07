'use client';

import { useState, useEffect } from 'react';
import { Menu, MessageCircle, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { createClient } from '@/lib/supabase/client';

const NAV_LINKS = [
  { label: 'Features', href: '#features' },
  { label: 'Demo', href: '#demo' },
  { label: 'Blog', href: '/blog' },
];

interface NavbarProps {
  variant?: 'default' | 'dark';
}

export function Navbar({ variant = 'default' }: NavbarProps) {
  const isDark = variant === 'dark';
  const [mobileOpen, setMobileOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  const [user, setUser] = useState<{
    initials: string;
    email: string;
  } | null>(null);

  useEffect(() => {
    createClient()
      .auth.getUser()
      .then(({ data }) => {
        if (!data.user) return;
        const first = (data.user.user_metadata?.['first_name'] as string) ?? '';
        const last = (data.user.user_metadata?.['last_name'] as string) ?? '';
        const initials =
          first || last
            ? `${first[0] ?? ''}${last[0] ?? ''}`.toUpperCase()
            : (data.user.email?.[0] ?? '?').toUpperCase();
        setUser({ initials, email: data.user.email ?? '' });
      });
  }, []);

  useEffect(() => {
    const onScroll = () => {
      setScrolled(window.scrollY > 20);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  return (
    <>
      <nav
        className={cn(
          'fixed left-0 z-50 w-full px-4 transition-all duration-500 ease-in-out',
          scrolled ? 'top-4' : 'top-0'
        )}
      >
        <div
          className={cn(
            'mx-auto flex max-w-7xl items-center justify-between rounded-full transition-all duration-500 ease-in-out',
            scrolled
              ? isDark
                ? 'max-w-5xl border border-white/10 bg-[#0C0A1A]/60 px-5 py-2.5 shadow-lg shadow-black/20 backdrop-blur-xl'
                : 'max-w-5xl border border-white/20 bg-white/60 px-5 py-2.5 shadow-lg shadow-black/5 backdrop-blur-xl'
              : 'border border-transparent bg-transparent px-6 py-4 lg:px-16'
          )}
        >
          {/* Logo */}
          <a href="/" className="flex items-center gap-2">
            <MessageCircle
              className={cn(
                'h-7 w-7 transition-colors',
                scrolled && !isDark ? 'text-primary' : 'text-lp-purple-light'
              )}
            />
            <span
              className={cn(
                'font-heading text-2xl font-bold transition-colors',
                scrolled && !isDark ? 'text-foreground' : 'text-white'
              )}
            >
              Wally
            </span>
          </a>

          {/* Desktop nav links */}
          <div className="hidden items-center gap-8 md:flex">
            {NAV_LINKS.map((link) => (
              <a
                key={link.label}
                href={link.href}
                className={cn(
                  'relative text-[15px] font-medium transition-colors after:absolute after:-bottom-1 after:left-0 after:h-[2px] after:w-0 after:bg-primary after:transition-all hover:after:w-full',
                  scrolled && !isDark
                    ? 'text-muted-foreground hover:text-foreground'
                    : 'text-lp-hero-muted hover:text-white'
                )}
              >
                {link.label}
              </a>
            ))}
          </div>

          {/* Desktop CTAs */}
          <div className="hidden items-center gap-3 md:flex">
            {user ? (
              <a
                href="/app/account"
                title={user.email}
                className="flex h-9 w-9 items-center justify-center rounded-full bg-primary transition-opacity hover:opacity-90"
              >
                <span className="font-sans text-sm font-semibold text-primary-foreground">
                  {user.initials}
                </span>
              </a>
            ) : (
              <>
                <Button
                  href="/login"
                  size="sm"
                  variant={scrolled && !isDark ? 'outline' : 'outline-dark'}
                >
                  Log In
                </Button>
                <Button
                  href="#waitlist"
                  size="sm"
                  variant={scrolled && !isDark ? 'solid-primary' : 'solid-white'}
                >
                  Join Waitlist
                </Button>
              </>
            )}
          </div>

          {/* Mobile menu button */}
          <button
            onClick={() => setMobileOpen(!mobileOpen)}
            className={cn(
              'md:hidden',
              scrolled && !isDark ? 'text-foreground' : 'text-white'
            )}
          >
            {mobileOpen ? (
              <X className="h-6 w-6" />
            ) : (
              <Menu className="h-6 w-6" />
            )}
          </button>
        </div>

        {/* Mobile menu */}
        {mobileOpen && (
          <div
            className={cn(
              'px-6 py-4 md:hidden',
              scrolled
                ? isDark
                  ? 'mx-auto mt-2 max-w-5xl rounded-2xl border border-white/10 bg-[#0C0A1A]/60 backdrop-blur-xl'
                  : 'mx-auto mt-2 max-w-5xl rounded-2xl border border-white/20 bg-white/60 backdrop-blur-xl'
                : 'border-t border-white/10 bg-lp-hero-dark/95 backdrop-blur-md'
            )}
          >
            <div className="flex flex-col gap-4">
              {NAV_LINKS.map((link) => (
                <a
                  key={link.label}
                  href={link.href}
                  onClick={() => setMobileOpen(false)}
                  className={cn(
                    'text-[15px] font-medium',
                    scrolled && !isDark ? 'text-foreground' : 'text-lp-hero-muted'
                  )}
                >
                  {link.label}
                </a>
              ))}
              <div className="flex gap-3 pt-2">
                {user ? (
                  <a
                    href="/app/account"
                    title={user.email}
                    className="flex h-9 w-9 items-center justify-center rounded-full bg-primary transition-opacity hover:opacity-90"
                  >
                    <span className="font-sans text-sm font-semibold text-primary-foreground">
                      {user.initials}
                    </span>
                  </a>
                ) : (
                  <>
                    <Button
                      href="/login"
                      size="sm"
                      variant={scrolled && !isDark ? 'outline' : 'outline-dark'}
                      className="flex-1 justify-center"
                    >
                      Log In
                    </Button>
                    <Button
                      href="#waitlist"
                      size="sm"
                      variant={scrolled && !isDark ? 'solid-primary' : 'solid-white'}
                      className="flex-1 justify-center"
                    >
                      Join Waitlist
                    </Button>
                  </>
                )}
              </div>
            </div>
          </div>
        )}
      </nav>
    </>
  );
}
