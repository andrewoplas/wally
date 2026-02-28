## Taxonomies

### Built-in Taxonomies

- `category` — hierarchical (parent-child), for posts. Default term: "Uncategorized".
- `post_tag` — flat (no hierarchy), for posts.
- `nav_menu` — used internally for navigation menus.
- `post_format` — special taxonomy for post formats (aside, gallery, link, image, quote, status, video, audio, chat).
- `wp_theme` — used internally for block themes.
- `wp_template_part_area` — used internally for template part areas.

---

### Registering Custom Taxonomies

#### register_taxonomy()

```php
register_taxonomy( string $taxonomy, array|string $object_type, array|string $args = array() ): WP_Taxonomy|WP_Error
```

Must be called on the `init` hook. The taxonomy key must not exceed 32 characters and may only contain lowercase alphanumeric characters, dashes, and underscores.

**Best practices:**
- Register on the `init` hook (not before, not after `admin_init`).
- Prefix taxonomy names with your plugin/theme slug (e.g., `myplugin_genre`) to avoid conflicts.
- Set `show_in_rest => true` for block editor (Gutenberg) support and REST API access.
- Use hierarchical taxonomies (like categories) for tree-structured classifications; flat (like tags) for free-form labeling.
- Flush rewrite rules only on plugin activation/deactivation, never on every page load.
- Place taxonomy registration in plugins (not themes) for content portability.
- Use taxonomies (not post meta) when you need to group/filter posts by a shared classification — they are indexed and performant for queries.

**Full args with defaults:**

| Argument | Type | Default | Description |
|----------|------|---------|-------------|
| `labels` | array | auto-generated | All UI text strings (see labels below) |
| `description` | string | `''` | Descriptive summary |
| `public` | bool | `true` | Intended for public use |
| `publicly_queryable` | bool | value of `public` | Whether queryable on front-end |
| `hierarchical` | bool | `false` | Parent-child relationships (category-like vs tag-like) |
| `show_ui` | bool | value of `public` | Generate default admin UI |
| `show_in_menu` | bool | value of `show_ui` | Show in admin menu |
| `show_in_nav_menus` | bool | value of `public` | Available in navigation menu builder |
| `show_in_rest` | bool | `false` | Include in REST API; required for block editor |
| `rest_base` | string | `$taxonomy` | REST API route base slug |
| `rest_namespace` | string | `'wp/v2'` | REST API namespace |
| `rest_controller_class` | string | `'WP_REST_Terms_Controller'` | Custom REST controller class |
| `show_tagcloud` | bool | value of `show_ui` | Allow in Tag Cloud widget |
| `show_in_quick_edit` | bool | value of `show_ui` | Show in quick/bulk edit panel |
| `show_admin_column` | bool | `false` | Display column on post type list table |
| `meta_box_cb` | bool\|callable | `null` | Custom meta box callback (`false` removes meta box) |
| `meta_box_sanitize_cb` | callable | varies | Sanitization callback for meta box input |
| `capabilities` | array | see below | Custom capability mapping |
| `rewrite` | bool\|array | `true` | URL rewrite rules (array for customization) |
| `query_var` | string\|bool | `$taxonomy` | Query variable key |
| `update_count_callback` | callable | varies | Function called when object count updates |
| `default_term` | string\|array | none | Default term auto-created and assigned |
| `sort` | bool | `false` | Whether terms should maintain insertion order |
| `args` | array | `[]` | Default args for `wp_get_object_terms()` |

**Capabilities sub-array (defaults):**

| Capability | Default |
|------------|---------|
| `manage_terms` | `'manage_categories'` |
| `edit_terms` | `'manage_categories'` |
| `delete_terms` | `'manage_categories'` |
| `assign_terms` | `'edit_posts'` |

**Rewrite sub-array (when array):**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `slug` | string | `$taxonomy` | Custom permalink slug |
| `with_front` | bool | `true` | Prepend global rewrite front base |
| `hierarchical` | bool | `false` | Allow hierarchical URLs (e.g., `/genre/rock/metal/`) |
| `ep_mask` | int | `EP_NONE` | Endpoint mask |

