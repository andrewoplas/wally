# WordPress Security Patterns

## When to Use
- User asks about WordPress security, nonces, input sanitization, or capability checks
- User asks about user permissions/roles and what they can do
- User wants to understand how WordPress protects against common attacks

## Key Patterns

### Nonce System (CSRF Protection)
- Nonces verify request intent — tied to current user and action
- Valid for 24 hours (12-hour tick window) — NOT truly single-use
- User-specific: a nonce created for user A fails for user B

### WordPress Capability System
| Capability | Role | Common Use |
|------------|------|------------|
| `manage_options` | Administrator | Settings, site configuration |
| `activate_plugins` | Administrator | Plugin management |
| `edit_theme_options` | Administrator | Menus, widgets, customizer |
| `edit_users` | Administrator | User management |
| `edit_others_posts` | Editor+ | Edit any post |
| `manage_categories` | Editor+ | Taxonomy management |
| `publish_posts` | Author+ | Publish own posts |
| `upload_files` | Author+ | Media library |
| `edit_posts` | Contributor+ | Create/edit own drafts |
| `read` | Subscriber+ | View content |

### Input Sanitization Functions
| Function | Use For |
|----------|---------|
| `sanitize_text_field()` | Single-line plain text |
| `sanitize_textarea_field()` | Multi-line plain text |
| `sanitize_email()` | Email addresses |
| `sanitize_title()` | URL slugs |
| `absint()` | Non-negative integers |
| `wp_kses_post()` | HTML (allows post-level tags) |
| `wp_strip_all_tags()` | Remove all HTML |

### Output Escaping Functions
| Function | Context |
|----------|---------|
| `esc_html()` | Inside HTML tags |
| `esc_attr()` | HTML attributes |
| `esc_url()` | href/src attributes |
| `esc_js()` | Inline JavaScript |
| `esc_textarea()` | Inside `<textarea>` |

### Security Validation Order
1. Verify nonce (CSRF protection)
2. Check capabilities (authorization)
3. Sanitize input (clean data)
4. Validate (check data correctness)
5. Process
6. Escape output (prevent XSS)

## Relevant Wally Tools
- Wally's own tools enforce capability checks — each tool has a `get_required_capability()` method
- Tools requiring confirmation (`update_option`, `delete_post`, etc.) add an extra safety layer
- `get_site_health` — may flag security issues

## Important Notes
- Wally inherits the current WordPress user's capabilities — it cannot do more than the logged-in user's role allows
- All Wally tool actions are logged in the audit log
- `wp_kses_post()` does NOT allow `<script>`, `<style>`, `<iframe>`, `<form>` tags
- `esc_url()` strips invalid protocols — only allows http, https, ftp, ftps, mailto, tel by default
- Never trust `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`, or `$_SERVER` without sanitization
