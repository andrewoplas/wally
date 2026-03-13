# WordPress Cron System

## When to Use
- User asks about scheduled tasks, cron jobs, or recurring events
- User reports scheduled actions not firing (e.g., missed scheduled posts, backups not running)
- User asks about WP-Cron vs system cron

## Key Patterns

### How WP-Cron Works
WP-Cron is triggered by **page visits**, not the system clock. On each page load, WordPress checks for overdue scheduled events and runs them. This means:
- Events may run **late** on low-traffic sites
- Events may **not fire** on idle sites
- Multiple events can stack up and fire simultaneously on the next visit

### Built-in Schedules
| Slug | Interval |
|------|----------|
| `hourly` | 1 hour |
| `twicedaily` | 12 hours |
| `daily` | 24 hours |
| `weekly` | 1 week |

### Common Scheduled Events
- `wp_scheduled_delete` — empties trash (daily)
- `wp_scheduled_auto_draft_delete` — removes auto-drafts
- Plugin cron hooks: backup schedules, email digests, cache cleanup, sitemap updates, scan schedules

### WP-Cron vs System Cron
- **WP-Cron**: No server config needed, but unreliable on low-traffic sites
- **System cron**: Set `DISABLE_WP_CRON` in `wp-config.php`, then run `wp cron event run --due-now` via system crontab every minute
- Many managed hosts (WP Engine, Kinsta) replace WP-Cron with system cron automatically

### Cron Storage
- All scheduled events stored in `wp_options` under the `cron` key
- Large cron arrays can slow option loading — a sign of orphaned events from deactivated plugins

### Troubleshooting Missed Events
1. Check if `DISABLE_WP_CRON` is set (should have system cron as replacement)
2. Check if `ALTERNATE_WP_CRON` is set (redirect-based triggering for hosts blocking loopback)
3. Security plugins or .htaccess rules may block `wp-cron.php`
4. Hosting-level restrictions may prevent loopback requests

## Relevant Wally Tools
- `get_option` with key `cron` — read all scheduled events (raw cron array)
- `get_site_health` — may flag cron issues in site health report
- `list_plugins` — check for cron management plugins (WP Crontrol)

## Important Notes
- Wally cannot schedule, unschedule, or trigger cron events — these require PHP code changes
- The `cron` option value is a large serialized array — reading it shows all scheduled hooks and their next run times
- If user has WP Crontrol plugin, guide them to Tools > Cron Events for visual management
- Missed scheduled posts are almost always a WP-Cron issue — check the patterns above
