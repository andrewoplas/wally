# Tool-Aware Knowledge Injection

## Problem

The current knowledge injection system relies solely on regex-based intent classification of the user's message. This means the LLM only receives knowledge about topics the user explicitly mentions.

**Example:** A user says "create a new post type" on a site with ACF Pro active. The intent classifier matches `content` (via the "custom post type" pattern), so the LLM gets `content.md` which only covers `register_post_type()` — the PHP code approach. It never receives `acf.md`, so it doesn't know ACF can create post types via `acf_create_post_type`. The LLM sees the tool in its tool list, but lacks the context to know when and why to prefer it.

This problem applies broadly: any plugin capability that overlaps with a generic WordPress task (WooCommerce products vs custom post types, Elementor content editing vs standard post_content, etc.) risks being invisible to the LLM unless the user names the plugin.

**Scale of the problem:** With the upcoming tool expansion (see `.ralph/fix_plan.md`), we'll go from 6 tool categories to 30+. A site could have WooCommerce, ACF, Gravity Forms, Yoast, and Elementor all active — the LLM needs to know the full picture of what it can do without the user naming every plugin.

## Current Architecture

```
User message
  → IntentClassifierService (regex patterns against message text)
  → Returns up to 4 intent keys (e.g. ['content', 'general'])
  → KnowledgeLoaderService loads matching .md files
  → PromptBuilderService assembles system prompt:
      1. Base instructions (always)
      2. WordPress Knowledge (intent-matched, up to 4 files)
      3. Site Context (from site_profile)
      4. Custom Instructions (optional)
```

**What's missing:** The system never considers which tools/plugins are actually available on the site when deciding what knowledge to inject.

### Current tool categories

These are the categories that exist today:

| Category | Tools | Core? |
|----------|-------|-------|
| `content` | list_posts, get_post, create_post, update_post, delete_post, list_categories, list_tags, create_category, create_tag | Yes |
| `site` | get_site_info, get_site_health, get_option, update_option | Yes |
| `plugins` | list_plugins, install_plugin, activate_plugin, deactivate_plugin, update_plugin, delete_plugin | Yes |
| `search` | search_content, replace_content | Yes |
| `elementor` | elementor_search_content, elementor_replace_content, elementor_get_page_structure, elementor_clear_css_cache | No |
| `acf` | 21 ACF tools (post types, taxonomies, field groups, field values, options pages) | No |

## Upcoming Tool Expansion

The fix plan (`.ralph/fix_plan.md`) adds tools across 4 tiers. Each tool file introduces a new category. Below is the full mapping of new categories to their tool files and knowledge files.

### Tier 1: Core WordPress (new core categories)

| Category | Tool file | Knowledge file | Tools |
|----------|-----------|----------------|-------|
| `users` | `class-user-tools.php` | `users.md` | list_users, get_user, create_user, update_user, delete_user, update_user_role |
| `menus` | `class-menu-tools.php` | `menus.md` | list_menus, get_menu, create_menu, delete_menu, add_menu_item, update_menu_item, delete_menu_item |
| `media` | `class-media-tools.php` | `media.md` | list_media, get_media, update_media, delete_media |
| `comments` | `class-comment-tools.php` | `wp-comments.md` | list_comments, get_comment, update_comment_status, delete_comment, reply_to_comment |

### Tier 2: High-value plugins

| Category | Tool file | Knowledge file | Tools |
|----------|-----------|----------------|-------|
| `woocommerce` | `class-woocommerce-tools.php` | `woocommerce.md` | list_products, get_product, create_product, update_product, delete_product, list_orders, get_order, update_order_status, list_coupons, get_coupon |
| `gravity-forms` | `class-gravity-forms-tools.php` | `gravity-forms.md` | list_forms, get_form, list_entries, get_entry, delete_entry, update_entry_status |
| `contact-form-7` | `class-contact-form-7-tools.php` | `contact-form-7.md` | list_contact_forms, get_contact_form, update_contact_form |
| `yoast-seo` | `class-yoast-seo-tools.php` | `yoast-seo.md` | get_yoast_meta, update_yoast_meta, get_yoast_indexables |
| `rank-math` | `class-rank-math-tools.php` | `rank-math.md` | get_rank_math_meta, update_rank_math_meta |
| `redirection` | `class-redirection-tools.php` | `redirection.md` | list_redirects, create_redirect, update_redirect, delete_redirect, get_404_logs |

