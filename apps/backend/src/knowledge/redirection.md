## WordPress Redirection & Link Management Plugins

### Redirection Plugin
- **Database tables**:
  - `{prefix}redirection_items` — redirect rules: `id`, `url` (source), `match_url`, `match_data`, `regex`, `action_type`, `action_code`, `action_data` (target URL), `group_id`, `position`, `status` (enabled/disabled), `last_count`, `last_access`
  - `{prefix}redirection_groups` — logical groups: `id`, `name`, `module_id` (1=WordPress, 2=Apache, 3=Nginx), `status`, `position`
  - `{prefix}redirection_logs` — redirect hit logs: `id`, `created`, `url`, `referrer`, `agent`, `redirection_id`, `ip`, `http_code`
  - `{prefix}redirection_404` — 404 error logs: `id`, `created`, `url`, `referrer`, `agent`, `ip`
- **Action types**: `url` (redirect to URL), `error` (return HTTP error), `random` (random target from group), `pass` (proxy pass-through to URL).
- **Match types**: `url` (exact URL), `login` (logged-in state), `referrer` (HTTP referrer), `agent` (user agent), `cookie`, `header`, `custom`, `ip`, `server`, `page` (page type).
- **Redirect codes**: 301 (permanent), 302 (temporary), 303 (see other), 304 (not modified), 307 (temporary, preserve method), 308 (permanent, preserve method), 410 (gone).
- **Settings**: wp_options key `red_options` (serialized). Key fields:
  - `expire_redirect` — redirect log retention in days (-1 = never delete)
  - `expire_404` — 404 log retention in days
  - `ip_logging` — `0` (full), `1` (anonymize), `2` (none)
  - `monitor_post` — post types monitored for URL changes (array)
  - `monitor_types` — what triggers monitoring (e.g., `post`, `page`, `trash`)
  - `associated_redirect` — group ID for auto-created redirects on slug changes
- **REST API**: `/wp-json/redirection/v1/`
  - `GET /redirect` — list redirects (params: `filterBy`, `orderby`, `direction`, `per_page`, `page`, `groupBy`)
  - `POST /redirect` — create redirect (body: `url`, `action_data.url`, `action_type`, `action_code`, `match_type`, `group_id`, `regex`)
  - `PUT /redirect/{id}` — update redirect
  - `DELETE /redirect/{id}` — delete redirect
  - `GET /log` — redirect logs
  - `GET /404` — 404 logs
  - `POST /import` — import redirects (CSV, JSON, htaccess, nginx)
  - `GET /export/{module}/{format}` — export (csv, json, apache, nginx)
- **Auto-monitoring**: When enabled, detects post/page URL slug changes on save and auto-creates 301 redirects from old URL to new URL. Configured via `monitor_post` and `associated_redirect` options.
- **Regex support**: Source URLs support regex with capture groups. Target uses `$1`, `$2` for backreferences. Example: source `/old/(.*)` → target `/new/$1`.
- **Functions**: `Red_Item::create($details)` — programmatic redirect creation. `Red_Item::get_by_id($id)` — fetch single redirect.

### Broken Link Checker
- **Purpose**: Scans posts, pages, comments, blogroll, and custom fields for broken links and missing images.
- **Database tables**:
  - `{prefix}blc_links` — all discovered URLs: `link_id`, `url`, `being_checked`, `last_check_attempt`, `log`, `http_code`, `request_duration`, `timeout`, `redirect_count`, `final_url`, `broken`, `warning`, `first_failure`, `last_success`, `may_recheck`, `status_text`, `status_code`
  - `{prefix}blc_instances` — where each link appears: `instance_id`, `link_id`, `container_type` (post/comment/bookmark/custom_field), `container_id`, `link_text`, `parser_type` (html_link/html_image/url_field/metadata), `link_context`, `raw_url`
  - `{prefix}blc_synch` — sync status per container: `container_type`, `container_id`, `synched`, `last_synch`
  - `{prefix}blc_filters` — saved search filters
- **Settings**: wp_options key `wsblc_options` (serialized). Key fields:
  - `check_threshold` — recheck interval in hours (default: 72)
  - `max_execution_time` — max seconds per check run (default: 420)
  - `recheck_count` — number of rechecks before marking broken
  - `custom_fields` — custom field keys to scan for URLs
  - `exclusion_list` — URLs to exclude from checking (one per line)
  - `target_resource_usage` — 1-8 scale for server load management
  - `send_email_notifications`, `send_authors_email_notifications` — email alert toggles
  - `notification_email_address` — where to send alerts
  - `mark_broken_links` — CSS class applied to broken links in frontend (default: `broken_link`)
  - `link_css` — custom CSS for broken links
- **Functions**: `blc_get_links($params)` — query links programmatically. `blc_cleanup_links()` — remove orphaned link data.
- **Dashboard widget**: Shows broken link count, redirects detected, and dismissed links.
- **Bulk actions**: Recheck, fix URL (search & replace), unlink (remove link keeping text), mark as not broken, dismiss.
- **Hooks**: `blc_link_broken`, `blc_link_okay` — fired on status change. `blc_options_saved` — after settings update.

### Common Patterns
- Redirection plugin is the primary tool for managing 301/302 redirects. Always create redirects when changing post slugs or restructuring URLs to preserve SEO.
- Broken Link Checker runs on WP-Cron and can be resource-intensive on large sites. Consider adjusting `check_threshold` and `max_execution_time` for performance.
- Both plugins have REST/admin APIs — prefer using those over direct database manipulation. Direct table writes skip validation and can cause inconsistencies.
- When migrating domains, Redirection's import/export feature handles bulk redirect creation from CSV or htaccess files.
