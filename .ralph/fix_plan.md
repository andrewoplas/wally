# Fix Plan — WordPress Tool Files

## Tier 1: Core WordPress (No plugin dependency)

- [x] **1.1 User Tools** — `class-user-tools.php`
  - Knowledge: `users.md`
  - Tools: list_users, get_user, create_user, update_user, delete_user, update_user_role
  - Confirmation: delete_user requires confirmation

- [x] **1.2 Menu Tools** — `class-menu-tools.php`
  - Knowledge: `menus.md`
  - Tools: list_menus, get_menu, create_menu, delete_menu, add_menu_item, update_menu_item, delete_menu_item
  - Confirmation: delete operations require confirmation

- [x] **1.3 Media Tools** — `class-media-tools.php`
  - Knowledge: `media.md`
  - Tools: list_media, get_media, update_media, delete_media
  - Note: No upload tool — requires file handling not yet supported
  - Confirmation: delete_media requires confirmation

- [x] **1.4 Comment Tools** — `class-comment-tools.php`
  - Knowledge: `wp-comments.md`
  - Tools: list_comments, get_comment, update_comment_status, delete_comment, reply_to_comment
  - Confirmation: delete_comment requires confirmation

## Tier 2: High-Value Plugins

- [x] **2.1 WooCommerce Tools** — `class-woocommerce-tools.php`
  - Knowledge: `woocommerce.md`
  - Conditional: WooCommerce plugin active
  - Tools: list_products, get_product, create_product, update_product, delete_product, list_orders, get_order, update_order_status, list_coupons, get_coupon
  - Confirmation: delete_product and update_order_status require confirmation

- [x] **2.2 Gravity Forms Tools** — `class-gravity-forms-tools.php`
  - Knowledge: `gravity-forms.md`
  - Conditional: Gravity Forms plugin active
  - Tools: list_forms, get_form, list_entries, get_entry, delete_entry, update_entry_status
  - Confirmation: delete_entry requires confirmation

- [x] **2.3 Contact Form 7 Tools** — `class-contact-form-7-tools.php`
  - Knowledge: `contact-form-7.md`
  - Conditional: Contact Form 7 plugin active
  - Tools: list_contact_forms, get_contact_form, update_contact_form

- [x] **2.4 Yoast SEO Tools** — `class-yoast-seo-tools.php`
  - Knowledge: `yoast-seo.md`
  - Conditional: Yoast SEO plugin active
  - Tools: get_yoast_meta, update_yoast_meta, get_yoast_indexables

- [x] **2.5 Rank Math Tools** — `class-rank-math-tools.php`
  - Knowledge: `rank-math.md`
  - Conditional: Rank Math plugin active
  - Tools: get_rank_math_meta, update_rank_math_meta

- [x] **2.6 Redirection Tools** — `class-redirection-tools.php`
  - Knowledge: `redirection.md`
  - Conditional: Redirection plugin active
  - Tools: list_redirects, create_redirect, update_redirect, delete_redirect, get_404_logs
  - Confirmation: delete_redirect requires confirmation

## Tier 3: Forms & Other Plugins

- [x] **3.1 WPForms Tools** — `class-wpforms-tools.php`
  - Knowledge: `forms-general.md` (WPForms section)
  - Conditional: WPForms plugin active
  - Tools: list_wpforms, get_wpform, list_wpform_entries

- [x] **3.2 Jetpack Tools** — `class-jetpack-tools.php`
  - Knowledge: `jetpack.md`
  - Conditional: Jetpack plugin active
  - Tools: list_jetpack_modules, activate_jetpack_module, deactivate_jetpack_module, get_jetpack_stats
  - Confirmation: module activation/deactivation requires confirmation

- [x] **3.3 Events Calendar Tools** — `class-events-tools.php`
  - Knowledge: `events-plugins.md`
  - Conditional: The Events Calendar plugin active
  - Tools: list_events, get_event, create_event, update_event, delete_event
  - Confirmation: delete_event requires confirmation

- [x] **3.4 Backup Tools** — `class-backup-tools.php`
  - Knowledge: `backup-plugins.md`
  - Conditional: UpdraftPlus plugin active
  - Tools: list_updraftplus_backups, trigger_updraftplus_backup, get_updraftplus_settings
  - Confirmation: trigger_backup requires confirmation

