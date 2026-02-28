# WordPress Hooks System Reference

## Core Functions

### Actions (execute code at specific points)

```php
add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 );
do_action( string $hook, mixed ...$args );
remove_action( string $hook, callable $callback, int $priority = 10 );
has_action( string $hook, callable $callback = false );  // Returns priority or false
did_action( string $hook );  // Returns count of times fired
```

### Filters (modify data and return it)

```php
add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 );
$value = apply_filters( string $hook, mixed $value, mixed ...$args );
remove_filter( string $hook, callable $callback, int $priority = 10 );
has_filter( string $hook, callable $callback = false );
```

### Priority & accepted_args

- **Priority:** Lower = runs earlier. Default `10`. Use `1` for early, `999` for late.
- **accepted_args:** Number of arguments your callback receives. Must match if hook passes multiple args.

```php
// Filter with 3 args
add_filter( 'the_content', 'my_filter', 10, 1 );       // Receives $content
add_filter( 'save_post', 'my_save', 10, 3 );            // $post_id, $post, $update
```

## Essential Hooks by Category

### Initialization (execution order)

| Hook | Type | When | Common Use |
|---|---|---|---|
| `muplugins_loaded` | action | After MU plugins load | Early setup |
| `plugins_loaded` | action | After all plugins load | Plugin init, load textdomains |
| `after_setup_theme` | action | After theme functions.php | Theme features, add_theme_support |
| `init` | action | WP fully loaded | Register CPTs, taxonomies, shortcodes |
| `wp_loaded` | action | After WP + plugins + theme | Late init, flush rewrite rules |
| `admin_init` | action | Admin page load start | Admin-only setup, option registration |

### Admin

| Hook | Type | When | Common Use |
|---|---|---|---|
| `admin_menu` | action | Before admin menu renders | Add menu/submenu pages |
| `admin_enqueue_scripts` | action | Admin head | Enqueue admin CSS/JS |
| `admin_notices` | action | After admin header | Display admin messages |
| `add_meta_boxes` | action | Meta box registration | Register post editor meta boxes |
| `dashboard_glance_items` | filter | Dashboard "At a Glance" | Add CPT counts |

### Frontend

| Hook | Type | When | Common Use |
|---|---|---|---|
| `wp_enqueue_scripts` | action | Frontend head | Enqueue CSS/JS (NOT admin_enqueue_scripts) |
| `wp_head` | action | Inside `<head>` | Output meta tags, inline styles |
| `wp_footer` | action | Before `</body>` | Output scripts, tracking code |
| `wp_body_open` | action | After `<body>` tag | Skip links, tracking pixels |
| `template_redirect` | action | Before template loads | Redirects, access control |
| `template_include` | filter | Template file path | Override template selection |

### Content & Queries

| Hook | Type | When | Common Use |
|---|---|---|---|
| `pre_get_posts` | action | Before WP_Query executes | Modify main/custom queries |
| `the_content` | filter | Post content output | Modify post content |
| `the_title` | filter | Post title output | Modify post title |
| `the_excerpt` | filter | Excerpt output | Modify excerpt |
| `wp_title` | filter | Page `<title>` | Modify page title |
| `posts_clauses` | filter | SQL query parts | Advanced query modification |
| `found_posts` | filter | After query count | Modify total found count |

### Post Lifecycle

| Hook | Type | When | Common Use |
|---|---|---|---|
| `save_post` | action | Post saved/updated | Save custom meta, 3 args: $post_id, $post, $update |
| `save_post_{post_type}` | action | Specific CPT saved | CPT-specific save logic |
| `wp_insert_post` | action | After post inserted in DB | Post-save processing |
| `before_delete_post` | action | Before post deleted | Cleanup related data |
| `wp_trash_post` | action | Post trashed | Pre-trash handling |
| `transition_post_status` | action | Status changes | 3 args: $new, $old, $post |

### User & Auth

| Hook | Type | When | Common Use |
|---|---|---|---|
| `wp_login` | action | Successful login | Log logins, redirect |
| `wp_logout` | action | User logs out | Cleanup sessions |
| `user_register` | action | New user created | Send welcome email |
| `profile_update` | action | Profile updated | Sync external systems |

### REST API

| Hook | Type | When | Common Use |
|---|---|---|---|
| `rest_api_init` | action | REST API bootstraps | Register routes |
| `rest_pre_dispatch` | filter | Before route dispatch | Auth, rate limiting |
| `rest_post_dispatch` | filter | After route dispatch | Modify response |

## Gotchas

- **remove_action/remove_filter:** Must match exact callback, priority. For class methods: `remove_action('hook', [$instance, 'method'], 10)` -- you need the same object instance.
- **pre_get_posts:** Always check `$query->is_main_query()` to avoid modifying all queries including widget/menu queries.
- **save_post:** Fires on autosave and revisions. Guard with:
  ```php
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
  if ( wp_is_post_revision( $post_id ) ) return;
  ```
- **Infinite loops:** Calling `wp_update_post()` inside `save_post` re-triggers the hook. Use `remove_action` before, `add_action` after.
- **Hook naming:** Dynamic hooks use post type: `save_post_{$post_type}`, `manage_{$post_type}_posts_columns`.
- **plugins_loaded** fires before `init` -- don't call functions that depend on taxonomies/CPTs being registered.
- Closures/anonymous functions cannot be removed with `remove_action`.
