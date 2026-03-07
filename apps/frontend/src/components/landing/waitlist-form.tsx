'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import { Mail, Loader2, PartyPopper, Sparkles, Rocket } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';

const CHALLENGE_OPTIONS = [
  'Managing content (posts, pages, media)',
  'Updating plugins & themes',
  'Troubleshooting site issues',
  'Managing WooCommerce / e-commerce',
  'SEO & site optimization',
  'Handling repetitive admin tasks',
] as const;

type FormState = 'idle' | 'loading' | 'success' | 'error';

interface WaitlistFormProps {
  source?: string;
  variant?: 'light' | 'dark';
}

export function WaitlistForm({ source = 'landing', variant = 'light' }: WaitlistFormProps) {
  const [email, setEmail] = useState('');
  const [challenges, setChallenges] = useState<string[]>([]);
  const [otherChallenge, setOtherChallenge] = useState('');
  const [state, setState] = useState<FormState>('idle');
  const [errorMsg, setErrorMsg] = useState('');

  const toggleChallenge = (challenge: string) => {
    setChallenges((prev) =>
      prev.includes(challenge) ? prev.filter((c) => c !== challenge) : [...prev, challenge]
    );
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setState('loading');

    const allChallenges = [...challenges];
    if (otherChallenge.trim()) {
      allChallenges.push(otherChallenge.trim());
    }

    try {
      const res = await fetch('/api/waitlist', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, source, challenges: allChallenges }),
      });
      const json = await res.json();
      if (!res.ok) {
        setErrorMsg(json.error ?? 'Something went wrong. Please try again.');
        setState('error');
      } else {
        setState('success');
        if (typeof window !== 'undefined' && typeof window.gtag === 'function') {
          window.gtag('event', 'waitlist_signup', { source });
        }
      }
    } catch {
      setErrorMsg('Network error. Please try again.');
      setState('error');
    }
  };

  const isDark = variant === 'dark';

  if (state === 'success') {
    return (
      <motion.div
        initial={{ opacity: 0, scale: 0.9 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{ type: 'spring', stiffness: 300, damping: 20 }}
        className={`flex flex-col items-center gap-5 rounded-3xl px-10 py-8 text-center ${isDark ? 'border border-white/15 bg-white/[0.08] backdrop-blur-sm' : 'border border-primary/10 bg-white shadow-[0_8px_40px_rgba(139,92,246,0.1)]'}`}
      >
        {/* Animated icon cluster */}
        <div className="relative">
          <motion.div
            initial={{ scale: 0, rotate: -20 }}
            animate={{ scale: 1, rotate: 0 }}
            transition={{ type: 'spring', stiffness: 400, damping: 15, delay: 0.1 }}
            className={`flex h-16 w-16 items-center justify-center rounded-2xl ${isDark ? 'bg-white/15' : 'bg-primary/10'}`}
          >
            <PartyPopper className={`h-8 w-8 ${isDark ? 'text-white' : 'text-primary'}`} />
          </motion.div>
          <motion.div
            initial={{ scale: 0, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            transition={{ delay: 0.3, type: 'spring', stiffness: 500 }}
            className={`absolute -right-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full ${isDark ? 'bg-white/20' : 'bg-primary/15'}`}
          >
            <Sparkles className={`h-3.5 w-3.5 ${isDark ? 'text-white' : 'text-primary'}`} />
          </motion.div>
        </div>

        {/* Text */}
        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="flex flex-col gap-2"
        >
          <p className={`font-heading text-xl font-extrabold ${isDark ? 'text-white' : 'text-foreground'}`}>
            You&apos;re in! Welcome aboard.
          </p>
          <p className={`max-w-[340px] text-sm leading-relaxed ${isDark ? 'text-white/70' : 'text-muted-foreground'}`}>
            We&apos;ll send your early access invite soon.
            <br />
            You&apos;re going to love what we&apos;re building.
          </p>
        </motion.div>

        {/* Confidence badge */}
        <motion.div
          initial={{ opacity: 0, y: 8 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.4 }}
          className={`inline-flex items-center gap-2 rounded-full px-4 py-2 text-[13px] font-semibold ${isDark ? 'bg-white/10 text-white/80' : 'bg-primary/[0.08] text-primary'}`}
        >
          <Rocket className="h-3.5 w-3.5" />
          Launching soon — you&apos;ll be first to know
        </motion.div>
      </motion.div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="flex w-full max-w-md flex-col gap-5">
      <div className="flex flex-col gap-3 sm:flex-row">
        <Input
          type="email"
          placeholder="you@example.com"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
          disabled={state === 'loading'}
          error={state === 'error'}
          className={`flex-1 ${isDark ? 'border-white/20 bg-white/10 text-white placeholder:text-white/50' : ''}`}
        />
        <Button
          type="submit"
          variant={isDark ? 'solid-white' : 'solid-primary'}
          size="md"
          disabled={state === 'loading'}
          icon={state === 'loading' ? <Loader2 className="h-4 w-4 animate-spin" /> : <Mail className="h-4 w-4" />}
        >
          {state === 'loading' ? 'Joining...' : 'Join Waitlist'}
        </Button>
      </div>

      {/* Optional challenges multi-select */}
      <div className="flex flex-col gap-3">
        <p className={`text-left text-[13px] font-medium ${isDark ? 'text-white/70' : 'text-muted-foreground'}`}>
          What&apos;s your biggest WordPress challenge? <span className={`font-normal ${isDark ? 'text-white/40' : 'text-muted-foreground/60'}`}>(optional)</span>
        </p>
        <div className="flex flex-wrap gap-2">
          {CHALLENGE_OPTIONS.map((challenge) => {
            const selected = challenges.includes(challenge);
            return (
              <label
                key={challenge}
                className={`inline-flex cursor-pointer items-center gap-2 rounded-full border px-3 py-1.5 text-[13px] transition-colors ${
                  selected
                    ? isDark
                      ? 'border-white/30 bg-white/15 text-white'
                      : 'border-primary/30 bg-primary/[0.08] text-primary'
                    : isDark
                      ? 'border-white/10 bg-white/[0.04] text-white/60 hover:border-white/20 hover:bg-white/[0.08]'
                      : 'border-border bg-background text-muted-foreground hover:border-primary/20 hover:bg-primary/[0.04]'
                }`}
              >
                <Checkbox
                  checked={selected}
                  onChange={() => toggleChallenge(challenge)}
                  disabled={state === 'loading'}
                  className="h-3.5 w-3.5"
                />
                {challenge}
              </label>
            );
          })}
        </div>
        <Input
          type="text"
          placeholder="Other — tell us yours"
          value={otherChallenge}
          onChange={(e) => setOtherChallenge(e.target.value)}
          disabled={state === 'loading'}
          className={`${isDark ? 'border-white/20 bg-white/10 text-white placeholder:text-white/50' : ''}`}
        />
      </div>

      {state === 'error' && (
        <p className="w-full text-center text-sm text-destructive sm:text-left">{errorMsg}</p>
      )}
    </form>
  );
}
