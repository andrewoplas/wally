# Audit & Activity Logging

## When to Use
- User mentions Simple History, audit log, activity log, or event tracking
- User asks about who changed what, login history, or site activity
- User wants to check logging settings or review recent activity

## Available Tools
- `list_plugins` — detect if an audit logging plugin is active
- `get_option` — read logging plugin settings

## Workflows

### Detect Active Plugin
1. Call `list_plugins`
2. Look for: `simple-history`

### Simple History — Read Settings
1. Call `get_option` with key `simple_history_pager_size` — entries per page (default 30)
2. Call `get_option` with key `simple_history_show_on_dashboard` — dashboard widget enabled (1/0)
3. Call `get_option` with key `simple_history_show_as_page` — separate admin page (1/0)
4. Call `get_option` with key `simple_history_enable_rss_feed` — RSS feed enabled (1/0)

### Check Log Retention
1. Call `get_option` with key `simple_history_clear_log_days`
2. Default: 60 days. Cleanup runs via daily WP-Cron

### What Simple History Tracks Automatically
- **Posts**: create, update, trash, delete, status changes
- **Users**: login, logout, failed login, profile update, create/delete
- **Plugins**: activate, deactivate, install, update, delete
- **Themes**: switch, customize, update
- **Media**: upload, edit, delete
- **Menus**: create, update, delete
- **Widgets**: add, update, remove
- **Options**: tracked wp_options changes (blogname, admin_email, etc.)
- **Comments**: post, edit, trash, approve, spam

### When User Asks "Who Changed X?"
1. Tell user: "Go to Dashboard > Simple History (or the Simple History admin page) to see the activity log."
2. They can filter by user, date range, and event type
3. RSS feed available for monitoring without logging in (if enabled)

## Important Notes
- Simple History stores logs in custom database tables — not accessible via standard Wally tools
- Wally cannot query or display audit log entries directly — guide user to the Simple History admin page
- Log context data (old/new values, field changes) is in a separate context table for each event
- High-traffic sites with many editors can generate thousands of entries/day — recommend adjusting retention
- RSS feed secret key (`simple_history_rss_secret`) is sensitive — do NOT expose
- Simple History auto-hooks into WordPress core actions — no configuration needed for basic logging
- For custom log queries, filtering, or export, guide user to the Simple History admin interface
