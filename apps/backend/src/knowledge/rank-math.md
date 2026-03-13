# Rank Math SEO

## When to Use
- User wants to set or update SEO title, meta description, or focus keyword on a post/page
- User asks about Rank Math SEO settings, sitemap, redirections, or schema
- Site has Rank Math active (check via `list_plugins` → look for `seo-by-rank-math`)

## Available Tools
- `list_plugins` — check if Rank Math SEO (free or Pro) is active
- `get_post` — read a post's current SEO meta values
- `update_post` — set Rank Math SEO meta fields on a post via the `meta` parameter
- `list_posts` — find posts to update SEO data on
- `search_content` — find posts by content/title
- `get_option` — read Rank Math plugin settings
- `update_option` — update Rank Math settings (requires confirmation)

## Workflows

### Check if Rank Math is Active
1. Call `list_plugins`
2. Look for `seo-by-rank-math` (free) or `seo-by-rank-math-pro` (Pro)

### Read SEO Data for a Post
1. Call `get_post` with the post ID
2. Check the `meta` section for `rank_math_title`, `rank_math_description`, `rank_math_focus_keyword`

### Set SEO Title and Meta Description
1. Find the post ID with `list_posts` or `search_content` if needed
2. Call `update_post` with `id` and:
   ```
   meta: {
     rank_math_title: '<SEO title>',
     rank_math_description: '<meta description>'
   }
   ```
3. Requires confirmation for meta updates

### Set Focus Keyword
1. Call `update_post` with `id` and `meta: { rank_math_focus_keyword: '<keyword>' }`
2. For multiple keywords (Rank Math supports this free), use comma-separated: `'keyword1, keyword2'`

### Set Canonical URL
1. Call `update_post` with `id` and `meta: { rank_math_canonical_url: 'https://example.com/page/' }`

### Set Robots Directives
1. Call `update_post` with `meta: { rank_math_robots: ['noindex', 'nofollow'] }` to noindex a page
2. Default: `['index', 'follow']`

### Set Open Graph Data
1. Call `update_post` with:
   ```
   meta: {
     rank_math_facebook_title: '<OG title>',
     rank_math_facebook_description: '<OG desc>'
   }
   ```

### Check Rank Math Settings
1. Call `get_option` with key `rank-math-options-general` for general settings and module toggles
2. Call `get_option` with key `rank-math-options-titles` for title/description templates per post type
3. Call `get_option` with key `rank-math-options-sitemap` for sitemap configuration

## Rank Math Post Meta Keys
| Data | Meta Key |
|------|---------|
| SEO title | `rank_math_title` |
| Meta description | `rank_math_description` |
| Focus keyword(s) | `rank_math_focus_keyword` |
| Canonical URL | `rank_math_canonical_url` |
| Robots directives | `rank_math_robots` (array) |
| OG title | `rank_math_facebook_title` |
| OG description | `rank_math_facebook_description` |
| OG image URL | `rank_math_facebook_image` |
| Twitter title | `rank_math_twitter_title` |

## Important Notes
- SEO titles support Rank Math variables: `%title%`, `%sep%`, `%sitename%`, `%excerpt%` (single `%`, unlike Yoast's `%%`)
- Rank Math includes a built-in redirect manager (free) — guide user to Rank Math > Redirections for managing 301/302 redirects
- Rank Math's sitemap is at `/sitemap.xml` — no tool needed; give the user the URL
- Schema/Rich Snippets configuration must be done via Rank Math > Schema in the admin panel — Wally cannot set schema type via tools
- The 404 Monitor logs are in Rank Math > 404 Monitor — guide user there to review missing pages
- Rank Math Pro is required for advanced schema, Google Analytics integration, and keyword tracking
