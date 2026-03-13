# Multilingual Plugins

## When to Use
- User mentions WPML, Polylang, TranslatePress, GTranslate, or translations
- User wants to manage multilingual content, check language settings, or find translated versions
- User asks about language switcher, default language, or translation status

## Available Tools
- `list_plugins` — detect which multilingual plugin is active
- `list_posts` — list posts (returns current language posts when WPML/Polylang active)
- `get_post` — get post details (may include translation metadata)
- `create_post` — create content (will be in default language unless translation is linked separately)
- `get_option` — read multilingual plugin settings
- `search_content` — search content across posts

## Workflows

### Detect Active Plugin
1. Call `list_plugins`
2. Look for: `sitepress-multilingual-cms` (WPML), `polylang`, `translatepress-multilingual`, `gtranslate`

### WPML — Read Language Settings
1. Call `get_option` with key `icl_sitepress_settings`
2. Key settings: `default_language`, `active_languages`, `negotiation_type` (1=directory, 2=domain, 3=parameter)

### Polylang — Read Language Settings
1. Call `get_option` with key `polylang`
2. Key settings: `default_lang`, `force_lang` (0=param, 1=directory, 2=subdomain, 3=domains)
3. Call `get_option` with key `polylang_languages` for list of configured languages

### TranslatePress — Read Settings
1. Call `get_option` with key `trp_settings`
2. Key settings: `default-language`, `translation-languages`, `url-slugs`

### GTranslate — Read Settings
1. Call `get_option` with key `GTranslate`
2. Key settings: `default_language`, `languages`, `widget_look`

### Check Translation Status of a Post
1. Call `get_post` with the post ID
2. For WPML/Polylang: translation links are stored in custom tables/taxonomies — not directly visible in post meta
3. Tell user which multilingual plugin is active and suggest checking translations in the plugin's admin UI

### Create Content in Default Language
1. Call `create_post` with standard parameters
2. The post is created in the default language
3. Tell user: "Post created in the default language. To add translations, go to the post editor where the multilingual plugin provides translation options."

## Important Notes
- WPML and Polylang create separate posts per language — `list_posts` returns only current-language posts by default
- TranslatePress uses a single post per content item with translations in separate database tables
- GTranslate (free) translates on-the-fly via Google Translate — no separate posts created
- Wally cannot create translation links between posts, switch languages, or manage translation workflows — guide user to the plugin's translation editor
- When WPML/Polylang is active, post queries are automatically filtered by language — results may not show all content
- For language switcher configuration, URL structure changes, or translation management, guide user to the plugin's admin settings
- Do NOT expose translation API keys (DeepL, Google Translate) stored in plugin settings
