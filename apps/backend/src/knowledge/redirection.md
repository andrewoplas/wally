# Redirection & Link Management

## When to Use
- User asks about URL redirects (301, 302) or the Redirection plugin
- User wants to check if redirects are set up for changed URLs
- User asks about 404 errors or broken links
- User wants to check Redirection plugin or Broken Link Checker settings

## Available Tools
- `list_plugins` — check if Redirection or Broken Link Checker is installed
- `get_option` — read plugin settings
- `install_plugin` — install Redirection from WordPress.org (requires confirmation)
- `activate_plugin` — activate Redirection (requires confirmation)

## Workflows

### Check if Redirection Plugin is Active
1. Call `list_plugins`
2. Look for `redirection`

### Check Redirection Settings
1. Call `get_option` with key `red_options`
2. Key fields: `expire_redirect` (log retention days), `expire_404` (404 log retention), `monitor_post` (auto-redirect on slug change)

### Install the Redirection Plugin
1. Call `install_plugin` with `slug: 'redirection'` (requires confirmation)
2. Call `activate_plugin` with `slug: 'redirection'` (requires confirmation)
3. Tell the user: "Redirection is installed. Go to **Tools > Redirection** to create redirects and configure settings."

### Check if Broken Link Checker is Active
1. Call `list_plugins`
2. Look for `broken-link-checker`

### Check Broken Link Checker Settings
1. Call `get_option` with key `wsblc_options`
2. Key fields: `check_threshold` (recheck interval hours), `notification_email_address`, `exclusion_list`

## Important Notes
- Wally cannot create, edit, or delete redirects — guide user to **Tools > Redirection** in WordPress admin
- Wally cannot view redirect logs or 404 logs — guide user to the Redirection admin page
- When a user changes a post/page slug, recommend setting up a 301 redirect from the old URL to the new one to preserve SEO
- Redirection supports auto-monitoring: when enabled, it auto-creates 301 redirects on slug changes (configure via `monitor_post` option)
- Redirect types: 301 (permanent), 302 (temporary), 307 (temporary, preserve method), 410 (gone)
- Broken Link Checker runs on WP-Cron and can be resource-intensive on large sites — guide user to adjust settings if performance is impacted
- For SEO plugin built-in redirects: Yoast Premium and Rank Math both have redirect managers — check those before installing a separate plugin
