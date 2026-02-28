## Users & Roles

### Default Roles & Capabilities
- **Administrator**: Full access — manage_options, install_plugins, edit_theme_options, manage_categories, edit_others_posts, delete_others_posts, create_users, edit_users, delete_users, list_users, promote_users, etc.
- **Editor**: Content management — edit_others_posts, delete_others_posts, manage_categories, edit_published_posts, publish_posts, moderate_comments, upload_files, edit_pages, edit_others_pages, publish_pages, delete_pages
- **Author**: Own content — edit_posts, edit_published_posts, publish_posts, delete_posts, delete_published_posts, upload_files
- **Contributor**: Submit for review — edit_posts, delete_posts (own only, unpublished only), read
- **Subscriber**: Read only — read

### User Data (Database)
- **wp_users** table: ID, user_login, user_pass, user_nicename, user_email, user_url, user_registered, user_activation_key, user_status, display_name
- **wp_usermeta** table: umeta_id, user_id, meta_key, meta_value (key/value pairs per user)

---

### User CRUD

#### wp_create_user() — Simple User Creation
```php
wp_create_user( string $username, string $password, string $email = '' ): int|WP_Error
```
- **$username** (string, required): The user's login name
- **$password** (string, required): Plain-text password (hashed internally)
- **$email** (string, optional): The user's email address
- **Returns**: New user ID on success, WP_Error on failure
- Internally calls `wp_insert_user()` — use that for more fields

#### wp_insert_user() — Full User Creation/Update
```php
wp_insert_user( array|object|WP_User $userdata ): int|WP_Error
```
Creates a new user or updates existing (when `ID` is supplied). All `$userdata` fields:

| Field | Type | Description |
|-------|------|-------------|
| `ID` | int | If supplied, updates existing user instead of creating |
| `user_login` | string | Login username (required for new users, max 60 chars) |
| `user_pass` | string | Plain-text password (required for new users; hashed internally) |
| `user_email` | string | Email address (must be unique) |
| `user_nicename` | string | URL-friendly name (defaults to sanitized login, max 50 chars) |
| `user_url` | string | Website URL (max 100 chars) |
| `display_name` | string | Public display name (defaults to username) |
| `first_name` | string | First name |
| `last_name` | string | Last name |
| `nickname` | string | Nickname (defaults to username) |
| `description` | string | Biographical info |
| `role` | string | User role (e.g., 'editor', 'subscriber') |
| `rich_editing` | string | Enable visual editor: 'true' or 'false' (default: 'true') |
| `syntax_highlighting` | string | Enable code editor: 'true' or 'false' (default: 'true') |
| `comment_shortcuts` | string | Keyboard shortcuts: 'true' or 'false' (default: 'false') |
| `admin_color` | string | Admin color scheme (default: 'fresh') |
| `use_ssl` | bool | Force HTTPS on admin (default: false) |
| `user_registered` | string | Registration date in UTC ('Y-m-d H:i:s') |
| `user_activation_key` | string | Password reset key |
| `show_admin_bar_front` | string | Show admin bar: 'true' or 'false' (default: 'true') |
| `locale` | string | User locale preference (empty = site default) |
| `spam` | bool | Mark as spam (Multisite only) |
| `meta_input` | array | Custom user meta as key => value pairs |

- **Returns**: User ID on success, WP_Error on failure
- Email and username must be unique
- Filters available: `pre_user_{field}` for each field

#### wp_update_user() — Update Existing User
```php
wp_update_user( array|object|WP_User $userdata ): int|WP_Error
```
- Accepts same `$userdata` as `wp_insert_user()` but `ID` is required
- Only specify fields you want to change — merges with existing data
- Password changes trigger `wp_set_password` action and clear auth cookies for current user
- Sends notification emails on password or email change (filterable via `send_password_change_email`, `send_email_change_email`)
- **Returns**: Updated user ID on success, WP_Error on failure

#### wp_delete_user() — Delete User
```php
wp_delete_user( int $id, int $reassign = null ): bool
```
- **$id** (int, required): User ID to delete
- **$reassign** (int, optional): User ID to reassign posts/links to. If null, all user content is deleted
- **Returns**: true on success
- Must include `wp-admin/includes/user.php` before calling
- On Multisite, only removes user from current site (not entire network)
- Automatically deletes user metadata
- Fires `delete_user` (before) and `deleted_user` (after) hooks

---

### WP_User Class

