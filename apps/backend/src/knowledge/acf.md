## Advanced Custom Fields (ACF)

### ACF Free vs Pro
- **ACF Free**: Field groups, field values (post/term/user), all field types, meta queries
- **ACF Pro** (adds): Repeater, Flexible Content, Gallery, Clone, Options Pages, ACF Blocks, **Custom Post Types** (6.1+), **Custom Taxonomies** (6.1+)

### Custom Post Types (ACF Pro 6.1+)
ACF Pro can register custom post types via the UI (ACF → Post Types). These are stored as WP posts of type `acf-post-type`. Use the `acf_list_post_types` tool to list them — it returns each post type's key, slug, labels, icon, supports, and active status. You can create, update, and delete ACF post types with the corresponding tools. The post type key is typically `post_type_<slug>` (e.g., `post_type_podcast`).

### Custom Taxonomies (ACF Pro 6.1+)
Similar to post types, ACF Pro can register custom taxonomies via the UI (ACF → Taxonomies). Use `acf_list_taxonomies` to list them. Each taxonomy has a key (e.g., `taxonomy_podcast_category`), a slug, labels, attached post types, and an active status. Taxonomies can be hierarchical (category-like) or flat (tag-like).

### Field Groups
Field groups define which custom fields appear on edit screens. Each group has:
- A **key** (e.g., `group_60a7b3c4d5e6f`) and **title**
- **Location rules** controlling where fields appear (e.g., post_type == product)
- **Fields** — ordered list of field definitions, each with a label, name, type, and settings
Use `acf_list_field_groups` to list all groups, `acf_get_field_group` for full details including all field definitions.

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

### Options Pages (Pro)
ACF can store fields on options pages (site-wide, not per-post). Read with get_field('field_name', 'option'). Use `acf_list_options_pages` to discover registered options pages, `acf_get_options_fields` to read their values, and `acf_update_options_field` to change a value.
