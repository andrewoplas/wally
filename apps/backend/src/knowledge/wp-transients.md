# WordPress Transients & Caching Reference

## Transient API

Transients store cached data in `wp_options` table with an expiration time. If a persistent object cache is active, transients use it instead of the database.

### Core Functions

```php
// SET: store a value with expiration
set_transient( string $transient, mixed $value, int $expiration = 0 );
// $expiration: seconds until expiry. 0 = no expiration (but may be evicted by object cache).

// GET: retrieve a value (returns false if expired/missing)
$value = get_transient( string $transient );

// DELETE: remove a transient
delete_transient( string $transient );
```

### Common Pattern

```php
function get_expensive_data() {
    $data = get_transient( 'my_expensive_query' );

    if ( false === $data ) {
        // Cache miss -- compute and store
        $data = perform_expensive_query();
        set_transient( 'my_expensive_query', $data, HOUR_IN_SECONDS );
    }

    return $data;
}
```

### Multisite (Site Transients)

For network-wide caching in multisite installations:

```php
set_site_transient( string $transient, mixed $value, int $expiration = 0 );
$value = get_site_transient( string $transient );
delete_site_transient( string $transient );
```

Site transients are stored in `wp_sitemeta` (multisite) or `wp_options` (single site, same as regular transients).

### Transient Name Limit

Transient names must be **172 characters or fewer**. Internally, WordPress prepends `_transient_` (11 chars) and `_transient_timeout_` (19 chars) to the option name, and `option_name` is `varchar(191)`.

## Object Cache API

`WP_Object_Cache` stores data in memory for the duration of a single request. By default, it is non-persistent. With a persistent cache plugin (Redis, Memcached), data persists across requests.

### Core Functions

```php
// Store a value in cache
wp_cache_set( string $key, mixed $data, string $group = '', int $expire = 0 );

// Retrieve a value
$data = wp_cache_get( string $key, string $group = '', bool $force = false, bool &$found = null );
// $found: set to true/false to distinguish "not cached" from "cached as false"

// Add only if key doesn't exist (avoids race conditions)
wp_cache_add( string $key, mixed $data, string $group = '', int $expire = 0 );

// Replace only if key exists
wp_cache_replace( string $key, mixed $data, string $group = '', int $expire = 0 );

// Delete a cached value
wp_cache_delete( string $key, string $group = '' );

// Flush entire cache
wp_cache_flush();
```

### Batch Operations (WP 6.0+)

```php
// Get/set/delete multiple keys at once
$values = wp_cache_get_multiple( array $keys, string $group = '' );
wp_cache_set_multiple( array $data, string $group = '', int $expire = 0 );
wp_cache_delete_multiple( array $keys, string $group = '' );

// Flush a specific group (WP 6.1+)
wp_cache_flush_group( string $group );

// Flush runtime-only (non-persistent) cache
wp_cache_flush_runtime();

// Check feature support before using
if ( wp_cache_supports( 'flush_group' ) ) {
    wp_cache_flush_group( 'my_plugin' );
}
```

### Cache Groups

```php
// Group prevents key collisions between plugins
wp_cache_set( 'key', $data, 'my_plugin_group' );
wp_cache_get( 'key', 'my_plugin_group' );

// Register global groups (shared across multisite network)
wp_cache_add_global_groups( [ 'my_plugin_global' ] );

// Register non-persistent groups (never saved to persistent cache)
wp_cache_add_non_persistent_groups( [ 'my_plugin_temp' ] );
```

## When to Use Which

| Scenario | Use | Why |
|---|---|---|
| Expensive DB query, result needed across requests | Transient | Persists in DB even without object cache |
| External API response with TTL | Transient | DB fallback ensures caching works everywhere |
| Data needed only within current request | Object cache | No DB overhead, memory only |
| Frequently accessed data, persistent cache available | Object cache | Faster than DB (Redis/Memcached) |
| Network-wide data in multisite | Site transient | Shared across all sites |
| Data that must survive cache flush | `wp_options` | Not affected by cache flush |

## Invalidation Patterns

```php
// Invalidate on content change
add_action( 'save_post', function( $post_id ) {
    delete_transient( 'my_posts_cache' );
    wp_cache_delete( 'my_posts', 'my_plugin' );
});

// Versioned cache keys (group invalidation without delete)
function get_cache_version() {
    $version = get_transient( 'my_cache_version' );
    if ( false === $version ) {
        $version = microtime( true );
        set_transient( 'my_cache_version', $version );
    }
    return $version;
}

// Bump version to invalidate all keys using it
function invalidate_cache() {
    set_transient( 'my_cache_version', microtime( true ) );
}

// Use versioned key
$key = 'my_data_' . get_cache_version();
$data = get_transient( $key );
```

## Gotchas

- `get_transient()` returns `false` on miss. If you cache `false` as a value, you cannot distinguish miss from hit. Cache a wrapper: `['data' => false]`.
- Transients without expiration in `wp_options` are autoloaded by default, adding memory overhead. Always set an expiration.
- With persistent object cache active, transients bypass the DB entirely -- `wp_options` rows won't be created.
- `wp_cache_flush()` clears ALL groups. With a shared persistent cache, this may affect other sites. Prefer `wp_cache_flush_group()` when available.
- Object cache data does NOT persist across requests unless a persistent cache plugin (Redis, Memcached) is installed.
- `wp_cache_supports()` must be used instead of `function_exists()` to check for batch operations -- WordPress polyfills them even when the backend does not truly support them.
- Transient expiration is a maximum lifetime, not a guarantee. Object cache backends may evict early under memory pressure.
- Do NOT store large serialized arrays in transients on high-traffic sites without persistent cache -- causes `wp_options` table bloat and autoload overhead.