#### Getting a User Object
```php
get_user_by( 'email'|'login'|'id'|'slug', $value )  // returns WP_User|false
get_userdata( $user_id )                               // returns WP_User|false
wp_get_current_user()                                  // returns WP_User (ID=0 if not logged in)
```

#### Key Properties
- `$user->ID` — User ID
- `$user->user_login` — Login name
- `$user->user_email` — Email address
- `$user->display_name` — Public display name
- `$user->user_registered` — Registration timestamp
- `$user->roles` — Array of role slugs (e.g., ['administrator'])
- `$user->caps` — Array of role => bool
- `$user->allcaps` — Array of all capabilities (capability => bool)

#### Key Methods
- `has_cap( $capability )` — Check if user has a specific capability
- `add_role( $role )` — Add a role to the user (users can have multiple roles)
- `remove_role( $role )` — Remove a role from the user
- `set_role( $role )` — Replace all roles with a single role
- `add_cap( $capability, $grant = true )` — Grant a capability directly to the user
- `remove_cap( $capability )` — Remove a directly granted capability
- `get( $key )` — Get user meta value
- `has_prop( $key )` — Check if a property or meta key exists

---

### WP_User_Query — Complex User Queries

```php
$query = new WP_User_Query( $args );
$users = $query->get_results();   // array of WP_User objects (or IDs/other based on 'fields')
$total = $query->get_total();     // total matching users (when count_total is true)
```

#### All Query Arguments

**Role Filtering:**
| Arg | Type | Description |
|-----|------|-------------|
| `role` | string/array | Filter by role(s) — user must have ALL specified roles |
| `role__in` | array | User must have at least ONE of these roles |
| `role__not_in` | array | Exclude users with any of these roles |

**User Selection:**
| Arg | Type | Description |
|-----|------|-------------|
| `include` | array | Only return these user IDs |
| `exclude` | array | Exclude these user IDs |

**Search:**
| Arg | Type | Description |
|-----|------|-------------|
| `search` | string | Search keyword (use `*` wildcards, e.g., `'*john*'`) |
| `search_columns` | array | Columns to search: `ID`, `user_login`, `user_nicename`, `user_email`, `user_url` |

**Pagination:**
| Arg | Type | Description |
|-----|------|-------------|
| `number` | int | Max results to return (-1 for all) |
| `offset` | int | Number of results to skip |
| `paged` | int | Page number (used with `number`) |
| `count_total` | bool | Calculate total results for pagination (default: true) |

**Sorting:**
| Arg | Type | Description |
|-----|------|-------------|
| `orderby` | string/array | Sort field: `ID`, `display_name`, `login`, `nicename`, `email`, `url`, `registered`, `post_count`, `meta_value`, `meta_value_num`, `include` |
| `order` | string | `ASC` or `DESC` (default: `ASC`) |

**Meta (Custom Fields):**
| Arg | Type | Description |
|-----|------|-------------|
| `meta_key` | string | Custom field key |
| `meta_value` | string | Custom field value |
| `meta_compare` | string | Operator: `=`, `!=`, `>`, `>=`, `<`, `<=`, `LIKE`, `NOT LIKE`, `IN`, `NOT IN`, `BETWEEN`, `NOT BETWEEN`, `EXISTS`, `NOT EXISTS` |
| `meta_query` | array | Complex meta queries (same structure as WP_Meta_Query) |

**Additional Filters:**
| Arg | Type | Description |
|-----|------|-------------|
| `blog_id` | int | Blog ID for Multisite |
| `who` | string | `'authors'` to limit to users who can publish |
| `has_published_posts` | bool/array | Filter by published posts (true or array of post types) |
| `nicename` | string | Filter by exact nicename |
| `nicename__in` | array | Filter by multiple nicenames |
| `nicename__not_in` | array | Exclude nicenames |
| `login` | string | Filter by exact login |
| `login__in` | array | Filter by multiple logins |
| `login__not_in` | array | Exclude logins |
| `fields` | string/array | Return format: `'all'` (WP_User objects), `'ID'`, `'display_name'`, `'user_login'`, `'user_nicename'`, `'user_email'`, `'user_url'`, `'user_registered'`, or array of columns |
| `date_query` | array | Filter by registration date (same structure as WP_Date_Query) |

#### Helper Functions
- `count_users()` — Returns array with `'total_users'` count and per-role counts in `'avail_roles'`
- `get_users( $args )` — Wrapper around WP_User_Query, returns array of WP_User objects

---

### Roles & Capabilities

