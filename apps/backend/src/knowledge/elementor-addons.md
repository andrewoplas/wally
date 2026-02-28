## Elementor Addon Plugins

Note: Core Elementor has its own dedicated knowledge file (`elementor.md`). This covers third-party Elementor addon/extension plugins.

### Essential Addons for Elementor
- **Plugin slug**: `essential-addons-for-elementor-lite/essential_adons_elementor.php` (note the typo in the original filename — "adons" not "addons").
- **Pro version**: `essential-addons-elementor/essential_adons_elementor.php` (separate plugin).
- **Detection**: `defined('EAEL_PLUGIN_VERSION')` or `class_exists('\\Essential_Addons_Elementor\\Classes\\Bootstrap')`.
- **Settings storage**: wp_options key `eael_save_settings` (serialized array). Each widget/extension is a key with value `1` (enabled) or empty/absent (disabled). Example keys: `post-grid`, `post-timeline`, `fancy-text`, `count-down`, `creative-btn`, `team-members`, `testimonials`, `flip-box`, `info-box`, `dual-header`, `price-table`, `filterable-gallery`, `data-table`, `content-toggle`, `tooltip`, `adv-tabs`, `adv-accordion`, `progress-bar`, `feature-list`, `woo-product-grid`.
- **Additional options**:
  - `eael_admin_notices` — dismissed admin notices
  - `eael_version` — installed version string
  - `eael_setup_wizard` — whether setup wizard has been completed
  - `widget_eael-*` — widget instance data for sidebar widgets (if any)
