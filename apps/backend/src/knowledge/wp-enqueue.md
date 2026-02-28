# WordPress Script & Style Enqueuing

Complete reference for registering, enqueuing, and managing JavaScript and CSS assets in WordPress.

---

## Core Functions

### wp_enqueue_script()

Enqueues a script for output on the page. If a source (`$src`) is provided, it registers the script first.

```php
wp_enqueue_script(
    string $handle,
    string $src = '',
    string[] $deps = array(),
    string|bool|null $ver = false,
    array|bool $args = array()
): void
```

**Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `$handle` | `string` | Yes | -- | Unique name for the script. If `$src` is omitted, it enqueues an already-registered handle. |
| `$src` | `string` | No | `''` | Full URL of the script, or path relative to the WordPress root directory. Use `plugins_url()` or `get_template_directory_uri()` for portable paths -- never hardcode URLs. |
| `$deps` | `string[]` | No | `array()` | Array of registered script handles this script depends on. WordPress loads dependencies first. |
| `$ver` | `string\|bool\|null` | No | `false` | Version string appended as `?ver=` query param for cache busting. `false` = use current WP version. `null` = no version query string at all. |
| `$args` | `array\|bool` | No | `array()` | Loading options (see below). For backward compat, passing `true` is equivalent to `['in_footer' => true]`. |

**`$args` array keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `strategy` | `string` | -- | `'defer'` or `'async'`. Controls delayed loading (WP 6.3+). |
| `in_footer` | `bool` | `false` | Whether to print the script in the footer instead of the `<head>`. |
| `fetchpriority` | `string` | `'auto'` | Resource fetch priority hint: `'auto'`, `'high'`, or `'low'`. |

**Example:**

```php
function my_enqueue_scripts() {
    wp_enqueue_script(
        'my-plugin-script',
        plugins_url( 'js/app.js', __FILE__ ),
        array( 'jquery', 'wp-api-fetch' ),
        '1.2.0',
        array( 'strategy' => 'defer', 'in_footer' => true )
    );
}
add_action( 'wp_enqueue_scripts', 'my_enqueue_scripts' );
```

---

### wp_enqueue_style()

Enqueues a CSS stylesheet for output. Registers the stylesheet first if a source is provided.

```php
wp_enqueue_style(
    string $handle,
    string $src = '',
    string[] $deps = array(),
    string|bool|null $ver = false,
    string $media = 'all'
): void
```

**Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `$handle` | `string` | Yes | -- | Unique name for the stylesheet. |
| `$src` | `string` | No | `''` | Full URL or path relative to the WP root directory. |
| `$deps` | `string[]` | No | `array()` | Array of registered stylesheet handles this stylesheet depends on. |
| `$ver` | `string\|bool\|null` | No | `false` | Version for cache busting. `false` = WP version. `null` = no version. |
| `$media` | `string` | No | `'all'` | CSS media type or query: `'all'`, `'print'`, `'screen'`, `'(max-width: 640px)'`, `'(orientation: portrait)'`, etc. |

**Example:**

```php
function my_enqueue_styles() {
    wp_enqueue_style(
        'my-plugin-style',
        plugins_url( 'css/style.css', __FILE__ ),
        array(),
        filemtime( plugin_dir_path( __FILE__ ) . 'css/style.css' ),
        'all'
    );
}
add_action( 'wp_enqueue_scripts', 'my_enqueue_styles' );
```

---

### wp_register_script()

Registers a script without enqueuing it. Useful when a script should only load if explicitly enqueued later or if it is a dependency of another script.

```php
wp_register_script(
    string $handle,
    string|false $src,
    string[] $deps = array(),
    string|bool|null $ver = false,
    array|bool $args = array()
): bool
```

**Parameters:** Same as `wp_enqueue_script()`, with one difference:

- `$src` can be `false` to create an alias script that only loads its `$deps` when enqueued.

**Returns:** `true` on success, `false` on failure.

**Notes:**
- Registered scripts are automatically loaded if listed as a dependency of any enqueued script.
- Re-registering with different parameters is silently ignored; use `wp_deregister_script()` first.

---

### wp_register_style()

Registers a stylesheet without enqueuing it.

```php
wp_register_style(
    string $handle,
    string|false $src,
    string[] $deps = array(),
    string|bool|null $ver = false,
    string $media = 'all'
): bool
```

**Parameters:** Same as `wp_enqueue_style()`.

**Returns:** `true` on success, `false` on failure.

---

