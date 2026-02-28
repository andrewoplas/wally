## WordPress Security Plugins

### Wordfence
- **Settings**: wp_options keys prefixed `wordfence*` and `wf*`. Configuration stored via `wfConfig::get($key)` and `wfConfig::set($key, $value)`.
- **Key options**:
  - `wf_alertEmails` — email addresses for security alerts
  - `wf_loginSec_maxFailures` — max failed login attempts before lockout
  - `wf_loginSec_lockoutMins` — lockout duration in minutes
  - `wf_loginSec_countFailMins` — time window for counting failures
  - `wordfenceActivated` — plugin activation status
  - `wf_scheduledScans` — cron-based scan schedule
- **Custom tables**: `{prefix}wfHits` (traffic log), `{prefix}wfStatus` (scan status), `{prefix}wfBlockedIPLog` (blocked IPs), `{prefix}wfConfig` (main config), `{prefix}wfIssues` (scan findings), `{prefix}wfLocs` (location data), `{prefix}wfLogins` (login attempts), `{prefix}wfKnownFileList` (file integrity baseline).
- **Features**: Web Application Firewall (WAF), malware scanner, login security (2FA, CAPTCHA, rate limiting), country blocking (Premium), real-time IP blocklist (Premium).
- **Hooks**: `wordfence_scan_complete`, `wordfence_security_event`, `wordfence_ls_require_captcha`.
- **Firewall mode**: Extended protection requires `wordfence-waf.php` auto-prepended via .user.ini or .htaccess.

### Sucuri Security
- **Settings**: wp_options keys prefixed `sucuri_*`. Access via `SucuriScanOption::get_option($key)`.
- **Key options**:
  - `sucuri_apikey` — API key for Sucuri cloud service
  - `sucuri_addr_header` — HTTP header for real IP detection
  - `sucuri_revproxy` — reverse proxy detection enabled
  - `sucuri_audit_report` — enable/disable audit logging
- **Features**: File integrity monitoring (compares core files against WordPress.org checksums), security activity audit log, security hardening (disable editor, block PHP in uploads), cloud WAF (Pro — DNS-level firewall), post-hack tools.
- **Audit log**: Stored in wp_options as `sucuri_integrity_*` keys and optionally sent to Sucuri cloud API.
- **Functions**: `SucuriScan::get_options()`, `SucuriScanFileInfo::get_file_checksum()`.
- **Hardening actions**: Remove readme.html, disable file editor, block PHP in wp-content/uploads, verify WordPress version.

### iThemes Security / Solid Security
- **Settings**: wp_options key `itsec-storage` (serialized). Modules have individual keys: `itsec_active_modules`, `itsec_lockout`, `itsec_log`.
- **Custom tables**: `{prefix}itsec_logs` (security events), `{prefix}itsec_lockouts` (active lockouts), `{prefix}itsec_temp` (temporary data).
- **Features**: Brute force protection, file change detection, 2FA, database prefix change, security dashboard, trusted devices, password requirements.
- **Functions**: `ITSEC_Modules::get_setting($module, $key)`, `ITSEC_Modules::set_setting($module, $key, $value)`.

### Common Security Settings Across Plugins
- **Login limiting**: All plugins offer brute force protection — max attempts, lockout duration, progressive delays.
- **Two-factor authentication**: TOTP-based 2FA for wp-admin login.
- **File change detection**: Monitor wp-includes/ and wp-admin/ for unauthorized modifications.
- **IP blocking**: Manual blocklists and automatic blocking on suspicious activity.
- **Security headers**: X-Frame-Options, X-Content-Type-Options, Strict-Transport-Security, Content-Security-Policy, Referrer-Policy, Permissions-Policy.

### Two-Factor
- **Purpose**: Lightweight two-factor authentication plugin maintained by WordPress core contributors. Adds 2FA to the WordPress login flow without extra security features (no firewall, no scanning — just 2FA).
- **User meta keys** (per-user settings):
  - `_two_factor_enabled_providers` — serialized array of enabled 2FA provider class names for the user
  - `_two_factor_provider` — primary 2FA provider class name
  - `_two_factor_totp_key` — TOTP secret key (for Google Authenticator / Authy)
  - `_two_factor_backup_codes` — serialized array of hashed backup codes
  - `_two_factor_email_token` — current email-based token (temporary)
- **Available Providers**:
  - `Two_Factor_Totp` — Time-based One-Time Password (Google Authenticator, Authy, etc.)
  - `Two_Factor_FIDO_U2F` — FIDO Universal 2nd Factor (hardware security keys)
  - `Two_Factor_Email` — Email-based verification codes
  - `Two_Factor_Backup_Codes` — One-time-use backup codes (recommended as fallback)
  - `Two_Factor_Dummy` — for development/testing only
