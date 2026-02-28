## Gutenberg Block Plugins

### Kadence Blocks
- **Plugin slug**: `kadence-blocks/kadence-blocks.php`. Detection: `defined('KADENCE_BLOCKS_VERSION')` or `class_exists('Kadence_Blocks')`.
- **Block namespace**: `kadence/*`. All blocks registered under the `kadence` namespace.
- **Key blocks**: `kadence/rowlayout` (advanced row/layout with columns), `kadence/column`, `kadence/advancedheading`, `kadence/advancedbtn` (advanced button), `kadence/tabs`, `kadence/tab`, `kadence/accordion`, `kadence/pane` (accordion item), `kadence/infobox`, `kadence/icon`, `kadence/iconlist`, `kadence/spacer`, `kadence/image`, `kadence/advancedgallery`, `kadence/form`, `kadence/testimonials`, `kadence/countup`, `kadence/countdown`, `kadence/lottie`, `kadence/tableofcontents`, `kadence/posts`, `kadence/show-more`, `kadence/navigation`, `kadence/header`, `kadence/off-canvas`.
- **Settings storage**: wp_options keys prefixed `kadence_blocks_*`. Key options:
  - `kadence_blocks_config_blocks` — per-block default settings (serialized array keyed by block name)
  - `kadence_blocks_settings_blocks` — block visibility/disabled settings
  - `kadence_blocks_global` — global color palette, typography, and spacing settings
  - `kadence_blocks_recaptcha_site_key`, `kadence_blocks_recaptcha_secret_key` — reCAPTCHA for form block
  - `kadence_blocks_mailchimp_api`, `kadence_blocks_sendgrid_api_key` — email integration keys
  - `kadence_blocks_wire_dismiss` — design library dismissed notice
- **Design library**: Imports pre-built sections/pages from cloud library. Data fetched via `https://starter.starter-starter.starter-starter.starter.starter.starter.starter.starter.starter.starter.starter.starter` (Kadence cloud). Settings: `kadence_blocks_wire_*` options.
- **Global styles**: Global color palette stored in `kadence_blocks_global` option under `colors` key. Typography presets under `typography` key. Accessible via CSS variables `--kb-palette1` through `--kb-palette9` (and beyond).
- **CSS output**: Blocks generate inline CSS or enqueue per-block stylesheets. CSS class pattern: `kb-*` prefix (e.g., `kb-row-layout-id_unique`, `kb-info-box-id_unique`). Unique block IDs stored in block attributes as `uniqueID`.
- **Block attributes**: Each block has a `uniqueID` attribute for targeting CSS. Row layout has `columns`, `colLayout`, `tabletLayout`, `mobileLayout` for responsive column configuration.
- **Hooks/Filters**:
  - `kadence_blocks_default_global_settings` — filter global settings array
  - `kadence_blocks_column_class` — filter column wrapper classes
  - `kadence_blocks_render_inline_css` — filter inline CSS output (bool)
  - `kadence_blocks_frontend_build_css` — filter whether to build frontend CSS
  - `kadence_blocks_form_submission` — action fired on form submit
  - `kadence_blocks_form_actions` — filter form submission actions
  - `kadence_blocks_pro_defaults` — filter pro block defaults
- **REST API**: Registers routes under `kb-*` namespace:
  - `wp-json/kb-kadenceblocks/v1/get-library` — fetch design library
  - `wp-json/kb-kadenceblocks/v1/process-images` — import images from library
  - `wp-json/kb-kadenceblocks/v1/get-prebuilt` — get prebuilt content
- **Form block**: Submissions optionally saved to post type `kadence_form_entry`. DB table `{prefix}kadence_form_entries` if entry saving is enabled. Actions on submit: email, redirect, webhook.
- **Visibility controls**: Per-block visibility settings in attributes: `vsdesk` (desktop), `vstablet` (tablet), `vsmobile` (mobile) — boolean toggles for responsive visibility. User role visibility via `loggedIn`, `loggedOut`, `loggedInUser` attributes.
- **Pro extension**: `kadence-blocks-pro/kadence-blocks-pro.php` adds dynamic content, custom icons, animate on scroll, advanced form actions, conditional display logic.