## Removing Scripts and Styles

### wp_dequeue_script() / wp_dequeue_style()

Removes a previously enqueued script or style from the output queue.

```php
wp_dequeue_script( string $handle ): void
wp_dequeue_style( string $handle ): void
```

Must be called after the script/style has been enqueued. Use an appropriate hook with a late priority:

```php
function my_dequeue() {
    wp_dequeue_script( 'jquery-ui-core' );
    wp_dequeue_style( 'unwanted-plugin-style' );
}
add_action( 'wp_print_scripts', 'my_dequeue', 100 );
```

### wp_deregister_script() / wp_deregister_style()

Removes a registered script or style entirely. Required before re-registering a handle with different parameters.

```php
wp_deregister_script( string $handle ): void
wp_deregister_style( string $handle ): void
```

**Important:** WordPress protects critical admin scripts (jQuery, jQuery UI, Underscore, Backbone) from deregistration. Attempting to deregister these in the admin area triggers a "doing it wrong" notice unless done on the correct hook (`admin_enqueue_scripts`).

---

## Passing PHP Data to JavaScript

### wp_localize_script()

Attaches a JavaScript object containing PHP data to a registered script.

```php
wp_localize_script(
    string $handle,
    string $object_name,
    array $l10n
): bool
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$handle` | `string` | Handle of the script to attach data to. Must already be registered or enqueued. |
| `$object_name` | `string` | Name of the JS variable. Must be a valid JS identifier (no dashes -- use underscores or camelCase). Must be unique across all scripts. |
| `$l10n` | `array` | Associative or indexed array of data. Associative arrays become JS objects; indexed arrays become JS arrays. |

**Returns:** `bool` -- `true` on success, `false` on failure.

**Critical:** Must be called AFTER `wp_register_script()` or `wp_enqueue_script()`. The inline `<script>` prints before the associated script file.

**Notes:**
- All values are converted to strings; use `parseInt()` / `parseFloat()` in JS for numbers.
- Multiple calls with the same `$object_name` overwrite previous data.
- Since WP 4.5+, prefer `wp_add_inline_script()` for passing arbitrary data.

```php
wp_enqueue_script( 'my-script', plugins_url( 'js/app.js', __FILE__ ) );
wp_localize_script( 'my-script', 'myScriptData', array(
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'my_nonce_action' ),
    'postId'  => get_the_ID(),
) );
```

In JavaScript: `myScriptData.ajaxUrl`, `myScriptData.nonce`, `myScriptData.postId`.

---

### wp_add_inline_script()

Adds inline JavaScript code before or after a registered/enqueued script.

```php
wp_add_inline_script(
    string $handle,
    string $data,
    string $position = 'after'
): bool
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$handle` | `string` | -- | Handle of the script to attach to. |
| `$data` | `string` | -- | JavaScript code (do NOT include `<script>` tags). |
| `$position` | `string` | `'after'` | `'before'` or `'after'` the script. |

**Returns:** `bool` -- `true` on success, `false` on failure.

**Recommended over `wp_localize_script()` for passing arbitrary data since WP 4.5+:**

```php
wp_enqueue_script( 'my-script', plugins_url( 'js/app.js', __FILE__ ) );
wp_add_inline_script( 'my-script', 'const CONFIG = ' . wp_json_encode( array(
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'my-action' ),
) ), 'before' );
```

---

### wp_add_inline_style()

Adds inline CSS after a registered/enqueued stylesheet.

```php
wp_add_inline_style(
    string $handle,
    string $data
): bool
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$handle` | `string` | Handle of the stylesheet to attach to. |
| `$data` | `string` | CSS code (do NOT include `<style>` tags). |

**Returns:** `bool` -- `true` on success, `false` on failure.

```php
wp_enqueue_style( 'my-theme-style', get_stylesheet_uri() );
$accent_color = get_theme_mod( 'accent_color', '#6400e0' );
wp_add_inline_style( 'my-theme-style', ".accent { color: {$accent_color}; }" );
```

---

## Checking Script/Style Status

### wp_script_is() / wp_style_is()

Checks whether a script or style has been registered, enqueued, printed, or is queued.

```php
wp_script_is( string $handle, string $status = 'enqueued' ): bool
wp_style_is( string $handle, string $status = 'enqueued' ): bool
```

**`$status` values:**

| Status | Description |
|--------|-------------|
| `'registered'` | Has been registered (via `wp_register_*`). |
| `'enqueued'` | Has been enqueued (via `wp_enqueue_*`). Alias: `'queue'`. |
| `'to_do'` | Is in the queue, waiting to be output. |
| `'done'` | Has already been output to the page. |

