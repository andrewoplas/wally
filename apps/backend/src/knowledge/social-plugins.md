## Social Media & Sharing Plugins

### Smash Balloon Social Photo Feed (instagram-feed)
- **Plugin slug**: `instagram-feed/instagram-feed.php`. Pro version: `instagram-feed-pro/instagram-feed.php`.
- **Settings**: wp_options key `sb_instagram_settings` (serialized array). Key sub-keys: `sb_instagram_at` (access token), `sb_instagram_user_id`, `connected_accounts` (serialized array of connected Instagram accounts), `sb_instagram_num` (number of photos), `sb_instagram_cols` (columns), `sb_instagram_width`, `sb_instagram_height`, `sb_instagram_image_res` (image resolution: auto, thumb, medium, full), `sb_instagram_sort` (none, random), `sb_instagram_cache_time`, `sb_instagram_cache_time_unit` (minutes, hours, days).
- **Shortcode**: `[instagram-feed]` — renders the feed. Attributes override global settings:
  - `[instagram-feed num=12 cols=4]` — 12 photos in 4 columns
  - `[instagram-feed user="username"]` — specific user feed
  - `[instagram-feed feed=1]` — render a specific saved feed (Pro)
  - `[instagram-feed type=hashtag hashtag="#example"]` — hashtag feed (Pro)
- **Custom post type**: `sbi_feed_cpt` — stores saved feed configurations (Pro feature, also used in free since v6). Each feed's settings stored in postmeta. Also `sbi_source` CPT for connected accounts.
- **Database tables**:
  - `{prefix}sbi_feed_caches` — cached feed data (JSON). Columns: `id`, `feed_id`, `cache_key`, `cache_value` (JSON blob of Instagram posts), `cron_update`, `last_updated`.
  - `{prefix}sbi_sources` — connected Instagram accounts. Columns: `id`, `account_id`, `token`, `username`, `info`, `error`, `expires`.
- **Options keys**:
  - `sb_instagram_settings` — main settings (serialized)
  - `sbi_usage_tracking` — anonymous usage data opt-in
  - `sbi_rating_notice` — admin notice state
  - `sbi_db_version` — database schema version
  - `sbi_cron_schedule` — cache refresh schedule
  - `sb_instagram_errors` — stored API error messages
- **Caching**: Feed data cached in `sbi_feed_caches` table (v6+) or in transients `sbi_feed_*` (older versions). Cache cleared via Settings > Instagram Feed > Clear Cache, or programmatically: `\InstagramFeed\SBI_Cache::clear_all_caches()`.
- **CSS customization**: Custom CSS stored in `sb_instagram_settings` under `sb_instagram_custom_css`. Plugin generates a CSS file at `wp-content/uploads/sb-instagram-feed-images/`. Enqueued stylesheet: `sb-instagram-feed/css/sbi-styles.css`.
- **Hooks**:
  ```php
  // Filter feed HTML output
  apply_filters( 'sbi_before_feed', $html, $feed_id );
  apply_filters( 'sbi_after_feed', $html, $feed_id );

  // Filter individual item HTML
  apply_filters( 'sbi_item_html', $html, $post_data );

  // Modify feed settings before rendering
  apply_filters( 'sbi_settings_before_display', $settings );

  // After cache is updated
  do_action( 'sbi_after_cache_update', $feed_id );

  // Custom image resolution
  apply_filters( 'sbi_image_resolution', $resolution, $post_data );
  ```
- **Image storage**: Local image storage (for GDPR compliance) saves copies to `wp-content/uploads/sb-instagram-feed-images/`. Option `sb_instagram_backup` controls this feature. When enabled, images served locally instead of from Instagram CDN.
- **Detection**:
  ```php
  class_exists( 'SB_Instagram_Feed' ) // true if Smash Balloon Instagram Feed is active
  defined( 'SBIVER' ) // version check
  defined( 'SBI_PLUGIN_DIR' ) // plugin directory path
  ```

### AddToAny Share Buttons (add-to-any)
- **Plugin slug**: `add-to-any/add-to-any.php`.
- **Settings**: wp_options key `addtoany_options` (serialized array). Key sub-keys:
  - `position` — button placement: `bottom`, `top`, `top_and_bottom`, or empty (manual only)
  - `display_in_posts` — show on single posts (1/0)
  - `display_in_pages` — show on pages (1/0)
  - `display_in_excerpts` — show in archive excerpts (1/0)
  - `display_in_feed` — show in RSS feed (1/0)
  - `display_in_cpt_{name}` — show on custom post type (1/0)
  - `icon_size` — button icon size in pixels (default 32)
  - `custom_icons` — enable custom icon images (1/0)
  - `custom_icons_prefix` — URL prefix for custom icon images
  - `additional_js_variables` — custom JavaScript configuration
  - `inline_css` — enable inline CSS (1/0)
  - `floating_vertical` — enable floating vertical bar (`left_docked`, `right_docked`, or empty)
  - `floating_horizontal` — enable floating horizontal bar (`bottom_docked` or empty)
  - `header_javascript` — custom JS injected in header
  - `active_services` — comma-separated list of enabled services (e.g., `facebook,twitter,email,linkedin`)
  - `special_services` — special service buttons (facebook_like, twitter_tweet, pinterest_pin, etc.)
- **Template functions / Actions**:
  ```php
  // Output share buttons in theme templates
  echo do_shortcode('[addtoany]');

  // Or use the action hook
  do_action( 'addtoany_share_buttons_html' );

  // PHP function (deprecated but still works)
  if ( function_exists( 'ADDTOANY_SHARE_SAVE_KIT' ) ) {
      ADDTOANY_SHARE_SAVE_KIT();
  }
  ```
