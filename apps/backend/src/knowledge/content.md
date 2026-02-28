## Content Management

### Post Types

WordPress ships with built-in post types: `post`, `page`, `attachment`, `revision`, `nav_menu_item`, `wp_block`, `wp_template`, `wp_template_part`, `wp_navigation`. Sites can register custom post types (CPTs) with `register_post_type()`.

#### register_post_type()

```php
register_post_type( string $post_type, array|string $args = array() ): WP_Post_Type|WP_Error
```

Must be called on the `init` hook (after `after_setup_theme`, before `admin_init`). The post type identifier must not exceed 20 characters, must be lowercase alphanumeric/dashes/underscores, and must not use the `wp_` prefix (reserved for core).

**Best practices:**
- Place CPT registration in plugins (not themes) so content persists across theme changes.
- Prefix identifiers with your plugin/theme slug to avoid conflicts (e.g., `myplugin_product`).
- After registration, call `flush_rewrite_rules()` only on plugin activation/deactivation (never on every page load).
- Set `show_in_rest => true` for block editor (Gutenberg) support.

**Full args with defaults:**

| Argument | Type | Default | Description |
|----------|------|---------|-------------|
| `label` | string | `$labels['name']` | Plural name for the menu |
| `labels` | array | auto-generated | All UI text strings (see labels below) |
| `description` | string | `''` | Brief summary |
| `public` | bool | `false` | Controls overall visibility |
| `hierarchical` | bool | `false` | Whether it supports parent-child (like pages) |
| `exclude_from_search` | bool | opposite of `public` | Exclude from front-end search |
| `publicly_queryable` | bool | value of `public` | Enable front-end queries |
| `show_ui` | bool | value of `public` | Generate admin UI |
| `show_in_menu` | bool\|string | value of `show_ui` | Display in admin menu (string = submenu parent) |
| `show_in_nav_menus` | bool | value of `public` | Available in navigation menus |
| `show_in_admin_bar` | bool | value of `show_in_menu` | Available in admin bar "New" menu |
| `show_in_rest` | bool | `false` | Include in REST API; required for block editor |
| `rest_base` | string | `$post_type` | REST API route base slug |
| `rest_namespace` | string | `'wp/v2'` | REST API namespace |
| `rest_controller_class` | string | `'WP_REST_Posts_Controller'` | Custom REST controller |
| `menu_position` | int | `null` | Admin menu order (5=below Posts, 25=below Comments) |
| `menu_icon` | string | `null` | Dashicon class (e.g. `'dashicons-book'`) or image URL |
| `capability_type` | string\|array | `'post'` | Base string for auto-generating capabilities |
| `capabilities` | array | auto-generated | Explicit capability mapping |
| `map_meta_cap` | bool | `false` | Use default meta capability handling |
| `supports` | array\|false | `['title', 'editor']` | Feature support flags |
| `register_meta_box_cb` | callable | `null` | Callback to set up meta boxes |
| `taxonomies` | array | `[]` | Taxonomies to connect |
| `has_archive` | bool\|string | `false` | Enable archive page (string = custom slug) |
| `rewrite` | bool\|array | `true` | Rewrite rules (array: slug, with_front, feeds, pages, ep_mask) |
| `query_var` | string\|bool | `$post_type` | Query variable key |
| `can_export` | bool | `true` | Allow export via Tools > Export |
| `delete_with_user` | bool\|null | `null` | Delete user's posts when user is deleted |
| `template` | array | `[]` | Default block template for the editor |
| `template_lock` | string\|false | `false` | Lock template: `'all'` (no changes) or `'insert'` (no new blocks) |

**Supports array options:** `'title'`, `'editor'`, `'comments'`, `'revisions'`, `'trackbacks'`, `'author'`, `'excerpt'`, `'page-attributes'`, `'thumbnail'`, `'custom-fields'`, `'post-formats'`, `'autosave'`

