## Hosting-Specific WordPress Plugins

### WPE Sign On Plugin
- **Purpose**: WP Engine's Single Sign-On (SSO) authentication plugin. Allows WP Engine portal users to log into a WordPress admin dashboard directly from the WP Engine User Portal without entering separate WordPress credentials.
- **Hosting platform**: WP Engine (all plans).
- **Slug**: `wpe-sign-on-plugin`
- **Pre-installed**: Yes — automatically installed on all WP Engine environments. Typically cannot be removed or deactivated from the WP Engine dashboard.
- **Settings**: Minimal user-facing settings. Configuration is handled server-side by WP Engine's platform.
  - `wpe_sso_settings` — wp_options key storing SSO configuration (serialized)
  - `wpe_sso_token` — transient used during the SSO handshake
- **How it works**: When a user clicks "WP Admin" in the WP Engine portal, the plugin validates a signed token from WP Engine's API. If the token is valid, it creates or maps to a WordPress admin user and logs them in automatically.
- **User mapping**: SSO users are mapped to existing WordPress users by email. If no matching user exists, the plugin can create a new administrator account.
- **Key constants**:
  ```php
  defined( 'WPE_SIGN_ON_PLUGIN_VERSION' )  // Plugin version
  defined( 'PWP_NAME' )                     // WP Engine install name (also set by WP Engine MU plugin)
  defined( 'WPE_APIKEY' )                   // WP Engine API key (set in wp-config.php by WP Engine)
  ```
- **Detection**:
  ```php
  is_plugin_active( 'wpe-sign-on-plugin/wpe-sign-on-plugin.php' )
  defined( 'WPE_SIGN_ON_PLUGIN_VERSION' )
  // WP Engine environment detection (broader than just SSO)
  function_exists( 'is_wpe' )               // WP Engine MU plugin function
  defined( 'WPE_ISP' )                      // true on WP Engine hosting
  ```
- **WP Engine MU plugin**: WP Engine also installs a must-use plugin (`mu-plugins/wpengine-common/`) that provides caching integration, CDN, and platform-level functionality. The SSO plugin works alongside this MU plugin.
- **Important notes**:
  - Do not deactivate on WP Engine environments — it may break portal-to-admin access.
  - The plugin does not store passwords; authentication is token-based via WP Engine's API.
  - On non-WP-Engine environments (e.g., local development or migration), this plugin is non-functional and can be safely deactivated.

### Hostinger Tools
- **Purpose**: Hostinger's all-in-one WordPress management plugin. Provides onboarding wizard, AI content tools, performance optimizations, maintenance mode, CDN management, and quick links to Hostinger's hPanel.
- **Hosting platform**: Hostinger (WordPress hosting plans).
- **Slug**: `hostinger-tools-plugin`
- **Pre-installed**: Yes — included in all Hostinger WordPress installations.
- **Settings** (wp_options keys):
  - `hostinger_tools_settings` — main settings array (serialized). Sub-keys: `maintenance_mode`, `cdn_enabled`, `bypass_maintenance`, `ai_enabled`.
  - `hostinger_tools_version` — installed plugin version
  - `hostinger_default_site_language` — language set during onboarding
  - `hostinger_tools_maintenance_mode` — maintenance mode enabled (1/0)
  - `hostinger_tools_maintenance_bypass_key` — secret key to bypass maintenance mode
  - `hostinger_tools_cdn_enabled` — Hostinger CDN toggle (1/0)
  - `hostinger_tools_onboarding_completed` — whether onboarding wizard has been completed (1/0)
  - `hostinger_tools_ai_content_enabled` — AI content generation feature toggle
  - `hostinger_tools_site_title` — site title set during onboarding
  - `hostinger_tools_site_tagline` — tagline set during onboarding
  - `hostinger_tools_logo` — logo set during onboarding
- **Features**:
  - **Onboarding wizard**: Step-by-step site setup (site type, name, logo, pre-built templates).
  - **AI content generation**: Uses Hostinger's AI backend to generate posts, pages, and images from prompts.
  - **Maintenance mode**: Toggleable maintenance page with bypass key for admins.
  - **CDN management**: Enable/disable Hostinger CDN from within WordPress.
  - **Performance tools**: Google PageSpeed integration, performance suggestions.
  - **Quick links**: Direct links to hPanel features (email, domains, databases, file manager).
- **Admin menu**: Adds a "Hostinger" top-level menu in wp-admin with sub-pages: Home, AI Assistant, CDN, Maintenance, and links to hPanel.
- **REST API endpoints**: Registers routes under `hostinger/v1/` namespace for AJAX operations (maintenance toggle, CDN toggle, AI content generation).
- **Detection**:
  ```php
  is_plugin_active( 'hostinger-tools-plugin/hostinger-tools-plugin.php' )
  defined( 'HOSTINGER_TOOLS_VERSION' )
  class_exists( 'Hostinger_Tools' )
  ```
