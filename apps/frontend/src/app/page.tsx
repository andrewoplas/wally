import { Navbar } from '@/components/landing/navbar';
import { HeroSection } from '@/components/landing/hero-section';
import { ProblemSection } from '@/components/landing/problem-section';
import { VideoDemoSection } from '@/components/landing/video-demo-section';
import { HowItWorksSection } from '@/components/landing/how-it-works-section';
import { WhoItsForSection } from '@/components/landing/who-its-for-section';
import { TrustSafetySection } from '@/components/landing/trust-safety-section';
import { PricingSection } from '@/components/landing/pricing-section';
import { FinalCtaSection } from '@/components/landing/final-cta-section';
import { Footer } from '@/components/landing/footer';

export default function LandingPage() {
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