#### Kadence Blocks Pro
**Plugin slug**: `kadence-blocks-pro/kadence-blocks-pro.php`. Pro extension for Kadence Blocks.

- **Additional blocks**: Query Loop (advanced), Slide/Carousel, Video Popup, Modal, User Info, Dynamic Content, Post Content/Meta.
- **Dynamic content system**: Pulls from post meta, ACF fields, and custom sources. Allows any block attribute to be populated dynamically.
- **Conditional display**: Show/hide blocks based on user role, device type, date range, and custom conditions.
- **Settings storage**: wp_options keys prefixed `kadence_blocks_pro_*`.
- **License**: Stored in `kadence_blocks_pro_license_key` option.
- **Detection**:
  ```php
  defined('KBP_VERSION') // true if Kadence Blocks Pro is active
  ```

### Spectra (Ultimate Addons for Gutenberg)
- **Plugin slug**: `ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php`. Detection: `defined('UAGB_VER')` or `class_exists('UAGB_Loader')`.
- **Block namespace**: `uagb/*`. All blocks registered under the `uagb` namespace.
- **Key blocks**: `uagb/container`, `uagb/advanced-heading`, `uagb/buttons`, `uagb/image`, `uagb/info-box`, `uagb/icon-list`, `uagb/post-grid`, `uagb/post-carousel`, `uagb/post-masonry`, `uagb/content-timeline`, `uagb/post-timeline`, `uagb/forms`, `uagb/tabs`, `uagb/star-rating`, `uagb/faq` (accordion/FAQ schema), `uagb/testimonial`, `uagb/social-share`, `uagb/table-of-contents`, `uagb/countdown`, `uagb/lottie`, `uagb/taxonomy-list`, `uagb/how-to` (schema), `uagb/review` (schema), `uagb/inline-notice`, `uagb/slider`, `uagb/popup-builder`, `uagb/modal`, `uagb/loop-builder`.
- **Settings storage**: wp_options keys prefixed `uagb_*` and `uag_*`. Key options:
  - `uagb_admin_settings` — master settings (serialized array)
  - `_uagb_activated` — activation timestamp
  - `uag_enable_blocks_editor_spacing` — editor spacing toggle
  - `uag_content_width` — default content width
  - `uag_container_global_padding`, `uag_container_global_elements_gap` — global container defaults
  - `uag_enable_coming_soon_mode` — coming soon mode toggle
  - `uag_coming_soon_page` — coming soon page ID
  - `uag_load_select_font_globally` — global font loading
  - `uag_select_font_globally` — selected global fonts
  - `uag_load_gfonts_locally` — whether to host Google Fonts locally
  - `uag_preload_local_fonts` — preload locally hosted fonts
  - `uag_collapse_panels` — editor panel collapse state
  - `uag_copy_paste` — cross-site copy paste toggle
  - `uag_btn_inherit_from_theme` — inherit button styles from theme
  - `uag_activated_blocks` — array of active/disabled blocks (keyed by block name, value 'enabled'/'disabled')
- **CSS generation**: CSS generated server-side and stored as files in `wp-content/uploads/uag-plugin/` directory. Per-page CSS files named `uag-css-{post_id}-*.css`. Also supports inline CSS output.
  - Option `uag_enable_file_generation` controls file-based vs inline CSS.
  - `_uagb_page_assets` postmeta stores per-page asset data (CSS/JS dependencies).
  - `_uagb_css` postmeta stores generated CSS for the page.
