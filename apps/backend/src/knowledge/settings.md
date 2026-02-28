## WordPress Settings & Options

### Options API

The Options API stores key-value pairs in the `wp_options` table. Options can hold any serializable data type (strings, arrays, objects — but not resources).

---

### Options CRUD

#### get_option() — Read an Option
```php
get_option( string $option, mixed $default_value = false ): mixed
```
- **$option** (string, required): Option name (not SQL-escaped)
- **$default_value** (mixed, optional): Value returned if option doesn't exist (default: `false`)
- **Returns**: The option value (strings for scalar DB values), or `$default_value` if not found

**Lookup order** (cache hierarchy):
1. `alloptions` cache (for autoloaded options)
2. `notoptions` cache (known non-existent options)
3. Individual `options` cache
4. Database query

**Type coercion on storage/retrieval:**
- `false` is stored as `''` (empty string)
- `true` is stored as `'1'`
- `null` is stored as `''`
- Integers stored as string equivalents
- Arrays/objects are serialized and unserialized automatically

**Filters:**
- `pre_option_{$option}` — Short-circuit before any lookup (return non-false to skip DB)
- `default_option_{$option}` — Filter the default value when option doesn't exist
- `option_{$option}` — Filter the retrieved value

**Best practice**: Always set a meaningful `$default` — relying on `false` to mean "not set" triggers unnecessary DB queries.

#### update_option() — Create or Update an Option
```php
update_option( string $option, mixed $value, bool|null $autoload = null ): bool
```
- **$option** (string, required): Option name
- **$value** (mixed, required): New value (serialized automatically if needed)
- **$autoload** (bool|null, optional): `true` = autoload, `false` = don't autoload, `null` = preserve existing or use WP default
- **Returns**: `true` if value was updated, `false` if value unchanged or update failed
- If the option doesn't exist, it is created via `add_option()` internally
- Returns `false` when new value is identical to old value (no DB write occurs)
- The `$autoload` flag can only be changed when `$value` also changes
- Deprecated string values `'yes'` / `'no'` still accepted for backward compatibility

**Hooks:**
- `pre_update_option_{$option}` — Filter value before saving
- `pre_update_option` — Generic filter for all option updates
- `update_option` — Action fired before DB update (receives $option, $old_value, $value)
- `update_option_{$option}` — Action fired after successful update (receives $old_value, $value, $option)
- `updated_option` — Generic action after any option update

#### add_option() — Create a New Option Only
```php
add_option( string $option, mixed $value = '', string $deprecated = '', bool|null $autoload = null ): bool
```
- **$option** (string, required): Option name
- **$value** (mixed, optional): Option value (default: empty string)
- **$deprecated** (string): No longer used, kept for backward compatibility
- **$autoload** (bool|null, optional): Same as update_option
- **Returns**: `true` if added, `false` if option already exists
- **Key difference from update_option**: Fails silently if option already exists (does NOT overwrite)
- Use this when you want to set a default only if no value exists yet

**Hooks:**
- `add_option` — Action before adding
- `add_option_{$option}` — Action after adding
- `added_option` — Generic action after any option is added

#### delete_option() — Remove an Option
```php
delete_option( string $option ): bool
```
- **$option** (string, required): Option name
- **Returns**: `true` if deleted, `false` if option didn't exist
- Protected WordPress core options cannot be deleted (`wp_protect_special_option()`)
- Handles cache invalidation automatically

**Hooks:**
- `delete_option` — Action before deletion
- `delete_option_{$option}` — Action after specific option is deleted
- `deleted_option` — Generic action after any option is deleted

---

### Autoload

Options with autoload enabled are loaded in a single DB query on every WordPress page load and cached in the `alloptions` object cache.

**When to autoload (`true` / formerly `'yes'`):**
- Options read on every page load (site title, permalink structure, active plugins)
- Small values needed frequently

