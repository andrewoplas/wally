# WordPress Analytics & Tracking Plugins

## When to Use
- User mentions Site Kit, MonsterInsights, GTM4WP, PixelYourSite, Google Analytics, or Tag Manager
- User asks about analytics, tracking, conversion pixels, or measurement IDs
- User wants to check or change analytics/tracking configuration

## Available Tools
- `list_plugins` — detect which analytics plugin is active
- `get_option` — read analytics plugin settings and tracking IDs
- `update_option` — change analytics settings (requires confirmation)

## Workflows

### Detect Active Analytics Plugin
1. Call `list_plugins`
2. Look for: `google-site-kit`, `google-analytics-for-wordpress` (MonsterInsights), `duracelltomi-google-tag-manager` (GTM4WP), `pixelyoursite`

### Site Kit by Google — Check Configuration
1. Call `get_option` with key `googlesitekit_active_modules` — enabled modules
2. Call `get_option` with key `googlesitekit_analytics-4_settings` — GA4 property/measurement ID
3. Call `get_option` with key `googlesitekit_search-console_settings` — Search Console property
4. Call `get_option` with key `googlesitekit_tagmanager_settings` — GTM container ID

### MonsterInsights — Check Tracking ID
1. Call `get_option` with key `monsterinsights_site_profile`
2. Contains `v4` (GA4 measurement ID) and `ua` (legacy UA ID)
3. Call `get_option` with key `monsterinsights_tracking_mode` — tracking method

### GTM4WP — Check Container ID
1. Call `get_option` with key `gtm4wp-options`
2. Key sub-keys: `gtm-code` (container ID like `GTM-XXXXX`), `gtm-container-placement`

### PixelYourSite — Check Pixel IDs
1. Call `get_option` with key `pys_facebook` — Facebook Pixel ID
2. Call `get_option` with key `pys_google_analytics` — GA4 measurement ID
3. Call `get_option` with key `pys_pinterest` — Pinterest Tag ID
4. Call `get_option` with key `pys_tiktok` — TikTok Pixel ID
5. Call `get_option` with key `pys_core` — general settings and GDPR consent config

### Update Analytics Settings
1. Identify the correct option key for the active plugin (see above)
2. Call `update_option` with key and new value (requires confirmation)
3. Remind user to clear page cache after changes so updated tracking code is served

### Exclude a Post from MonsterInsights Tracking
1. Call `update_post` with the post ID and `meta: { _monsterinsights_skip_tracking: '1' }`

## Important Notes
- GA4 measurement IDs follow format `G-XXXXXXXXXX`; GTM container IDs follow `GTM-XXXXXX`
- When GTM is active, individual tracking pixels are typically managed inside GTM, not via separate plugins
- Most analytics plugins exclude logged-in admins/editors from tracking by default
- WooCommerce e-commerce tracking requires explicit integration toggles in each plugin — not automatic
- Site Kit requires Google OAuth connection — Wally can read settings but cannot initiate the OAuth flow
- PixelYourSite has built-in GDPR consent mode — check `pys_core` settings for consent configuration
- Do NOT expose API keys or access tokens (e.g., Site Kit credentials, Facebook access tokens)
