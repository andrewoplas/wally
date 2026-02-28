## WordPress theme.json

The `theme.json` file is the central configuration file for block themes and block-aware classic themes. It configures global settings, styles, templates, and patterns through a standardized JSON format, replacing many uses of `add_theme_support()` and custom CSS.

---

### File Location & Schema

- Located at theme root: `{theme-directory}/theme.json`
- Child themes can have their own `theme.json` that merges with the parent's
- Schema declaration and version are required at the top level

```json
{
    "$schema": "https://schemas.wp.org/trunk/theme.json",
    "version": 3,
    "settings": {},
    "styles": {},
    "templateParts": [],
    "customTemplates": [],
    "patterns": []
}
```

**Schema versions:**
- `version: 2` -- WordPress 5.9+ (original stable version)
- `version: 3` -- WordPress 6.6+ (current, adds new capabilities)

---

### Top-Level Keys

| Key | Type | Purpose |
|-----|------|---------|
| `$schema` | string | JSON schema URL for IDE validation |
| `version` | int | Schema version (2 or 3) |
| `settings` | object | Configure editor options and design presets |
| `styles` | object | Apply CSS styles globally, per-element, or per-block |
| `templateParts` | array | Register template parts (header, footer, sidebar areas) |
| `customTemplates` | array | Register custom page/post templates |
| `patterns` | array | Reference patterns from the WordPress pattern directory |

---

### Settings

Settings control which design tools are available in the editor and define preset values.

#### settings.color

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `background` | bool | `true` | Allow background color changes |
| `text` | bool | `true` | Allow text color changes |
| `link` | bool | `false` | Allow link color customization |
| `custom` | bool | `true` | Allow custom color picker (beyond presets) |
| `customGradient` | bool | `true` | Allow custom gradient creation |
| `customDuotone` | bool | `true` | Allow custom duotone filters |
| `defaultPalette` | bool | `true` | Include WordPress default color palette |
| `defaultGradients` | bool | `true` | Include WordPress default gradients |
| `defaultDuotone` | bool | `true` | Include WordPress default duotone filters |
| `palette` | array | `[]` | Custom color presets |
| `gradients` | array | `[]` | Custom gradient presets |
| `duotone` | array | `[]` | Custom duotone filter presets |

**Palette item structure:**
```json
{
    "color": "#1e1e1e",
    "name": "Charcoal",
    "slug": "charcoal"
}
```

**Gradient item structure:**
```json
{
    "gradient": "linear-gradient(135deg, #1e1e1e 0%, #ffffff 100%)",
    "name": "Charcoal to White",
    "slug": "charcoal-to-white"
}
```

#### settings.typography

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `customFontSize` | bool | `true` | Allow custom font size input |
| `dropCap` | bool | `true` | Enable drop-cap styling for paragraphs |
| `fluid` | bool | `false` | Enable viewport-based fluid font scaling |
| `fontStyle` | bool | `true` | Allow font style selection |
| `fontWeight` | bool | `true` | Allow font weight selection |
| `letterSpacing` | bool | `false` | Allow letter spacing input |
| `lineHeight` | bool | `false` | Allow line height input |
| `textColumns` | bool | `false` | Allow text column options |
| `textDecoration` | bool | `true` | Allow text decoration (underline, strikethrough) |
| `textTransform` | bool | `true` | Allow text transform (uppercase, lowercase, capitalize) |
| `writingMode` | bool | `false` | Allow horizontal/vertical text orientation |
| `fontFamilies` | array | `[]` | Custom font family presets |
| `fontSizes` | array | `[]` | Custom font size presets |

**fontFamilies item structure:**
```json
{
    "name": "Inter",
    "slug": "inter",
    "fontFamily": "Inter, sans-serif",
    "fontFace": [
        {
            "fontFamily": "Inter",
            "fontWeight": "400",
            "fontStyle": "normal",
            "src": ["file:./assets/fonts/inter-regular.woff2"]
        }
    ]
}
```

**fontSizes item structure:**
```json
{
    "name": "Large",
    "slug": "large",
    "size": "2.25rem",
    "fluid": {
        "min": "1.75rem",
        "max": "2.25rem"
    }
}
```

#### settings.spacing

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `blockGap` | bool\|null | `true` | Block spacing control (`true` = UI + CSS, `false` = CSS only, `null` = disabled) |
| `margin` | bool | `false` | Enable margin controls |
| `padding` | bool | `false` | Enable padding controls |
| `customSpacingSize` | bool | `true` | Allow custom spacing values beyond presets |
| `units` | array | `["px","em","rem","vh","vw","%"]` | Available CSS units |
| `spacingScale` | object | -- | Auto-generate spacing presets from a mathematical scale |
| `spacingSizes` | array | `[]` | Manually defined spacing presets |

