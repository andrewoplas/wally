import type { Metadata } from 'next';
import Image from 'next/image';
import Link from 'next/link';
import { Twitter, Linkedin, Link2 } from 'lucide-react';
import { notFound } from 'next/navigation';
import { Navbar } from '@/components/landing/navbar';
import { Footer } from '@/components/landing/footer';
import { NewsletterCta } from '@/components/blog/newsletter-cta';
import {
  BLOG_POSTS,
  ARTICLE_DATA,
  getPostBySlug,
  getRelatedPosts,
} from '@/lib/blog-data';

// ─── Static Params ───────────────────────────────────────────────────────────

export function generateStaticParams() {
  return BLOG_POSTS.map((post) => ({ slug: post.slug }));
}

// ─── Metadata ────────────────────────────────────────────────────────────────

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const post = getPostBySlug(slug);
  if (!post) return {};

  return {
    title: `${post.title} — Wally Blog`,
    description: post.excerpt,
    openGraph: {
      title: post.title,
      description: post.excerpt,
      url: `/blog/${post.slug}`,
      type: 'article',
      publishedTime: post.date,
      authors: [post.author.name],
      images: [{ url: post.image, width: 1200, height: 630 }],
    },
    twitter: {
      card: 'summary_large_image',
      title: post.title,
      description: post.excerpt,
      images: [post.image],
    },
    alternates: { canonical: `/blog/${post.slug}` },
  };
}

// ─── Related Card ────────────────────────────────────────────────────────────

function RelatedCard({
  post,
}: {
  post: (typeof BLOG_POSTS)[number];
}) {
  return (
    <Link
      href={`/blog/${post.slug}`}
      className="group flex flex-col overflow-hidden rounded-[16px] border border-[#1E1A32] bg-[#14112A] transition-all hover:-translate-y-1 hover:border-[#2B2840]"
    >
      <div className="relative h-[180px] w-full overflow-hidden">
        <Image
          src={post.image}
          alt={post.title}
          fill
          className="object-cover transition-transform duration-500 group-hover:scale-105"
        />
      </div>
      <div className="flex flex-col gap-3 px-6 pt-5 pb-6">
        <span className="inline-flex w-fit rounded-full border border-[#8B5CF625] bg-[#8B5CF615] px-2.5 py-1 text-[11px] font-semibold text-[#A78BFA]">
          {post.category}
        </span>
        <h3 className="font-heading text-[18px] font-bold leading-[1.35] text-[#D8D4E2] transition-colors group-hover:text-[#A78BFA]">
          {post.title}
        </h3>
        <div className="flex items-center gap-2.5 text-[13px] text-[#5E5870]">
          <span>{post.date}</span>
          <span className="inline-block h-[3px] w-[3px] rounded-full bg-[#3D3650]" />
          <span>{post.readTime}</span>
        </div>
      </div>
    </Link>
  );
}

// ─── Page ────────────────────────────────────────────────────────────────────

