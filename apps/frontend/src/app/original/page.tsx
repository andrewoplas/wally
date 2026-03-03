import type { Metadata } from 'next';
import { Navbar } from '@/components/landing-v1/navbar';
import { HeroSection } from '@/components/landing-v1/hero-section';
import { ProblemSection } from '@/components/landing/problem-section';
import { VideoDemoSection } from '@/components/landing/video-demo-section';
import { HowItWorksSection } from '@/components/landing/how-it-works-section';
import { WhoItsForSection } from '@/components/landing/who-its-for-section';
import { TrustSafetySection } from '@/components/landing/trust-safety-section';
import { PricingSection } from '@/components/landing-v1/pricing-section';
import { FinalCtaSection } from '@/components/landing-v1/final-cta-section';
import { Footer } from '@/components/landing/footer';

export const metadata: Metadata = {
  title: 'Wally — Manage Your WordPress Site by Just Asking',
  description:
    'Wally is an AI chat assistant inside wp-admin that handles your WordPress site tasks through natural language — no menus, no tickets, no tech skills needed.',
  robots: { index: false, follow: false },
};

export default function OriginalLandingPage() {
  return (
    <main>
      <Navbar />
      <HeroSection />
      <ProblemSection />
      <VideoDemoSection />
      <HowItWorksSection />
      <WhoItsForSection />
      <TrustSafetySection />
      <PricingSection />
      <FinalCtaSection />
      <Footer />
    </main>
  );
}
