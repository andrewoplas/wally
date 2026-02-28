## Advanced Custom Fields (ACF)

### Reading Fields
- get_field($selector, $post_id) — returns formatted value (e.g., image field returns array with url, alt, etc.)
- get_field_object($selector, $post_id) — returns full field config including type, choices, label
- Raw meta: get_post_meta($post_id, $field_name, true) — returns unformatted value (e.g., image field returns just attachment ID)

### Writing Fields
- update_field($selector, $value, $post_id) — preferred method, handles formatting
- update_post_meta($post_id, $field_name, $value) — works but bypasses ACF formatting

### Field Types
text, textarea, number, range, email, url, password, image, file, wysiwyg, oembed, gallery, select, checkbox, radio, button_group, true_false, link, post_object, page_link, relationship, taxonomy, user, date_picker, date_time_picker, time_picker, color_picker, message, accordion, tab, group, repeater, flexible_content, clone.

### Complex Field Types
- **Repeater**: returns array of rows. Each row is an associative array of sub-fields. Access: get_field('repeater_name') returns [['sub_field_1' => 'val', ...], ...]
- **Group**: returns associative array. Access: get_field('group_name') returns ['sub_field_1' => 'val', ...]
- **Flexible Content**: returns array of layouts with 'acf_fc_layout' key. Access: get_field('flex_name') returns [['acf_fc_layout' => 'layout_name', 'field' => 'val'], ...]
- **Relationship/Post Object**: returns WP_Post objects (or array of them)
- **Image**: returns array (url, id, alt, title, sizes) or just ID depending on return format setting
- **Gallery**: returns array of image arrays

### ACF in Meta Queries
ACF fields are stored as regular postmeta. You can query them with WP_Query meta_query:
'meta_query' => [['key' => 'fan_love_type', 'value' => 'tweet', 'compare' => '=']]

### Options Pages
ACF can store fields on options pages (site-wide, not per-post). Read with get_field('field_name', 'option').
