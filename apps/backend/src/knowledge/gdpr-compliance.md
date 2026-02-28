## CookieYes (Cookie Law Info)

### Settings
- wp_options: `CookieLawInfo-0` through `CookieLawInfo-9` (individual settings), `cookielawinfo_options` (serialized)
- Cookie scanner results stored in `cookielawinfo_*` option keys
- Banner text, colors, and behavior configured via serialized options

### Cookie Categories
`necessary`, `functional`, `analytics`, `performance`, `advertisement`, `others`

### Consent Detection
```php
// Detection
class_exists('Cookie_Law_Info') // or defined('CLI_PLUGIN_DEVELOPMENT_MODE')
// JavaScript API
cli_show_cookiebar();
cli_accept_cookie();
window.cookielawinfo_get_categories(); // returns array of accepted categories
```

### Consent Storage
User consent stored in browser cookies: `CookieLawInfoConsent`, `cookielawinfo-checkbox-{category}` (set to "yes" or "no"). Optional server-side consent logging.

---

## Complianz (GDPR/CCPA)

### Settings
- wp_options: `cmplz_options` (main settings, serialized), `cmplz_banner_status`
- All options prefixed `cmplz_*`

### Cookie Categories
`functional`, `statistics`, `marketing`, `preferences`

### Database Tables
- `{prefix}cmplz_cookiebanner` — banner configurations (supports multiple banners)
- `{prefix}cmplz_cookies` — scanned/registered cookies

### Consent Detection
```php
// PHP
cmplz_has_consent('statistics'); // returns bool
cmplz_has_consent('marketing');
// Detection
defined('cmplz_premium') || class_exists('COMPLIANZ')
```

### Consent Storage
Browser cookies prefixed `cmplz_*`: `cmplz_functional`, `cmplz_statistics`, `cmplz_marketing`, `cmplz_preferences`. Values: "allow" or "deny".

### Hooks
- `cmplz_before_cookie_banner` — action before banner renders
- `cmplz_statistics` — action when statistics consent granted
- `cmplz_marketing` — action when marketing consent granted

### Geo-Based Detection
Complianz detects visitor region (EU/US/UK) and shows the appropriate banner type (GDPR opt-in for EU, CCPA opt-out for US). Region stored in `cmplz_region` cookie.

---

## Cookie Notice

### Settings
- wp_options: `cookie_notice_options` (serialized array)
- Key sub-keys: `position`, `message`, `button_text`, `button_class`, `accept_text`, `refuse_text`, `revoke_text`, `cookie_name`, `cookie_value`, `cookie_expiry`, `script_placement`

### Consent Model
Simple accept/refuse model (no granular categories). Single cookie stores consent status.

### Consent Detection
```php
// PHP
Cookie_Notice()->get_status(); // check if user accepted
// Detection
class_exists('Cookie_Notice')
```

### JavaScript
```js
// Check acceptance via cookie
document.cookie.includes('cn_cookies_accepted=true');
```

### Hooks
- `cn_cookie_notice_output` — filter to customize banner HTML output

---

## Common GDPR Patterns
- All plugins store consent in browser cookies — server-side consent logs are optional.
- Scripts conditionally loaded based on consent category (blocking `<script>` tags until consent).
- Most support **Google Consent Mode v2** integration (analytics_storage, ad_storage, ad_user_data, ad_personalization).
- After clearing cookies or cache, consent banners reappear for the user.
- Do not programmatically grant consent or disable consent banners without explicit user confirmation.
