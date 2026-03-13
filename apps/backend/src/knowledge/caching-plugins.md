# WordPress Caching Plugins

## When to Use
- User mentions WP Rocket, W3 Total Cache, WP Super Cache, LiteSpeed Cache, WP Fastest Cache, Autoptimize, WP-Optimize, or Perfmatters
- User asks about caching, page speed, minification, or performance optimization
- User wants to check or change caching/performance settings
- User reports stale content (may need cache purge guidance)

## Available Tools
- `list_plugins` — detect which caching plugin is active
- `get_option` — read caching plugin settings
- `update_option` — change caching settings (requires confirmation)

## Workflows

### Detect Active Caching Plugin
1. Call `list_plugins`
2. Look for: `wp-rocket`, `w3-total-cache`, `wp-super-cache`, `litespeed-cache`, `wp-fastest-cache`, `autoptimize`, `wp-optimize`, `perfmatters`

### WP Rocket — Read Settings
1. Call `get_option` with key `wp_rocket_settings`
2. Key sub-keys: `cache_mobile`, `minify_css`, `minify_js`, `cdn`, `lazyload`, `preload`

### W3 Total Cache — Read Settings
1. Call `get_option` with keys prefixed `w3tc_*`
2. Master config is file-based (`wp-content/w3tc-config/master.php`) — not fully readable via `get_option`

### WP Super Cache — Read Settings
1. Settings are file-based (`wp-content/wp-cache-config.php`) — limited access via `get_option`
2. Some CDN settings available: `get_option` with keys prefixed `ossdl*`

### LiteSpeed Cache — Read Settings
1. Call `get_option` with keys prefixed `litespeed.*`
2. Example: `litespeed.conf.cache` (cache enabled), `litespeed.conf.optm-css_min` (CSS minify)

### WP Fastest Cache — Read Settings
1. Call `get_option` with key `WpFastestCache`
2. Key settings: `wpFastestCacheStatus`, `wpFastestCacheMobile`, `wpFastestCacheMinifyHtml`

### Autoptimize — Read Settings
1. Call `get_option` with keys prefixed `autoptimize_*`
2. Key: `autoptimize_html`, `autoptimize_js`, `autoptimize_css`, `autoptimize_cdn_url`

### WP-Optimize — Read Settings
1. Call `get_option` with keys prefixed `wpo_*`
2. Key: `wpo_cache_config`, `wpo_minify_config`

### Perfmatters — Read Settings
1. Call `get_option` with key `perfmatters_options`
2. Key settings: `disable_emojis`, `disable_dashicons`, `lazy_loading`, `cdn_url`

### Update Caching Settings
1. Identify the correct option key for the active plugin (see above)
2. Call `update_option` with key and new value (requires confirmation)
3. Warn user that content may appear stale until cache is cleared

## Important Notes
- Wally cannot purge caches directly — guide user to the plugin's admin "Clear Cache" button
- After any content change via Wally, caching plugins auto-purge the affected post's cache (via `save_post` hook)
- For site-wide cache clears (after settings changes), user must purge manually from the plugin's admin UI
- WP Rocket and LiteSpeed use `advanced-cache.php` drop-in — do not modify this file
- Object caching (Redis/Memcached) uses `wp-content/object-cache.php` — check with `get_site_info`
- Some settings are file-based (W3TC, WP Super Cache) — not fully manageable via `get_option`/`update_option`
- Multiple caching plugins should NOT be active simultaneously — check `list_plugins` and warn if duplicates found
