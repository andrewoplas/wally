# WordPress Internationalization (i18n)

## When to Use
- User asks about translations, language files, or multilingual support at the code level
- User asks about text domains, .po/.mo files, or translation functions
- User troubleshoots missing translations or untranslated strings

## Key Patterns

### How WordPress Translations Work
- **Text domain**: unique identifier per plugin/theme (must match directory slug)
- **Translation files**: `.pot` (template), `.po` (human-readable), `.mo` (binary, loaded at runtime), `.json` (JS translations)
- **File naming**: `{text-domain}-{locale}.mo` (e.g., `my-plugin-fr_FR.mo`)
- **Location**: typically `{plugin}/languages/` or `{theme}/languages/`

### Core Translation Functions (PHP)
| Function | Purpose |
|----------|---------|
| `__($text, $domain)` | Return translated string |
| `_e($text, $domain)` | Echo translated string |
| `esc_html__($text, $domain)` | Return translated + HTML-escaped |
| `esc_attr__($text, $domain)` | Return translated + attribute-escaped |
| `_n($single, $plural, $count, $domain)` | Pluralization |
| `_x($text, $context, $domain)` | Translation with disambiguation context |
| `sprintf(__('Hello %s', $domain), $name)` | Translation with placeholders |

### JavaScript Translations
- Uses `wp.i18n` package (`wp-i18n` script dependency)
- `wp_set_script_translations($handle, $domain, $path)` connects JSON translation files
- Available JS functions: `__()`, `_n()`, `_x()`, `sprintf()`

### Site Language Setting
- Stored in `wp_options` as `WPLANG` (e.g., `fr_FR`, `de_DE`, empty string = English)
- Readable via `get_option` with key `WPLANG`
- Also: `get_locale()` returns current locale

### WP-CLI Translation Commands
- `wp i18n make-pot .` — extract translatable strings into .pot file
- `wp i18n make-json languages/` — generate JSON for JS translations
- `wp i18n make-mo languages/` — compile .po to .mo

### Since WP 6.7
WordPress.org-hosted plugins use just-in-time translation loading — manual `load_plugin_textdomain()` is no longer required.

## Relevant Wally Tools
- `get_option` with key `WPLANG` — check site language
- `get_site_info` — returns site language setting
- `list_plugins` — check for translation plugins (Loco Translate, WPML, Polylang)

## Important Notes
- Wally cannot modify translation files or create translations — this is developer/translator work
- If user reports untranslated strings, check: 1) plugin has .mo file for their locale, 2) text domain matches, 3) file is in correct location
- For user-facing multilingual content (not code translations), see the multilingual-plugins knowledge file
- Text domains must be string literals, not variables — tools cannot extract strings from variable domains
