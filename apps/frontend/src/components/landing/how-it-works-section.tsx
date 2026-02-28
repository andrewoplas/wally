'use client';

import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
  Sparkles,
  Check,
  EllipsisVertical,
  MessageCircle,
  Home,
  FileText,
  Plug,
  Palette,
  ArrowUp,
} from 'lucide-react';
import { SectionBadge } from './shared/section-badge';
import { AnimatedSection } from './shared/animated-section';
import { Container } from './shared/container';
import { cn } from '@/lib/utils';

interface TabData {
  id: string;
  number: number;
  label: string;
  step: string;
  title: string;
  description: string;
  features: string[];
  mockup: React.ReactNode;
}

const tabs: TabData[] = [
  {
    id: 'install',
    number: 1,
    label: 'Install Plugin',
    step: 'STEP 1 OF 3',
    title: 'Install the Plugin',
    description:
      'Activate it like any WordPress plugin. Enter your license key in the settings page and you\'re ready to go — no configuration, no setup wizard, no external accounts.',
    features: [
      'One-click activation from wp-admin',
      'Works with any theme or page builder',
      'No external accounts or API keys needed',
    ],
    mockup: <InstallMockup />,
  },
  {
    id: 'open',
    number: 2,
    label: 'Open Sidebar',
    step: 'STEP 2 OF 3',
    title: 'Open the Chat Sidebar',
    description:
      'A floating chat panel appears right inside wp-admin. It\'s always there when you need it, and out of the way when you don\'t. No new tabs, no context switching.',
    features: [
      'Accessible from every wp-admin page',
      'Drag, resize, and minimize as needed',
      'Conversation history persists across sessions',
    ],
    mockup: <SidebarMockup />,
  },
  {
    id: 'ask',
    number: 3,
    label: 'Just Ask',
    step: 'STEP 3 OF 3',
    title: 'Just Ask',
    description:
      'Type what you need in plain English — update a page, install a plugin, find and replace text. Wally handles the rest. It confirms before making changes.',
    features: [
      'Plain English — no commands to memorize',
      'Confirmation before destructive actions',
      '40+ WordPress tools at your fingertips',
    ],
    mockup: <ConversationMockup />,
  },
];

export function HowItWorksSection() {
  const [activeTab, setActiveTab] = useState(0);

  return (
    <section id="features" className="py-20 lg:py-24">
      <Container>
        {/* Header */}
        <AnimatedSection className="flex flex-col items-center text-center">
          <SectionBadge icon={Sparkles}>How It Works</SectionBadge>
          <h2 className="mt-5 font-heading text-3xl font-extrabold text-foreground sm:text-4xl md:text-[48px]">
            Three steps. Zero learning curve.
          </h2>
          <p className="mt-5 max-w-[620px] text-base leading-[1.5] text-muted-foreground md:text-lg">
            Install, open, ask. That&apos;s it. Each tab below shows what Wally
            looks like at every step.
          </p>
        </AnimatedSection>

        {/* Tab Bar */}
        <AnimatedSection delay={0.2} className="mt-10">
          <div className="grid grid-cols-3 gap-0">
            {tabs.map((tab, i) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(i)}
                className="flex flex-col items-center"
              >
                <div className="flex items-center gap-2.5 py-3.5">
                  <div
                    className={cn(
                      'flex h-7 w-7 items-center justify-center rounded-full text-[13px] font-extrabold transition-colors',
                      i === activeTab
                        ? 'bg-primary text-white'
                        : 'bg-gray-200 text-lp-text-muted'
                    )}
                  >
                    {tab.number}
                  </div>
                  <span
                    className={cn(
                      'hidden text-[15px] font-semibold transition-colors sm:block',
                      i === activeTab
                        ? 'font-bold text-foreground'
                        : 'text-lp-text-muted'
                    )}
                  >
                    {tab.label}
                  </span>
                </div>
                {/* Progress bar */}
                <div className="h-[3px] w-full rounded-sm bg-border">
                  {i === activeTab && (
                    <motion.div
                      className="h-full rounded-sm bg-primary"
                      layoutId="tab-progress"
                      transition={{ type: 'spring', stiffness: 300, damping: 30 }}
                    />
                  )}
                </div>
              </button>
            ))}
          </div>
        </AnimatedSection>

        {/* Tab Content */}
        <div className="mt-8">
          <AnimatePresence mode="wait">
            <motion.div
              key={activeTab}
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              transition={{ duration: 0.3 }}
              className="overflow-hidden rounded-3xl border border-[#E4E4E740] bg-white"
            >
              <div className="grid min-h-[480px] lg:grid-cols-2">
                {/* Left: Text content */}
                <div className="flex flex-col justify-center gap-8 p-8 md:p-12">
                  <div>
                    <div className="flex items-center gap-2">
                      <div className="h-2 w-2 rounded-full bg-primary" />
                      <span className="text-xs font-bold tracking-[1.5px] text-primary">
                        {tabs[activeTab].step}
                      </span>
                    </div>
                    <h3 className="mt-4 font-heading text-2xl font-extrabold leading-[1.1] text-foreground md:text-4xl">
                      {tabs[activeTab].title}
                    </h3>
                    <p className="mt-4 leading-[1.7] text-lp-text-body md:text-base">
                      {tabs[activeTab].description}
                    </p>
                  </div>

                  <div className="flex flex-col gap-3.5">
                    {tabs[activeTab].features.map((feat, i) => (
                      <motion.div
                        key={feat}
                        initial={{ opacity: 0, x: -10 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ delay: 0.15 + i * 0.05 }}
                        className="flex items-center gap-2.5"
                      >
                        <Check className="h-4 w-4 text-lp-check" />
                        <span className="text-sm text-lp-text-body">{feat}</span>
                      </motion.div>
                    ))}
                  </div>
                </div>

                {/* Right: Mockup */}
                <div className="flex items-center justify-center bg-[#F4F4F5] p-10">
                  {tabs[activeTab].mockup}
                </div>
              </div>
            </motion.div>
          </AnimatePresence>
        </div>
      </Container>
    </section>
  );
}

