'use client';

import { notFound } from 'next/navigation';
import { motion } from 'framer-motion';
import { Clock, Twitter, Linkedin } from 'lucide-react';
import Image from 'next/image';
import {
  AnimatedSection,
  StaggerContainer,
  StaggerItem,
} from '@/components/landing/shared/animated-section';
import { Container } from '@/components/landing/shared/container';
import { Navbar } from '@/components/landing/navbar';
import { Footer } from '@/components/landing/footer';
import { BLOG_POSTS } from '../page';
import type { BlogPost } from '../page';
import { cn } from '@/lib/utils';
import { use } from 'react';

// ─── Full article content keyed by slug ─────────────────────────────────────

interface ArticleData {
  toc: { id: string; label: string }[];
  content: React.ReactNode;
  pullQuote?: { text: string; source: string };
  authorBio: string;
}

const ARTICLE_DATA: Record<string, ArticleData> = {
  'ai-powered-wordpress-management': {
    toc: [
      { id: 'interface-problem', label: 'The interface problem' },
      { id: 'conversational-ai', label: 'How conversational AI changes everything' },
      { id: 'real-world-impact', label: 'Real-world impact' },
    ],
    pullQuote: {
      text: '"The best interface is the one you never have to think about. With Wally, managing WordPress becomes as natural as sending a message to a colleague."',
      source: '— Sarah Chen, Head of Product at Wally',
    },
    authorBio:
      'Sarah has spent 8 years at the intersection of AI and web publishing. She leads product strategy at Wally and writes about the future of site management, automation, and developer productivity.',
    content: (
      <>
        <p>
          For most WordPress users, managing a site has always meant navigating a labyrinth
          of dashboards, settings panels, and plugin interfaces. You need to know where to
          find things, what each option does, and how to avoid breaking something in the
          process.
        </p>

        <h2 id="interface-problem">The interface problem</h2>
        <p>
          WordPress&apos;s block editor, Gutenberg, was a major step forward for content
          creation — but it didn&apos;t solve the admin complexity problem. For every post a user
          creates, there are dozens of behind-the-scenes tasks: managing redirects, updating
          SEO metadata, handling plugin conflicts, monitoring performance.
        </p>

        <h2 id="conversational-ai">How conversational AI changes everything</h2>
        <p>
          Wally replaces the entire admin navigation with a single chat interface. Instead
          of hunting for the right settings panel, you simply describe what you want to do.
          &ldquo;Update the SEO title on my latest post.&rdquo; &ldquo;Show me all plugins
          that haven&apos;t been updated in 6 months.&rdquo; &ldquo;Create a draft about our
          spring sale.&rdquo;
        </p>
        <p>
          The AI understands intent, not just keywords. It knows the difference between
          &lsquo;delete the post&rsquo; and &lsquo;trash the post&rsquo;, and it will ask
          for confirmation before taking any action that can&apos;t be undone. This kind of
          contextual awareness is what separates Wally from simple automation scripts.
        </p>

        <h2 id="real-world-impact">Real-world impact</h2>
        <p>
          Early adopters report saving 2-4 hours per week on routine site management.
          Content teams can now publish posts, manage media, and update settings without
          ever leaving their workflow. Developers use Wally to quickly audit sites and make
          bulk changes across multiple installations.
        </p>
      </>
    ),
  },

  'top-productivity-tips-wp-admin': {
    toc: [
      { id: 'bulk-metadata', label: 'Bulk-updating post metadata' },
      { id: 'search-replace', label: 'Search-and-replace across content' },
      { id: 'scheduling', label: 'Scheduling content in bulk' },
    ],
    pullQuote: {
      text: '"You describe the outcome, not the steps. That shift alone reclaims hours every month."',
      source: '— Marcus Webb, Developer Advocate',
    },
    authorBio:
      'Marcus is a developer advocate at Wally who helps WordPress developers automate their workflows. Previously at Automattic, he brings deep WordPress expertise to everything he writes.',
    content: (
      <>
        <p>
          If you&apos;ve ever spent twenty minutes doing something that should take two,
          you&apos;re not alone. WordPress admin is full of repetitive tasks that eat into
          your day. Here are five of the most common ones you can hand off to an AI
          assistant right now.
        </p>

        <h2 id="bulk-metadata">Bulk-updating post metadata</h2>
        <p>
          Need to add a category, update an author, or set a featured image across thirty
          posts? Instead of editing each post individually, just ask: &ldquo;Add the
          category &apos;Tutorials&apos; to all posts tagged &apos;beginner&apos;.&rdquo;
        </p>

        <h2 id="search-replace">Running search-and-replace across content</h2>
        <p>
          Rebranding? Changed a product name? A single natural language command can update
          every instance across all your posts and pages in seconds — safely, with a
          preview before committing.
        </p>

        <h2 id="scheduling">Scheduling content in bulk</h2>
        <p>
          You&apos;ve drafted five posts. Instead of opening each one and setting a publish
          date, tell the AI: &ldquo;Schedule these five posts to publish every Tuesday at
          9am starting next week.&rdquo;
        </p>
      </>
    ),
  },

  'getting-started-wally-plugin': {
    toc: [
      { id: 'connect-site', label: 'Connect your site' },
      { id: 'open-sidebar', label: 'Open the chat sidebar' },
      { id: 'first-change', label: 'Make your first change' },
    ],
    pullQuote: {
      text: '"The best way to learn what Wally can do is to just ask it. If a task isn\'t supported yet, it will say so clearly."',
      source: '— Priya Nair, Technical Writer',
    },
    authorBio:
      'Priya is a technical writer at Wally who specializes in creating clear, accessible documentation for WordPress tools and developer APIs.',
    content: (
      <>
        <p>
          You&apos;ve installed Wally. The chat sidebar is open inside your WordPress
          admin. Now what? This guide walks you through the first ten minutes — from your
          first command to your first real site change.
        </p>

        <h2 id="connect-site">Step 1: Connect your site</h2>
        <p>
          After activating the plugin, navigate to <strong>Settings &rarr; Wally</strong>{' '}
          in your WordPress admin. Paste your API key from the Wally dashboard and click
          Save. The sidebar will show a green &ldquo;Connected&rdquo; indicator when the
          link is established.
        </p>

        <h2 id="open-sidebar">Step 2: Open the chat sidebar</h2>
        <p>
          The Wally icon appears in the bottom-right corner of every wp-admin page. Click
          it to open the sliding chat panel. You can drag it to resize, or click the expand
          button for a wider view when working on longer tasks.
        </p>

        <h2 id="first-change">Step 3: Make your first change</h2>
        <p>
          Pick something low-stakes for your first real action. A good starting point:
          &ldquo;Update the site tagline to [your new tagline].&rdquo; Wally will show you
          a confirmation card before executing — review it, then click Confirm.
        </p>
      </>
    ),
  },

  'content-workflows-with-ai': {
    toc: [
      { id: 'anatomy', label: 'The anatomy of a content workflow' },
      { id: 'drafting', label: 'Drafting at scale' },
      { id: 'scheduling', label: 'Scheduling without overhead' },
    ],
    pullQuote: {
      text: '"AI handles the mechanics; humans handle the judgment. That division of labor is what makes the workflow sustainable."',
      source: '— Sarah Chen, Head of Product',
    },
    authorBio:
      'Sarah has spent 8 years at the intersection of AI and web publishing. She leads product strategy at Wally and writes about the future of site management, automation, and developer productivity.',
    content: (
      <>
        <p>
          Publishing content consistently is one of the hardest habits to maintain. Most
          teams have good intentions but fragile workflows — a process that depends on one
          person, a calendar that gets ignored when things get busy, or a backlog that never
          gets cleared. AI can fix that.
        </p>

        <h2 id="anatomy">The anatomy of a content workflow</h2>
        <p>
          A repeatable content workflow has four stages: idea &rarr; draft &rarr; review
          &rarr; publish. The problem is that each transition is a friction point. Ideas
          stay in Notion, drafts sit in Google Docs, review comments get lost in Slack.
        </p>

        <h2 id="drafting">Drafting at scale</h2>
        <p>
          Instead of staring at a blank editor, use Wally to generate first drafts from a
          brief. You&apos;re not publishing the draft as-is — you&apos;re eliminating the
          hardest part of writing: starting.
        </p>

        <h2 id="scheduling">Scheduling without the admin overhead</h2>
        <p>
          Once your drafts are ready, scheduling is a single command: &ldquo;Schedule these
          four posts to publish every Monday at 8am, starting next week.&rdquo; No opening
          each post. No setting dates one by one. Done.
        </p>
      </>
    ),
  },

  'rest-api-guide-2026': {
    toc: [
      { id: 'why-rest-api', label: 'Why the REST API matters' },
      { id: 'authentication', label: 'Authentication methods' },
      { id: 'custom-endpoints', label: 'Building custom endpoints' },
    ],
    pullQuote: {
      text: '"The REST API is the backbone of modern WordPress development. Understanding it isn\'t optional anymore — it\'s essential."',
      source: '— Priya Nair, Technical Writer',
    },
    authorBio:
      'Priya is a technical writer at Wally who specializes in creating clear, accessible documentation for WordPress tools and developer APIs.',
    content: (
      <>
        <p>
          WordPress&apos;s REST API has matured into one of the most powerful tools in the
          ecosystem. Whether you&apos;re building a headless frontend, integrating with
          third-party services, or extending WordPress with custom functionality, the REST
          API is your foundation.
        </p>

        <h2 id="why-rest-api">Why the REST API matters in 2026</h2>
        <p>
          The REST API enables WordPress to function as a headless CMS, powering React,
          Next.js, and mobile applications. Tools like Wally use it internally to execute
          site management tasks through natural language — every chat command translates to
          one or more API calls behind the scenes.
        </p>

        <h2 id="authentication">Authentication methods</h2>
        <p>
          WordPress supports several authentication methods for the REST API: application
          passwords (built-in since 5.6), JWT tokens via plugins, and OAuth 2.0 for
          third-party integrations. For most use cases, application passwords provide the
          simplest setup with strong security.
        </p>

        <h2 id="custom-endpoints">Building custom endpoints</h2>
        <p>
          Custom endpoints let you extend the REST API with your own business logic.
          Register them with <code>register_rest_route()</code> in your plugin or theme,
          define permission callbacks, and return structured JSON responses. This is how
          Wally&apos;s plugin communicates with the backend orchestration server.
        </p>
      </>
    ),
  },
};

