# Niche & Utility Plugins

## When to Use
- User mentions NPS Survey, WC Admin Email, CI HUB, KB Vector, ZipWP, OTGS Installer, Hello Elementor, or Object Cache
- User asks about surveys, WooCommerce email customization, DAM integration, SVG support, AI site generation, WPML updates, or object caching
- User wants to check settings for any of these utility plugins

## Available Tools
- `list_plugins` — detect which utility plugin is active
- `get_option` — read plugin settings
- `update_option` — change plugin settings (requires confirmation)
- `get_site_info` — check active theme (for Hello Elementor detection)

## Workflows

### Detect Active Plugin
1. Call `list_plugins`
2. Look for: `nps-survey`, `wc-admin-email`, `ci-hub-connector`, `kb-vector`, `kb-custom-svg`, `zipwp`, `otgs-installer-plugin`, `objectcache`

### NPS Survey — Read Settings
1. Call `get_option` with key `nps_survey_settings`
2. Key settings: `enabled`, `question`, `display_frequency`, `display_delay`, `position`

### WC Admin Email — Read Settings
1. Call `get_option` with key `wc_admin_email_settings`
2. Key settings: `custom_recipients`, `disable_emails`, `custom_subjects`
3. Requires WooCommerce to be active

### KB Vector / KB Custom SVG — Read Settings
1. Call `get_option` with key `kb_vector_settings` or `kb_custom_svg_settings`
2. KB Custom SVG settings: `enabled` (SVG uploads), `sanitize` (sanitize on upload), `allowed_roles`
3. Part of the Kadence ecosystem — works with Kadence Blocks

### ZipWP — Read Settings
1. Call `get_option` with key `zipwp_settings`
2. Key settings: `site_type`, `site_description`, `onboarding_complete`
3. Do NOT expose `api_key`

### Object Cache — Read Settings
1. Call `get_option` with key `objectcache_settings`
2. Key settings: `backend` (redis/memcached/apcu), `host`, `port`
3. Do NOT expose `password`
4. Check if object cache is active: `get_site_info` shows object cache status

### Check Active Theme (Hello Elementor)
1. Call `get_site_info` to see the active theme
2. Hello Elementor is a minimal blank-canvas theme designed for Elementor page builder

### OTGS Installer — Check WPML License
1. Call `get_option` with key `otgs_installer_subscription` for subscription status
2. Do NOT expose license/site keys

## Important Notes
- NPS Survey responses are in a custom database table — not accessible via Wally tools
- WC Admin Email requires WooCommerce — check `list_plugins` for WooCommerce first
- CI HUB requires an external DAM subscription and API connectivity — Wally cannot manage DAM assets
- ZipWP and ZipWP Images require external API keys — Wally can read settings but not trigger site generation
- OTGS Installer manages WPML license and updates — license keys are sensitive, do NOT expose
- Object Cache requires a running Redis/Memcached server — if not available, WordPress falls back gracefully
- Hello Elementor is a theme, not a plugin — detected via `get_site_info`, not `list_plugins`
- For configuring surveys, email templates, DAM connections, or cache backends, guide user to each plugin's admin page
