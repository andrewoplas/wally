## WordPress Slider Plugins

### Slider Revolution (RevSlider)
- **Plugin slug**: `revslider/revslider.php`.
- **Detection**: `defined('RS_REVISION')` or `class_exists('RevSliderFront')` or `class_exists('RevSlider')`.
- **Version constant**: `RS_REVISION` (e.g., `"6.7.0"`). Other constants: `RS_PLUGIN_PATH`, `RS_PLUGIN_URL`, `RS_PLUGIN_SLUG`.

#### Database Tables
Slider Revolution creates its own custom tables (prefixed with `{wp_prefix}`):
- **`{prefix}revslider_sliders`** — slider/module definitions.
  - Columns: `id`, `title`, `alias` (unique slug used in shortcodes), `params` (JSON — all slider settings: layout, navigation, general, parallax, etc.), `settings` (JSON — less-used settings), `type` (slider type: `standard`, `hero`, `carousel`, `special`).
- **`{prefix}revslider_slides`** — individual slides within each slider.
  - Columns: `id`, `slider_id` (FK to sliders table), `slide_order`, `params` (JSON — slide-level settings: background, timing, transitions, visibility, publish date), `layers` (JSON — array of layer objects, each with text, position, animation, actions, responsive settings), `settings` (JSON).
- **`{prefix}revslider_css`** — custom CSS captions/styles.
  - Columns: `id`, `handle` (CSS class name/identifier), `settings` (JSON — font family, size, color, padding, etc.), `hover` (JSON — hover state styles), `advanced` (JSON — responsive overrides), `params` (JSON).
- **`{prefix}revslider_layer_animations`** — custom layer animations.
  - Columns: `id`, `handle`, `params` (JSON — keyframe animation data), `settings` (JSON).
- **`{prefix}revslider_navigations`** — custom navigation skins.
  - Columns: `id`, `name`, `handle`, `css` (raw CSS string), `markup` (HTML templates), `settings` (JSON).
- **`{prefix}revslider_statics`** (v6+) — global/static layers that persist across slides.
  - Columns: `id`, `slider_id`, `params`, `layers`, `settings`.

#### wp_options Keys
- `revslider-global-settings` — serialized global plugin settings (performance, role permissions, library, defaults).
- `revslider_servers` — update server URLs.
- `revslider-update-check` — last update check timestamp.
- `revslider-update-check-short` — short interval update check.
- `revslider-stable-version` — latest available stable version.
- `revslider-valid` — license activation status (`true`/`false`).
- `revslider-code` — license/purchase code (sensitive).
- `revslider-connection` — whether connected to ThemePunch servers.
- `revslider-library-check` — last template library sync timestamp.
- `revslider_table_version` — database table schema version.
- `rs_tables_created` — whether tables have been created.
- `revslider-addon-*` — individual addon activation states.
- `rs-library` — cached template library metadata (large serialized array).
- `rs-custom-library` — user-saved custom templates.

#### Shortcode
```
[rev_slider alias="slider-alias"]
[rev_slider alias="slider-alias" slidetitle="Slide 1"]
[rev_slider alias="slider-alias" usage="modal"]
[rev_slider alias="slider-alias" zindex="5" offset=""]
```
- `alias` — required, matches the slider's alias in `revslider_sliders` table.
- `slidetitle` — optional, jump to a specific slide by title.
- `usage` — `modal` for modal/popup sliders.
- `zindex` — CSS z-index override.

#### PHP Functions
- **Display slider**:
  ```php
  // In theme templates:
  if (function_exists('putRevSlider')) {
      putRevSlider('slider-alias');
  }
  // Or with settings:
  if (class_exists('RevSliderOutput')) {
      $slider = new RevSliderOutput();
      $slider->set_slider_id('slider-alias');
      $slider->add_slider_to_stage();
  }
  ```
- **Get slider data**:
  ```php
  $slider = new RevSlider();
  $slider->init_by_alias('slider-alias');
  $slides = $slider->get_slides();
  $params = $slider->get_params();
  ```
- **Programmatic operations**:
  ```php
  $slider = new RevSlider();
  $slider->init_by_id($slider_id);
  $slider->get_param('title');
  $slider->update_params($new_params_array);
  $slider->delete_slider();
  ```