**Example -- avoid duplicate enqueue:**

```php
if ( ! wp_script_is( 'my-lib', 'enqueued' ) ) {
    wp_enqueue_script( 'my-lib', plugins_url( 'js/lib.js', __FILE__ ) );
}
```

---

## Enqueue Hooks

Always enqueue scripts and styles inside the appropriate action hook. Enqueuing outside of a hook can lead to problems.

| Hook | Context | Callback Signature |
|------|---------|-------------------|
| `wp_enqueue_scripts` | Frontend pages | `function()` |
| `admin_enqueue_scripts` | Admin pages | `function( $hook_suffix )` -- receives the current admin page filename (e.g. `'edit.php'`, `'post.php'`). Use this to conditionally load assets. |
| `login_enqueue_scripts` | Login / registration pages | `function()` |

**Conditional frontend loading:**

```php
add_action( 'wp_enqueue_scripts', function() {
    if ( is_single() && get_post_type() === 'product' ) {
        wp_enqueue_script( 'product-gallery', ... );
    }
} );
```

**Conditional admin loading:**

```php
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( $hook !== 'toplevel_page_my-plugin' ) {
        return;
    }
    wp_enqueue_script( 'my-plugin-admin', ... );
} );
```

---

## Built-in Script Handles (Dependencies)

WordPress bundles many scripts you can list as dependencies. Common handles:

| Handle | Library |
|--------|---------|
| `jquery` | jQuery (runs in noConflict mode) |
| `jquery-core` | jQuery core without noConflict wrapper |
| `jquery-ui-core` | jQuery UI Core |
| `jquery-ui-dialog` | jQuery UI Dialog |
| `jquery-ui-sortable` | jQuery UI Sortable |
| `jquery-ui-datepicker` | jQuery UI Datepicker |
| `jquery-ui-autocomplete` | jQuery UI Autocomplete |
| `underscore` | Underscore.js |
| `backbone` | Backbone.js |
| `lodash` | Lodash |
| `wp-element` | WordPress React wrapper (`@wordpress/element`) |
| `wp-components` | WordPress UI Components (`@wordpress/components`) |
| `wp-blocks` | Block registration API (`@wordpress/blocks`) |
| `wp-block-editor` | Block editor components (`@wordpress/block-editor`) |
| `wp-data` | Data store (`@wordpress/data`) |
| `wp-i18n` | Internationalization (`@wordpress/i18n`) |
| `wp-api-fetch` | Authenticated fetch wrapper (`@wordpress/api-fetch`) |
| `wp-hooks` | Hooks system (`@wordpress/hooks`) |
| `wp-dom-ready` | DOM ready utility (`@wordpress/dom-ready`) |
| `wp-url` | URL utilities (`@wordpress/url`) |
| `wp-notices` | Notices module (`@wordpress/notices`) |
| `wp-plugins` | Plugin API for slot fills (`@wordpress/plugins`) |
| `wp-edit-post` | Post editor (`@wordpress/edit-post`) |
| `media-upload` | WordPress media uploader |
| `thickbox` | Thickbox modal |

Built-in style handles: `wp-components`, `wp-block-editor`, `wp-edit-post`, `dashicons`, `wp-admin`, `common`, `forms`, `buttons`.

---

## Versioning & Cache Busting

### Static version string

```php
wp_enqueue_script( 'my-script', $src, array(), '1.0.3' );
```

### Dynamic version via filemtime()

Automatically bust the cache whenever the file changes on disk:

```php
$path = plugin_dir_path( __FILE__ ) . 'js/app.js';
wp_enqueue_script( 'my-script', plugins_url( 'js/app.js', __FILE__ ), array(), filemtime( $path ) );
```

```php
$css_path = get_template_directory() . '/assets/css/main.css';
wp_enqueue_style( 'theme-main', get_template_directory_uri() . '/assets/css/main.css', array(), filemtime( $css_path ) );
```

### Version parameter behavior

| `$ver` value | Query string result |
|-------------|---------------------|
| `'1.2.3'` | `?ver=1.2.3` |
| `false` | `?ver={WP_VERSION}` |
| `null` | No query string at all |

---

## Async & Defer Loading (WP 6.3+)

WordPress 6.3 introduced the `strategy` key in the `$args` parameter for both `wp_register_script()` and `wp_enqueue_script()`.

