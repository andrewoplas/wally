## Multilingual Plugins

### WPML (WordPress Multilingual Plugin)
- **Core table**: `{prefix}icl_translations` — links all translated content. Key columns: `element_id`, `element_type` (e.g., `post_page`, `post_post`, `tax_category`), `language_code`, `source_language_code` (NULL for originals), `trid` (translation group ID — all translations of the same content share a trid).
- **Additional tables**: `{prefix}icl_strings` (string translations), `{prefix}icl_string_translations` (translated strings), `{prefix}icl_languages` (language definitions), `{prefix}icl_locale_map`.
- **Settings**: wp_options key `icl_sitepress_settings` (serialized). Key sub-keys: `default_language`, `active_languages`, `negotiation_type` (1=directory, 2=domain, 3=parameter), `urls`.
- **Key API filters and actions** (all use `wpml_` prefix):
  - `apply_filters('wpml_object_id', $post_id, $post_type, true, $lang)` — get translated post/page ID. The `true` param returns original if translation missing.
  - `apply_filters('wpml_active_languages', null, $args)` — get array of active languages
  - `apply_filters('wpml_current_language', null)` — get current language code
  - `apply_filters('wpml_default_language', null)` — get default language code
  - `apply_filters('wpml_element_trid', null, $post_id, $element_type)` — get translation group ID
  - `apply_filters('wpml_get_element_translations', null, $trid, $element_type)` — get all translations in group
  - `do_action('wpml_switch_language', $lang)` — temporarily switch active language (use null to reset)
  - `do_action('wpml_add_language_selector')` — output language switcher
- **String translation**: Register with `do_action('wpml_register_single_string', $context, $name, $value)`. Retrieve with `apply_filters('wpml_translate_single_string', $value, $context, $name, $lang)`.
- **URL conversion**: `apply_filters('wpml_permalink', $url, $lang)` — get URL in specific language.

### Polylang
- **Data model**: Uses WordPress taxonomies. Language defined as taxonomy `language` (each language is a term). Translations linked via `post_translations` and `term_translations` taxonomies — each group of translations shares a term with a serialized array mapping language codes to post/term IDs.
- **Settings**: wp_options key `polylang` (serialized). Sub-keys: `default_lang`, `force_lang` (0=URL param, 1=directory, 2=subdomain, 3=separate domains), `hide_default`, `rewrite`, `domains`.
- **Languages list**: wp_options key `polylang_languages` — cached array of language objects.
- **Functions** (available after `plugins_loaded`):
  - `pll_current_language($field)` — current language ('slug', 'name', 'locale')
  - `pll_default_language($field)` — default language
  - `pll_get_post($post_id, $lang)` — get translated post ID in target language
  - `pll_get_term($term_id, $lang)` — get translated term ID in target language
  - `pll_the_languages($args)` — output/return language switcher
  - `pll_home_url($lang)` — get home URL for language
  - `pll_register_string($name, $string, $context, $multiline)` — register string for translation
  - `pll_translate_string($string, $lang)` — get translated string
  - `pll_count_posts($lang, $args)` — count posts in a language
  - `pll_languages_list($args)` — list of language slugs
- **Creating translated content**: `pll_set_post_language($post_id, $lang)` — assign language. `pll_save_post_translations($translations)` — link translations (array of lang => post_id).

### TranslatePress
- **Data model**: Single post per content item (not duplicated per language). Translations stored in custom dictionary tables.
- **Translation tables**: `{prefix}trp_dictionary_{lang_code}` — one table per language. Columns: `id`, `original`, `translated`, `status` (0=not translated, 1=human, 2=machine), `block_type`.
- **Settings**: wp_options key `trp_settings` (serialized). Key sub-keys: `default-language`, `translation-languages` (array of active language codes), `url-slugs` (lang code to URL slug mapping), `publish-languages`, `native_or_english_name`.
- **How it works**: Translates rendered HTML output. Hooks into `the_content`, `the_title`, `gettext`, and output buffering to replace strings. Does not create duplicate posts.
- **Functions**:
  - `trp_get_languages()` — get active languages
  - `TRP_Translate_Press::get_trp_instance()` — singleton accessor
  - Translation editing happens in visual frontend editor — not typical programmatic workflow
- **Automatic translation**: Supports Google Translate and DeepL APIs. Keys stored in `trp_machine_translation_settings` option.

### GTranslate
- **How it works**: Uses Google Translate to automatically translate page content on the fly. Free version uses Google Translate's JavaScript widget for client-side translation. Paid (Enterprise) version creates SEO-friendly translated pages with subdirectories, subdomains, or separate domains.
- **Settings**: wp_options key `GTranslate` (serialized). Key sub-keys: `pro_version`, `enterprise_version`, `url_translation`, `add_hreflang_tags`, `default_language`, `languages` (comma-separated language codes), `widget_look` (dropdown, flags, popup, etc.), `flag_size` (16, 24, 32, 48), `monochrome_flags`, `native_language_names`.
- **Widget output**: Language switcher rendered via widget `GTranslate_Widget` or shortcode `[gtranslate]`. The widget injects Google Translate JavaScript and a `<div class="gtranslate_wrapper">` element.
- **URL structures** (paid versions):
  - Subdirectory: `example.com/es/page-slug` (Enterprise)
  - Subdomain: `es.example.com/page-slug` (Enterprise)
  - URL parameter: `example.com/?lang=es` (free/Pro)
- **CSS classes**: `.gtranslate_wrapper` (container), `.gt_switcher` (switcher element), `.gt-current-lang` (active language), `.glink` (language link), `.gt_float_switcher` (floating widget). Flag images use `.gt_flag` with language code classes (e.g., `.gt_flag.gt-flag-es`).
- **JavaScript widget code**: Inserts `<script>window.gtranslateSettings = {...}</script>` and loads `https://cdn.gtranslate.net/widgets/latest/float.js` (or other widget JS based on `widget_look`).
- **Key differences — Free vs Paid**:
  - Free: Client-side Google Translate, no SEO benefit, `?lang=` parameter, no URL translation
  - Pro: Server-side proxy translation, URL parameter mode, some SEO benefit
  - Enterprise: Full subdirectory/subdomain URL structure, search engine indexable, hreflang tags, URL/slug translation, meta tag translation
- **Hooks**:
  - `gtranslate_widget_output` — filter the widget HTML output
  - `gtranslate_exclude_selectors` — CSS selectors to exclude from translation
- **Detection**:
  ```php
  defined('GT_VERSION') // true if GTranslate is active
  class_exists('GTranslate') // alternative check
  ```
- **Customization**: Add `notranslate` class to any HTML element to exclude it from translation. Use `<meta name="google" content="notranslate">` to exclude entire page.

### Common Patterns
- **WPML and Polylang** create separate posts per language, linked by translation group IDs. Content queries return only current language posts by default.
- **TranslatePress** uses a single post with translations in separate tables — simpler data model but less flexible for per-language content differences.
- **URL structures**: All three support subdirectory (/en/, /fr/), subdomain (en.site.com), or parameter (?lang=en). WPML also supports separate domains.
- **When querying posts programmatically**: WPML and Polylang filter WP_Query by default to return current language only. Use `'suppress_filters' => true` or language-switch actions to query across languages.
- **Menus**: WPML syncs menu items across languages. Polylang creates separate menus per language assigned via language-specific menu locations. TranslatePress translates menu items inline.
- **Switching language in code**: WPML uses `do_action('wpml_switch_language', $lang)`. Polylang uses `PLL()->curlang = PLL()->model->get_language($lang)` (less formal API). Always restore original language after temporary switches.