**Labels sub-array keys (auto-generated from name/singular_name):**

| Key | Hierarchical Default | Non-hierarchical Default |
|-----|---------------------|-------------------------|
| `name` | "Categories" | "Tags" |
| `singular_name` | "Category" | "Tag" |
| `menu_name` | value of `name` | value of `name` |
| `all_items` | "All Categories" | "All Tags" |
| `edit_item` | "Edit Category" | "Edit Tag" |
| `view_item` | "View Category" | "View Tag" |
| `update_item` | "Update Category" | "Update Tag" |
| `add_new_item` | "Add New Category" | "Add New Tag" |
| `new_item_name` | "New Category Name" | "New Tag Name" |
| `parent_item` | "Parent Category" | `null` |
| `parent_item_colon` | "Parent Category:" | `null` |
| `search_items` | "Search Categories" | "Search Tags" |
| `popular_items` | `null` | "Popular Tags" |
| `separate_items_with_commas` | `null` | "Separate tags with commas" |
| `add_or_remove_items` | `null` | "Add or remove tags" |
| `choose_from_most_used` | `null` | "Choose from the most used tags" |
| `not_found` | "No categories found." | "No tags found." |
| `no_terms` | "No categories" | "No tags" |
| `filter_by_item` | "Filter by category" | `null` |
| `items_list_navigation` | "Categories list navigation" | "Tags list navigation" |
| `items_list` | "Categories list" | "Tags list" |
| `back_to_items` | "Back to categories" | "Back to tags" |
| `item_link` | "Category Link" | "Tag Link" |
| `item_link_description` | "A link to a category." | "A link to a tag." |

---

### Term CRUD

#### wp_insert_term()

```php
wp_insert_term( string $term, string $taxonomy, array|string $args = array() ): array|WP_Error
```

Creates a new term in the specified taxonomy.

**$args keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `alias_of` | string | `''` | Slug of term to make this an alias of (shares term_group) |
| `description` | string | `''` | Term description |
| `parent` | int | `0` | Parent term ID (hierarchical taxonomies only) |
| `slug` | string | auto from name | URL-friendly identifier |

**Return on success:** `array( 'term_id' => int, 'term_taxonomy_id' => int|string )`
**Return on failure:** `WP_Error` — taxonomy does not exist, empty name, parent does not exist, or duplicate term.

#### wp_update_term()

```php
wp_update_term( int $term_id, string $taxonomy, array $args = array() ): array|WP_Error
```

Updates an existing term. Accepts the same `$args` as `wp_insert_term()` plus `term_group`.

**Updatable fields:** `name`, `slug`, `description`, `parent`, `alias_of`, `term_group`

**Key behaviors:**
- If slug is omitted, auto-generated from the name.
- Duplicate slugs are auto-uniquified.
- Returns `WP_Error` if taxonomy does not exist, name is empty, or parent does not exist.

**Return on success:** `array( 'term_id' => int, 'term_taxonomy_id' => int )`

#### wp_delete_term()

```php
wp_delete_term( int $term, string $taxonomy, array|string $args = array() ): bool|int|WP_Error
```

Deletes a term and removes all object associations.

**$args keys:**

| Key | Type | Description |
|-----|------|-------------|
| `default` | int | Term ID to reassign as replacement |
| `force_default` | bool | Force default assignment even when other terms remain |

**Return values:**
- `true` — successful deletion
- `false` — term does not exist
- `0` — attempted deletion of the default category (not allowed)
- `WP_Error` — taxonomy does not exist

**Behavior on deletion:**
- Objects assigned only to this term get the `default` term (if specified).
- Objects with multiple terms lose only the deleted term.
- Child terms in hierarchical taxonomies are reassigned to the deleted term's parent.
- Associated term meta is automatically deleted.
- Caches are cleaned for affected terms and objects.

---

### Reading Terms

#### get_terms()

```php
get_terms( array|string $args = array() ): WP_Term[]|int[]|string[]|string|WP_Error
```

Retrieves terms from one or more taxonomies. Internally creates a `WP_Term_Query` instance.