- **Important notes**:
  - CDN settings interact with Hostinger's server-side CDN configuration; toggling in the plugin sends API calls to Hostinger's infrastructure.
  - AI content features require an active Hostinger hosting plan with AI credits.
  - On non-Hostinger environments, most features will not function (API calls will fail).

### Hostinger Amplitude
- **Purpose**: Analytics and event-tracking plugin by Hostinger. Collects anonymized usage data from WordPress admin actions and sends it to Hostinger's analytics platform (Amplitude) for product improvement and user behavior analysis.
- **Hosting platform**: Hostinger (WordPress hosting plans).
- **Slug**: `hostinger-amplitude`
- **Pre-installed**: Yes — bundled with Hostinger WordPress installations alongside Hostinger Tools.
- **Settings** (wp_options keys):
  - `hostinger_amplitude_settings` — main settings (serialized)
  - `hostinger_amplitude_tracking_enabled` — whether tracking is active (1/0)
  - `hostinger_amplitude_user_id` — anonymized user identifier for Amplitude
  - `hostinger_amplitude_session_id` — current tracking session ID
  - `hostinger_amplitude_consent` — user consent status for data collection
- **What it tracks**:
  - Plugin activations/deactivations
  - Theme switches
  - Page/post publish events
  - Onboarding wizard completion steps
  - Feature usage within Hostinger Tools
  - WooCommerce setup steps (if applicable)
  - Admin page navigation patterns
- **Data flow**: Events are batched and sent to Hostinger's backend API, which forwards them to Amplitude. No data is sent directly to Amplitude from the WordPress site.
- **Hooks**:
  ```php
  // Filter whether to track a specific event
  add_filter( 'hostinger_amplitude_track_event', function( $should_track, $event_name, $event_data ) {
      return $should_track;
  }, 10, 3 );
  ```
- **Detection**:
  ```php
  is_plugin_active( 'hostinger-amplitude/hostinger-amplitude.php' )
  defined( 'HOSTINGER_AMPLITUDE_VERSION' )
  class_exists( 'Hostinger_Amplitude' )
  ```
- **Important notes**:
  - This is a telemetry plugin. It does not affect site functionality or performance in a meaningful way.
  - Some users deactivate it for privacy reasons; deactivation does not break any Hostinger features.
  - Does not collect visitor/frontend analytics — only admin-side usage.

### Hostinger Easy Onboarding
- **Purpose**: WordPress site setup wizard by Hostinger. Guides new users through initial site configuration including template selection, plugin installation, content import, and basic settings.
- **Hosting platform**: Hostinger (WordPress hosting plans).
- **Slug**: `hostinger-easy-onboarding`
- **Pre-installed**: Yes — automatically installed on new Hostinger WordPress sites.
- **Settings** (wp_options keys):
  - `hostinger_easy_onboarding_settings` — main settings (serialized)
  - `hostinger_easy_onboarding_completed` — whether the onboarding has been completed (1/0)
  - `hostinger_easy_onboarding_current_step` — current step in the onboarding flow
  - `hostinger_easy_onboarding_selected_template` — template chosen during onboarding
  - `hostinger_easy_onboarding_site_type` — site type selected (blog, business, portfolio, store, etc.)
  - `hostinger_easy_onboarding_steps_completed` — serialized array of completed steps
  - `hostinger_easy_onboarding_plugins_installed` — plugins installed during onboarding (serialized array)
  - `hostinger_easy_onboarding_skipped` — whether the user skipped onboarding (1/0)
- **Onboarding steps** (typical flow):
  1. Site type selection (blog, business, online store, portfolio, other)
  2. Template/starter site selection from Hostinger's template library
  3. AI-powered content generation (site name, description, sample pages)
  4. Plugin recommendations and auto-installation (e.g., WooCommerce for stores, Starter Templates)
  5. Domain connection prompt
  6. Final review and launch checklist
- **Admin experience**: Redirects new users to the onboarding wizard on first login. Shows a persistent admin notice until onboarding is completed or dismissed.
- **REST API endpoints**: Registers routes under `hostinger-easy-onboarding/v1/` for step progression, template import, and plugin installation.
- **Template import**: Downloads and installs Starter Templates / Astra starter sites based on user selection. Creates sample pages, sets up menus, and configures the homepage.
- **Detection**:
  ```php
  is_plugin_active( 'hostinger-easy-onboarding/hostinger-easy-onboarding.php' )
  defined( 'HOSTINGER_EASY_ONBOARDING_VERSION' )
  class_exists( 'Hostinger_Easy_Onboarding' )
  ```