/* Mockup Components */

function InstallMockup() {
  return (
    <div className="w-full max-w-[340px] overflow-hidden rounded-xl bg-white shadow-[0_8px_32px_rgba(0,0,0,0.08)] outline outline-1 outline-black/[0.06]">
      {/* Top bar */}
      <div className="flex h-7 items-center gap-2 bg-[#23282D] px-3">
        <div className="flex gap-[5px]">
          <div className="h-2 w-2 rounded-full bg-[#FF5F57]" />
          <div className="h-2 w-2 rounded-full bg-[#FFBD2E]" />
          <div className="h-2 w-2 rounded-full bg-[#28C940]" />
        </div>
        <span className="text-[9px] text-white/50">Plugins — WordPress</span>
      </div>

      {/* Admin body */}
      <div className="flex" style={{ height: 332 }}>
        {/* Sidebar */}
        <div className="flex w-[70px] flex-col gap-px bg-[#1D2327] py-2.5">
          {[
            { icon: <Home className="h-2.5 w-2.5" />, label: 'Dashboard' },
            { icon: <Plug className="h-2.5 w-2.5" />, label: 'Plugins', active: true },
            { icon: <FileText className="h-2.5 w-2.5" />, label: 'Pages' },
            { icon: <Palette className="h-2.5 w-2.5" />, label: 'Media' },
          ].map((item, i) => (
            <div
              key={i}
              className={cn(
                'flex items-center gap-1.5 px-2.5 py-1.5',
                item.active
                  ? 'bg-[#0073AA] text-white'
                  : 'text-white/40'
              )}
            >
              {item.icon}
              <span className={cn('text-[7px]', item.active ? 'font-bold text-white' : 'text-white/50')}>
                {item.label}
              </span>
            </div>
          ))}
        </div>

        {/* Main area */}
        <div className="flex flex-1 flex-col gap-3 bg-[#F0F0F1] p-3.5">
          <span className="text-[14px] font-bold text-[#1D2327]">Plugins</span>

          {/* Plugin card */}
          <div className="flex flex-col gap-2.5 rounded-md border border-[#C3C4C7] bg-white p-3.5">
            {/* Card header */}
            <div className="flex items-center justify-between">
              <span className="text-[11px] font-bold text-[#1D2327]">Wally — AI Assistant</span>
              <span className="text-[8px] text-[#787C82]">v1.0.0</span>
            </div>

            {/* Description */}
            <p className="text-[8px] leading-[1.5] text-[#50575E]">
              AI-powered WordPress admin assistant. Manage your site through natural language.
            </p>

            {/* Success banner */}
            <div className="flex items-center gap-1.5 rounded bg-[#EBF5EB] px-2.5 py-2">
              <Check className="h-2.5 w-2.5 text-[#00A32A]" />
              <span className="text-[8px] font-semibold text-[#00A32A]">Plugin activated successfully!</span>
            </div>

            {/* License row */}
            <div className="flex items-center gap-1.5">
              <div className="flex h-[22px] flex-1 items-center rounded border border-[#8C8F94] bg-white px-2">
                <span className="text-[8px] text-[#1D2327]">wally-pro-a8f2k9x3...</span>
              </div>
              <div className="flex h-[22px] items-center justify-center rounded bg-primary px-3">
                <span className="text-[8px] font-semibold text-white">Activate</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

function SidebarMockup() {
  return (
    <div
      className="flex w-[260px] flex-col overflow-hidden rounded-xl bg-white outline outline-1 outline-black/[0.06]"
      style={{
        height: 360,
        boxShadow: '0 8px 32px -4px rgba(0,0,0,0.08)',
      }}
    >
      {/* Header — h:36, px:14, border-bottom */}
      <div className="flex h-9 flex-shrink-0 items-center justify-between border-b border-[#E4E4E7] px-3.5">
        <div className="flex items-center gap-1.5">
          <div className="flex h-[18px] w-[18px] items-center justify-center rounded-md bg-primary">
            <MessageCircle className="h-[11px] w-[11px] text-white" />
          </div>
          <span className="font-heading text-[11px] font-bold text-[#18181B]">Wally</span>
        </div>
        <EllipsisVertical className="h-3.5 w-3.5 text-[#A1A1AA]" />
      </div>

      {/* Body — fill, layout vertical, centered, gap:16, padding:[20,16] */}
      <div className="flex flex-1 flex-col items-center justify-center gap-4 px-4 py-5">
        {/* Bot avatar circle — 44x44 */}
        <div className="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-primary/[0.12]">
          <MessageCircle className="h-[22px] w-[22px] text-primary" />
        </div>

        {/* Text */}
        <div className="flex flex-col items-center gap-1 text-center">
          <span className="font-heading text-base font-bold text-[#18181B]">
            How can I help?
          </span>
          <span className="text-[11px] leading-[1.5] text-[#71717A]">
            Ask me anything about
            <br />
            your WordPress site.
          </span>
        </div>

        {/* Suggestion chips — gap:6, centered */}
        <div className="flex flex-wrap justify-center gap-1.5">
          {['Update a page', 'Install plugin', 'Find & replace'].map((s) => (
            <span
              key={s}
              className="rounded-lg bg-[#F4F4F5] px-2.5 py-1.5 text-[8px] text-[#52525B]"
            >
              {s}
            </span>
          ))}
        </div>
      </div>

      {/* Input — h:36, px:12, gap:8, border-top */}
      <div className="flex h-9 flex-shrink-0 items-center gap-2 border-t border-[#E4E4E7] px-3">
        <span className="flex-1 text-[10px] text-[#A1A1AA]">
          Ask Wally anything...
        </span>
        <div className="flex h-[22px] w-[22px] flex-shrink-0 items-center justify-center rounded-full bg-primary">
          <ArrowUp className="h-3 w-3 text-white" />
        </div>
      </div>
    </div>
  );
}

function ConversationMockup() {
  return (
    <div className="w-full max-w-[280px] overflow-hidden rounded-xl bg-white shadow-[0_8px_32px_rgba(0,0,0,0.08)] outline outline-1 outline-black/[0.06]">
      {/* Header */}
      <div className="flex h-9 items-center justify-between border-b border-border px-3.5">
        <div className="flex items-center gap-1.5">
          <div className="flex h-[18px] w-[18px] items-center justify-center rounded-md bg-primary">
            <MessageCircle className="h-[11px] w-[11px] text-white" />
          </div>
          <span className="text-[11px] font-bold text-foreground">Wally</span>
        </div>
        <EllipsisVertical className="h-3.5 w-3.5 text-[#A1A1AA]" />
      </div>

      {/* Messages */}
      <div className="flex flex-col gap-2.5 p-3.5" style={{ height: 324 }}>
        {/* User message */}
        <div className="flex justify-end">
          <div className="rounded-[12px_12px_4px_12px] bg-primary px-3 py-2">
            <p className="text-[9px] leading-[1.5] text-white">
              Update the homepage hero{'\n'}text to &quot;Ship faster with AI&quot;
            </p>
          </div>
        </div>

        {/* Assistant reply */}
        <div className="flex gap-2">
          <div className="flex h-[22px] w-[22px] flex-shrink-0 items-center justify-center rounded-full bg-primary/[0.12]">
            <MessageCircle className="h-3 w-3 text-primary" />
          </div>
          <div className="flex flex-col gap-1.5 flex-1">
            <p className="text-[9px] leading-[1.5] text-[#3F3F46]">
              I&apos;ll update the hero heading on your homepage. Here&apos;s what I&apos;ll change:
            </p>

            {/* Diff card */}
            <div className="flex flex-col gap-1 rounded-md border border-[#E4E4E7] bg-[#F9FAFB] p-2.5">
              <span className="text-[7px] font-semibold uppercase tracking-[0.5px] text-[#71717A]">
                Hero Heading
              </span>
              <div className="flex items-center gap-1">
                <span className="text-[8px] font-semibold text-[#EF4444]">Before:</span>
                <span className="text-[8px] text-[#71717A]">&quot;Welcome to our site&quot;</span>
              </div>
              <div className="flex items-center gap-1">
                <span className="text-[8px] font-semibold text-[#22C55E]">After:</span>
                <span className="text-[8px] font-semibold text-[#18181B]">&quot;Ship faster with AI&quot;</span>
              </div>
            </div>

            {/* Action buttons */}
            <div className="flex gap-1.5">
              <div className="flex items-center justify-center rounded-md bg-primary px-3 py-[5px]">
                <span className="text-[8px] font-semibold text-white">Confirm</span>
              </div>
              <div className="flex items-center justify-center rounded-md border border-[#E4E4E7] bg-white px-3 py-[5px]">
                <span className="text-[8px] font-semibold text-[#71717A]">Cancel</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