**Key args (all optional):**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `taxonomy` | string\|array | `''` | Taxonomy name(s) to query |
| `object_ids` | int\|int[] | none | Restrict to terms associated with these object IDs |
| `hide_empty` | bool | `true` | Exclude terms with no assigned objects |
| `orderby` | string | `'name'` | Sort field: `'name'`, `'slug'`, `'term_id'`, `'id'`, `'description'`, `'count'`, `'include'`, `'meta_value'`, `'meta_value_num'`, `'none'` |
| `order` | string | `'ASC'` | `'ASC'` or `'DESC'` |
| `number` | int | `0` (unlimited) | Maximum terms to return |
| `offset` | int | `0` | Skip N terms |
| `fields` | string | `'all'` | Return format: `'all'`, `'ids'`, `'tt_ids'`, `'names'`, `'slugs'`, `'count'`, `'id=>parent'`, `'id=>name'`, `'id=>slug'` |
| `include` | int[] | `[]` | Only include these term IDs |
| `exclude` | int[] | `[]` | Exclude these term IDs |
| `exclude_tree` | int[] | `[]` | Exclude terms and all their descendants |
| `child_of` | int | `0` | Get all descendants of this term (hierarchical only) |
| `parent` | int | `''` | Direct children of this term only |
| `childless` | bool | `false` | Only terms with no children |
| `name` | string\|array | `''` | Filter by exact term name(s) |
| `slug` | string\|array | `''` | Filter by exact term slug(s) |
| `search` | string | `''` | Search by term name and slug |
| `name__like` | string | `''` | LIKE search on term name |
| `description__like` | string | `''` | LIKE search on description |
| `hierarchical` | bool | `true` | Include terms with non-empty descendants |
| `pad_counts` | bool | `false` | Include descendant post counts in parent |
| `get` | string | `''` | Set to `'all'` to bypass `hide_empty` and `child_of` |
| `cache_results` | bool | `true` | Cache term data |
| `update_term_meta_cache` | bool | `true` | Prime term meta cache |
| `meta_key` | string | `''` | Filter by meta key |
| `meta_value` | string | `''` | Filter by meta value |
| `meta_query` | array | `[]` | Complex meta filtering (same syntax as WP_Query meta_query) |

**Return types:**
- `WP_Term[]` — default (array of term objects)
- `int[]` — when `fields = 'ids'`
- `string[]` — when `fields = 'names'` or `'slugs'`
- `string` — when `fields = 'count'` (numeric string)
- `WP_Error` — if taxonomy does not exist

**Hooks:** `get_terms` (filter results), `get_terms_orderby` (filter ORDER BY), `list_terms_exclusions` (filter exclusion SQL).

#### WP_Term_Query

```php
$term_query = new WP_Term_Query( array $args );
$terms = $term_query->get_terms();
```

The underlying class used by `get_terms()`. Accepts all the same args. Useful when you need access to the SQL query (`$term_query->request`) or want to modify query behavior.

**Key properties:**
- `$terms` — Retrieved term array
- `$request` — Generated SQL query string
- `$query_vars` — Parsed parameters
- `$sql_clauses` — Individual SQL clause components (SELECT, FROM, WHERE, ORDER BY)
- `$meta_query` — `WP_Meta_Query` instance

#### Other term reading functions

```php
get_term( int|WP_Term $term, string $taxonomy = '', string $output = OBJECT, string $filter = 'raw' ): WP_Term|array|WP_Error|null
```
Get a single term by ID or object.

```php
get_term_by( string $field, string|int $value, string $taxonomy = '', string $output = OBJECT, string $filter = 'raw' ): WP_Term|array|false
```
Find a specific term. `$field`: `'slug'`, `'name'`, `'id'`, or `'term_taxonomy_id'`.

```php
term_exists( int|string $term, string $taxonomy = '', int $parent_term = null ): null|int|array
```
Check if a term exists. Returns `null` if not found, term ID if found by name/slug, or array `['term_id', 'term_taxonomy_id']` if taxonomy specified.

---

### Assigning Terms to Objects

#### wp_set_object_terms()

```php
wp_set_object_terms( int $object_id, string|int|array $terms, string $taxonomy, bool $append = false ): array|WP_Error
```

