# Email Marketing & Popup Plugins

## When to Use
- User mentions Mailchimp, MC4WP, OptinMonster, Popup Maker, email opt-in, or popups
- User wants to embed a signup form, manage popup settings, or check email integration
- User asks about newsletter forms or lead capture

## Available Tools
- `list_plugins` — detect which email/popup plugin is active
- `list_posts` — list MC4WP forms (`post_type: 'mc4wp-form'`) or Popup Maker popups (`post_type: 'popup'`)
- `get_post` — get form or popup content and settings
- `create_post` — create a new popup (basic content only)
- `update_post` — update form/popup content
- `search_content` — find pages containing email form or popup shortcodes
- `get_option` — read plugin settings
- `update_option` — change plugin settings (requires confirmation)

## Workflows

### Detect Active Plugin
1. Call `list_plugins`
2. Look for: `mailchimp-for-wp` (MC4WP), `optinmonster`, `popup-maker`

### MC4WP — List Signup Forms
1. Call `list_posts` with `post_type: 'mc4wp-form'`
2. Form HTML is stored in post content; config is in postmeta

### MC4WP — Read Settings
1. Call `get_option` with key `mc4wp`
2. Contains `api_key` (do NOT expose) and `list_id` (default audience)
3. Call `get_option` with key `mc4wp_integrations` for integration settings

### MC4WP — Embed Form in a Page
1. Call `update_post` on the target page, adding `[mc4wp_form id="123"]` to the content

### Popup Maker — List Popups
1. Call `list_posts` with `post_type: 'popup'`
2. Popup settings (triggers, cookies, conditions) are in postmeta `popup_settings`

### Popup Maker — Read Settings
1. Call `get_option` with key `popmake_settings`

### OptinMonster — Read Settings
1. Call `get_option` with key `optin_monster_api`
2. Contains `api_key` (do NOT expose) and `accountId`
3. Campaigns are stored in OptinMonster's cloud, not locally

### Embed Shortcodes
1. Call `update_post` to add shortcodes to page content:
   - MC4WP: `[mc4wp_form id="123"]`
   - Popup Maker: `[popup id="123"]Trigger Text[/popup]` (click-triggered)

## Important Notes
- MC4WP API keys and OptinMonster API keys are sensitive — do NOT expose them to the user
- OptinMonster campaigns are managed in their cloud dashboard — Wally can only read local settings
- Popup Maker triggers (time delay, exit intent, scroll) are configured in popup postmeta — not editable via simple `update_post`
- For creating or editing form HTML, popup triggers, or campaign targeting, guide user to the plugin's admin page
- MC4WP integrates with CF7, Gravity Forms, WPForms, WooCommerce checkout — check `mc4wp_integrations` option
