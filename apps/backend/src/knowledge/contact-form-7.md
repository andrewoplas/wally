# Contact Form 7

## When to Use
- User asks about contact forms or Contact Form 7
- User wants to find, embed, or check the status of a CF7 form
- User wants to check CF7 settings or spam filtering configuration
- Site has CF7 active (check via `list_plugins` → look for `contact-form-7`)

## Available Tools
- `list_plugins` — check if Contact Form 7 is installed and active
- `list_posts` with `post_type: 'wpcf7_contact_form'` — list all CF7 forms
- `get_post` — get a CF7 form's details by ID
- `create_post` / `update_post` — embed a CF7 form on a page using the shortcode block
- `search_content` — find pages that already embed a specific form
- `get_option` — read CF7 global settings

## Workflows

### Check if CF7 is Active
1. Call `list_plugins`
2. Look for `contact-form-7`

### List All Contact Forms
1. Call `list_posts` with `post_type: 'wpcf7_contact_form'`
2. Returns form IDs and titles

### Embed a CF7 Form on a Page
1. Find the form ID using `list_posts` with `post_type: 'wpcf7_contact_form'`
2. Add the shortcode to the page content:
   - For Gutenberg: use a shortcode block `<!-- wp:shortcode -->[contact-form-7 id="123" title="Contact Form"]<!-- /wp:shortcode -->`
   - For Elementor: use `elementor_add_section` or `elementor_update_widget` with a shortcode widget
3. Call `update_post` to add the shortcode to the page's `content`

### Find Pages Embedding a Specific Form
1. Call `search_content` with `keyword: 'contact-form-7'` or the form's shortcode ID
2. Returns pages that contain the CF7 shortcode

### Check CF7 Settings
1. Call `get_option` with key `wpcf7`
2. Returns global CF7 configuration

## Important Notes
- CF7 does NOT store form submissions by default — submissions are only emailed; recommend the Flamingo plugin for database storage
- Wally cannot create or edit CF7 form fields/templates — guide user to **Contact > Contact Forms** in WordPress admin
- Wally cannot configure mail recipients, spam filtering, or reCAPTCHA — guide user to edit the form in the CF7 admin
- CF7 shortcode format: `[contact-form-7 id="123" title="Contact Form"]`
- CF7 forms are stored as post type `wpcf7_contact_form` — use this in `list_posts`
