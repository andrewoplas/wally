'use client';

import { motion, type Variants } from 'framer-motion';
import { ShieldCheck, Zap, Languages, Users, Building2, MessageCircle, Rocket } from 'lucide-react';
import Image from 'next/image';
import { SectionBadge } from './shared/section-badge';
import { AnimatedSection } from './shared/animated-section';
import { Container } from './shared/container';
import { cn } from '@/lib/utils';

const audiences = [
  {
    title: 'Agency Owners',
    description:
      'Stop fielding trivial client requests. Let your clients self-serve safely — with guardrails you control.',
    avatarSrc: '/images/agency-owners.png',
    avatarFallbackBg: 'from-violet-500/20 to-violet-300/10',
    badgeBg: '#F3F0FF',
    badgeIcon: Building2,
    badgeIconColor: '#8B5CF6',
    tagBg: '#F3F0FF',
    tagIcon: ShieldCheck,
    tagIconColor: '#8B5CF6',
    tagLabel: 'Guardrails & Control',
    tagTextColor: '#7C3AED',
  },
  {
    title: 'Agency Clients & Non-Technical Users',
    description:
      "No more waiting on your developer for small updates. Just tell the assistant what you need — in plain language.",
    avatarSrc: '/images/agency-clients.png',
    avatarFallbackBg: 'from-pink-500/20 to-pink-300/10',
    badgeBg: '#FDF2F8',
    badgeIcon: MessageCircle,
    badgeIconColor: '#EC4899',
    tagBg: '#FDF2F8',
    tagIcon: Zap,
    tagIconColor: '#EC4899',
    tagLabel: 'No Code Needed',
    tagTextColor: '#DB2777',
  },
  {
    title: 'Solo Site Owners',
    description:
      'Skip the learning curve. Manage your site the way you think about it — in plain English, not WordPress menus.',
    avatarSrc: '/images/solo-site-owners.png',
    avatarFallbackBg: 'from-amber-500/20 to-amber-200/10',
    badgeBg: '#FFFBEB',
    badgeIcon: Rocket,
    badgeIconColor: '#F59E0B',
    tagBg: '#FFFBEB',
    tagIcon: Languages,
    tagIconColor: '#F59E0B',
    tagLabel: 'Plain English',
    tagTextColor: '#D97706',
  },
];

const cardVariants: Variants = {
  hidden: { opacity: 0, y: 24 },
  visible: (i: number) => ({
    opacity: 1,
    y: 0,
    transition: { duration: 0.5, ease: 'easeOut' as const, delay: i * 0.15 },
  }),
};

export function WhoItsForSection() {
  return (
    <section className="bg-white py-24">
      <Container>
        <AnimatedSection className="flex flex-col items-center text-center">
          <SectionBadge icon={Users}>Who&apos;s it For</SectionBadge>

          <h2 className="mt-5 font-heading text-[38px] font-bold leading-[1.15] text-[#18181B]">
            Built for everyone who manages WordPress
          </h2>
          <p className="mt-4 max-w-[680px] text-[17px] leading-[1.6] text-[#71717A]">
            Whether you&apos;re running an agency, managing client sites, or
            flying solo — Wally adapts to how you work.
          </p>
        </AnimatedSection>

        <motion.div
          className="mt-14 grid gap-6 md:grid-cols-3"
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true, margin: '-80px' }}
        >
          {audiences.map((audience, i) => (
            <motion.div
              key={audience.title}
              custom={i}
              variants={cardVariants}
              className="flex h-full flex-col items-center rounded-[20px] border border-[#E4E4E7] bg-white shadow-[0_4px_16px_rgba(0,0,0,0.04)]"
            >
              <div className="flex w-full flex-col items-center gap-6 px-8 pb-8 pt-7">
                {/* Avatar with badge */}
                <div className="relative h-[72px] w-[72px]">
                  <div
                    className={cn(
                      'h-[72px] w-[72px] overflow-hidden rounded-[36px] bg-gradient-to-b',
                      audience.avatarFallbackBg
                    )}
                  >
                    <Image
                      src={audience.avatarSrc}
                      alt={audience.title}
                      width={72}
                      height={72}
                      className="h-full w-full object-cover"
                    />
                  </div>
                  <div
                    className="absolute bottom-0 right-0 flex h-7 w-7 items-center justify-center rounded-[14px] shadow-[0_1px_3px_rgba(0,0,0,0.08)] outline outline-2 outline-white"
                    style={{ backgroundColor: audience.badgeBg }}
                  >
                    <audience.badgeIcon
                      className="h-3.5 w-3.5"
                      style={{ color: audience.badgeIconColor }}
                    />
                  </div>
                </div>

                {/* Text */}
                <div className="flex flex-col items-center gap-2.5">
                  <h3 className="text-center font-heading text-xl font-bold text-[#18181B]">
                    {audience.title}
                  </h3>
                  <p className="text-center text-[15px] leading-[1.6] text-[#71717A]">
                    {audience.description}
                  </p>
                </div>

                {/* Tag */}
                <div
                  className="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5"
                  style={{ backgroundColor: audience.tagBg }}
                >
                  <audience.tagIcon
                    className="h-3.5 w-3.5"
                    style={{ color: audience.tagIconColor }}
                  />
                  <span
                    className="text-xs font-semibold"
                    style={{ color: audience.tagTextColor }}
                  >
                    {audience.tagLabel}
                  </span>
                </div>
              </div>
            </motion.div>
          ))}
        </motion.div>
      </Container>
    </section>
  );
}
