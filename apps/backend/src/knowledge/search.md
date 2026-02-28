## Search & Replace

### Search Architecture
WordPress content can live in multiple locations:
1. **post_content** (wp_posts.post_content) — standard Gutenberg/classic editor content
2. **Elementor _elementor_data** (wp_postmeta) — JSON-encoded page builder data
3. **Post meta** (wp_postmeta) — ACF fields, custom fields, SEO meta
4. **Options** (wp_options) — site-wide settings, widget content
5. **Term meta** (wp_termmeta) — taxonomy term custom fields

### Search Strategy
When user asks to find/replace text:
1. First search post_content (standard content)
2. Then search _elementor_data (Elementor pages)
3. If targeting specific fields, search postmeta directly
4. Report ALL locations where the text was found before replacing

### Replace Safety
- Always do a dry run first to show matches before making changes
- For post_content: direct string replacement, then wp_update_post()
- For Elementor: JSON decode > recursive tree search > replace in settings > JSON encode > update_post_meta > clear CSS cache
- Case sensitivity matters — offer both options
- Regex support is powerful but dangerous — validate patterns before executing

### WP_Query Search
The 's' parameter in WP_Query searches post_title and post_content. It does NOT search:
- Post meta values
- Elementor data
- Taxonomy term names
- Comments
For comprehensive search, must query postmeta separately.

### Database-Level Search
For complex patterns, use $wpdb->get_results() with LIKE or REGEXP:
$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE post_content LIKE %s", '%' . $wpdb->esc_like($search) . '%'))