### Tier 3: Forms & other plugins

| Category | Tool file | Knowledge file | Tools |
|----------|-----------|----------------|-------|
| `wpforms` | `class-wpforms-tools.php` | `forms-general.md` | list_wpforms, get_wpform, list_wpform_entries |
| `jetpack` | `class-jetpack-tools.php` | `jetpack.md` | list_jetpack_modules, activate_jetpack_module, deactivate_jetpack_module, get_jetpack_stats |
| `events` | `class-events-tools.php` | `events-plugins.md` | list_events, get_event, create_event, update_event, delete_event |
| `backup` | `class-backup-tools.php` | `backup-plugins.md` | list_updraftplus_backups, trigger_updraftplus_backup, get_updraftplus_settings |
| `caching` | `class-caching-tools.php` | `caching-plugins.md` | clear_cache, get_cache_settings |
| `security` | `class-security-plugin-tools.php` | `security-plugins.md` | get_wordfence_scan_status, list_wordfence_blocked_ips, run_wordfence_scan |
| `woocommerce-extensions` | `class-woocommerce-extensions-tools.php` | `woocommerce-extensions.md` | list_subscriptions, get_subscription, update_subscription_status |

### Tier 4: Additional plugins

| Category | Tool file | Knowledge file | Tools |
|----------|-----------|----------------|-------|
| `ecommerce` | `class-ecommerce-tools.php` | `ecommerce-plugins.md` | list_edd_downloads, get_edd_download, list_edd_payments, list_memberpress_memberships, list_learndash_courses |
| `analytics` | `class-analytics-tools.php` | `analytics-plugins.md` | get_site_kit_stats, get_monsterinsights_stats |
| `email-marketing` | `class-email-marketing-tools.php` | `email-marketing.md` | list_mailchimp_lists, get_mailchimp_subscribers, list_optinmonster_campaigns |
| `multilingual` | `class-multilingual-tools.php` | `multilingual-plugins.md` | list_wpml_languages, get_wpml_translation_status, list_polylang_languages |
| `page-builders` | `class-page-builder-tools.php` | `page-builders.md` | beaver_builder_search_content, beaver_builder_get_layout, divi_search_content, divi_get_layout |
| `image-optimization` | `class-image-optimization-tools.php` | `image-optimization.md` | get_smush_stats, bulk_smush_status, get_ewww_stats |
| `tablepress` | `class-tablepress-tools.php` | `content-plugins.md` | list_tables, get_table, update_table_cell |
| `slider` | `class-slider-tools.php` | `slider-plugins.md` | list_sliders, get_slider, update_slider_status |
| `audit-log` | `class-audit-log-tools.php` | `audit-logging.md` | get_activity_log, get_activity_log_entry |
| `social` | `class-social-tools.php` | `social-plugins.md` | get_instagram_feed_settings, list_social_share_counts |
| `media-plugins` | `class-media-plugin-tools.php` | `media-plugins.md` | regenerate_thumbnails, get_regeneration_status |

## Proposed Solution: Capabilities Map

Add a lightweight, auto-generated "Available Capabilities" section to the system prompt that is derived from the tool definitions the plugin sends with every request.

### How It Works

1. The plugin already sends `tool_definitions` with every request — these only include tools for plugins that are actually active on the site (e.g., ACF tools are only present if ACF is installed).

2. Each tool already has a `category` field. After the tool expansion, there will be 30+ possible categories.

3. A new step in `PromptBuilderService` groups the incoming tools by category and generates a concise summary block for non-core categories.

### Category Classification: Core vs Plugin

Categories are split into two groups:

**Core categories** — always present on every WordPress site, excluded from the capabilities map:
- `content`, `site`, `plugins`, `search`
- `users`, `menus`, `media`, `comments` (Tier 1 tools from fix plan)

**Plugin categories** — only present when a specific plugin is active, included in the capabilities map:
- Everything else: `acf`, `elementor`, `woocommerce`, `gravity-forms`, `yoast-seo`, `rank-math`, `redirection`, `wpforms`, `jetpack`, `events`, `backup`, `caching`, `security`, `woocommerce-extensions`, `ecommerce`, `analytics`, `email-marketing`, `multilingual`, `page-builders`, `image-optimization`, `tablepress`, `slider`, `audit-log`, `social`, `media-plugins`