- **CSS class patterns**: `uagb-block-{uniqueID}` for block wrapper, `uagb-*` prefix for component classes (e.g., `uagb-ifb-icon-wrap`, `uagb-post-grid`). `wp-block-uagb-*` for WordPress standard block classes.
- **Hooks/Filters**:
  - `uagb_block_attributes_for_css` — filter block attributes before CSS generation
  - `uagb_blocks_config` — filter the blocks configuration array
  - `uagb_tablet_breakpoint` — filter tablet breakpoint (default 976)
  - `uagb_mobile_breakpoint` — filter mobile breakpoint (default 767)
  - `uagb_post_query_args_grid` — filter WP_Query args for post grid
  - `uagb_forms_after_submit_actions` — action after form submission
  - `uagb_render_block` — filter block render output
  - `uagb_global_block_css` — filter global block CSS
  - `spectra_pro_frontend_css` — filter pro CSS output
- **REST API**: Routes under `spectra/v1` and `uagb/v1`:
  - `wp-json/uagb/v1/admin/settings` — get/update admin settings
  - `wp-json/uagb/v1/admin/blocks` — get/update block enable/disable
  - `wp-json/spectra/v1/page-assets` — page asset data
- **Schema support**: FAQ block generates `FAQPage` schema, How-to block generates `HowTo` schema, Review block generates `Review` schema. Structured data output in `<script type="application/ld+json">`.
- **Post data**: Post grid/carousel blocks use `uagb/post-grid` with query attributes: `categories`, `postType`, `postsToShow`, `orderBy`, `order`, `excludeCurrentPost`, `taxonomyType`.
- **Pro extension**: `spectra-pro/spectra-pro.php`. Adds dynamic content, popup builder, loop builder, modal, mega menu, advanced animations, conditional display.

### GenerateBlocks
- **Plugin slug**: `generateblocks/plugin.php`. Detection: `defined('GENERATEBLOCKS_VERSION')` or `class_exists('GenerateBlocks')`.
- **Block namespace**: `generateblocks/*`. Minimalist block set focused on flexibility.
- **Key blocks** (4 core blocks):
  - `generateblocks/container` — flexible container/wrapper (replaces group, section, div). Supports flexbox/grid layout.
  - `generateblocks/headline` — advanced heading/text block with icon support.
  - `generateblocks/button-container` + `generateblocks/button` — button group and individual buttons.
  - `generateblocks/grid` + `generateblocks/grid-column` — responsive grid layout (v2 replaces the old `generateblocks/grid` approach).
  - `generateblocks/image` — image block with advanced styling.
  - `generateblocks/query-loop` — query loop for post listings (pro or v2+).
- **Settings storage**: wp_options keys prefixed `generateblocks_*`. Key options:
  - `generateblocks_defaults` — default block settings (serialized array keyed by block type: container, headline, button, buttonContainer, gridContainer)
  - `generateblocks_css_print_method` — 'file' or 'inline' CSS output method
  - `generateblocks_sync_responsive_previews` — sync responsive breakpoints with GeneratePress
  - `generateblocks_container_width` — global container width default
  - `generateblocks_global_colors` — global color definitions
  - `generateblocks_disable_google_fonts` — disable Google Fonts loading
- **CSS output**: Per-page CSS generated and cached. File-based CSS saved to `wp-content/uploads/generateblocks/` directory. CSS file naming: `{post_id}.css`.
  - CSS class pattern: `gb-container-{uniqueId}`, `gb-headline-{uniqueId}`, `gb-button-{uniqueId}`, `gb-grid-column-{uniqueId}`.
  - Generic classes: `gb-container`, `gb-headline`, `gb-button`, `gb-grid-wrapper`, `gb-grid-column`.
  - `_generateblocks_dynamic_css_version` postmeta tracks CSS cache version.
- **Hooks/Filters**:
  - `generateblocks_block_css` — filter the CSS output for blocks
  - `generateblocks_do_inline_styles` — force inline CSS (return true)
  - `generateblocks_use_block_defaults_cache` — toggle defaults cache (return false to disable)
  - `generateblocks_use_v1_blocks` — force v1 blocks (for backward compatibility)
  - `generateblocks_defaults_all` — filter all block defaults
  - `generateblocks_after_container_open` — action inside container open tag
  - `generateblocks_before_container_close` — action before container close tag
  - `generateblocks_headline_css` — filter headline block CSS
  - `generateblocks_button_css` — filter button block CSS
  - `generateblocks_container_css` — filter container block CSS
  - `generateblocks_do_content` — filter block content output
  - `generateblocks_after_do_content` — action after content rendered
