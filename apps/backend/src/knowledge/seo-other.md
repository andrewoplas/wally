## All in One SEO (AIOSEO)

### Meta Data Storage
AIOSEO stores SEO data in a custom table `{prefix}aioseo_posts` (not just postmeta). Key columns:
- `post_id`, `title`, `description`, `keywords`, `canonical_url`
- `og_title`, `og_description`, `og_image`, `og_article_tags`
- `twitter_title`, `twitter_description`, `twitter_image`
- `robots_noindex`, `robots_nofollow`, `robots_default` (boolean flags)
- `focus_keyphrase`, `keyphrases` (JSON — primary + additional)
- `seo_score`, `schema` (JSON — structured data config)
- Compatibility postmeta keys with `_aioseo_*` prefix also exist

### Reading/Writing SEO Data
```php
// Via model (preferred)
$aioseoPost = AIOSEO\Plugin\Common\Models\Post::getPost($post_id);
$aioseoPost->title = 'New Title';
$aioseoPost->save();
// Via helper
aioseo()->meta->title->getTitle($post);
aioseo()->meta->description->getDescription($post);
// Via postmeta (compatibility)
get_post_meta($post_id, '_aioseo_title', true);
update_post_meta($post_id, '_aioseo_title', 'New Title');
```

### Settings in wp_options
- `aioseo_options` — main settings (JSON string)
- `aioseo_options_dynamic` — dynamic/computed settings
- `aioseo_options_localized` — localized strings config

### Database Tables
- `{prefix}aioseo_posts` — per-post SEO data (primary storage)
- `{prefix}aioseo_notifications` — admin notifications
- `{prefix}aioseo_cache` — internal caching

### Hooks
- `aioseo_title` — filter the title tag output
- `aioseo_description` — filter the meta description
- `aioseo_schema_output` — modify JSON-LD schema output
- `aioseo_robots_meta` — filter robots meta directives

### Sitemaps & Schema
- Sitemap URL: `/sitemap.xml`, configurable per post type/taxonomy
- Schema types stored in `aioseo_posts.schema` column as JSON
- Built-in types: Article, WebPage, Product, FAQ, Recipe, etc.

### Detection
```php
defined('AIOSEO_VERSION') // true if AIOSEO is active
```

### Key Difference from Yoast
AIOSEO stores data in its own table (`aioseo_posts`) rather than relying solely on postmeta. Uses `aioseo_` prefix. Title variables use `#title`, `#separator`, `#site_title` syntax.

---

## SEOPress

### Meta Data Storage
SEOPress stores SEO data as standard postmeta with `_seopress_` prefix:
- `_seopress_titles_title` — SEO title
- `_seopress_titles_desc` — meta description
- `_seopress_social_fb_title`, `_seopress_social_fb_desc`, `_seopress_social_fb_img` — Open Graph
- `_seopress_social_twitter_title`, `_seopress_social_twitter_desc` — Twitter card
- `_seopress_robots_canonical` — canonical URL override
- `_seopress_robots_index` — "yes" to noindex
- `_seopress_analysis_target_kw` — focus keyword
- `_seopress_analysis_score` — SEO score

### Reading/Writing SEO Data
```php
// Read via postmeta
get_post_meta($post_id, '_seopress_titles_title', true);
get_post_meta($post_id, '_seopress_titles_desc', true);
// Write
update_post_meta($post_id, '_seopress_titles_title', 'New Title');
update_post_meta($post_id, '_seopress_robots_index', ''); // empty = index
// Via service (frontend rendering)
seopress_get_service('TitleMeta')->getValue();
seopress_get_service('DescriptionMeta')->getValue();
```

### Settings in wp_options
- `seopress_titles_option` — title/meta templates per post type
- `seopress_social_option` — social profiles, OG defaults
- `seopress_xml_sitemap_option` — sitemap configuration
- `seopress_advanced_option` — advanced/misc settings
- `seopress_pro_option` — Pro features (schemas, redirects, etc.)

### Hooks
- `seopress_titles_title` — filter the title tag
- `seopress_titles_desc` — filter the meta description
- `seopress_sitemaps_xml_single` — filter sitemap entries

### Sitemaps
- URL: `/sitemaps.xml` (note: plural, different from Yoast/AIOSEO)
- Per post type: `/sitemaps/{posttype}-sitemap.xml`

### Detection
```php
defined('SEOPRESS_VERSION') // true if SEOPress is active
```

---

## XML Sitemap Generator for Google (Google XML Sitemaps)

### Purpose
One of the oldest WordPress sitemap plugins (by Arne Brachhold, now maintained by Jeherve). Generates XML sitemaps following the sitemap.org protocol to help search engines (Google, Bing, Yahoo) discover and index site content.

