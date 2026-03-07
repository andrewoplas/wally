import type { Metadata } from 'next';
import { Navbar } from '@/components/landing/navbar';
import { Footer } from '@/components/landing/footer';
import { Container } from '@/components/landing/shared/container';
import { FeedbackForm } from '@/components/feedback/feedback-form';

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL ?? 'https://www.wallychat.com';

export const metadata: Metadata = {
  title: 'Feedback | Wally',
  description:
    'Share your feedback, report bugs, or request features for Wally — the AI-powered WordPress assistant.',
  alternates: { canonical: `${SITE_URL}/feedback` },
};

export default function FeedbackPage() {
  return (
    <>
      <Navbar />
      <main className="min-h-screen bg-gradient-to-b from-muted/40 to-white pt-32 pb-20">
        <Container className="flex flex-col items-center">
          <div className="mb-10 text-center">
            <h1 className="font-heading text-4xl font-extrabold tracking-tight text-foreground sm:text-5xl">
              Send us feedback
            </h1>
            <p className="mx-auto mt-4 max-w-[500px] text-lg text-muted-foreground">
              Have a bug to report, a feature to request, or just want to share
              your thoughts? We&apos;d love to hear from you.
            </p>
          </div>
          <FeedbackForm />
        </Container>
      </main>
      <Footer />
    </>
  );
}
