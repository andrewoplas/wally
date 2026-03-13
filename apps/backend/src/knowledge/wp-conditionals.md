# WordPress Conditional Tags

## When to Use
- User asks about conditional logic in WordPress templates or themes
- User asks when/where certain content or code should appear
- User troubleshoots why something shows on wrong pages or doesn't appear

## Key Patterns

### What Conditional Tags Are
Functions that return `true`/`false` based on the current request context. Used in themes and plugins to control what displays where.

### Content Conditionals
| Function | Checks if viewing... |
|----------|---------------------|
| `is_single()` | A single post (not pages) |
| `is_page()` | A single page |
| `is_singular()` | Any single post, page, or CPT |
| `is_attachment()` | An attachment page |
| `is_singular('product')` | A single post of specific CPT |

### Archive Conditionals
| Function | Checks if viewing... |
|----------|---------------------|
| `is_archive()` | Any archive page |
| `is_category()` | A category archive |
| `is_tag()` | A tag archive |
| `is_tax('flavor')` | A custom taxonomy archive |
| `is_author()` | An author archive |
| `is_date()` | A date-based archive |
| `is_post_type_archive('product')` | A CPT archive |

### Special Page Conditionals
| Function | Checks if viewing... |
|----------|---------------------|
| `is_front_page()` | The site front page (respects Settings > Reading) |
| `is_home()` | The blog posts index page |
| `is_search()` | Search results |
| `is_404()` | 404 Not Found page |

### Front Page vs Home Page
- **Static front page + separate posts page**: `is_front_page()` on static page, `is_home()` on posts page
- **Default (latest posts)**: both `is_front_page()` and `is_home()` return true

### Context Conditionals
| Function | Checks... |
|----------|-----------|
| `is_admin()` | Admin dashboard area (NOT a permission check) |
| `is_user_logged_in()` | Current visitor is logged in |
| `is_ssl()` | HTTPS connection |
| `wp_doing_ajax()` | AJAX request |
| `wp_doing_cron()` | WP-Cron execution |

### Important Timing
- Query-dependent tags (`is_single()`, `is_page()`, etc.) only work after the main query runs (after `wp` hook)
- Context tags (`is_admin()`, `is_user_logged_in()`) work earlier (from `init`)

## Relevant Wally Tools
- `get_option` with key `show_on_front` — check if front page is `page` or `posts`
- `get_option` with key `page_on_front` — which page is the static front page
- `get_option` with key `page_for_posts` — which page is the blog page

## Important Notes
- Wally cannot evaluate conditional tags — they are PHP runtime checks in themes/plugins
- Understanding conditionals helps explain to users why content appears where it does
- `is_admin()` does NOT check permissions — it only checks request context. Use `current_user_can()` for capability checks.
- These are primarily relevant for theme/plugin developers — most users interact with this via page builder display conditions
