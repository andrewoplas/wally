## WordPress Email Marketing & Popup Plugins

### MC4WP: Mailchimp for WP
- **Settings**: wp_options key `mc4wp` (serialized). Sub-keys: `api_key`, `list_id` (default audience).
- **Forms**: Custom post type `mc4wp-form`. Form HTML in `post_content`, config in `mc4wp_form_settings` postmeta (serialized — lists, double_optin, update_existing, redirect).
- **Shortcode**: `[mc4wp_form id="123"]`.
- **Functions**: `mc4wp_get_form($id)` — returns MC4WP_Form object, `mc4wp_show_form($id)` — renders form.
- **Hooks**: `mc4wp_form_subscribed` ($form, $subscriber, $request) — fires on successful subscribe, `mc4wp_form_error` ($form, $error_code), `mc4wp_integration_*` (integrations with other form plugins).
- **Integrations**: Built-in integration with WooCommerce checkout, CF7, Gravity Forms, WPForms, comment form, registration form. Enabled via `mc4wp_integrations` option.
- **List cache**: Mailchimp audience data cached in transient `mc4wp_mailchimp_lists`.
- **Detection**: `defined('MC4WP_VERSION')`.

### OptinMonster
- **Settings**: wp_options key `optin_monster_api` (serialized). Sub-keys: `api_key` (REST API key), `accountId`.
- **Campaigns**: Stored remotely in OptinMonster cloud, loaded via embed script. Local mapping in `optin_monster_ids` option (campaign slugs to IDs).
- **Campaign types**: popup (lightbox), fullscreen, floating-bar, inline, slide-in, sidebar/widget.
- **Page-level targeting**: Configured in OptinMonster cloud dashboard, not stored in WordPress.
- **WooCommerce integration**: Cart abandonment triggers, product page targeting, revenue attribution.
- **Functions**: `OMAPI::get_instance()`, `OMAPI_Output` class handles campaign rendering on frontend.
- **Detection**: `defined('OMAPI_VERSION')` or `class_exists('OMAPI')`.

### Popup Maker
- **Post types**: `popup` (stores popup content and settings), `popup_theme` (stores popup styling).
- **Popup settings**: `popup_settings` postmeta (serialized). Includes:
  - `triggers` — array of trigger configs: `click` (CSS selector), `time_delay` (ms), `exit_intent`, `scroll` (percentage), `form_submission`
  - `cookies` — auto-set cookies on action to prevent re-display (name, time, path, event)
  - `conditions` — page targeting rules (is_front_page, is_single, is_page with IDs, etc.)
  - `display` — animation, position, size, overlay settings
- **Shortcode**: `[popup id="123"]Trigger Text[/popup]` — click-triggered popup. Auto-open popups use time_delay or other triggers without shortcode.
- **Plugin settings**: wp_options key `popmake_settings` (serialized).
- **Functions**: `pum_get_popup($id)` — returns PUM_Popup object.
- **Hooks**: `pum_popup_before_open`, `pum_popup_after_close`, `pum_sub_form_submission`.
- **JavaScript API**: `PUM.open(popupID)`, `PUM.close(popupID)`, `PUM.getPopup(popupID)` — for programmatic control.
- **Detection**: `defined('POPMAKE_VERSION')` or `class_exists('Popup_Maker')`.

### Common Patterns
- Email marketing plugins store API keys in wp_options and forms/campaigns as custom post types or in cloud.
- Display methods: shortcodes for inline placement, auto-embed via triggers (time delay, exit intent, scroll) for popups.
- Conversion tracking uses cookies and JavaScript events. Popup plugins set cookies to suppress re-display after conversion or dismissal.
- Integration with form plugins (CF7, Gravity Forms, WPForms) allows subscribe-on-submit workflows without dedicated optin forms.
