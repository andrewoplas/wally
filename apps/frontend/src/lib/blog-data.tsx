import React from 'react';

// ─── Types ───────────────────────────────────────────────────────────────────

export interface BlogPost {
  slug: string;
  category: string;
  filterCategory: string;
  date: string;
  readTime: string;
  title: string;
  excerpt: string;
  author: { name: string; role: string };
  featured: boolean;
  image: string;
  tagColor: { bg: string; text: string };
}

export interface ArticleData {
  content: React.ReactNode;
  tags: string[];
}

// ─── Categories & Tag Colors ─────────────────────────────────────────────────

export const CATEGORIES = ['All', 'Guides', 'Tips', 'Updates'] as const;

export type Category = (typeof CATEGORIES)[number];

/**
 * Predefined tag color palettes. Use these keys in BlogPost.tagColor
 * instead of remembering hex codes. See BLOG.md for the full list.
 */
export const TAG_COLORS = {
  purple: { bg: '#EDE9FE', text: '#7C3AED' },
  blue:   { bg: '#DBEAFE', text: '#2563EB' },
  amber:  { bg: '#FEF3C7', text: '#D97706' },
  green:  { bg: '#DCFCE7', text: '#16A34A' },
  rose:   { bg: '#FFE4E6', text: '#E11D48' },
} as const;

// ─── Blog Posts ──────────────────────────────────────────────────────────────

export const BLOG_POSTS: BlogPost[] = [
  {
    slug: 'getting-started-wally-plugin',
    category: 'Getting Started',
    filterCategory: 'Guides',
    date: 'Feb 24, 2026',
    readTime: '8 min read',
    title: 'Getting Started with Wally: Your Complete Setup Guide',
    excerpt:
      "Everything you need to know to install, configure, and start managing your WordPress site with Wally's AI assistant. From activation to your first conversation.",
    author: { name: 'Wally Team', role: 'Product' },
    featured: true,
    image: '/blog/getting-started.png',
    tagColor: TAG_COLORS.purple,
  },
  {
    slug: 'top-productivity-tips-wp-admin',
    category: 'Productivity',
    filterCategory: 'Tips',
    date: 'Feb 20, 2026',
    readTime: '5 min read',
    title: '5 Ways Wally Saves You Hours Every Week',
    excerpt:
      'Discover the most impactful time-saving features that WordPress managers love about Wally.',
    author: { name: 'Sarah Chen', role: 'Head of Product' },
    featured: false,
    image: '/blog/productivity-tips.png',
    tagColor: TAG_COLORS.purple,
  },
  {
    slug: 'content-workflows-with-ai',
    category: 'Guide',
    filterCategory: 'Guides',
    date: 'Feb 15, 2026',
    readTime: '6 min read',
    title: 'Mastering Content Workflows with AI',
    excerpt:
      'Learn how to create, edit, and publish WordPress content using natural language commands.',
    author: { name: 'Marcus Webb', role: 'Developer Advocate' },
    featured: false,
    image: '/blog/content-workflows.png',
    tagColor: TAG_COLORS.blue,
  },
  {
    slug: 'rest-api-guide-2026',
    category: 'Tips',
    filterCategory: 'Tips',
    date: 'Feb 10, 2026',
    readTime: '10 min read',
    title: "WordPress REST API: A Developer's Guide",
    excerpt:
      'Deep dive into the WordPress REST API and how Wally leverages it for seamless site management.',
    author: { name: 'Priya Nair', role: 'Technical Writer' },
    featured: false,
    image: '/blog/rest-api-guide.png',
    tagColor: TAG_COLORS.amber,
  },
  {
    slug: 'whats-new-wally-1-0',
    category: 'Updates',
    filterCategory: 'Updates',
    date: 'Feb 5, 2026',
    readTime: '7 min read',
    title: "What's New in Wally 1.0: Full Feature Breakdown",
    excerpt:
      "A comprehensive look at every feature shipping in Wally's first major release.",
    author: { name: 'Sarah Chen', role: 'Head of Product' },
    featured: false,
    image: '/blog/whats-new.png',
    tagColor: TAG_COLORS.green,
  },
  {
    slug: 'managing-multiple-wordpress-sites',
    category: 'Productivity',
    filterCategory: 'Tips',
    date: 'Jan 28, 2026',
    readTime: '6 min read',
    title: 'Managing Multiple WordPress Sites with AI',
    excerpt:
      'How agency owners use Wally to handle dozens of client sites without breaking a sweat.',
    author: { name: 'Marcus Webb', role: 'Developer Advocate' },
    featured: false,
    image: '/blog/multiple-sites.png',
    tagColor: TAG_COLORS.purple,
  },
  {
    slug: 'plugin-management-natural-language',
    category: 'Guide',
    filterCategory: 'Guides',
    date: 'Jan 22, 2026',
    readTime: '4 min read',
    title: 'Plugin Management Made Simple with Natural Language',
    excerpt:
      'Install, update, and manage WordPress plugins just by asking. No more hunting through menus.',
    author: { name: 'Priya Nair', role: 'Technical Writer' },
    featured: false,
    image: '/blog/plugin-management.png',
    tagColor: TAG_COLORS.blue,
  },
];

