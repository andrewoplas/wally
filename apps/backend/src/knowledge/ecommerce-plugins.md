## E-commerce & Membership Plugins (Non-WooCommerce)

Note: WooCommerce has its own dedicated knowledge file. This covers Easy Digital Downloads, MemberPress, and LearnDash.

### Easy Digital Downloads (EDD)
- **Products**: Post type `download`. Supports categories (`download_category`) and tags (`download_tag`).
- **Key product meta**:
  - `edd_price` — single price (simple pricing)
  - `edd_variable_prices` — serialized array of price options (variable pricing)
  - `_edd_download_files` — serialized array of downloadable file attachments
  - `_edd_download_limit` — max download attempts per purchase
  - `_edd_product_type` — 'default' or 'bundle'
  - `_edd_bundled_products` — array of download IDs (if bundle)
  - `_thumbnail_id` — featured image
- **Orders (EDD 3.0+)**: Custom tables `{prefix}edd_orders`, `{prefix}edd_order_items`, `{prefix}edd_order_addresses`, `{prefix}edd_order_adjustments`. Pre-3.0 used post type `edd_payment`.
- **Functions**:
  - `edd_get_download($id)` — returns EDD_Download object
  - `edd_get_order($id)` — returns EDD\Orders\Order object (3.0+)
  - `edd_get_payment($id)` — returns EDD_Payment object (legacy, still works)
  - `edd_get_cart_contents()` — current cart items
  - `edd_get_orders(['status' => 'complete', 'number' => 10])` — query orders (3.0+)
  - `edd_update_order($id, $data)` — update order fields
- **Settings**: wp_options key `edd_settings` (serialized array). Access via `edd_get_option($key)`.
- **Key settings keys**: `purchase_page` (checkout page ID), `success_page` (confirmation page ID), `currency`, `currency_position`, `test_mode`.
- **Hooks**: `edd_complete_purchase`, `edd_insert_payment`, `edd_update_payment_status`, `edd_after_download_content`.

### MemberPress
- **Memberships**: Post type `memberpressproduct` — each membership level is a post. Price, period, and access rules defined in postmeta.
- **Transactions**: Post type `mepr-transaction` — individual payment records.
- **Subscriptions**: Custom table `{prefix}mepr_subscriptions` — recurring subscription records.
- **Rules**: Post type `memberpressrule` — access rules that protect content. Rules link memberships to content via conditions (post, page, category, tag, custom URI, partial).
- **Key classes**:
  - `MeprUser($user_id)` — extends WP_User with membership methods: `active_product_subscriptions()`, `is_already_subscribed_to($product_id)`, `lifetime_value()`
  - `MeprTransaction` — `get_one($id)`, `get_all_by_user_id($user_id)`, statuses: pending, complete, failed, refunded
  - `MeprSubscription` — `get_one($id)`, `get_all_by_user_id($user_id)`, statuses: active, suspended, cancelled
  - `MeprProduct($post_id)` — membership level: `$product->price`, `$product->period`, `$product->period_type`
- **Settings**: wp_options key `mepr_options` (serialized). Access via `MeprOptions::fetch()`. Key sub-keys: `account_page_id`, `login_page_id`, `thankyou_page_id`.
- **Hooks**: `mepr-txn-store`, `mepr-event-transaction-completed`, `mepr-account-is-active`, `mepr_subscription_transition_status`.

### LearnDash
- **Custom post types**:
  - `sfwd-courses` — courses
  - `sfwd-lessons` — lessons (belong to a course)
  - `sfwd-topic` — topics (belong to a lesson)
  - `sfwd-quiz` — quizzes (can attach to course, lesson, or topic)
  - `sfwd-certificates` — certificate templates
  - `sfwd-assignment` — student assignments
- **Course structure**: Courses contain lessons, lessons contain topics. Hierarchy managed via postmeta (`course_id`, `lesson_id`) and `{prefix}learndash_course_steps` meta.
- **User progress**: Stored in usermeta key `_sfwd-course_progress` (serialized). Also `_sfwd-quizzes` for quiz results. Activity tracked in `{prefix}learndash_user_activity` and `{prefix}learndash_user_activity_meta` tables.
- **Functions**:
  - `learndash_get_course_steps($course_id)` — ordered array of all step IDs
  - `learndash_user_get_enrolled_courses($user_id)` — courses user is enrolled in
  - `learndash_get_course_progress($user_id, $course_id)` — user's progress in course
  - `learndash_is_course_complete($user_id, $course_id)` — boolean completion check
  - `learndash_process_mark_complete($user_id, $post_id)` — mark step complete
  - `sfwd_lms_has_access($post_id, $user_id)` — check if user can access content
- **Settings**: wp_options keys prefixed `learndash_settings_*` (e.g., `learndash_settings_courses_cpt`, `learndash_settings_quizzes_cpt`). Per-course/lesson settings in postmeta as `_sfwd-courses` or `_sfwd-lessons` (serialized).
- **Hooks**: `learndash_course_completed`, `learndash_lesson_completed`, `learndash_quiz_completed`, `learndash_update_course_access`.

### Common Patterns
- All three plugins use Custom Post Types for their primary content (products, memberships, courses).
- Transactions and subscriptions may use CPTs (EDD legacy, MemberPress) or custom tables (EDD 3.0+).
- User-specific data (enrollments, purchases, progress) is typically stored in usermeta or custom tables.
- Always use the plugin's API functions rather than direct database queries — data structures change between major versions.