**Labels array keys (auto-generated from label/singular_name):**
`name`, `singular_name`, `add_new`, `add_new_item`, `edit_item`, `new_item`, `view_item`, `view_items`, `search_items`, `not_found`, `not_found_in_trash`, `parent_item_colon`, `all_items`, `archives`, `attributes`, `insert_into_item`, `uploaded_to_this_item`, `featured_image`, `set_featured_image`, `remove_featured_image`, `use_featured_image`, `menu_name`, `filter_items_list`, `items_list_navigation`, `items_list`, `item_published`, `item_published_privately`, `item_reverted_to_draft`, `item_trashed`, `item_scheduled`, `item_updated`, `item_link`, `item_link_description`

---

### Creating/Updating Posts

#### wp_insert_post()

```php
wp_insert_post( array $postarr, bool $wp_error = false, bool $fire_after_hooks = true ): int|WP_Error
```

Creates a new post or updates an existing one (when `ID` is present in `$postarr`).

**$postarr keys:**

| Key | Type | Description |
|-----|------|-------------|
| `ID` | int | Post ID; non-zero triggers update mode |
| `post_author` | int | User ID (defaults to current user) |
| `post_date` | string | Local publish date (`'Y-m-d H:i:s'`) |
| `post_date_gmt` | string | GMT publish date |
| `post_content` | string | Main body (HTML, Gutenberg blocks, or empty for Elementor) |
| `post_content_filtered` | string | Filtered/cached content version |
| `post_title` | string | Post headline (required with post_content) |
| `post_excerpt` | string | Brief summary |
| `post_status` | string | `'draft'`, `'publish'`, `'pending'`, `'private'`, `'future'`, `'trash'`, `'auto-draft'` |
| `post_type` | string | `'post'`, `'page'`, or custom type |
| `comment_status` | string | `'open'` or `'closed'` |
| `ping_status` | string | `'open'` or `'closed'` |
| `post_password` | string | Password protection |
| `post_name` | string | URL slug (auto-generated from title if omitted) |
| `to_ping` | string | URLs to ping on publish |
| `pinged` | string | Already-pinged URLs |
| `post_parent` | int | Parent post ID (for hierarchical types) |
| `menu_order` | int | Display order |
| `post_mime_type` | string | MIME type (attachments only) |
| `guid` | string | Global unique identifier |
| `import_id` | int | Force a specific ID on import |
| `post_category` | int[] | Category IDs array |
| `tags_input` | array | Tag names, slugs, or IDs |
| `tax_input` | array | Custom taxonomy terms keyed by taxonomy name |
| `meta_input` | array | Post meta key-value pairs (set in one call on create) |
| `page_template` | string | Page template file (pages only) |

**Return:** Post ID (int) on success; `0` or `WP_Error` on failure.

**Key behaviors:**
- `post_title` and `post_content` are required for non-empty posts.
- `meta_input` sets multiple meta fields atomically on create.
- Posts without a category get the default category automatically (except drafts).
- Duplicate slugs are auto-uniquified.
- Future-dated posts automatically get `'future'` status.
- Data is sanitized through `sanitize_post()`.
- Always pass `$wp_error = true` to get meaningful error information.

#### wp_update_post()

```php
wp_update_post( array|object $postarr = array(), bool $wp_error = false, bool $fire_after_hooks = true ): int|WP_Error
```

Merges new values with the existing post's fields. Accepts the same keys as `wp_insert_post()`.

**Key differences from wp_insert_post():**
- Omitted fields are preserved from the existing post (not reset).
- `post_category`: if omitted, existing categories are preserved; if provided, it replaces the full category list.
- `tags_input`: compared against existing tags; identical sets are skipped to avoid unnecessary processing.
- Draft/pending/auto-draft posts get special date handling (dates not auto-set unless explicitly modified).

#### wp_delete_post()

```php
wp_delete_post( int $post_id, bool $force_delete = false ): WP_Post|false|null
```

By default moves posts to Trash. Permanent deletion occurs when:
- `$force_delete = true`
- The post is already in Trash
- `EMPTY_TRASH_DAYS` is set to `0`
- The post type is `'attachment'`

**On permanent deletion, also removes:** all comments, post meta, taxonomy term associations, revisions, and updates child post parent references.

**Return:** `WP_Post` on success, `false` on failure, `null` if post does not exist.

#### wp_trash_post()

```php
wp_trash_post( int $post_id ): WP_Post|false|null
```

Moves a post to Trash without permanent deletion. Stores the original status in `_wp_trash_meta_status` meta and the trash time in `_wp_trash_meta_time`.

