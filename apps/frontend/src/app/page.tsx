import type { Metadata } from 'next';
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

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL ?? 'https://www.wallychat.com';

export const metadata: Metadata = {
  title: 'Wally — Manage Your WordPress Site by Just Asking',
  description:
    'Wally is an AI chat assistant inside wp-admin that handles your WordPress site tasks through natural language — no menus, no tickets, no tech skills needed.',
  keywords: [
    'WordPress AI assistant',
    'WordPress automation',
    'AI WordPress plugin',
    'WordPress site management',
    'natural language WordPress',
    'wp-admin AI',
    'WordPress chatbot',
    'manage WordPress with AI',
    'Wally',
  ],
  openGraph: {
    title: 'Wally — Manage Your WordPress Site by Just Asking',
    description:
      'An AI chat assistant inside wp-admin that handles your site tasks — no menus, no tickets, no tech skills needed.',
    url: SITE_URL,
    type: 'website',
    images: [{ url: '/site-og.png', width: 1200, height: 630, alt: 'Wally — AI WordPress Assistant' }],
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Wally — Manage Your WordPress Site by Just Asking',
    description:
      'An AI chat assistant inside wp-admin that handles your site tasks — no menus, no tickets, no tech skills needed.',
    images: ['/site-og.png'],
  },
  alternates: {
    canonical: SITE_URL,
  },
};

const jsonLd = {
  '@context': 'https://schema.org',
  '@graph': [
    {
      '@type': 'WebSite',
      '@id': `${SITE_URL}/#website`,
      url: SITE_URL,
      name: 'Wally',
      description: 'AI-powered WordPress admin assistant',
      potentialAction: {
        '@type': 'SearchAction',
        target: { '@type': 'EntryPoint', urlTemplate: `${SITE_URL}/blog?q={search_term_string}` },
        'query-input': 'required name=search_term_string',
      },
    },
    {
      '@type': 'Organization',
      '@id': `${SITE_URL}/#organization`,
      name: 'Wally',
      url: SITE_URL,
      logo: { '@type': 'ImageObject', url: `${SITE_URL}/icon-512.png` },
      sameAs: [],
    },
  ],
};

export default function LandingPage() {
  return (
    <main>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />
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