**When NOT to autoload (`false` / formerly `'no'`):**
- Options only used on specific admin pages
- Large serialized data (e.g., widget configurations, plugin settings)
- Rarely accessed options

**Impact**: Every autoloaded option adds to the `alloptions` query payload. Excessive autoloading increases memory usage on every request.

**Since WP 6.6**: WordPress uses internal heuristics (`wp_determine_option_autoload_value()`) when autoload is `null`, and tracks autoload metadata via `wp_autoload_meta`.

---

### Common Built-in WordPress Options

#### General Settings
| Option | Description | Example Value |
|--------|-------------|---------------|
| `blogname` | Site title | `'My WordPress Site'` |
| `blogdescription` | Tagline | `'Just another WordPress site'` |
| `siteurl` | WordPress URL (where WP core files live) | `'https://example.com'` |
| `home` | Site URL (public-facing address) | `'https://example.com'` |
| `admin_email` | Admin email address | `'admin@example.com'` |
| `users_can_register` | Open registration | `'0'` or `'1'` |
| `default_role` | Default role for new users | `'subscriber'` |

#### Reading Settings
| Option | Description | Example Value |
|--------|-------------|---------------|
| `posts_per_page` | Blog posts per page | `'10'` |
| `show_on_front` | Front page displays | `'posts'` or `'page'` |
| `page_on_front` | Static front page ID | `'42'` (0 = latest posts) |
| `page_for_posts` | Blog page ID | `'15'` |
| `blog_public` | Search engine visibility | `'1'` (visible) or `'0'` (discouraged) |

#### Writing Settings
| Option | Description | Example Value |
|--------|-------------|---------------|
| `default_category` | Default post category ID | `'1'` |
| `default_post_format` | Default post format | `''` (standard) |
| `default_comment_status` | Comments on new posts | `'open'` or `'closed'` |
| `default_ping_status` | Pingbacks on new posts | `'open'` or `'closed'` |

#### Date & Time
| Option | Description | Example Value |
|--------|-------------|---------------|
| `date_format` | Date display format | `'F j, Y'` |
| `time_format` | Time display format | `'g:i a'` |
| `timezone_string` | Timezone identifier | `'America/New_York'` |
| `gmt_offset` | UTC offset (numeric) | `'-5'` |
| `start_of_week` | Week start day (0=Sun) | `'1'` (Monday) |

#### Permalinks
| Option | Description | Example Value |
|--------|-------------|---------------|
| `permalink_structure` | URL pattern | `'/%postname%/'` |
| `category_base` | Category URL prefix | `''` (default) |
| `tag_base` | Tag URL prefix | `''` (default) |

#### Media
| Option | Description | Example Value |
|--------|-------------|---------------|
| `thumbnail_size_w` | Thumbnail width | `'150'` |
| `thumbnail_size_h` | Thumbnail height | `'150'` |
| `thumbnail_crop` | Crop thumbnails | `'1'` |
| `medium_size_w` | Medium width | `'300'` |
| `medium_size_h` | Medium height | `'300'` |
| `large_size_w` | Large width | `'1024'` |
| `large_size_h` | Large height | `'1024'` |
| `uploads_use_yearmonth_folders` | Organize by date | `'1'` |

#### System / Internal
| Option | Description | Example Value |
|--------|-------------|---------------|
| `active_plugins` | Active plugin paths (serialized array) | `['plugin-dir/plugin.php']` |
| `template` | Active theme directory name | `'twentytwentyfour'` |
| `stylesheet` | Active child theme directory | `'my-child-theme'` |
| `current_theme` | Active theme display name | `'Twenty Twenty-Four'` |
| `db_version` | Database schema version | `'57155'` |
| `wp_version` | WordPress version (from code, not DB) | — |
| `initial_db_version` | DB version at install time | `'57155'` |
| `WPLANG` | Site language | `''` (English) or `'fr_FR'` |
| `cron` | Scheduled cron events (serialized) | — |
| `sidebars_widgets` | Widget-to-sidebar assignments | — |
| `widget_{id_base}` | Widget instance settings | — |

