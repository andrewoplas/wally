# Other SEO Plugins

## When to Use
- User asks about All in One SEO (AIOSEO) or SEOPress
- User wants to set SEO title, meta description, or Open Graph data on a post (and the site uses AIOSEO or SEOPress)
- User asks about sitemap configuration for these plugins
- For Yoast SEO, see `yoast-seo.md`; for Rank Math, see `rank-math.md`

## Available Tools
- `list_plugins` — check which SEO plugin is active
- `get_post` — read a post's SEO meta values
- `update_post` — set SEO meta fields via the `meta` parameter
- `get_option` — read SEO plugin settings
- `update_option` — update SEO plugin settings (requires confirmation)

## Workflows

### Identify Which SEO Plugin is Active
1. Call `list_plugins`
2. Look for:
   - All in One SEO: `all-in-one-seo-pack`
   - SEOPress: `wp-seopress`
   - Yoast SEO: `wordpress-seo` (see `yoast-seo.md`)
   - Rank Math: `seo-by-rank-math` (see `rank-math.md`)

### Set SEO Data with AIOSEO
1. Call `update_post` with `id` and:
   ```
   meta: {
     _aioseo_title: '<SEO title>',
     _aioseo_description: '<meta description>'
   }
   ```
2. AIOSEO also stores data in its own `aioseo_posts` table — postmeta compatibility keys work for setting values

### Set SEO Data with SEOPress
1. Call `update_post` with `id` and:
   ```
   meta: {
     _seopress_titles_title: '<SEO title>',
     _seopress_titles_desc: '<meta description>'
   }
   ```

### Set Open Graph Data with SEOPress
1. Call `update_post` with:
   ```
   meta: {
     _seopress_social_fb_title: '<OG title>',
     _seopress_social_fb_desc: '<OG description>'
   }
   ```

### Check AIOSEO Settings
1. Call `get_option` with key `aioseo_options` — returns main settings as JSON

### Check SEOPress Settings
1. Call `get_option` with key `seopress_titles_option` — title templates
2. Call `get_option` with key `seopress_social_option` — social/OG defaults

## Meta Key Reference

### AIOSEO
| Data | Meta Key |
|------|---------|
| SEO title | `_aioseo_title` |
| Meta description | `_aioseo_description` |
| Focus keyphrase | `_aioseo_keyphrases` (JSON) |
| Canonical URL | `_aioseo_canonical_url` |

### SEOPress
| Data | Meta Key |
|------|---------|
| SEO title | `_seopress_titles_title` |
| Meta description | `_seopress_titles_desc` |
| Focus keyword | `_seopress_analysis_target_kw` |
| Canonical URL | `_seopress_robots_canonical` |
| Noindex | `_seopress_robots_index` (`yes` = noindex) |
| OG title | `_seopress_social_fb_title` |
| OG description | `_seopress_social_fb_desc` |

## Important Notes
- AIOSEO title variables use `#title`, `#separator`, `#site_title` syntax (different from Yoast's `%%` and Rank Math's `%`)
- SEOPress sitemap is at `/sitemaps.xml` (plural) — different from Yoast/Rank Math/AIOSEO which use `/sitemap_index.xml` or `/sitemap.xml`
- Only ONE SEO plugin should be active at a time — multiple SEO plugins conflict with each other
- Schema/structured data configuration must be done in the plugin's admin panel — Wally cannot set schema types via tools
- Google XML Sitemaps is a standalone sitemap plugin (no meta/schema) — settings in `get_option` key `sm_options`
