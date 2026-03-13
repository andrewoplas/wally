# Settings

## When to Use
- User wants to read or update WordPress site settings (title, tagline, timezone, etc.)
- User asks about permalink structure, media sizes, reading settings, or registration
- User wants to check or change a plugin setting stored in wp_options
- User asks what a WordPress setting is currently set to

## Available Tools
- `get_site_info` — get site title, tagline, URL, WP version, theme, active plugins, timezone
- `get_option` — read any WordPress option by key
- `update_option` — update any WordPress option by key (requires confirmation)

## Workflows

### Get Site Overview
1. Call `get_site_info` for a summary of title, tagline, URL, WP version, theme, and timezone
2. For a specific setting, call `get_option` with the exact key

### Update Site Title or Tagline
1. Call `update_option` with `key: 'blogname'` and `value: '<new title>'` (requires confirmation)
2. Call `update_option` with `key: 'blogdescription'` and `value: '<new tagline>'` (requires confirmation)

### Change Timezone
1. Call `get_option` with key `timezone_string` to see current timezone
2. Call `update_option` with `key: 'timezone_string'` and `value: 'America/New_York'` (requires confirmation)

### Update Reading Settings
1. Call `get_option` with key `show_on_front` — `'posts'` (blog) or `'page'` (static front page)
2. Call `get_option` with key `posts_per_page` — posts shown per page
3. Update with `update_option` + confirmation

### Change Permalink Structure
1. Call `get_option` with key `permalink_structure` to see current pattern
2. Call `update_option` with `key: 'permalink_structure'` and new value (e.g., `'/%postname%/'`) — requires confirmation

### Check Search Engine Visibility
1. Call `get_option` with key `blog_public` — `'1'` = visible, `'0'` = discouraged by search engines

### Update Media Image Sizes
1. Call `update_option` with key `thumbnail_size_w` / `thumbnail_size_h` for thumbnail dimensions (requires confirmation)
2. Repeat for `medium_size_w`, `medium_size_h`, `large_size_w`, `large_size_h`
3. Tell the user: size changes only apply to future uploads — existing images need regeneration

## Common Option Keys

| Setting | Option Key | Example Value |
|---------|-----------|---------------|
| Site title | `blogname` | `'My Site'` |
| Tagline | `blogdescription` | `'Just another site'` |
| Admin email | `admin_email` | `'admin@example.com'` |
| Posts per page | `posts_per_page` | `'10'` |
| Front page type | `show_on_front` | `'posts'` or `'page'` |
| Static front page ID | `page_on_front` | `'42'` |
| Blog page ID | `page_for_posts` | `'15'` |
| Permalink structure | `permalink_structure` | `'/%postname%/'` |
| Timezone | `timezone_string` | `'America/Chicago'` |
| User registration | `users_can_register` | `'0'` or `'1'` |
| Default role | `default_role` | `'subscriber'` |
| Search engine visibility | `blog_public` | `'1'` or `'0'` |
| Date format | `date_format` | `'F j, Y'` |

## Important Notes
- `update_option` requires user confirmation before executing
- Never change `siteurl` or `home` — this can break the site
- Plugin settings often use prefixed keys (e.g., `woocommerce_currency`, `yoast_wpseo_titles`)
- Theme mods are in `theme_mods_{theme_slug}` — readable via `get_option` with that key