**spacingScale structure:**
```json
{
    "operator": "*",
    "increment": 1.5,
    "steps": 7,
    "mediumStep": 1.5,
    "unit": "rem"
}
```

#### settings.layout

| Key | Type | Description |
|-----|------|-------------|
| `contentSize` | string | Default content width (e.g., `"40rem"`, `"720px"`) |
| `wideSize` | string | Wide alignment width (e.g., `"64rem"`, `"1200px"`) |

#### settings.border

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `color` | bool | `false` | Allow border color |
| `radius` | bool | `false` | Allow border radius |
| `style` | bool | `false` | Allow border style |
| `width` | bool | `false` | Allow border width |

#### settings.shadow

| Key | Type | Description |
|-----|------|-------------|
| `defaultPresets` | bool | Include WordPress default shadow presets |
| `presets` | array | Custom shadow presets (`name`, `slug`, `shadow` CSS value) |

#### settings.dimensions

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `minHeight` | bool | `false` | Enable minimum height control |

#### settings.position

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `sticky` | bool | `false` | Enable sticky positioning for blocks |

#### settings.custom

Arbitrary key-value pairs output as CSS custom properties:

```json
{
    "settings": {
        "custom": {
            "line-height": {
                "body": 1.7,
                "heading": 1.3
            }
        }
    }
}
```

Generates: `--wp--custom--line-height--body: 1.7` and `--wp--custom--line-height--heading: 1.3`

#### settings.appearanceTools

Setting `"appearanceTools": true` is a shorthand that enables: `border` (all), `color.link`, `dimensions.minHeight`, `position.sticky`, `spacing.blockGap`, `spacing.margin`, `spacing.padding`, and `typography.lineHeight`.

#### settings.blocks

Per-block settings override global settings:

```json
{
    "settings": {
        "blocks": {
            "core/paragraph": {
                "typography": {
                    "fontSizes": [
                        { "name": "Small", "slug": "small", "size": "0.875rem" }
                    ]
                }
            }
        }
    }
}
```

#### settings.useRootPaddingAwareAlignments

When `true`, applies root-level padding while allowing full-width aligned blocks to stretch edge-to-edge.

---

### Styles

Styles apply CSS rules at three levels: global, per-element, and per-block.

#### Global Styles

```json
{
    "styles": {
        "color": {
            "text": "#1e1e1e",
            "background": "#ffffff"
        },
        "typography": {
            "fontFamily": "var(--wp--preset--font-family--inter)",
            "fontSize": "var(--wp--preset--font-size--medium)",
            "fontStyle": "normal",
            "fontWeight": "400",
            "lineHeight": "1.7",
            "letterSpacing": "0",
            "textDecoration": "none",
            "textTransform": "none"
        },
        "spacing": {
            "margin": { "top": "0", "bottom": "0" },
            "padding": { "top": "2rem", "right": "2rem", "bottom": "2rem", "left": "2rem" },
            "blockGap": "1.5rem"
        },
        "border": {
            "color": "#cccccc",
            "radius": "4px",
            "style": "solid",
            "width": "1px"
        },
        "shadow": "0 2px 4px rgba(0,0,0,0.1)",
        "filter": {
            "duotone": "var(--wp--preset--duotone--blue-orange)"
        },
        "outline": {
            "color": "#0073aa",
            "offset": "2px",
            "style": "dotted",
            "width": "2px"
        }
    }
}
```

#### Element-Level Styles

Target specific HTML elements:

```json
{
    "styles": {
        "elements": {
            "link": {
                "color": { "text": "#0073aa" },
                ":hover": { "color": { "text": "#005177" } },
                ":focus": { "color": { "text": "#005177" } }
            },
            "heading": {
                "typography": { "fontWeight": "700", "lineHeight": "1.3" }
            },
            "h1": {
                "typography": { "fontSize": "var(--wp--preset--font-size--xx-large)" }
            },
            "h2": {
                "typography": { "fontSize": "var(--wp--preset--font-size--x-large)" }
            },
            "h3": {
                "typography": { "fontSize": "var(--wp--preset--font-size--large)" }
            },
            "h4": {},
            "h5": {},
            "h6": {},
            "button": {
                "color": { "text": "#ffffff", "background": "#0073aa" },
                "border": { "radius": "4px" },
                ":hover": { "color": { "background": "#005177" } }
            },
            "caption": {
                "typography": { "fontSize": "0.875rem" },
                "color": { "text": "#6b7280" }
            }
        }
    }
}
```

Available elements: `link`, `heading`, `h1`-`h6`, `button`, `caption`, `cite`

#### Block-Level Styles

