# Elementor

## When to Use
- User wants to create, edit, or view an Elementor page
- User asks to search or replace text in Elementor pages
- User wants to build a landing page or update page sections
- Site has Elementor active (check via `list_plugins` → look for `elementor`)

## Available Tools
- `elementor_create_page` — create a new page with Elementor content
- `elementor_get_page` — get full Elementor page data
- `elementor_get_page_structure` — get section/widget structure of a page
- `elementor_update_section` — update a section in an Elementor page
- `elementor_add_section` — add a new section to a page
- `elementor_delete_section` — delete a section (requires confirmation)
- `elementor_reorder_sections` — reorder sections on a page
- `elementor_duplicate_section` — duplicate a section
- `elementor_update_widget` — update a widget's settings
- `elementor_get_global_settings` — get Elementor global settings
- `elementor_get_templates` — list saved Elementor templates
- `elementor_verify_page` — verify page content after changes
- `elementor_search_content` — search text inside Elementor page data
- `elementor_replace_content` — find and replace text in Elementor data (requires confirmation)
- `elementor_clear_css_cache` — clear Elementor's CSS cache
- `list_posts` — list pages to find page IDs

## Workflows

### Create a New Elementor Page
1. Build the complete `elements` array (sections → columns → widgets) before creating
2. Call `elementor_create_page` with `title`, `status`, and the full `elements` array in ONE call — never create an empty page and add content separately
3. Call `elementor_get_page_structure` to verify the content saved correctly

### View a Page's Structure
1. Call `list_posts` with `post_type: 'page'` and `search` to find the page ID
2. Call `elementor_get_page_structure` with the page ID
3. Returns the section/widget tree with IDs

### Update a Widget's Content
1. Call `elementor_get_page_structure` to find the section and widget IDs
2. Call `elementor_update_widget` with the page ID, widget ID, and updated settings

### Add a Section to an Existing Page
1. Call `elementor_get_page_structure` to understand current layout
2. Call `elementor_add_section` with the page ID and new section data

### Search and Replace in Elementor Pages
1. Call `elementor_search_content` with `search: '<text>'` to preview matches
2. Show matches to user and confirm replacement
3. Call `elementor_replace_content` with `old_text` and `new_text` (requires confirmation)
4. Call `elementor_clear_css_cache` after replacing

### Clear Cache After Edits
1. Call `elementor_clear_css_cache` after any content update
2. Required so Elementor regenerates CSS and changes appear on the front end

## Element Structure Reference

All element IDs must be exactly 8 hex characters (e.g., `a1b2c3d4`). All IDs on the page must be unique.

```json
{ "id": "a1b2c3d4", "elType": "container", "isInner": false, "settings": {}, "elements": [] }
{ "id": "e5f6a7b8", "elType": "widget", "widgetType": "heading", "isInner": false, "settings": { "title": "Hello" }, "elements": [] }
```

**Column layout (inner container):**
```json
{ "id": "sec00001", "elType": "container", "isInner": false, "settings": { "flex_direction": "row" }, "elements": [
  { "id": "col00011", "elType": "container", "isInner": true, "settings": { "width": { "unit": "%", "size": 50 } }, "elements": [] },
  { "id": "col00012", "elType": "container", "isInner": true, "settings": { "width": { "unit": "%", "size": 50 } }, "elements": [] }
]}
```

### Key Widget Settings
| Widget | Key settings |
|--------|-------------|
| `heading` | `title`, `header_size` (h1–h6), `align`, `title_color` |
| `text-editor` | `editor` (HTML string with inline styles for color/alignment) |
| `button` | `text`, `link.url`, `align` |
| `image` | `image.url`, `image.id` (0 if no WP media ID), `align` |
| `icon-box` | `icon.value` ("fas fa-star"), `icon.library` ("fa-solid"), `title_text`, `description_text` |
| `spacer` | `space` ({"unit":"px","size":50}) |

## Important Notes
- Always build the complete `elements` array before calling `elementor_create_page` — partial builds fail
- Always call `elementor_clear_css_cache` after edits so the front end updates
- Standard `search_content` does NOT find Elementor text — always use `elementor_search_content` for Elementor pages
- Background color: `"background_background": "classic", "background_color": "#1a1a2e"` in container settings
- Padding: `{"unit": "px", "top": "60", "right": "40", "bottom": "60", "left": "40", "isLinked": false}`