### Defer

Script executes after the document is parsed, in the order scripts were added.

```php
wp_enqueue_script( 'my-script', $src, array(), '1.0', array(
    'strategy'  => 'defer',
    'in_footer' => true,
) );
```

### Async

Script executes as soon as it finishes downloading, with no guaranteed order.

```php
wp_enqueue_script( 'analytics', $src, array(), '1.0', array(
    'strategy' => 'async',
) );
```

### Intelligent dependency handling

WordPress automatically adjusts the strategy for scripts in a dependency chain. If a deferred script depends on a non-deferred script, WordPress ensures execution order is preserved. If an `async` script has dependencies, WordPress may downgrade it to `defer` to maintain correct ordering.

### Legacy approach: wp_script_add_data()

Before WP 6.3, you could use:

```php
wp_register_script( 'my-script', $src );
wp_script_add_data( 'my-script', 'strategy', 'defer' );
```

```php
wp_script_add_data( string $handle, string $key, mixed $value ): bool
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `$handle` | `string` | Registered script handle. |
| `$key` | `string` | Metadata key (e.g. `'strategy'`, `'conditional'`). |
| `$value` | `mixed` | Metadata value. |

---

## Module Scripts (WP 6.5+)

WordPress 6.5 introduced native ES module support via the Script Modules API.

### wp_register_script_module()

```php
wp_register_script_module(
    string $id,
    string $src,
    array $deps = array(),
    string|false|null $version = false,
    array $args = array()
): void
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$id` | `string` | -- | Unique module identifier (used in the import map). |
| `$src` | `string` | -- | Full URL or path relative to WP root. |
| `$deps` | `array` | `array()` | Array of dependency identifiers. Each can specify `'type' => 'static'` (default) or `'type' => 'dynamic'`. |
| `$version` | `string\|false\|null` | `false` | Version for cache busting. Same behavior as `wp_register_script()`. |
| `$args` | `array` | `array()` | Additional options including `in_footer` (bool) and `fetchpriority` (`'auto'`, `'low'`, `'high'`). Added in WP 6.9. |

### Related module functions

| Function | Description |
|----------|-------------|
| `wp_register_script_module()` | Register a module without enqueuing. |
| `wp_enqueue_script_module()` | Register and enqueue a module for output. |
| `wp_deregister_script_module()` | Remove a registered module. |

### Key differences from classic scripts

- Uses native ES `import` / `export` syntax.
- WordPress generates an import map in the HTML `<head>` for module resolution.
- Dependencies can be `static` (loaded eagerly) or `dynamic` (loaded on demand via dynamic `import()`).
- No need for `async`/`defer` attributes -- ES modules are deferred by default.

```php
// Register a module
wp_register_script_module( 'my-module', plugins_url( 'js/my-module.js', __FILE__ ), array(
    array( 'id' => 'wp-api-fetch', 'type' => 'static' ),
    array( 'id' => 'heavy-lib', 'type' => 'dynamic' ),
), '1.0.0' );

// Enqueue it
wp_enqueue_script_module( 'my-module' );
```

---

## Quick Reference

| Task | Function |
|------|----------|
| Enqueue a JS file | `wp_enqueue_script( $handle, $src, $deps, $ver, $args )` |
| Enqueue a CSS file | `wp_enqueue_style( $handle, $src, $deps, $ver, $media )` |
| Register JS (no enqueue) | `wp_register_script( $handle, $src, $deps, $ver, $args )` |
| Register CSS (no enqueue) | `wp_register_style( $handle, $src, $deps, $ver, $media )` |
| Remove enqueued JS | `wp_dequeue_script( $handle )` |
| Remove enqueued CSS | `wp_dequeue_style( $handle )` |
| Unregister JS | `wp_deregister_script( $handle )` |
| Unregister CSS | `wp_deregister_style( $handle )` |
| Pass PHP data to JS | `wp_localize_script( $handle, $object_name, $data )` |
| Inline JS | `wp_add_inline_script( $handle, $code, $position )` |
| Inline CSS | `wp_add_inline_style( $handle, $css )` |
| Check JS status | `wp_script_is( $handle, $status )` |
| Check CSS status | `wp_style_is( $handle, $status )` |
| Add script metadata | `wp_script_add_data( $handle, $key, $value )` |
| Register ES module | `wp_register_script_module( $id, $src, $deps, $ver )` |
| Enqueue ES module | `wp_enqueue_script_module( $id, $src, $deps, $ver )` |
