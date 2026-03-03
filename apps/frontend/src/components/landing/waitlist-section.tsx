'use client';

import { Mail } from 'lucide-react';
import { AnimatedSection } from './shared/animated-section';
import { Container } from './shared/container';
import { SectionBadge } from './shared/section-badge';
import { WaitlistForm } from './waitlist-form';

export function WaitlistSection() {
  return (
    <section id="waitlist" className="bg-primary/[0.04] py-24">
      <Container className="flex flex-col items-center text-center">
        <AnimatedSection>
          <div className="flex flex-col items-center">
            <SectionBadge icon={Mail} variant="light">
              Limited Early Access
            </SectionBadge>
            <h2 className="mt-6 font-heading text-3xl font-extrabold leading-tight text-foreground md:text-[40px]">
              Get early access to Wally
            </h2>
            <p className="mt-4 max-w-[560px] text-base text-muted-foreground md:text-[17px]">
              Be among the first to use Wally when it launches.
              No spam — just your invite when your spot is ready.
            </p>
            <div className="mt-8 flex flex-col items-center gap-4">
              <WaitlistForm source="waitlist-section" />
              <p className="text-[13px] text-muted-foreground">
                Free to join &middot; Invite-only &middot; Launching soon
              </p>
            </div>
          </div>
        </AnimatedSection>
      </Container>
    </section>
  );
}
