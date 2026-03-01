import type { MetadataRoute } from 'next';

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      { userAgent: '*', allow: '/' },
      { userAgent: '*', disallow: '/app/' },
    ],
    sitemap: `${process.env.NEXT_PUBLIC_SITE_URL ?? 'https://www.wallychat.com'}/sitemap.xml`,
  };
}