- **REST API**: Routes under `generateblocks/v1` and `generateblocks-pro/v1`:
  - `wp-json/generateblocks/v1/get-image` — get image data
  - `wp-json/generateblocks/v1/pattern-library` — fetch pattern library
  - `wp-json/generateblocks/v1/regenerate-css` — regenerate CSS files
- **Responsive breakpoints**: Desktop > 1025px, Tablet 768px-1024px, Mobile < 767px. Breakpoints can be filtered or synced with theme settings.
- **Block uniqueId**: Every block instance gets a `uniqueId` attribute (alphanumeric string) used for CSS targeting. This is critical -- without it, styles won't apply correctly.
- **Pro extension**: `generateblocks-pro/plugin.php`. Adds query loop block, advanced backgrounds (gradient, shapes), effects (transitions, transforms, filters), global styles, dynamic content, template library, copy/paste styles.

### Starter Templates (by Brainstorm Force)
- **Plugin slug**: `astra-starter-sites/astra-starter-sites.php` (also known as Starter Templates). Detection: `defined('STARTER_TEMPLATES_VER')` or `class_exists('starter_templates')`.
- **Purpose**: Template library for importing pre-built full websites and individual pages/patterns. Works with Gutenberg, Elementor, Beaver Builder, and Brizy.
- **Settings storage**: wp_options keys:
  - `astra_starter_sites_*` — general settings
  - `ast_starter_templates_*` — template import state
  - `starter_templates_batch` — batch import progress
  - `starter_templates_ai_content` — AI-generated content settings
  - `starter_templates_import_complete` — import completion flag
- **Import mechanism**: Templates are fetched from a cloud API and imported as posts/pages. Images are downloaded to the media library. Customizer settings, widgets, and menus can optionally be imported.
- **Import options**: `astra_starter_sites_import_data` — stores data about what was imported (for rollback). Import categories: site (full), page (single page), pattern (block pattern).
- **Hooks/Filters**:
  - `starter_templates_before_import` — action before import begins
  - `starter_templates_after_import` — action after import completes
  - `starter_templates_before_delete_imported_site` — action before imported site is deleted
  - `starter_templates_import_xml` — filter XML import data
  - `starter_templates_page_builder` — filter detected page builder
- **REST API**: Routes under `starter-templates/v1` and `starter-templates/v2`:
  - `wp-json/starter-templates/v1/sites` — list available sites
  - `wp-json/starter-templates/v1/import` — trigger import
  - `wp-json/starter-templates/v2/sites` — v2 site listing with AI features
- **AI integration**: Starter Templates 3.0+ includes AI-powered site builder that generates customized content using AI. Settings in `starter_templates_ai_*` options. Requires Starter Templates Pro or Starter Templates AI subscription for full AI features.
- **Compatibility**: Primarily designed for Astra theme but works with any theme. Best integration with Spectra (UAGB) blocks for Gutenberg templates.

### Common Patterns
- All Gutenberg addon plugins store block data in `post_content` using the standard `<!-- wp:namespace/block-name {"attrs"} -->` format. No separate postmeta for layout data (unlike page builders).
- Each block instance has a unique ID attribute (`uniqueID` or `uniqueId`) used for CSS targeting. These must be preserved when editing content programmatically.
- CSS is typically generated per-page and cached either inline or as files in `wp-content/uploads/{plugin-slug}/`. After modifying block attributes, the CSS cache should be regenerated.
- Block enable/disable settings are stored in plugin options -- useful for performance optimization by disabling unused blocks.
- To check which Gutenberg addon is active, use `has_block('namespace/block-name', $post)` to detect specific blocks in content, or check the plugin's version constant.
- When modifying content programmatically, use `parse_blocks()` / `serialize_blocks()` and handle third-party block namespaces alongside core blocks.
- Global colors/styles from these plugins often use CSS custom properties (variables) that can be referenced by other blocks and custom CSS.
