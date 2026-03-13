# Theme & Appearance

## When to Use
- User asks about themes, templates, child themes, or theme customization
- User wants to know about the active theme, template hierarchy, or theme settings
- User asks about Customizer settings, theme mods, or block themes
- User mentions sidebars, widgets, or enqueuing assets

## Available Tools
- `get_site_info` — check active theme, WP version, and site settings
- `get_option` — read theme mods and Customizer settings
- `update_option` — change theme settings (requires confirmation)
- `list_posts` — list pages (to check which template they use)
- `get_post` — get page details including template assignment
- `search_content` — search across page/post content

## Workflows

### Check Active Theme
1. Call `get_site_info`
2. Returns the active theme name, version, and whether it's a child theme

### Read Theme Customizer Settings
1. Call `get_option` with key `theme_mods_{theme_slug}` (e.g., `theme_mods_astra`, `theme_mods_kadence`)
2. Common mods: `custom_logo` (attachment ID), `nav_menu_locations`, `header_image`, `background_color`

### Read Custom Logo
1. Call `get_option` with key `theme_mods_{theme_slug}`
2. Get the `custom_logo` value — it's an attachment ID
3. Call `get_post` with that ID to get the logo image URL

### Check Homepage Settings
1. Call `get_option` with key `show_on_front` — `page` (static page) or `posts` (latest posts)
2. If static: `get_option` with key `page_on_front` (homepage ID) and `page_for_posts` (blog page ID)

### Check Menu Locations
1. Call `get_option` with key `theme_mods_{theme_slug}`
2. Look for `nav_menu_locations` — maps location slugs to menu IDs

### Check Widget Settings
1. Call `get_option` with key `sidebars_widgets` — maps sidebar IDs to widget IDs
2. Individual widget settings: `get_option` with key `widget_{widget_base}` (e.g., `widget_text`, `widget_search`)

## Template Hierarchy Reference

WordPress selects templates from most specific to least specific:
- **Front Page**: `front-page.php` > `home.php` > `page.php` > `index.php`
- **Single Post**: `single-{post_type}-{slug}.php` > `single-{post_type}.php` > `single.php` > `index.php`
- **Page**: custom template > `page-{slug}.php` > `page-{id}.php` > `page.php` > `index.php`
- **Category**: `category-{slug}.php` > `category-{id}.php` > `category.php` > `archive.php` > `index.php`
- **Search**: `search.php` > `index.php`
- **404**: `404.php` > `index.php`

## Block Themes
- Use HTML templates with block markup instead of PHP
- Structure: `templates/` (full pages) + `parts/` (header, footer)
- Configuration via `theme.json` (see wp-theme-json knowledge)

## Important Notes
- Wally cannot switch themes, install themes, or edit theme files — guide user to Appearance admin
- Theme mods are stored per-theme — switching themes resets Customizer settings
- Child theme `functions.php` loads BEFORE parent — it's additive, not a replacement
- `get_template_directory_uri()` always points to parent theme; `get_stylesheet_directory_uri()` points to child theme
- For theme file editing, template overrides, or Customizer changes, guide user to Appearance > Customize or Appearance > Theme File Editor
- Block themes automatically support: `post-thumbnails`, `responsive-embeds`, `editor-styles`, `html5`
