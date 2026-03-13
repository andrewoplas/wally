# WordPress theme.json

## When to Use
- User asks about theme.json, block theme configuration, or Global Styles
- User wants to understand how colors, fonts, spacing, or layout are configured in block themes
- User asks about the relationship between theme.json and the Site Editor/Customizer

## Key Patterns

### What theme.json Does
Central configuration file for block themes (and block-aware classic themes). Controls:
- **Settings**: Which design tools are available in the editor (colors, fonts, spacing)
- **Styles**: Default CSS for the site, elements, and individual blocks
- **Templates**: Custom page templates and template parts

### File Location
- Theme root: `{theme-directory}/theme.json`
- Child themes can have their own `theme.json` that merges with the parent's
- Schema version: `3` (WP 6.6+), `2` (WP 5.9+)

### Three Cascading Layers
1. **Core** — WordPress defaults (`wp-includes/theme.json`)
2. **Theme** — Theme's `theme.json` overrides core
3. **User** — Global Styles customizations in Site Editor override both

### Key Settings Sections
| Section | Controls |
|---------|----------|
| `settings.color` | Color palette, gradients, custom colors, duotone |
| `settings.typography` | Font families, font sizes, line height, letter spacing |
| `settings.spacing` | Padding, margin, block gap, spacing scale |
| `settings.layout` | Content width (`contentSize`), wide width (`wideSize`) |
| `settings.border` | Border color, radius, style, width controls |
| `settings.appearanceTools` | Shorthand to enable many settings at once |

### CSS Custom Properties
WordPress auto-generates CSS variables from presets:
- Color: `--wp--preset--color--{slug}`
- Font size: `--wp--preset--font-size--{slug}`
- Font family: `--wp--preset--font-family--{slug}`
- Spacing: `--wp--preset--spacing--{slug}`

### User Customizations Storage
Global Styles (user layer) are stored as a custom post type `wp_global_styles` in the database.

## Relevant Wally Tools
- `get_site_info` — returns active theme name (helps determine if it's a block theme)
- `list_posts` with `post_type: 'wp_global_styles'` — access user's Global Styles customizations
- `list_posts` with `post_type: 'wp_template'` — list custom templates
- `list_posts` with `post_type: 'wp_template_part'` — list template parts (header, footer)
- `get_option` with key `stylesheet` — get active theme slug

## Important Notes
- Wally cannot edit theme.json directly — it's a file on the server, not a database record
- User-level Global Styles (from the Site Editor) ARE stored in the database and can be read via `list_posts` with `post_type: 'wp_global_styles'`
- If user asks to change colors/fonts/spacing, guide them to Appearance > Editor > Styles in the Site Editor
- `theme.json` replaces many `add_theme_support()` calls — if both exist, theme.json takes precedence
- Classic themes can use a partial `theme.json` to configure block editor settings