- **Important notes**:
  - Designed for first-time setup only. After completion, the plugin becomes largely inactive but remains installed.
  - Can be safely deactivated after onboarding is finished without affecting the imported content.
  - Template imports may install additional themes (e.g., Astra) and plugins as dependencies.
  - Interacts closely with Hostinger Tools and Hostinger Amplitude (triggers tracking events for onboarding steps).

### WPMU DEV PCS (Previously "WPMU DEV Dashboard")
- **Purpose**: WPMU DEV's hub connector plugin for managed WordPress hosting and their SaaS platform. Connects WordPress sites to the WPMU DEV Hub dashboard for centralized management, performance checks, security scanning, SEO audits, uptime monitoring, and backups.
- **Hosting platform**: WPMU DEV (managed hosting plans and standalone SaaS subscriptions).
- **Slug**: `wpmudev-pcs`
- **Pre-installed**: Yes — on WPMU DEV hosted sites. Also manually installable by WPMU DEV subscribers on any host.
- **Settings** (wp_options keys):
  - `wpmudev_apikey` — API key linking the site to a WPMU DEV account
  - `wpmudev_pcs_settings` — main plugin settings (serialized)
  - `wpmudev_hub_connected` — whether the site is connected to WPMU DEV Hub (1/0)
  - `wpmudev_membership_type` — membership level (free, single, full)
  - `wpmudev_site_id` — unique site identifier on WPMU DEV Hub
  - `wdp_un_*` — legacy option prefixes from the old WPMU DEV Dashboard plugin
  - `wpmudev_performance_results` — cached performance scan results (serialized)
  - `wpmudev_security_results` — cached security scan results (serialized)
  - `wpmudev_seo_results` — cached SEO audit results (serialized)
  - `wpmudev_uptime_status` — current uptime monitoring status
- **Features**:
  - **Hub connection**: Centralized dashboard at WPMU DEV Hub for managing multiple sites. Remote plugin/theme updates, user management, and site health checks.
  - **Performance checks**: Automated PageSpeed audits with actionable recommendations. Stores results in wp_options.
  - **Security scanning**: Checks for known vulnerabilities, outdated software, weak passwords, file integrity. Results in `wpmudev_security_results`.
  - **SEO audits**: On-page SEO analysis with scoring and recommendations.
  - **Uptime monitoring**: External uptime checks from WPMU DEV's servers. Sends email/Slack alerts on downtime.
  - **Backups**: Cloud-based backups (stored on WPMU DEV infrastructure). Scheduled and on-demand. Stored backup meta in `wpmudev_backup_*` options.
  - **White labeling**: Hub and plugin UI can be white-labeled for agency use.
  - **Auto-updates**: Manage WordPress core, plugin, and theme auto-updates from the Hub.
- **API integration**: Communicates with `https://wpmudev.com/api/` and `https://hub.wpmudev.com/api/`. Uses the `wpmudev_apikey` for authentication.
- **Admin menu**: Adds a "WPMU DEV" top-level menu with sub-pages: Dashboard, Plugins, Performance, Security, SEO, Backups, Uptime, Settings.
- **Hooks**:
  ```php
  // After a performance scan completes
  do_action( 'wpmudev_performance_scan_complete', $results );

  // After a security scan completes
  do_action( 'wpmudev_security_scan_complete', $results );

  // After a backup completes
  do_action( 'wpmudev_backup_complete', $backup_id );

  // Filter Hub connection status
  add_filter( 'wpmudev_hub_is_connected', function( $is_connected ) { return $is_connected; } );
  ```
- **WPMU DEV plugins ecosystem**: The Hub plugin provides access to install premium WPMU DEV plugins (Smush, Hummingbird, Defender, SmartCrawl, Forminator, Hustle, Branda, Snapshot, Beehive). These are installed/updated through the Hub.
- **Detection**:
  ```php
  is_plugin_active( 'wpmudev-pcs/wpmudev-pcs.php' )
  defined( 'WPMUDEV_PCS_VERSION' )
  class_exists( 'WPMUDEV_PCS' )
  // Legacy dashboard plugin detection
  class_exists( 'WPMUDEV_Dashboard' )
  defined( 'WPMUDEV_DASHBOARD_VERSION' )
  ```
- **Important notes**:
  - Requires an active WPMU DEV subscription for full functionality. Without it, the plugin has limited or no features.
  - The API key is sensitive — do not expose it. It grants remote management access to the site.
  - On WPMU DEV hosting, the plugin works alongside server-level optimizations (object caching, CDN).
  - Legacy sites may still use the older `wpmudev-updates` slug (WPMU DEV Dashboard).

