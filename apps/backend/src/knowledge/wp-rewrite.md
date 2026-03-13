# WordPress Rewrite & Permalink System

## When to Use
- User asks about permalinks, URL structure, or pretty URLs
- User reports 404 errors after changing permalinks or adding custom post types
- User wants to change the permalink structure

## Key Patterns

### Permalink Structures
| Setting | Structure | Example |
|---------|-----------|---------|
| Plain | `?p=123` | `example.com/?p=123` |
| Post name | `/%postname%/` | `example.com/hello-world/` |
| Day and name | `/%year%/%monthnum%/%day%/%postname%/` | `example.com/2024/03/15/hello-world/` |
| Month and name | `/%year%/%monthnum%/%postname%/` | `example.com/2024/03/hello-world/` |

### Reading/Changing Permalink Structure
- Current structure: `get_option('permalink_structure')` — e.g., `/%postname%/`
- Rewrite rules: stored in `wp_options` under key `rewrite_rules` (large serialized array)
- Category base: `get_option('category_base')` — default empty (uses `/category/`)
- Tag base: `get_option('tag_base')` — default empty (uses `/tag/`)

### Custom Post Type Permalinks
- CPTs registered with `'rewrite' => ['slug' => 'products']` get `/products/{post-name}/`
- `with_front => false` prevents blog prefix from being prepended
- After registering new CPTs, rewrite rules must be flushed

### Flushing Rewrite Rules
- **Manual flush**: Visit Settings > Permalinks and click "Save Changes"
- This is the most common fix for 404 errors after plugin activation or permalink changes
- `flush_rewrite_rules()` is the PHP function — should only be called on plugin activation/deactivation, never on every page load

### Troubleshooting 404 Errors
1. Ask user to go to Settings > Permalinks and click "Save Changes" (flushes rewrite rules)
2. If that doesn't work, check `.htaccess` (Apache) or server config (nginx)
3. Check if the requested post type has `'public' => true` and `'show_in_rest' => true`

## Relevant Wally Tools
- `get_option` with key `permalink_structure` — check current permalink format
- `get_option` with key `rewrite_rules` — inspect all rewrite rules (large value)
- `get_option` with key `category_base` or `tag_base` — check taxonomy URL bases
- `get_site_info` — returns permalink structure

## Important Notes
- Wally cannot flush rewrite rules — guide user to Settings > Permalinks > Save Changes
- Wally cannot modify `.htaccess` or nginx configuration
- On nginx, WordPress cannot write rewrite rules automatically — server config must be updated manually
- The most common permalink issue is stale rewrite rules after plugin activation — "Save Permalinks" is almost always the fix
- Changing `permalink_structure` via `update_option` alone is NOT enough — rewrite rules must also be flushed
