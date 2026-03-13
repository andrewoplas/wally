# Advanced Custom Fields (ACF)

## When to Use
- User wants to manage ACF field groups, custom post types, or custom taxonomies
- User wants to read or update ACF field values on posts, terms, users, or options pages
- Site has ACF active (check via `list_plugins` → look for `advanced-custom-fields` or `acf`)

## Available Tools
- `acf_list_field_groups` — list all ACF field groups
- `acf_get_field_group` — get a field group's full details (including field definitions)
- `acf_create_field_group` — create a new field group
- `acf_update_field_group` — update a field group
- `acf_delete_field_group` — delete a field group (requires confirmation)
- `acf_list_post_types` — list ACF-registered custom post types (ACF Pro 6.1+)
- `acf_get_post_type` — get ACF post type details
- `acf_create_post_type` — create a custom post type via ACF
- `acf_update_post_type` — update an ACF custom post type
- `acf_delete_post_type` — delete an ACF custom post type (requires confirmation)
- `acf_list_taxonomies` — list ACF-registered custom taxonomies
- `acf_get_taxonomy` — get ACF taxonomy details
- `acf_create_taxonomy` — create a custom taxonomy via ACF
- `acf_update_taxonomy` — update an ACF custom taxonomy
- `acf_delete_taxonomy` — delete an ACF custom taxonomy (requires confirmation)
- `acf_get_post_fields` — get ACF field values for a post
- `acf_update_post_fields` — update ACF field values on a post
- `acf_get_term_fields` — get ACF field values for a taxonomy term
- `acf_update_term_fields` — update ACF field values on a term
- `acf_get_user_fields` — get ACF field values for a user
- `acf_update_user_fields` — update ACF field values on a user
- `acf_list_options_pages` — list ACF options pages
- `acf_get_option_field` — get a single ACF options page field value
- `acf_update_option_field` — update a single ACF options page field value
- `list_plugins` — check if ACF or ACF Pro is active

## Workflows

### Check if ACF is Active
1. Call `list_plugins`
2. Look for `advanced-custom-fields` (free) or `advanced-custom-fields-pro` (Pro)
3. ACF Pro is required for custom post types, custom taxonomies, repeater/flexible content fields, and options pages

### List All Field Groups
1. Call `acf_list_field_groups`
2. Returns all field groups with their ID, title, and location rules

### Get Field Group Details (including all fields)
1. Call `acf_get_field_group` with the field group ID
2. Returns field definitions: label, name, type, and settings for each field

### Read ACF Field Values on a Post
1. Call `acf_get_post_fields` with the post ID
2. Returns all ACF field values for that post, keyed by field name

### Update ACF Field Values on a Post
1. Call `acf_update_post_fields` with the post ID and field values as a key→value object
2. Requires confirmation for writes

### Read / Update Term Fields
1. Call `acf_get_term_fields` with the term ID and taxonomy
2. Call `acf_update_term_fields` to update (requires confirmation)

### Read / Update User Fields
1. Call `acf_get_user_fields` with the user ID
2. Call `acf_update_user_fields` to update (requires confirmation)

### Read / Update Options Page Fields
1. Call `acf_list_options_pages` to find the options page name
2. Call `acf_get_option_field` with the page name and field name
3. Call `acf_update_option_field` to update (requires confirmation)

### List ACF Custom Post Types (Pro)
1. Call `acf_list_post_types`
2. Returns all ACF-registered custom post types with key, slug, labels, and active status

### Create a Custom Post Type (Pro)
1. Call `acf_create_post_type` with `post_type` key, `label`, `singular_label`, and any other settings

### List ACF Custom Taxonomies (Pro)
1. Call `acf_list_taxonomies`
2. Returns all ACF-registered taxonomies with key, slug, labels, and attached post types

## Important Notes
- Custom post types and custom taxonomies via ACF require ACF Pro 6.1+
- Repeater, Flexible Content, and Gallery fields are also ACF Pro only
- `acf_update_post_fields` writes values directly; the LLM should use field names (not keys) when possible
- Options page fields store site-wide data — always confirm before updating these
- ACF field names are the snake_case identifiers (e.g., `hero_title`), not the labels (e.g., "Hero Title")
