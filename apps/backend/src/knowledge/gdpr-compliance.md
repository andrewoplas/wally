# GDPR & Privacy Compliance Plugins

## When to Use
- User mentions CookieYes, Complianz, Cookie Notice, GDPR, CCPA, or cookie consent
- User asks about cookie banners, consent management, or privacy compliance
- User wants to check or change cookie consent settings

## Available Tools
- `list_plugins` — detect which GDPR plugin is active
- `get_option` — read consent plugin settings
- `update_option` — change consent settings (requires confirmation)

## Workflows

### Detect Active Plugin
1. Call `list_plugins`
2. Look for: `cookie-law-info` (CookieYes), `complianz-gdpr`, `cookie-notice`

### CookieYes — Read Settings
1. Call `get_option` with key `cookielawinfo_options`
2. Individual settings also in `CookieLawInfo-0` through `CookieLawInfo-9`
3. Cookie categories: `necessary`, `functional`, `analytics`, `performance`, `advertisement`, `others`

### Complianz — Read Settings
1. Call `get_option` with key `cmplz_options`
2. Call `get_option` with key `cmplz_banner_status`
3. Cookie categories: `functional`, `statistics`, `marketing`, `preferences`
4. Complianz auto-detects visitor region (EU/US/UK) and shows the appropriate banner type

### Cookie Notice — Read Settings
1. Call `get_option` with key `cookie_notice_options`
2. Key settings: `position`, `message`, `button_text`, `accept_text`, `refuse_text`, `cookie_expiry`
3. Simple accept/refuse model — no granular cookie categories

### Update Consent Banner Settings
1. Identify the correct option key for the active plugin (see above)
2. Call `update_option` with key and new value (requires confirmation)
3. Warn user: "Changes to consent settings may affect compliance — review with your legal/privacy advisor."

### Check Google Consent Mode Integration
1. Call `get_option` with the plugin's settings key
2. Most GDPR plugins support Google Consent Mode v2 (`analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization`)

## Important Notes
- Do NOT programmatically grant consent or disable consent banners without explicit user confirmation
- Consent is stored in browser cookies — Wally has no access to visitor consent state
- All plugins conditionally block scripts until consent is given — disabling the banner may violate GDPR/CCPA
- CookieYes and Complianz offer granular per-category consent; Cookie Notice is simpler (accept/refuse only)
- Complianz has geo-based detection: GDPR opt-in for EU, CCPA opt-out for US — stored in `cmplz_region` cookie
- Cookie scanner results (what cookies the site sets) are stored in plugin-specific options — useful for auditing
- After changing consent settings, clear page cache so updated banner is served to visitors
- For banner design, cookie scanning, or compliance configuration, guide user to the plugin's admin page
