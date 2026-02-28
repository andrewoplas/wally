'use client';

import { motion } from 'framer-motion';
import { MessageCircle } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface AuthLeftPanelItem {
  badge: React.ReactNode;
  badgeOpacity?: 'high' | 'low';
  title: string;
  titleMuted?: boolean;
  description: string;
  descriptionMuted?: boolean;
}

interface AuthLeftPanelProps {
  headline: string;
  subheadline: string;
  items: AuthLeftPanelItem[];
}

const PARTICLES = [
  { x: '43.8%', y: '8.9%',  size: 3,   opacity: 0.5,  color: '#ffffff', floatDelay: 0,    twinkleDelay: 0.3,  floatDuration: 5.2, twinkleDuration: 2.8 },
  { x: '81.3%', y: '13.3%', size: 2,   opacity: 0.4,  color: '#ffffff', floatDelay: 0.8,  twinkleDelay: 1.1,  floatDuration: 4.6, twinkleDuration: 3.2 },
  { x: '12.5%', y: '28.9%', size: 4,   opacity: 0.35, color: '#C4B5FD', floatDelay: 1.4,  twinkleDelay: 0.7,  floatDuration: 6.1, twinkleDuration: 2.5 },
  { x: '90.6%', y: '22.2%', size: 2,   opacity: 0.3,  color: '#ffffff', floatDelay: 0.3,  twinkleDelay: 1.8,  floatDuration: 4.9, twinkleDuration: 3.6 },
  { x: '53.1%', y: '5.6%',  size: 3,   opacity: 0.45, color: '#ffffff', floatDelay: 1.1,  twinkleDelay: 0,    floatDuration: 5.7, twinkleDuration: 2.2 },
  { x: '25%',   y: '16.7%', size: 1.5, opacity: 0.35, color: '#ffffff', floatDelay: 0.6,  twinkleDelay: 2.1,  floatDuration: 4.3, twinkleDuration: 3.9 },
  { x: '71.9%', y: '6.7%',  size: 2,   opacity: 0.4,  color: '#DDD6FE', floatDelay: 1.7,  twinkleDelay: 0.5,  floatDuration: 5.5, twinkleDuration: 2.7 },
  { x: '93.8%', y: '35.6%', size: 1.5, opacity: 0.25, color: '#ffffff', floatDelay: 0.4,  twinkleDelay: 1.4,  floatDuration: 4.8, twinkleDuration: 3.4 },
  { x: '15.6%', y: '46.7%', size: 3,   opacity: 0.3,  color: '#C4B5FD', floatDelay: 2.1,  twinkleDelay: 2.8,  floatDuration: 6.8, twinkleDuration: 4.5 },
  { x: '68.8%', y: '80%',   size: 2,   opacity: 0.2,  color: '#ffffff', floatDelay: 1.3,  twinkleDelay: 1.1,  floatDuration: 5.9, twinkleDuration: 3.3 },
  { x: '34.4%', y: '75.6%', size: 1.5, opacity: 0.2,  color: '#DDD6FE', floatDelay: 2.5,  twinkleDelay: 3.2,  floatDuration: 7.2, twinkleDuration: 4.8 },
  { x: '87.5%', y: '88.9%', size: 2,   opacity: 0.2,  color: '#ffffff', floatDelay: 0.9,  twinkleDelay: 0.9,  floatDuration: 5.3, twinkleDuration: 2.9 },
  { x: '7.8%',  y: '62.2%', size: 3.5, opacity: 0.2,  color: '#C4B5FD', floatDelay: 3.0,  twinkleDelay: 4.0,  floatDuration: 8.0, twinkleDuration: 5.5 },
  { x: '59.4%', y: '53.3%', size: 1.5, opacity: 0.3,  color: '#ffffff', floatDelay: 0.2,  twinkleDelay: 0.2,  floatDuration: 4.1, twinkleDuration: 2.2 },
  { x: '95.3%', y: '64.4%', size: 2,   opacity: 0.2,  color: '#DDD6FE', floatDelay: 1.9,  twinkleDelay: 2.5,  floatDuration: 6.4, twinkleDuration: 3.7 },
];

