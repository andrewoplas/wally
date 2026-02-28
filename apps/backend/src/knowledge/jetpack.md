## Jetpack Plugin

### Architecture
- Jetpack connects the self-hosted site to WordPress.com via XML-RPC. Requires a WordPress.com account for activation.
- Settings and data are synced between local WP and WordPress.com servers. Some features (Stats, Search, Related Posts) are computed remotely.
- Connection data stored in `jetpack_options` and `jetpack_private_options` (contains `blog_id`, `id`, `blog_token`, `user_tokens`).

### Modules System
- Features are individually activated/deactivated as modules. Stored in `jetpack_active_modules` (serialized array).
- **Key modules**: `stats`, `protect`, `photon` (image CDN), `publicize` (auto-share to social), `related-posts`, `search`, `sso` (WordPress.com SSO login), `markdown`, `custom-css`, `lazy-images`, `sitemaps`, `wordads`, `videopress`, `monitor` (downtime alerts), `contact-form`, `carousel` (lightbox gallery), `shortcodes`, `widget-visibility`, `enhanced-distribution`, `subscriptions`, `copy-post`, `comments` (WordPress.com comments), `sharedaddy` (sharing buttons), `likes` (WordPress.com likes), `tiled-gallery`.
- **Functions**:
  - `Jetpack::is_module_active($module)` — check if module is on
  - `Jetpack::activate_module($module)` — enable a module
  - `Jetpack::deactivate_module($module)` — disable a module
  - `Jetpack::get_active_modules()` — list all active module slugs

### Settings Storage
- wp_options keys prefixed `jetpack_*`. Key options:
  - `jetpack_active_modules` — array of active module slugs
  - `jetpack_options` — connection data (blog_id, id, master_user)
  - `jetpack_private_options` — sensitive tokens (blog_token, user_tokens)
  - `stats_options` — Stats module config (admin_bar, roles to view, count_roles, blog_id)
  - `sharing-services` — enabled sharing button services and visibility (visible vs hidden)
  - `sharing-options` — sharing button display options (label, style, show/hide)
  - `jetpack_sync_settings` — sync module configuration
  - `jetpack_publicize_options` — social sharing defaults
- **Module-specific**: `Jetpack_Options::get_option($key)`, `Jetpack_Options::update_option($key, $value)`.

### Stats
- Data stored remotely on WordPress.com. Local cache in transients prefixed `jetpack_site_stats_*`.
- **Functions**: `stats_get_csv($table, $args)` — fetch stats (views, referrers, search terms, clicks). Tables: `views`, `postviews`, `referrers`, `searchterms`, `clicks`, `videoplays`.
- **REST**: `GET /wpcom/v2/sites/{blog_id}/stats/visits` and similar WordPress.com REST endpoints.

### Publicize (Social Sharing)
- Auto-shares new posts to connected social accounts. Connections stored in Jetpack cloud, synced via `Jetpack_Options`.
- **Per-post control**: `_wpas_done_all` postmeta (1 = skip sharing). `_wpas_mess` — custom share message. `_wpas_skip_{connection_id}` — skip specific connection.
- **Supported services**: Facebook, Twitter/X, LinkedIn, Tumblr, Mastodon.
- **Hooks**: `publicize_save_meta`, `wpas_submit_post`.

### Related Posts
- Computed by WordPress.com Elasticsearch — requires active connection.
- **Filter**: `jetpack_relatedposts_filter_options` — modify display options (show heading, show thumbnails, show date, show context, layout).
- **Filter**: `jetpack_relatedposts_filter_filters` — modify Elasticsearch query filters.
- **Shortcode**: `[jetpack-related-posts]` — manually place related posts.

### Contact Form
- Creates `feedback` post type for form submissions.
- **Shortcode**: `[contact-form][contact-field label="Name" type="name" required="1"/][contact-field label="Email" type="email" required="1"/][contact-field label="Message" type="textarea"/][/contact-form]`.
- **Field types**: `name`, `email`, `url`, `text`, `textarea`, `checkbox`, `checkbox-multiple`, `radio`, `select`, `date`, `telephone`.
- Submissions stored as `feedback` posts with `_feedback_*` postmeta.

### Protect (Brute Force)
- Blocks brute force login attacks using WordPress.com's IP blocklist.
- **Whitelist**: `jetpack_protect_whitelist` option — array of allowed IPs.
- **Functions**: `Jetpack_Protect_Module::get_protect_counts()` — total blocked attacks.

### Sitemaps
- Available at `/sitemap.xml` when module active. Auto-generated, includes posts, pages, and custom post types.
- **Filters**: `jetpack_sitemap_post_types` — control which post types are included. `jetpack_sitemap_exclude_posts` — exclude specific post IDs.

### Search
- Replaces default WP search with WordPress.com Elasticsearch (paid plan required).
- **Widget**: `jetpack-search-widget` — provides faceted search with filters.
- **Options**: `jetpack_search_*` keys — configuration for search behavior and customization.
- **Hooks**: `jetpack_search_es_query_args` — modify Elasticsearch query.

### Important Notes
- Many features require active WordPress.com connection. If connection breaks, verify `jetpack_options` contains valid `blog_id`, `id`, and that `jetpack_private_options` has `blog_token` and `user_tokens`.
- Photon (image CDN) serves images from `i0.wp.com` — disabling it reverts to local image URLs.
- Jetpack Sync keeps local and WordPress.com data in sync. If sync breaks, check `jetpack_sync_settings` and wp_cron for `jetpack_sync_*` events.
- SSO module allows logging in with WordPress.com credentials. Disable to revert to default WP login. Option: `jetpack_sso_require_two_step` — enforce 2FA.
