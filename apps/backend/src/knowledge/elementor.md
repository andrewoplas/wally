## Elementor Integration

### Data Structure
Elementor stores page content as JSON in postmeta key `_elementor_data`. The structure is a nested tree:
- **Sections** (elType: "section") — top-level containers, full-width rows
- **Columns** (elType: "column") — inside sections, define layout grid
- **Widgets** (elType: "widget") — inside columns, the actual content elements

Each element has:
- `id` — unique hex string (e.g., "2a3b4c5d")
- `elType` — "section", "column", or "widget"
- `widgetType` — for widgets: "heading", "text-editor", "image", "button", etc.
- `settings` — object containing all widget configuration (text, URLs, styling)
- `elements` — array of child elements (sections contain columns, columns contain widgets)

### Common Widget Types
heading, text-editor, image, video, button, icon, divider, spacer, google_maps, icon-list, image-box, image-gallery, tabs, accordion, toggle, alert, html, shortcode, menu-anchor, sidebar, icon-box, star-rating, image-carousel, basic-gallery, counter, progress, testimonial, social-icons, wp-widget-*, nav-menu, posts, portfolio, slides, form (Pro), price-table (Pro), countdown (Pro).

### Where Content Lives in Settings
- Headings: settings.title
- Text editor: settings.editor (HTML string)
- Images: settings.image.url, settings.image.id
- Buttons: settings.text, settings.link.url
- Icons: settings.selected_icon.value
- Forms: settings.form_name, settings.form_fields

### Modifying Elementor Content
1. Retrieve: get_post_meta($post_id, '_elementor_data', true)
2. Decode: json_decode($data, true) — MUST use associative arrays
3. Traverse the nested tree recursively to find target widgets
4. Modify the settings values
5. Re-encode: wp_slash(wp_json_encode($data))
6. Save: update_post_meta($post_id, '_elementor_data', $encoded)
7. CRITICAL: Clear CSS cache after changes — Elementor\Plugin::$instance->files_manager->clear_cache() or delete postmeta keys _elementor_css and associated CSS files

### Elementor CSS
- Per-page CSS stored in postmeta _elementor_css and files at /wp-content/uploads/elementor/css/
- Global CSS at /wp-content/uploads/elementor/css/global.css
- After any _elementor_data modification, CSS cache MUST be cleared

### Searching Elementor Content
Standard post_content search won't find Elementor text. Must search _elementor_data postmeta values. The JSON can be deeply nested — recursive search is required.
