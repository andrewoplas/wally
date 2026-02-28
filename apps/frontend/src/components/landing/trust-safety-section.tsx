'use client';

import { ClipboardList, ShieldCheck, Server } from 'lucide-react';
import { AnimatedSection, StaggerContainer, StaggerItem } from './shared/animated-section';
import { Container } from './shared/container';

const trustSignals = [
  {
    icon: ClipboardList,
    title: 'Every action is logged',
    description:
      'A complete audit trail of every change Wally makes, so you always know what happened and when.',
  },
  {
    icon: ShieldCheck,
    title: 'Destructive changes require your approval',
    description:
      'Deleting content, changing settings, or modifying plugins — nothing risky happens without your explicit confirmation.',
  },
  {
    icon: Server,
    title: 'Your data stays on your server',
    description:
      'Wally runs as a WordPress plugin. Your content and credentials never leave your infrastructure.',
  },
];

export function TrustSafetySection() {
  return (
    <section className="bg-white py-24">
      <Container className="flex flex-col items-center gap-16">
        <AnimatedSection className="flex flex-col items-center gap-4 text-center">
          <h2 className="font-heading text-[34px] font-bold leading-[1.1] text-[#18181B]">
            Built with safety in mind
          </h2>
          <p className="max-w-[700px] font-sans text-[17px] leading-[1.5] text-[#71717A]">
            Your WordPress site is in safe hands. Every action is transparent,
            reversible, and under your control.
          </p>
        </AnimatedSection>

        <StaggerContainer
          className="grid w-full grid-cols-1 gap-10 md:grid-cols-3"
          staggerDelay={0.15}
        >
          {trustSignals.map((signal) => (
            <StaggerItem key={signal.title} className="flex">
              <div className="flex flex-1 flex-col items-center rounded-2xl border border-[#E4E4E7] bg-[#FAFAFA] px-8 py-9 text-center">
                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-[#8B5CF6]/[0.125]">
                  <signal.icon className="h-8 w-8 text-primary" />
                </div>

                <h3 className="mt-4 font-heading text-[18px] font-bold text-[#18181B]">
                  {signal.title}
                </h3>
                <p className="mt-2 font-sans text-[15px] leading-[1.6] text-[#71717A]">
                  {signal.description}
                </p>
              </div>
            </StaggerItem>
          ))}
        </StaggerContainer>

        <AnimatedSection delay={0.4}>
          <p className="font-sans text-[14px] font-medium text-[#A1A1AA]">
            Powered by Claude and OpenAI
          </p>
        </AnimatedSection>
      </Container>
    </section>
  );
}
