## Theme & Appearance

---

### Template Hierarchy

WordPress determines which template file to load by checking from most specific to least specific, falling back to `index.php`.

#### Front Page
1. `front-page.php`
2. `home.php` (if set to show latest posts)
3. `page.php` (if set to a static page)
4. `index.php`

#### Home / Blog Posts Index
1. `home.php`
2. `index.php`

#### Single Post
1. `single-{post_type}-{slug}.php`
2. `single-{post_type}.php`
3. `single.php`
4. `singular.php`
5. `index.php`

#### Page
1. Custom template file (selected via page attributes)
2. `page-{slug}.php`
3. `page-{id}.php`
4. `page.php`
5. `singular.php`
6. `index.php`

#### Category Archive
1. `category-{slug}.php`
2. `category-{id}.php`
3. `category.php`
4. `archive.php`
5. `index.php`

#### Tag Archive
1. `tag-{slug}.php`
2. `tag-{id}.php`
3. `tag.php`
4. `archive.php`
5. `index.php`

#### Custom Taxonomy Archive
1. `taxonomy-{taxonomy}-{term}.php`
2. `taxonomy-{taxonomy}.php`
3. `taxonomy.php`
4. `archive.php`
5. `index.php`

#### Custom Post Type Archive
1. `archive-{post_type}.php`
2. `archive.php`
3. `index.php`

#### Author Archive
1. `author-{nicename}.php`
2. `author-{id}.php`
3. `author.php`
4. `archive.php`
5. `index.php`

#### Date Archive (Year / Month / Day)
1. `date.php`
2. `archive.php`
3. `index.php`

#### Search Results
1. `search.php`
2. `index.php`

#### 404 Not Found
1. `404.php`
2. `index.php`

#### Attachment
1. `{MIME-type}.php` (e.g., `image.php`, `video.php`, `text.php`)
2. `attachment.php`
3. `single-attachment-{slug}.php`
4. `single-attachment.php`
5. `single.php`
6. `singular.php`
7. `index.php`

#### Embed
1. `embed-{post_type}-{post_format}.php`
2. `embed-{post_type}.php`
3. `embed.php`
4. Falls back to `wp-includes/theme-compat/embed.php`

#### Privacy Policy Page
1. `privacy-policy.php`
2. Custom template file
3. `page-{slug}.php`
4. `page-{id}.php`
5. `page.php`
6. `singular.php`
7. `index.php`

---

### Template Files

#### Required Files
- **`index.php`** (classic) or **`index.html`** (block) -- main fallback template
- **`style.css`** -- must contain theme metadata header (Theme Name, Version, etc.)

#### Standard Template Files

| File | Purpose |
|------|---------|
| `front-page.php` | Site front page (overrides Reading settings) |
| `home.php` | Blog posts index |
| `single.php` | Single post display |
| `page.php` | Single page display |
| `archive.php` | Archive pages |
| `category.php` | Category archive |
| `tag.php` | Tag archive |
| `author.php` | Author archive |
| `date.php` | Date archive |
| `search.php` | Search results |
| `404.php` | Not found page |
| `attachment.php` | Attachment display |
| `comments.php` | Comments display (classic themes only) |

#### Template Sections

| File | Function to load |
|------|-----------------|
| `header.php` | `get_header()` or `get_header('name')` for `header-name.php` |
| `footer.php` | `get_footer()` or `get_footer('name')` for `footer-name.php` |
| `sidebar.php` | `get_sidebar()` or `get_sidebar('name')` for `sidebar-name.php` |
| `searchform.php` | `get_search_form()` |

---

### get_template_part()

```php
get_template_part( string $slug, string|null $name = null, array $args = array() ): void|false
```

Loads a reusable template part. Searches child theme first, then parent theme.

**Parameters:**
- `$slug` (string, required) -- the slug for the template (e.g., `'template-parts/content'`)
- `$name` (string|null, optional) -- specialized template name (default: `null`)
- `$args` (array, optional) -- data to pass to the template (WP 5.5+, default: `[]`)

**Lookup order** (when `$name` is provided):
1. `{slug}-{name}.php`
2. `{slug}.php`

