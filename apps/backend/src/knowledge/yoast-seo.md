# Yoast SEO

## When to Use
- User wants to set or update SEO title, meta description, or focus keyword on a post/page
- User asks about Yoast SEO settings, sitemap, or Open Graph data
- User wants to check or update schema/JSON-LD settings
- Site has Yoast SEO active (check via `list_plugins` → look for `wordpress-seo`)

## Available Tools
- `list_plugins` — check if Yoast SEO (free or premium) is active
- `get_post` — read a post's current SEO meta values
- `update_post` — set Yoast SEO meta fields on a post via the `meta` parameter
- `list_posts` — find posts to update SEO data on
- `search_content` — find posts by content/title
- `get_option` — read Yoast SEO plugin settings
- `update_option` — update Yoast SEO settings (requires confirmation)

## Workflows

### Check if Yoast SEO is Active
1. Call `list_plugins`
2. Look for `wordpress-seo` (free) or `wordpress-seo-premium` (Premium)

### Read SEO Data for a Post
1. Call `get_post` with the post ID
2. Check the `meta` section for `_yoast_wpseo_title`, `_yoast_wpseo_metadesc`, `_yoast_wpseo_focuskw`

### Set SEO Title and Meta Description
1. Find the post ID with `list_posts` or `search_content` if needed
2. Call `update_post` with `id` and:
   ```
   meta: {
     _yoast_wpseo_title: '<SEO title>',
     _yoast_wpseo_metadesc: '<meta description>'
   }
   ```
3. Requires confirmation for meta updates

### Set Focus Keyword
1. Call `update_post` with `id` and `meta: { _yoast_wpseo_focuskw: '<keyword>' }`

### Set Canonical URL
1. Call `update_post` with `id` and `meta: { _yoast_wpseo_canonical: 'https://example.com/page/' }`

### Set Open Graph Data
1. Call `update_post` with `meta: { _yoast_wpseo_opengraph-title: '<OG title>', _yoast_wpseo_opengraph-description: '<OG desc>' }`

### Check Yoast Settings
1. Call `get_option` with key `wpseo` for general settings
2. Call `get_option` with key `wpseo_titles` for title templates per post type
3. Call `get_option` with key `wpseo_social` for social/Open Graph defaults

## Yoast SEO Post Meta Keys
| Data | Meta Key |
|------|---------|
| SEO title | `_yoast_wpseo_title` |
| Meta description | `_yoast_wpseo_metadesc` |
| Focus keyword | `_yoast_wpseo_focuskw` |
| Canonical URL | `_yoast_wpseo_canonical` |
| OG title | `_yoast_wpseo_opengraph-title` |
| OG description | `_yoast_wpseo_opengraph-description` |
| OG image URL | `_yoast_wpseo_opengraph-image` |
| Twitter title | `_yoast_wpseo_twitter-title` |
| Twitter description | `_yoast_wpseo_twitter-description` |

## Important Notes
- SEO titles support Yoast variables: `%%title%%`, `%%sep%%`, `%%sitename%%`, `%%page%%`
- If the user asks to update SEO data for many posts at once, process them one at a time with `update_post`
- Yoast's sitemap is at `/sitemap_index.xml` — no tool needed to view it; give the user the URL
- Redirect management requires Yoast SEO Premium — guide user to Yoast > Redirects for free alternatives
- For bulk SEO audits or schema customization, guide user to the Yoast SEO admin panel
- `wpseo_xml` option controls sitemap settings; `wpseo_social` controls Open Graph/Twitter defaults
