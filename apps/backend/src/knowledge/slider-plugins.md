# WordPress Slider Plugins

## When to Use
- User mentions Slider Revolution, RevSlider, MetaSlider, Smart Slider, or sliders/carousels
- User wants to find, embed, or manage sliders on their site
- User asks about slider shortcodes or where sliders are used

## Available Tools
- `list_plugins` — detect which slider plugin is active
- `search_content` — find pages containing slider shortcodes
- `replace_content` — update slider shortcodes (requires confirmation)
- `update_post` — embed slider shortcode in a page
- `get_option` — read slider plugin settings

## Workflows

### Detect Active Slider Plugin
1. Call `list_plugins`
2. Look for: `revslider` (Slider Revolution), `ml-slider` (MetaSlider), `smart-slider-3`

### Find Pages Using Sliders
1. Call `search_content` with the appropriate shortcode pattern:
   - Slider Revolution: `[rev_slider`
   - MetaSlider: `[metaslider` or `[ml-slider`
   - Smart Slider 3: `[smartslider3`
2. Results show which pages contain slider embeds

### Embed a Slider on a Page
1. Call `update_post` on the target page, adding the slider shortcode to content:
   - Slider Revolution: `[rev_slider alias="slider-alias"]`
   - MetaSlider: `[metaslider id="123"]`
   - Smart Slider 3: `[smartslider3 slider="123"]`

### Replace a Slider Shortcode
1. Call `replace_content` with the old shortcode and the new one (requires confirmation)
2. Example: replace `[rev_slider alias="old-slider"]` with `[rev_slider alias="new-slider"]`

### Slider Revolution — Read Global Settings
1. Call `get_option` with key `revslider-global-settings`
2. Check `revslider-valid` for license status (do NOT expose `revslider-code`)

### MetaSlider — Read Settings
1. Call `get_option` with key `metaslider_default_settings` for defaults
2. Slider data is stored as post type `ml-slider` — use `list_posts` with `post_type: 'ml-slider'` to find sliders

### Smart Slider 3 — Check Version
1. Call `get_option` with key `smart-slider-3-version`
2. Slider data is in custom tables — not directly accessible via Wally tools

## Important Notes
- Slider content and configuration are stored in custom database tables (RevSlider, Smart Slider 3) or custom post types (MetaSlider) — Wally can find and embed sliders but cannot edit slide content
- Slider Revolution license code (`revslider-code`) is sensitive — do NOT expose it
- For creating, editing, or configuring sliders, guide user to the plugin's visual editor
- Slider JavaScript libraries are large (~300KB for RevSlider) — check if "load only on pages with sliders" is enabled for performance
- RevSlider is a premium plugin (not on WordPress.org) — cannot be installed via `install_plugin`
- MetaSlider sliders are post type `ml-slider`; slides are attachments linked via postmeta
- After embedding a slider shortcode, verify the alias/ID matches an existing slider
