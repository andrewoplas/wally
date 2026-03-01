'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import { Clock, Search } from 'lucide-react';
import Image from 'next/image';
import {
  AnimatedSection,
  StaggerContainer,
  StaggerItem,
} from '@/components/landing/shared/animated-section';
import { Container } from '@/components/landing/shared/container';
import { Navbar } from '@/components/landing/navbar';
import { Footer } from '@/components/landing/footer';
import { cn } from '@/lib/utils';


const CATEGORIES = [
  'All Posts',
  'AI & Automation',
  'WordPress',
  'Productivity',
  'Tutorials',
];

export interface BlogPost {
  slug: string;
  category: string;
  date: string;
  readTime: string;
  title: string;
  excerpt: string;
  author: { name: string; role: string };
  featured: boolean;
  image: string;
  tagColor: string;
}

export const BLOG_POSTS: BlogPost[] = [
  {
    slug: 'ai-powered-wordpress-management',
    category: 'AI & Automation',
    date: 'Feb 28, 2026',
    readTime: '8 min read',
    title: 'How AI Is Rewriting the Rules of WordPress Management',
    excerpt:
      "Discover how Wally's AI assistant transforms complex site management tasks into simple conversations — no plugin menus, no documentation hunting.",
    author: { name: 'Sarah Chen', role: 'Head of Product' },
    featured: true,
    image: '/blog/ai-wordpress-management.png',
    tagColor: 'bg-[#A78BFA] text-white',
  },
  {
    slug: 'top-productivity-tips-wp-admin',
    category: 'WordPress',
    date: 'Feb 20, 2026',
    readTime: '4 min read',
    title:
      '5 WordPress Tasks You Can Now Do with a Single Chat Message',
    excerpt:
      'From updating plugins to creating posts, Wally handles your entire WordPress workflow through natural language commands.',
    author: { name: 'Marcus Webb', role: 'Developer Advocate' },
    featured: false,
    image: '/blog/productivity-tips.png',
    tagColor: 'bg-[#F0F0FF] text-[#6D28D9]',
  },
  {
    slug: 'rest-api-guide-2026',
    category: 'Tutorials',
    date: 'Feb 14, 2026',
    readTime: '10 min read',
    title: 'The Complete Guide to WordPress REST API in 2026',
    excerpt:
      "Everything you need to know about building on WordPress's REST API — from authentication to custom endpoints and beyond.",
    author: { name: 'Priya Nair', role: 'Technical Writer' },
    featured: false,
    image: '/blog/rest-api-guide.png',
    tagColor: 'bg-[#FFF0E0] text-[#B45309]',
  },
  {
    slug: 'content-workflows-with-ai',
    category: 'Productivity',
    date: 'Feb 6, 2026',
    readTime: '5 min read',
    title:
      'Building a Faster Content Workflow with AI Assistants',
    excerpt:
      "Stop copy-pasting between tools. Here's how modern content teams are using AI to publish 3x faster without sacrificing quality.",
    author: { name: 'Sarah Chen', role: 'Head of Product' },
    featured: false,
    image: '/blog/content-workflows.png',
    tagColor: 'bg-[#DCFCE7] text-[#15803D]',
  },
  {
    slug: 'getting-started-wally-plugin',
    category: 'Tutorials',
    date: 'Jan 28, 2026',
    readTime: '8 min read',
    title: 'Getting Started with Wally: Your First 10 Minutes',
    excerpt:
      'A step-by-step walkthrough for installing the Wally plugin, connecting your WordPress site, and making your first AI-powered site change.',
    author: { name: 'Priya Nair', role: 'Technical Writer' },
    featured: false,
    image: '/blog/getting-started.png',
    tagColor: 'bg-[#F0F0FF] text-[#6D28D9]',
  },
];

function AuthorAvatar({ name, size = 36 }: { name: string; size?: number }) {
  const initials = name
    .split(' ')
    .map((n) => n[0])
    .join('');
  return (
    <div
      className="flex flex-shrink-0 items-center justify-center rounded-full bg-[#E5E5E5] text-[12px] font-bold text-[#888]"
      style={{ width: size, height: size }}
    >
      {initials}
    </div>
  );
}

