## WordPress Core Patterns

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
