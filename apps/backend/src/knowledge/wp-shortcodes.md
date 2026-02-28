# WordPress Shortcode API Reference

## Registration

```php
add_shortcode( 'my_shortcode', 'my_shortcode_callback' );

function my_shortcode_callback( $atts, $content = null, $tag = '' ) {
    // $atts    -- array of attributes (or empty string if none)
    // $content -- text between opening/closing tags (null for self-closing)
    // $tag     -- the shortcode name itself
    return '<div>output</div>';  // MUST return, never echo
}
```

**Usage in content:**
```
[my_shortcode]                              <!-- self-closing -->
[my_shortcode attr="value" flag]            <!-- with attributes -->
[my_shortcode]inner content[/my_shortcode]  <!-- enclosing -->
```

## Attribute Handling

```php
function my_shortcode_callback( $atts, $content = null ) {
    $atts = shortcode_atts(
        [
            'title'  => 'Default Title',   // defaults
            'count'  => 5,
            'type'   => 'grid',
        ],
        $atts,
        'my_shortcode'  // $tag -- enables the shortcode_atts_{$tag} filter
    );

    // Access as: $atts['title'], $atts['count'], $atts['type']
    return "<h2>{$atts['title']}</h2>";
}
```

**Note:** Attribute names are always lowercased by WordPress. `[shortcode MyAttr="val"]` becomes `$atts['myattr']`.

## Nested Shortcodes

```php
function wrapper_shortcode( $atts, $content = null ) {
    // Process shortcodes inside this shortcode's content
    return '<div class="wrapper">' . do_shortcode( $content ) . '</div>';
}
add_shortcode( 'wrapper', 'wrapper_shortcode' );
```

**Usage:** `[wrapper][inner_shortcode][/wrapper]`

**Limitation:** WordPress does not support self-nesting: `[wrapper][wrapper]...[/wrapper][/wrapper]` will not work. Different shortcode names can nest freely.

## Output Buffering Pattern

When including template files or using functions that echo, use output buffering:

```php
function my_template_shortcode( $atts ) {
    $atts = shortcode_atts( [ 'id' => 0 ], $atts );
    ob_start();
    include plugin_dir_path( __FILE__ ) . 'templates/my-template.php';
    return ob_get_clean();
}
```

## Checking & Removing Shortcodes

```php
shortcode_exists( 'my_shortcode' );         // bool: is this tag registered?
has_shortcode( $content, 'my_shortcode' );   // bool: does content contain this shortcode?

remove_shortcode( 'my_shortcode' );          // Unregister a specific shortcode
remove_all_shortcodes();                     // Unregister all (rarely used)
```

## Processing Shortcodes

```php
// Apply shortcodes to a string (same as what WP does on post_content)
$output = do_shortcode( '[my_shortcode attr="val"]' );

// Strip all shortcode tags from content (removes the shortcode, keeps inner content)
$clean = strip_shortcodes( $content );

// Get regex pattern that matches registered shortcodes
$pattern = get_shortcode_regex( [ 'my_shortcode' ] );
```

## Shortcodes in Widgets & Other Contexts

- Text widgets process shortcodes by default since WP 4.9.
- In Gutenberg, use the **Shortcode block** (`<!-- wp:shortcode -->[my_shortcode]<!-- /wp:shortcode -->`).
- To apply shortcodes to custom fields or other strings: `echo do_shortcode( get_post_meta( $id, 'my_field', true ) );`

## Hooks

```php
// Modify a specific shortcode's attributes after merging with defaults
add_filter( 'shortcode_atts_my_shortcode', function( $out, $pairs, $atts, $tag ) {
    // $out   -- merged attributes
    // $pairs -- defaults defined in shortcode_atts()
    // $atts  -- raw user attributes
    return $out;
}, 10, 4 );
```

## Gotchas

- **Always return, never echo.** Echoing will output content in the wrong position on the page.
- **Attribute values are strings.** `[shortcode count="5"]` gives `$atts['count'] === '5'` (string, not int). Cast as needed.
- **Empty `$atts` can be an empty string**, not an array, when no attributes are passed. `shortcode_atts()` handles this, but direct access to `$atts['key']` before `shortcode_atts()` may fail.
- **Square brackets in content** can break parsing. Use `[[shortcode]]` to escape (outputs literal `[shortcode]`).
- **Shortcodes in excerpts** are stripped by default via `wp_trim_excerpt()`. They only run in `the_content`.
- **Execution order:** Shortcodes run after `wpautop` (auto paragraph), which can wrap shortcode output in `<p>` tags. To avoid, use `remove_filter('the_content', 'wpautop')` or return block-level HTML.
