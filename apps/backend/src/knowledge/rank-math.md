## Rank Math SEO Plugin

### Meta Data Storage
Rank Math stores SEO data as postmeta with prefix `rank_math_`. Key meta keys:
- `rank_math_title` — custom SEO title (supports variables like `%title% %sep% %sitename%`)
- `rank_math_description` — custom meta description
- `rank_math_focus_keyword` — primary focus keyword (comma-separated for multiple)
- `rank_math_canonical_url` — canonical URL override
- `rank_math_robots` — array of robots directives (index, noindex, nofollow, etc.)
- `rank_math_facebook_title` — Open Graph title
- `rank_math_facebook_description` — Open Graph description
- `rank_math_facebook_image` — Open Graph image URL
- `rank_math_twitter_title` — Twitter/X card title
- `rank_math_twitter_use_facebook` — "on" to reuse Facebook OG data for Twitter

### Reading/Writing SEO Data
```php
// Read
get_post_meta($post_id, 'rank_math_title', true);
get_post_meta($post_id, 'rank_math_description', true);
get_post_meta($post_id, 'rank_math_focus_keyword', true);
// Write
update_post_meta($post_id, 'rank_math_title', 'New SEO Title');
update_post_meta($post_id, 'rank_math_description', 'New description');
update_post_meta($post_id, 'rank_math_robots', ['index', 'follow']);
```

### SEO Score
- `rank_math_seo_score` — numeric SEO score (0-100) stored as postmeta

### Important Filters
- `rank_math/frontend/title` — filter the title tag output
- `rank_math/frontend/description` — filter the meta description
- `rank_math/frontend/keywords` — filter meta keywords (defaults to focus keywords)
- `rank_math/frontend/robots` — filter robots meta array (e.g., add 'noindex')
- `rank_math/json_ld` — modify the full JSON-LD structured data output
- `rank_math/sitemap/entry` — filter individual sitemap entries
- `rank_math/settings/snippet/type` — set default schema type per post type

### Settings in wp_options
- `rank-math-options-general` — general plugin settings and module toggles
- `rank-math-options-titles` — title/description templates, robots defaults per post type
- `rank-math-options-sitemap` — sitemap configuration

### Modules
Rank Math uses a modular architecture. Key modules: Analytics, SEO Analysis, Sitemap, Schema (Rich Snippets), Redirections, 404 Monitor, Local SEO, WooCommerce, Instant Indexing.

### Schema / Rich Snippets
Built-in schema types: Article, Product, FAQ, HowTo, Recipe, Event, Course, Book, Music, Video, Person, Review, Service, Software, Local Business. Customize via `rank_math/json_ld` filter or `rank_math/snippet/rich_snippet_{type}_entity` filters.

### Template Variables
Use in title/description templates: `%title%`, `%excerpt%`, `%seo_title%`, `%seo_description%`, `%url%`, `%date%`, `%modified%`, `%category%`, `%tag%`, `%post_thumbnail%`, `%sitename%`, `%sep%`

### Redirections
Rank Math has a built-in redirection manager. Redirections stored in `{prefix}rank_math_redirections` table. Types: 301 (permanent), 302 (temporary), 307, 410 (gone), 451.

### 404 Monitor
Tracks 404 errors in `{prefix}rank_math_404_log` table. Can auto-create redirections from detected 404s.

### Detecting Rank Math
```php
defined('RANK_MATH_VERSION') // true if Rank Math is active
class_exists('RankMath') // alternative check
```

### Key Differences from Yoast
- Meta prefix: `rank_math_` vs `_yoast_wpseo_`
- Title variables: `%title%` vs `%%title%%` (single vs double percent)
- Built-in redirections and 404 monitor (Yoast requires separate plugin)
- Modular architecture with toggleable features
- Multiple focus keywords in free version

---

## Rank Math SEO Pro

**Plugin slug**: `seo-by-rank-math-pro/rank-math-pro.php`. Extends the free Rank Math SEO plugin with advanced features.

### Pro Features
- **Advanced Schema Generator** — custom schema types beyond the built-in set, with full JSON-LD editing.
- **News Sitemap** — Google News compatible sitemap for news publishers.
- **Video Sitemap** — dedicated video sitemap for video-rich content.
- **Local SEO Module** — supports multiple business locations with individual schema markup.
- **WooCommerce SEO** — advanced product schema, category optimization, and product filter SEO.
- **Google Analytics & Search Console Integration** — view analytics data directly in the WordPress dashboard.
- **Content AI Credits** — AI-powered content suggestions and optimization recommendations.
- **Keyword Tracking** — track keyword rankings over time.

### Settings Storage
Pro settings are stored in the same `rank-math-options-*` options as the free version with additional keys. License and connection data is stored in the `rank_math_connect_data` option.

### Detecting Rank Math SEO Pro
```php
defined('RANK_MATH_PRO_FILE') // true if Rank Math Pro is active
class_exists('RankMathPro\\Plugin') // alternative check
```