---

### Post Meta

```php
get_post_meta( int $post_id, string $key = '', bool $single = false )
```
- With `$key` and `$single = true`: returns a single value.
- With `$key` and `$single = false`: returns an array of all values for that key.
- Without `$key`: returns all meta as an associative array.

```php
update_post_meta( int $post_id, string $meta_key, mixed $meta_value, mixed $prev_value = '' ): int|bool
```
- Creates the meta if it does not exist; updates if it does.
- `$prev_value` can disambiguate which row to update when multiple values exist for the same key.
- Returns meta ID on create, `true` on update, `false` on failure.

```php
add_post_meta( int $post_id, string $meta_key, mixed $meta_value, bool $unique = false ): int|false
```
- Adds a new meta entry. If `$unique = true`, does not add if key already exists.

```php
delete_post_meta( int $post_id, string $meta_key, mixed $meta_value = '' ): bool
```
- Without `$meta_value`: deletes all entries for that key.
- With `$meta_value`: deletes only the matching entry.

**Featured images** are stored as meta key `_thumbnail_id` pointing to an attachment post ID. Use `set_post_thumbnail($post_id, $attachment_id)` and `get_post_thumbnail_id($post_id)`.

#### register_meta() / register_post_meta()

```php
register_meta( string $object_type, string $meta_key, array $args ): bool
register_post_meta( string $post_type, string $meta_key, array $args ): bool
```

Registers metadata for standardized handling, REST API exposure, and schema validation.

**Args:**

| Key | Type | Description |
|-----|------|-------------|
| `object_subtype` | string | Specific post type or taxonomy (used with `register_meta`) |
| `type` | string | `'string'`, `'boolean'`, `'integer'`, `'number'`, `'array'`, `'object'` |
| `label` | string | Human-readable field name |
| `description` | string | Field description |
| `single` | bool | Single value per object vs. multiple |
| `default` | mixed | Default value when none exists |
| `sanitize_callback` | callable | Input sanitization function |
| `auth_callback` | callable | Authorization check for read/write |
| `show_in_rest` | bool\|array | Expose via REST API; array allows schema definition |
| `revisions_enabled` | bool | Track changes across post revisions |

**Important:** Post types must declare `'supports' => ['custom-fields']` for registered meta to appear in REST responses.

---

### Post Status

WordPress built-in statuses: `publish`, `draft`, `pending`, `private`, `future`, `trash`, `auto-draft`, `inherit` (attachments/revisions).

#### Status Transitions

- `wp_publish_post( int $post_id )` — Publishes a post, firing transition hooks.
- Transition hooks: `transition_post_status`, `{old_status}_to_{new_status}`, `{new_status}_{post_type}`.

#### register_post_status()

```php
register_post_status( string $post_status, array|string $args = array() ): object
```

Must be called on `init` or later. Status name max 20 characters, sanitized via `sanitize_key()`.

**Args:**

| Argument | Type | Description |
|----------|------|-------------|
| `label` | string | Display name |
| `label_count` | array | Plural forms via `_n_noop()` |
| `exclude_from_search` | bool | Hide from search results |
| `public` | bool | Visible on front-end |
| `internal` | bool | Internal use only |
| `protected` | bool | Protected status (requires permission to view) |
| `private` | bool | Private visibility |
| `publicly_queryable` | bool | Allow public URL queries |
| `show_in_admin_all_list` | bool | Show in "All" post list |
| `show_in_admin_status_list` | bool | Show in status filter counts |
| `date_floating` | bool | Enable floating (non-fixed) dates |

---

### Querying Content

#### WP_Query

```php
$query = new WP_Query( array $args );
```

The core class for querying posts. Each instance operates independently.

**Key properties:**
- `$posts` — Array of retrieved WP_Post objects
- `$found_posts` — Total posts matching criteria (for pagination math)
- `$max_num_pages` — Total pages available (`ceil(found_posts / posts_per_page)`)
- `$post_count` — Number of posts in current page
- `$current_post` — Index during The Loop (-1 before loop starts)
- `$post` — Current post object during The Loop
- `$query_vars` — Parsed query parameters
- `$request` — The SQL query string

