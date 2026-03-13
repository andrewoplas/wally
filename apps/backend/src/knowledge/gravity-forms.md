# Gravity Forms

## When to Use
- User asks about Gravity Forms or form entries/submissions
- User wants to embed a Gravity Form on a page
- User wants to check Gravity Forms settings or add-ons
- Site has Gravity Forms active (check via `list_plugins` → look for `gravityforms`)

## Available Tools
- `list_plugins` — check if Gravity Forms is installed and active
- `create_post` / `update_post` — embed a Gravity Form on a page using shortcode
- `search_content` — find pages that embed a specific form
- `get_option` — read Gravity Forms settings (add-on configs use `gravityformsaddon_*` keys)

## Workflows

### Check if Gravity Forms is Active
1. Call `list_plugins`
2. Look for `gravityforms` — note: Gravity Forms is a premium plugin, not on WordPress.org

### Embed a Gravity Form on a Page
1. Ask the user for the form ID (Wally cannot list GF forms directly)
2. Add the shortcode to the page content: `[gravityform id="5" title="true" description="true"]`
3. For Gutenberg: wrap in shortcode block `<!-- wp:shortcode -->[gravityform id="5" title="true"]<!-- /wp:shortcode -->`
4. Call `update_post` with the page ID and updated `content`

### Find Pages with Gravity Forms Embedded
1. Call `search_content` with `keyword: 'gravityform'`
2. Returns pages containing Gravity Forms shortcodes

### Check Gravity Forms Add-on Settings
1. Call `get_option` with keys prefixed `gravityformsaddon_` (e.g., `gravityformsaddon_gravityformsmailchimp_settings`)

## Important Notes
- Wally cannot list, create, edit, or delete Gravity Forms — guide user to **Forms** in the WordPress admin menu
- Wally cannot view or manage form entries/submissions — guide user to **Forms > Entries**
- Gravity Forms is a premium plugin — it must be purchased and uploaded manually; `install_plugin` does not work
- Gravity Forms stores data in custom tables (`wp_gf_form`, `wp_gf_entry`, `wp_gf_entry_meta`), not accessible via Wally tools
- Shortcode format: `[gravityform id="X" title="true" description="true"]`
- Form notifications and confirmations must be configured in the Forms admin