- [x] **3.5 Caching Tools** — `class-caching-tools.php`
  - Knowledge: `caching-plugins.md`
  - Conditional: Detect active caching plugin (WP Rocket, W3 Total Cache, or core)
  - Tools: clear_cache, get_cache_settings
  - Confirmation: clear_cache requires confirmation

- [x] **3.6 Security Plugin Tools** — `class-security-plugin-tools.php`
  - Knowledge: `security-plugins.md`
  - Conditional: Wordfence plugin active
  - Tools: get_wordfence_scan_status, list_wordfence_blocked_ips, run_wordfence_scan
  - Confirmation: run_scan requires confirmation

- [x] **3.7 WooCommerce Extensions Tools** — `class-woocommerce-extensions-tools.php`
  - Knowledge: `woocommerce-extensions.md`
  - Conditional: WooCommerce Subscriptions plugin active
  - Tools: list_subscriptions, get_subscription, update_subscription_status
  - Confirmation: update_subscription_status requires confirmation

## Tier 4: Additional Plugins

- [x] **4.1 EDD & Membership Tools** — `class-ecommerce-tools.php`
  - Knowledge: `ecommerce-plugins.md`
  - Conditional: Per-tool (EDD, MemberPress, or LearnDash active)
  - Tools: list_edd_downloads, get_edd_download, list_edd_payments, list_memberpress_memberships, list_learndash_courses
  - Read-only tools, no confirmation needed

- [x] **4.2 Analytics Tools** — `class-analytics-tools.php`
  - Knowledge: `analytics-plugins.md`
  - Conditional: Google Site Kit or MonsterInsights active
  - Tools: get_site_kit_stats, get_monsterinsights_stats
  - Read-only tools, no confirmation needed

- [x] **4.3 Email Marketing Tools** — `class-email-marketing-tools.php`
  - Knowledge: `email-marketing.md`
  - Conditional: MC4WP or OptinMonster active
  - Tools: list_mailchimp_lists, get_mailchimp_subscribers, list_optinmonster_campaigns
  - Read-only tools, no confirmation needed

- [x] **4.4 Multilingual Tools** — `class-multilingual-tools.php`
  - Knowledge: `multilingual-plugins.md`
  - Conditional: WPML or Polylang active
  - Tools: list_wpml_languages, get_wpml_translation_status, list_polylang_languages
  - Read-only tools, no confirmation needed

- [x] **4.5 Page Builder Tools** — `class-page-builder-tools.php`
  - Knowledge: `page-builders.md`
  - Conditional: Beaver Builder or Divi active
  - Tools: beaver_builder_search_content, beaver_builder_get_layout, divi_search_content, divi_get_layout
  - Read-only tools, no confirmation needed

- [x] **4.6 Image Optimization Tools** — `class-image-optimization-tools.php`
  - Knowledge: `image-optimization.md`
  - Conditional: Smush or EWWW active
  - Tools: get_smush_stats, bulk_smush_status, get_ewww_stats
  - Read-only stats, no confirmation needed

- [x] **4.7 TablePress Tools** — `class-tablepress-tools.php`
  - Knowledge: `content-plugins.md` (TablePress section)
  - Conditional: TablePress plugin active
  - Tools: list_tables, get_table, update_table_cell

- [x] **4.8 Slider Tools** — `class-slider-tools.php`
  - Knowledge: `slider-plugins.md`
  - Conditional: RevSlider plugin active
  - Tools: list_sliders, get_slider, update_slider_status
  - Confirmation: update_status requires confirmation

- [x] **4.9 Audit Log Tools** — `class-audit-log-tools.php`
  - Knowledge: `audit-logging.md`
  - Conditional: Simple History plugin active
  - Tools: get_activity_log, get_activity_log_entry
  - Read-only tools, no confirmation needed

- [ ] **4.10 Social Plugin Tools** — `class-social-tools.php`
  - Knowledge: `social-plugins.md`
  - Conditional: Smash Balloon or similar social plugin active
  - Tools: get_instagram_feed_settings, list_social_share_counts
  - Read-only tools, no confirmation needed

- [ ] **4.11 Media Plugin Tools** — `class-media-plugin-tools.php`
  - Knowledge: `media-plugins.md`
  - Conditional: Regenerate Thumbnails plugin active
  - Tools: regenerate_thumbnails, get_regeneration_status
  - Confirmation: regenerate requires confirmation

## Discovered
<!-- Ralph adds discovered tasks here -->
