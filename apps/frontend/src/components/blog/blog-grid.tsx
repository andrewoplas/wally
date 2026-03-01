'use client';

import { useState } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { Search } from 'lucide-react';
import { cn } from '@/lib/utils';
import { CATEGORIES, type BlogPost, type Category } from '@/lib/blog-data';

function BlogCard({ post }: { post: BlogPost }) {
  return (
    <Link
      href={`/blog/${post.slug}`}
      className="group flex flex-col overflow-hidden rounded-[16px] border border-[#E4E4E7] bg-white transition-all hover:-translate-y-1 hover:shadow-[0_12px_32px_rgba(0,0,0,0.08)]"
      style={{ boxShadow: '0 4px 16px -4px rgba(0,0,0,0.04)' }}
    >
      <div className="relative h-[200px] w-full overflow-hidden">
        <Image
          src={post.image}
          alt={post.title}
          fill
          className="object-cover transition-transform duration-500 group-hover:scale-105"
        />
      </div>

      <div className="flex flex-1 flex-col gap-4 p-6 pb-7">
        <span
          className="inline-flex w-fit rounded-full px-3 py-1 text-[12px] font-semibold"
          style={{ backgroundColor: post.tagColor.bg, color: post.tagColor.text }}
        >
          {post.category}
        </span>

        <h3 className="font-heading text-[20px] font-bold leading-[1.3] text-[#18181B] transition-colors group-hover:text-primary">
          {post.title}
        </h3>

        <p className="flex-1 text-[14px] leading-[1.6] text-[#71717A] line-clamp-3">
          {post.excerpt}
        </p>

        <div className="flex items-center gap-3 pt-1 text-[13px] text-[#A1A1AA]">
          <span>{post.date}</span>
          <span className="inline-block h-[3px] w-[3px] rounded-full bg-[#D4D4D8]" />
          <span>{post.readTime}</span>
        </div>
      </div>
    </Link>
  );
}

interface BlogGridProps {
  posts: BlogPost[];
}

export function BlogGrid({ posts }: BlogGridProps) {
  const [activeCategory, setActiveCategory] = useState<Category>('All');

  const filtered =
    activeCategory === 'All'
      ? posts
      : posts.filter((p) => p.filterCategory === activeCategory);

  return (
    <section className="bg-white px-6 py-16 md:px-20 md:py-20 lg:px-[120px] lg:py-[80px]">
      <div className="mx-auto max-w-7xl">
        {/* Header row */}
        <div className="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
          <h2 className="font-heading text-2xl font-bold text-[#18181B] md:text-[32px]">
            Latest Articles
          </h2>

          <div className="flex flex-wrap items-center gap-2">
            {CATEGORIES.map((cat) => (
              <button
                key={cat}
                onClick={() => setActiveCategory(cat)}
                className={cn(
                  'rounded-full px-5 py-2 text-[14px] font-medium transition-colors',
                  activeCategory === cat
                    ? 'bg-[#18181B] text-white'
                    : 'bg-[#F4F4F5] text-[#52525B] hover:bg-[#E4E4E7]'
                )}
              >
                {cat}
              </button>
            ))}
          </div>
        </div>

        {/* Grid */}
        {filtered.length > 0 ? (
          <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {filtered.map((post) => (
              <BlogCard key={post.slug} post={post} />
            ))}
          </div>
        ) : (
          <div className="flex flex-col items-center gap-4 py-20 text-center">
            <div className="flex h-14 w-14 items-center justify-center rounded-full bg-[#F4F4F5]">
              <Search className="h-6 w-6 text-[#A1A1AA]" />
            </div>
            <p className="font-heading text-lg font-semibold text-[#18181B]">
              No articles found
            </p>
            <p className="text-sm text-[#A1A1AA]">
              Try a different category.
            </p>
            <button
              onClick={() => setActiveCategory('All')}
              className="mt-2 rounded-full bg-[#18181B] px-6 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#333]"
            >
              Clear filters
            </button>
          </div>
        )}
      </div>
    </section>
  );
}
