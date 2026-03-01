# Wally Launch Checklist

## Frontend Post-Launch Setup

### SEO
- [x] Sitemap at `/sitemap.xml` (auto-generated via `src/app/sitemap.ts`)
- [x] Robots.txt at `/robots.txt` — `/app/` disallowed
- [x] Favicon icons in metadata (`layout.tsx`)
- [x] Open Graph / Twitter card metadata in `layout.tsx` (default) and `page.tsx` (homepage)
- [x] JSON-LD structured data on homepage (WebSite + Organization schema)
- [x] App routes noindexed via `(app)/layout.tsx`
- [x] Vercel Analytics added to `layout.tsx`
- [ ] Add `NEXT_PUBLIC_SITE_URL=https://www.wallychat.com` to production environment variables
- [ ] Create and upload OG image (`/public/site-og.png`, 1200×630px)
- [ ] Add favicon files to `/public/` — `favicon.ico`, `favicon.svg`, `apple-icon.png`
- [ ] Add per-page metadata overrides for blog posts

### Google Search Console
- [ ] Add property for `wallychat.com`
- [ ] Verify ownership via DNS TXT record
- [ ] Submit sitemap: `https://www.wallychat.com/sitemap.xml`
- [ ] Check for crawl errors after 24–48 hours

### Google Analytics (GA4)
- [ ] Create GA4 property at analytics.google.com
- [ ] Copy Measurement ID (`G-XXXXXXXXXX`)
- [ ] Add `NEXT_PUBLIC_GA_MEASUREMENT_ID` to production env vars
- [ ] Implement GA4 script in `layout.tsx` (code change)

### Google Tag Manager (optional, replaces direct GA4)
- [ ] Create GTM account + container
- [ ] Copy GTM ID (`GTM-XXXXXXX`)
- [ ] Add GTM snippet to `layout.tsx` (code change)
- [ ] Inside GTM: add GA4 tag pointing to Measurement ID
- [ ] Publish the GTM container

### PostHog
- [ ] Create project at posthog.com
- [ ] Add `NEXT_PUBLIC_POSTHOG_KEY` and `NEXT_PUBLIC_POSTHOG_HOST` to production env vars
- [ ] Install `posthog-js` and implement provider in `layout.tsx` (code change)
- [ ] Set up funnel: Landing → Register → First license activated

### Sentry (Error Monitoring)
- [ ] Create Next.js project at sentry.io
- [ ] Add `NEXT_PUBLIC_SENTRY_DSN` to production env vars
- [ ] Install `@sentry/nextjs` and run wizard (code change)

### Uptime Monitoring
- [ ] Set up monitor at betteruptime.com or uptimerobot.com
- [ ] Monitor `https://www.wallychat.com`
- [ ] Configure email/Slack alerts for downtime

---

## WordPress Plugin — wordpress.org Submission

### Plugin Header (`wally.php`) — Fix Before Submission
- [ ] Update `Plugin URI` from placeholder to `https://www.wallychat.com`
- [ ] Update `Author` from "Your Name" to real name
- [ ] Update `Author URI` from placeholder to real URL

### Assets Required
- [ ] Plugin icon: `assets/icon-128x128.png` and `assets/icon-256x256.png`
- [ ] Banner: `assets/banner-772x250.png` and `assets/banner-1544x500.png` (retina)
- [ ] Screenshots: `assets/screenshot-1.png`, `screenshot-2.png`, etc.

### SVN Repository (wordpress.org uses SVN)
- [ ] Submit plugin at wordpress.org/plugins/developers/add/
- [ ] Wait for review (can take 1–4 weeks)
- [ ] Once approved, set up SVN:
  - `trunk/` — current plugin code
  - `tags/0.1.0/` — versioned release
  - `assets/` — icons, banners, screenshots

### readme.txt (Required by wordpress.org)
- [ ] Create `readme.txt` in plugin root with:
  - `=== Wally ===` header
  - Contributors, Tags, Requires at least, Tested up to, Stable tag, License
  - Short description (150 chars max)
  - Long description (sell the plugin)
  - Installation instructions
  - FAQ section
  - Changelog section
  - Screenshots descriptions matching `screenshot-N.png` files

### Pre-Submission Checklist
- [ ] Run Plugin Check plugin (wordpress.org/plugins/plugin-check/) on your site
- [ ] Ensure external API calls are disclosed in readme.txt
- [ ] Ensure plugin deactivation doesn't delete user data
- [ ] All strings translatable via `__()`, `_e()`, `esc_html__()`, etc.
- [ ] All inputs sanitized, all outputs escaped
- [ ] No GPL-incompatible licenses in dependencies

---

## Business / Legal
- [ ] Privacy policy live at `wallychat.com/privacy`
- [ ] Terms of service live at `wallychat.com/terms`
- [ ] Stripe/payment setup tested in production (not test mode)
- [ ] Transactional emails working (license key delivery, welcome email)
- [ ] Support channel set up (email, Discord, etc.)
