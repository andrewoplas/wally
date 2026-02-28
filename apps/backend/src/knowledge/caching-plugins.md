## WordPress Caching Plugins

### WP Rocket
- **Settings**: wp_options key `wp_rocket_settings` (serialized array). Sub-keys: `cache_mobile`, `do_caching_mobile_files`, `minify_css`, `minify_js`, `cdn`, `cdn_cnames`, `lazyload`, `preload`.
- **Cache location**: `wp-content/cache/wp-rocket/{domain}/` — static HTML files organized by URL path.
- **Config files**: `wp-content/wp-rocket-config/` — per-domain PHP config.
- **Purge functions**:
  - `rocket_clean_domain()` — purge entire cache
  - `rocket_clean_post($post_id)` — purge single post/page cache
  - `rocket_clean_home()` — purge homepage cache
  - `rocket_clean_files($urls)` — purge specific URLs
  - `rocket_clean_minify()` — purge minified CSS/JS
- **Hooks**: `after_rocket_clean_domain`, `after_rocket_clean_post`, `before_rocket_clean_domain`, `rocket_rucss_after_clearing_usedcss`.
- **Advanced**: wp-content/advanced-cache.php drop-in handles early page caching.

### W3 Total Cache
- **Settings**: wp_options keys prefixed `w3tc_*`. Master config in `wp-content/w3tc-config/master.php`.
- **Cache location**: `wp-content/cache/` with subdirectories for page, minify, object, db.
- **Purge functions**:
  - `w3tc_flush_all()` — purge everything
  - `w3tc_flush_post($post_id)` — purge single post
  - `w3tc_flush_url($url)` — purge specific URL
  - `w3tc_dbcache_flush()` — flush database cache
  - `w3tc_objectcache_flush()` — flush object cache
  - `w3tc_minify_flush()` — flush minified files
- **Hooks**: `w3tc_flush_all`, `w3tc_flush_post`.

### WP Super Cache
- **Settings**: `wp-content/wp-cache-config.php` (PHP file, not DB). Some settings in wp_options as `ossdl*` (CDN), `super_cache_*`.
- **Cache location**: `wp-content/cache/supercache/{domain}/` for supercache mode, `wp-content/cache/wp-cache-*` for WP-Cache mode.
- **Purge functions**:
  - `wp_cache_clear_cache()` — purge all
  - `wp_cache_post_change($post_id)` — purge single post
  - `prune_super_cache($dir, true)` — delete cache directory
- **Plugin slug**: `wp-super-cache/wp-cache.php`.

### LiteSpeed Cache
- **Settings**: wp_options keys prefixed `litespeed.*` (e.g., `litespeed.conf.cache`, `litespeed.conf.optm-css_min`).
- **Purge via actions** (no direct function calls):
  - `do_action('litespeed_purge_all')` — purge all
  - `do_action('litespeed_purge_post', $post_id)` — purge single post
  - `do_action('litespeed_purge_url', $url)` — purge specific URL
  - `do_action('litespeed_purge_blog')` — purge current blog
- **Mechanism**: Uses `X-LiteSpeed-Purge` response headers — requires LiteSpeed web server.
- **Cache location**: Server-level (not in wp-content for page cache). CSS/JS optimizations in `wp-content/litespeed/`.

### WP Fastest Cache
- **Settings**: wp_options key `WpFastestCache` (serialized). Key settings: wpFastestCacheStatus (on/off), wpFastestCacheMobile, wpFastestCacheMinifyHtml, wpFastestCacheMinifyCss, wpFastestCacheMinifyJs, wpFastestCacheCombineCss, wpFastestCacheCombineJs, wpFastestCacheLazyLoad, wpFastestCachePreload.
- **Cache location**: `wp-content/cache/all/` for page cache, `wp-content/cache/wpfc-minified/` for minified assets.
- **Purge functions**: `wpfc_clear_all_cache()` function via global $wp_fastest_cache. Or `do_action('wpfc_clear_all_cache')`.
- **Plugin slug**: `wp-fastest-cache/wpFastestCache.php`.

### Autoptimize
- **Settings**: wp_options keys prefixed `autoptimize_*`. Key: autoptimize_html (minify HTML), autoptimize_js (optimize JS), autoptimize_css (optimize CSS), autoptimize_cdn_url (CDN base URL), autoptimize_imgopt_*.
- **Cache location**: `wp-content/cache/autoptimize/` for minified/combined CSS and JS.
- **Purge**: `autoptimize_flush_pagecache()`, or via `Autoptimize_Cache::clear_all()`. Hook: `autoptimize_action_cachepurged`.
- **API filter**: `autoptimize_filter_css_exclude` and `autoptimize_filter_js_exclude` to exclude specific files.

### WP-Optimize
- **Settings**: wp_options key `wpo_*`. Key: wpo_cache_config (page cache settings), wpo_minify_config (minification), wpo_images_config.
- **Features**: Database optimization (clean revisions, spam, transients), page caching, image compression, minification.
- **Purge**: `WP_Optimize()->get_page_cache()->purge()` or `wpo_cache_flush()`.
- **Database cleanup**: `WP_Optimize()->get_optimizer()->do_optimization($optimization_id)` — IDs: revisions, auto_drafts, trashed_posts, spam_comments, unapproved_comments, expired_transients, orphaned_postmeta.

### Perfmatters
- **Settings**: wp_options key `perfmatters_options` (serialized array). Key settings: disable_emojis, disable_dashicons, disable_xmlrpc, disable_google_maps, lazy_loading (native/js), preload, cdn_url, local_analytics (host GA locally), script_manager.
- **Script Manager**: Per-page/post script/style disabling. Settings stored in postmeta `perfmatters_script_manager_settings`.
- **Functions**: Limited public API. Settings accessed via `get_option('perfmatters_options')`.

### Object Caching (Redis / Memcached)
- Drop-in file: `wp-content/object-cache.php` (provided by Redis Object Cache, W3TC, or LiteSpeed).
- **Functions**: `wp_cache_get($key, $group)`, `wp_cache_set($key, $data, $group, $expire)`, `wp_cache_delete($key, $group)`, `wp_cache_flush()`.
- Check if available: `wp_using_ext_object_cache()` returns true if persistent object cache is active.

### Common Pattern
After any content change (post update, option change, menu save), always purge relevant cache. All caching plugins hook into `save_post`, `edit_post`, `delete_post`, and `update_option` to auto-purge. For manual purges after programmatic changes, call the appropriate plugin-specific flush function. Check which caching plugin is active before calling its functions — use `is_plugin_active()` or `function_exists()`.
