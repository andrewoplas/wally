# WordPress Script & Style Enqueuing

## When to Use
- User asks about JavaScript or CSS loading in WordPress
- User troubleshoots missing styles, script conflicts, or render-blocking resources
- User asks about dequeuing unwanted plugin assets for performance

## Key Patterns

### How Asset Loading Works
WordPress uses a registration/enqueue system for scripts and styles. Assets are registered with a unique handle and loaded via hooks:
- `wp_enqueue_scripts` — frontend pages
- `admin_enqueue_scripts` — admin pages
- `login_enqueue_scripts` — login page

### Core Functions
| Function | Purpose |
|----------|---------|
| `wp_enqueue_script($handle, $src, $deps, $ver, $args)` | Load a JavaScript file |
| `wp_enqueue_style($handle, $src, $deps, $ver, $media)` | Load a CSS file |
| `wp_dequeue_script($handle)` | Remove a queued script |
| `wp_dequeue_style($handle)` | Remove a queued style |
| `wp_localize_script($handle, $obj, $data)` | Pass PHP data to JavaScript |
| `wp_add_inline_script($handle, $code)` | Add inline JS before/after a script |
| `wp_add_inline_style($handle, $css)` | Add inline CSS after a style |

### Script Loading Strategies (WP 6.3+)
- `'strategy' => 'defer'` — executes after document parsed, in order
- `'strategy' => 'async'` — executes as soon as downloaded, no guaranteed order
- ES Modules (WP 6.5+): `wp_register_script_module()` / `wp_enqueue_script_module()`

### Cache Busting
- `$ver` parameter appends `?ver=` to URL
- `false` = uses WP version, `null` = no version string
- Best practice: use `filemtime($path)` for automatic cache busting on file changes

### Common Built-in Script Handles
| Handle | Library |
|--------|---------|
| `jquery` | jQuery (noConflict mode) |
| `wp-element` | WordPress React wrapper |
| `wp-components` | WordPress UI components |
| `wp-blocks` | Block registration API |
| `wp-api-fetch` | Authenticated fetch wrapper |
| `wp-i18n` | Internationalization |

### Performance Patterns
- Scripts with `in_footer: true` don't block page rendering
- Conditional loading: only enqueue on pages that need the asset
- Dequeue unused plugin assets on pages where they're not needed

## Relevant Wally Tools
- `list_plugins` — identify which plugins may be loading scripts/styles
- `get_site_health` — may flag performance issues related to excessive scripts
- `get_option` — some performance plugins store dequeue lists in options

## Important Notes
- Wally cannot enqueue, dequeue, or modify scripts/styles — this is PHP code-level functionality
- Script conflicts are a common source of broken WordPress sites — usually caused by plugins loading incompatible jQuery versions or conflicting libraries
- If user reports broken layout/functionality after activating a plugin, suggest deactivating plugins one-by-one to isolate the conflict
- Never deregister core WordPress scripts (jquery, backbone, underscore) in admin — it breaks the dashboard
- For performance optimization, guide user to asset optimization plugins (Autoptimize, Perfmatters, WP Rocket)
