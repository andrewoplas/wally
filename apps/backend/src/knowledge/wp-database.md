# WordPress Database & $wpdb Reference

## Accessing $wpdb

```php
global $wpdb;
// Or inside a class method:
$wpdb = $GLOBALS['wpdb'];
```

## Table Name Properties

| Property | Table | Description |
|---|---|---|
| `$wpdb->posts` | `wp_posts` | Posts, pages, CPTs, attachments, revisions |
| `$wpdb->postmeta` | `wp_postmeta` | Post custom fields |
| `$wpdb->options` | `wp_options` | Site options |
| `$wpdb->users` | `wp_users` | User accounts |
| `$wpdb->usermeta` | `wp_usermeta` | User metadata |
| `$wpdb->terms` | `wp_terms` | Taxonomy terms |
| `$wpdb->term_taxonomy` | `wp_term_taxonomy` | Term-taxonomy relationships |
| `$wpdb->term_relationships` | `wp_term_relationships` | Object-term associations |
| `$wpdb->termmeta` | `wp_termmeta` | Term metadata |
| `$wpdb->comments` | `wp_comments` | Comments |
| `$wpdb->commentmeta` | `wp_commentmeta` | Comment metadata |
| `$wpdb->prefix` | `wp_` | Table prefix (use for custom tables) |

Always use `$wpdb->prefix` or property names -- never hardcode `wp_`.

## Query Methods

### SELECT queries

```php
// Multiple rows -- returns array of objects (default) or arrays
$results = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_type = 'post'", OBJECT );
// Output types: OBJECT, OBJECT_K (keyed by first col), ARRAY_A, ARRAY_N

// Single row
$row = $wpdb->get_row( "SELECT * FROM $wpdb->posts WHERE ID = 1", OBJECT );

// Single column (returns flat array)
$ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_status = 'publish'" );

// Single value
$count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'post'" );
```

### $wpdb->prepare() -- ALWAYS use for user input

```php
// Placeholders: %d (int), %f (float), %s (string), %i (identifier/table/column name)
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_status = %s LIMIT %d",
        'post', 'publish', 10
    )
);

// Numbered placeholders for reuse
$wpdb->prepare( "WHERE id = %1\$d OR parent_id = %1\$d", $id );

// %i for identifiers (WP 6.2+) -- escapes with backticks
$wpdb->prepare( "SELECT %i FROM $wpdb->posts WHERE ID = %d", $column, $id );
```

### INSERT

```php
$wpdb->insert(
    $wpdb->posts,                           // Table name
    [ 'post_title' => 'Hello', 'post_status' => 'draft' ],  // Data (column => value)
    [ '%s', '%s' ]                           // Format array (optional but recommended)
);
$new_id = $wpdb->insert_id;                 // Last auto-increment ID
```

### UPDATE

```php
$wpdb->update(
    $wpdb->postmeta,                        // Table
    [ 'meta_value' => 'new_value' ],         // Data to set
    [ 'post_id' => 5, 'meta_key' => 'color' ], // WHERE conditions
    [ '%s' ],                                // Data formats
    [ '%d', '%s' ]                           // WHERE formats
);
// Returns: number of rows updated, or false on error
```

### DELETE

```php
$wpdb->delete(
    $wpdb->postmeta,                        // Table
    [ 'post_id' => 13, 'meta_key' => 'gargle' ], // WHERE conditions
    [ '%d', '%s' ]                           // Formats
);
// Returns: number of rows deleted, or false on error
```

### REPLACE (insert or update if key exists)

```php
$wpdb->replace(
    $wpdb->options,
    [ 'option_name' => 'my_opt', 'option_value' => 'val' ],
    [ '%s', '%s' ]
);
```

### Raw query

```php
$wpdb->query(
    $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s", 13, 'gargle' )
);
// Returns: int (affected rows) for INSERT/UPDATE/DELETE, true for CREATE/ALTER/DROP, false on error
```

## Creating Custom Tables with dbDelta()

```php
function my_plugin_create_tables() {
    global $wpdb;
    $table = $wpdb->prefix . 'my_custom_table';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        data longtext NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'my_plugin_create_tables' );
```

**dbDelta() formatting rules:**
- Each field on its own line
- TWO spaces between `PRIMARY KEY` and `(column)`
- Must use KEY not INDEX for indexes
- Must include the column type lengths
- Must use `$wpdb->get_charset_collate()` for charset

## Transactions

```php
$wpdb->query( 'START TRANSACTION' );
try {
    $wpdb->insert( $table, $data1 );
    $wpdb->insert( $table, $data2 );
    $wpdb->query( 'COMMIT' );
} catch ( Exception $e ) {
    $wpdb->query( 'ROLLBACK' );
}
```

## Error Handling

```php
$wpdb->show_errors();      // Enable error display (dev only)
$wpdb->hide_errors();      // Suppress errors
$wpdb->suppress_errors();  // Suppress and return previous state

// After a query:
if ( $wpdb->last_error ) {
    error_log( 'DB Error: ' . $wpdb->last_error );
}
$wpdb->last_query;         // Last query string executed
$wpdb->num_rows;           // Rows returned by last SELECT
$wpdb->rows_affected;      // Rows affected by last INSERT/UPDATE/DELETE
```

## Gotchas

- **NEVER** concatenate user input into SQL -- always use `$wpdb->prepare()`.
- `$wpdb->prepare()` returns `null` if called with no args to substitute.
- `$wpdb->insert()` returns `false` on error, `1` (rows affected) on success -- not the ID. Use `$wpdb->insert_id`.
- `get_results()` returns `null` on failure, empty array on no results.
- For WP multisite, use `$wpdb->base_prefix` for network-wide tables.
- Prefer WP API functions (`get_post_meta`, `update_option`, etc.) over direct queries when possible.