Target specific blocks by slug:

```json
{
    "styles": {
        "blocks": {
            "core/code": {
                "color": { "text": "#f8f8f2", "background": "#282a36" },
                "typography": { "fontFamily": "monospace" },
                "spacing": { "padding": { "top": "1.5rem", "bottom": "1.5rem" } }
            },
            "core/quote": {
                "border": { "width": "4px", "style": "solid", "color": "#0073aa" }
            },
            "core/button": {
                "color": { "background": "#0073aa" },
                "border": { "radius": "6px" }
            }
        }
    }
}
```

Blocks can also define element styles within them:

```json
{
    "styles": {
        "blocks": {
            "core/group": {
                "elements": {
                    "link": { "color": { "text": "#ff0000" } }
                }
            }
        }
    }
}
```

---

### CSS Custom Properties

WordPress auto-generates CSS custom properties from settings presets:

| Setting type | CSS property pattern | Example |
|-------------|---------------------|---------|
| Color palette | `--wp--preset--color--{slug}` | `--wp--preset--color--primary` |
| Gradient | `--wp--preset--gradient--{slug}` | `--wp--preset--gradient--vivid-cyan` |
| Duotone | `--wp--preset--duotone--{slug}` | `--wp--preset--duotone--blue-orange` |
| Font size | `--wp--preset--font-size--{slug}` | `--wp--preset--font-size--large` |
| Font family | `--wp--preset--font-family--{slug}` | `--wp--preset--font-family--inter` |
| Spacing | `--wp--preset--spacing--{slug}` | `--wp--preset--spacing--40` |
| Shadow | `--wp--preset--shadow--{slug}` | `--wp--preset--shadow--natural` |
| Custom values | `--wp--custom--{path}` | `--wp--custom--line-height--body` |

Use these in styles with `var()`:
```json
"color": { "text": "var(--wp--preset--color--charcoal)" }
```

---

### Template Parts

Register template parts for block themes:

```json
{
    "templateParts": [
        { "name": "header", "title": "Header", "area": "header" },
        { "name": "footer", "title": "Footer", "area": "footer" },
        { "name": "sidebar", "title": "Sidebar", "area": "uncategorized" }
    ]
}
```

- `name` -- filename without extension (maps to `parts/{name}.html`)
- `title` -- human-readable label in the editor
- `area` -- `header`, `footer`, or `uncategorized` (determines placement in Site Editor)

---

### Custom Templates

Register custom templates for specific post types:

```json
{
    "customTemplates": [
        {
            "name": "page-no-title",
            "title": "Page Without Title",
            "postTypes": ["page"]
        },
        {
            "name": "blank",
            "title": "Blank Canvas",
            "postTypes": ["page", "post"]
        }
    ]
}
```

- `name` -- filename without extension (maps to `templates/{name}.html`)
- `title` -- label shown in template selector
- `postTypes` -- array of post types this template applies to (default: `["page"]`)

---

### Patterns

Reference patterns from the WordPress pattern directory by slug:

```json
{
    "patterns": [
        "short-text-and-image",
        "heading-and-paragraph"
    ]
}
```

Pattern slugs reference patterns registered at wordpress.org/patterns/.

---

### Layers: Core, Theme, User

WordPress applies theme.json data in three cascading layers:

1. **Core** -- WordPress default settings and styles (`wp-includes/theme.json`)
2. **Theme** -- Your theme's `theme.json` overrides core defaults
3. **User** -- Global Styles customizations in the Site Editor override both core and theme

Each layer merges on top of the previous one. Theme settings override core defaults, and user customizations override theme settings.

---

### WP_Theme_JSON Class

Internal WordPress class that processes theme.json data. Intended for core usage; the API may change.

**Constants:**
- `ROOT_CSS_PROPERTIES_SELECTOR` = `:root` -- selector for top-level preset properties
- `ROOT_BLOCK_SELECTOR` = `body` -- selector for top-level styles
- `VALID_ORIGINS` = `['default', 'blocks', 'theme', 'custom']`

**Key methods:**

| Method | Description |
|--------|-------------|
| `get_data()` | Returns valid theme.json data as provided by the theme |
| `get_raw_data()` | Returns unprocessed theme.json data |
| `get_settings()` | Returns resolved settings for each block |
| `get_stylesheet()` | Generates the full CSS stylesheet from theme.json |
| `get_styles_for_block()` | Returns CSS rules for a specific block |
| `get_block_styles()` | Converts style sections into CSS rulesets |
| `get_css_variables()` | Converts settings into CSS custom property declarations |
| `get_preset_classes()` | Creates CSS classes for preset values |
| `get_settings_slugs()` | Returns preset slugs |
| `get_settings_values_by_slug()` | Returns preset values keyed by slug |
| `get_blocks_metadata()` | Returns metadata for all registered blocks |
| `merge()` | Merges incoming theme.json data |
| `sanitize()` | Validates data against the schema |
| `remove_insecure_properties()` | Strips unsafe properties |
| `resolve_variables()` | Resolves CSS variable references to actual values |

