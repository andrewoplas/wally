# Plugins

## When to Use
- User wants to list, install, activate, deactivate, update, or delete plugins
- User asks what plugins are installed or currently active
- User asks whether a specific plugin is installed or has updates available

## Available Tools
- `list_plugins` — list all installed plugins (name, status, version, update_available)
- `install_plugin` — install from WordPress.org by slug (requires confirmation)
- `activate_plugin` — activate an installed plugin by slug (requires confirmation)
- `deactivate_plugin` — deactivate a plugin by slug (requires confirmation)
- `update_plugin` — update a plugin to latest version by slug (requires confirmation)
- `delete_plugin` — delete a deactivated plugin by slug (requires confirmation)

## Workflows

### List All Plugins
1. Call `list_plugins`
2. Returns name, active/inactive status, version, and whether an update is available

### Check if a Plugin is Active
1. Call `list_plugins`
2. Find the plugin by name and check its `status` field

### Install and Activate a Plugin
1. Call `install_plugin` with `slug: '<plugin-slug>'` (requires confirmation)
2. After install, call `activate_plugin` with `slug: '<plugin-slug>'` (requires confirmation)

Common slugs: `woocommerce`, `contact-form-7`, `elementor`, `wordpress-seo`, `advanced-custom-fields`, `wordfence`, `wp-super-cache`, `really-simple-ssl`

### Deactivate a Plugin
1. Call `list_plugins` to confirm the plugin slug and that it is active
2. Call `deactivate_plugin` with `slug: '<plugin-slug>'` (requires confirmation)

### Update a Plugin
1. Call `list_plugins` to check which plugins have `update_available: true`
2. Call `update_plugin` with `slug: '<plugin-slug>'` (requires confirmation)

### Update All Plugins with Updates Available
1. Call `list_plugins` to get the full list
2. For each plugin with `update_available: true`, call `update_plugin` with its slug (each requires confirmation)

### Delete a Plugin
1. If plugin is active, call `deactivate_plugin` first (requires confirmation)
2. Call `delete_plugin` with `slug: '<plugin-slug>'` (requires confirmation)
3. Warn the user: deletion removes plugin files and may erase settings/data

## Important Notes
- All plugin actions require user confirmation before executing
- Plugins must be deactivated before they can be deleted
- `install_plugin` only works for plugins on WordPress.org — premium plugins must be uploaded manually via Plugins > Add New
- Plugin slugs are lowercase with hyphens (e.g., `contact-form-7`, not `Contact Form 7`)
- Must-use plugins (in mu-plugins/) are always active and cannot be managed via these tools
