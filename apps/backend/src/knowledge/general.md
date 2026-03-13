## WordPress Core Patterns

### Step-by-Step Decision Guide

**When the user asks to do something to a post/page:**
1. If they give a title/name but no ID → call `list_posts` or `search_content` first to find the ID
2. If they say "draft" → use `post_status: draft`; "publish" → `post_status: publish`; "schedule" → `post_status: future` with a date
3. For Elementor pages → prefer `elementor_*` tools over `update_post` for layout changes
4. For destructive actions (delete, trash) → the tool marks `requires_confirmation: true`; do NOT warn the user in text — the UI shows a confirm dialog

**When the user asks about site settings:**
1. Use `get_option` to read current values before updating
2. Use `update_option` for standard settings (title, tagline, timezone, etc.)
3. Use `get_site_health` for diagnostic/status questions

**When the user asks to manage plugins:**
1. `list_plugins` → see what is installed and active
2. `install_plugin` → installs from WordPress.org (slug required)
3. `activate_plugin` / `deactivate_plugin` → toggle state
4. All plugin ops require administrator capability

**When a task has multiple steps:**
1. State your plan in 1–2 sentences before starting
2. Execute immediately — do not wait for "go ahead"
3. Report what was done after each major step

### Data Model

- Posts (wp_posts) store all content: posts, pages, CPTs, attachments, revisions, menus.
- Post meta (wp_postmeta): key-value pairs per post. Access via get_post_meta/update_post_meta.
- Options (wp_options): site-wide settings. Access via get_option/update_option. Autoloaded options cached in memory.
- Terms & taxonomies: categories, tags, and custom taxonomies stored in wp_terms + wp_term_taxonomy + wp_term_relationships.

### Post Statuses

publish, draft, pending, private, trash, future, auto-draft, inherit (attachments/revisions).

### Common Operations

- Create content: wp_insert_post(). Returns post ID or WP_Error.
- Update content: wp_update_post(). Only include fields you want to change.
- Delete content: wp_trash_post() (soft) or wp_delete_post(force=true) (permanent).
- Query content: WP_Query for complex queries, get_posts() for simple lists.
- Sanitize input: sanitize_text_field(), wp_kses_post() for HTML, absint() for IDs.

### Important Gotchas

- wp_update_post() can trigger infinite loops if called inside save_post hook — use remove_action/add_action around it.
- post_content is filtered through the_content filter on display — raw content may contain shortcodes or block markup.
- Post slugs (post_name) must be unique within a post type.
- WordPress uses UTC internally (post_date_gmt); post_date is in the site's timezone.
- Transients (get/set/delete_transient) are the proper way to cache. They auto-expire and use object cache if available.
- wp_safe_redirect() + exit for redirects; never use PHP header() directly.
- wpdb->prepare() for any raw SQL — prevents SQL injection.
