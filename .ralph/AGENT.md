# Ralph Agent Configuration

## Prerequisites
- None — this mission only edits `.md` files

## Build Instructions

No build needed. This mission modifies only Markdown knowledge files.

```bash
# No build commands required for .md file changes
```

## Project Structure

```
apps/backend/src/knowledge/
  general.md              ← ALREADY CONVERTED (do not touch)
  content.md              ← ALREADY CONVERTED (do not touch)
  wally-capabilities.md   ← ALREADY CONVERTED (do not touch)
  acf.md                  ← Convert
  analytics-plugins.md    ← Convert
  audit-logging.md        ← Convert
  ... (64 more files to convert)
```

## Available Wally Tools (REFERENCE)

These are the ONLY tools you should reference in converted knowledge files. Tool names must match exactly.

### Content Tools (`class-content-tools.php`)
| Tool | Action | Description |
|------|--------|-------------|
| `list_posts` | read | List posts/pages/CPTs with filters (post_type, status, per_page, page, search, category, tag, author, orderby, order, date_after, date_before, meta_key, meta_value) |
| `get_post` | read | Get a single post by ID (returns title, content, excerpt, status, author, dates, meta, terms) |
| `create_post` | create | Create a post (title, content, excerpt, status, post_type, author, categories, tags, meta) |
| `update_post` | update | Update a post by ID (any field: title, content, excerpt, status, categories, tags, meta) |
| `delete_post` | delete | Delete a post by ID (moves to trash by default, `force: true` to permanently delete) — **requires confirmation** |

### Taxonomy Tools (`class-taxonomy-tools.php`)
| Tool | Action | Description |
|------|--------|-------------|
| `list_categories` | read | List all categories (returns id, name, slug, count, parent) |
| `list_tags` | read | List all tags (returns id, name, slug, count) |
| `create_category` | create | Create a category (name, slug, parent, description) |
| `create_tag` | create | Create a tag (name, slug, description) |

### Site Tools (`class-site-tools.php`)
| Tool | Action | Description |
|------|--------|-------------|
| `get_site_info` | read | Get site title, tagline, URL, WP version, theme, active plugins, timezone, language, permalink structure |
| `get_site_health` | read | Get WordPress Site Health status and issues |
| `get_option` | read | Read any wp_options value by key |
| `update_option` | update | Update any wp_options value by key — **requires confirmation** |

### Plugin Tools (`class-plugin-tools.php`)
| Tool | Action | Description |
|------|--------|-------------|
| `list_plugins` | read | List all installed plugins (name, status, version, update_available) |
| `install_plugin` | create | Install a plugin from WordPress.org by slug — **requires confirmation** |
| `activate_plugin` | update | Activate an installed plugin by slug — **requires confirmation** |
| `deactivate_plugin` | update | Deactivate a plugin by slug — **requires confirmation** |
| `update_plugin` | update | Update a plugin to latest version by slug — **requires confirmation** |
| `delete_plugin` | delete | Delete a deactivated plugin by slug — **requires confirmation** |

### Search Tools (`class-search-tools.php`)
| Tool | Action | Description |
|------|--------|-------------|
| `search_content` | read | Search across posts/pages by keyword (searches title, content, excerpt) |
| `replace_content` | update | Find and replace text across posts/pages — **requires confirmation** |

### Elementor Tools (`class-elementor-tools.php` + `class-elementor-builder-tools.php`)
| Tool | Action | Description |
|------|--------|-------------|
| `elementor_search_content` | read | Search within Elementor page data for text |
| `elementor_replace_content` | update | Find and replace text within Elementor page data — **requires confirmation** |
| `elementor_get_page_structure` | read | Get the widget structure of an Elementor page |
| `elementor_clear_css_cache` | update | Clear Elementor's CSS cache |
| `elementor_get_page` | read | Get full Elementor page data (widgets, settings) |
| `elementor_create_page` | create | Create a new page with Elementor content |
| `elementor_update_section` | update | Update a section in an Elementor page |
| `elementor_add_section` | create | Add a new section to an Elementor page |
| `elementor_delete_section` | delete | Delete a section from an Elementor page — **requires confirmation** |
| `elementor_reorder_sections` | update | Reorder sections in an Elementor page |
| `elementor_duplicate_section` | create | Duplicate a section in an Elementor page |
| `elementor_update_widget` | update | Update a widget's settings in an Elementor page |
| `elementor_get_global_settings` | read | Get Elementor global settings |
| `elementor_get_templates` | read | List Elementor saved templates |
| `elementor_verify_page` | read | Verify Elementor page content after changes |

### ACF Tools (`class-acf-tools.php`)
| Tool | Action | Description |
|------|--------|-------------|
| `acf_list_field_groups` | read | List all ACF field groups |
| `acf_get_field_group` | read | Get field group details by ID |
| `acf_create_field_group` | create | Create a new ACF field group |
| `acf_update_field_group` | update | Update an ACF field group |
| `acf_delete_field_group` | delete | Delete an ACF field group — **requires confirmation** |
| `acf_list_post_types` | read | List ACF registered post types |
| `acf_get_post_type` | read | Get ACF post type details |
| `acf_create_post_type` | create | Create an ACF custom post type |
| `acf_update_post_type` | update | Update an ACF custom post type |
| `acf_delete_post_type` | delete | Delete an ACF custom post type — **requires confirmation** |
| `acf_list_taxonomies` | read | List ACF registered taxonomies |
| `acf_get_taxonomy` | read | Get ACF taxonomy details |
| `acf_create_taxonomy` | create | Create an ACF custom taxonomy |
| `acf_update_taxonomy` | update | Update an ACF custom taxonomy |
| `acf_delete_taxonomy` | delete | Delete an ACF custom taxonomy — **requires confirmation** |
| `acf_get_post_fields` | read | Get ACF field values for a post |
| `acf_update_post_fields` | update | Update ACF field values for a post |
| `acf_get_term_fields` | read | Get ACF field values for a term |
| `acf_update_term_fields` | update | Update ACF field values for a term |
| `acf_get_user_fields` | read | Get ACF field values for a user |
| `acf_update_user_fields` | update | Update ACF field values for a user |
| `acf_list_options_pages` | read | List ACF options pages |
| `acf_get_option_field` | read | Get a single ACF options page field value |
| `acf_update_option_field` | update | Update a single ACF options page field value |

## Notes
- **Tool names must match exactly** — use the names from the table above in all converted files
- **"requires confirmation"** tools need the user to approve before execution — mention this in workflows where relevant
- **Conditional tools** — ACF tools only appear when ACF is active, Elementor tools only when Elementor is active. Use `list_plugins` to check availability.
- **Post types** — Many WordPress features (products, events, etc.) are custom post types accessible via `list_posts` with the right `post_type` parameter
- **Options** — Many plugin settings are stored in `wp_options` and accessible via `get_option`/`update_option`