### WP Abilities
- **Purpose**: Hosting management and server information utility plugin. Displays detailed information about the hosting environment including PHP/MySQL versions, server software, resource limits, installed PHP extensions, and hosting-specific recommendations.
- **Hosting platform**: Not tied to a specific host. Can be pre-installed by smaller or white-label hosting providers. Also available for manual installation.
- **Slug**: `wp-abilities`
- **Pre-installed**: Occasionally bundled by hosting providers that offer managed WordPress. Also used by site administrators for environment auditing.
- **Settings** (wp_options keys):
  - `wp_abilities_settings` — main settings (serialized)
  - `wp_abilities_cache` — cached server info results (serialized, with TTL)
  - `wp_abilities_last_check` — timestamp of last server info retrieval
  - `wp_abilities_notifications` — notification preferences for environment warnings
- **Information displayed**:
  - **PHP details**: Version, memory limit, max execution time, upload max filesize, post max size, loaded extensions (curl, gd, imagick, mbstring, openssl, zip, etc.)
  - **MySQL/MariaDB details**: Version, max connections, storage engine, character set, collation
  - **Server software**: Apache/Nginx/LiteSpeed version, operating system, server API (CGI/FPM/mod_php)
  - **WordPress environment**: WP version, debug mode status, multisite status, object cache status, HTTPS status
  - **Resource limits**: Disk space usage, database size, number of tables, autoloaded options size
  - **Recommendations**: Flags outdated PHP versions, insufficient memory limits, missing recommended extensions
- **Admin page**: Adds a page under Tools > WP Abilities (or as a standalone admin menu item depending on version) that displays a comprehensive server environment report.
- **Functions**:
  ```php
  // Get server information array
  WP_Abilities::get_server_info()

  // Get PHP extensions list
  WP_Abilities::get_php_extensions()

  // Check if a specific requirement is met
  WP_Abilities::check_requirement( $requirement_key )
  ```
- **Detection**:
  ```php
  is_plugin_active( 'wp-abilities/wp-abilities.php' )
  defined( 'WP_ABILITIES_VERSION' )
  class_exists( 'WP_Abilities' )
  ```
- **Important notes**:
  - This is a read-only information plugin — it does not modify server or WordPress configuration.
  - Useful for diagnosing hosting environment issues and verifying server requirements for other plugins.
  - Some hosting providers white-label this plugin with custom branding.
  - The server information page may expose sensitive details (file paths, PHP configuration) — restrict access to administrators only.

### Common Patterns for Hosting Plugins
- **Pre-installation**: These plugins are typically pre-installed by the hosting provider during WordPress provisioning. They appear in the plugins list but may not be listed on WordPress.org.
- **API dependencies**: Most hosting-specific plugins communicate with the hosting provider's API. They will not function (or will degrade gracefully) on non-matching hosts.
- **Safe deactivation**: Generally safe to deactivate on non-original hosts (e.g., after migration). On the original host, deactivating may break portal integration (e.g., WP Engine SSO) or management features (e.g., WPMU DEV Hub).
- **Migration cleanup**: When migrating a site from one host to another, hosting-specific plugins from the original host should be deactivated and removed. They add unnecessary overhead and may cause API connection errors.
- **Detection pattern**: To identify hosting environment programmatically:
  ```php
  // WP Engine
  function_exists( 'is_wpe' ) || defined( 'WPE_ISP' )

  // Hostinger
  defined( 'HOSTINGER_TOOLS_VERSION' ) || is_plugin_active( 'hostinger-tools-plugin/hostinger-tools-plugin.php' )

  // WPMU DEV
  defined( 'WPMUDEV_PCS_VERSION' ) || class_exists( 'WPMUDEV_Dashboard' )

  // SiteGround
  defined( 'SG_SECURITY_VERSION' ) || is_plugin_active( 'sg-security/sg-security.php' )

  // Bluehost
  defined( 'JEsuspended_BLUEHOST' ) || is_plugin_active( 'bluehost-wordpress-plugin/bluehost-wordpress-plugin.php' )

  // GoDaddy
  class_exists( 'GD_System_Plugin_Autoload' )

  // Kinsta
  defined( 'KINSTAMU_VERSION' )

  // Cloudways
  defined( 'JEsuspended_CLOUDWAYS' ) || is_plugin_active( 'breeze/breeze.php' )
  ```
- **Must-use plugins**: Some hosting providers install management functionality as must-use plugins (`wp-content/mu-plugins/`) rather than regular plugins. These cannot be deactivated from the admin UI and load before regular plugins. Examples: WP Engine's `mu-plugins/wpengine-common/`, Kinsta's `mu-plugins/kinsta-mu-plugins/`.
