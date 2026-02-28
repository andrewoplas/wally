# WordPress Multisite Reference

## Detection & Setup

```php
// Check if multisite is active
if ( is_multisite() ) { /* multisite-specific code */ }

// wp-config.php constants (set during multisite installation)
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', false );           // false = subdirectory, true = subdomain
define( 'DOMAIN_CURRENT_SITE', 'example.com' );
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );           // Network ID
define( 'BLOG_ID_CURRENT_SITE', 1 );           // Main site ID
```

## Database Tables

Each sub-site gets its own set of tables with the blog ID in the prefix:
- Main site (ID 1): `wp_posts`, `wp_options`, `wp_postmeta`, etc.
- Sub-site (ID 2): `wp_2_posts`, `wp_2_options`, `wp_2_postmeta`, etc.
- Sub-site (ID 5): `wp_5_posts`, `wp_5_options`, `wp_5_postmeta`, etc.

**Network-wide tables** (shared across all sites):
`wp_blogs`, `wp_site`, `wp_sitemeta`, `wp_signups`, `wp_registration_log`, `wp_users`, `wp_usermeta`

```php
$wpdb->prefix;       // Current site prefix: 'wp_2_' on sub-site 2
$wpdb->base_prefix;  // Always 'wp_' -- use for network tables
```

## Switching Between Sites

```php
// ALWAYS pair switch_to_blog() with restore_current_blog()
switch_to_blog( $blog_id );
$posts = get_posts( [ 'post_type' => 'page' ] );  // Queries the switched site
$option = get_option( 'blogname' );                 // Gets option from switched site
restore_current_blog();

// Get current blog ID
$current_id = get_current_blog_id();
```

## Querying Sites

```php
// Get sites (WP 4.6+)
$sites = get_sites( [
    'number'     => 50,
    'offset'     => 0,
    'network_id' => 1,
    'public'     => 1,
    'archived'   => 0,
    'deleted'    => 0,
    'orderby'    => 'domain',
    'order'      => 'ASC',
    'search'     => 'example',       // Searches domain and path
    'site__in'   => [ 1, 2, 3 ],
] );
// Returns array of WP_Site objects: blog_id, domain, path, registered, last_updated, public, archived, deleted

$site = get_blog_details( $blog_id );   // Single site details
$count = get_blog_count();               // Total number of sites
```

## Creating & Managing Sites

```php
// Create a new site
$blog_id = wpmu_create_blog( $domain, $path, $title, $user_id, $options, $network_id );
// $options: array of site options to set (e.g., ['public' => 1])

// Update site attributes
update_blog_status( $blog_id, 'archived', 1 );  // Archive a site
update_blog_status( $blog_id, 'deleted', 1 );   // Mark as deleted
update_blog_status( $blog_id, 'spam', 1 );      // Mark as spam

// Delete a site permanently
wpmu_delete_blog( $blog_id, $drop_tables = false );
```

## Network Options (shared across all sites)

```php
get_site_option( $key, $default = false );    // Network-wide option
update_site_option( $key, $value );
delete_site_option( $key );
add_site_option( $key, $value );              // Only adds if not exists
```

## Super Admin

```php
is_super_admin( $user_id = false );    // Checks current user if no ID passed
grant_super_admin( $user_id );
revoke_super_admin( $user_id );
get_super_admins();                    // Returns array of usernames
```

## Network-Activated Plugins

```php
// Network-activated plugins apply to ALL sites
// Stored in wp_sitemeta, key 'active_sitewide_plugins'
$network_plugins = get_site_option( 'active_sitewide_plugins' );
// Format: [ 'plugin-dir/plugin-file.php' => timestamp ]

is_plugin_active_for_network( 'plugin-dir/plugin-file.php' );
```

## Hooks Specific to Multisite

| Hook | Type | When |
|---|---|---|
| `wp_initialize_site` | action | New site created (WP 5.1+) |
| `wp_delete_site` | action | Site deleted |
| `switch_blog` | action | After switch_to_blog() |
| `network_admin_menu` | action | Network admin menu setup |
| `wpmu_new_blog` | action | After new blog created (deprecated, use wp_initialize_site) |

## Gotchas

- **Blog ID 1** is always the main/primary site.
- **Always call `restore_current_blog()`** after `switch_to_blog()`. Failing to do so corrupts global state for subsequent code. Nesting switches is supported but each switch must be restored.
- `$wpdb->prefix` changes after `switch_to_blog()` -- any cached table names become stale.
- User accounts are shared network-wide, but roles/capabilities are per-site (stored in `wp_{N}_capabilities` usermeta).
- `get_option()` and `get_post_meta()` are site-scoped. Use `get_site_option()` for network-wide settings.
- Uploads are separated per site: `wp-content/uploads/sites/{blog_id}/`.
