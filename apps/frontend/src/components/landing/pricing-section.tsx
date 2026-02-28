'use client';

import { motion } from 'framer-motion';
import { Check } from 'lucide-react';
import { AnimatedSection, StaggerContainer, StaggerItem } from './shared/animated-section';
import { Container } from './shared/container';
import { Button } from '@/components/ui/button';
import { useCountUp } from '@/lib/use-count-up';
import { cn } from '@/lib/utils';

interface PricingTier {
  name: string;
  price: number;
  period: string;
  sites: string;
  features: string[];
  cta: string;
  highlighted?: boolean;
  ctaVariant: 'primary' | 'secondary' | 'outline';
  note?: string;
}

const tiers: PricingTier[] = [
  {
    name: 'Free',
    price: 0,
    period: '/mo',
    sites: '1 site',
    features: [
      'Bring your own API key',
      'Content management tools',
      '50 messages per day',
    ],
    cta: 'Get Started Free',
    ctaVariant: 'primary',
    note: 'No credit card required',
  },
  {
    name: 'Pro',
    price: 12,
    period: '/mo',
    sites: '1 site',
    features: [
      'All WordPress tools',
      'Unlimited messages',
      'Action log & audit trail',
      'Priority support',
    ],
    cta: 'Start Free Trial',
    ctaVariant: 'primary',
    highlighted: true,
  },
  {
    name: 'Agency',
    price: 49,
    period: '/mo',
    sites: '10 sites',
    features: [
      'White-label branding',
      'Bulk site management',
      'All Pro features included',
      'Team permissions',
    ],
    cta: 'Start Free Trial',
    ctaVariant: 'secondary',
  },
  {
    name: 'Enterprise',
    price: 149,
    period: '/mo',
    sites: 'Unlimited sites',
    features: [
      'Custom branding',
      'SSO authentication',
      'Dedicated SLA',
      'All Agency features included',
    ],
    cta: 'Contact Sales',
    ctaVariant: 'outline',
  },
];

function PriceDisplay({ price }: { price: number }) {
  const [count, ref] = useCountUp(price, 1200);
  return (
    <span ref={ref as React.RefObject<HTMLSpanElement>} className="font-heading text-[34px] font-extrabold leading-[0.9] text-foreground">
      ${count}
    </span>
  );
}

export function PricingSection() {
  return (
    <section id="pricing" className="bg-lp-pricing-bg py-24">
      <Container className="flex flex-col items-center">
        <AnimatedSection className="text-center">
          <h2 className="font-heading text-2xl font-bold leading-[1.1] text-foreground sm:text-3xl md:text-[34px]">
            Simple, transparent pricing
          </h2>
          <p className="mt-4 text-base text-muted-foreground md:text-[17px]">
            Start free, upgrade when you&apos;re ready. No surprises.
          </p>
        </AnimatedSection>

        <StaggerContainer
          className="mt-16 grid w-full gap-5 md:grid-cols-2 lg:grid-cols-4"
          staggerDelay={0.1}
        >
          {tiers.map((tier) => (
            <StaggerItem key={tier.name}>
              <motion.div
                className={cn(
                  'flex h-full flex-col gap-6 rounded-3xl bg-white p-7',
                  tier.highlighted
                    ? 'border-2 border-primary'
                    : 'border border-transparent'
                )}
                whileHover={{ y: -6, boxShadow: '0 12px 40px rgba(0,0,0,0.1)' }}
                transition={{ duration: 0.2 }}
              >
                {/* Header */}
                <div className="flex flex-col gap-2">
                  <div className="flex items-center justify-between">
                    <span className="font-heading text-xl font-bold text-foreground">
                      {tier.name}
                    </span>
                    {tier.highlighted && (
                      <span
                        className="relative overflow-hidden rounded-full bg-primary px-3.5 py-1.5 text-[11px] font-bold text-white"
                      >
                        Most Popular
                        {/* Shimmer effect */}
                        <span className="absolute inset-0 animate-shimmer bg-[length:200%_100%] bg-[linear-gradient(110deg,transparent_25%,rgba(255,255,255,0.3)_50%,transparent_75%)]" />
                      </span>
                    )}
                  </div>
                  <div className="flex items-end gap-1">
                    <PriceDisplay price={tier.price} />
                    <span className="text-[15px] text-muted-foreground">
                      {tier.period}
                    </span>
                  </div>
                  <span className="text-sm font-medium text-muted-foreground">
                    {tier.sites}
                  </span>
                </div>

                {/* Divider */}
                <div className="h-px bg-border" />

                {/* Features */}
                <div className="flex flex-1 flex-col gap-3.5">
                  {tier.features.map((feature, i) => (
                    <motion.div
                      key={feature}
                      initial={{ opacity: 0, x: -5 }}
                      whileInView={{ opacity: 1, x: 0 }}
                      viewport={{ once: true }}
                      transition={{ delay: 0.3 + i * 0.03 }}
                      className="flex items-center gap-2.5"
                    >
                      <Check className="h-[18px] w-[18px] text-primary" />
                      <span className="text-sm text-lp-text-body">{feature}</span>
                    </motion.div>
                  ))}
                </div>

                {/* CTA */}
                <div className="flex flex-col items-center gap-3">
                  <Button
                    href="#"
                    size="md"
                    variant={
                      tier.ctaVariant === 'primary' ? 'solid-primary' :
                      tier.ctaVariant === 'secondary' ? 'secondary' : 'outline'
                    }
                    className="w-full justify-center"
                  >
                    {tier.cta}
                  </Button>
                  {tier.note && (
                    <span className="text-xs font-medium text-lp-text-muted">
                      {tier.note}
                    </span>
                  )}
                </div>
              </motion.div>
            </StaggerItem>
          ))}
        </StaggerContainer>
      </Container>
    </section>
  );
}