- **Key Functions**:
  ```php
  Two_Factor_Core::is_user_using_two_factor( $user_id )  // Check if user has 2FA enabled
  Two_Factor_Core::get_enabled_providers_for_user( $user )  // Get active providers for user
  Two_Factor_Core::get_primary_provider_for_user( $user_id )  // Get primary provider
  Two_Factor_Core::get_available_providers_for_user( $user )  // All configured providers
  ```
- **Hooks**:
  ```php
  // Filter available 2FA providers (add/remove providers)
  add_filter( 'two_factor_providers', function( $providers ) { return $providers; } );

  // After successful 2FA authentication
  do_action( 'two_factor_user_authenticated', $user );

  // Customize 2FA prompt page
  add_action( 'two_factor_user_options', function( $user ) {} );

  // Filter whether 2FA is enabled for user
  add_filter( 'two_factor_enabled_providers_for_user', function( $providers, $user_id ) { return $providers; }, 10, 2 );
  ```
- **Login flow**: After username/password validation, if user has 2FA enabled, WordPress redirects to an interim page (`wp-login.php?action=validate_2fa`) where the user enters their 2FA code. On success, the standard WordPress login cookie is set.
- **Per-user configuration**: Each user manages their own 2FA settings under Users > Profile. Administrators cannot force 2FA on users with this plugin alone (use a complementary plugin for enforcement).
- **Detection**:
  ```php
  class_exists( 'Two_Factor_Core' ) // true if Two-Factor is active
  ```

### Security Optimizer by SiteGround (SG Security)
- **Purpose**: SiteGround's security plugin. Despite being made by SiteGround, it works on any WordPress host. Provides login protection, 2FA, activity logging, and post-hack recovery tools.
- **Settings**: Stored in wp_options with `sg_security_` prefix. Key options:
  - `sg_security_login_access` — login access settings (whitelist/blacklist IPs)
  - `sg_security_login_attempts` — limit login attempts configuration
  - `sg_security_login_url` — custom login URL slug (changes wp-login.php URL)
  - `sg_security_2fa` — two-factor authentication enabled (1/0)
  - `sg_security_disable_editors` — disable theme/plugin file editors (1/0)
  - `sg_security_disable_xml_rpc` — disable XML-RPC (1/0)
  - `sg_security_disable_feed` — disable RSS/Atom feeds (1/0)
  - `sg_security_xss_protection` — add X-XSS-Protection header (1/0)
  - `sg_security_delete_readme` — delete readme.html from root (1/0)
  - `sg_security_lock_system_folders` — block PHP execution in system folders (1/0)
  - `sg_security_disable_usernames_api` — disable REST API user enumeration (1/0)
  - `sg_security_hsts` — enable HSTS header (1/0)
- **Features**:
  - **Login protection**: Limit login attempts, custom login URL, disable common usernames, CAPTCHA
  - **Two-factor authentication**: TOTP-based (Google Authenticator). Enabled per-user. User meta: `sg_security_2fa_secret`, `sg_security_2fa_configured`
  - **Activity log**: Records login attempts, plugin/theme changes, post edits, settings changes, user creation/deletion
  - **Post-hack actions**: Force password reset for all users, log out all users, reinstall all free plugins
- **Database table**: `{prefix}sg_security_log` — activity log table. Columns: `id`, `visitor_id`, `type` (login, settings, post, plugin, theme, user, etc.), `action`, `description`, `ip`, `hostname`, `code`, `created_at`, `object_id`, `visitor_type`.
- **Custom login URL**: When `sg_security_login_url` is set (e.g., "my-login"), the standard `/wp-login.php` returns a 404 and users must log in at `/my-login/`. Important to note when modifying login URLs programmatically.
- **Hooks**:
  ```php
  // After activity is logged
  do_action( 'sg_security_after_log_event', $type, $action, $description );

  // Filter whether to block a login attempt
  add_filter( 'sg_security_block_login', function( $block, $ip ) { return $block; }, 10, 2 );
  ```
- **Detection**:
  ```php
  class_exists( 'SG_Security' ) // true if SG Security is active
  defined( 'SG_SECURITY_VERSION' ) // alternative check
  ```

### Important Security Notes
- Never expose security plugin configuration, API keys, or firewall rules through the assistant.
- Never disable security features (WAF, login protection) programmatically without explicit user confirmation.
- Security plugin tables contain sensitive data (IP logs, blocked users) — treat as confidential.
- Firewall drop-in files (.user.ini, wordfence-waf.php) are loaded before WordPress — do not modify them.