- **Widget registration**: Hooks into `elementor/widgets/register`. All widgets extend `\Elementor\Widget_Base` and are namespaced under `\Essential_Addons_Elementor\Elements\`. Widget class names follow the pattern `Eael_Widget_Name` (e.g., `Post_Grid`, `Fancy_Text`, `Filterable_Gallery`).
- **Widget categories**: Registers custom category `essential-addons-elementor` via `elementor/elements/categories_registered`.
- **Key widget types (50+ total)**:
  - **Content**: Post Grid, Post Timeline, Post Carousel, Post Block, Content Timeline, Data Table, Advanced Tabs, Advanced Accordion, Content Toggle, Tooltip
  - **Creative**: Fancy Text, Creative Buttons, Countdown, Team Members, Testimonials, Testimonial Slider, Info Box, Flip Box, Dual Color Header, Price Table, Feature List
  - **Marketing**: Call to Action, Filterable Gallery, Image Accordion, Logo Carousel, Progress Bar, Counter, Interactive Promo
  - **Form Integrations**: Contact Form 7, WPForms, Ninja Forms, Gravity Forms, Caldera Forms, FluentForm, Formstack, Typeform
  - **WooCommerce (Pro)**: Product Grid, Product Carousel, Woo Checkout, Woo Cart, Woo Product Compare, Woo Product Gallery
- **File structure**:
  ```
  essential-addons-for-elementor-lite/
  ├── essential_adons_elementor.php    # Main plugin file
  ├── includes/
  │   ├── Classes/
  │   │   ├── Bootstrap.php            # Main loader, registers everything
  │   │   ├── Elements_Manager.php     # Widget/extension management
  │   │   ├── Asset_Builder.php        # CSS/JS asset generation
  │   │   └── WPML_Compatibility.php   # WPML translation support
  │   ├── Elements/                    # Widget classes (one per file)
  │   ├── Extensions/                  # Section/column extensions
  │   ├── Traits/                      # Shared traits (Ajax_Handler, Helper, etc.)
  │   └── Template/                    # Widget render templates
  ├── assets/
  │   ├── front/css/                   # Per-widget frontend CSS
  │   ├── front/js/                    # Per-widget frontend JS
  │   └── admin/                       # Admin assets
  ```
- **Asset loading**: Uses a custom asset builder that only loads CSS/JS for widgets actually used on a page. Assets stored in `wp-content/uploads/essential-addons-elementor/` after optimization.
- **Extensions** (apply to sections/columns, not standalone widgets): Parallax, Particles, Content Protection, Reading Progress Bar, Table of Contents, Post Duplicator, Custom JS.
- **Hooks/Filters**:
  - `eael/active_plugins` — filter list of active EAEL widgets
  - `eael/is_pro` — boolean filter for pro check
  - `eael/{widget_name}/query_args` — filter WP_Query args for post-based widgets (e.g., `eael/post-grid/query_args`)
  - `eael/{widget_name}/markup` — filter rendered HTML output
  - `eael/template/{widget_name}` — filter template file path for a widget
  - Action: `eael/before_widget_render`, `eael/after_widget_render`
- **AJAX endpoints**: Uses `wp_ajax_eael_*` actions for dynamic loading. Key: `wp_ajax_load_more` (infinite scroll/load more for post widgets), `wp_ajax_eael_product_*` (WooCommerce), `wp_ajax_nopriv_*` equivalents for logged-out users.
- **Template overrides**: Widget templates can be overridden in the theme at `theme/essential-addons-elementor/{widget-name}/`.

### ElementsKit Elementor Addons
- **Plugin slug**: `elementskit-lite/elementskit-lite.php`.
- **Pro version**: `elementskit/elementskit.php` (separate plugin).
- **Detection**: `defined('STARTER_STARTER_VERSION')` (lite) or more reliably `class_exists('\\ElementsKit_Lite\\Plugin')` or `defined('STARTER_STARTER_FILE')`.
- **Settings storage**: wp_options key `elementskit_options` (serialized array). Sub-keys for widget toggles follow `widget_{widget_name}` pattern with value `active` or empty.
  - `widget_list` — serialized array of enabled/disabled widgets
  - `module_list` — serialized array of enabled/disabled modules
  - `user_data` — license key and user info (Pro)
- **Additional options**:
  - `elementskit_lite_version` / `elementskit_version` — installed version
  - `ekit_widget_builder_posts` — stored header/footer template post IDs
  - `elementskit_megamenu_settings_{menu_id}` — per-menu mega menu config
- **Widget registration**: Hooks into `elementor/widgets/register`. Widgets extend `\Elementor\Widget_Base` and are namespaced under `\ElementsKit_Lite\Widgets\{Widget_Name}\{Widget_Name}`. Each widget is in its own directory with the widget class and render templates.
- **Widget categories**: Registers `elementskit`, `elementskit_headerfooter` categories via `elementor/elements/categories_registered`.
- **Key widget types (85+ total)**:
  - **Header/Footer**: Nav Menu, Site Logo, Site Title, Search, Header Info, Header Offcanvas
  - **Content**: Heading, Icon Box, Image Box, Image Accordion, Image Comparison, FAQ, Funfact (Counter), Piechart, Progressbar, Testimonial, Team, Countdown, Business Hours, Tab, Accordion, Pricing Table, Client Logo, Social, Blog Posts
  - **Creative**: Lottie Animation, Motion Text, Interactive Circle Infographic, Video, Gallery, Popup Modal, Table, Chart, Timeline, Unfold
  - **Form Integrations**: Contact Form 7, WPForms, Mailchimp, Fluent Forms
  - **WooCommerce (Pro)**: Product Grid, Product Carousel, Product Tab, Mini Cart, Category List
- **Mega Menu Builder**:
  - Stored as custom post type `elementskit_template` with meta `_elementskit_template_type` = `megamenu`.
  - Menu item settings stored in postmeta on the nav_menu_item: `_menu_item_megamenu`, `_menu_item_megamenu_width`, `_menu_item_megamenu_template_id`.
  - Admin: Adds mega menu options to WordPress Appearance > Menus via JS overlay panel.
  - Hooks: `elementskit/megamenu/render/before`, `elementskit/megamenu/render/after`.
- **Header/Footer Builder**:
  - Templates stored as custom post type `elementskit_template`.
  - Template types: `header`, `footer`, `section` (saved sections).
  - Postmeta: `_elementskit_template_type`, `_elementskit_template_conditions` (where to display — entire site, specific pages, etc.), `_elementskit_template_activation` (active/inactive).
  - Overrides theme header/footer via `get_header` and `get_footer` hooks: `get_header` action hook filtered to load Elementor template instead.
- **Modules** (non-widget features): Header Footer Builder, Mega Menu Builder, Widget Builder, Onepage Scroll, Sticky Content, Icon Pack (custom icon libraries), Parallax Effects.
- **File structure**:
  ```
  elementskit-lite/
  ├── elementskit-lite.php             # Main plugin file
  ├── plugin.php                       # Plugin class, bootstrapper
  ├── widgets/                         # Widget directories
  │   ├── nav-menu/
  │   │   ├── nav-menu.php             # Widget class
  │   │   └── nav-menu-markup.php      # Render template
  │   ├── heading/
  │   ├── icon-box/
  │   └── ...
  ├── modules/                         # Module directories
  │   ├── header-footer/
  │   ├── megamenu/
  │   └── ...
  ├── libs/                            # Third-party libraries
  └── assets/
  ```
- **Hooks/Filters**:
  - `elementskit/widgets/list` — filter the list of registered widgets
  - `elementskit/modules/list` — filter the list of active modules
  - `elementskit/template/before_render`, `elementskit/template/after_render` — around template rendering
  - `elementskit/nav-menu/item_args` — filter nav menu walker arguments
  - `elementskit/header-footer/template/{type}` — filter header/footer template ID by type
- **AJAX endpoints**: `wp_ajax_ekit_*` actions. Key: `wp_ajax_ekit_admin_save_settings` (save widget/module toggles), `wp_ajax_ekit_admin_action` (admin operations), `wp_ajax_nopriv_ekit_*` for front-end.

### Header Footer Elementor (Ultimate Addons / Starter Templates)
- **Plugin slug**: `header-footer-elementor/header-footer-elementor.php`.
- **By**: Brainstorm Force (same team as Astra theme and Ultimate Addons for Elementor).
- **Detection**: `defined('HFE_VER')` or `class_exists('Header_Footer_Elementor')`.
- **Purpose**: Allows creating custom headers, footers, and block sections using Elementor, applied globally or conditionally, without requiring Elementor Pro's Theme Builder. Works with any theme but has enhanced integration with Astra.
- **Settings storage**:
  - wp_options key `hfe_compatibility_option` — theme compatibility mode (`developer`, `starter`, or blank for auto-detect).
  - wp_options key `hfe-plugin-meta` — plugin metadata and migration status.
  - No master settings array — configuration is per-template via postmeta.
- **Custom post type**: `elementor-hf` — Header/Footer/Block templates.
  - **Postmeta keys**:
    - `ehf_template_type` — `type_header`, `type_footer`, or `type_before_footer` (block before footer).
    - `ehf_target_include_locations` — serialized array of display rules (where to show). Format: `['rule' => ['basic-global'], 'specific' => []]` for entire site, or specific post types, pages, categories.
    - `ehf_target_exclude_locations` — serialized array of exclusion rules (same format).
    - `ehf_target_user_roles` — serialized array of user roles that can see the template (empty = all).
    - `_elementor_data` — standard Elementor page data (JSON).
- **Template selection logic**: Iterates all published `elementor-hf` posts, checks include/exclude rules and user roles, selects the most specific matching template for header/footer positions.
- **Theme compatibility**:
  - Astra: Native support via `astra_header` and `astra_footer` action hooks (best integration).
  - GeneratePress: Hooks into `generate_header`, `generate_footer`.
  - OceanWP: Hooks into `ocean_header`, `ocean_footer`.
  - Other themes: Falls back to `get_header`/`get_footer` filters or `wp_head`/`wp_footer` hooks with JavaScript-based replacement.
  - Custom: Developers can add theme support via `hfe_header_enabled` and `hfe_footer_enabled` filters, plus `hfe_render_header()` and `hfe_render_footer()` functions in theme templates.
- **Key functions**:
  - `hfe_render_header()` — render the active header template
  - `hfe_render_footer()` — render the active footer template
  - `hfe_render_before_footer()` — render before-footer block
  - `hfe_is_header_enabled()` — check if a custom header is active
  - `hfe_is_footer_enabled()` — check if a custom footer is active
  - `hfe_is_before_footer_enabled()` — check if before-footer block is active
  - `Header_Footer_Elementor::get_settings($setting, $default)` — retrieve plugin settings
- **Hooks/Filters**:
  - `hfe_header_enabled` — filter (bool) whether custom header should render
  - `hfe_footer_enabled` — filter (bool) whether custom footer should render
  - `hfe_render_header` — action fired when rendering the header
  - `hfe_render_footer` — action fired when rendering the footer
  - `hfe_header_before` / `hfe_header_after` — actions before/after header output
  - `hfe_footer_before` / `hfe_footer_after` — actions before/after footer output
  - `hfe_header_css` / `hfe_footer_css` — filter CSS classes on header/footer containers
  - `hfe_template_query_args` — filter WP_Query args for template lookup
- **Widgets** (registered for use within HFE templates):
  - Site Logo, Site Title, Site Tagline, Navigation Menu, Page Title, Retina Image, Copyright, Search, Cart (WooCommerce)
  - Widget prefix: `hfe-` (e.g., `hfe-nav-menu`, `hfe-site-logo`, `hfe-copyright`).
  - Category: `hfe-widgets` registered via `elementor/elements/categories_registered`.
- **File structure**:
  ```
  header-footer-elementor/
  ├── header-footer-elementor.php      # Main plugin file
  ├── admin/
  │   ├── class-hfe-admin.php          # Admin UI and settings page
  │   └── class-hfe-addons-actions.php # Template management
  ├── inc/
  │   ├── class-header-footer-elementor.php  # Main class, template logic
  │   ├── class-hfe-elementor-canvas-compat.php  # Canvas template support
  │   ├── hfe-functions.php            # Global helper functions
  │   ├── widgets-manager/
  │   │   ├── class-widgets-loader.php
  │   │   └── widgets/                 # Widget class files
  │   └── compatibility/               # Theme-specific compatibility files
  ├── themes/                          # Theme override templates
  │   ├── default/
  │   │   ├── class-hfe-default-compat.php
  │   │   ├── header.php
  │   │   └── footer.php
  │   └── astra/
  │       └── class-hfe-astra-compat.php
  ```
- **Astra integration**: When Astra theme is active, HFE uses Astra's native hooks (`astra_header`, `astra_footer`) for seamless replacement. Transparent header and sticky header features from Astra work with HFE templates. Settings in Astra Customizer (Astra Settings > Header Builder) coordinate with HFE.

### Premium Addons for Elementor
- **Plugin slug**: `premium-addons-for-elementor/premium-addons-for-elementor.php`.
- **Pro version**: `premium-addons-pro/premium-addons-pro-for-elementor.php` (separate plugin).
- **Detection**: `defined('STARTER_EL_STARTER_VER')` or more reliably `class_exists('PremiumAddons\\Includes\\Plugin')` or `defined('STARTER_STARTER_BASENAME')`. Constant `STARTER_EL_STARTER_VER` holds the version.
- **Settings storage**: wp_options key `pa_save_settings` (serialized array). Each widget is a key (widget slug) with value `1` (enabled) or absent (disabled).
  - Individual widget keys: `premium-banner`, `premium-blog`, `premium-carousel`, `premium-countdown`, `premium-counter`, `premium-dual-header`, `premium-fancytext`, `premium-image-separator`, `premium-maps`, `premium-modalbox`, `premium-person`, `premium-pricing-table`, `premium-progressbar`, `premium-testimonials`, `premium-title`, `premium-videobox`, `premium-contactform`, `premium-button`, `premium-img-gallery`, `premium-grid`, `premium-lottie`, etc.
- **Additional options**:
  - `pa_maps_save_settings` — Google Maps API key and map settings (key: `premium-map-api`, `premium-map-disable-api`, `premium-map-cluster`, `premium-map-locale`).
  - `pa_beta_save_settings` — beta features toggle.
  - `pa_elements_save_settings` — per-element settings.
  - `pa_default_settings` — default widget enable/disable states.
  - `pa_version` — installed version.
- **Widget registration**: Hooks into `elementor/widgets/register`. Widgets extend `\Elementor\Widget_Base` and are under `\PremiumAddons\Widgets\` namespace. Class names: `Premium_Banner`, `Premium_Blog`, `Premium_Carousel`, etc.
- **Widget categories**: Registers `premium-elements` category via `elementor/elements/categories_registered`.
- **Key widget types (60+ total)**:
  - **Content**: Blog, Carousel, Grid, Testimonials, Persons (Team), Pricing Table, FAQ (Accordion), Tabs, Content Switcher, Table, Magazine
  - **Creative**: Banner, Fancy Text, Countdown, Counter, Progress Bar, Dual Heading, Title, Image Separator, Lottie Animation, Image Hotspots, Image Layers, Image Comparison, Ken Burns, SVG Draw, Blob Generator
  - **Section Features**: Parallax, Particles, Animated Gradient, Multi-Scroll, Badge
  - **Marketing**: Modal Box, Alert Box, Button, Icon Box, Flip Box, iHover, Image Accordion, Video Box, Contact Form 7, Behance Feed, Facebook/Twitter Feed (Pro), Google Reviews (Pro)
  - **Navigation**: Mega Menu (Pro), Navigation Menu, Breadcrumbs, Site Logo, Search Form
  - **WooCommerce (Pro)**: Product Grid, Product Carousel, Product Category, Mini Cart, Woo CTA
- **Google Maps integration**: Requires API key stored in `pa_maps_save_settings`. Widget slug: `premium-addon-maps`. Supports multiple markers, custom skins, and info windows.
- **File structure**:
  ```
  premium-addons-for-elementor/
  ├── premium-addons-for-elementor.php # Main plugin file
  ├── includes/
  │   ├── class-plugin.php             # Main plugin class
  │   ├── class-addons-integration.php # Elementor integration
  │   ├── class-admin-helper.php       # Admin settings helper
  │   ├── class-helper-functions.php   # Utility functions
  │   ├── class-pa-rollback.php        # Version rollback
  │   └── compatibility/               # Third-party compatibility
  ├── widgets/
  │   ├── premium-banner.php
  │   ├── premium-blog.php
  │   ├── premium-carousel.php
  │   └── ...                          # One file per widget
  ├── modules/                         # Non-widget modules
  │   ├── pa-display-conditions/       # Conditional display logic
  │   ├── premium-equal-height/
  │   ├── premium-global-tooltips/
  │   └── ...
  ├── assets/
  │   ├── frontend/css/                # Per-widget CSS
  │   ├── frontend/js/                 # Per-widget JS
  │   └── admin/                       # Admin panel assets
  ```
- **Hooks/Filters**:
  - `pa_widgets_list` — filter the complete list of widget slugs and class names
  - `pa_pro_widgets_list` — filter pro widgets list
  - `pa/{widget_slug}/query_args` — filter WP_Query args for post-based widgets (Blog, Grid)
  - `pa/{widget_slug}/render` — filter widget rendered output
  - `pa/editor/before_enqueue_scripts` — action before editor scripts load
  - `pa/options/defaults` — filter default option values
- **Display Conditions module** (Pro): Allows showing/hiding any Elementor widget/section based on conditions (user role, date/time, browser, device, URL params, ACF fields, WooCommerce, etc.). Settings stored per-element in Elementor data, not in wp_options.
- **AJAX endpoints**: `wp_ajax_pa_*` actions. Key: `wp_ajax_pa_get_posts` (blog/grid infinite scroll), `wp_ajax_pa_maps_marker` (maps data), `wp_ajax_nopriv_pa_*` equivalents for logged-out users.
- **Asset optimization**: Similar to Essential Addons — loads only CSS/JS for widgets present on the page. Cached in `wp-content/uploads/starter-starter/` directory.

### Common Patterns Across Elementor Addon Plugins

- **Widget toggle system**: All addon plugins store per-widget enable/disable settings in wp_options. Disabled widgets are not registered with Elementor, reducing overhead.
- **Registration hook**: All use `elementor/widgets/register` (Elementor 3.5+). Older versions used `elementor/widgets/widgets_registered` (deprecated).
- **Category registration**: All register custom widget categories via `elementor/elements/categories_registered` hook.
- **Asset optimization**: Most modern addons only enqueue CSS/JS for widgets actually used on the current page (not all widgets globally).
- **Pro/Lite split**: Free versions register lite widgets; Pro versions extend or replace them with enhanced classes. Pro plugins check for the lite version and may deactivate it or extend its classes.
- **Template overrides**: Some addons allow theme-level template overrides by placing files in a specific theme directory (e.g., `theme/essential-addons-elementor/`).
- **Detecting which addons are active**: Use `is_plugin_active()` or check for defining constants (`EAEL_PLUGIN_VERSION`, `HFE_VER`, etc.) or class existence checks before interacting with addon-specific APIs.
- **Conflict avoidance**: Multiple Elementor addons can coexist, but duplicate widget types (e.g., multiple "Pricing Table" widgets) may confuse users. Each addon namespaces its widgets with a prefix (eael-, elementskit-, premium-, hfe-).