### Example Output

For a site with ACF Pro, WooCommerce, Elementor, and Yoast SEO active:

```
--- Available Capabilities ---
In addition to core WordPress tools, this site has the following plugin capabilities:

ACF (Advanced Custom Fields):
- Create, update, delete custom post types (acf_create_post_type, acf_update_post_type, acf_delete_post_type)
- Create, update, delete custom taxonomies (acf_create_taxonomy, acf_update_taxonomy, acf_delete_taxonomy)
- Manage field groups (acf_list_field_groups, acf_get_field_group, acf_create_field_group, acf_update_field_group, acf_delete_field_group)
- Read/update field values on posts, terms, users, and options pages
→ Prefer ACF tools for post type/taxonomy creation when ACF is available.

WooCommerce:
- Manage products (list_products, get_product, create_product, update_product, delete_product)
- Manage orders (list_orders, get_order, update_order_status)
- Manage coupons (list_coupons, get_coupon)
→ Use WooCommerce tools for product/order management instead of generic post tools. Products are WooCommerce objects, not regular posts.

Elementor:
- Search and replace content within Elementor widget data (elementor_search_content, elementor_replace_content)
- Inspect page widget structure (elementor_get_page_structure)
- Clear CSS cache after modifications (elementor_clear_css_cache)
→ Always check both standard post_content and Elementor data when editing page content.

Yoast SEO:
- Read/update SEO meta (get_yoast_meta, update_yoast_meta)
- Query indexables (get_yoast_indexables)
→ Use Yoast tools for SEO title, meta description, and focus keyword management.
```

### Key Design Decisions

**1. Auto-generated, not manually maintained**

The capabilities summary is derived from the tool definitions at request time. When a developer adds a new tool class in PHP with `get_category() => 'woocommerce'`, it automatically appears in the capabilities section. No backend changes needed.

**2. Category-to-label mapping**

A simple map provides human-readable labels and optional guidance hints per category:

```typescript
const CATEGORY_META: Record<string, { label: string; hint?: string }> = {
  // ── Existing plugin categories ─────────────────────────────────
  acf: {
    label: 'ACF (Advanced Custom Fields)',
    hint: 'Prefer ACF tools for post type/taxonomy creation when ACF is available.',
  },
  elementor: {
    label: 'Elementor',
    hint: 'Always check both standard post_content and Elementor data when editing page content.',
  },

  // ── Tier 2: High-value plugins ─────────────────────────────────
  woocommerce: {
    label: 'WooCommerce',
    hint: 'Use WooCommerce tools for product/order management instead of generic post tools. Products are WooCommerce objects, not regular posts.',
  },
  'gravity-forms': {
    label: 'Gravity Forms',
    hint: 'Use Gravity Forms tools to list forms, view entries, and manage submission data.',
  },
  'contact-form-7': {
    label: 'Contact Form 7',
    hint: 'Use CF7 tools to list and manage contact forms.',
  },
  'yoast-seo': {
    label: 'Yoast SEO',
    hint: 'Use Yoast tools for SEO title, meta description, and focus keyword management.',
  },
  'rank-math': {
    label: 'Rank Math',
    hint: 'Use Rank Math tools for SEO meta management. Similar to Yoast but uses different meta keys.',
  },
  redirection: {
    label: 'Redirection',
    hint: 'Use Redirection tools for managing URL redirects and viewing 404 logs.',
  },

  // ── Tier 3: Forms & other plugins ──────────────────────────────
  wpforms: {
    label: 'WPForms',
    hint: 'Use WPForms tools to list forms and view form entries.',
  },
  jetpack: {
    label: 'Jetpack',
    hint: 'Use Jetpack tools to manage modules and view site stats.',
  },
  events: {
    label: 'The Events Calendar',
    hint: 'Use Events tools to manage events instead of generic post tools. Events have venue, organizer, and date metadata.',
  },
  backup: {
    label: 'UpdraftPlus Backup',
    hint: 'Use backup tools to list existing backups and trigger new ones.',
  },
  caching: {
    label: 'Cache Management',
    hint: 'Use caching tools to clear site cache. Automatically detects the active caching plugin.',
  },
  security: {
    label: 'Wordfence Security',
    hint: 'Use security tools to check scan status and manage blocked IPs.',
  },
  'woocommerce-extensions': {
    label: 'WooCommerce Subscriptions',
    hint: 'Use subscription tools for managing WooCommerce subscription orders.',
  },

  // ── Tier 4: Additional plugins ─────────────────────────────────
  ecommerce: {
    label: 'E-commerce (EDD / MemberPress / LearnDash)',
    hint: 'Use these tools for digital downloads, memberships, and course management.',
  },
  analytics: {
    label: 'Analytics (Site Kit / MonsterInsights)',
    hint: 'Use analytics tools to view site traffic stats and reports.',
  },
  'email-marketing': {
    label: 'Email Marketing (Mailchimp / OptinMonster)',
    hint: 'Use email marketing tools to view lists, subscribers, and campaign data.',
  },
  multilingual: {
    label: 'Multilingual (WPML / Polylang)',
    hint: 'Use multilingual tools to view language settings and translation status.',
  },
  'page-builders': {
    label: 'Page Builders (Beaver Builder / Divi)',
    hint: 'Use page builder tools to search content and inspect layouts within non-Elementor page builders.',
  },
  'image-optimization': {
    label: 'Image Optimization (Smush / EWWW)',
    hint: 'Use image optimization tools to check compression stats and bulk optimization status.',
  },
  tablepress: {
    label: 'TablePress',
    hint: 'Use TablePress tools to list, view, and edit table data.',
  },
  slider: {
    label: 'Slider Revolution',
    hint: 'Use slider tools to list and manage slider configurations.',
  },
  'audit-log': {
    label: 'Activity Log (Simple History)',
    hint: 'Use audit log tools to review recent site activity and changes.',
  },
  social: {
    label: 'Social Media (Smash Balloon / AddToAny)',
    hint: 'Use social tools to view feed settings and share counts.',
  },
  'media-plugins': {
    label: 'Media Management (Regenerate Thumbnails)',
    hint: 'Use media plugin tools to regenerate image thumbnails.',
  },
};

// Core categories — always present, excluded from capabilities map
const CORE_CATEGORIES = new Set([
  'content', 'site', 'plugins', 'search',
  'users', 'menus', 'media', 'comments',
]);
```

