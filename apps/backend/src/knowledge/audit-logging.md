## WordPress Audit & Logging Plugins

### Simple History
- **Plugin slug**: `simple-history/index.php` (ID #77)
- **Purpose**: Lightweight activity log for WordPress. Tracks post changes, user logins, plugin activations, option changes, widget updates, menu changes, and more.
- **Custom DB tables**:
  - `{prefix}simple_history` — main log table.
    - Columns: `id`, `date` (datetime UTC), `logger` (logger slug), `level` (PSR-3 level), `message` (log message template), `occasionsID` (groups repeated events), `initiator` (who triggered: wp_user, wp_cli, wp, other).
  - `{prefix}simple_history_contexts` — contextual metadata for each log entry.
    - Columns: `context_id`, `history_id` (FK to simple_history.id), `key`, `value`.
    - Stores details like: `_user_id`, `_user_login`, `_user_email`, `post_type`, `post_title`, `old_post_title`, `new_post_title`, `plugin_name`, `option_name`, `_server_remote_addr`.
- **Settings**: wp_options keys prefixed `simple_history_*`.
  - `simple_history_db_version` — current database schema version
  - `simple_history_pager_size` — number of log entries per page (default 30)
  - `simple_history_show_on_dashboard` — show log widget on WP dashboard (1/0)
  - `simple_history_show_as_page` — show as a separate admin menu page (1/0)
  - `simple_history_enable_rss_feed` — enable RSS feed for log entries (1/0)
  - `simple_history_rss_secret` — secret key for RSS feed URL authentication
- **Log retention**: Configurable number of days to keep log entries. Default: 60 days. Setting: `simple_history_clear_log_days`. Cleanup runs on a daily WP-Cron schedule.
- **Built-in loggers** (what gets logged automatically):
  - `SimplePostLogger` — post/page/CPT create, update, trash, delete, status changes
  - `SimpleUserLogger` — user login, logout, failed login, profile update, user create/delete
  - `SimplePluginLogger` — plugin activate, deactivate, install, update, delete
  - `SimpleThemeLogger` — theme switch, customize, update
  - `SimpleMediaLogger` — media upload, edit, delete
  - `SimpleMenuLogger` — menu create, update, delete, items changed
  - `SimpleWidgetLogger` — widget add, update, remove, reorder
  - `SimpleOptionsLogger` — tracked wp_options changes (blogname, blogdescription, admin_email, etc.)
  - `SimpleCoreUpdatesLogger` — WordPress core updates
  - `SimpleExportLogger` — content exports
  - `SimpleCategoriesLogger` — taxonomy term create, edit, delete
  - `SimpleCommentsLogger` — comment post, edit, trash, approve, spam
- **PSR-3 log levels**: `emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, `debug`.
- **PHP API** (for custom logging):
  - `SimpleLogger()->emergency($message, $context)` — log emergency level
  - `SimpleLogger()->alert($message, $context)` — log alert level
  - `SimpleLogger()->critical($message, $context)` — log critical level
  - `SimpleLogger()->error($message, $context)` — log error level
  - `SimpleLogger()->warning($message, $context)` — log warning level
  - `SimpleLogger()->notice($message, $context)` — log notice level
  - `SimpleLogger()->info($message, $context)` — log info level
  - `SimpleLogger()->debug($message, $context)` — log debug level
  - Message supports placeholders: `SimpleLogger()->info('Updated post "{post_title}"', ['post_title' => $title, 'post_id' => $id])`
- **Custom logger registration**:
  ```php
  add_action('simple_history/add_custom_logger', function($simple_history) {
      $simple_history->register_logger('MyCustomLogger');
  });
  ```
  Custom loggers extend `SimpleLogger` base class and define a unique slug, name, and description.
- **Hooks**:
  - `simple_history_log` — action fired after every log entry is created. Receives logger instance and log entry data.
  - `simple_history/log/inserted` — action fired after a log row is inserted into the database.
  - `simple_history_log_query_args` — filter to modify the query arguments when retrieving log entries (for custom filtering).
  - `simple_history/log_query_inner_where` — filter to add custom WHERE clauses to log queries.
  - `simple_history/feeds/show` — filter to control RSS feed visibility.
  - `simple_history/rss_item_link` — filter to modify RSS item links.
  - `simple_history/db_purge_days_interval` — filter to change log retention days (default 60).
  - `simple_history/log/do_log` — filter to prevent specific events from being logged (return false to skip).
  - `simple_history/loggers_loaded` — action fired after all loggers are loaded.
  - `simple_history/dropin/loaded` — action fired after drop-in modules are loaded.
- **Querying logs programmatically**:
  ```php
  $events = SimpleHistory()->get_log_rows_result([
      'posts_per_page' => 50,
      'paged' => 1,
      'loggers' => 'SimplePostLogger',
      'loglevels' => 'info,warning',
      'date_from' => '2024-01-01',
      'date_to' => '2024-12-31',
      'search' => 'keyword',
  ]);
  ```
- **REST API** (namespace `simple-history/v1`):
  - `GET /events` — list log events (supports pagination, filtering by logger, level, date range, search)
  - `GET /events/{id}` — single event details with full context
  - `GET /loggers` — list available loggers
  - `GET /stats` — log statistics (event counts by logger, level)
- **RSS feed**: Available at `{site_url}/?simple_history_get_rss=1&rss_secret={secret}`. Provides real-time monitoring via RSS reader.
- **Dashboard widget**: Shows recent activity directly on the WordPress dashboard. Configurable number of items.
- **Occasions grouping**: Repeated identical events (e.g., multiple failed logins) are grouped together using `occasionsID` to prevent log flooding. Displays as "X more" in the UI.
- **IP logging**: Stores visitor IP addresses in context data (`_server_remote_addr`). Supports `X-Forwarded-For` headers for reverse proxy setups.
- **WP-CLI support**: `wp simple-history list` — display log entries from command line.
- **File paths**:
  - Plugin directory: `wp-content/plugins/simple-history/`
  - Loggers directory: `wp-content/plugins/simple-history/loggers/`
  - Drop-ins directory: `wp-content/plugins/simple-history/dropins/`

### Common Patterns
- Simple History is the most widely used free audit logging plugin. It auto-hooks into WordPress core actions — no configuration needed for basic logging.
- Log context data is stored separately from the log message in the `_contexts` table. Always query context for full details about an event (e.g., which fields changed on a post update, old vs new values).
- For security monitoring, focus on `SimpleUserLogger` events: failed logins, profile changes, and role modifications.
- Log retention should be configured based on site size. High-traffic sites with many editors can generate thousands of log entries per day. Reduce retention days or filter out noisy loggers to manage database size.
- The RSS feed provides a simple way to monitor site activity without logging into wp-admin. Protect the RSS secret key.
- When building custom integrations, use the `simple_history/log/do_log` filter to suppress noisy events, and `simple_history_log_query_args` to build custom log views.
