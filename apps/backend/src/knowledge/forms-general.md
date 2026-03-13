# Forms (General)

## When to Use
- User asks about form plugins (WPForms, Ninja Forms, Fluent Forms, Formidable, Forminator)
- User wants to check which form plugin is active
- User wants to embed a form on a page
- User asks about form submissions or entries

## Available Tools
- `list_plugins` — check which form plugin is installed and active
- `create_post` / `update_post` — embed a form on a page via shortcode
- `search_content` — find pages that already embed forms
- `get_option` — read form plugin settings
- `install_plugin` — install a free form plugin (requires confirmation)
- `activate_plugin` — activate an installed form plugin (requires confirmation)

## Workflows

### Identify Which Form Plugin is Active
1. Call `list_plugins`
2. Look for these slugs:
   - WPForms: `wpforms-lite` (free) or `wpforms` (Pro)
   - Ninja Forms: `ninja-forms`
   - Fluent Forms: `fluentform`
   - Formidable: `formidable`
   - Forminator: `forminator`
   - Contact Form 7: `contact-form-7` (see `contact-form-7.md`)
   - Gravity Forms: `gravityforms` (see `gravity-forms.md`)

### Embed a Form on a Page
1. Ask the user for the form ID (Wally cannot list forms from most plugins)
2. Use the correct shortcode for the active plugin:
   - WPForms: `[wpforms id="123"]`
   - Ninja Forms: `[ninja_form id=123]`
   - Fluent Forms: `[fluentform id="123"]`
   - Formidable: `[formidable id=123]`
   - Forminator: `[forminator_form id=123]`
3. For Gutenberg pages, wrap in: `<!-- wp:shortcode -->[shortcode here]<!-- /wp:shortcode -->`
4. Call `update_post` with the page ID and updated `content`

### Install a Form Plugin
1. Call `install_plugin` with slug (e.g., `wpforms-lite`, `ninja-forms`, `fluentform`) — requires confirmation
2. Call `activate_plugin` with the slug — requires confirmation
3. Guide user to the plugin's admin menu to create their first form

### Find Pages with Embedded Forms
1. Call `search_content` with `keyword: 'wpforms'` (or the relevant shortcode tag)
2. Returns pages containing form shortcodes

## Important Notes
- Wally cannot create, edit, or delete forms in any form plugin — guide user to the plugin's admin page
- Wally cannot view or manage form submissions/entries — guide user to the entries section in the plugin's admin
- Form submissions are stored in custom database tables (not accessible via Wally tools)
- WPForms Lite and Ninja Forms are free on WordPress.org; Gravity Forms, WPForms Pro, and Formidable Pro are premium
- All form plugins support notifications (email) and confirmations (success message/redirect)