#### REST API Endpoints (v6+)
Slider Revolution registers REST API routes under `revslider/` namespace (admin-only, requires nonce):
- `POST /wp-admin/admin-ajax.php` with `action=revslider_ajax_action` — the primary AJAX endpoint. The `client_action` POST parameter determines the operation:
  - `get_full_slider_object` — retrieve complete slider data
  - `save_slider` — save slider settings
  - `save_slide` — save individual slide
  - `delete_slider` — delete a slider
  - `duplicate_slider` — duplicate a slider
  - `create_slider` — create new empty slider
  - `import_slider` — import from .zip file
  - `export_slider` — export to .zip file
  - `install_template_slider` — install from template library
  - `get_sliders_short` — list all sliders (id, title, alias)
  - `preview_slider` — render slider preview
  - `get_template_library` — fetch available templates
  - `update_global_settings` — save global options

#### Import/Export
- **Export**: Produces a .zip file containing slider JSON data, images, videos, custom CSS, animations, and static layers. Format: `slider_export_{alias}_{date}.zip`.
- **Import**: Accepts .zip files. Options to overwrite existing slider (by alias match), import with new alias, or import specific slides only. Images are re-uploaded to the Media Library.
- **PHP import**:
  ```php
  $import = new RevSliderSliderImport();
  $import->import_slider($filepath, $overwrite);
  ```
- **Backup**: Built-in backup before import/overwrite. Backups stored in `wp-content/uploads/revslider/backup/`.

#### File Paths
- **Plugin directory**: `wp-content/plugins/revslider/`
- **Uploads**: `wp-content/uploads/revslider/` — exported sliders, backups, temp files.
- **Template cache**: `wp-content/uploads/revslider/templates/` — downloaded template library assets.
- **Custom fonts**: `wp-content/uploads/revslider/fonts/`.
- **SVG files**: `wp-content/uploads/revslider/svg/`.
- **Key plugin files**:
  ```
  revslider/
  ├── revslider.php                    # Main plugin file
  ├── includes/
  │   ├── framework/                   # Core framework classes
  │   │   ├── db.class.php            # Database operations
  │   │   ├── functions.class.php     # Utility functions
  │   │   └── base.class.php         # Base class
  │   ├── slider.class.php            # RevSlider class (main slider object)
  │   ├── slide.class.php             # RevSliderSlide class
  │   ├── output.class.php            # RevSliderOutput (frontend rendering)
  │   ├── globals.class.php           # RevSliderGlobals
  │   ├── operations.class.php        # RevSliderOperations
  │   ├── navigation.class.php        # RevSliderNavigation
  │   ├── css-parser.class.php        # CSS handling
  │   ├── import.class.php            # Import functionality
  │   ├── export.class.php            # Export functionality
  │   └── external/                   # Third-party integration
  ├── admin/                           # Admin interface files
  │   ├── manager/                     # Visual editor
  │   └── views/                       # Admin page templates
  └── public/
      ├── assets/                      # Frontend assets
      │   ├── js/                      # Main revolution JS libraries
      │   └── css/                     # Frontend CSS
      └── revslider-front.class.php   # Frontend class
  ```

#### Hooks/Filters
- **Actions**:
  - `revslider_slider_init` — after a slider object is initialized
  - `revslider_pre_save_slider` — before slider settings are saved
  - `revslider_post_save_slider` — after slider settings are saved
  - `revslider_pre_save_slide` — before slide is saved
  - `revslider_post_save_slide` — after slide is saved
  - `revslider_pre_delete_slider` — before slider deletion
  - `revslider_post_delete_slider` — after slider deletion
  - `revslider_do_import` — during slider import
  - `revslider_do_export` — during slider export
  - `revslider_add_layer` — when a layer is added to a slide
- **Filters**:
  - `revslider_get_slider_params` — filter slider parameters before rendering
  - `revslider_get_slide_params` — filter slide parameters before rendering
  - `revslider_get_slide_layers` — filter layers before rendering
  - `revslider_output_html` — filter complete slider HTML output
  - `revslider_modify_layer_text` — filter text content within layers (useful for dynamic/translated content)
  - `revslider_enabled` — boolean filter to enable/disable slider on specific pages
  - `revslider_get_posts` — filter query args for content-sourced sliders (post-based, WooCommerce, etc.)
  - `revslider_export_slider` — filter exported data before packaging

