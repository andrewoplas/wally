# WordPress HTTP API

## When to Use
- User asks about making external API calls from WordPress
- User troubleshoots connection timeouts, SSL errors, or failed remote requests
- User asks about caching API responses or external data

## Key Patterns

### How WordPress Makes HTTP Requests
WordPress provides `wp_remote_get()`, `wp_remote_post()`, and `wp_remote_request()` for outbound HTTP. These abstract away cURL/streams and provide consistent error handling.

### Common Request Functions
| Function | Use |
|----------|-----|
| `wp_remote_get($url, $args)` | GET request |
| `wp_remote_post($url, $args)` | POST request |
| `wp_remote_head($url, $args)` | HEAD request (check metadata) |
| `wp_remote_request($url, $args)` | Any HTTP method (PUT, DELETE, PATCH) |
| `wp_safe_remote_get($url, $args)` | GET with SSRF protection (for user-supplied URLs) |

### Key $args Parameters
- `timeout` — seconds to wait (default: 5). Increase for slow APIs
- `headers` — associative array of HTTP headers
- `body` — request body (arrays auto-encoded as form data; use `wp_json_encode()` for JSON)
- `sslverify` — SSL verification (default: true). **Never disable in production**
- `blocking` — set `false` for fire-and-forget requests

### Response Handling
```
$response = wp_remote_get($url);
if (is_wp_error($response)) { /* network/DNS/timeout error */ }
$code = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);
$header = wp_remote_retrieve_header($response, 'content-type');
```

### Caching with Transients
Common pattern: check transient → if miss, make request → store in transient with expiration:
- `get_transient('cache_key')` — returns `false` on miss
- `set_transient('cache_key', $data, HOUR_IN_SECONDS)` — cache with TTL

### SSRF Protection
Use `wp_safe_remote_*` functions when URL comes from user input — they block private/reserved IP ranges and validate URLs.

## Relevant Wally Tools
- `get_option` — read transient values (`_transient_{name}`) or API configuration stored in options
- `get_site_health` — may flag HTTP/connectivity issues

## Important Notes
- Wally cannot make outbound HTTP requests — this is developer-level PHP functionality
- `WP_Error` is returned for network failures (timeout, DNS), NOT for HTTP 404/500 (those are valid responses)
- Default timeout is 5 seconds — many plugin issues stem from this being too short for slow APIs
- `is_wp_error()` must always be checked before accessing response data