Sets or appends terms for an object (typically a post).

**Parameters:**
- `$object_id` — The post (or other object) ID.
- `$terms` — Single term or array. **Integer values are treated as term IDs; string values are treated as slugs.** Non-existent integer IDs are silently skipped; non-existent string slugs create new terms.
- `$taxonomy` — Taxonomy name.
- `$append` — `false` (default): replaces all existing terms in that taxonomy. `true`: adds without removing existing terms.

**Return:** Array of term taxonomy IDs on success; `WP_Error` on failure. Empty array when removing all terms.

#### wp_get_object_terms()

```php
wp_get_object_terms( int|int[] $object_ids, string|string[] $taxonomies, array|string $args = array() ): WP_Term[]|int[]|string[]|string|WP_Error
```

Retrieves terms for one or more objects. Accepts `WP_Term_Query` args.

**Important:** Results are NOT cached. For posts, prefer `get_the_terms( $post_id, $taxonomy )` which uses the object cache and avoids redundant DB queries.

**Hooks:** `wp_get_object_terms_args` (filter args), `get_object_terms` (filter results), `wp_get_object_terms` (final filter).

#### wp_remove_object_terms()

```php
wp_remove_object_terms( int $object_id, string|int|array $terms, string $taxonomy ): bool|WP_Error
```

Removes specific terms from an object. Terms can be identified by ID (int) or slug (string).

**Return:** `true` on success, `false` or `WP_Error` on failure.

#### Shorthand functions

```php
wp_set_post_categories( int $post_id, array $post_categories = array(), bool $append = false ): array|false
```
Shorthand for setting categories on a post.

```php
wp_set_post_tags( int $post_id, string|array $tags = '', bool $append = false ): array|false|WP_Error
```
Shorthand for setting tags on a post. `$tags` can be a comma-separated string.

```php
wp_set_post_terms( int $post_id, string|array $terms = '', string $taxonomy = 'post_tag', bool $append = false ): array|false|WP_Error
```
General shorthand for any taxonomy.

---

### Querying by Taxonomy (in WP_Query)

Use the `tax_query` parameter in `WP_Query`:

```php
'tax_query' => array(
    'relation' => 'AND',    // 'AND' or 'OR' between clauses
    array(
        'taxonomy'         => 'category',
        'field'            => 'slug',     // 'term_id', 'name', 'slug', 'term_taxonomy_id'
        'terms'            => array( 'news', 'updates' ),
        'operator'         => 'IN',       // 'IN', 'NOT IN', 'AND', 'EXISTS', 'NOT EXISTS'
        'include_children' => true,       // Include child terms (hierarchical only, default true)
    ),
    array(
        'taxonomy' => 'post_tag',
        'field'    => 'slug',
        'terms'    => 'featured',
        'operator' => 'IN',
    ),
)
```

**Operators:**
- `IN` — posts with ANY of the listed terms (default)
- `NOT IN` — posts WITHOUT any of the listed terms
- `AND` — posts with ALL listed terms
- `EXISTS` — posts with any term in the taxonomy (ignores `terms`)
- `NOT EXISTS` — posts with no term in the taxonomy (ignores `terms`)

**Nested queries:** `tax_query` clauses can be nested with their own `relation` for complex logic.

---

### Term Meta

```php
get_term_meta( int $term_id, string $key = '', bool $single = false ): mixed
```
Same pattern as `get_post_meta()`. With key and `$single = true` returns single value; without key returns all meta.

```php
update_term_meta( int $term_id, string $meta_key, mixed $meta_value, mixed $prev_value = '' ): int|bool|WP_Error
```
Creates or updates term meta. Returns meta ID on create, `true` on update, `false`/`WP_Error` on failure.

```php
add_term_meta( int $term_id, string $meta_key, mixed $meta_value, bool $unique = false ): int|false|WP_Error
```
Adds a new term meta entry.

```php
delete_term_meta( int $term_id, string $meta_key, mixed $meta_value = '' ): bool
```
Deletes term meta. Without `$meta_value` deletes all entries for that key.

#### register_term_meta()

```php
register_term_meta( string $taxonomy, string $meta_key, array $args ): bool
```

