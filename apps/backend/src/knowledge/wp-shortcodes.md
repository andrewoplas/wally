# WordPress Shortcodes

## When to Use
- User asks about shortcodes, how to use them, or which shortcodes are available
- User wants to find where a shortcode is used in content
- User asks about shortcode output issues or broken shortcodes

## Key Patterns

### What Shortcodes Are
Shortcodes are `[bracketed_tags]` in post content that WordPress replaces with dynamic output at render time. They are registered by plugins and themes.

### Common Shortcode Formats
```
[shortcode]                              — self-closing
[shortcode attr="value" flag]            — with attributes
[shortcode]inner content[/shortcode]     — enclosing
```

### Shortcode Storage
- Shortcodes appear in `post_content` as plain text brackets: `[contact-form-7 id="123"]`
- In Gutenberg, they use the Shortcode block: `<!-- wp:shortcode -->[my_shortcode]<!-- /wp:shortcode -->`
- In Elementor, shortcodes go in a Shortcode widget

### Finding Shortcodes in Content
Use `search_content` to find posts containing specific shortcodes. Search for the shortcode tag name (e.g., `contact-form-7`, `gallery`, `woocommerce_cart`).

### Common Plugin Shortcodes
| Plugin | Example Shortcodes |
|--------|-------------------|
| Contact Form 7 | `[contact-form-7 id="123"]` |
| WooCommerce | `[woocommerce_cart]`, `[woocommerce_checkout]`, `[products]` |
| Gravity Forms | `[gravityform id="1"]` |
| Slider Revolution | `[rev_slider alias="name"]` |
| TablePress | `[table id=1]` |

### Replacing Shortcodes in Content
Use `replace_content` to update shortcode attributes (e.g., changing an ID). Be careful — shortcode syntax must remain valid after replacement.

## Workflows

### Find All Posts Using a Specific Shortcode
1. Call `search_content` with the shortcode tag as the search term (e.g., `contact-form-7`)
2. Report matching posts to the user

### Update a Shortcode Across the Site
1. Call `search_content` to find all posts containing the shortcode
2. Call `replace_content` with the old shortcode string and new shortcode string (requires confirmation)
3. Verify with another `search_content` that the replacement worked

## Relevant Wally Tools
- `search_content` — find posts containing specific shortcodes
- `replace_content` — update shortcode text across posts (requires confirmation)
- `get_post` — view a specific post's content to see its shortcodes
- `list_plugins` — check if the plugin providing the shortcode is active

## Important Notes
- Wally cannot register, modify, or remove shortcode handlers — that's PHP code
- If a shortcode shows as plain text `[like_this]`, the plugin providing it is likely inactive — check with `list_plugins`
- Shortcode attribute names are always lowercased by WordPress
- Shortcodes in excerpts are stripped by default — they only render in full content
- Broken shortcode output is usually caused by: missing plugin, changed shortcode ID, or invalid nesting