---

### Settings API

The Settings API provides a standardized framework for creating admin settings pages. It handles form submission to `wp-admin/options.php`, nonce verification, capability checks (`manage_options`), and data sanitization automatically.

#### Step 1: Register Settings

```php
register_setting( string $option_group, string $option_name, array $args = [] )
```
- **$option_group** (string, required): Settings group name. Must match the group used in `settings_fields()`. Built-in groups: `'general'`, `'discussion'`, `'media'`, `'reading'`, `'writing'`, `'options'`
- **$option_name** (string, required): The option key stored in wp_options
- **$args** (array, optional):

| Key | Type | Description |
|-----|------|-------------|
| `type` | string | Data type: `'string'`, `'boolean'`, `'integer'`, `'number'`, `'array'`, `'object'` |
| `description` | string | Description of the setting |
| `sanitize_callback` | callable | Function to sanitize value before saving |
| `show_in_rest` | bool/array | Expose in REST API. Array with `'schema'` key for complex types |
| `default` | mixed | Default value returned by `get_option()` |
| `label` | string | Descriptive label (since WP 6.6) |

To remove: `unregister_setting( $option_group, $option_name )`

#### Step 2: Add Settings Sections

```php
add_settings_section( string $id, string $title, callable $callback, string $page, array $args = [] )
```
- **$id** (string): Slug identifier for the section
- **$title** (string): Section heading displayed on page
- **$callback** (callable): Function that outputs content above the section's fields (can echo nothing)
- **$page** (string): Settings page slug (`'general'`, `'reading'`, `'writing'`, `'discussion'`, `'media'`, or custom page slug)
- **$args** (array, optional):
  - `before_section` — HTML before section (`%s` = section class)
  - `after_section` — HTML after section
  - `section_class` — CSS class for the section

#### Step 3: Add Settings Fields

```php
add_settings_field( string $id, string $title, callable $callback, string $page, string $section = 'default', array $args = [] )
```
- **$id** (string): Unique slug for the field (used in HTML attributes)
- **$title** (string): Label displayed for the field
- **$callback** (callable): Function that echoes the HTML input. The input's `name` attribute must match the option registered in `register_setting()`
- **$page** (string): Settings page slug (must match `add_settings_section()`)
- **$section** (string, optional): Section ID to attach to (default: `'default'`)
- **$args** (array, optional):
  - `label_for` — Sets the `for` attribute on the `<label>` wrapping the title
  - `class` — CSS class for the `<tr>` element

#### Step 4: Render the Settings Page

```php
function my_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'my_option_group' );       // Nonce, action, option_page hidden fields
            do_settings_sections( 'my-settings-page' );  // Renders all sections & fields for this page
            submit_button();                              // Save button
            ?>
        </form>
    </div>
    <?php
}
```

#### Rendering Functions
| Function | Purpose |
|----------|---------|
| `settings_fields( $option_group )` | Outputs nonce, action, and option_page hidden fields |
| `do_settings_sections( $page )` | Renders all sections and their fields for a page |
| `do_settings_fields( $page, $section )` | Renders fields for a specific section |
| `submit_button( $text, $type, $name, $wrap, $other_attributes )` | Outputs the submit button |

#### Error Handling
| Function | Purpose |
|----------|---------|
| `add_settings_error( $setting, $code, $message, $type )` | Register an error/success message |
| `get_settings_errors( $setting )` | Retrieve registered messages |
| `settings_errors( $setting, $sanitize, $hide_on_update )` | Display messages on the page |

---

### Full Settings API Pattern Example

