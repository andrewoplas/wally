import Link from 'next/link';
import { MessageCircle } from 'lucide-react';
import { LegalFooter } from '@/components/legal/legal-footer';

export const metadata = {
  title: 'Terms of Service',
  description: 'Terms of Service for Wally, the AI-powered WordPress admin assistant.',
  openGraph: {
    title: 'Terms of Service | Wally',
    description: 'Terms of Service for Wally, the AI-powered WordPress admin assistant.',
    url: '/terms',
    type: 'website',
    images: [{ url: '/site-og.png', width: 1200, height: 630 }],
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Terms of Service | Wally',
    description: 'Terms of Service for Wally, the AI-powered WordPress admin assistant.',
    images: ['/site-og.png'],
  },
  alternates: { canonical: '/terms' },
  robots: { index: true, follow: false },
};

const sections = [
  {
    title: '1. Acceptance of Terms',
    body: 'By installing, accessing, or using Wally, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree, you are prohibited from using the Plugin.',
  },
  {
    title: '2. Description of Service',
    body: 'Wally is an AI-powered WordPress admin assistant that allows site administrators to manage their WordPress site through natural language commands. The Plugin connects to our backend server to process requests using large language model (LLM) technology.',
  },
  {
    title: '3. License',
    body: 'Subject to your compliance with these Terms, we grant you a limited, non-exclusive, non-transferable, revocable license to install and use the Plugin on WordPress sites you own or operate. You may not sublicense, sell, or commercially redistribute the Plugin.',
  },
  {
    title: '4. User Responsibilities',
    body: 'You are responsible for maintaining the security of your API keys and account credentials. You agree not to use the Plugin to perform unauthorized actions on WordPress sites you do not own or have permission to manage. You are solely responsible for all actions executed through the Plugin.',
  },
  {
    title: '5. Disclaimer of Warranties',
    body: 'THE PLUGIN IS PROVIDED "AS IS" WITHOUT WARRANTY OF ANY KIND. WE DISCLAIM ALL WARRANTIES, EXPRESS OR IMPLIED, INCLUDING MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT. WE DO NOT WARRANT THAT THE PLUGIN WILL BE UNINTERRUPTED OR ERROR-FREE.',
  },
  {
    title: '6. Limitation of Liability',
    body: 'TO THE MAXIMUM EXTENT PERMITTED BY LAW, WE SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING LOSS OF PROFITS OR DATA, ARISING FROM YOUR USE OF OR INABILITY TO USE THE PLUGIN.',
  },
  {
    title: '7. Changes to Terms',
    body: 'We reserve the right to modify these Terms at any time. We will notify you of significant changes by updating the date at the top of this page. Continued use of the Plugin after changes constitutes acceptance of the revised Terms.',
  },
  {
    title: '8. Contact Us',
    body: 'If you have questions about these Terms, please contact us at legal@wally.ai. We aim to respond to all inquiries within 5 business days.',
  },
];

export default function TermsPage() {
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
              Terms of Service
            </h1>
            <p className="text-sm text-muted-foreground">
              Effective Date: March 1, 2026 · Version 1.0
            </p>
          </div>

          <hr className="border-border" />

          {/* Intro */}
          <p className="text-[15px] leading-[1.7] text-muted-foreground">
            By installing, accessing, or using Wally (&ldquo;the Plugin&rdquo;), you agree to be bound by
            these Terms of Service. If you do not agree, please do not use the Plugin. These terms
            apply to all users, including website administrators and content editors.
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