function FeaturedCard({ post }: { post: BlogPost }) {
  return (
    <AnimatedSection>
      <a
        href={`/blog/${post.slug}`}
        className="group flex flex-col overflow-hidden bg-white lg:flex-row"
      >
        {/* Content */}
        <div className="flex flex-1 flex-col gap-6 py-12 pr-14 lg:py-12">
          <div className="flex items-center gap-3">
            <span
              className={cn(
                'inline-flex items-center rounded px-2.5 py-1 text-[11px] font-semibold',
                post.tagColor
              )}
            >
              {post.category}
            </span>
            <span className="text-[11px] font-semibold uppercase tracking-[2px] text-[#888]">
              Featured
            </span>
          </div>

          <h2 className="font-heading text-3xl font-extrabold leading-[1.1] text-[#0A0A0A] transition-colors group-hover:text-primary lg:text-4xl">
            {post.title}
          </h2>

          <p className="max-w-[520px] text-[15px] leading-relaxed text-[#666]">
            {post.excerpt}
          </p>

          <div className="flex items-center gap-4">
            <AuthorAvatar name={post.author.name} />
            <div>
              <p className="text-[13px] font-semibold text-[#1A1A1A]">
                {post.author.name}
              </p>
              <p className="text-[12px] text-[#888]">
                {post.date} &middot; {post.readTime}
              </p>
            </div>
          </div>

          <div>
            <span className="inline-flex items-center rounded-pill bg-[#F5F5F5] px-4 py-2.5 text-[14px] font-medium text-[#0A0A0A] transition-colors group-hover:bg-primary/10 group-hover:text-primary">
              Read Article &rarr;
            </span>
          </div>
        </div>

        {/* Image */}
        <div className="relative h-64 w-full flex-shrink-0 overflow-hidden rounded-xl lg:h-auto lg:w-[540px]">
          <Image
            src={post.image}
            alt={post.title}
            fill
            className="object-cover transition-transform duration-500 group-hover:scale-105"
          />
        </div>
      </a>
    </AnimatedSection>
  );
}

function PostCard({ post }: { post: BlogPost }) {
  return (
    <StaggerItem>
      <a
        href={`/blog/${post.slug}`}
        className="group flex flex-col overflow-hidden rounded-xl border border-[#E5E5E5] bg-white transition-all hover:-translate-y-1 hover:shadow-[0_8px_32px_rgba(0,0,0,0.08)]"
      >
        {/* Image */}
        <div className="relative h-[220px] w-full overflow-hidden">
          <Image
            src={post.image}
            alt={post.title}
            fill
            className="object-cover transition-transform duration-500 group-hover:scale-105"
          />
        </div>

        {/* Content */}
        <div className="flex flex-1 flex-col gap-4 p-6">
          <span
            className={cn(
              'inline-flex w-fit items-center rounded px-2.5 py-1 text-[11px] font-semibold',
              post.tagColor
            )}
          >
            {post.category}
          </span>

          <h3 className="font-heading text-lg font-bold leading-[1.2] text-[#0A0A0A] transition-colors group-hover:text-primary">
            {post.title}
          </h3>

          <p className="flex-1 text-[13px] leading-relaxed text-[#666] line-clamp-3">
            {post.excerpt}
          </p>

          {/* Meta */}
          <div className="flex items-center justify-between pt-2">
            <div className="flex items-center gap-2.5">
              <AuthorAvatar name={post.author.name} size={28} />
              <span className="text-[13px] font-medium text-[#1A1A1A]">
                {post.author.name}
              </span>
            </div>
            <span className="flex items-center gap-1 text-[12px] text-[#888]">
              <Clock className="h-3 w-3" />
              {post.readTime}
            </span>
          </div>
        </div>
      </a>
    </StaggerItem>
  );
}