Categories not in `CATEGORY_META` but also not in `CORE_CATEGORIES` get a fallback: the category name is title-cased as the label, and tools are listed without a hint. This makes the system forward-compatible — a new tool file with `get_category() => 'some-new-plugin'` will still appear in the capabilities map even before `CATEGORY_META` is updated.

**3. Token-efficient**

Each plugin category adds roughly 3–6 lines to the prompt. Worst case: a site with every supported plugin active (unlikely) would add ~25 categories × ~4 lines = ~100 lines. Typical sites (3–5 plugins) add ~15–25 lines. This is far cheaper than injecting full knowledge files for every active plugin.

**4. Complements, not replaces, intent classification**

The capabilities map tells the LLM *what it can do*. The full knowledge files (loaded via intent classification) tell it *how to do it well*. Both layers serve different purposes:

| Layer | When loaded | Purpose | Token cost |
|-------|-------------|---------|------------|
| Capabilities map | Always (from tool_definitions) | "You have ACF tools available" | ~3-6 lines per plugin |
| Full knowledge files | On intent match (user mentions topic) | "Here's how ACF field groups work in detail" | ~50-200 lines per file |

**5. Category naming convention**

Tool categories should match the corresponding knowledge file name (without `.md`) when possible. This ensures forward-compatible alignment between the capabilities map and the intent classifier:

| Category | Knowledge file | Intent key |
|----------|---------------|------------|
| `acf` | `acf.md` | `acf` |
| `woocommerce` | `woocommerce.md` | `woocommerce` |
| `yoast-seo` | `yoast-seo.md` | `yoast-seo` |
| `gravity-forms` | `gravity-forms.md` | `gravity-forms` |
| `events` | `events-plugins.md` | `events-plugins` |
| `caching` | `caching-plugins.md` | `caching-plugins` |

When they don't match exactly (e.g., `events` vs `events-plugins`), `CATEGORY_META` can include an optional `knowledgeKey` field to bridge the gap (see Future Enhancements).

## Implementation Plan

### Prerequisites

This feature should be implemented **after** the tool expansion from `.ralph/fix_plan.md` is complete (or at least after Tier 1 and Tier 2 tools are done). The capabilities map becomes increasingly valuable as more plugin categories are added. However, it works fine with the current 6 categories too — just less impactful.

