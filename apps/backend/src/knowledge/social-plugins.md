# Social Media & Sharing Plugins

## When to Use
- User mentions Instagram feed, Smash Balloon, AddToAny, TrustIndex, or social sharing buttons
- User wants to display social feeds, add share buttons, or show reviews on their site
- User asks about social media integration or review widgets

## Available Tools
- `list_plugins` — detect which social plugin is active
- `search_content` — find pages containing social shortcodes
- `replace_content` — update social shortcode attributes (requires confirmation)
- `update_post` — embed social shortcodes in page content
- `get_option` — read plugin settings
- `update_option` — change plugin settings (requires confirmation)

## Workflows

### Detect Active Plugin
1. Call `list_plugins`
2. Look for: `instagram-feed` (Smash Balloon), `add-to-any`, `trustindex-plugin`

### Smash Balloon Instagram — Read Settings
1. Call `get_option` with key `sb_instagram_settings`
2. Key settings: `sb_instagram_num` (photo count), `sb_instagram_cols` (columns), `sb_instagram_cache_time`
3. Do NOT expose `sb_instagram_at` (access token)

### Smash Balloon Instagram — Find Feed Embeds
1. Call `search_content` with `[instagram-feed` to find pages displaying the feed

### Smash Balloon Instagram — Embed Feed
1. Call `update_post` to add `[instagram-feed]` to page content
2. Override settings inline: `[instagram-feed num=12 cols=4]`

### AddToAny — Read Settings
1. Call `get_option` with key `addtoany_options`
2. Key settings: `position`, `display_in_posts`, `display_in_pages`, `active_services`, `icon_size`

### AddToAny — Update Display Settings
1. Call `update_option` with key `addtoany_options` and updated values (requires confirmation)
2. Example: change `position` to `top`, `bottom`, or `top_and_bottom`

### TrustIndex — Read Settings
1. Call `get_option` with keys prefixed `trustindex-*`
2. Check `trustindex-widget-setted-up` for setup status

### TrustIndex — Embed Reviews Widget
1. Call `update_post` to add shortcode to page content:
   - Google reviews: `[trustindex no-registration=google]`
   - Facebook reviews: `[trustindex no-registration=facebook]`

### Find Social Shortcodes Across Site
1. Call `search_content` with `[instagram-feed` or `[addtoany` or `[trustindex`
2. Results show which pages contain social embeds

## Important Notes
- Instagram access tokens (`sb_instagram_at`) are sensitive — do NOT expose them
- Smash Balloon caches feed data locally — if feed appears stale, guide user to clear cache in plugin settings
- AddToAny attaches to `the_content` filter for automatic placement — no shortcode needed for standard display
- TrustIndex widgets in "registered mode" load from external CDN — may affect page performance
- For GDPR compliance, Smash Balloon can store images locally (`sb_instagram_backup` option) instead of loading from Instagram CDN
- For configuring feed layouts, connected accounts, or review sources, guide user to each plugin's admin page
