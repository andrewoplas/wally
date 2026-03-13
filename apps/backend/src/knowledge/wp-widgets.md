# WordPress Widgets & Sidebars

## When to Use
- User asks about widgets, sidebars, or widget areas
- User wants to know what widgets are active or where they appear
- User asks about block-based widgets vs classic widgets

## Key Patterns

### How Widgets Work
- **Sidebars** (widget areas) are registered by the theme
- **Widgets** are placed into sidebars via Appearance > Widgets
- Since WP 5.8, widget areas support the block editor (block-based widgets)

### Widget Storage in Database
- `sidebars_widgets` option — maps sidebar IDs to widget instance IDs (e.g., `text-3`, `search-2`)
- `widget_{type}` options — each widget type stores all instances in a single option (e.g., `widget_text`, `widget_nav_menu`)
- `wp_inactive_widgets` — sidebar holding deactivated widgets that retain their settings

### Common Built-in Widgets
| Widget Type | Option Key | Description |
|-------------|-----------|-------------|
| Text | `widget_text` | Arbitrary text/HTML |
| Custom HTML | `widget_custom_html` | Raw HTML |
| Recent Posts | `widget_recent-posts` | Recent posts list |
| Categories | `widget_categories` | Category list/dropdown |
| Navigation Menu | `widget_nav_menu` | Custom menu |
| Search | `widget_search` | Search form |
| Block | `widget_block` | Block editor widget (WP 5.8+) |

### Block-Based Widgets (WP 5.8+)
- Widget areas now support Gutenberg blocks
- Block widgets stored in `widget_block` option with HTML block content
- Classic widget editor can be restored with Classic Widgets plugin

### Checking Widget Configuration
- Read `sidebars_widgets` to see which widgets are in which sidebars
- Read `widget_{type}` to see individual widget settings
- Widget IDs follow pattern: `{type}-{instance_number}` (e.g., `text-3`)

## Workflows

### List Active Widgets and Their Locations
1. Call `get_option` with key `sidebars_widgets`
2. For each sidebar, the value shows which widget instances are assigned
3. To see a widget's settings, call `get_option` with key `widget_{type}` (e.g., `widget_text`)

### Check if a Specific Widget Type Is Active
1. Call `get_option` with key `sidebars_widgets`
2. Look for widget instances matching the type (e.g., `nav_menu-2` means Navigation Menu widget)

## Relevant Wally Tools
- `get_option` with key `sidebars_widgets` — see all sidebar-to-widget mappings
- `get_option` with key `widget_{type}` — read settings for a specific widget type
- `get_site_info` — returns active theme (themes define available sidebars)

## Important Notes
- Wally cannot add, remove, or rearrange widgets — guide user to Appearance > Widgets
- Widget areas are defined by the theme — switching themes may deactivate widgets (moved to inactive)
- Block-based widget editor may confuse users familiar with the classic editor — check for Classic Widgets plugin
- Widget settings persist even when moved to inactive — only deleting from inactive removes them
