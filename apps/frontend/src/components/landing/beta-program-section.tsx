'use client';

import { Sparkles, MessageSquare, ShieldCheck } from 'lucide-react';
import { AnimatedSection, StaggerContainer, StaggerItem } from './shared/animated-section';
import { Container } from './shared/container';
import { SectionBadge } from './shared/section-badge';

const betaPerks = [
  {
    icon: Sparkles,
    title: 'Full feature access',
    description:
      'Beta testers get everything — posts, pages, plugins, search & replace, and 30+ tools — completely free during the beta period.',
  },
  {
    icon: MessageSquare,
    title: 'Direct feedback line',
    description:
      'You\'ll have a direct channel to the founding team. Your feedback shapes what we build next.',
  },
  {
    icon: ShieldCheck,
    title: 'Founding member pricing',
    description:
      'Beta testers lock in early adopter pricing when we launch. Significantly lower than public pricing.',
  },
];

export function BetaProgramSection() {
  return (
    <section id="beta-program" className="bg-lp-pricing-bg py-24">
      <Container className="flex flex-col items-center">
        <AnimatedSection className="text-center">
          <SectionBadge icon={Sparkles} variant="light">
            Beta Program
          </SectionBadge>
          <h2 className="mt-6 font-heading text-2xl font-bold leading-tight text-foreground sm:text-3xl md:text-[34px]">
            What you get as a beta tester
          </h2>
          <p className="mt-4 text-base text-muted-foreground md:text-[17px]">
            Early access isn&apos;t just about getting in first — it comes with real benefits.
          </p>
        </AnimatedSection>

        <StaggerContainer className="mt-16 grid w-full gap-6 md:grid-cols-3" staggerDelay={0.12}>
          {betaPerks.map((perk) => (
            <StaggerItem key={perk.title}>
              <div className="flex h-full flex-col gap-4 rounded-3xl border border-transparent bg-white p-7 transition-colors hover:border-primary/20">
                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10">
                  <perk.icon className="h-6 w-6 text-primary" />
                </div>
                <h3 className="font-heading text-lg font-bold text-foreground">
                  {perk.title}
                </h3>
                <p className="text-sm leading-relaxed text-muted-foreground">
                  {perk.description}
                </p>
              </div>
            </StaggerItem>
          ))}
        </StaggerContainer>
      </Container>
    </section>
  );
}