**Examples:**
```php
// Loads template-parts/content.php
get_template_part( 'template-parts/content' );

// Loads template-parts/content-page.php, falls back to template-parts/content.php
get_template_part( 'template-parts/content', 'page' );

// Loads template-parts/content-page.php in a subdirectory
get_template_part( 'template-parts/content', get_post_type() );

// Pass data to template (WP 5.5+)
get_template_part( 'template-parts/hero', null, array(
    'title'    => 'Welcome',
    'subtitle' => 'Hello World',
) );
// In the template: echo $args['title'];
```

Uses `require` (not `require_once`), so the same template part can be loaded multiple times. Returns `false` if template does not exist.

---

### get_header(), get_footer(), get_sidebar()

```php
get_header( string $name = null, array $args = array() ): void|false
get_footer( string $name = null, array $args = array() ): void|false
get_sidebar( string $name = null, array $args = array() ): void|false
```

- `get_header()` loads `header.php`; `get_header('shop')` loads `header-shop.php`
- `get_footer()` loads `footer.php`; `get_footer('minimal')` loads `footer-minimal.php`
- `get_sidebar()` loads `sidebar.php`; `get_sidebar('left')` loads `sidebar-left.php`
- All accept `$args` (WP 5.5+) to pass data to the template

---

### Theme Support

Features declared via `add_theme_support()` in `functions.php`, typically inside `after_setup_theme` hook.

```php
function mytheme_setup() {
    // Let WordPress manage the document title
    add_theme_support( 'title-tag' );

    // Enable featured images
    add_theme_support( 'post-thumbnails' );
    set_post_thumbnail_size( 1200, 630, true );

    // HTML5 markup for specified features
    add_theme_support( 'html5', array(
        'search-form', 'comment-form', 'comment-list',
        'gallery', 'caption', 'style', 'script',
    ) );

    // Custom logo
    add_theme_support( 'custom-logo', array(
        'height'               => 100,
        'width'                => 400,
        'flex-height'          => true,
        'flex-width'           => true,
        'header-text'          => array( 'site-title', 'site-description' ),
        'unlink-homepage-logo' => true,
    ) );

    // Custom header image
    add_theme_support( 'custom-header', array(
        'default-image' => '',
        'width'         => 1920,
        'height'        => 400,
        'flex-width'    => true,
        'flex-height'   => true,
    ) );

    // Custom background
    add_theme_support( 'custom-background', array(
        'default-color' => 'ffffff',
        'default-image' => '',
    ) );

    // Editor styles
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/editor.css' );

    // Wide alignment in Gutenberg
    add_theme_support( 'align-wide' );

    // Responsive embeds
    add_theme_support( 'responsive-embeds' );

    // Block editor styles
    add_theme_support( 'wp-block-styles' );

    // Automatic feed links
    add_theme_support( 'automatic-feed-links' );

    // Post formats
    add_theme_support( 'post-formats', array(
        'aside', 'gallery', 'link', 'image',
        'quote', 'status', 'video', 'audio', 'chat',
    ) );

    // Navigation menus
    register_nav_menus( array(
        'primary'   => __( 'Primary Menu', 'mytheme' ),
        'footer'    => __( 'Footer Menu', 'mytheme' ),
    ) );
}
add_action( 'after_setup_theme', 'mytheme_setup' );
```

**All `add_theme_support()` features:**

| Feature | Description |
|---------|-------------|
| `title-tag` | WordPress manages the `<title>` element |
| `post-thumbnails` | Enable featured images for posts/pages |
| `html5` | HTML5 markup for search-form, comment-form, comment-list, gallery, caption, style, script |
| `custom-logo` | Custom logo upload via Customizer |
| `custom-header` | Custom header image |
| `custom-background` | Custom background color/image |
| `editor-styles` | Load custom editor stylesheets |
| `align-wide` | Wide and full-width alignment in block editor |
| `responsive-embeds` | Responsive embed wrappers |
| `wp-block-styles` | Default block styles on frontend |
| `automatic-feed-links` | RSS feed links in `<head>` |
| `post-formats` | Post format support (aside, gallery, link, image, quote, status, video, audio, chat) |
| `editor-color-palette` | Custom color palette for block editor |
| `editor-gradient-presets` | Custom gradient palette for block editor |
| `editor-font-sizes` | Custom font size presets |
| `custom-line-height` | Line height control in block editor |
| `custom-spacing` | Spacing controls for blocks |
| `custom-units` | CSS unit options for spacing |
| `menus` | Enable navigation menus (legacy, pre-WP 3.0) |

Block themes automatically enable: `post-thumbnails`, `responsive-embeds`, `editor-styles`, `html5` (style and script), `automatic-feed-links`.

