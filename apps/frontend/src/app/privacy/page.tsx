import Link from 'next/link';
import { MessageCircle } from 'lucide-react';
import { LegalFooter } from '@/components/legal/legal-footer';

export const metadata = {
  title: 'Privacy Policy',
  description: 'Privacy Policy for Wally, the AI-powered WordPress admin assistant.',
  openGraph: {
    title: 'Privacy Policy | Wally',
    description: 'Privacy Policy for Wally, the AI-powered WordPress admin assistant.',
    url: '/privacy',
    type: 'website',
    images: [{ url: '/site-og.png', width: 1200, height: 630 }],
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Privacy Policy | Wally',
    description: 'Privacy Policy for Wally, the AI-powered WordPress admin assistant.',
    images: ['/site-og.png'],
  },
  alternates: { canonical: '/privacy' },
  robots: { index: true, follow: false },
};

const sections = [
  {
    title: '1. Information We Collect',
    body: 'We collect information you provide directly, such as your name, email address, and site URL when you register for an account. We also collect usage data including conversation history, WordPress actions performed, and plugin configuration settings to improve our service.',
  },
  {
    title: '2. How We Use Your Information',
    body: 'We use the collected information to provide and improve the Plugin, process your requests, send service-related communications, monitor usage for billing and rate limiting, and ensure the security and integrity of our services.',
  },
  {
    title: '3. Data Storage & Security',
    body: 'Your data is stored on secure servers with industry-standard encryption. Conversation history is stored in your WordPress database. API keys are encrypted at rest. We implement appropriate technical and organizational measures to protect your data against unauthorized access.',
  },
  {
    title: '4. Third-Party Services',
    body: 'The Plugin uses third-party AI providers (Anthropic, OpenAI) to process your natural language commands. Your prompts and relevant site context are sent to these providers. Please review their respective privacy policies for details on how they handle your data.',
  },
  {
    title: '5. Data Retention',
    body: 'We retain your account data for as long as your account is active or as needed to provide services. Conversation logs are retained for 90 days by default. You may request deletion of your data at any time by contacting us at privacy@wally.ai.',
  },
  {
    title: '6. Your Rights',
    body: 'You have the right to access, correct, or delete your personal data. You may also object to or restrict certain processing of your data. To exercise these rights, contact us at privacy@wally.ai. We will respond to all requests within 30 days.',
  },
  {
    title: '7. Changes to This Policy',
    body: 'We may update this Privacy Policy from time to time. We will notify you of significant changes by updating the date at the top of this page and, where appropriate, by email. We encourage you to review this policy periodically.',
  },
  {
    title: '8. Contact Us',
    body: 'If you have questions or concerns about this Privacy Policy, please contact our Data Privacy team at privacy@wally.ai. We take all privacy inquiries seriously and respond within 5 business days.',
  },
];

export default function PrivacyPage() {
  return (
    <div className="flex min-h-screen flex-col bg-background">
      {/* Header */}
      <header className="flex h-16 items-center justify-between border-b border-border px-20">
        <Link href="/" className="flex items-center gap-2.5">
          <MessageCircle className="h-8 w-8 text-primary" />
          <span className="font-heading text-[28px] font-bold text-foreground">Wally</span>
        </Link>
        <Link
          href="/"
          className="rounded-md bg-accent px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent/80"
        >
          ← Back to app
        </Link>
      </header>

      {/* Body */}
      <main className="flex flex-1 flex-col items-center px-5 py-16">
        <div className="flex w-full max-w-[760px] flex-col gap-10">
          {/* Page Header */}
          <div className="flex flex-col gap-2">
            <h1 className="font-heading text-[40px] font-bold text-foreground">
              Privacy Policy
            </h1>
            <p className="text-sm text-muted-foreground">
              Effective Date: March 1, 2026 · Version 1.0
            </p>
          </div>

          <hr className="border-border" />

          {/* Intro */}
          <p className="text-[15px] leading-[1.7] text-muted-foreground">
            At Wally, we take your privacy seriously. This Privacy Policy explains how we collect,
            use, disclose, and safeguard your information when you use our WordPress plugin and
            related services. Please read this policy carefully before using the Plugin.
          </p>

          {/* Sections */}
          {sections.map((section) => (
            <div key={section.title} className="flex flex-col gap-2.5">
              <h2 className="font-heading text-xl font-semibold text-foreground">
                {section.title}
              </h2>
              <p className="text-[15px] leading-[1.7] text-muted-foreground">{section.body}</p>
            </div>
          ))}

          <hr className="border-border" />
        </div>
      </main>

      <LegalFooter />
    </div>
  );
}
