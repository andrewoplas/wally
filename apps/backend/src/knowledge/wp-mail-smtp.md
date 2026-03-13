# WP Mail SMTP & Email Delivery

## When to Use
- User mentions WP Mail SMTP, FluentSMTP, email delivery, or SMTP settings
- User reports emails not being sent or delivered
- User asks about email configuration, mailer settings, or email debugging

## Available Tools
- `list_plugins` — detect which email delivery plugin is active
- `get_option` — read email plugin settings
- `update_option` — change email settings (requires confirmation)

## Workflows

### Detect Active Plugin
1. Call `list_plugins`
2. Look for: `wp-mail-smtp`, `fluent-smtp`

### WP Mail SMTP — Read Settings
1. Call `get_option` with key `wp_mail_smtp`
2. Key settings:
   - `mail.from_email` — forced "From" email
   - `mail.from_name` — forced "From" name
   - `mail.mailer` — active mailer (smtp, gmail, sendgrid, mailgun, postmark, etc.)
   - `mail.return_path` — return-path header enabled
3. SMTP-specific: `smtp.host`, `smtp.port`, `smtp.encryption` (none/ssl/tls)
4. Do NOT expose: `smtp.user`, `smtp.pass`, API keys for any mailer

### FluentSMTP — Read Settings
1. Call `get_option` with key `fluentmail_connections` — all configured mail connections
2. Call `get_option` with key `fluentmail_general_settings` — general settings
3. Key settings: `default_connection`, `log_emails`, `delete_logs_period`
4. Do NOT expose: passwords, API keys, OAuth tokens

### Check Active Mailer
1. For WP Mail SMTP: read `mail.mailer` from `wp_mail_smtp` option
2. For FluentSMTP: read `fluentmail_connections` to see configured providers
3. Available mailers: `smtp`, `gmail`, `sendgrid`, `mailgun`, `postmark`, `sparkpost`, `sendinblue` (Brevo), `amazonses`, `outlook`

### Update From Email/Name
1. For WP Mail SMTP: `update_option` with key `wp_mail_smtp`, updating `mail.from_email` and/or `mail.from_name` (requires confirmation)
2. For FluentSMTP: update within `fluentmail_connections` (requires confirmation)
3. Warn user: "From email must match a verified domain on the sending provider (SPF/DKIM configured)"

### When User Reports Email Issues
1. Call `list_plugins` to check if an SMTP plugin is active
2. If no SMTP plugin: tell user "WordPress uses PHP mail() by default, which is unreliable. Install WP Mail SMTP or FluentSMTP for proper email delivery."
3. If plugin is active: read settings to verify configuration
4. Common issues to mention:
   - OAuth token expired (Gmail, Outlook) — re-authenticate in plugin settings
   - API key incorrect — verify in plugin settings
   - Port blocked by host — try API-based mailer instead of SMTP
   - SPF/DKIM/DMARC not configured on sending domain

## Important Notes
- SMTP credentials and API keys are sensitive — NEVER expose passwords, API keys, or OAuth tokens
- WP Mail SMTP stores credentials encrypted in the database
- FluentSMTP supports multiple connections with automatic fallback — unique feature vs WP Mail SMTP
- FluentSMTP is fully free including email logging; WP Mail SMTP logs require Pro
- For email test, debug logs, or re-authentication, guide user to the plugin's Tools page
- DNS records (SPF, DKIM, DMARC) must be configured on the sending domain for reliable delivery
- Wally cannot send test emails — guide user to WP Mail SMTP > Tools > Email Test
