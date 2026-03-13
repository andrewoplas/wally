# Elementor Addons

## When to Use
- User asks about Elementor addon plugins (Essential Addons, ElementsKit, Premium Addons, Header Footer Elementor)
- User wants to check if an Elementor addon is installed or active
- User wants to check addon widget settings or enable/disable state
- User wants to install an Elementor addon plugin

## Available Tools
- `list_plugins` — check if an addon is installed and active
- `get_option` — read addon settings (widget toggles, API keys)
- `update_option` — change addon settings (requires confirmation)
- `install_plugin` — install an addon from WordPress.org (requires confirmation)
- `activate_plugin` — activate an installed addon (requires confirmation)
- `elementor_clear_css_cache` — clear Elementor CSS cache after activating new addons

## Workflows

### Check if an Elementor Addon is Active
1. Call `list_plugins`
2. Look for these common slugs:
   - Essential Addons: `essential-addons-for-elementor-lite`
   - ElementsKit: `elementskit-lite`
   - Premium Addons: `premium-addons-for-elementor`
   - Header Footer Elementor: `header-footer-elementor`

### Check Essential Addons Widget Settings
1. Call `get_option` with key `eael_save_settings`
2. Returns serialized array — each widget slug maps to `1` (enabled) or absent (disabled)

### Check ElementsKit Settings
1. Call `get_option` with key `elementskit_options`
2. Returns widget and module enable/disable configuration

### Check Premium Addons Settings
1. Call `get_option` with key `pa_save_settings`
2. Returns per-widget enabled/disabled state (widget slug → `1`)

### Install and Activate an Elementor Addon
1. Call `install_plugin` with the plugin slug (requires confirmation)
2. Call `activate_plugin` with the slug (requires confirmation)
3. Call `elementor_clear_css_cache` so Elementor picks up new widget styles

## Important Notes
- Wally can read addon settings but cannot toggle individual widgets via the Elementor editor UI — guide user to the addon's admin settings page (e.g., Essential Addons > Elements) to enable/disable widgets
- All Elementor addons require core Elementor to be installed and active
- Header Footer Elementor templates are stored as custom pages — edit them via Elementor like any page
- Premium plugins (Essential Addons Pro, ElementsKit Pro) must be uploaded manually — `install_plugin` only works for WordPress.org plugins
- After activating a new addon, always call `elementor_clear_css_cache` to register new widget styles