- **Shortcode**: `[addtoany]` — renders share buttons inline. Attributes:
  - `[addtoany url="https://example.com" title="Custom Title"]` — share specific URL
  - `[addtoany buttons="facebook,twitter,email"]` — specific buttons only
- **Placement hooks**: Plugin uses `the_content` filter for automatic placement. For manual placement in themes:
  ```php
  // After post content
  add_filter( 'the_content', 'A2A_SHARE_SAVE_add_to_content', 98 );
  ```
- **Floating bar**: Configured via `floating_vertical` and `floating_horizontal` options. CSS classes: `.a2a_floating_style.a2a_vertical_style` (vertical), `.a2a_floating_style.a2a_default_style` (horizontal).
- **Share counts**: Enabled via `share_counts` option. Counts fetched client-side via AddToAny's API. Cache controlled by `share_counts_cache_time` (in minutes).
- **Hooks**:
  ```php
  // Filter share URL
  apply_filters( 'addtoany_sharing_url', $url, $post_id );

  // Filter share title
  apply_filters( 'addtoany_sharing_title', $title, $post_id );

  // Filter the list of services
  apply_filters( 'addtoany_active_services', $services );

  // Control display per post
  apply_filters( 'addtoany_disable', $disable, $post );

  // Modify button HTML
  apply_filters( 'addtoany_output', $html );
  ```
- **CSS**: Styles enqueued from `add-to-any/addtoany.min.css`. Buttons use class prefix `.a2a_`. Key classes: `.a2a_kit` (container), `.a2a_button_facebook`, `.a2a_button_twitter`, `.a2a_button_email`, `.a2a_dd` (universal share button).
- **Detection**:
  ```php
  function_exists( 'ADDTOANY_SHARE_SAVE_KIT' ) // true if AddToAny is active
  defined( 'A2A_SHARE_SAVE_VERSION' ) // version check
  ```

### TrustIndex (trustindex)
- **Plugin slug**: Various per platform — `trustindex-plugin/trustindex-plugin.php` (Google), or platform-specific slugs like `wp-starter-flavor-flavor/flavor.php`. The main all-in-one slug: `developer-flavor-flavor/flavor-developer.php`. Most common: `trustindex-plugin`.
- **Settings**: wp_options keys prefixed `trustindex-*`. Key options:
  - `trustindex-google-page-details` — connected Google Business page data (serialized)
  - `trustindex-facebook-page-details` — connected Facebook page data
  - `trustindex-notification-email` — email for review notifications
  - `trustindex-widget-setted-up` — whether initial widget setup is complete (1/0)
  - `trustindex-referral-token` — API referral token
  - `trustindex_subscription` — subscription plan details
- **Widget configuration**: Widgets created through the TrustIndex admin dashboard. Each widget stored in options:
  - `trustindex-widget-{id}` — widget HTML/embed code
  - `trustindex-selected-widget-type` — layout type (slider, grid, list, badge, button, floating, popup, sidebar)
  - `trustindex-selected-widget-style` — visual style variant (numbered)
  - `trustindex-selected-lang` — widget language
- **Shortcode**: `[trustindex no-registration=google]` — displays the configured Google reviews widget. Platform-specific shortcodes:
  - `[trustindex no-registration=google]` — Google reviews
  - `[trustindex no-registration=facebook]` — Facebook reviews
  - `[trustindex no-registration=yelp]` — Yelp reviews
  - `[trustindex data-widget-id="WIDGET_ID"]` — specific widget by ID (registered users)
- **Gutenberg block**: "Starter Templates" or "TrustIndex" block available in the block editor for inserting review widgets.
- **Review platforms supported**: Google, Facebook, Yelp, TripAdvisor, Trustpilot, Booking.com, Amazon, AliExpress, Airbnb, Hotels.com, G2, Capterra, and others.
- **Review data**: When using the "no-registration" mode (free), reviews are fetched from the platform API and cached locally in wp_options as serialized data under `trustindex-{platform}-review-content`. Cached review data includes reviewer name, rating, text, date, and profile image URL.
- **Hooks**: TrustIndex has limited hook support. Widget output is primarily embedded HTML/JavaScript.
  ```php
  // Widget rendered via shortcode callback
  add_shortcode( 'trustindex', array( $this, 'shortcode_render' ) );

  // Enqueues widget CSS/JS
  add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_widget_scripts' ) );
  ```
- **Widget display**: Widgets render using an iframe or inline HTML depending on the mode:
  - **No-registration mode**: Renders inline HTML with reviews fetched and cached by the plugin
  - **Registered mode**: Loads widget via JavaScript from TrustIndex CDN (`cdn.trustindex.io`)
- **CSS classes**: `.ti-widget` (widget container), `.ti-review-item` (individual review), `.ti-stars` (star rating), `.ti-profile-img` (reviewer avatar), `.ti-review-content` (review text).
- **Detection**:
  ```php
  defined( 'STARTER_FLAVOR_VERSION' ) // true if TrustIndex is active
  class_exists( 'flavor_Developer' ) // alternative check
  ```

### Common Patterns
- Social and sharing plugins typically use shortcodes and/or widget areas for display, making them easy to place via Elementor's Shortcode widget or theme template modifications.
- Instagram feed plugins cache API responses locally to avoid rate limits (Instagram Graph API allows ~200 calls/hour). Always use the plugin's built-in caching rather than custom cron jobs.
- Share button plugins attach to `the_content` filter at a specific priority (usually 98-99) for automatic placement. When conflicts arise with other plugins modifying content, adjust the priority.
- Review/social proof widgets often load external JavaScript and may impact page performance. Consider lazy-loading or deferring their scripts where possible.
- GDPR considerations: Instagram feed local image storage and review display may require consent notices depending on jurisdiction. Smash Balloon's GDPR compliance mode disables direct Instagram CDN connections.