// ─── Components ─────────────────────────────────────────────────────────────

function AuthorAvatar({ name, size = 44 }: { name: string; size?: number }) {
  const initials = name
    .split(' ')
    .map((n) => n[0])
    .join('');
  return (
    <div
      className="flex flex-shrink-0 items-center justify-center rounded-full bg-[#E5E5E5] text-[13px] font-bold text-[#888]"
      style={{ width: size, height: size }}
    >
      {initials}
    </div>
  );
}

function RelatedCard({ post }: { post: BlogPost }) {
  return (
    <a
      href={`/blog/${post.slug}`}
      className="group flex flex-col overflow-hidden rounded-xl border border-[#E5E5E5] bg-white transition-all hover:-translate-y-1 hover:shadow-[0_8px_32px_rgba(0,0,0,0.08)]"
    >
      <div className="relative h-40 w-full overflow-hidden">
        <Image
          src={post.image}
          alt={post.title}
          fill
          className="object-cover transition-transform duration-500 group-hover:scale-105"
        />
      </div>
      <div className="flex flex-col gap-3 p-5">
        <span
          className={cn(
            'inline-flex w-fit items-center rounded px-2.5 py-1 text-[11px] font-semibold',
            post.tagColor
          )}
        >
          {post.category}
        </span>
        <h3 className="font-heading text-base font-bold leading-snug text-[#0A0A0A] transition-colors group-hover:text-primary">
          {post.title}
        </h3>
        <span className="flex items-center gap-1 text-[12px] text-[#888]">
          <Clock className="h-3 w-3" />
          {post.readTime}
        </span>
      </div>
    </a>
  );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function BlogPostPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = use(params);
  const post = BLOG_POSTS.find((p) => p.slug === slug);

  if (!post) notFound();

  const article = ARTICLE_DATA[slug];
  const related = BLOG_POSTS.filter((p) => p.slug !== slug).slice(0, 2);

  return (
    <div className="min-h-screen bg-white">
      <Navbar />

      {/* Navbar */}
      <div className="h-[72px]" />

      {/* Hero Image */}
      <motion.div
        className="relative h-[400px] w-full overflow-hidden sm:h-[520px]"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ duration: 0.6 }}
      >
        <Image
          src={post.image}
          alt={post.title}
          fill
          className="object-cover"
          priority
        />
      </motion.div>

      {/* Article Wrapper */}
      <Container>
        <div className="flex gap-20 py-[72px]">
          {/* Main content */}
          <div className="min-w-0 flex-1">
            {/* Article Header */}
            <motion.div
              className="flex flex-col gap-6"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, delay: 0.2 }}
            >
              <div className="flex items-center gap-3">
                <span
                  className={cn(
                    'inline-flex items-center rounded px-2.5 py-1 text-[11px] font-semibold',
                    post.tagColor
                  )}
                >
                  {post.category}
                </span>
                <div className="flex items-center gap-1.5 text-[12px] text-[#888]">
                  <a
                    href="/blog"
                    className="transition-colors hover:text-[#333]"
                  >
                    Blog
                  </a>
                  <span>/</span>
                  <span className="text-[#555]">Article</span>
                </div>
              </div>

              <h1 className="font-heading text-4xl font-extrabold leading-[1.05] text-[#0A0A0A] sm:text-5xl">
                {post.title}
              </h1>

              <p className="text-lg leading-relaxed text-[#555]">
                {post.excerpt}
              </p>

              <div className="flex items-center gap-5">
                <AuthorAvatar name={post.author.name} />
                <div>
                  <p className="text-[14px] font-semibold text-[#0A0A0A]">
                    {post.author.name}
                  </p>
                  <p className="text-[13px] text-[#888]">
                    {post.author.role} &middot; {post.date}
                  </p>
                </div>
              </div>
            </motion.div>

            {/* Divider */}
            <div className="my-10 h-px w-full bg-[#E5E5E5]" />

            {/* Body Content */}
            <motion.article
              className="prose-wally"
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, delay: 0.3 }}
            >
              {article?.content ?? (
                <p className="text-[#888]">Content coming soon.</p>
              )}
            </motion.article>

            {/* Pull Quote */}
            {article?.pullQuote && (
              <AnimatedSection className="my-10">
                <div className="rounded-none border-l-4 border-primary bg-[#FAFAFA] px-10 py-8">
                  <p className="font-heading text-[22px] font-semibold leading-[1.4] text-[#1A1A1A]">
                    {article.pullQuote.text}
                  </p>
                  <p className="mt-4 text-[13px] font-medium text-[#888]">
                    {article.pullQuote.source}
                  </p>
                </div>
              </AnimatedSection>
            )}

            {/* Author Bio */}
            {article?.authorBio && (
              <AnimatedSection className="mt-10">
                <div className="flex gap-6 rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA] p-8">
                  <AuthorAvatar name={post.author.name} size={72} />
                  <div className="flex flex-col gap-2">
                    <p className="text-[11px] font-semibold uppercase tracking-[2px] text-[#888]">
                      Written by
                    </p>
                    <p className="font-heading text-xl font-bold text-[#0A0A0A]">
                      {post.author.name}
                    </p>
                    <p className="text-[13px] font-medium text-primary">
                      {post.author.role} &middot; AI &amp; WordPress researcher
                    </p>
                    <p className="mt-1 text-[14px] leading-relaxed text-[#555]">
                      {article.authorBio}
                    </p>
                  </div>
                </div>
              </AnimatedSection>
            )}

            {/* Related Articles */}
            <div className="mt-16 flex flex-col gap-6">
              <p className="text-[11px] font-semibold uppercase tracking-[2px] text-[#888]">
                Continue Reading
              </p>
              <div className="h-px w-full bg-[#E5E5E5]" />
              <StaggerContainer
                className="grid gap-6 sm:grid-cols-2"
                staggerDelay={0.1}
              >
                {related.map((relPost) => (
                  <StaggerItem key={relPost.slug}>
                    <RelatedCard post={relPost} />
                  </StaggerItem>
                ))}
              </StaggerContainer>
            </div>
          </div>

          {/* Sidebar */}
          <aside className="hidden w-[280px] flex-shrink-0 lg:block">
            <div className="sticky top-28 flex flex-col gap-8">
              {/* Table of Contents */}
              {article?.toc && (
                <div className="flex flex-col gap-5 rounded-xl border border-[#E5E5E5] bg-white p-6">
                  <p className="text-[11px] font-semibold uppercase tracking-[2px] text-[#888]">
                    Table of Contents
                  </p>
                  <div className="h-px w-full bg-[#E5E5E5]" />
                  <nav className="flex flex-col gap-3.5">
                    {article.toc.map((item, i) => (
                      <a
                        key={item.id}
                        href={`#${item.id}`}
                        className="flex items-start gap-2.5 text-[13px] text-[#555] transition-colors hover:text-primary"
                      >
                        <span className="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-[#F5F5F5] text-[10px] font-semibold text-[#888]">
                          {i + 1}
                        </span>
                        {item.label}
                      </a>
                    ))}
                  </nav>
                </div>
              )}

              {/* Share */}
              <div className="flex flex-col gap-4 rounded-xl border border-[#E5E5E5] bg-[#FAFAFA] p-6">
                <p className="text-[11px] font-semibold uppercase tracking-[2px] text-[#888]">
                  Share Article
                </p>
                <div className="flex gap-2.5">
                  <button className="flex items-center gap-1.5 rounded-lg border border-[#E5E5E5] bg-white px-3.5 py-2 text-[12px] font-medium text-[#333] transition-colors hover:border-[#999]">
                    <Twitter className="h-3.5 w-3.5" />
                    Twitter
                  </button>
                  <button className="flex items-center gap-1.5 rounded-lg border border-[#E5E5E5] bg-white px-3.5 py-2 text-[12px] font-medium text-[#333] transition-colors hover:border-[#999]">
                    <Linkedin className="h-3.5 w-3.5" />
                    LinkedIn
                  </button>
                </div>
              </div>
            </div>
          </aside>
        </div>
      </Container>

      <Footer />
    </div>
  );
}