**The Loop pattern:**
```php
$query = new WP_Query( $args );
if ( $query->have_posts() ) {
    while ( $query->have_posts() ) {
        $query->the_post();
        // Access global $post, use template tags
    }
    wp_reset_postdata(); // Always restore original $post
}
```

**All major query parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `post_type` | string\|array | `'post'`, `'page'`, `'any'`, or custom type(s) |
| `post_status` | string\|array | `'publish'`, `'draft'`, `'pending'`, `'private'`, `'trash'`, `'any'` |
| `posts_per_page` | int | Posts per page (`-1` for all; default: `get_option('posts_per_page')`) |
| `paged` | int | Page number for pagination |
| `offset` | int | Number of posts to skip (overrides `paged`) |
| `nopaging` | bool | Show all posts (ignore pagination) |
| `p` | int | Get a specific post by ID |
| `name` | string | Get a specific post by slug |
| `page_id` | int | Get a specific page by ID |
| `pagename` | string | Get a specific page by slug |
| `post__in` | int[] | Include only these post IDs (no order guarantee unless `orderby => 'post__in'`) |
| `post__not_in` | int[] | Exclude these post IDs |
| `post_parent` | int | Filter by parent post ID |
| `post_parent__in` | int[] | Filter by multiple parent IDs |
| `post_parent__not_in` | int[] | Exclude by parent IDs |
| `author` | int | Filter by author ID |
| `author_name` | string | Filter by author nicename |
| `author__in` | int[] | Include multiple authors |
| `author__not_in` | int[] | Exclude authors |
| `cat` | int | Category ID (negative excludes) |
| `category_name` | string | Category slug |
| `category__and` | int[] | Posts in ALL listed categories |
| `category__in` | int[] | Posts in ANY listed category |
| `category__not_in` | int[] | Posts NOT in these categories |
| `tag` | string | Tag slug |
| `tag_id` | int | Tag ID |
| `tag__and` | int[] | Posts with ALL listed tags |
| `tag__in` | int[] | Posts with ANY listed tag |
| `tag__not_in` | int[] | Posts NOT with these tags |
| `s` | string | Search keyword |
| `exact` | bool | Exact search match (no wildcards) |
| `sentence` | bool | Search as full phrase |
| `orderby` | string\|array | Sort field(s) — see orderby options below |
| `order` | string | `'ASC'` or `'DESC'` (default: `'DESC'`) |
| `ignore_sticky_posts` | bool | Ignore sticky post ordering (default: `false`) |
| `has_password` | bool | Filter by password protection |
| `post_password` | string | Filter by specific password |
| `comment_count` | int\|array | Filter by comment count (array: `['value' => N, 'compare' => '>=']`) |
| `fields` | string | `'ids'` returns post IDs only; `'id=>parent'` returns ID/parent pairs |
| `no_found_rows` | bool | Skip counting total rows (faster when pagination not needed) |
| `suppress_filters` | bool | Skip pre/post query filters |
| `cache_results` | bool | Cache post information (default: `true`) |
| `update_post_meta_cache` | bool | Prime meta cache (default: `true`) |
| `update_post_term_cache` | bool | Prime term cache (default: `true`) |
| `lazy_load_term_meta` | bool | Lazy-load term meta (default: `true`) |

**orderby options:** `'none'`, `'ID'`, `'author'`, `'title'`, `'name'` (slug), `'type'`, `'date'`, `'modified'`, `'parent'`, `'rand'`, `'comment_count'`, `'relevance'` (search only), `'menu_order'`, `'meta_value'`, `'meta_value_num'`, `'post__in'`, `'post_name__in'`, `'post_parent__in'`

**tax_query — Taxonomy filtering:**
```php
'tax_query' => array(
    'relation' => 'AND',  // 'AND' or 'OR' between clauses
    array(
        'taxonomy' => 'category',
        'field'    => 'slug',       // 'term_id', 'name', 'slug', or 'term_taxonomy_id'
        'terms'    => array( 'news', 'updates' ),
        'operator' => 'IN',        // 'IN', 'NOT IN', 'AND', 'EXISTS', 'NOT EXISTS'
        'include_children' => true, // Include child terms (hierarchical only)
    ),
)
```

