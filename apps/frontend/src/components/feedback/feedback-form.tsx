'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import { Send, Loader2, PartyPopper, Sparkles, Bug, Lightbulb, MessageCircle } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

type FormState = 'idle' | 'loading' | 'success' | 'error';

const CATEGORIES = [
  { value: 'bug', label: 'Bug Report', icon: Bug },
  { value: 'feature', label: 'Feature Request', icon: Lightbulb },
  { value: 'general', label: 'General Feedback', icon: MessageCircle },
] as const;

export function FeedbackForm() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [category, setCategory] = useState('general');
  const [message, setMessage] = useState('');
  const [state, setState] = useState<FormState>('idle');
  const [errorMsg, setErrorMsg] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!message.trim()) return;

    setState('loading');
    try {
      const res = await fetch('/api/feedback', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          message: message.trim(),
          category,
          ...(email && { email }),
          ...(name && { name }),
        }),
      });
      const json = await res.json();
      if (!res.ok) {
        setErrorMsg(json.error ?? 'Something went wrong. Please try again.');
        setState('error');
      } else {
        setState('success');
      }
    } catch {
      setErrorMsg('Network error. Please try again.');
      setState('error');
    }
  };

  if (state === 'success') {
    return (
      <motion.div
        initial={{ opacity: 0, scale: 0.9 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{ type: 'spring', stiffness: 300, damping: 20 }}
        className="flex flex-col items-center gap-5 rounded-3xl border border-primary/10 bg-white px-10 py-8 text-center shadow-[0_8px_40px_rgba(139,92,246,0.1)]"
      >
        <div className="relative">
          <motion.div
            initial={{ scale: 0, rotate: -20 }}
            animate={{ scale: 1, rotate: 0 }}
            transition={{ type: 'spring', stiffness: 400, damping: 15, delay: 0.1 }}
            className="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10"
          >
            <PartyPopper className="h-8 w-8 text-primary" />
          </motion.div>
          <motion.div
            initial={{ scale: 0, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            transition={{ delay: 0.3, type: 'spring', stiffness: 500 }}
            className="absolute -right-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full bg-primary/15"
          >
            <Sparkles className="h-3.5 w-3.5 text-primary" />
          </motion.div>
        </div>

        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="flex flex-col gap-2"
        >
          <p className="font-heading text-xl font-extrabold text-foreground">
            Thank you for your feedback!
          </p>
          <p className="max-w-[340px] text-sm leading-relaxed text-muted-foreground">
            We appreciate you taking the time to help us improve Wally. Your input helps us build a better product.
          </p>
        </motion.div>
      </motion.div>
    );
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="flex w-full max-w-lg flex-col gap-5 rounded-3xl border border-border bg-white p-8 shadow-[0_8px_40px_rgba(0,0,0,0.06)]"
    >
      {/* Name */}
      <div className="flex flex-col gap-1.5">
        <label className="text-sm font-medium text-foreground">
          Name <span className="text-muted-foreground">(optional)</span>
        </label>
        <Input
          type="text"
          placeholder="Your name"
          value={name}
          onChange={(e) => setName(e.target.value)}
          disabled={state === 'loading'}
        />
      </div>

      {/* Email */}
      <div className="flex flex-col gap-1.5">
        <label className="text-sm font-medium text-foreground">
          Email <span className="text-muted-foreground">(optional)</span>
        </label>
        <Input
          type="email"
          placeholder="you@example.com"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          disabled={state === 'loading'}
        />
      </div>

      {/* Category */}
      <div className="flex flex-col gap-1.5">
        <label className="text-sm font-medium text-foreground">Category</label>
        <div className="flex gap-2">
          {CATEGORIES.map(({ value, label, icon: Icon }) => (
            <button
              key={value}
              type="button"
              onClick={() => setCategory(value)}
              className={`flex flex-1 items-center justify-center gap-2 rounded-[14px] border px-3 py-3 text-sm font-medium transition-colors ${
                category === value
                  ? 'border-primary bg-primary/5 text-primary'
                  : 'border-border bg-white text-muted-foreground hover:border-primary/30 hover:bg-muted'
              }`}
            >
              <Icon className="h-4 w-4" />
              <span className="hidden sm:inline">{label}</span>
            </button>
          ))}
        </div>
      </div>

      {/* Message */}
      <div className="flex flex-col gap-1.5">
        <label className="text-sm font-medium text-foreground">
          Message <span className="text-destructive">*</span>
        </label>
        <textarea
          placeholder="Tell us what's on your mind..."
          value={message}
          onChange={(e) => setMessage(e.target.value)}
          required
          disabled={state === 'loading'}
          rows={5}
          className="w-full resize-none rounded-[14px] border border-border bg-white px-[18px] py-3 text-sm text-foreground outline-none transition-colors focus:border-primary"
        />
      </div>

      {state === 'error' && (
        <p className="text-center text-sm text-destructive">{errorMsg}</p>
      )}

      <Button
        type="submit"
        variant="solid-primary"
        size="md"
        disabled={state === 'loading' || !message.trim()}
        icon={
          state === 'loading' ? (
            <Loader2 className="h-4 w-4 animate-spin" />
          ) : (
            <Send className="h-4 w-4" />
          )
        }
      >
        {state === 'loading' ? 'Sending...' : 'Send Feedback'}
      </Button>
    </form>
  );
}