export default function BlogPage() {
  const [activeCategory, setActiveCategory] = useState('All Posts');
  const [searchQuery, setSearchQuery] = useState('');

  const featured = BLOG_POSTS.find((p) => p.featured)!;
  const rest = BLOG_POSTS.filter((p) => !p.featured);

  const filtered = rest.filter((p) => {
    const matchesCategory =
      activeCategory === 'All Posts' || p.category === activeCategory;
    const matchesSearch =
      !searchQuery ||
      p.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      p.excerpt.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  return (
    <div className="min-h-screen bg-white">
      <Navbar />

      {/* Editorial Hero */}
      <section className="bg-white px-6 pt-28 pb-12 lg:px-20">
        <Container>
          <div className="flex flex-col gap-8">
            <motion.p
              className="text-[11px] font-semibold uppercase tracking-[2px] text-[#888]"
              initial={{ opacity: 0, y: 12 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: 0.1 }}
            >
              The Wally Blog
            </motion.p>

            <motion.h1
              className="max-w-[900px] font-heading text-5xl font-extrabold leading-[0.95] text-[#0A0A0A] md:text-7xl lg:text-[80px]"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, delay: 0.2 }}
            >
              Ideas, insights &amp;
              <br />
              automation for
              <br />
              WordPress teams.
            </motion.h1>

            {/* Divider */}
            <motion.div
              className="h-[2px] w-full bg-[#0A0A0A]"
              initial={{ scaleX: 0, originX: 0 }}
              animate={{ scaleX: 1 }}
              transition={{ duration: 0.6, delay: 0.4 }}
            />

            {/* Filter pills */}
            <motion.div
              className="flex flex-wrap items-center gap-3"
              initial={{ opacity: 0, y: 12 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: 0.5 }}
            >
              {CATEGORIES.map((cat) => (
                <button
                  key={cat}
                  onClick={() => setActiveCategory(cat)}
                  className={cn(
                    'rounded-pill px-5 py-2 text-[13px] font-medium transition-colors',
                    activeCategory === cat
                      ? 'bg-[#0A0A0A] text-white'
                      : 'border border-[#E5E5E5] bg-white text-[#666] hover:border-[#999] hover:text-[#333]'
                  )}
                >
                  {cat}
                </button>
              ))}
            </motion.div>
          </div>
        </Container>
      </section>

      {/* Featured post */}
      {activeCategory === 'All Posts' && !searchQuery && (
        <section className="bg-white px-6 pb-16 lg:px-20">
          <Container>
            <FeaturedCard post={featured} />
          </Container>
        </section>
      )}

      {/* Article Grid */}
      <section className="bg-white px-6 pb-20 lg:px-20">
        <Container>
          <div className="flex flex-col gap-8">
            <div className="flex items-center justify-between">
              <AnimatedSection>
                <p className="text-[11px] font-semibold uppercase tracking-[2px] text-[#888]">
                  {activeCategory === 'All Posts' && !searchQuery
                    ? 'Latest Articles'
                    : `${filtered.length} article${filtered.length !== 1 ? 's' : ''} found`}
                </p>
              </AnimatedSection>
            </div>

            <div className="h-px w-full bg-[#E5E5E5]" />

            {filtered.length > 0 ? (
              <StaggerContainer
                className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3"
                staggerDelay={0.08}
              >
                {filtered.map((post) => (
                  <PostCard key={post.slug} post={post} />
                ))}
              </StaggerContainer>
            ) : (
              <AnimatedSection className="flex flex-col items-center gap-4 py-20 text-center">
                <div className="flex h-14 w-14 items-center justify-center rounded-full bg-[#F5F5F5]">
                  <Search className="h-6 w-6 text-[#888]" />
                </div>
                <p className="font-heading text-lg font-semibold text-[#0A0A0A]">
                  No articles found
                </p>
                <p className="text-sm text-[#888]">
                  Try a different keyword or category.
                </p>
                <button
                  onClick={() => {
                    setActiveCategory('All Posts');
                    setSearchQuery('');
                  }}
                  className="mt-2 rounded-pill bg-[#0A0A0A] px-6 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#333]"
                >
                  Clear filters
                </button>
              </AnimatedSection>
            )}
          </div>
        </Container>
      </section>

      <Footer />
    </div>
  );
}
