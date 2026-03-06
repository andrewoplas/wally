# Fix Plan — Wally Intelligence Upgrade (Phases 2-4)

## Tier 1: New Strategic Tools (Phase 2)

- [x] **1.1 Debug Tools** — `class-debug-tools.php` (NEW FILE)
  - Spec: `docs/phase-2-strategic-tools.md` section 2
  - Tools: `get_error_log` (read last N lines of WP_CONTENT_DIR/debug.log), `get_site_health_tests` (run WP Site Health direct tests)
  - Both require `manage_options` capability
  - No confirmation needed (read-only)

- [x] **1.2 Media Upload Tools** — `class-media-tools.php` (ADD TO EXISTING)
  - Spec: `docs/phase-2-strategic-tools.md` section 3
  - Add `upload_media_from_url` tool: downloads image from URL via `download_url()` + `media_handle_sideload()`, sets alt text
  - Add `set_featured_image` tool: calls `set_post_thumbnail($post_id, $attachment_id)`
  - Read existing file first, add new classes matching the existing style

- [x] **1.3 Customizer Tools** — `class-customizer-tools.php` (NEW FILE)
  - Spec: `docs/phase-2-strategic-tools.md` section 4
  - Tools: `get_theme_mods` (read all theme mods via `get_theme_mods()`), `update_theme_mod` (set via `set_theme_mod()`, requires confirmation)
  - Capability: `edit_theme_options`

- [x] **1.4 Widget Tools** — `class-widget-tools.php` (NEW FILE)
  - Spec: `docs/phase-2-strategic-tools.md` section 5
  - Tools: `list_widget_areas` (registered sidebars), `list_widgets` (active widgets per area), `add_widget`, `remove_widget`
  - `can_register()` should return `!wp_is_block_theme()` (classic themes only)
  - Capability: `edit_theme_options`

- [x] **1.5 Menu Location Tool** — `class-menu-tools.php` (ADD TO EXISTING)
  - Add `set_menu_location` tool: assigns a menu to a theme location via `set_theme_mod('nav_menu_locations', [...])`
  - Read existing file first, add new class matching the existing style
  - Requires confirmation, capability: `edit_theme_options`

## Tier 2: Tool Expansions (Phase 2-3)

- [x] **2.1 WooCommerce Expansions** — `class-woocommerce-tools.php` (ADD TO EXISTING)
  - Spec: `docs/phase-3-plugin-expansions.md` section 1
  - Add: `create_coupon` (create WC_Coupon with code, type, amount), `get_revenue_summary` (query revenue/orders for date range), `manage_stock` (bulk update product stock), `list_product_categories` (woo product categories)
  - Read existing file first, add new classes at the end

- [x] **2.2 Yoast SEO Expansion** — `class-yoast-seo-tools.php` (ADD TO EXISTING)
  - Spec: `docs/phase-3-plugin-expansions.md` section 2
  - Add: `list_yoast_seo_issues` (query posts with missing/poor SEO meta using WP_Query + post meta)
  - Read existing file first, add new class

- [x] **2.3 Comment Expansions** — `class-comment-tools.php` (ADD TO EXISTING)
  - Spec: `docs/phase-2-strategic-tools.md` section 7
  - Add: `bulk_moderate_comments` (approve/spam/trash multiple comments by ID array, requires confirmation)
  - Read existing file first, add new class

## Tier 3: Page Template Library (Phase 4)

- [x] **3.1 Page Templates Knowledge File** — `apps/backend/src/knowledge/page-templates.md` (NEW FILE)
  - Spec: `docs/phase-4-advanced-features.md` section 2
  - Create complete, tested Gutenberg block markup templates for: Business Landing Page, Restaurant, Portfolio, About Page, Contact Page, Service Page, Coming Soon
  - Each template should have all sections with full block markup ready to customize
  - Read `apps/backend/src/knowledge/gutenberg-blocks.md` first for block syntax reference

- [x] **3.2 Page Templates Intent Pattern** — `intent-classifier.service.ts`
  - Ensure `gutenberg-blocks` intent also loads `page-templates` knowledge
  - Update `KnowledgeLoaderService` or intent classifier so page-building intents inject both `gutenberg-blocks` and `page-templates`

## Tier 4: Content Style Matching (Phase 4)

- [x] **4.1 SiteScanner: Add Recent Posts Sample** — `class-site-scanner.php`
  - Spec: `docs/phase-4-advanced-features.md` section 3
  - Add `recent_posts_sample` field to site profile: 3 recent published posts with title + 40-word excerpt
  - Read existing file first, add to the `scan()` method output

- [x] **4.2 PromptBuilder: Inject Content Style** — `prompt-builder.service.ts`
  - Add `recent_posts_sample` to `SiteProfile` interface
  - When present, append a "Content Style Reference" section to the system prompt with the post excerpts
  - Read existing file first, add after the Site Context section

## Tier 5: Guided Wizards (Phase 4)

- [x] **5.1 Guided Wizards Knowledge File** — `apps/backend/src/knowledge/guided-wizards.md` (NEW FILE)
  - Spec: `docs/phase-4-advanced-features.md` section 4
  - Three wizard flows: New Site Setup, Migration Helper, Launch Checklist
  - Each with trigger phrases, step-by-step instructions, and what tools to use at each step
  - Written as instructions for the LLM, not for the user

- [x] **5.2 Wizard Intent Patterns** — `intent-classifier.service.ts`
  - Add new intent key `guided-wizards` with patterns: "set up my site", "just installed wordpress", "get started", "migrate from squarespace/wix", "ready to launch", "pre-launch check", "launch checklist"
  - Read existing file first, add new pattern block

## Tier 6: Rollback / Undo System (Phase 4)

- [x] **6.1 Snapshot Database Table** — `class-database.php`
  - Spec: `docs/phase-4-advanced-features.md` section 1
  - Add `wp_wally_snapshots` table creation to the Database class (id, conversation_id, snapshot_type, object_id, object_key, previous_value, created_at)
  - Read existing Database class first, add new table in the same pattern

- [x] **6.2 Snapshot Helper Class** — `class-snapshot.php` (NEW FILE)
  - Create `Wally\Snapshot` class with static methods: `save()`, `get_latest()`, `list_for_conversation()`, `delete()`, `cleanup_old()`
  - Uses `$wpdb` for all queries
  - `cleanup_old()` deletes snapshots older than 24 hours

- [x] **6.3 Undo Tools** — `class-undo-tools.php` (NEW FILE)
  - Tools: `undo_last_action` (restore from latest snapshot, requires confirmation), `list_recent_changes` (show snapshots for current conversation)
  - Uses the Snapshot helper class

- [x] **6.4 Add Snapshot Calls to Existing Tools**
  - Modify `class-content-tools.php`: before `update_post` and `delete_post`, save snapshot of current post state
  - Modify `class-site-tools.php`: before `update_option`, save snapshot of current option value
  - Modify `class-menu-tools.php`: before `delete_menu`, `update_menu_item`, `delete_menu_item`, save snapshot
  - Read each file first, add snapshot save calls at the top of relevant `execute()` methods
  - Pass `$conversation_id` through — check how ToolExecutor passes it

- [ ] **6.5 Snapshot Cleanup Cron** — `class-plugin.php`
  - Add `Snapshot::cleanup_old()` call to the existing `wally_daily_site_scan` cron handler
  - Read existing file first, add one line to the cron callback

## Discovered
<!-- Ralph adds discovered tasks here -->