Registers term metadata for REST API exposure and schema validation. Shorthand for `register_meta('term', ...)` with `object_subtype` set.

**Args (same as `register_meta`):**

| Key | Type | Description |
|-----|------|-------------|
| `type` | string | `'string'`, `'boolean'`, `'integer'`, `'number'`, `'array'`, `'object'` |
| `description` | string | Field description |
| `single` | bool | Single value per term vs. multiple |
| `default` | mixed | Default value |
| `sanitize_callback` | callable | Input sanitization |
| `auth_callback` | callable | Authorization check |
| `show_in_rest` | bool\|array | Expose via REST API; array for schema |
| `revisions_enabled` | bool | Track across revisions (not applicable to terms) |

---

### Hooks

#### Registration hooks
- `registered_taxonomy` — Fires after a taxonomy is registered. Params: `$taxonomy`, `$object_type`, `$args`.
- `registered_taxonomy_{$taxonomy}` — Same, specific to a taxonomy.

#### Term lifecycle hooks
- `create_{$taxonomy}` — Fires after a term is created in a specific taxonomy. Params: `$term_id`, `$tt_id`.
- `created_{$taxonomy}` — Fires after a term is created and the cache is cleaned. Params: `$term_id`, `$tt_id`.
- `edit_{$taxonomy}` — Fires when editing a term. Params: `$term_id`, `$tt_id`.
- `edited_{$taxonomy}` — Fires after a term is updated and cache cleared. Params: `$term_id`, `$tt_id`.
- `delete_{$taxonomy}` — Fires after a term is deleted. Params: `$term_id`, `$tt_id`, `$deleted_term`, `$object_ids`.
- `pre_delete_term` — Fires before a term is deleted. Params: `$term_id`, `$taxonomy`.
- `set_object_terms` — Fires after terms are set on an object. Params: `$object_id`, `$terms`, `$tt_ids`, `$taxonomy`, `$append`, `$old_tt_ids`.

#### Query hooks
- `pre_get_terms` — Fires before terms are retrieved. Modify `WP_Term_Query` before execution.
- `get_terms` — Filters the found terms before returning.
- `get_terms_orderby` — Filters the ORDER BY clause.
- `terms_clauses` — Filters all SQL clauses (fields, join, where, orderby, order, limits).

---

### Common Patterns

**List all terms in a taxonomy:**
```php
$terms = get_terms([ 'taxonomy' => 'genre', 'hide_empty' => false ]);
```

**Get terms for a specific post (cached):**
```php
$terms = get_the_terms( $post_id, 'genre' );
if ( $terms && ! is_wp_error( $terms ) ) {
    $names = wp_list_pluck( $terms, 'name' );
}
```

**Create a hierarchical term:**
```php
$parent = wp_insert_term( 'Rock', 'genre' );
wp_insert_term( 'Metal', 'genre', [ 'parent' => $parent['term_id'] ] );
```

**Replace all terms on a post:**
```php
wp_set_object_terms( $post_id, [ 'rock', 'jazz' ], 'genre', false );
```

**Append terms without removing existing:**
```php
wp_set_object_terms( $post_id, [ 'blues' ], 'genre', true );
```

**Remove a specific term from a post:**
```php
wp_remove_object_terms( $post_id, 'jazz', 'genre' );
```

**Query posts by multiple taxonomies:**
```php
$query = new WP_Query([
    'post_type' => 'post',
    'tax_query' => [
        'relation' => 'AND',
        [ 'taxonomy' => 'category', 'field' => 'slug', 'terms' => 'news' ],
        [ 'taxonomy' => 'post_tag', 'field' => 'slug', 'terms' => [ 'featured', 'important' ], 'operator' => 'IN' ],
    ],
]);
```

**Term meta for custom data:**
```php
register_term_meta( 'genre', 'icon_url', [
    'type'              => 'string',
    'single'            => true,
    'show_in_rest'      => true,
    'sanitize_callback' => 'esc_url_raw',
]);
update_term_meta( $term_id, 'icon_url', 'https://example.com/icon.png' );
$icon = get_term_meta( $term_id, 'icon_url', true );
```
