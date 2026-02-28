'use client';

import { motion, AnimatePresence } from 'framer-motion';
import { useEffect, useState } from 'react';
import {
  Sparkles,
  ChevronLeft,
  EllipsisVertical,
  ArrowUp,
  LayoutDashboard,
  FileText,
  Image,
  Puzzle,
  Paintbrush,
  Users,
  Settings,
  PencilLine,
  CircleCheck,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';

const stars = [
  { x: '19%', y: '6%', size: 4, opacity: 0.5, floatDelay: 0, twinkleDelay: 0.3, floatDuration: 5.2, twinkleDuration: 2.8 },
  { x: '78%', y: '9%', size: 3, opacity: 0.4, floatDelay: 0.8, twinkleDelay: 1.1, floatDuration: 4.6, twinkleDuration: 3.2 },
  { x: '12%', y: '22%', size: 5, opacity: 0.35, purple: true, floatDelay: 1.4, twinkleDelay: 0.7, floatDuration: 6.1, twinkleDuration: 2.5 },
  { x: '89%', y: '17%', size: 3, opacity: 0.3, floatDelay: 0.3, twinkleDelay: 1.8, floatDuration: 4.9, twinkleDuration: 3.6 },
  { x: '50%', y: '4%', size: 4, opacity: 0.45, floatDelay: 1.1, twinkleDelay: 0, floatDuration: 5.7, twinkleDuration: 2.2 },
  { x: '35%', y: '12%', size: 2, opacity: 0.35, floatDelay: 0.6, twinkleDelay: 2.1, floatDuration: 4.3, twinkleDuration: 3.9 },
  { x: '66%', y: '5%', size: 3, opacity: 0.4, purple: true, floatDelay: 1.7, twinkleDelay: 0.5, floatDuration: 5.5, twinkleDuration: 2.7 },
];

const wpMenuItems = [
  { icon: LayoutDashboard, label: 'Dashboard', active: false },
  { icon: FileText, label: 'Posts', active: false },
  { icon: FileText, label: 'Pages', active: true },
  { icon: Image, label: 'Media', active: false },
  { icon: Puzzle, label: 'Plugins', active: false },
  { icon: Paintbrush, label: 'Appearance', active: false },
  { icon: Users, label: 'Users', active: false },
  { icon: Settings, label: 'Settings', active: false },
];

const USER_MESSAGE = 'Make the About Us intro more professional and compelling';

function useTypingAnimation(text: string, startDelay: number, speed = 40) {
  const [displayed, setDisplayed] = useState('');
  const [done, setDone] = useState(false);

  useEffect(() => {
    let timeout: ReturnType<typeof setTimeout>;
    timeout = setTimeout(() => {
      let i = 0;
      const interval = setInterval(() => {
        i++;
        setDisplayed(text.slice(0, i));
        if (i >= text.length) {
          clearInterval(interval);
          setDone(true);
        }
      }, speed);
      return () => clearInterval(interval);
    }, startDelay);
    return () => clearTimeout(timeout);
  }, [text, startDelay, speed]);

  return { displayed, done };
}

function ChatSidebarPreview({
  className,
  style,
}: {
  className?: string;
  style?: Record<string, string | number>;
}) {
  // Animation timeline (ms → s for framer-motion delays):
  // 1.8s  – sidebar slides in
  // 2.6s  – user starts typing
  // USER_MESSAGE.length * 40ms ≈ 2.2s to finish typing
  // ~5.0s – sent bubble appears, assistant card fades in

  const SIDEBAR_DELAY = 1.8;
  const TYPING_START_DELAY_MS = 2600;
  const SENT_DELAY = TYPING_START_DELAY_MS / 1000 + USER_MESSAGE.length * 0.04 + 0.3;
  const ASSISTANT_DELAY = SENT_DELAY + 0.6;

  const { displayed: typedText, done: typingDone } = useTypingAnimation(
    USER_MESSAGE,
    TYPING_START_DELAY_MS,
    40
  );

  return (
    <motion.div
      className={cn(
        'flex-shrink-0 flex-col overflow-hidden rounded-[20px] border border-[#E4E4E7] bg-white shadow-[0_8px_40px_rgba(139,92,246,0.25),0_20px_80px_rgba(0,0,0,0.12)]',
        className
      )}
      style={style}
      initial={{ opacity: 0, x: 40, scale: 0.96 }}
      animate={{ opacity: 1, x: 0, scale: 1 }}
      transition={{
        type: 'spring',
        stiffness: 200,
        damping: 24,
        delay: SIDEBAR_DELAY,
      }}
    >
      {/* Chat Header */}
      <div className="flex flex-shrink-0 items-center justify-between border-b border-[#E4E4E7] px-5 py-4">
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#F4F4F5]">
            <ChevronLeft className="h-[18px] w-[18px] text-[#71717A]" />
          </div>
          <span className="font-heading text-[17px] font-bold text-[#18181B]">
            Edit Page Content
          </span>
        </div>
        <div className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#F4F4F5]">
          <EllipsisVertical className="h-[18px] w-[18px] text-[#71717A]" />
        </div>
      </div>

      {/* Messages */}
      <div className="flex flex-1 flex-col gap-4 overflow-hidden p-5">
        {/* User sent bubble */}
        <AnimatePresence>
          {typingDone && (
            <motion.div
              className="flex justify-end"
              initial={{ opacity: 0, y: 8, scale: 0.96 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              transition={{ type: 'spring', stiffness: 300, damping: 22, delay: 0 }}
            >
              <div className="rounded-[20px_20px_6px_20px] bg-[#8B5CF6] px-[18px] py-3">
                <p
                  className="text-[15px] leading-[1.4] text-white"
                  style={{ maxWidth: 220 }}
                >
                  {USER_MESSAGE}
                </p>
              </div>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Assistant message */}
        <AnimatePresence>
          {typingDone && (
            <motion.div
              className="flex gap-2.5"
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: ASSISTANT_DELAY - SENT_DELAY }}
            >
              <div className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-[#8B5CF6]">
                <Sparkles className="h-4 w-4 text-white" />
              </div>
              <div className="flex flex-col gap-3">
                {/* Tool execution card */}
                <motion.div
                  className="rounded-2xl bg-[#F4F4F5] p-4"
                  initial={{ opacity: 0, y: 6 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{
                    duration: 0.35,
                    delay: ASSISTANT_DELAY - SENT_DELAY,
                  }}
                >
                  {/* Tool header */}
                  <div className="flex items-center gap-2.5">
                    <div className="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-[#8B5CF6]">
                      <PencilLine className="h-3.5 w-3.5 text-white" />
                    </div>
                    <div className="flex flex-col gap-0.5">
                      <span className="text-[13px] font-semibold text-[#18181B]">
                        Page Content Updated
                      </span>
                      <span className="text-[12px] text-[#71717A]">
                        about-us · About Us
                      </span>
                    </div>
                  </div>
                  {/* Tool result */}
                  <div className="mt-3 flex items-center gap-2 rounded-xl bg-white px-3.5 py-2.5">
                    <CircleCheck className="h-3.5 w-3.5 flex-shrink-0 text-[#8B5CF6]" />
                    <span className="text-[13px] font-medium text-[#52525B]">
                      1 paragraph rewritten
                    </span>
                  </div>
                </motion.div>

                <motion.p
                  className="text-[13px] leading-[1.5] text-[#18181B]"
                  style={{ maxWidth: 240 }}
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{
                    duration: 0.4,
                    delay: ASSISTANT_DELAY - SENT_DELAY + 0.2,
                  }}
                >
                  Done! I&apos;ve rewritten the About Us intro to be more
                  professional and compelling. The new version leads with your
                  value proposition and uses confident language.
                </motion.p>
              </div>
            </motion.div>
          )}
        </AnimatePresence>
      </div>

      {/* Input */}
      <div className="flex-shrink-0 border-t border-[#E4E4E7] px-5 pb-5 pt-3">
        <div className="flex items-end gap-2.5">
          <div
            className="flex flex-1 items-center rounded-3xl bg-[#F4F4F5] px-[18px]"
            style={{ minHeight: 48 }}
          >
            <AnimatePresence mode="wait">
              {!typingDone ? (
                <motion.span
                  key="typing"
                  className="text-[12px] text-[#18181B]"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  exit={{ opacity: 0 }}
                >
                  {typedText}
                  <motion.span
                    className="inline-block h-[15px] w-[2px] translate-y-[2px] bg-[#8B5CF6]"
                    animate={{ opacity: [1, 0] }}
                    transition={{ repeat: Infinity, duration: 0.6 }}
                  />
                </motion.span>
              ) : (
                <motion.span
                  key="placeholder"
                  className="text-[12px] text-[#A1A1AA]"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ duration: 0.3 }}
                >
                  Ask anything about your site...
                </motion.span>
              )}
            </AnimatePresence>
          </div>
          <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-[#8B5CF6]">
            <ArrowUp className="h-5 w-5 text-white" />
          </div>
        </div>
      </div>
    </motion.div>
  );
}

export function HeroSection() {
  return (
    <section className="relative overflow-hidden bg-lp-hero-dark">
      {/* Background gradient */}
      <div className="absolute inset-0">
        <div
          className="absolute inset-0"
          style={{
            background:
              'linear-gradient(180deg, #0C0A1A 0%, #110E28 12%, #1A1240 24%, #221850 34%, #2D1F62 42%, #4A3580 52%, #9E85C8 62%, #D8CDE8 74%, #F0EBF5 86%, #FFFFFF 100%)',
          }}
        />
        {/* Central glow */}
        <div
          className="absolute left-[19%] top-[4%] h-[600px] w-[900px] blur-[80px]"
          style={{
            background:
              'radial-gradient(ellipse, rgba(124, 58, 237, 0.25) 0%, rgba(91, 33, 182, 0.12) 50%, transparent 100%)',
          }}
        />
        {/* Side glows */}
        <div
          className="absolute -left-[7%] top-[8%] h-[400px] w-[500px] blur-[60px]"
          style={{
            background:
              'radial-gradient(ellipse, rgba(109, 40, 217, 0.12) 0%, transparent 100%)',
          }}
        />
        <div
          className="absolute right-[-2%] top-[6%] h-[400px] w-[500px] blur-[60px]"
          style={{
            background:
              'radial-gradient(ellipse, rgba(76, 29, 149, 0.09) 0%, transparent 100%)',
          }}
        />

        {/* Stars */}
        {stars.map((star, i) => (
          <motion.div
            key={i}
            className="absolute rounded-full"
            style={{
              left: star.x,
              top: star.y,
              width: star.size,
              height: star.size,
              backgroundColor: star.purple ? '#DDD6FE' : '#FFFFFF',
              filter: 'blur(0.5px)',
            }}
            animate={{
              opacity: [star.opacity * 0.3, star.opacity, star.opacity * 0.15, star.opacity * 0.85, star.opacity * 0.3],
              scale: [1, 1.4, 0.85, 1.2, 1],
              y: [0, -6, -2, -8, 0],
              x: [0, 2, -1, 1, 0],
            }}
            transition={{
              duration: star.floatDuration,
              repeat: Infinity,
              ease: 'easeInOut',
              delay: star.floatDelay,
              times: [0, 0.25, 0.5, 0.75, 1],
              opacity: {
                duration: star.twinkleDuration,
                repeat: Infinity,
                ease: 'easeInOut',
                delay: star.twinkleDelay,
              },
              scale: {
                duration: star.twinkleDuration,
                repeat: Infinity,
                ease: 'easeInOut',
                delay: star.twinkleDelay,
              },
            }}
          />
        ))}
      </div>

      {/* Content */}
      <div className="relative z-10 flex flex-col items-center pt-24 md:pt-28">
        {/* Badge */}
        <motion.div
          initial={{ scale: 0, opacity: 0 }}
          animate={{ scale: 1, opacity: 1 }}
          transition={{ type: 'spring', stiffness: 400, damping: 20, delay: 0.2 }}
        >
          <span className="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/[0.06] px-4 py-1.5 text-[13px] font-semibold text-lp-purple-light">
            <Sparkles className="h-3.5 w-3.5 text-lp-purple-light" />
            AI-Powered WordPress Assistant
          </span>
        </motion.div>

        {/* Headline */}
        <motion.h1
          className="mt-6 max-w-[900px] px-4 text-center font-heading text-4xl font-extrabold leading-[1.1] text-white sm:text-5xl md:text-[64px]"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.4 }}
        >
          Manage your WordPress site by just asking.
        </motion.h1>

        {/* Subheadline */}
        <motion.p
          className="mt-6 max-w-[700px] px-4 text-center text-lg text-lp-hero-muted md:text-xl"
          style={{ lineHeight: 1.5 }}
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.6 }}
        >
          A chat assistant inside wp-admin that handles your site tasks —
          <br className="hidden sm:block" />
          no menus, no tickets, no tech skills needed.
        </motion.p>

        {/* CTA Buttons */}
        <motion.div
          className="mt-10 flex flex-col items-center gap-4 px-4 sm:flex-row"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5, delay: 0.8 }}
        >
          <Button
            href="#"
            variant="solid-white"
            className="shadow-[0_0_32px_rgba(255,255,255,0.19)]"
          >
            Install Free Plugin
          </Button>
          <Button href="#" variant="outline-dark">
            See it in action
          </Button>
        </motion.div>

        {/* Product Screenshot */}
        <motion.div
          className="mt-10 w-full px-4 sm:px-10 lg:px-[120px]"
          initial={{ opacity: 0, y: 60 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ type: 'spring', stiffness: 100, damping: 20, delay: 1 }}
        >
          {/* Mobile: chat sidebar card only */}
          <div className="mx-auto h-[520px] max-w-[380px] md:hidden">
            <ChatSidebarPreview className="flex h-full w-full" />
          </div>

          {/* Desktop: full WP admin mockup */}
          <div className="hidden md:block">
            {/* Screenshot glow */}
            <div
              className="pointer-events-none absolute bottom-[10%] left-[15%] h-[300px] w-[1000px] blur-[50px]"
              style={{
                background:
                  'radial-gradient(ellipse, rgba(124, 58, 237, 0.15) 0%, transparent 100%)',
              }}
            />

            <div className="relative mx-auto max-w-[1200px]">
              <motion.div
                className="overflow-hidden rounded-3xl border border-white/25 bg-[#1E1E2E] shadow-[0_4px_60px_rgba(124,58,237,0.3),0_16px_100px_rgba(91,33,182,0.15)]"
                animate={{ y: [0, -8, 0] }}
                transition={{
                  duration: 4,
                  repeat: Infinity,
                  ease: 'easeInOut',
                }}
              >
                {/* WP Admin Bar */}
                <div className="flex h-8 items-center justify-between bg-[#23282D]/80 px-3">
                  <div className="flex items-center gap-2">
                    <div className="h-3 w-3 rounded-full bg-[#FF5F56]" />
                    <div className="h-3 w-3 rounded-full bg-[#FFBD2E]" />
                    <div className="h-3 w-3 rounded-full bg-[#27C93F]" />
                  </div>
                  <span className="text-[9px] text-white/50">WordPress Admin</span>
                  <div className="flex items-center gap-2">
                    <div className="h-2 w-[70px] rounded bg-white/10" />
                    <div className="h-5 w-5 rounded-full bg-white/10" />
                  </div>
                </div>

                <div
                  className="relative flex"
                  style={{ height: 'clamp(300px, 45vw, 608px)' }}
                >
                  {/* WP Sidebar */}
                  <div className="hidden w-[180px] flex-shrink-0 flex-col gap-0.5 bg-[#23282D]/80 p-2 sm:flex">
                    {wpMenuItems.map((item, i) => (
                      <div
                        key={i}
                        className={cn(
                          'flex items-center gap-2 rounded px-3 py-2',
                          item.active ? 'bg-white/[0.10]' : ''
                        )}
                      >
                        <item.icon className="h-3.5 w-3.5 text-white/50" />
                        <span className="text-[11px] text-white/60">
                          {item.label}
                        </span>
                      </div>
                    ))}
                  </div>

                  {/* WP Main Content Area */}
                  <div className="flex flex-1 flex-col bg-[#F1F1F1]/70">
                    {/* WP admin subheader */}
                    <div className="flex items-center justify-between border-b border-black/10 bg-white/70 px-6 py-3">
                      <div className="flex items-center gap-1.5">
                        <div className="h-2.5 w-[100px] rounded bg-[#DCDCDC]" />
                      </div>
                      <div className="flex items-center gap-2">
                        <div className="h-5 w-12 rounded bg-[#E5E5E5]" />
                        <div className="flex h-5 items-center rounded bg-[#8B5CF6]/80 px-2">
                          <span className="text-[8px] font-semibold text-white">
                            Update
                          </span>
                        </div>
                      </div>
                    </div>

                    {/* Editor area */}
                    <div className="flex flex-1 flex-col overflow-hidden p-6">
                      <div className="flex h-full flex-col overflow-hidden rounded border border-[#DDDDDD] bg-white">
                        {/* Editor toolbar */}
                        <div className="flex items-center gap-2 border-b border-[#EEEEEE] px-6 py-2">
                          {['T', 'B', 'I', '/'].map((btn) => (
                            <div
                              key={btn}
                              className="flex h-5 w-5 items-center justify-center rounded text-[9px] font-medium text-[#71717A]"
                            >
                              {btn}
                            </div>
                          ))}
                          <div className="mx-1 h-4 w-px bg-[#EEEEEE]" />
                          <div className="h-2 w-10 rounded bg-[#EEEEEE]" />
                          <div className="h-2 w-8 rounded bg-[#EEEEEE]" />
                        </div>

                        {/* Page content skeleton */}
                        <div className="flex-1 overflow-hidden p-6">
                          <div className="mb-4 h-[18px] w-36 rounded bg-[#E5E5E5]" />
                          <div className="mb-4 h-px w-full bg-[#EEEEEE]" />
                          <div className="mb-4 flex flex-col gap-2">
                            <div className="h-2.5 w-full rounded bg-[#ECECEC]" />
                            <div className="h-2.5 w-full rounded bg-[#ECECEC]" />
                            <div className="h-2.5 w-[360px] max-w-full rounded bg-[#ECECEC]" />
                          </div>
                          <div className="mb-4 flex flex-col gap-2">
                            <div className="h-2.5 w-full rounded bg-[#ECECEC]" />
                            <div className="h-2.5 w-[280px] max-w-full rounded bg-[#ECECEC]" />
                          </div>
                          <div className="mb-4 flex flex-col gap-2">
                            <div className="h-2.5 w-full rounded bg-[#ECECEC]" />
                            <div className="h-2.5 w-full rounded bg-[#ECECEC]" />
                            <div className="h-2.5 w-full rounded bg-[#ECECEC]" />
                            <div className="h-2.5 w-[200px] max-w-full rounded bg-[#ECECEC]" />
                          </div>
                          <div className="flex flex-col gap-2">
                            <div className="h-2.5 w-full rounded bg-[#ECECEC]" />
                            <div className="h-2.5 w-full rounded bg-[#ECECEC]" />
                            <div className="h-2.5 w-[320px] max-w-full rounded bg-[#ECECEC]" />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Chat Sidebar — animated popup */}
                  <ChatSidebarPreview
                    className="absolute right-0 top-[15px] flex w-[340px]"
                    style={{ bottom: 15, marginRight: 20 }}
                  />
                </div>
              </motion.div>
            </div>
          </div>
        </motion.div>
      </div>

      {/* Bottom spacer for gradient to white transition */}
      <div className="h-16" />
    </section>
  );
}