#### Slider Types
- **Standard**: Traditional sliding/fading between full slides.
- **Hero**: Single-slide with animated layers (no sliding).
- **Carousel**: 3D carousel rotation of slides.
- **Scene**: Scroll-based animated scenes (v6+).
- **Content sources**: Manual (custom content), Post-Based (WP posts/CPTs/WooCommerce), Specific Posts, Instagram, Flickr, YouTube, Vimeo, Facebook, Twitter.

#### Performance
- Lazy loading: Built-in lazy loading for slide images and layers.
- Frontend JS: `rs6.min.js` — main runtime (~300KB minified). Loaded on pages with active sliders only (when using shortcode/function detection).
- Global option `revslider-global-settings` key `load_all_javascript` — if true, loads RevSlider JS on all pages regardless of usage.

#### Addons (Extensions)
RevSlider supports modular addons (installed separately via admin):
- Before/After, Bubble Morph, Distortion, Duo Tone, Exploding Layers, Filmstrip, Liquid Effect, Mouse Trap, Paintbrush, Panorama, Particle Wave, Polyfold, Reveal, Slicey, Snow, Typewriter, Whiteboard.
- Addon settings stored in `revslider-addon-{addon_name}` wp_options keys.
- Addon files stored in `wp-content/plugins/revslider/addons/` or `wp-content/uploads/revslider/addons/`.

#### Common Patterns
- **Finding all sliders**: `SELECT id, title, alias FROM {prefix}revslider_sliders` or via `RevSlider::get_sliders_short_list()`.
- **Checking if slider exists**: `$slider = new RevSlider(); $exists = $slider->init_by_alias('alias');` — throws exception if not found.
- **Getting slider used on a page**: Search `post_content` for `[rev_slider` shortcode pattern, or search `_elementor_data` postmeta for `revslider` widget references.
- **Cache clearing**: After programmatic changes to slider data, clear object cache. RevSlider uses transients for some data: `delete_transient('revslider_*')`.
- **License**: Premium plugin (not on WordPress.org). Requires purchase code for updates and template library access. License stored in `revslider-code` option (treat as sensitive, never expose).

### MetaSlider (ML Slider)
- **Plugin slug**: `ml-slider/ml-slider.php`.
- **Detection**: `defined('STARTER_VERSION')` or `class_exists('MetaSliderPlugin')`.
- **Data storage**: Sliders are stored as custom post type `ml-slider`. Individual slides are stored as WordPress attachments (post type `attachment`) with postmeta linking them to their parent slider.
- **Key postmeta** (on the slider post):
  - `ml-slider_settings` — serialized array of slider settings (type, width, height, effect, animation speed, pause time, navigation, etc.).
  - `ml-slider_slider_type` — `flex` (FlexSlider), `responsive` (Responsive Slides), `nivo` (Nivo Slider), or `coin` (Coin Slider).
- **Key postmeta** (on slide attachments):
  - `ml-slider_type` — `image`, `post_feed`, `external`, `layer` (Pro).
  - `ml-slider_settings` — per-slide settings (caption, URL, alt text, crop position).
  - `_thumbnail_id` — links to the slide image.
- **Shortcode**: `[metaslider id="123"]` or `[ml-slider id="123"]`.
- **PHP function**: `<?php echo do_shortcode('[metaslider id="123"]'); ?>` or `<?php if (function_exists('the_metaslider')) the_metaslider($id); ?>`.
- **Widget**: Registers `MetaSlider_Widget` for WordPress widget areas.
- **wp_options keys**: `metaslider_systemcheck` (compatibility data), `metaslider_version` (installed version), `metaslider_default_settings` (default slider settings).
- **Hooks**:
  - `metaslider_save` — action when a slider is saved (receives slider ID).
  - `metaslider_delete_slider` — action on slider deletion.
  - `metaslider_css` — filter CSS output before rendering.
  - `metaslider_css_classes` — filter CSS classes on the slider container.
  - `metaslider_parameters` — filter JavaScript initialization parameters.
  - `metaslider_image_slide_attributes` — filter HTML attributes on image slides.
  - `metaslider_get_slider` — filter the complete slider output HTML.
