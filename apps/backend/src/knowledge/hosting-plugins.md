# Hosting-Specific WordPress Plugins

## When to Use
- User mentions WP Engine, Hostinger, WPMU DEV, SiteGround, or hosting-specific plugins
- User asks about hosting management plugins, SSO, maintenance mode, or hosting CDN
- User wants to clean up hosting plugins after a site migration
- User asks about pre-installed plugins they didn't install

## Available Tools
- `list_plugins` — detect hosting-specific plugins
- `get_option` — read hosting plugin settings
- `get_site_info` — check hosting environment details
- `deactivate_plugin` — deactivate hosting plugins after migration (requires confirmation)
- `delete_plugin` — remove unnecessary hosting plugins (requires confirmation)

## Workflows

### Identify Hosting Environment
1. Call `list_plugins`
2. Look for hosting-specific plugins:
   - WP Engine: `wpe-sign-on-plugin`
   - Hostinger: `hostinger-tools-plugin`, `hostinger-amplitude`, `hostinger-easy-onboarding`
   - WPMU DEV: `wpmudev-pcs` or `wpmudev-updates`
   - SiteGround: `sg-security`, `sg-cachepress`

### Hostinger — Check Maintenance Mode
1. Call `get_option` with key `hostinger_tools_maintenance_mode`
2. Value `1` = maintenance mode ON, `0` = OFF

### Hostinger — Read Settings
1. Call `get_option` with key `hostinger_tools_settings`
2. Sub-keys: `maintenance_mode`, `cdn_enabled`, `ai_enabled`
3. Call `get_option` with key `hostinger_tools_cdn_enabled` — CDN status
4. Call `get_option` with key `hostinger_easy_onboarding_completed` — onboarding status

### WPMU DEV — Check Hub Connection
1. Call `get_option` with key `wpmudev_hub_connected` — connected (1/0)
2. Call `get_option` with key `wpmudev_membership_type` — subscription level

### WPMU DEV — Check Scan Results
1. Call `get_option` with key `wpmudev_performance_results` — performance audit
2. Call `get_option` with key `wpmudev_security_results` — security scan
3. Call `get_option` with key `wpmudev_seo_results` — SEO audit

### Post-Migration Cleanup
1. Call `list_plugins` to identify hosting plugins from the old host
2. For each old-host plugin no longer needed:
   a. Call `deactivate_plugin` with the plugin slug (requires confirmation)
   b. Call `delete_plugin` with the plugin slug (requires confirmation)
3. Common cleanup targets after migration:
   - WP Engine SSO plugin on non-WP-Engine hosts
   - Hostinger plugins on non-Hostinger hosts
   - WPMU DEV dashboard on non-WPMU-DEV hosts

## Important Notes
- Hosting plugins communicate with their provider's API — they do NOT function on other hosts
- Do NOT deactivate WP Engine SSO (`wpe-sign-on-plugin`) on WP Engine — it breaks portal access
- Do NOT deactivate Hostinger Tools on Hostinger if user relies on CDN or maintenance mode
- WPMU DEV API key (`wpmudev_apikey`) is sensitive — do NOT expose it
- Hostinger Amplitude is telemetry only — safe to deactivate for privacy without breaking features
- Some hosting plugins are must-use (`wp-content/mu-plugins/`) and cannot be deactivated via Wally
- After migration, always confirm with user before removing hosting plugins — they may still need them