// ─── Article Content ─────────────────────────────────────────────────────────
//
// Each key must match a slug from BLOG_POSTS above.
// Write content using standard JSX — it renders inside .prose-wally-dark.
//
// Available custom elements (styled via global.css):
//   <div className="tip-callout">   — purple-bordered tip box
//   <div className="code-block">    — dark code snippet container
//   <div className="numbered-list">  — numbered step list
//   <figure> + <figcaption>          — image with caption
//
// The first <p> should use className="intro" for the larger intro paragraph.

export const ARTICLE_DATA: Record<string, ArticleData> = {
  'getting-started-wally-plugin': {
    tags: ['WordPress', 'Setup Guide', 'AI'],
    content: (
      <>
        <p className="intro">
          Wally transforms how you interact with WordPress. Instead of clicking
          through menus, searching for settings, and memorizing where things live
          — you just ask. This guide walks you through everything: installation,
          first-time setup, and how to get the most out of your AI-powered
          assistant from day one.
        </p>

        <hr />

        <h2>1. Installation</h2>
        <p>
          Getting Wally onto your WordPress site takes less than two minutes.
          Head to your WordPress dashboard, navigate to Plugins → Add New, and
          search for &quot;Wally&quot;. Click Install Now, then Activate.
        </p>
        <p>
          Alternatively, if you have a .zip file from the Wally Pro download, go
          to Plugins → Add New → Upload Plugin, select the file, and activate
          it.
        </p>

        <div className="tip-callout">
          <p>
            Pro tip: Make sure your WordPress version is 6.0 or higher and PHP
            8.0+ is running on your server. Wally uses modern APIs that require
            these minimum versions.
          </p>
        </div>

        <h2>2. Initial Configuration</h2>
        <p>
          After activation, you&apos;ll see the Wally icon appear in your admin
          sidebar. Click it to open the chat panel for the first time.
          You&apos;ll be guided through a quick setup wizard that takes about 30
          seconds:
        </p>

        <div className="numbered-list">
          <div className="numbered-item">
            <span className="number">1</span>
            <p>
              Connect your license key — paste the key from your Wally account
              dashboard. Free users get a key too.
            </p>
          </div>
          <div className="numbered-item">
            <span className="number">2</span>
            <p>
              Choose your AI model — select between Claude or GPT depending on
              your preference and plan.
            </p>
          </div>
          <div className="numbered-item">
            <span className="number">3</span>
            <p>
              Site scan runs automatically — Wally scans your site to understand
              your theme, plugins, and content structure.
            </p>
          </div>
        </div>

        <h2>3. Your First Conversation</h2>
        <p>
          Once setup is complete, you&apos;re ready to start chatting. Wally
          understands natural language, so just type what you need. Here are some
          great first commands to try:
        </p>

        <div className="code-block">
          <code>&quot;Update the homepage title to Welcome to Our Store&quot;</code>
          <code>&quot;Install and activate the WooCommerce plugin&quot;</code>
          <code>&quot;Create a new draft blog post about summer sales&quot;</code>
        </div>

        <p>
          Wally will confirm what it understood, show you exactly what it&apos;s
          about to do, and ask for your approval before making any changes. For
          destructive actions like deleting posts or deactivating plugins,
          you&apos;ll always get a confirmation dialog first.
        </p>

        <figure>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src="/blog/getting-started-inline.png"
            alt="Wally's confirmation dialog"
          />
          <figcaption>
            Wally&apos;s confirmation dialog ensures you&apos;re always in
            control of changes.
          </figcaption>
        </figure>

        <h2>4. What&apos;s Next</h2>
        <p>
          You&apos;ve installed Wally, connected your license, and had your
          first conversation. From here, the possibilities expand rapidly. You
          can manage plugins, edit posts, update settings, handle media — all
          through natural language.
        </p>
        <p>
          Wally learns your site&apos;s structure over time through daily scans,
          so the more you use it, the more context-aware it becomes. And with the
          Pro plan, you unlock advanced features like bulk operations, scheduled
          tasks, and priority AI models.
        </p>
      </>
    ),
  },

  'top-productivity-tips-wp-admin': {
    tags: ['Productivity', 'WordPress', 'Tips'],
    content: (
      <>
        <p className="intro">
          If you&apos;ve ever spent twenty minutes doing something that should
          take two, you&apos;re not alone. WordPress admin is full of repetitive
          tasks that eat into your day. Here are the most impactful ways Wally
          saves you time.
        </p>

        <hr />

        <h2>Bulk-Updating Post Metadata</h2>
        <p>
          Need to add a category, update an author, or set a featured image
          across thirty posts? Instead of editing each post individually, just
          ask: &quot;Add the category &apos;Tutorials&apos; to all posts tagged
          &apos;beginner&apos;.&quot;
        </p>

        <h2>Running Search-and-Replace Across Content</h2>
        <p>
          Rebranding? Changed a product name? A single natural language command
          can update every instance across all your posts and pages in seconds —
          safely, with a preview before committing.
        </p>

        <h2>Scheduling Content in Bulk</h2>
        <p>
          You&apos;ve drafted five posts. Instead of opening each one and
          setting a publish date, tell the AI: &quot;Schedule these five posts to
          publish every Tuesday at 9am starting next week.&quot;
        </p>
      </>
    ),
  },

  'content-workflows-with-ai': {
    tags: ['Content', 'AI', 'Workflow'],
    content: (
      <>
        <p className="intro">
          Publishing content consistently is one of the hardest habits to
          maintain. Most teams have good intentions but fragile workflows. AI can
          fix that.
        </p>

        <hr />

        <h2>The Anatomy of a Content Workflow</h2>
        <p>
          A repeatable content workflow has four stages: idea → draft → review →
          publish. The problem is that each transition is a friction point.
        </p>

        <h2>Drafting at Scale</h2>
        <p>
          Instead of staring at a blank editor, use Wally to generate first
          drafts from a brief. You&apos;re not publishing the draft as-is —
          you&apos;re eliminating the hardest part of writing: starting.
        </p>

        <h2>Scheduling Without the Admin Overhead</h2>
        <p>
          Once your drafts are ready, scheduling is a single command:
          &quot;Schedule these four posts to publish every Monday at 8am,
          starting next week.&quot; No opening each post. No setting dates one
          by one. Done.
        </p>
      </>
    ),
  },

  'rest-api-guide-2026': {
    tags: ['REST API', 'Development', 'WordPress'],
    content: (
      <>
        <p className="intro">
          WordPress&apos;s REST API has matured into one of the most powerful
          tools in the ecosystem. Whether you&apos;re building a headless
          frontend or extending WordPress with custom functionality, the REST API
          is your foundation.
        </p>

        <hr />

        <h2>Why the REST API Matters in 2026</h2>
        <p>
          The REST API enables WordPress to function as a headless CMS, powering
          React, Next.js, and mobile applications. Tools like Wally use it
          internally to execute site management tasks through natural language.
        </p>

        <h2>Authentication Methods</h2>
        <p>
          WordPress supports several authentication methods for the REST API:
          application passwords (built-in since 5.6), JWT tokens via plugins,
          and OAuth 2.0 for third-party integrations.
        </p>

        <h2>Building Custom Endpoints</h2>
        <p>
          Custom endpoints let you extend the REST API with your own business
          logic. Register them with register_rest_route() in your plugin or
          theme, define permission callbacks, and return structured JSON
          responses.
        </p>
      </>
    ),
  },

  'whats-new-wally-1-0': {
    tags: ['Product', 'Updates', 'Release'],
    content: (
      <>
        <p className="intro">
          Wally 1.0 is here. After months of development and community feedback,
          we&apos;re shipping the most comprehensive AI-powered WordPress
          management tool available.
        </p>

        <hr />

        <h2>Natural Language Site Management</h2>
        <p>
          Manage posts, pages, plugins, themes, settings, and media through
          conversational AI. Wally understands context and remembers your site
          structure.
        </p>

        <h2>Smart Confirmation System</h2>
        <p>
          Every destructive action goes through a confirmation dialog. Wally
          shows you exactly what will change before executing, so you always stay
          in control.
        </p>

        <h2>Multi-Model Support</h2>
        <p>
          Choose between Claude and GPT models depending on your preference. Pro
          users get access to the latest and most capable models with priority
          processing.
        </p>
      </>
    ),
  },

  'managing-multiple-wordpress-sites': {
    tags: ['Agency', 'Multi-site', 'Productivity'],
    content: (
      <>
        <p className="intro">
          Agency owners know the pain of managing dozens of WordPress sites. Each
          client has different plugins, themes, and configurations. Wally makes
          this manageable.
        </p>

        <hr />

        <h2>One Interface for All Sites</h2>
        <p>
          Instead of logging into each WordPress admin separately, Wally gives
          you a unified interface. Just tell it which site you&apos;re working on
          and what you need done.
        </p>

        <h2>Batch Operations Across Sites</h2>
        <p>
          Need to update a plugin across all client sites? Or change a setting on
          every installation? Wally handles batch operations that would otherwise
          take hours of manual work.
        </p>

        <h2>Client-Safe Audit Logs</h2>
        <p>
          Every action Wally takes is logged with timestamps and details. Share
          audit reports with clients to demonstrate exactly what maintenance was
          performed.
        </p>
      </>
    ),
  },

  'plugin-management-natural-language': {
    tags: ['Plugins', 'Natural Language', 'Management'],
    content: (
      <>
        <p className="intro">
          WordPress plugin management shouldn&apos;t require clicking through
          multiple screens. With Wally, you can install, update, activate,
          deactivate, and remove plugins using plain English.
        </p>

        <hr />

        <h2>Installing Plugins by Name</h2>
        <p>
          Just say &quot;Install WooCommerce&quot; or &quot;Add the Yoast SEO
          plugin.&quot; Wally searches the WordPress plugin repository, finds the
          right plugin, and handles installation.
        </p>

        <h2>Checking for Updates</h2>
        <p>
          Ask Wally &quot;Which plugins need updating?&quot; and get an instant
          overview. Then say &quot;Update all plugins&quot; or &quot;Update only
          Elementor&quot; for precise control.
        </p>

        <h2>Troubleshooting Conflicts</h2>
        <p>
          When plugins conflict, Wally can help identify the issue. Ask
          &quot;Which plugins were recently updated?&quot; or &quot;Deactivate
          the last installed plugin&quot; to quickly isolate problems.
        </p>
      </>
    ),
  },
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

export function getPostBySlug(slug: string): BlogPost | undefined {
  return BLOG_POSTS.find((p) => p.slug === slug);
}

export function getRelatedPosts(slug: string, count = 3): BlogPost[] {
  const post = getPostBySlug(slug);
  if (!post) return BLOG_POSTS.filter((p) => !p.featured).slice(0, count);

  return BLOG_POSTS
    .filter((p) => p.slug !== slug && !p.featured)
    .sort((a, b) => {
      const aMatch = a.filterCategory === post.filterCategory ? 1 : 0;
      const bMatch = b.filterCategory === post.filterCategory ? 1 : 0;
      return bMatch - aMatch;
    })
    .slice(0, count);
}
