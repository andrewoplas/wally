# WordPress Security Plugins

## When to Use
- User mentions Wordfence, Sucuri, iThemes Security, Solid Security, Two-Factor, or SG Security
- User asks about login protection, 2FA, firewall, malware scanning, or security hardening
- User wants to check or change security settings

## Available Tools
- `list_plugins` — detect which security plugin is active
- `get_option` — read security plugin settings
- `update_option` — change security settings (requires confirmation)
- `get_site_health` — check WordPress site health for security issues

## Workflows

### Detect Active Security Plugin
1. Call `list_plugins`
2. Look for: `wordfence`, `sucuri-scanner`, `better-wp-security` (iThemes/Solid), `two-factor`, `sg-security`

### Check Site Security Status
1. Call `get_site_health` for WordPress-level security checks
2. Call `list_plugins` to identify which security plugins are active and up-to-date

### Wordfence — Read Settings
1. Call `get_option` with keys prefixed `wf_*`:
   - `wf_alertEmails` — alert email addresses
   - `wf_loginSec_maxFailures` — max failed login attempts
   - `wf_loginSec_lockoutMins` — lockout duration
   - `wordfenceActivated` — activation status

### Sucuri — Read Settings
1. Call `get_option` with keys prefixed `sucuri_*`:
   - `sucuri_apikey` — API key (do NOT expose to user)
   - `sucuri_audit_report` — audit logging enabled

### iThemes / Solid Security — Read Settings
1. Call `get_option` with key `itsec-storage`
2. Module list: `get_option` with key `itsec_active_modules`

### SG Security — Read Settings
1. Call `get_option` with keys prefixed `sg_security_*`:
   - `sg_security_login_attempts` — login attempt limits
   - `sg_security_2fa` — 2FA enabled
   - `sg_security_login_url` — custom login URL
   - `sg_security_disable_xml_rpc` — XML-RPC disabled

### Update a Security Setting
1. Identify the correct option key for the plugin (see above)
2. Call `update_option` with the key and new value (requires confirmation)
3. Warn user about potential lockout risks when changing login protection settings

## Important Notes
- NEVER expose API keys, firewall rules, or sensitive security configuration to the user
- NEVER disable security features (WAF, login protection, 2FA) without explicit user confirmation and understanding of risks
- Security plugin custom tables contain sensitive data (IP logs, blocked IPs) — not accessible via Wally tools
- Firewall drop-in files (`.user.ini`, `wordfence-waf.php`) are loaded before WordPress — do not modify
- For malware scanning, firewall configuration, or IP blocking, guide user to the plugin's admin dashboard
- Two-Factor plugin is lightweight (2FA only, no firewall/scanning) — configured per-user under Users > Profile
- Changing `sg_security_login_url` changes the wp-login.php URL — warn user this affects all login access
