# WordPress Cron & Scheduled Events Reference

## Scheduling Events

### Recurring Events

```php
// wp_schedule_event( int $timestamp, string $recurrence, string $hook, array $args = [], bool $wp_error = false )

if ( ! wp_next_scheduled( 'my_plugin_daily_task' ) ) {
    wp_schedule_event( time(), 'daily', 'my_plugin_daily_task' );
}

add_action( 'my_plugin_daily_task', 'my_daily_function' );
function my_daily_function() {
    // Task logic here
}
```

### Single Events (one-time)

```php
// wp_schedule_single_event( int $timestamp, string $hook, array $args = [], bool $wp_error = false )

wp_schedule_single_event( time() + 3600, 'my_plugin_one_time_task', [ 'arg1', 'arg2' ] );

add_action( 'my_plugin_one_time_task', function( $arg1, $arg2 ) {
    // Runs once, 1 hour from now
}, 10, 2 );
```

## Built-in Recurrence Intervals

| Slug | Interval |
|---|---|
| `hourly` | 1 hour |
| `twicedaily` | 12 hours |
| `daily` | 24 hours |
| `weekly` | 1 week |

### Registering Custom Intervals

```php
add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['every_five_minutes'] = [
        'interval' => 300,
        'display'  => 'Every 5 Minutes',
    ];
    $schedules['monthly'] = [
        'interval' => 30 * DAY_IN_SECONDS,
        'display'  => 'Once Monthly',
    ];
    return $schedules;
});
```

## Managing Scheduled Events

```php
// Check if event is scheduled
$timestamp = wp_next_scheduled( 'my_hook' );            // Returns timestamp or false
$timestamp = wp_next_scheduled( 'my_hook', [ $arg ] );  // With specific args

// Unschedule a specific event instance
wp_unschedule_event( $timestamp, 'my_hook' );
wp_unschedule_event( $timestamp, 'my_hook', [ $arg ] );  // With args

// Remove ALL scheduled instances of a hook
wp_clear_scheduled_hook( 'my_hook' );
wp_clear_scheduled_hook( 'my_hook', [ $arg ] );  // With specific args

// Unschedule all events for a hook (WP 5.1+)
wp_unschedule_hook( 'my_hook' );  // Removes ALL instances regardless of args

// Get all scheduled cron events
$crons = _get_cron_array();  // Returns full cron array

// Get scheduled event details
$event = wp_get_scheduled_event( 'my_hook' );
// Returns object: { hook, timestamp, schedule, args, interval }
```

## Plugin Activation/Deactivation Pattern

```php
// Schedule on activation
register_activation_hook( __FILE__, function() {
    if ( ! wp_next_scheduled( 'my_plugin_cron' ) ) {
        wp_schedule_event( time(), 'hourly', 'my_plugin_cron' );
    }
});

// Clean up on deactivation (IMPORTANT)
register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'my_plugin_cron' );
});
```

## WP-Cron vs System Cron

### How WP-Cron Works
WP-Cron is **triggered by page visits**, not by the system clock. On each page load, WordPress checks if any scheduled events are overdue and runs them. This means:
- Events may run **late** if the site has low traffic
- Events may **not fire at all** on idle sites
- Multiple events can stack up and fire simultaneously on the next visit

### Using System Cron Instead

```php
// wp-config.php -- disable WP-Cron page-load trigger
define( 'DISABLE_WP_CRON', true );
```

```bash
# System crontab -- trigger WP-Cron via HTTP every minute
* * * * * wget -q -O /dev/null https://example.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1

# Or via WP-CLI (preferred, avoids HTTP overhead)
* * * * * cd /path/to/wordpress && wp cron event run --due-now >/dev/null 2>&1
```

## Useful Time Constants

```php
MINUTE_IN_SECONDS  // 60
HOUR_IN_SECONDS    // 3600
DAY_IN_SECONDS     // 86400
WEEK_IN_SECONDS    // 604800
MONTH_IN_SECONDS   // 2592000 (30 days)
YEAR_IN_SECONDS    // 31536000
```

## Gotchas

- **args must match exactly:** `wp_next_scheduled('hook', ['a'])` and `wp_next_scheduled('hook', ['b'])` are different events.
- Always clean up cron events on plugin deactivation to avoid orphaned events.
- WP-Cron does not guarantee precise timing -- events may run seconds to hours late depending on traffic.
- `wp_schedule_event` with a past timestamp will fire on the next page load.
- Maximum execution time: cron tasks share PHP's `max_execution_time`. Long tasks should batch work.
- `ALTERNATE_WP_CRON` constant: Uses redirect-based triggering for hosts that block loopback requests.
- Cron events persist in `wp_options` table under `cron` key. Large cron arrays can slow option loading.
