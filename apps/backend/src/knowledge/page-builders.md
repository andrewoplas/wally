## WordPress Page Builders (Non-Elementor)

Note: Elementor has its own dedicated knowledge file. This covers Beaver Builder, Divi, Brizy, and Oxygen.

### Beaver Builder
- **Data storage**: Post meta key `_fl_builder_data` (serialized PHP array of nodes). Draft data in `_fl_builder_draft`. Published status tracked by `_fl_builder_enabled` (1 = active).
- **CSS cache**: `wp-content/uploads/bb-plugin/cache/` — per-post CSS/JS files named `{post_id}-layout.css`.
- **Node structure**: Flat array keyed by node ID. Each node has `type` (row, column, module), `parent` (parent node ID), `position` (order), `settings` (module-specific config).
- **Functions**:
  - `FLBuilderModel::get_layout_data($status, $post_id)` — get node array ('published' or 'draft')
  - `FLBuilderModel::update_layout_data($data, $status, $post_id)` — save modified layout
  - `FLBuilderModel::delete_layout_data($status, $post_id)` — remove layout
  - `FLBuilderModel::is_builder_enabled($post_id)` — check if BB is active on post
- **Hooks**: `fl_builder_before_save_layout`, `fl_builder_after_save_layout`, `fl_builder_before_render_modules`, `fl_builder_after_render_modules`.
- **Templates**: Stored as post type `fl-builder-template`. Global rows/modules are also templates.
- **After editing data**: Must clear CSS cache — `FLBuilderModel::delete_asset_cache_for_all_posts()` or delete files in cache directory.

### Divi (Elegant Themes)
- **Data storage**: Content stored directly in `post_content` using Divi shortcode format. No separate postmeta for layout data.
- **Shortcode hierarchy**: `[et_pb_section]` > `[et_pb_row]` > `[et_pb_column]` > `[et_pb_module]` (e.g., `[et_pb_text]`, `[et_pb_image]`, `[et_pb_button]`).
- **Global settings**: wp_options key `et_divi` (serialized theme options). Additional keys: `et_pb_builder_options`, `et_pb_role_settings`.
- **Theme Builder**: Layouts stored as post type `et_template`. Header, footer, and body templates assigned via `et_template` taxonomy terms.
- **Global modules**: Saved as post type `et_pb_layout` with taxonomy `layout_category` and `layout_type`.
- **Functions**:
  - `et_core_portability_export()`, `et_core_portability_import()` — layout import/export
  - `et_pb_is_pagebuilder_used($post_id)` — check if Divi builder is active
  - `et_get_option($key)` — get Divi theme option
- **Hooks**: `et_pb_before_page_builder`, `et_pb_after_page_builder`, `et_builder_module_init`.
- **Editing content**: Since Divi uses post_content shortcodes, modifications can be done via `wp_update_post()`. Parse and manipulate shortcodes carefully — regex is fragile; use `do_shortcode_tag` patterns.

### Brizy
- **Data storage**: Post meta keys `brizy_post_uid` (unique ID), `brizy_post_editor_data` (JSON string with full page data), `brizy-meta` (additional metadata).
- **Key classes**: `Brizy_Editor_Post` — main post wrapper. `Brizy_Editor_Post::get($post_id)` to retrieve.
- **Functions**:
  - `Brizy_Editor_Post::get($post_id)->get_editor_data()` — get JSON editor data
  - `Brizy_Editor_Post::get($post_id)->set_editor_data($json)` — update editor data
  - `Brizy_Editor_Post::get($post_id)->is_uses_editor()` — check if Brizy is active
- **Compiled HTML**: Stored in post meta `brizy_post_compiled_html`. Also saved to `wp-content/uploads/brizy/`.
- **Global blocks**: Stored as post type `brizy-global-block`. Popups as `brizy-popup`.

### Oxygen
- **Data storage**: Post meta key `ct_builder_shortcodes` (shortcode-format string). Uses custom shortcode syntax similar to Divi but with `[ct_]` prefix.
- **Settings**: wp_options keys prefixed `oxygen_*`. Key options: `oxygen_options`, `oxygen_global_colors`, `oxygen_global_fonts`.
- **Templates**: Post type `ct_template` — reusable design templates applied conditionally. `oxy_user_library` for user-saved components.
- **CSS**: Generated and stored in `wp-content/uploads/oxygen/css/` — universal.css (global) and per-page files.
- **Selectors**: Oxygen uses CSS selectors stored in `ct_builder_shortcodes` and `ct_other_shortcodes` postmeta. Global styles via Oxygen's stylesheet system.
- **Functions**: Limited public API. Content manipulation requires parsing shortcodes from postmeta.
- **Key difference**: Oxygen replaces the theme entirely. When active, the WordPress theme is effectively bypassed for all pages using Oxygen templates.

### WPBakery Page Builder (formerly Visual Composer)
- **Data storage**: Content stored in `post_content` using shortcode format, similar to Divi. Shortcode prefix: `[vc_]`. Hierarchy: `[vc_row]` > `[vc_column]` > `[vc_module]` (e.g., `[vc_column_text]`, `[vc_single_image]`, `[vc_btn]`).
- **Settings**: wp_options key `wpb_js_*`. Key: `wpb_js_content_types` (enabled post types), `wpb_js_compiled_js_composer_less`, `wpb_js_not_responsive_css`.
- **Custom templates**: Stored in wp_options as arrays. User templates in `vc_templates_*` options.
- **Grid builder**: Uses `vc_grid_id` shortcode attribute. Grid data cached in transients.
- **Functions**:
  - `vc_is_page_editable()` — check if in frontend editor
  - `WPBMap::getShortCodes()` — list all registered elements
  - `visual_composer()->parseShortcodesString($content)` — parse VC shortcodes
- **Hooks**: `vc_before_init`, `vc_after_init`, `vc_before_save_post`, `vc_after_mapping`.
- **Element Mapping**: Custom elements registered via `vc_map($params)` with name, base, category, params array.
- **CSS**: Custom CSS per page stored in postmeta `_wpb_shortcodes_custom_css`. Global CSS in `_wpb_post_custom_css`.
- **Key difference from Divi**: Both use post_content shortcodes, but WPBakery uses `[vc_*]` prefix while Divi uses `[et_pb_*]`. Never have both active.
- **Plugin file path**: `js_composer/js_composer.php` (not "wpbakery").
- **Detection**: `defined('WPB_VC_VERSION')` or `class_exists('Vc_Manager')`

### Common Patterns
- All page builders store layout data in postmeta (except Divi, which uses post_content shortcodes). Never have two builders active on the same post.
- Check which builder is active before modifying content: look for the builder's postmeta key or use the builder's `is_active` check function.
- After programmatic changes to any builder's data, clear the builder's CSS/asset cache.
- Standard `post_content` may be empty or contain fallback text when a page builder is active — the real content is in the builder's data store.
- For search operations, you must search the builder-specific data (postmeta or shortcodes), not just post_content.
