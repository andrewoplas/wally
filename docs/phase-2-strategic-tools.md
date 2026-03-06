# Phase 2: Strategic New Tools

> These tools unlock entire new categories of functionality. Each one is a single PHP file in `apps/wally/includes/tools/`. No backend changes needed — tools are auto-discovered.

## Priority Order

### 1. `class-menu-tools.php` — Navigation Management
**Unlocks:** Site setup, page building follow-ups ("add this to your nav")

| Tool | Action | Confirmation | Capability |
|------|--------|-------------|------------|
| `list_menus` | read | no | `edit_theme_options` |
| `get_menu_items` | read | no | `edit_theme_options` |
| `create_menu` | create | no | `edit_theme_options` |
| `add_menu_item` | create | no | `edit_theme_options` |
| `update_menu_item` | update | yes | `edit_theme_options` |
| `delete_menu_item` | delete | yes | `edit_theme_options` |
| `set_menu_location` | update | yes | `edit_theme_options` |

**WordPress APIs:**
- `wp_get_nav_menus()` — list all menus
- `wp_get_nav_menu_items($menu_id)` — get items for a menu
- `wp_create_nav_menu($name)` — create menu
- `wp_update_nav_menu_item($menu_id, 0, $args)` — add item (page, custom link, category)
- `wp_update_nav_menu_item($menu_id, $item_id, $args)` — update item
- `wp_delete_post($item_id, true)` — delete item (menu items are posts)
- `set_theme_mod('nav_menu_locations', [...])` — assign menu to location
- `get_registered_nav_menus()` — get available theme locations

**Key `$args` for menu items:**
```php
[
    'menu-item-title'     => 'About Us',
    'menu-item-url'       => '/about',
    'menu-item-object'    => 'page',        // page, post, category, custom
    'menu-item-object-id' => 42,            // post/page ID (for page/post types)
    'menu-item-type'      => 'post_type',   // post_type, taxonomy, custom
    'menu-item-status'    => 'publish',
    'menu-item-parent-id' => 0,             // for nested items
    'menu-item-position'  => 1,             // order
]
```

---

### 2. `class-debug-tools.php` — Debugging & Diagnostics
**Unlocks:** "My site is broken", "why is it slow", error investigation

| Tool | Action | Confirmation | Capability |
|------|--------|-------------|------------|
| `get_error_log` | read | no | `manage_options` |
| `get_site_health_tests` | read | no | `manage_options` |

**`get_error_log` implementation:**
```php
public function execute(array $params): array {
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (!file_exists($log_file)) {
        return ['success' => true, 'data' => ['exists' => false, 'message' => 'No debug.log found. WP_DEBUG_LOG may not be enabled.']];
    }
    $lines = (int)($params['lines'] ?? 100);
    $lines = min($lines, 500); // cap to prevent huge responses
    // Read last N lines efficiently
    $content = file_get_contents($log_file);
    $all_lines = explode("\n", trim($content));
    $last_lines = array_slice($all_lines, -$lines);
    return ['success' => true, 'data' => [
        'exists' => true,
        'file_size' => filesize($log_file),
        'total_lines' => count($all_lines),
        'lines_returned' => count($last_lines),
        'content' => implode("\n", $last_lines),
    ]];
}
```

**Parameters:** `lines` (int, optional, default 100) — number of lines from the end

**`get_site_health_tests` implementation:**
Run WordPress Site Health tests programmatically:
```php
// Load Site Health if not loaded
if (!class_exists('WP_Site_Health')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
}
$health = WP_Site_Health::get_instance();
$tests = WP_Site_Health::get_tests();
// Run direct tests (async tests require JS, skip those)
$results = [];
foreach ($tests['direct'] as $test_key => $test) {
    $result = call_user_func($test['callback']);
    $results[] = $result;
}
```

---

### 3. `class-media-upload-tools.php` — Media Upload from URL
**Unlocks:** Content creation with images, featured images from URLs

| Tool | Action | Confirmation | Capability |
|------|--------|-------------|------------|
| `upload_media_from_url` | create | no | `upload_files` |
| `set_featured_image` | update | no | `edit_posts` |

