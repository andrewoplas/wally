import type { Metadata } from 'next';
import Image from 'next/image';
import Link from 'next/link';
import { PenLine } from 'lucide-react';
import { Navbar } from '@/components/landing/navbar';
import { Footer } from '@/components/landing/footer';
import { BlogGrid } from '@/components/blog/blog-grid';
import { NewsletterCta } from '@/components/blog/newsletter-cta';
import { BLOG_POSTS } from '@/lib/blog-data';

export const metadata: Metadata = {
  title: 'Blog — Wally',
  description:
    'Insights, tips & updates for WordPress managers. Learn how to streamline your WordPress workflow with AI-powered automation.',
  openGraph: {
    title: 'Blog — Wally',
    description:
      'Insights, tips & updates for WordPress managers. Learn how to streamline your WordPress workflow with AI-powered automation.',
    url: '/blog',
    type: 'website',
    images: [{ url: '/site-og.png', width: 1200, height: 630 }],
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Blog — Wally',
    description:
      'Insights, tips & updates for WordPress managers.',
    images: ['/site-og.png'],
  },
  alternates: { canonical: '/blog' },
};

export default function BlogPage() {
  const featured = BLOG_POSTS.find((p) => p.featured)!;
  const rest = BLOG_POSTS.filter((p) => !p.featured);

  return (
    <div className="min-h-screen bg-white">
      <Navbar />

      {/* ─── Hero Section ─────────────────────────────────────────────── */}
      <section className="relative overflow-hidden" style={{ height: 'auto' }}>
        {/* Background layers */}
        <div className="absolute inset-0" aria-hidden="true">
          {/* Gradient background */}
          <div
            className="absolute inset-0"
            style={{
              background:
                'linear-gradient(to bottom, #0C0A1A 0%, #0E0C22 6%, #13102E 12%, #1A1240 18%, #1E1548 24%, #221850 30%, #2D1F62 37%, #3D2B74 44%, #4A3580 50%, #6B52A0 56%, #9E85C8 62%, #C8BAE0 68%, #E8E0F2 74%, #F5F3FF 78%, #FFFFFF 82%)',
            }}
          />
          {/* Central glow */}
          <div
            className="absolute left-1/2 top-0 -translate-x-1/2"
            style={{
              width: 900,
              height: 550,
              marginTop: -20,
              background:
                'radial-gradient(ellipse at center, rgba(124,58,237,0.21) 0%, rgba(91,33,182,0.09) 50%, transparent 100%)',
              filter: 'blur(80px)',
            }}
          />
          {/* Side glow left */}
          <div
            className="absolute"
            style={{
              left: -80,
              top: 50,
              width: 500,
              height: 400,
              background:
                'radial-gradient(ellipse at center, rgba(109,40,217,0.13) 0%, transparent 100%)',
              filter: 'blur(60px)',
            }}
          />
          {/* Side glow right */}
          <div
            className="absolute"
            style={{
              right: -80,
              top: 40,
              width: 500,
              height: 400,
              background:
                'radial-gradient(ellipse at center, rgba(76,29,149,0.09) 0%, transparent 100%)',
              filter: 'blur(60px)',
            }}
          />
          {/* Bottom light wash */}
          <div
            className="absolute bottom-0 left-0 right-0"
            style={{
              height: 380,
              background:
                'linear-gradient(to bottom, transparent 0%, rgba(255,255,255,0.25) 30%, white 70%)',
            }}
          />

          {/* Decorative stars */}
          {[
            { left: 250, top: 60, size: 4, opacity: 0.5 },
            { left: '76%', top: 90, size: 3, opacity: 0.4 },
            { left: 160, top: 200, size: 5, opacity: 0.35, color: '#C4B5FD' },
            { left: '87%', top: 160, size: 3, opacity: 0.3 },
            { left: '48%', top: 40, size: 4, opacity: 0.45 },
            { left: '33%', top: 120, size: 2, opacity: 0.35 },
            { left: '64%', top: 50, size: 3, opacity: 0.4, color: '#DDD6FE' },
            { left: '26%', top: 30, size: 3, opacity: 0.3 },
          ].map((star, i) => (
            <div
              key={i}
              className="absolute rounded-full"
              style={{
                left: star.left,
                top: star.top,
                width: star.size,
                height: star.size,
                backgroundColor: star.color ?? '#FFFFFF',
                opacity: star.opacity,
                filter: `blur(${star.size > 3 ? 2 : 1.5}px)`,
              }}
            />
          ))}
        </div>

        {/* Hero content */}
        <div className="relative z-10 flex flex-col items-center gap-5 px-6 pt-32 pb-0 md:px-20 md:pt-36 lg:px-20 lg:pt-40">
          {/* Badge */}
          <div className="flex items-center gap-1.5 rounded-full border border-white/[.13] bg-white/[.06] px-4 py-1.5">
            <PenLine className="h-3.5 w-3.5 text-[#C4B5FD]" />
            <span className="text-[13px] font-semibold text-[#C4B5FD]">
              Wally Blog
            </span>
          </div>

          {/* Title */}
          <h1 className="max-w-[700px] text-center font-heading text-3xl font-extrabold leading-[1.15] text-white md:text-4xl lg:text-[48px]">
            Insights, tips &amp; updates
            <br />
            for WordPress managers
          </h1>

          {/* Subtitle */}
          <p className="max-w-[600px] text-center text-base leading-[1.6] text-[#E0D8F0] md:text-lg">
            Learn how to streamline your WordPress workflow with AI-powered
            automation, best practices, and expert guides.
          </p>
        </div>

        {/* Featured Card */}
        <div className="relative z-10 mx-auto mt-10 w-full max-w-[1200px] px-6 pb-16 md:mt-12 md:px-10 lg:px-[120px] lg:pb-20">
          <Link
            href={`/blog/${featured.slug}`}
            className="group flex flex-col overflow-hidden rounded-[20px] border border-[#E4E4E7] bg-white lg:flex-row"
            style={{
              boxShadow: '0 12px 40px -8px rgba(0,0,0,0.08)',
            }}
          >
            {/* Image */}
            <div className="relative h-56 w-full flex-shrink-0 overflow-hidden sm:h-64 lg:h-auto lg:w-[560px]">
              <Image
                src={featured.image}
                alt={featured.title}
                fill
                className="object-cover transition-transform duration-500 group-hover:scale-105"
                priority
              />
            </div>

            {/* Content */}
            <div className="flex flex-1 flex-col justify-center gap-5 p-8 md:p-10 lg:p-12">
              <span
                className="inline-flex w-fit rounded-full px-3 py-1 text-[12px] font-semibold"
                style={{
                  backgroundColor: featured.tagColor.bg,
                  color: featured.tagColor.text,
                }}
              >
                {featured.category}
              </span>

              <h2 className="font-heading text-2xl font-bold leading-[1.3] text-[#18181B] transition-colors group-hover:text-primary md:text-[28px]">
                {featured.title}
              </h2>

              <p className="text-[16px] leading-[1.6] text-[#71717A]">
                {featured.excerpt}
              </p>

              <div className="flex items-center gap-4 pt-2 text-[14px] text-[#71717A]">
                <span>{featured.date}</span>
                <span className="inline-block h-1 w-1 rounded-full bg-[#D4D4D8]" />
                <span>{featured.readTime}</span>
              </div>
            </div>
          </Link>
        </div>
      </section>

      {/* ─── Blog Posts Grid ──────────────────────────────────────────── */}
      <BlogGrid posts={rest} />

      {/* ─── Newsletter CTA ───────────────────────────────────────────── */}
      <NewsletterCta variant="light" />

      {/* ─── Footer ───────────────────────────────────────────────────── */}
      <Footer />
    </div>
  );
}