---

### Child Themes

A child theme inherits all templates and functionality from a parent theme while allowing overrides.

#### style.css Header (required)

```css
/*
Theme Name:   My Child Theme
Template:     parent-theme-slug
Version:      1.0.0
Description:  Child theme for Parent Theme
Author:       Developer Name
*/
```

The `Template` field must exactly match the parent theme's directory name.

#### Loading Order

1. Child theme `functions.php` loads **first** (before parent's)
2. Parent theme `functions.php` loads second
3. Both files are additive (child does not replace parent)
4. Use `function_exists()` checks in parent theme to allow child overrides

```php
// In parent functions.php (overridable)
if ( ! function_exists( 'parent_theme_setup' ) ) {
    function parent_theme_setup() { ... }
}

// In child functions.php (overrides parent function)
function parent_theme_setup() { ... }
```

#### Template Overrides

- Any template file placed in the child theme directory overrides the parent's version
- Child theme template files take priority at every level of the template hierarchy
- To extend rather than replace a parent template, use `get_template_directory()` to reference parent files

#### Asset Enqueuing in Child Theme

```php
function child_theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style', get_stylesheet_uri(), array( 'parent-style' ) );
}
add_action( 'wp_enqueue_scripts', 'child_theme_enqueue_styles' );
```

- `get_template_directory_uri()` -- always points to parent theme
- `get_stylesheet_directory_uri()` -- points to child theme (or parent if no child)
- `get_stylesheet_uri()` -- child theme's `style.css` URL

---

### Customizer

The WordPress Customizer (`WP_Customize_Manager`) provides live-preview theme options.

```php
function mytheme_customize_register( $wp_customize ) {
    // Add section
    $wp_customize->add_section( 'mytheme_options', array(
        'title'    => __( 'Theme Options', 'mytheme' ),
        'priority' => 30,
    ) );

    // Add setting (stores the value)
    $wp_customize->add_setting( 'mytheme_accent_color', array(
        'default'           => '#0073aa',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage', // or 'refresh'
    ) );

    // Add control (the UI element)
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'mytheme_accent_color',
        array(
            'label'   => __( 'Accent Color', 'mytheme' ),
            'section' => 'mytheme_options',
        )
    ) );
}
add_action( 'customize_register', 'mytheme_customize_register' );
```

**Built-in control types:**
- `WP_Customize_Color_Control` -- color picker
- `WP_Customize_Image_Control` -- image upload
- `WP_Customize_Media_Control` -- media library selector
- `WP_Customize_Upload_Control` -- file upload
- `WP_Customize_Cropped_Image_Control` -- image with crop
- Text, textarea, select, checkbox, radio, dropdown-pages via `$wp_customize->add_control()`

**Transport options:**
- `refresh` -- full page refresh on change (default)
- `postMessage` -- live update via JavaScript (requires `customize-preview-js`)

---

### Theme Mods

Theme modification values stored in `wp_options` as serialized array under `theme_mods_{theme_slug}`.

```php
// Read a theme mod
$value = get_theme_mod( 'mytheme_accent_color', '#0073aa' );

// Write a theme mod
set_theme_mod( 'mytheme_accent_color', '#ff0000' );

// Remove a single mod
remove_theme_mod( 'mytheme_accent_color' );

// Remove all mods for the active theme
remove_theme_mods();

// Get all mods
$all_mods = get_theme_mods();
```

**Common built-in mods:**
- `custom_logo` -- attachment ID of custom logo
- `header_image` -- URL of custom header image
- `background_color` -- custom background color hex
- `background_image` -- URL of custom background image
- `nav_menu_locations` -- array mapping menu location slugs to menu IDs

---

### Block Themes

Block themes use HTML template files with block markup instead of PHP templates.

#### Directory Structure

```
theme-directory/
  style.css
  theme.json
  templates/           # Full page templates (HTML)
    index.html
    single.html
    page.html
    archive.html
    search.html
    404.html
    home.html
    front-page.html
  parts/               # Reusable template parts (HTML)
    header.html
    footer.html
    sidebar.html
```

#### Template Markup

Templates use block comment syntax:

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
    <!-- wp:post-title /-->
    <!-- wp:post-content /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

#### theme.json Integration

Block themes use `theme.json` for settings and styles (see `wp-theme-json.md`). The file replaces many `add_theme_support()` calls and custom CSS with a structured JSON configuration.

---

### Theme Hooks

| Hook | Type | When it fires |
|------|------|---------------|
| `after_setup_theme` | action | After theme is loaded; use for `add_theme_support()`, `register_nav_menus()` |
| `wp_enqueue_scripts` | action | Enqueue frontend CSS and JS |
| `admin_enqueue_scripts` | action | Enqueue admin CSS and JS |
| `wp_head` | action | Output in `<head>` (meta tags, inline styles) |
| `wp_footer` | action | Output before `</body>` (inline scripts, tracking) |
| `wp_body_open` | action | Right after `<body>` tag (skip-to-content, analytics) |
| `body_class` | filter | Modify CSS classes on `<body>` element |
| `post_class` | filter | Modify CSS classes on post wrapper elements |
| `the_content` | filter | Modify post content before display |
| `the_title` | filter | Modify post title before display |
| `excerpt_length` | filter | Change auto-excerpt word count (default: 55) |
| `excerpt_more` | filter | Change auto-excerpt trailing text (default: `[...]`) |
| `template_include` | filter | Override which template file loads for a request |
| `template_redirect` | action | Fires before template is loaded; use for redirects |
| `widgets_init` | action | Register sidebars and widget areas |
| `customize_register` | action | Register Customizer sections/settings/controls |
| `init` | action | Register post types, taxonomies (though not theme-specific) |

---

### Enqueuing Assets

```php
function mytheme_scripts() {
    // Theme stylesheet
    wp_enqueue_style(
        'mytheme-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get( 'Version' )
    );

    // Additional CSS
    wp_enqueue_style(
        'mytheme-main',
        get_template_directory_uri() . '/assets/css/main.css',
        array( 'mytheme-style' ),
        '1.0.0'
    );

    // JavaScript (in footer)
    wp_enqueue_script(
        'mytheme-main',
        get_template_directory_uri() . '/assets/js/main.js',
        array( 'jquery' ),
        '1.0.0',
        true  // $in_footer
    );

    // WP 6.3+ footer loading with strategy
    wp_enqueue_script(
        'mytheme-main',
        get_template_directory_uri() . '/assets/js/main.js',
        array(),
        '1.0.0',
        array( 'in_footer' => true, 'strategy' => 'defer' )
    );

    // Pass PHP data to JavaScript
    wp_localize_script( 'mytheme-main', 'mythemeData', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'mytheme_nonce' ),
        'homeUrl' => home_url( '/' ),
    ) );

    // Inline styles
    wp_add_inline_style( 'mytheme-style', ':root { --accent: #0073aa; }' );

    // Inline scripts
    wp_add_inline_script( 'mytheme-main', 'console.log("loaded");', 'after' );

    // Conditional loading
    if ( is_singular() && comments_open() ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'mytheme_scripts' );
```

**Key functions:**

| Function | Purpose |
|----------|---------|
| `wp_enqueue_style( $handle, $src, $deps, $ver, $media )` | Load a CSS file |
| `wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer )` | Load a JS file |
| `wp_register_style()` / `wp_register_script()` | Register without loading (enqueue later) |
| `wp_dequeue_style()` / `wp_dequeue_script()` | Remove an enqueued asset |
| `wp_deregister_style()` / `wp_deregister_script()` | Unregister a handle |
| `wp_localize_script( $handle, $object_name, $data )` | Pass PHP data to JS as a global object |
| `wp_add_inline_style( $handle, $css )` | Add inline CSS after a stylesheet |
| `wp_add_inline_script( $handle, $js, $position )` | Add inline JS before/after a script |
| `get_stylesheet_uri()` | URL to active theme's `style.css` |
| `get_template_directory_uri()` | URL to parent theme directory |
| `get_stylesheet_directory_uri()` | URL to child (or active) theme directory |

---

### Sidebars & Widgets

```php
function mytheme_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Primary Sidebar', 'mytheme' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Main sidebar area.', 'mytheme' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}
add_action( 'widgets_init', 'mytheme_widgets_init' );
```

| Function | Purpose |
|----------|---------|
| `register_sidebar( $args )` | Define a widget area |
| `dynamic_sidebar( $id )` | Display widgets in a sidebar |
| `is_active_sidebar( $id )` | Check if sidebar has widgets |
| `wp_get_sidebars_widgets()` | Get all sidebars with their widget IDs |

Widget options stored in `wp_options` as `widget_{widget_base}` (serialized array).
