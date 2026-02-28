## Yoast SEO Plugin

### Meta Data Storage
Yoast stores SEO data as postmeta with prefix `_yoast_wpseo_`. Key meta keys:
- `_yoast_wpseo_title` — custom SEO title (supports variables like `%%title%% %%sep%% %%sitename%%`)
- `_yoast_wpseo_metadesc` — custom meta description
- `_yoast_wpseo_focuskw` — primary focus keyword
- `_yoast_wpseo_canonical` — canonical URL override
- `_yoast_wpseo_opengraph-title` — Open Graph title
- `_yoast_wpseo_opengraph-description` — Open Graph description
- `_yoast_wpseo_opengraph-image` — Open Graph image URL
- `_yoast_wpseo_twitter-title` — Twitter/X card title
- `_yoast_wpseo_twitter-description` — Twitter/X card description

### Reading/Writing SEO Data
```php
// Read
get_post_meta($post_id, '_yoast_wpseo_title', true);
get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
// Write
update_post_meta($post_id, '_yoast_wpseo_title', 'New SEO Title');
update_post_meta($post_id, '_yoast_wpseo_metadesc', 'New description');
// Canonical via API
YoastSEO()->meta->for_current_page()->canonical;
```

### Content Analysis Scores
- `_yoast_wpseo_linkdex` — SEO score (0-100)
- `_yoast_wpseo_content_score` — readability score (0-100)

### Important Filters
- `wpseo_title` — filter the `<title>` tag output
- `wpseo_metadesc` — filter the meta description output
- `wpseo_canonical` — filter or remove the canonical URL (return `false` to remove)
- `wpseo_opengraph_title` — filter Open Graph title
- `wpseo_opengraph_desc` — filter Open Graph description
- `wpseo_opengraph_image` — filter Open Graph image
- `wpseo_schema_graph` — modify the full JSON-LD schema @graph array

### Settings in wp_options
- `wpseo` — general plugin settings
- `wpseo_titles` — title templates and meta robots per post type/taxonomy
- `wpseo_social` — social profiles, Open Graph and Twitter defaults
- `wpseo_xml` — XML sitemap configuration

### XML Sitemaps
- Index: `/sitemap_index.xml`
- Per post type: `/post-sitemap.xml`, `/page-sitemap.xml`, `/{cpt}-sitemap.xml`
- Taxonomy: `/category-sitemap.xml`, `/post_tag-sitemap.xml`

### Schema / JSON-LD
Yoast auto-generates structured data using `@graph` with entities: Organization, WebSite, WebPage, Article, BreadcrumbList, Person. Customize via `wpseo_schema_graph` filter or individual `wpseo_schema_{type}` filters.

### Breadcrumbs
- Template function: `yoast_breadcrumb('<p>', '</p>')` or `WPSEO_Breadcrumbs::breadcrumb()`
- Enable in Yoast SEO > Settings > Breadcrumbs
- Schema BreadcrumbList is auto-included when enabled

### Detecting Yoast
```php
defined('WPSEO_VERSION') // true if Yoast is active
function_exists('wpseo_init') // alternative check
```

---

## Yoast SEO Premium

**Plugin slug**: `wordpress-seo-premium/wp-seo-premium.php`. Extends the free Yoast SEO plugin with advanced features.

### Premium Features
- **Redirect Manager** — create and manage redirects (301, 302, 307, 410, 451). Redirects stored in the `wpseo_redirect` option (serialized array). Automatic redirect prompts on slug changes.
- **Internal Linking Suggestions** — suggests related posts/pages to link to while editing content.
- **Social Previews** — live previews for Facebook and Twitter/X sharing appearance.
- **Content Insights** — shows the most-used words in your content to verify topic focus.
- **SEO Workouts** — guided workflows for improving SEO (orphaned content, cornerstone content).
- **IndexNow Integration** — automatically pings search engines when content is published or updated.
- **AI Title & Description Generation** — generates SEO titles and meta descriptions using AI.

### Settings Storage
Premium settings are stored in the same `wpseo*` options as the free version with additional keys. License data is stored in `yoast_premium_*` options.

### Premium-Specific Filter
- `wpseo_premium_post_redirect_slug_change` — filter whether to create a redirect when a post slug changes.

### Detecting Yoast SEO Premium
```php
defined('WPSEO_PREMIUM_FILE') // true if Yoast SEO Premium is active
```