export default async function BlogPostPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const post = getPostBySlug(slug);
  if (!post) notFound();

  const article = ARTICLE_DATA[slug];
  const related = getRelatedPosts(slug, 3);

  // JSON-LD structured data
  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'BlogPosting',
    headline: post.title,
    description: post.excerpt,
    datePublished: post.date,
    author: {
      '@type': 'Person',
      name: post.author.name,
    },
    publisher: {
      '@type': 'Organization',
      name: 'Wally',
      url: process.env.NEXT_PUBLIC_SITE_URL ?? 'https://usewally.com',
    },
    image: post.image,
  };

  return (
    <div className="min-h-screen bg-[#0C0A1A]">
      <Navbar variant="dark" />

      {/* JSON-LD */}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />

      {/* ─── Article Header ─────────────────────────────────────────── */}
      <section className="px-6 pt-28 pb-12 md:px-20 md:pt-32 lg:px-[300px] lg:pt-[calc(72px+56px)]">
        <div className="flex flex-col items-center gap-7">
          {/* Breadcrumb */}
          <nav className="flex items-center gap-2 text-[14px]">
            <Link
              href="/blog"
              className="font-medium text-[#A78BFA] transition-colors hover:text-[#C4B5FD]"
            >
              Blog
            </Link>
            <span className="text-[#4A4458]">/</span>
            <span className="text-[#6B6580]">{post.category}</span>
          </nav>

          {/* Title block */}
          <div className="flex flex-col items-center gap-5">
            <span className="inline-flex rounded-full border border-[#8B5CF630] bg-[#8B5CF618] px-3.5 py-1 text-[12px] font-semibold tracking-[0.5px] text-[#A78BFA]">
              {post.category}
            </span>

            <h1 className="max-w-[800px] text-center font-heading text-3xl font-extrabold leading-[1.15] text-[#F0ECF8] md:text-4xl lg:text-[44px]">
              {post.title}
            </h1>

            <p className="max-w-[620px] text-center text-base leading-[1.6] text-[#8A8498] md:text-lg">
              {post.excerpt}
            </p>
          </div>

          {/* Meta row */}
          <div className="flex items-center gap-5 text-[14px] text-[#6B6580]">
            <span>{post.date}</span>
            <span className="inline-block h-1 w-1 rounded-full bg-[#3D3650]" />
            <span>{post.readTime}</span>
            <span className="inline-block h-1 w-1 rounded-full bg-[#3D3650]" />
            <span>By {post.author.name}</span>
          </div>
        </div>
      </section>

      {/* ─── Featured Image ─────────────────────────────────────────── */}
      <section className="px-6 md:px-20 lg:px-[120px]">
        <div
          className="relative mx-auto h-[280px] w-full overflow-hidden rounded-[20px] border border-white/[.06] sm:h-[380px] lg:h-[520px]"
          style={{
            boxShadow:
              '0 8px 60px -4px rgba(124,58,237,0.09), 0 24px 120px -20px rgba(91,33,182,0.06)',
          }}
        >
          <Image
            src={post.image}
            alt={post.title}
            fill
            className="object-cover"
            priority
          />
        </div>
      </section>

      {/* ─── Article Body ───────────────────────────────────────────── */}
      <section className="px-6 py-12 md:px-20 md:py-16 lg:px-[360px] lg:py-16">
        <article className="prose-wally-dark flex flex-col gap-8">
          {article?.content ?? (
            <p className="text-[#5E5870]">Content coming soon.</p>
          )}
        </article>

        {/* Divider */}
        <hr className="my-10 border-[#1E1A32]" />

        {/* Tags & Share */}
        {article?.tags && (
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex flex-wrap gap-2">
              {article.tags.map((tag) => (
                <span
                  key={tag}
                  className="rounded-full border border-[#2B2840] bg-[#1A1730] px-3.5 py-1.5 text-[13px] font-medium text-[#8A8498]"
                >
                  {tag}
                </span>
              ))}
            </div>

            <div className="flex items-center gap-3">
              <span className="text-[14px] font-medium text-[#5E5870]">
                Share
              </span>
              {[
                { icon: Twitter, label: 'Twitter' },
                { icon: Linkedin, label: 'LinkedIn' },
                { icon: Link2, label: 'Copy link' },
              ].map(({ icon: Icon, label }) => (
                <button
                  key={label}
                  aria-label={label}
                  className="flex h-9 w-9 items-center justify-center rounded-[10px] border border-[#2B2840] bg-[#1A1730] text-[#6B6580] transition-colors hover:border-[#3D3650] hover:text-[#A78BFA]"
                >
                  <Icon className="h-4 w-4" />
                </button>
              ))}
            </div>
          </div>
        )}
      </section>

      {/* ─── Related Articles ───────────────────────────────────────── */}
      <section className="px-6 pt-8 pb-16 md:px-20 md:pt-16 md:pb-20 lg:px-[120px] lg:pt-16 lg:pb-20">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <h2 className="font-heading text-[28px] font-bold text-[#E8E4F0]">
            Related Articles
          </h2>
          <Link
            href="/blog"
            className="text-[15px] font-medium text-[#A78BFA] transition-colors hover:text-[#C4B5FD]"
          >
            View all articles &rarr;
          </Link>
        </div>

        <hr className="my-8 border-[#1E1A32]" />

        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {related.map((relPost) => (
            <RelatedCard key={relPost.slug} post={relPost} />
          ))}
        </div>
      </section>

      {/* ─── Newsletter CTA ─────────────────────────────────────────── */}
      <NewsletterCta variant="dark" />

      {/* ─── Footer ─────────────────────────────────────────────────── */}
      <Footer />
    </div>
  );
}
