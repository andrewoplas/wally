# WordPress Rewrite & Permalink System Reference

## Permalink Structures

Common structures set in Settings > Permalinks:

| Setting | Structure | Example URL |
|---|---|---|
| Plain | `?p=123` | `example.com/?p=123` |
| Post name | `/%postname%/` | `example.com/hello-world/` |
| Day and name | `/%year%/%monthnum%/%day%/%postname%/` | `example.com/2024/03/15/hello-world/` |
| Month and name | `/%year%/%monthnum%/%postname%/` | `example.com/2024/03/hello-world/` |
| Category and name | `/%category%/%postname%/` | `example.com/news/hello-world/` |

**Available structure tags:** `%year%`, `%monthnum%`, `%day%`, `%hour%`, `%minute%`, `%second%`, `%post_id%`, `%postname%`, `%category%`, `%author%`

```php
// Read current permalink structure
$structure = get_option( 'permalink_structure' );  // e.g., '/%postname%/'
```

## Rewrite Rules

Rules are stored in `wp_options` as `rewrite_rules` (serialized array). The global `$wp_rewrite` object manages them.

```php
global $wp_rewrite;
$wp_rewrite->rules;              // Array of regex => query string pairs
$wp_rewrite->permalink_structure; // Current structure
```

## Adding Custom Rewrite Rules

```php
// Add a rewrite rule
add_rewrite_rule(
    '^products/([^/]+)/?$',                    // Regex to match URL
    'index.php?post_type=product&name=$matches[1]',  // Internal rewrite
    'top'                                       // 'top' = before WP rules, 'bottom' = after
);

// Must flush rewrite rules after adding (only on activation, NEVER on every load)
```

## Custom Rewrite Tags

```php
// Register a custom rewrite tag for use in permalink structures
add_rewrite_tag( '%product_sku%', '([^/]+)' );

// Now usable in permalink structures:
// $wp_rewrite->set_permalink_structure('/products/%product_sku%/');
```

## Query Variables

```php
// Register custom query vars so WP recognizes them
add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'my_custom_var';
    return $vars;
} );

// Access in templates
$value = get_query_var( 'my_custom_var' );
// Works with: example.com/?my_custom_var=something
// Or with rewrite rules that map to: index.php?my_custom_var=$matches[1]
```

## Custom Post Type Permalinks

```php
register_post_type( 'product', [
    'public'  => true,
    'rewrite' => [
        'slug'       => 'products',       // URL prefix: /products/post-name/
        'with_front' => false,            // Don't prepend blog prefix (e.g., /blog/products/)
        'feeds'      => true,             // Enable RSS feeds
        'pages'      => true,             // Enable pagination
        'ep_mask'    => EP_PERMALINK,     // Endpoint mask
    ],
] );

// For hierarchical CPTs (like pages)
register_post_type( 'documentation', [
    'hierarchical' => true,
    'rewrite'      => [ 'slug' => 'docs', 'with_front' => false ],
] );
```

## Custom Taxonomy Permalinks

```php
register_taxonomy( 'genre', 'book', [
    'rewrite' => [
        'slug'         => 'genre',          // URL prefix: /genre/term-name/
        'with_front'   => false,
        'hierarchical' => true,             // Include parent term in URL: /genre/parent/child/
    ],
] );
```

## Flushing Rewrite Rules

```php
// ONLY call on plugin/theme activation/deactivation
flush_rewrite_rules();

// Soft flush (preferred) -- updates .htaccess without regenerating all rules
flush_rewrite_rules( false );

// Proper pattern: register CPTs first, then flush on activation
register_activation_hook( __FILE__, function() {
    my_register_custom_post_types();   // Register CPTs first
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
} );

// Manual flush: visit Settings > Permalinks and click Save
// Equivalent to flush_rewrite_rules() -- useful for debugging
```

## Endpoints

```php
// Add an endpoint to existing URLs (e.g., /my-page/json/)
add_rewrite_endpoint( 'json', EP_PERMALINK | EP_PAGES );
// Creates query var 'json' accessible via get_query_var('json')

// Endpoint masks:
// EP_PERMALINK -- post permalinks
// EP_PAGES     -- page permalinks
// EP_ROOT      -- site root
// EP_ALL       -- all endpoints
```

## Hooks

```php
// Filter all rewrite rules before they are stored
add_filter( 'rewrite_rules_array', function( $rules ) {
    // Add, remove, or reorder rules
    $new_rules = [ 'custom/([^/]+)/?$' => 'index.php?custom_var=$matches[1]' ];
    return $new_rules + $rules;  // Prepend custom rules
} );

// After rules are generated
add_action( 'generate_rewrite_rules', function( $wp_rewrite ) {
    $wp_rewrite->rules = $custom_rules + $wp_rewrite->rules;
} );

// Control trailing slashes
add_filter( 'user_trailingslashit', function( $url, $type ) {
    return untrailingslashit( $url );  // Remove trailing slashes
}, 10, 2 );
```

## Useful Utility Functions

```php
get_permalink( $post_id );             // Full URL for a post
get_post_permalink( $post_id );        // For custom post types
get_term_link( $term, $taxonomy );     // URL for a taxonomy term
home_url( '/custom-path/' );           // Site URL with path
url_to_postid( $url );                // Get post ID from URL
```

## Gotchas

- **Never call `flush_rewrite_rules()` on `init` or every page load.** It writes to the database and is expensive. Only flush on activation/deactivation hooks.
- **Order matters:** `add_rewrite_rule()` with `'top'` places rules before WordPress defaults. Use `'top'` for custom rules that might conflict with built-in patterns.
- **Register CPTs/taxonomies before flushing.** If you flush before registering, their rewrite rules won't be included.
- **`.htaccess` / nginx:** On Apache, rules are written to `.htaccess`. On nginx, rewrite rules must be added to the server config manually -- WP cannot write them.
- **Debugging:** Install the "Rewrite Rules Inspector" plugin or inspect `get_option('rewrite_rules')` to see all active rules.
- **`with_front => false`** prevents the blog prefix (e.g., `/blog/`) from being prepended to CPT/taxonomy URLs. Almost always desirable for CPTs.
- After changing permalink settings via `update_option('permalink_structure', ...)`, you must call `flush_rewrite_rules()`.
