# WordPress AJAX

## When to Use
- User asks about AJAX in WordPress, admin-ajax.php, or asynchronous requests
- User troubleshoots AJAX errors (response 0, 403, -1)
- User asks about admin-ajax.php vs REST API

## Key Patterns

### How WordPress AJAX Works
All AJAX requests route through `wp-admin/admin-ajax.php`. Plugins register named actions, then JavaScript sends requests specifying which action to invoke.

### Two Hook Types
- `wp_ajax_{action}` — fires for **logged-in** users only
- `wp_ajax_nopriv_{action}` — fires for **non-logged-in** users only
- Register both if the feature should work for all visitors

### Common AJAX Error Codes
| Response | Cause | Fix |
|----------|-------|-----|
| `0` | No handler registered, handler has fatal error, or missing `wp_die()` | Check action name matches between JS and PHP |
| `403` | Nonce verification failed | Check nonce is included and action name matches |
| `-1` | `check_ajax_referer()` failed | Same as 403 — nonce mismatch or expiration |
| Empty | Handler doesn't call a response function | Must end with `wp_send_json_*()` or `wp_die()` |

### admin-ajax.php vs REST API
| Feature | admin-ajax.php | REST API |
|---------|----------------|----------|
| Best for | Legacy code, simple internal operations | New features, Gutenberg, external access |
| Auth | Cookie + nonce (manual) | Cookie + nonce or Application Passwords |
| Caching | Not cacheable by default | HTTP cache headers supported |
| Discovery | Not discoverable | Self-documenting with schema |

### Heartbeat API
Built-in polling mechanism (15-120 second intervals) for:
- Post lock detection (preventing simultaneous editing)
- Autosave
- Login expiration warnings
- Real-time notification polling

## Relevant Wally Tools
- `list_plugins` — check which plugins may be registering AJAX handlers
- `get_site_health` — may flag REST API or AJAX issues

## Important Notes
- Wally does not use admin-ajax.php — Wally's plugin uses its own REST API endpoints
- AJAX debugging requires browser dev tools (Network tab) — guide user to check there
- Nonces expire after 12-24 hours — session timeout can cause AJAX failures
- `ajaxurl` is available in admin JS automatically but must be passed explicitly on the frontend
- If user reports "admin-ajax.php 400/500 errors," it's usually a plugin conflict — try deactivating plugins
