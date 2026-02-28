import { CircleAlert } from 'lucide-react';
import { SectionBadge } from './shared/section-badge';
import { AnimatedSection } from './shared/animated-section';
import { Container } from './shared/container';

export function ProblemSection() {
  return (
    <section className="border-t-2 border-primary/20 bg-lp-problem-bg py-24">
      <Container className="flex flex-col items-center text-center">
        <AnimatedSection>
          <SectionBadge icon={CircleAlert}>Sound familiar?</SectionBadge>
        </AnimatedSection>

        <AnimatedSection delay={0.1} className="mt-6">
          <h2 className="max-w-[800px] font-heading text-3xl font-bold leading-[1.15] text-foreground sm:text-4xl md:text-[40px]">
            The daily WordPress grind
            <br className="hidden sm:block" />
            is quietly eating your time.
          </h2>
        </AnimatedSection>

        <AnimatedSection delay={0.2} className="mt-6">
          <p className="max-w-[700px] text-base leading-[1.6] text-lp-text-body md:text-lg">
            Your clients submit support tickets for things that should take 10
            seconds. You open wp-admin, navigate three menus, make the change,
            close the ticket. Then it happens again tomorrow.
          </p>
        </AnimatedSection>
      </Container>
    </section>
  );
}
