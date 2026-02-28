## WP Mail SMTP

### Purpose
Routes WordPress emails through proper SMTP or third-party email API providers instead of PHP's mail() function. Hooks into wp_mail() transparently.

### Settings Storage
All settings stored in wp_options as serialized array under key `wp_mail_smtp`. Key sub-keys:
- `mail.from_email` — forced "From" email address
- `mail.from_name` — forced "From" name
- `mail.mailer` — active mailer slug (see list below)
- `mail.return_path` — whether to set return-path header to match from_email
- `smtp.host`, `smtp.port`, `smtp.encryption` (none/ssl/tls), `smtp.auth` (true/false)
- `smtp.user`, `smtp.pass` — SMTP credentials (encrypted in DB)

### Available Mailers
`mail` (PHP default), `smtp`, `gmail`, `outlook`, `amazonses`, `sendgrid`, `mailgun`, `postmark`, `sparkpost`, `sendinblue` (Brevo), `zoho`. Each mailer stores its own sub-keys under the main option (e.g., `gmail.client_id`, `sendgrid.api_key`, `mailgun.api_key`, `mailgun.domain`, `mailgun.region`).

### How It Works
Replaces PHPMailer configuration via `wp_mail` filter and phpmailer_init action. Never call PHPMailer directly — always use `wp_mail()`.
- `wp_mail($to, $subject, $message, $headers, $attachments)` — core function, unchanged API
- Plugin intercepts via `phpmailer_init` hook to reconfigure transport

### Email Logging (Pro)
Table: `{prefix}wfsmtp_emails_log` — stores all sent/failed emails with to, subject, headers, status, date, mailer used, error message. Query via WPMailSMTP\Pro\Emails\Logs\Logs class.

### Key Hooks
- `wp_mail_smtp_providers_mailer_get_body` — filter outgoing email body per mailer
- `wp_mail_smtp_mail_catcher_send_before` — fires before email is sent
- `wp_mail_smtp_mail_catcher_send_after` — fires after email attempt (success or failure)
- `wp_mail_smtp_options_set` — fires when settings are saved
- `wp_mail_smtp_admin_area_enqueue_assets` — enqueue custom assets on plugin pages

### Debugging
- `wp_mail_smtp_debug` option stores last debug output
- Admin connection test: wp-admin > WP Mail SMTP > Tools > Email Test
- Debug events log accessible under WP Mail SMTP > Tools > Debug Events

### Retrieving Settings Programmatically
```php
$options = wp_mail_smtp()->get_providers()->get_options_all();
// or
$mailer = \WPMailSMTP\Options::init()->get('mail', 'mailer');
$from   = \WPMailSMTP\Options::init()->get('mail', 'from_email');
```

### Common Issues
- Authentication failures — OAuth token expired (Gmail, Outlook) or incorrect API key
- "From" email mismatch — sending provider rejects emails where from_email domain has no SPF/DKIM records
- DNS records — SPF, DKIM, and DMARC must be configured on sending domain for deliverability
- Port blocked — hosting providers may block port 25/465/587; use API-based mailers instead

---

## FluentSMTP

### Purpose
Free, open-source WordPress SMTP and email delivery plugin. Supports multiple mail connections with automatic fallback. Unlike WP Mail SMTP (freemium), FluentSMTP offers all features for free including email logging.

### Settings Storage
All connection settings stored in wp_options as serialized array under key `fluentmail_connections`. Each connection keyed by sender email address. Additional options:
- `fluentmail_connections` — array of configured mailer connections (keyed by from_email)
- `fluentmail_general_settings` — general settings including `default_connection`, `log_emails` (yes/no), `log_saved_interval_days`, `delete_logs_period`
- `fluentmail_email_identity` — forced sender identity settings
- `fluentmail_is_installed` — installation flag

### Available Mailers (Connections)
`smtp`, `ses` (Amazon SES), `sendgrid`, `mailgun`, `postmark`, `gmail` (Gmail API / OAuth), `outlook` (Microsoft 365 / OAuth), `sparkpost`, `elasticmail`, `sendinblue` (Brevo), `pepipost`. Each connection stores provider-specific credentials:
- SMTP: `host`, `port`, `auth` (yes/no), `username`, `password`, `encryption` (none/ssl/tls)
- SendGrid: `api_key`
- Mailgun: `api_key`, `domain_name`, `region` (us/eu)
- Amazon SES: `access_key`, `secret_key`, `region`
- Postmark: `server_api_token`
- Gmail API: `client_id`, `client_secret`, `auth_token` (OAuth)
- Outlook: `client_id`, `client_secret`, `auth_token` (OAuth)

### Multiple Connections & Fallback
Unique feature: configure multiple SMTP/API connections. If the primary connection fails, FluentSMTP automatically tries the next connection. Connections can be mapped to specific sender email addresses — emails from `support@example.com` use one connection while `noreply@example.com` uses another.

### Email Logging
All sent emails logged in custom database table `{prefix}fsmtp_email_logs`. Key columns:
- `id`, `to` (JSON array), `from`, `subject`, `body`, `status` (sent/failed/pending)
- `response` (JSON — provider response), `extra` (JSON — headers, attachments info)
- `created_at`, `updated_at`, `retries`
Logs browsable in wp-admin > FluentSMTP > Email Logs with filtering by status, date, and search.

### Dashboard & Reporting
Built-in dashboard shows delivery statistics: total sent, failed, last 7 days chart. No external service or Pro version required.

### Key Hooks
```php
// Before email is sent
add_action( 'fluentmail_before_email_send', function( $phpMailer ) {} );

// After email is sent (includes status)
add_action( 'fluentmail_after_email_send', function( $logData, $response ) {}, 10, 2 );

// After email log saved
add_action( 'fluentmail_email_logged', function( $logId, $logData ) {}, 10, 2 );

// Modify email before sending
add_filter( 'fluentmail_email_data', function( $data ) { return $data; } );

// Filter connections
add_filter( 'fluentmail_connections', function( $connections ) { return $connections; } );
```

### WP-CLI Support
```bash
wp fluentmail test --to=user@example.com     # Send test email
wp fluentmail info                            # Show active connection info
```

### Retrieving Settings Programmatically
```php
$settings = get_option('fluentmail_connections');
$general  = get_option('fluentmail_general_settings');
// or via FluentMail API
$manager = FluentMail\App\Services\Mailer\Manager::getInstance();
```

### Detection
```php
defined('FLUENTMAIL')               // true if FluentSMTP is active
defined('FLUENTMAIL_PLUGIN_VERSION') // version constant
```

### Key Differences from WP Mail SMTP
- Completely free (no Pro version) — all features including email logging included
- Multiple connections with automatic fallback (WP Mail SMTP: single connection in free, backup in Pro)
- Open source on GitHub
- Lighter weight with fewer admin pages
- No email resend from logs (can be added via hooks)