**meta_query — Custom field filtering:**
```php
'meta_query' => array(
    'relation' => 'AND',
    array(
        'key'     => 'color',
        'value'   => 'blue',
        'compare' => '=',    // '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE',
                              // 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN',
                              // 'EXISTS', 'NOT EXISTS', 'REGEXP', 'NOT REGEXP', 'RLIKE'
        'type'    => 'CHAR', // 'NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME',
                              // 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED'
    ),
)
```

Named meta_query clauses can be used as `orderby` keys:
```php
'meta_query' => array(
    'price_clause' => array( 'key' => 'price', 'type' => 'NUMERIC' ),
),
'orderby' => 'price_clause',
```

**date_query — Date filtering:**
```php
'date_query' => array(
    'relation' => 'AND',
    array(
        'after'     => '2024-01-01',    // or array('year'=>2024, 'month'=>1, 'day'=>1)
        'before'    => '2024-12-31',
        'inclusive' => true,
        'column'    => 'post_date',     // 'post_date', 'post_modified', 'post_date_gmt', 'post_modified_gmt'
    ),
    array(
        'year'      => 2024,
        'month'     => 6,
        'week'      => 25,
        'day'       => 15,
        'hour'      => 14,
        'minute'    => 30,
        'dayofweek' => array( 2, 6 ),  // Monday=1, Sunday=7
        'compare'   => 'BETWEEN',
    ),
)
```

**Pagination:**
- Use `$query->found_posts` for total matching posts.
- Use `$query->max_num_pages` for total page count.
- Current page: `$paged = get_query_var('paged') ? get_query_var('paged') : 1;`
- `paginate_links()` or `the_posts_pagination()` for pagination output.

**pre_get_posts hook:**
```php
add_action( 'pre_get_posts', function( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        $query->set( 'posts_per_page', 12 );
    }
});
```
Modifies the main query before it runs. Always check `is_main_query()` to avoid affecting custom queries.

#### get_posts()

```php
get_posts( array $args = null ): WP_Post[]|int[]
```

Wrapper around `WP_Query` that returns an array without affecting The Loop.

**Key differences from WP_Query:**
- `ignore_sticky_posts` defaults to `true`
- `no_found_rows` defaults to `true` (faster; no pagination data)
- `suppress_filters` defaults to `true`
- Uses `numberposts` (default: `5`) as alias for `posts_per_page`
- Uses `include`/`exclude` as aliases for `post__in`/`post__not_in`
- Uses `category` as alias for `cat`

Accepts all `WP_Query` parameters in addition to its own aliases.

---

### Revisions

WordPress auto-saves revisions on each post update. `wp_get_post_revisions( $post_id )` retrieves them. Each revision stores the full post content at that point in time. Control with `WP_POST_REVISIONS` constant (number or `false` to disable).

### Bulk Operations

For large operations, use `$wpdb` directly with `$wpdb->prepare()`. Wrap in transactions if needed:
```php
$wpdb->query( 'START TRANSACTION' );
// ... multiple operations ...
$wpdb->query( 'COMMIT' );
```

### Common Patterns

**Get all posts of a custom type:**
```php
$posts = get_posts([ 'post_type' => 'case-study', 'numberposts' => -1, 'post_status' => 'publish' ]);
```

**Create a post with meta and terms:**
```php
$post_id = wp_insert_post([
    'post_title'   => 'My Post',
    'post_content' => 'Content here',
    'post_status'  => 'publish',
    'post_type'    => 'post',
    'meta_input'   => [ '_custom_key' => 'value' ],
    'post_category' => [ 3, 5 ],
    'tags_input'   => [ 'tag1', 'tag2' ],
], true);
```

**Paginated query with meta filter:**
```php
$query = new WP_Query([
    'post_type'      => 'product',
    'posts_per_page' => 12,
    'paged'          => get_query_var('paged') ?: 1,
    'meta_query'     => [[ 'key' => 'featured', 'value' => '1' ]],
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
```

**Search with taxonomy and date filter:**
```php
$query = new WP_Query([
    's'          => 'keyword',
    'post_type'  => 'post',
    'tax_query'  => [[ 'taxonomy' => 'category', 'field' => 'slug', 'terms' => 'news' ]],
    'date_query' => [[ 'after' => '2024-01-01' ]],
]);
```
