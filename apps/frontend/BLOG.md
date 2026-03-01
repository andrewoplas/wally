# Blog — Adding & Editing Posts

All blog data lives in a single file:

```
apps/frontend/src/lib/blog-data.tsx
```

No CMS, no database, no markdown pipeline. You edit one `.tsx` file and deploy.

---

## Adding a New Post

### 1. Add a card entry to `BLOG_POSTS`

Open `blog-data.tsx` and add a new object to the `BLOG_POSTS` array. Newest posts go **first** (the array is ordered newest → oldest).

```tsx
{
  slug: 'your-post-slug',            // URL: /blog/your-post-slug
  category: 'Productivity',          // Display label on the tag pill
  filterCategory: 'Tips',            // Must match a CATEGORIES value (see below)
  date: 'Mar 1, 2026',              // Display date (not parsed, just a string)
  readTime: '5 min read',           // Estimated read time
  title: 'Your Post Title',
  excerpt: 'A one-sentence summary shown on cards and in meta descriptions.',
  author: { name: 'Author Name', role: 'Role Title' },
  featured: false,                   // Only ONE post should be true (the hero card)
  image: '/blog/your-image.png',     // Place image in apps/frontend/public/blog/
  tagColor: TAG_COLORS.purple,       // Pick from TAG_COLORS (see below)
},
```

### 2. Add article content to `ARTICLE_DATA`

Below the posts array, add a matching entry keyed by the same slug:

```tsx
'your-post-slug': {
  tags: ['WordPress', 'Productivity', 'AI'],  // Shown at the bottom of the article
  content: (
    <>
      <p className="intro">
        The opening paragraph — displayed larger and lighter than body text.
      </p>

      <hr />

      <h2>Section Title</h2>
      <p>Body paragraph text goes here.</p>

      <h2>Another Section</h2>
      <p>More content...</p>
    </>
  ),
},
```

### 3. Add the cover image

Place the image at `apps/frontend/public/blog/your-image.png`. Recommended: **1200x630px** (works for both the card thumbnail and Open Graph).

### 4. That's it

The post automatically appears on `/blog`, gets its own page at `/blog/your-post-slug`, shows up in the sitemap, and gets full SEO metadata.

---

## Reference

### Filter Categories

The filter pills on `/blog` use `filterCategory`. It must be one of:

| `filterCategory` | Pill label |
|-------------------|------------|
| `'Guides'`        | Guides     |
| `'Tips'`          | Tips       |
| `'Updates'`       | Updates    |

The `category` field is separate — it's the display label on the tag pill (e.g. "Getting Started", "Productivity", "Guide"). It can be anything.

### Tag Colors

Use the `TAG_COLORS` constant instead of raw hex values:

| Key      | Pill appearance         |
|----------|-------------------------|
| `purple` | Purple text, light purple bg |
| `blue`   | Blue text, light blue bg     |
| `amber`  | Amber text, light yellow bg  |
| `green`  | Green text, light green bg   |
| `rose`   | Rose text, light pink bg     |

Usage: `tagColor: TAG_COLORS.purple`

### Content Elements

Inside `ARTICLE_DATA[slug].content`, you can use these elements. They are styled by the `.prose-wally-dark` class in `global.css`.

**Intro paragraph** — larger, lighter text for the opening:
```tsx
<p className="intro">Opening paragraph text.</p>
```

**Section divider:**
```tsx
<hr />
```

**Headings:**
```tsx
<h2>Section Title</h2>
```

**Body paragraphs:**
```tsx
<p>Regular paragraph text.</p>
```

**Tip callout** — purple-bordered box:
```tsx
<div className="tip-callout">
  <p>Pro tip: helpful information here.</p>
</div>
```

**Code block** — dark box with monospace text:
```tsx
<div className="code-block">
  <code>"First command example"</code>
  <code>"Second command example"</code>
</div>
```

**Numbered steps:**
```tsx
<div className="numbered-list">
  <div className="numbered-item">
    <span className="number">1</span>
    <p>First step description.</p>
  </div>
  <div className="numbered-item">
    <span className="number">2</span>
    <p>Second step description.</p>
  </div>
</div>
```

**Image with caption:**
```tsx
<figure>
  <img src="/blog/your-image.png" alt="Description" />
  <figcaption>Caption text below the image.</figcaption>
</figure>
```

### Changing the Featured Post

Set `featured: true` on the post you want in the hero card, and `featured: false` on all others. Only one post should be featured.

### File Structure

```
apps/frontend/
├── src/
│   ├── lib/
│   │   └── blog-data.tsx          ← All post data + article content
│   ├── app/
│   │   ├── blog/
│   │   │   ├── page.tsx           ← Listing page (server component)
│   │   │   └── [slug]/page.tsx    ← Post page (server component)
│   │   ├── sitemap.ts             ← Auto-includes all blog posts
│   │   └── robots.ts
│   └── components/blog/
│       ├── blog-grid.tsx          ← Filter pills + card grid (client)
│       └── newsletter-cta.tsx     ← Newsletter section (client)
└── public/blog/                   ← Blog images
```
