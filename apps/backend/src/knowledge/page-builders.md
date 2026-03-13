# Page Builders

## When to Use
- User mentions Beaver Builder, Divi, Brizy, Oxygen, WPBakery, or "Visual Composer"
- User asks what page builder their site uses
- User wants to find or replace content on pages built with these builders
- User wants to check if a page builder is active

## Available Tools
- `list_plugins` — check which page builder is installed and active
- `search_content` — search standard post_content (works for Divi and WPBakery only)
- `elementor_search_content` — search Elementor page data (Elementor only)
- `elementor_replace_content` — replace text in Elementor pages (requires confirmation)
- `replace_content` — replace text in post_content (works for Divi and WPBakery)
- `get_option` — read page builder global settings

## Workflows

### Identify Which Page Builder is Active
1. Call `list_plugins`
2. Look for these slugs:
   - Elementor: `elementor` (has dedicated Elementor tools — see `elementor.md`)
   - Divi: `Divi` theme or `divi-builder`
   - Beaver Builder: `beaver-builder-lite-version` or `bb-plugin`
   - Oxygen: `oxygen`
   - WPBakery: `js_composer`
   - Brizy: `brizy`

### Find Content on Page Builder Pages

| Page Builder | How to Search |
|-------------|--------------|
| Elementor | Use `elementor_search_content` — content is in `_elementor_data` postmeta |
| Divi | Use `search_content` — content stored as shortcodes in `post_content` |
| WPBakery | Use `search_content` — content stored as shortcodes in `post_content` |
| Beaver Builder | Guide user to admin — content is in serialized postmeta, not accessible via Wally |
| Brizy | Guide user to admin — content is in JSON postmeta, not accessible via Wally |
| Oxygen | Guide user to admin — content is in shortcode postmeta, not accessible via Wally |

### Replace Content on Page Builder Pages

| Page Builder | How to Replace |
|-------------|---------------|
| Elementor | Use `elementor_replace_content` (requires confirmation), then `elementor_clear_css_cache` |
| Divi | Use `replace_content` (caution: avoid breaking shortcode syntax) |
| WPBakery | Use `replace_content` (caution: avoid breaking shortcode syntax) |
| Beaver Builder / Brizy / Oxygen | Guide user to their editor — Wally cannot replace content |

### Read Page Builder Global Settings
- Divi: `get_option` with key `et_divi`
- WPBakery: `get_option` with keys prefixed `wpb_js_`
- Beaver Builder: `get_option` with keys prefixed `_fl_builder_`

## Important Notes
- Elementor has dedicated tools — always use those for Elementor pages (see `elementor.md`)
- Never have two page builders active on the same site — conflicts cause broken layouts
- Standard `search_content` only finds text in `post_content` — it misses Elementor, Beaver Builder, Brizy, and Oxygen data which is stored in postmeta
- For all direct page editing in Beaver Builder, Brizy, or Oxygen, guide the user to the WordPress admin editor for that builder
- Wally cannot create or edit Beaver Builder, Divi, Brizy, or Oxygen pages — guide user to their respective admin editors