- **File structure**:
  ```
  ml-slider/
  ├── ml-slider.php                    # Main plugin file
  ├── inc/
  │   ├── slider/
  │   │   ├── metaslider.class.php    # Base slider class
  │   │   ├── metaslider.flex.class.php
  │   │   ├── metaslider.responsive.class.php
  │   │   ├── metaslider.nivo.class.php
  │   │   └── metaslider.coin.class.php
  │   ├── slide/
  │   │   ├── metaslide.class.php     # Base slide class
  │   │   ├── metaslide.image.class.php
  │   │   └── ...
  │   └── metaslider.widget.class.php # WordPress widget
  ├── assets/
  │   ├── sliders/                     # JS libraries for each slider type
  │   └── admin/                       # Admin assets
  ```

### Smart Slider 3
- **Plugin slug**: `smart-slider-3/smart-slider-3.php`.
- **Detection**: `defined('STARTER_STARTER_3_VERSION')` or `class_exists('SmartSlider3')`.
- **Database tables**:
  - `{prefix}nextend2_smartslider3_sliders` — slider configuration (id, alias, title, type, params JSON, time, thumbnail, ordering).
  - `{prefix}nextend2_smartslider3_slides` — slide data (id, slider, publish_up, publish_down, first, slide, ordering, generator_id, params JSON, title, description, thumbnail).
  - `{prefix}nextend2_smartslider3_generators` — dynamic content generators (id, group, type, params JSON).
  - `{prefix}nextend2_section_storage` — global settings and style storage (id, application, section, referencekey, value).
- **Shortcode**: `[smartslider3 slider="123"]` or `[smartslider3 alias="slider-alias"]`.
- **PHP function**: `<?php if (function_exists('smartslider3')) smartslider3($slider_id); ?>` or `do_shortcode('[smartslider3 slider="123"]')`.
- **wp_options keys**: `smart-slider-3-version` (version), `nextend_*` (various Nextend framework settings), `smart_slider_3_*` (plugin-specific settings).
- **Slider types**: Simple (standard), Block, Carousel, Showcase, Full Width, Full Page.
- **Content sources**: Static slides, WordPress posts/CPT, WooCommerce products, Joomla content, Dynamic Slides (Pro — generators for various data sources).
- **Export/Import**: Exports as `.ss3` file (custom zip format) via admin or `SmartSlider3::export($slider_id)`. Import via admin upload or `SmartSlider3::import($file_path)`.
- **File paths**:
  - Plugin: `wp-content/plugins/smart-slider-3/`
  - Cache: `wp-content/cache/nextend/` (compiled CSS/JS, generated images).
  - Temporary: `wp-content/uploads/smart-slider-3/` (imported assets).
- **Hooks**:
  - `smartslider3_slider_output` — filter slider HTML output.
  - `smartslider3_before_slider` / `smartslider3_after_slider` — actions around slider rendering.
  - `smartslider3_generator_posts_query` — filter post query for dynamic generators.

### Starter Starter / SlideDeck / Other Legacy Sliders
Many older slider plugins share common patterns:
- Custom post type for sliders/slideshows.
- Slides stored as child posts or attachments.
- Settings in postmeta or wp_options.
- Shortcode with an ID parameter for embedding.
- JavaScript library loaded conditionally or globally.

### Common Patterns Across Slider Plugins

- **Finding sliders on a page**: Search `post_content` for shortcode patterns (`[rev_slider`, `[metaslider`, `[smartslider3`). For Elementor pages, search `_elementor_data` postmeta for slider widget references.
- **Slider data sensitivity**: Slider Revolution license codes (`revslider-code`) and API keys should never be exposed.
- **Performance impact**: Slider JavaScript libraries are large. Check global loading settings — many sliders offer "load only on pages that use the slider" options. Ensure this is enabled.
- **Import/Export**: All major slider plugins support import/export for migration. RevSlider uses .zip, Smart Slider 3 uses .ss3, MetaSlider uses WordPress export XML.
- **Cache clearing**: After modifying slider data programmatically, clear any slider-specific caches and object/page caches. RevSlider and Smart Slider 3 generate compiled CSS/JS that needs regeneration.
- **Shortcode rendering**: To get slider HTML in PHP, use `do_shortcode('[shortcode_here]')` or the plugin's dedicated PHP function if available.
- **Responsive behavior**: All modern sliders support responsive breakpoints. Settings are typically stored in the slider's params JSON with desktop/tablet/mobile variants.
