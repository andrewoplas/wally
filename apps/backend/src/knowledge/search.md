# Search & Replace

## When to Use
- User wants to find text across posts, pages, or custom post types
- User wants to replace text (a URL, phrase, company name) across site content
- User wants to find or replace text inside Elementor pages
- User asks about bulk content changes

## Available Tools
- `search_content` — search post titles and content across post types
- `replace_content` — find and replace text across posts/pages (requires confirmation)
- `elementor_search_content` — search within Elementor page builder widget data
- `elementor_replace_content` — replace text inside Elementor pages (requires confirmation)

## Workflows

### Find Text Across Site Content
1. Call `search_content` with `keyword: '<text to find>'`
2. Optionally filter by `post_type` (default: post and page)
3. Report title, ID, and URL of each matching post to the user

### Find Text in Elementor Pages
1. Call `elementor_search_content` with `search: '<text to find>'`
2. Returns Elementor pages that contain the text in widget/element data

### Replace Text in Standard Content
1. First call `search_content` to confirm where the text exists
2. Show the user what will be changed and ask for confirmation
3. Call `replace_content` with `old_text: '<find>'` and `new_text: '<replace>'`
4. Optionally scope with `post_type` to limit to posts or pages

### Replace Text in Elementor Pages
1. First call `elementor_search_content` to preview matches
2. Show the user what will be changed and confirm
3. Call `elementor_replace_content` with `old_text: '<find>'` and `new_text: '<replace>'`
4. After replacing, call `elementor_clear_css_cache` to refresh Elementor styles

### Comprehensive Find and Replace (Standard + Elementor)
1. Call `search_content` for standard content matches
2. Call `elementor_search_content` for Elementor content matches
3. Report ALL matches from both before replacing
4. Run `replace_content` then `elementor_replace_content` after confirmation

## Important Notes
- Always show the user what will change before replacing — `replace_content` requires confirmation
- `search_content` searches title and post_content only — not post meta, taxonomy names, or options
- For settings/options containing the old text, use `get_option` / `update_option` separately
- Wally does not support regex in replace — use exact text matches
- If replacing a URL sitewide, also check `get_option` for `siteurl`, `home`, and plugin-specific option keys
