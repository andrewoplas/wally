# Theme Extension Plugins

## When to Use
- User mentions Astra Pro, GeneratePress Premium/Pro, Kadence Pro, or theme pro addons
- User asks about theme settings, custom layouts, sticky headers, or theme modules
- User wants to check or change theme extension settings

## Available Tools
- `list_plugins` — detect which theme extension is active
- `get_option` — read theme and pro settings
- `update_option` — change theme settings (requires confirmation)
- `get_site_info` — check which theme is active
- `list_posts` — list custom layout elements (Astra hooks, GP elements, Kadence elements)
- `get_post` — get custom layout details

## Workflows

### Detect Active Theme Extension
1. Call `get_site_info` to check active theme (Astra, GeneratePress, or Kadence)
2. Call `list_plugins` to check for pro addon: `astra-addon`, `gp-premium` or `generatepress-pro`, `kadence-pro`

### Astra Pro — Read Settings
1. Call `get_option` with key `astra-settings` — shared between free Astra and Astra Pro
2. Call `get_option` with key `astra-addon-modules` — enabled/disabled Pro modules

### Astra Pro — List Custom Layouts
1. Call `list_posts` with `post_type: 'astra-advanced-hook'`
2. Each layout has display rules and hook location in postmeta

### GeneratePress Pro — Read Settings
1. Call `get_option` with key `generate_settings` — shared between free GP and GP Premium
2. Module state: `get_option` with keys like `generate_package_colors`, `generate_package_typography`

### GeneratePress Pro — List Elements
1. Call `list_posts` with `post_type: 'gp_elements'`
2. Element types: hook, layout, header, block — stored in `_generate_element_type` meta

### Kadence Pro — Read Settings
1. Call `get_option` with key `kadence_theme_options` — shared between free Kadence and Kadence Pro
2. Call `get_option` with key `kadence_pro_activated_addons` — enabled Pro modules

### Kadence Pro — List Hooked Elements
1. Call `list_posts` with `post_type: 'kadence_element'`
2. Element types: code, fixed, replace_header, replace_footer — stored in `_kad_element_type` meta

### Check Enabled Modules
1. For Astra Pro: `get_option` with key `astra-addon-modules`
2. For GP Pro: `get_option` with keys `generate_package_*`
3. For Kadence Pro: `get_option` with key `kadence_pro_activated_addons`

## Important Notes
- All three themes store settings in a SINGLE wp_options key shared between free theme and pro plugin
- Updating settings via `update_option` replaces the entire serialized array — read first, merge changes, then write
- After changing settings programmatically, clear CSS cache transients:
  - Astra: `astra-addon-dynamic-css`
  - GeneratePress: `generate_dynamic_css_output`
  - Kadence: `kadence_dynamic_css`
- Custom layouts/elements have conditional display rules in postmeta — not easily editable via `update_post`
- For Customizer settings, header/footer builder, or module configuration, guide user to Appearance > Customize
- Each extension has a "hooked elements" system for injecting content at theme hook locations — these are custom post types listable via `list_posts`
