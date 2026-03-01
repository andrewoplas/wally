'use client';

import { motion } from 'framer-motion';
import {
  Sparkles,
  Download,
  CircleCheck,
  ShieldCheck,
  MessageCircle,
  Pencil,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';

const floatingCards = [
  {
    id: 'plugin-updated',
    icon: <div className="h-2 w-2 rounded-full bg-emerald-400" />,
    label: 'Plugin Updated',
    detail: 'Yoast SEO v21.6 → v21.7',
    x: '3%',
    y: '13%',
    rotation: -4,
    opacity: 0.7,
    width: 220,
    floatDuration: 4,
    floatDelay: 0,
    floatY: -8,
    floatRotateMid: -3,
  },
  {
    id: 'search-replace',
    icon: <div className="h-2 w-2 rounded-full bg-primary" />,
    label: '24 Replaced',
    detail: '"2025" → "2026" across 12 pages',
    x: '82%',
    y: '17%',
    rotation: 3,
    opacity: 0.7,
    width: 220,
    floatDuration: 5,
    floatDelay: 0.8,
    floatY: -10,
    floatRotateMid: 4,
  },
  {
    id: 'page-published',
    icon: <CircleCheck className="h-3.5 w-3.5 text-emerald-400" />,
    label: 'Page published',
    detail: '"About Us" is now live',
    x: '4%',
    y: '73%',
    rotation: 2,
    opacity: 0.55,
    width: 200,
    floatDuration: 4.5,
    floatDelay: 1.6,
    floatY: -6,
    floatRotateMid: 3,
  },
  {
    id: 'action-confirmed',
    icon: <ShieldCheck className="h-3.5 w-3.5 text-amber-400" />,
    label: 'Action confirmed',
    detail: null,
    x: '83%',
    y: '77%',
    rotation: -2,
    opacity: 0.55,
    width: 190,
    floatDuration: 3.5,
    floatDelay: 2.4,
    floatY: -7,
    floatRotateMid: -1,
  },
  {
    id: 'message-indicator',
    icon: <MessageCircle className="h-3 w-3 text-white/50" />,
    label: '50+ tasks completed today',
    detail: null,
    x: '76%',
    y: '52%',
    rotation: 5,
    opacity: 0.45,
    width: 'auto',
    pill: true,
    floatDuration: 5.5,
    floatDelay: 1.2,
    floatY: -6,
    floatRotateMid: 6,
  },
  {
    id: 'content-edit',
    icon: <Pencil className="h-3 w-3 text-white/50" />,
    label: 'Post title updated',
    detail: null,
    x: '6%',
    y: '45%',
    rotation: -3,
    opacity: 0.45,
    width: 'auto',
    pill: true,
    floatDuration: 4.8,
    floatDelay: 2,
    floatY: -8,
    floatRotateMid: -4,
  },
];

const backgroundOrbs = [
  {
    id: 'orb-1',
    size: 480,
    top: '-10%',
    left: '-5%',
    opacity: 0.18,
    driftX: 18,
    driftY: 24,
    duration: 18,
    delay: 0,
  },
  {
    id: 'orb-2',
    size: 360,
    top: '20%',
    left: '60%',
    opacity: 0.14,
    driftX: -22,
    driftY: 16,
    duration: 22,
    delay: 3,
  },
  {
    id: 'orb-3',
    size: 300,
    top: '55%',
    left: '10%',
    opacity: 0.12,
    driftX: 14,
    driftY: -20,
    duration: 26,
    delay: 6,
  },
  {
    id: 'orb-4',
    size: 260,
    top: '60%',
    left: '75%',
    opacity: 0.1,
    driftX: -12,
    driftY: -18,
    duration: 20,
    delay: 9,
  },
];

export function FinalCtaSection() {
  const [loaded, setLoaded] = useState(false);

  useEffect(() => {
    const timer = setTimeout(() => setLoaded(true), 600);
    return () => clearTimeout(timer);
  }, []);

  return (
    <section className="relative overflow-hidden">
      {/* Animated background */}
      <div className="absolute inset-0 animate-hue-shift" />

      {/* Drifting background orbs — start moving after load */}
      <div className="pointer-events-none absolute inset-0">
        {backgroundOrbs.map((orb) => (
          <motion.div
            key={orb.id}
            className="absolute rounded-full"
            style={{
              width: orb.size,
              height: orb.size,
              top: orb.top,
              left: orb.left,
              background:
                'radial-gradient(circle, rgba(255,255,255,0.22) 0%, rgba(255,255,255,0) 70%)',
              opacity: orb.opacity,
            }}
            animate={
              loaded
                ? {
                    x: [0, orb.driftX, 0, -orb.driftX * 0.6, 0],
                    y: [0, orb.driftY * 0.5, orb.driftY, orb.driftY * 0.3, 0],
                  }
                : {}
            }
            transition={{
              duration: orb.duration,
              delay: orb.delay,
              repeat: Infinity,
              ease: 'easeInOut',
            }}
          />
        ))}
      </div>

      {/* Gradient overlays */}
      <div
        className="absolute inset-x-0 top-0 h-[120px]"
        style={{
          background:
            'linear-gradient(180deg, rgba(124,58,237,0.27) 0%, transparent 100%)',
        }}
      />
      <div
        className="absolute inset-x-0 bottom-0 h-[120px]"
        style={{
          background:
            'linear-gradient(0deg, rgba(109,40,217,0.27) 0%, transparent 100%)',
        }}
      />

      {/* Floating cards — hidden on mobile */}
      <div className="pointer-events-none absolute inset-0 hidden md:block">
        {floatingCards.map((card, i) => (
          <motion.div
            key={card.id}
            className="absolute"
            style={{ left: card.x, top: card.y }}
            initial={{ opacity: 0, scale: 0.85, rotate: card.rotation, y: 0 }}
            animate={
              loaded
                ? {
                    opacity: card.opacity,
                    scale: 1,
                    rotate: [card.rotation, card.floatRotateMid, card.rotation],
                    y: [0, card.floatY, 0],
                  }
                : { opacity: 0, scale: 0.85, rotate: card.rotation, y: 0 }
            }
            transition={
              loaded
                ? {
                    opacity: { delay: 0.2 + i * 0.12, duration: 0.5 },
                    scale: { delay: 0.2 + i * 0.12, duration: 0.5 },
                    rotate: {
                      delay: 0.2 + i * 0.12 + 0.5,
                      duration: card.floatDuration,
                      repeat: Infinity,
                      ease: 'easeInOut',
                      repeatDelay: 0,
                    },
                    y: {
                      delay: 0.2 + i * 0.12 + 0.5,
                      duration: card.floatDuration,
                      repeat: Infinity,
                      ease: 'easeInOut',
                      repeatDelay: 0,
                    },
                  }
                : { delay: 0.2 + i * 0.12, duration: 0.5 }
            }
          >
            <div
              className={`
                ${card.pill ? 'rounded-full px-3.5 py-2' : 'rounded-2xl px-[18px] py-3.5'}
                border border-white/[0.12] bg-white/[0.09]
              `}
              style={{ width: card.width === 'auto' ? 'auto' : card.width }}
            >
              {card.detail ? (
                <div className="flex flex-col gap-2">
                  <div className="flex items-center gap-2">
                    {card.icon}
                    <span className="text-xs font-semibold text-white/[0.73]">
                      {card.label}
                    </span>
                  </div>
                  <span className="text-[11px] text-white/[0.47]">
                    {card.detail}
                  </span>
                </div>
              ) : (
                <div className="flex items-center gap-1.5">
                  {card.icon}
                  <span className="text-[10px] font-medium text-white/[0.47]">
                    {card.label}
                  </span>
                </div>
              )}
            </div>
          </motion.div>
        ))}
      </div>

      {/* Content */}
      <div className="relative z-10 flex min-h-[600px] flex-col items-center justify-center px-4 py-20">
        <motion.div
          initial={{ scale: 0, opacity: 0 }}
          whileInView={{ scale: 1, opacity: 1 }}
          viewport={{ once: true }}
          transition={{
            type: 'spring',
            stiffness: 300,
            damping: 15,
            delay: 0.3,
          }}
        >
          <span className="inline-flex items-center gap-2 rounded-full bg-white/20 px-5 py-2 text-sm font-semibold text-white/80">
            <Sparkles className="h-4 w-4" />
            Ready to get started?
          </span>
        </motion.div>

        <motion.h2
          className="mt-8 max-w-[800px] text-center font-heading text-3xl font-extrabold !leading-tight text-white sm:text-4xl md:text-[48px]"
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ delay: 0.5, duration: 0.6 }}
        >
          Stop navigating menus.
          <br />
          Start just asking.
        </motion.h2>

        <motion.p
          className="mt-6 max-w-[640px] text-center text-base leading-[1.6] text-white/80 md:text-lg"
          initial={{ opacity: 0, y: 15 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ delay: 0.7, duration: 0.5 }}
        >
          Join thousands of WordPress users who manage their sites through
          natural conversation. Free forever — no credit card needed.
        </motion.p>

        <motion.div
          className="mt-10 flex flex-col items-center gap-4 sm:flex-row"
          initial={{ opacity: 0, scale: 0.9 }}
          whileInView={{ opacity: 1, scale: 1 }}
          viewport={{ once: true }}
          transition={{ delay: 0.9, duration: 0.4 }}
        >
          <Button
            href="/app/license"
            variant="solid-white"
            icon={<Download className="h-5 w-5" />}
            className="animate-pulse-glow"
          >
            Get Started
          </Button>
          <Button href="#" variant="ghost-dark">
            Get notified of updates
          </Button>
        </motion.div>

        <motion.p
          className="mt-8 text-sm font-medium text-white/50"
          initial={{ opacity: 0 }}
          whileInView={{ opacity: 1 }}
          viewport={{ once: true }}
          transition={{ delay: 1.1, duration: 0.4 }}
        >
          Free forever &middot; No credit card &middot; 2-minute setup
        </motion.p>
      </div>
    </section>
  );
}
