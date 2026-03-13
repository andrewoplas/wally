# Content Display Plugins

## When to Use
- User mentions TablePress, tables, or WP Popular Posts
- User wants to create or manage data tables on their site
- User wants to display popular/trending posts
- User asks about shortcodes for tables or popular content

## Available Tools
- `list_plugins` — detect which content plugin is active
- `list_posts` — list TablePress tables (post type `tablepress_table`)
- `get_post` — get table data (stored as JSON in post content)
- `search_content` — find pages containing table or popular posts shortcodes
- `replace_content` — update shortcode attributes across pages
- `get_option` — read plugin settings
- `update_option` — change plugin settings (requires confirmation)

## Workflows

### Detect Active Plugin
1. Call `list_plugins`
2. Look for: `tablepress` or `wordpress-popular-posts`

### TablePress — Find All Tables
1. Call `list_posts` with `post_type: 'tablepress_table'`
2. Each post title is the table name; content contains table data as JSON

### TablePress — Find Pages Using a Table
1. Call `search_content` with `[table id=` to find all pages embedding tables
2. Results show which pages contain TablePress shortcodes

### TablePress — Update Shortcode Attributes
1. Call `replace_content` with old shortcode and updated attributes (requires confirmation)
2. Example: replace `[table id=1 /]` with `[table id=1 datatables_sort="true" /]`

### TablePress — Read Settings
1. Call `get_option` with key `tablepress_tables`
2. Returns mapping of table IDs to post IDs: `{ "1": 123, "2": 456 }`

### WP Popular Posts — Read Settings
1. Call `get_option` with key `wpp_settings`
2. Key settings: `is_active`, `ajax` (for cached sites), `post_type`, `cache`

### WP Popular Posts — Find Pages Using Widget/Shortcode
1. Call `search_content` with `[wpp` to find pages with popular posts shortcodes

### Embed Shortcodes in Content
1. Call `update_post` to add shortcodes to page/post content:
   - TablePress: `[table id=X /]`
   - WP Popular Posts: `[wpp range="last7days" limit=10]`

## Important Notes
- TablePress table data is JSON in the post content — do not edit it directly via `update_post`; guide user to TablePress admin
- WP Popular Posts on cached sites should use AJAX tracking (`ajax` = 1) — check via `get_option`
- Both plugins use shortcodes as the primary rendering mechanism, compatible with any page builder
- For creating or modifying table data, guide user to TablePress admin (Tools > TablePress)
- TablePress custom CSS is in `get_option` key `tablepress_custom_css`
