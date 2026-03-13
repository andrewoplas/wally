# Developer & Utility Plugins

## When to Use
- User mentions Code Snippets, Search Regex, WP Rollback, WP-PageNavi, or Font Awesome
- User wants to manage code snippets, do advanced search/replace, rollback a plugin, or configure pagination/icons
- User asks about adding custom PHP/CSS/JS without editing theme files

## Available Tools
- `list_plugins` — detect which dev/utility plugin is active
- `list_posts` — list Code Snippets (stored in custom table, not directly listable) or search for shortcodes
- `search_content` — find pages using snippet shortcodes or pagination
- `get_option` — read plugin settings
- `update_option` — change plugin settings (requires confirmation)

## Workflows

### Detect Active Plugin
1. Call `list_plugins`
2. Look for: `code-snippets`, `search-regex`, `wp-rollback`, `wp-pagenavi`, `font-awesome`

### Code Snippets — Read Settings
1. Call `get_option` with key `code_snippets_settings`
2. Key settings: `general` (enable_tags, enable_description), `safety` (enable_safe_mode)

### Code Snippets — Find Snippet Shortcodes in Pages
1. Call `search_content` with `[code_snippet` to find pages using snippet shortcodes

### Search Regex — Read Settings
1. Call `get_option` with key `searchregex_options`
2. Search Regex provides advanced regex search/replace across the database — more powerful than Wally's `search_content`/`replace_content`

### WP-PageNavi — Read Settings
1. Call `get_option` with key `pagenavi_options`
2. Key settings: `num_pages`, `prev_text`, `next_text`, `use_pagenavi_css`

### WP-PageNavi — Update Pagination Text
1. Call `update_option` with key `pagenavi_options` and updated values (requires confirmation)

### Font Awesome — Read Settings
1. Call `get_option` with key `font-awesome`
2. Key settings: `technology` (svg/webfont), `version`, `usePro`, `compat` (v4 compatibility)
3. Do NOT expose `kitToken` or `apiToken`

### WP Rollback — Check Availability
1. Call `list_plugins` to verify `wp-rollback` is active
2. Tell user: "WP Rollback is available. Go to Plugins page — each WordPress.org plugin has a 'Rollback' link to select a previous version."

## Important Notes
- Code Snippets stores data in a custom database table (`snippets`) — not accessible as posts via Wally tools
- For creating, editing, or running code snippets, guide user to Snippets admin page
- Search Regex handles serialized data safely (critical for Elementor/ACF data) — for advanced regex operations, guide user to Tools > Search Regex
- WP Rollback only works with WordPress.org plugins — not premium or private plugins
- WP Rollback replaces files but does NOT revert database changes made by newer plugin versions
- Font Awesome conflict detection auto-removes duplicate FA versions — check `removeUnregisteredClients` setting if icons appear broken
- Code Snippets safe mode: if a snippet causes a fatal error, access `?snippets-safe-mode=1` to deactivate all snippets
