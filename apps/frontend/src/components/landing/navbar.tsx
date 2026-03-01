'use client';

import { useState, useEffect } from 'react';
import { Menu, MessageCircle, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { createClient } from '@/lib/supabase/client';

const NAV_LINKS = [
  { label: 'Features', href: '#features' },
  { label: 'Pricing', href: '#pricing' },
  { label: 'Blog', href: '/blog' },
  { label: 'Docs', href: '#' },
];

interface NavbarProps {
  variant?: 'default' | 'dark';
}

export function Navbar({ variant = 'default' }: NavbarProps) {
  const isDark = variant === 'dark';
  const [mobileOpen, setMobileOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const [scrollProgress, setScrollProgress] = useState(0);
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
      const docHeight =
        document.documentElement.scrollHeight - window.innerHeight;
      setScrollProgress(docHeight > 0 ? (window.scrollY / docHeight) * 100 : 0);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  return (
    <>
      {/* Scroll progress bar */}
      <div className="fixed top-0 left-0 z-[60] h-[3px] w-full">
        <div
          className="h-full bg-primary transition-[width] duration-150"
          style={{ width: `${scrollProgress}%` }}
        />
      </div>

      <nav
        className={cn(
          'fixed top-[3px] left-0 z-50 w-full transition-all duration-200',
          scrolled
            ? isDark
              ? 'bg-[#0C0A1A]/95 shadow-sm shadow-black/20 backdrop-blur-md'
              : 'bg-white/95 shadow-sm backdrop-blur-md'
            : 'bg-transparent'
        )}
      >
        <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-20">
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
                  href="/register"
                  size="sm"
                  variant={scrolled && !isDark ? 'solid-primary' : 'solid-white'}
                >
                  Get Started
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
              'border-t px-6 py-4 md:hidden',
              scrolled && !isDark
                ? 'border-border bg-white'
                : 'border-white/10 bg-lp-hero-dark/95 backdrop-blur-md'
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
                      href="/register"
                      size="sm"
                      variant={scrolled && !isDark ? 'solid-primary' : 'solid-white'}
                      className="flex-1 justify-center"
                    >
                      Get Started
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