---

### Filters

| Filter | Description |
|--------|-------------|
| `wp_theme_json_data_default` | Modify core/default layer data |
| `wp_theme_json_data_blocks` | Modify block-provided data |
| `wp_theme_json_data_theme` | Modify theme layer data |
| `wp_theme_json_data_user` | Modify user/Global Styles layer data |

```php
add_filter( 'wp_theme_json_data_theme', function( $theme_json ) {
    $data = $theme_json->get_data();
    $data['settings']['color']['palette']['theme'][] = [
        'color' => '#bada55',
        'name'  => 'Accent',
        'slug'  => 'accent',
    ];
    return $theme_json->update_with( $data );
});
```

---

### Block Supports & theme.json

Theme.json settings map to block support declarations. When a setting is disabled in theme.json, the corresponding block support UI is hidden:

| theme.json setting | Block support | Effect |
|-------------------|---------------|--------|
| `settings.color.text` | `color.text` | Text color picker |
| `settings.color.background` | `color.background` | Background color picker |
| `settings.typography.fontSize` | `typography.fontSize` | Font size control |
| `settings.spacing.padding` | `spacing.padding` | Padding controls |
| `settings.border.radius` | `__experimentalBorder.radius` | Border radius control |

---

### theme.json vs add_theme_support() Migration

| `add_theme_support()` call | theme.json equivalent |
|---------------------------|----------------------|
| `add_theme_support('editor-color-palette', [...])` | `settings.color.palette` |
| `add_theme_support('editor-gradient-presets', [...])` | `settings.color.gradients` |
| `add_theme_support('disable-custom-colors')` | `settings.color.custom: false` |
| `add_theme_support('disable-custom-gradients')` | `settings.color.customGradient: false` |
| `add_theme_support('editor-font-sizes', [...])` | `settings.typography.fontSizes` |
| `add_theme_support('disable-custom-font-sizes')` | `settings.typography.customFontSize: false` |
| `add_theme_support('custom-line-height')` | `settings.typography.lineHeight: true` |
| `add_theme_support('custom-spacing')` | `settings.spacing.padding/margin: true` |
| `add_theme_support('custom-units', [...])` | `settings.spacing.units` |
| `add_theme_support('editor-styles')` | Automatic in block themes |
| `add_theme_support('wp-block-styles')` | Automatic in block themes |
| `add_theme_support('responsive-embeds')` | Automatic in block themes |
| Content width via `$content_width` global | `settings.layout.contentSize` |

When `theme.json` is present, its settings take precedence over `add_theme_support()` calls for overlapping features.

---

### Minimal Example

```json
{
    "$schema": "https://schemas.wp.org/trunk/theme.json",
    "version": 3,
    "settings": {
        "appearanceTools": true,
        "layout": {
            "contentSize": "720px",
            "wideSize": "1200px"
        },
        "color": {
            "defaultPalette": false,
            "palette": [
                { "color": "#1e1e1e", "name": "Primary", "slug": "primary" },
                { "color": "#ffffff", "name": "Base", "slug": "base" },
                { "color": "#0073aa", "name": "Accent", "slug": "accent" }
            ]
        },
        "typography": {
            "fontFamilies": [
                {
                    "name": "System Sans",
                    "slug": "system-sans",
                    "fontFamily": "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
                }
            ],
            "fontSizes": [
                { "name": "Small", "slug": "small", "size": "0.875rem" },
                { "name": "Medium", "slug": "medium", "size": "1rem" },
                { "name": "Large", "slug": "large", "size": "1.5rem" },
                { "name": "Extra Large", "slug": "x-large", "size": "2.25rem" }
            ]
        }
    },
    "styles": {
        "color": {
            "text": "var(--wp--preset--color--primary)",
            "background": "var(--wp--preset--color--base)"
        },
        "typography": {
            "fontFamily": "var(--wp--preset--font-family--system-sans)",
            "fontSize": "var(--wp--preset--font-size--medium)",
            "lineHeight": "1.7"
        },
        "elements": {
            "link": {
                "color": { "text": "var(--wp--preset--color--accent)" }
            },
            "heading": {
                "typography": { "fontWeight": "700", "lineHeight": "1.3" }
            }
        }
    },
    "templateParts": [
        { "name": "header", "title": "Header", "area": "header" },
        { "name": "footer", "title": "Footer", "area": "footer" }
    ]
}
```