### Execution order

1. Complete tool expansion (`.ralph/fix_plan.md`) — adds new tool categories
2. Implement the capabilities map (this doc) — makes the LLM aware of them
3. (Optional future) Implement smart intent expansion — loads full knowledge for relevant plugins

### Files to modify

| File | Change |
|------|--------|
| `apps/backend/src/knowledge/prompt-builder.service.ts` | Add `buildCapabilitiesSection()` method that accepts tool definitions, groups by category, and generates the summary text. Call it in `buildSystemPrompt()`. |
| `apps/backend/src/chat/chat.controller.ts` | Pass parsed `dynamicTools` to `buildSystemPrompt()` (or pass raw `tool_definitions`). |
| `apps/backend/src/chat/tool-result.controller.ts` | Same as above for the tool-result continuation route. |

### New file

| File | Purpose |
|------|---------|
| `apps/backend/src/knowledge/category-meta.ts` | Export `CATEGORY_META` mapping and `CORE_CATEGORIES` set. Keeps prompt-builder clean. |

### Steps

1. Create `category-meta.ts` with the `CATEGORY_META` mapping, `CORE_CATEGORIES` set, and a helper to title-case unknown categories as fallback.

2. Add `buildCapabilitiesSection(tools: ToolDefinition[]): string` to `PromptBuilderService`:
   - Group tools by `category`
   - Skip categories in `CORE_CATEGORIES`
   - For each remaining category, look up label/hint from `CATEGORY_META` (fallback to title-cased category name)
   - Format: category label → bullet list of tool names grouped by action verb → hint line
   - Return the assembled text block (or empty string if no plugin tools)

3. Update `buildSystemPrompt()` signature to accept an optional `tools` parameter. Insert the capabilities section between the base instructions and the knowledge section.

4. Update both `chat.controller.ts` and `tool-result.controller.ts` to pass the resolved tools into `buildSystemPrompt()`.

5. Add unit tests for `buildCapabilitiesSection()`:
   - Correctly groups tools by category
   - Skips core categories
   - Includes hints from `CATEGORY_META`
   - Falls back to title-cased label for unknown categories
   - Returns empty string when no plugin tools are present
   - Handles empty/null tool definitions

### Signature change

```typescript
// Before
buildSystemPrompt(
  siteProfile?: SiteProfile | null,
  customPrompt?: string | null,
  userMessage?: string | null,
  conversationHistory?: ConversationMessage[] | null,
): string

// After
buildSystemPrompt(
  siteProfile?: SiteProfile | null,
  customPrompt?: string | null,
  userMessage?: string | null,
  conversationHistory?: ConversationMessage[] | null,
  tools?: ToolDefinition[] | null,
): string
```

### Also update: ToolInterface PHPDoc

The `get_category()` docstring in `class-tool-interface.php` currently lists a hardcoded set:

```php
/**
 * Tool category for permission grouping.
 * One of: content, site, plugins, search, elementor.
 */
```

Update this to reflect the expanded set and the naming convention:

```php
/**
 * Tool category for permission grouping and capabilities mapping.
 *
 * Core categories (always present): content, site, plugins, search, users, menus, media, comments
 * Plugin categories: use the knowledge file name (e.g., 'woocommerce', 'acf', 'yoast-seo').
 * New categories are auto-discovered by the backend capabilities map.
 */
```

## Future Enhancements

- **Smart intent expansion:** Use the capabilities map to also expand intent classification — if ACF tools are present and the user asks about post types, auto-add `acf` to the intents list so the full `acf.md` gets loaded too. This bridges the gap between "knowing what tools exist" and "knowing the details of how they work."

- **Category-to-knowledge bridging:** Add an optional `knowledgeKey` field to `CATEGORY_META` for categories that don't exactly match their knowledge file name (e.g., `events` → `events-plugins`, `caching` → `caching-plugins`). This enables future smart intent expansion to load the right knowledge file.

- **Per-tool hints in PHP:** Allow tool classes to define a `get_capability_hint(): string` method that surfaces in the capabilities map (e.g., "Use this instead of create_post for WooCommerce products"). This lets tool authors provide guidance without modifying backend code.

- **Token budget awareness:** If a site has many plugins active (10+), prioritize capabilities by relevance to the user's message rather than listing all of them. Could use a lightweight scoring pass: categories whose tool names partially match the user's message get listed first.
