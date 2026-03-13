# WordPress Database Layer

## When to Use
- User asks about database tables, where WordPress stores data, or how custom data is saved
- User asks about post meta, options, or custom tables
- User troubleshoots missing data or slow queries

## Key Patterns

### Core Tables & What They Store
| Table | Accessed via Wally | What's stored |
|-------|-------------------|---------------|
| `wp_posts` | `list_posts`, `get_post`, `create_post`, `update_post` | Posts, pages, CPTs, attachments, revisions, menu items |
| `wp_postmeta` | `get_post` (meta fields), `update_post` (meta) | Post custom fields, plugin data per post |
| `wp_options` | `get_option`, `update_option` | Site settings, plugin settings, theme mods, transients |
| `wp_users` | Not directly accessible | User accounts |
| `wp_usermeta` | Not directly accessible | User metadata, capabilities, preferences |
| `wp_terms` | `list_categories`, `list_tags` | Taxonomy terms (categories, tags, custom) |
| `wp_term_taxonomy` | Via taxonomy tools | Term-taxonomy relationships |
| `wp_term_relationships` | Via post/taxonomy tools | Object-term associations |
| `wp_comments` | Not directly accessible | Comments |
| `wp_commentmeta` | Not directly accessible | Comment metadata |

### How Plugins Store Data
1. **wp_options** — Most common. Single key-value pair per setting. Accessible via `get_option`.
2. **wp_postmeta** — Per-post data. Accessible via `get_post` (returns meta) and `update_post` (set meta).
3. **Custom post types** — Stored in `wp_posts` with custom `post_type`. Accessible via `list_posts` with `post_type` parameter.
4. **Custom tables** — Some plugins create their own tables (e.g., WooCommerce orders, Simple History logs). NOT accessible via standard Wally tools.

### Table Prefix
- Default: `wp_` — but can be changed in `wp-config.php`
- Always referenced via `$wpdb->prefix` in code
- Wally tools abstract this away — no need to know the actual prefix

### Key wp_options Patterns
- Plugin settings: usually stored as serialized arrays under a single key (e.g., `wp_mail_smtp`, `astra-settings`)
- Theme mods: `theme_mods_{theme_slug}` — Customizer settings
- Transients: `_transient_{name}` and `_transient_timeout_{name}` — cached data with expiration
- Autoloaded options (`autoload = yes`) are loaded on every page — too many bloat memory

## Relevant Wally Tools
- `get_option` / `update_option` — read/write `wp_options` table
- `list_posts` / `get_post` / `create_post` / `update_post` — CRUD on `wp_posts` + `wp_postmeta`
- `list_categories` / `list_tags` — read `wp_terms` + `wp_term_taxonomy`
- `search_content` — searches across `wp_posts` (title, content, excerpt)

## Important Notes
- Wally cannot run raw SQL queries — all database access is through the tool abstractions above
- Custom tables created by plugins (e.g., WooCommerce `wp_wc_orders`, Simple History logs) are not accessible via Wally
- If user reports slow site, autoloaded options bloat is a common cause — check with `get_site_health`
- Post meta values can be serialized arrays — `get_post` returns them as-is
