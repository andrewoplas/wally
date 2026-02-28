## WordPress Analytics & Tracking Plugins

### Site Kit by Google
- **Settings**: wp_options keys prefixed `googlesitekit_*`.
- **Key options**:
  - `googlesitekit_active_modules` — enabled modules array (e.g., `search-console`, `analytics-4`, `adsense`, `pagespeed-insights`, `tagmanager`)
  - `googlesitekit_analytics-4_settings` — GA4 property ID, measurement ID, web data stream ID
  - `googlesitekit_search-console_settings` — connected property URL
  - `googlesitekit_adsense_settings` — AdSense account/client ID
  - `googlesitekit_tagmanager_settings` — GTM container ID
- **Connection**: OAuth via Google — credentials in `googlesitekit_credentials` option. Connection status in `googlesitekit_connected_proxy_url`.
- **Data caching**: API responses cached in transients prefixed `googlesitekit_`. Dashboard widget data refreshed on admin load.
- **REST API**: `/google-site-kit/v1/` — modules, settings, and data endpoints.
- **Functions**: `Google\Site_Kit\Modules\Analytics_4\Analytics_4` class manages GA4 config. Module access via `Google\Site_Kit\Core\Modules\Modules::get_module($slug)`.
- **Permissions**: Requires `googlesitekit_manage_options` capability (mapped to `manage_options` by default).

### MonsterInsights
- **Settings**: wp_options keys prefixed `monsterinsights_*`.
- **Key options**:
  - `monsterinsights_site_profile` — serialized array with `ua` (UA ID), `v4` (GA4 measurement ID), `viewname`, `ua_name`
  - `monsterinsights_tracking_mode` — tracking method (typically `gtag`)
  - `monsterinsights_events_mode` — event tracking method (`js` or `php`)
  - `monsterinsights_db_version` — tracks schema version
  - `monsterinsights_over_time` — first install timestamp
- **Post-level**: `_monsterinsights_skip_tracking` postmeta (1 = exclude post from tracking).
- **Functions**: `monsterinsights_get_ua()` — returns active tracking ID. `MonsterInsights()` — main singleton. `MonsterInsights()->auth` — authentication and license data.
- **Reports**: Dashboard reports cached in transients prefixed `monsterinsights_report_`. Data fetched from Google API via `MonsterInsights_Report` subclasses.
- **Popular Posts**: `MonsterInsights_Popular_Posts` class. Inline/widget/products modes. Cached in `monsterinsights_popular_posts_cache_*` transients.
- **Hooks**: `monsterinsights_tracking_before`, `monsterinsights_tracking_after`, `monsterinsights_frontend_tracking_options`.

### GTM4WP (Google Tag Manager for WordPress)
- **Settings**: wp_options key `gtm4wp-options` (serialized array).
- **Key settings**:
  - `gtm-code` — container ID (e.g., `GTM-XXXXX`)
  - `gtm-container-placement` — placement: `0` (footer), `1` (manually coded), `2` (header with `wp_body_open`)
  - `gtm-datalayer-variable-name` — dataLayer variable name (default: `dataLayer`)
  - `gtm-env-gtm-auth`, `gtm-env-gtm-preview` — environment parameters
- **DataLayer**: Events pushed via `wp_head`/`wp_footer`. Custom events include `gtm4wp.addProductToCartEEC`, `gtm4wp.productClickEEC`, `gtm4wp.checkoutStepEEC`, `gtm4wp.orderCompletedEEC`.
- **WooCommerce**: Auto-pushes Enhanced E-commerce events (product impressions, clicks, add-to-cart, checkout steps, purchases) when WooCommerce integration enabled.
- **Hooks**: `gtm4wp_compile_datalayer` filter — modify dataLayer output. `gtm4wp_add_global_vars` — add custom JS variables.
- **Constants**: `GTM4WP_OPTION_GTM_CODE`, `GTM4WP_OPTION_GTM_PLACEMENT` — used internally for option keys.

### PixelYourSite
- **Settings**: wp_options keys prefixed `pys_*`.
- **Key options**:
  - `pys_core` — serialized main settings (general config, GDPR consent, event enrichment)
  - `pys_facebook` — Facebook Pixel ID, access token, event settings, custom audiences
  - `pys_google_analytics` — GA4 measurement ID, event settings
  - `pys_pinterest` — Pinterest Tag ID and event config
  - `pys_tiktok` — TikTok Pixel ID and settings
- **Auto-tracked events**: PageView, ViewContent, AddToCart, InitiateCheckout, Purchase — fired automatically based on page context.
- **WooCommerce integration**: Conversion tracking for add-to-cart, checkout, and purchase events. Value and currency passed to all pixels.
- **Custom events**: Stored in `pys_events` postmeta. Also configurable globally via admin UI (click triggers, URL triggers, CSS selector triggers).
- **GDPR/Consent**: Built-in consent mode integrates with CookieYes, CookieBot, Complianz via `pys_core` settings.
- **Functions**: `PYS()->getRegisteredPixels()` — returns active pixel instances. `EventsManager::addEvents()` — programmatic event registration.

### Common Patterns
- Analytics plugins inject tracking scripts via `wp_head` or `wp_footer`. Clearing page cache after config changes ensures updated tracking code is served.
- GA4 measurement IDs follow format `G-XXXXXXXXXX`. Legacy UA IDs follow `UA-XXXXXXXX-X`.
- GTM container IDs follow format `GTM-XXXXXX`. When GTM is used, individual tracking pixels are typically managed inside GTM rather than via separate plugins.
- Most plugins exclude logged-in admins/editors from tracking by default — check plugin settings if admin pageviews seem missing.
- WooCommerce e-commerce tracking requires explicit integration toggles in each plugin — it is not automatic on install.