**`upload_media_from_url` implementation:**
```php
// WordPress has built-in support for this
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$url = $params['url'];
$description = $params['description'] ?? '';

// Download and sideload
$tmp = download_url($url);
if (is_wp_error($tmp)) { return error; }

$file_array = [
    'name'     => basename(parse_url($url, PHP_URL_PATH)),
    'tmp_name' => $tmp,
];

$attachment_id = media_handle_sideload($file_array, 0, $description);
if (is_wp_error($attachment_id)) { @unlink($tmp); return error; }

// Set alt text
if (!empty($params['alt_text'])) {
    update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($params['alt_text']));
}

return ['success' => true, 'data' => [
    'attachment_id' => $attachment_id,
    'url' => wp_get_attachment_url($attachment_id),
]];
```

**`set_featured_image`:** Simply `set_post_thumbnail($post_id, $attachment_id)`

**Parameters:**
- `upload_media_from_url`: `url` (string, required), `alt_text` (string, optional), `description` (string, optional)
- `set_featured_image`: `post_id` (int, required), `attachment_id` (int, required)

---

### 4. `class-customizer-tools.php` — Theme Customization
**Unlocks:** "Change my site colors", "update my logo", "change fonts"

| Tool | Action | Confirmation | Capability |
|------|--------|-------------|------------|
| `get_customizer_settings` | read | no | `edit_theme_options` |
| `update_customizer_setting` | update | yes | `edit_theme_options` |
| `get_theme_mods` | read | no | `edit_theme_options` |

**Implementation approach:**
```php
// Read all theme mods
$mods = get_theme_mods();
// Common settings: custom_logo, background_color, header_textcolor, etc.

// Update a theme mod
set_theme_mod($key, $value);

// For custom_logo specifically:
// set_theme_mod('custom_logo', $attachment_id);
```

**Parameters:**
- `get_customizer_settings`: none (returns all theme mods + registered customizer settings)
- `update_customizer_setting`: `key` (string, required), `value` (mixed, required)
- `get_theme_mods`: none

**Important:** Theme mods vary wildly between themes. The tool should return what's available and let the LLM figure out what to change. Popular theme mods:
- `custom_logo` (attachment ID)
- `background_color`
- `header_textcolor`
- Theme-specific: Astra uses `astra-settings[...]`, Kadence uses `kadence[...]`, etc.

---

### 5. `class-widget-tools.php` — Widget & Sidebar Management
**Unlocks:** "Add a recent posts widget to my sidebar"

| Tool | Action | Confirmation | Capability |
|------|--------|-------------|------------|
| `list_widget_areas` | read | no | `edit_theme_options` |
| `list_widgets` | read | no | `edit_theme_options` |
| `add_widget` | create | no | `edit_theme_options` |
| `remove_widget` | delete | yes | `edit_theme_options` |

**Note:** Only relevant for classic themes. Block themes use template parts instead. The `can_register()` method should check if the theme uses widgets:
```php
public static function can_register(): bool {
    // Block themes don't use traditional widgets
    return !wp_is_block_theme();
}
```

---

### 6. `class-user-tools.php` — User Management
**Unlocks:** "Create an editor account for my assistant", "list all admins"

| Tool | Action | Confirmation | Capability |
|------|--------|-------------|------------|
| `list_users` | read | no | `list_users` |
| `get_user` | read | no | `list_users` |
| `create_user` | create | yes | `create_users` |
| `update_user_role` | update | yes | `promote_users` |
| `delete_user` | delete | yes | `delete_users` |

---

### 7. `class-comment-tools.php` — Comment Management
**Unlocks:** "Show me pending comments", "approve all comments", "delete spam"

| Tool | Action | Confirmation | Capability |
|------|--------|-------------|------------|
| `list_comments` | read | no | `moderate_comments` |
| `approve_comment` | update | no | `moderate_comments` |
| `trash_comment` | delete | yes | `moderate_comments` |
| `spam_comment` | update | no | `moderate_comments` |
| `bulk_moderate_comments` | update | yes | `moderate_comments` |

---

## Implementation Notes

- Each tool file follows the existing pattern in `class-tool-interface.php`
- Auto-discovered by `class-plugin.php` — no registration code needed
- Conditional tools use `can_register()` (e.g., widget tools only for classic themes)
- Destructive actions set `requires_confirmation()` to `true`
- All tools return `['success' => true/false, 'data' => [...]]`
- Priority: menu tools + debug tools first (highest impact), then media, customizer, widgets, users, comments
