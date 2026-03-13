# WordPress Transients & Caching

## When to Use
- User asks about caching, transients, or object cache
- User troubleshoots slow site performance related to caching
- User asks about cached data, stale content, or clearing caches

## Key Patterns

### Transient API
Transients store cached data with an expiration time. Stored in `wp_options` table unless a persistent object cache (Redis/Memcached) is active.

| Function | Purpose |
|----------|---------|
| `set_transient($key, $value, $expiration)` | Store with TTL (seconds) |
| `get_transient($key)` | Retrieve (returns `false` if expired/missing) |
| `delete_transient($key)` | Remove a transient |

### Where Transients Are Stored
- **Without object cache**: `wp_options` table as `_transient_{name}` and `_transient_timeout_{name}`
- **With object cache** (Redis/Memcached): Bypasses database entirely — `wp_options` rows not created
- Transient name limit: **172 characters**

### Object Cache API
| Function | Purpose |
|----------|---------|
| `wp_cache_set($key, $data, $group, $expire)` | Store in cache |
| `wp_cache_get($key, $group)` | Retrieve from cache |
| `wp_cache_delete($key, $group)` | Remove from cache |
| `wp_cache_flush()` | Flush entire cache |

### When to Use Which
| Scenario | Use |
|----------|-----|
| Data needed across requests, no object cache | Transient |
| External API response with TTL | Transient |
| Data needed only within current request | Object cache |
| Data that must survive cache flush | `wp_options` (via `get_option`) |

### Common Performance Issues
- **Transients without expiration** are autoloaded from `wp_options` — adds memory overhead
- **Expired transients** accumulate in `wp_options` on sites without object cache — causes table bloat
- **Object cache** data doesn't persist across requests unless Redis/Memcached is installed

### Time Constants
| Constant | Value |
|----------|-------|
| `MINUTE_IN_SECONDS` | 60 |
| `HOUR_IN_SECONDS` | 3,600 |
| `DAY_IN_SECONDS` | 86,400 |
| `WEEK_IN_SECONDS` | 604,800 |

## Relevant Wally Tools
- `get_option` — read transient values directly: key `_transient_{name}`
- `get_option` — check for object cache drop-in: `active_plugins` or site health
- `get_site_health` — may flag autoloaded data bloat or missing object cache
- `list_plugins` — check for caching plugins (Redis Object Cache, WP Super Cache, W3TC)

## Important Notes
- Wally cannot flush caches, delete transients, or manage the object cache — guide user to caching plugin admin or WP-CLI
- `get_transient()` returns `false` on miss — if caching `false` as a value, wrap it: `['data' => false]`
- `wp_cache_flush()` clears ALL groups — on shared environments, may affect other sites
- For site-wide cache purging, guide user to their caching plugin's purge button or WP-CLI: `wp cache flush`