export function AuthLeftPanel({ headline, subheadline, items }: AuthLeftPanelProps) {
  return (
    <div
      className="relative hidden w-1/2 shrink-0 overflow-hidden lg:flex"
      style={{ background: 'hsl(var(--lp-hero-dark))' }}
    >
      {/* Dot grid — fine CSS radial-gradient pattern, vignette-masked at edges */}
      <div
        className="absolute inset-0"
        style={{
          backgroundImage: 'radial-gradient(circle, rgba(255,255,255,0.4) 1px, transparent 1px)',
          backgroundSize: '20px 20px',
          maskImage: 'radial-gradient(ellipse 85% 85% at 50% 50%, black 30%, transparent 100%)',
          WebkitMaskImage: 'radial-gradient(ellipse 85% 85% at 50% 50%, black 30%, transparent 100%)',
          opacity: 0.1,
        }}
      />

      {/* Ambient glow — soft multi-layer radial blobs, animated breathing */}
      <motion.div
        className="absolute inset-0"
        style={{
          background: `
            radial-gradient(ellipse 75% 60% at 25% 65%, rgba(124,58,237,0.2) 0%, transparent 70%),
            radial-gradient(ellipse 55% 45% at 75% 25%, rgba(87,73,244,0.11) 0%, transparent 65%),
            radial-gradient(ellipse 45% 40% at 50% 85%, rgba(167,139,250,0.07) 0%, transparent 60%)
          `,
        }}
        animate={{ opacity: [0.7, 1, 0.7], scale: [1, 1.06, 1] }}
        transition={{ duration: 8, repeat: Infinity, ease: 'easeInOut' }}
      />

      {/* Star particles */}
      {PARTICLES.map((p, i) => (
        <motion.div
          key={i}
          className="absolute rounded-full"
          style={{
            left: p.x,
            top: p.y,
            width: p.size,
            height: p.size,
            backgroundColor: p.color,
            filter: 'blur(0.3px)',
          }}
          animate={{
            opacity: [p.opacity * 0.25, p.opacity, p.opacity * 0.15, p.opacity * 0.85, p.opacity * 0.25],
            scale: [1, 1.5, 0.8, 1.3, 1],
            y: [0, -5, -1, -7, 0],
            x: [0, 2, -1, 1, 0],
          }}
          transition={{
            duration: p.floatDuration,
            repeat: Infinity,
            ease: 'easeInOut',
            delay: p.floatDelay,
            times: [0, 0.25, 0.5, 0.75, 1],
            opacity: {
              duration: p.twinkleDuration,
              repeat: Infinity,
              ease: 'easeInOut',
              delay: p.twinkleDelay,
            },
            scale: {
              duration: p.twinkleDuration,
              repeat: Infinity,
              ease: 'easeInOut',
              delay: p.twinkleDelay,
            },
          }}
        />
      ))}

      {/* Content */}
      <div className="relative z-10 flex w-full flex-col justify-center gap-12 px-14 py-16">
        {/* Logo + headline + subheadline */}
        <div className="flex flex-col gap-4">
          <div className="flex items-center gap-2.5">
            <MessageCircle className="text-[#A78BFA]" size={32} />
            <span className="font-heading text-[28px] font-bold text-white">Wally</span>
          </div>

          <h1 className="whitespace-pre-line font-heading text-[32px] font-bold leading-[1.25] text-white">
            {headline}
          </h1>

          <p className="font-sans text-[15px] leading-[1.6]" style={{ color: '#B8B0D0' }}>
            {subheadline}
          </p>
        </div>

        {/* Purple divider */}
        <div className="h-0.5 w-10 rounded-full bg-primary" />

        {/* Feature / step items */}
        <div className="flex flex-col gap-5">
          {items.map((item, i) => (
            <div key={i} className="flex w-full items-center gap-3.5">
              <div
                className={cn(
                  'flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px]',
                  item.badgeOpacity === 'high' ? 'bg-[#7C3AED99]' : 'bg-[#7C3AED4D]',
                )}
              >
                {item.badge}
              </div>

              <div className="flex flex-1 flex-col gap-0.5">
                <span
                  className={cn(
                    'font-heading text-sm font-semibold',
                    item.titleMuted ? 'text-[#B8B0D0]' : 'text-white',
                  )}
                >
                  {item.title}
                </span>
                <span
                  className="font-sans text-[13px]"
                  style={{ color: item.descriptionMuted ? 'rgba(184,176,208,0.5)' : '#B8B0D0' }}
                >
                  {item.description}
                </span>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