### Settings Storage
All settings stored in wp_options under key `sm_options` (serialized). Key sub-keys:
- `sm_b_ping` — ping search engines on update (true/false)
- `sm_b_stats` — allow anonymous statistics (true/false)
- `sm_b_pingmsn` — ping Bing on update (true/false)
- `sm_b_memory` — PHP memory limit override
- `sm_b_time` — PHP time limit override
- `sm_b_style_default` — use default XSLT stylesheet (true/false)
- `sm_in_home` — include homepage (true/false)
- `sm_in_posts` — include standard posts (true/false)
- `sm_in_pages` — include pages (true/false)
- `sm_in_cats` — include category archives (true/false)
- `sm_in_arch` — include date archives (true/false)
- `sm_in_auth` — include author archives (true/false)
- `sm_in_tags` — include tag archives (true/false)
- `sm_in_tax` — array of additional taxonomy slugs to include
- `sm_in_customtypes` — array of custom post type slugs to include
- `sm_in_lastmod` — include last modification time (true/false)
- `sm_cf_home` — change frequency for homepage (always/hourly/daily/weekly/monthly/yearly/never)
- `sm_cf_posts` — change frequency for posts
- `sm_cf_pages` — change frequency for pages
- `sm_cf_cats` — change frequency for categories
- `sm_cf_arch` — change frequency for archives
- `sm_cf_auth` — change frequency for author pages
- `sm_cf_tags` — change frequency for tag pages
- `sm_pr_home` — priority for homepage (0.0–1.0)
- `sm_pr_posts` — priority for posts
- `sm_pr_posts_min` — minimum priority for posts
- `sm_pr_pages` — priority for pages
- `sm_pr_cats` — priority for categories
- `sm_pr_arch` — priority for archives
- `sm_pr_auth` — priority for author pages
- `sm_pr_tags` — priority for tag pages

### Sitemap Output
- **Default URL**: `sitemap.xml` at site root (e.g., `https://example.com/sitemap.xml`)
- **Index sitemap**: For large sites, creates a sitemap index with child sitemaps (`sitemap-pt-post-2024-01.xml`, `sitemap-pt-page-2024-01.xml`, etc.)
- **Generation mode**: Dynamic (virtual — served via WordPress rewrite rules) or static (writes physical XML files to disk). Dynamic is default and recommended.
- **XSLT stylesheet**: Applies an XSLT stylesheet for human-readable browser display

### Key Hooks

```php
// Add custom URLs to the sitemap
add_action( 'sm_buildmap', function() {
    $generatorObject = &GoogleSitemapGenerator::GetInstance();
    if ( $generatorObject != null ) {
        $generatorObject->AddUrl(
            'https://example.com/custom-page/',
            time(),                    // last modified timestamp
            'weekly',                  // change frequency
            0.5                        // priority
        );
    }
});

// Shortcut to add a single URL
do_action( 'sm_addurl', array(
    'loc'        => 'https://example.com/my-page/',
    'lastmod'    => time(),
    'changefreq' => 'monthly',
    'priority'   => 0.5,
));

// Trigger sitemap rebuild programmatically
do_action( 'sm_rebuild' );

// Filter post priority calculation
add_filter( 'sm_post_priority', function( $priority, $post_id, $post ) {
    return $priority;
}, 10, 3 );

// Filter post change frequency
add_filter( 'sm_post_changefreq', function( $freq, $post_id ) {
    return $freq;
}, 10, 2 );
```

### Custom Post Type & Taxonomy Support
Custom post types and taxonomies can be included via settings (checkboxes in admin) or programmatically:
```php
// The plugin auto-detects public custom post types and taxonomies
// and displays them as options in Settings > XML-Sitemap
```

### Search Engine Notification
On content update, the plugin pings:
- Google: `https://www.google.com/webmasters/tools/ping?sitemap=URL`
- Bing: `https://www.bing.com/ping?sitemap=URL`
Pinging can be disabled via `sm_b_ping` and `sm_b_pingmsn` options.

### Detection
```php
class_exists( 'GoogleSitemapGenerator' )  // true if XML Sitemap Generator is active
defined( 'SM_SITEMAPURL' )                // alternative check
```

### Key Difference from Yoast/AIOSEO Sitemaps
This is a standalone sitemap plugin — it does not handle meta tags, schema, or any other SEO features. Useful when the active SEO plugin lacks sitemap functionality or when you want independent sitemap control. If Yoast or AIOSEO is active, their built-in sitemaps should be used instead to avoid conflicts (multiple sitemap plugins can cause duplicate sitemaps).