#### Managing Roles
```php
// Add a new role (only run on activation — stored in DB)
add_role( string $role, string $display_name, bool[] $capabilities = [] ): WP_Role|void
// Returns WP_Role on success, void if role already exists

// Remove a role
remove_role( string $role )

// Get a role object
get_role( string $role ): WP_Role|null
// WP_Role properties: $name (slug), $capabilities (array)
```

**Best practices for add_role():**
- Only call during plugin/theme activation (`register_activation_hook`)
- Role data is stored in the database (wp_options), not recalculated per request
- If role already exists, `add_role()` silently does nothing
- Capabilities can be associative (`['read' => true, 'edit_posts' => false]`) or indexed (`['read', 'edit_posts']`)
- To clone a role: `get_role('editor')->capabilities` as starting point

#### Managing Capabilities on Roles
```php
$role = get_role( 'editor' );
$role->add_cap( 'edit_custom_thing' );      // Grant capability to role
$role->remove_cap( 'edit_custom_thing' );   // Remove capability from role
$role->has_cap( 'edit_custom_thing' );      // Check if role has capability
```

#### Checking Capabilities
```php
current_user_can( $capability )              // Check current logged-in user
current_user_can( 'edit_post', $post_id )    // Meta capability with object ID
user_can( $user_id, $capability )            // Check specific user
```

**Principle of least privilege**: Always check capabilities, not roles. Check the most specific capability needed for the action.

#### map_meta_cap()
Maps meta capabilities to primitive capabilities. For example:
- `edit_post` (meta) checks ownership and maps to `edit_posts` or `edit_others_posts`
- `delete_post` (meta) maps to `delete_posts` or `delete_others_posts`
- `read_post` (meta) checks post status and visibility

---

### User Meta

```php
// Get meta
get_user_meta( $user_id, $key, $single = false )
// $single = true: returns single value; false: returns array of values

// Update meta (creates if not exists)
update_user_meta( $user_id, $key, $value, $prev_value = '' )

// Add meta (allows duplicate keys)
add_user_meta( $user_id, $key, $value, $unique = false )

// Delete meta
delete_user_meta( $user_id, $key, $value = '' )
// If $value specified, only deletes matching value
```

#### Common Meta Keys
| Key | Description |
|-----|-------------|
| `nickname` | User nickname |
| `first_name` | First name |
| `last_name` | Last name |
| `description` | Bio/description |
| `wp_capabilities` | Serialized roles array |
| `wp_user_level` | Numeric user level (deprecated, 0-10) |
| `rich_editing` | Visual editor enabled |
| `syntax_highlighting` | Code editor highlighting |
| `show_admin_bar_front` | Show admin bar on frontend |
| `locale` | User language preference |
| `dismissed_wp_pointers` | Dismissed admin pointers |
| `session_tokens` | Active login sessions |

---

### Hooks

#### User Lifecycle
| Hook | Type | When Fired |
|------|------|------------|
| `user_register` | action | After a new user is created (receives $user_id, $userdata) |
| `profile_update` | action | After a user is updated (receives $user_id, $old_user_data, $userdata) |
| `delete_user` | action | Before a user is deleted (receives $user_id, $reassign, $user) |
| `deleted_user` | action | After a user is deleted (receives $user_id, $reassign, $user) |
| `wp_login` | action | After successful login (receives $user_login, $user) |
| `wp_logout` | action | After user logs out (receives $user_id) |

#### Role & Capability
| Hook | Type | When Fired |
|------|------|------------|
| `set_user_role` | action | When user role is changed (receives $user_id, $role, $old_roles) |
| `add_user_role` | action | When a role is added to a user |
| `remove_user_role` | action | When a role is removed from a user |
| `user_has_cap` | filter | Filters user capabilities at check time |

#### User Data Filters
| Hook | Type | Description |
|------|------|-------------|
| `pre_user_login` | filter | Filter login before insert |
| `pre_user_email` | filter | Filter email before insert |
| `pre_user_nicename` | filter | Filter nicename before insert |
| `pre_user_display_name` | filter | Filter display name before insert |
| `pre_user_description` | filter | Filter description before insert |
| `send_password_change_email` | filter | Whether to send password change email |
| `send_email_change_email` | filter | Whether to send email change notification |
| `insert_user_meta` | filter | Filter user meta before insert |

#### Security Best Practices
- Always check capabilities before performing user operations
- Use `wp_nonce_field()` / `wp_verify_nonce()` for form submissions
- Validate and sanitize all user input before passing to user functions
- Follow the principle of least privilege — grant only necessary capabilities
- Create custom roles on activation only, not on every page load
- Use capability checks (`current_user_can`) rather than role checks