```php
// 1. Register the admin menu page
add_action( 'admin_menu', function() {
    add_options_page(
        'My Plugin Settings',     // Page title
        'My Plugin',              // Menu title
        'manage_options',         // Required capability
        'my-plugin-settings',     // Menu slug
        'my_plugin_render_page'   // Render callback
    );
});

// 2. Register settings, sections, and fields
add_action( 'admin_init', function() {
    register_setting( 'my_plugin_group', 'my_plugin_option', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ]);

    add_settings_section(
        'my_plugin_main_section',
        'Main Settings',
        function() { echo '<p>Configure the main plugin settings below.</p>'; },
        'my-plugin-settings'
    );

    add_settings_field(
        'my_plugin_field',
        'API Key',
        function() {
            $value = get_option( 'my_plugin_option', '' );
            echo '<input type="text" name="my_plugin_option" value="' . esc_attr( $value ) . '" class="regular-text">';
        },
        'my-plugin-settings',
        'my_plugin_main_section',
        [ 'label_for' => 'my_plugin_option' ]
    );
});

// 3. Render the page
function my_plugin_render_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <?php settings_errors(); ?>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'my_plugin_group' );
            do_settings_sections( 'my-plugin-settings' );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}
```

---

### Theme Mods

Theme-specific settings stored as a serialized array in option `theme_mods_{theme_slug}`:

```php
get_theme_mod( $name, $default )      // Read a theme mod
set_theme_mod( $name, $value )        // Set a theme mod
remove_theme_mod( $name )             // Remove a single theme mod
remove_theme_mods()                   // Remove ALL theme mods for active theme
```

Theme mods are scoped to the active theme — switching themes uses a different set of values.

---

### Transients — Cached Options with Expiration

For expensive operations, use transients instead of plain options. With an object cache (Redis/Memcached), transients bypass the database entirely.

```php
set_transient( $key, $value, $expiration )     // $expiration in seconds (0 = no expiry)
get_transient( $key )                          // Returns value or false if expired/missing
delete_transient( $key )                       // Remove manually

// Multisite equivalents
set_site_transient( $key, $value, $expiration )
get_site_transient( $key )
delete_site_transient( $key )
```

**Expiration constants**: `MINUTE_IN_SECONDS`, `HOUR_IN_SECONDS`, `DAY_IN_SECONDS`, `WEEK_IN_SECONDS`, `MONTH_IN_SECONDS`, `YEAR_IN_SECONDS`

**Storage**: Without an external object cache, transients are stored in `wp_options` with keys `_transient_{name}` and `_transient_timeout_{name}`. With an object cache, they are stored entirely in the cache backend.

---

### Hooks Reference

#### Option Lifecycle Hooks (Dynamic — `{$option}` = option name)

| Hook | Type | When Fired | Parameters |
|------|------|------------|------------|
| `pre_option_{$option}` | filter | Before get_option lookup | `$default_value, $option, $passed_default` |
| `default_option_{$option}` | filter | When option doesn't exist | `$default, $option, $passed_default` |
| `option_{$option}` | filter | After get_option retrieval | `$value, $option` |
| `pre_update_option_{$option}` | filter | Before update_option saves | `$value, $old_value, $option` |
| `pre_update_option` | filter | Generic pre-update for all options | `$value, $option, $old_value` |
| `update_option` | action | Before DB update | `$option, $old_value, $value` |
| `update_option_{$option}` | action | After successful update | `$old_value, $value, $option` |
| `updated_option` | action | After any option update | `$option, $old_value, $value` |
| `add_option` | action | Before adding new option | `$option, $value` |
| `add_option_{$option}` | action | After specific option added | `$option, $value` |
| `added_option` | action | After any option added | `$option, $value` |
| `delete_option` | action | Before deletion | `$option` |
| `delete_option_{$option}` | action | After specific option deleted | `$option` |
| `deleted_option` | action | After any option deleted | `$option` |

#### Widget Settings
Widgets store settings in options named `widget_{widget_id_base}`. Format: serialized array indexed by widget instance number.
