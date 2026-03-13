# E-commerce & Membership Plugins (Non-WooCommerce)

## When to Use
- User mentions Easy Digital Downloads (EDD), MemberPress, or LearnDash
- User wants to manage digital products, memberships, or courses
- User asks about downloads, subscriptions, enrollments, or LMS content
- Note: WooCommerce has its own dedicated knowledge file

## Available Tools
- `list_plugins` — detect which e-commerce/membership plugin is active
- `list_posts` — list products, memberships, or courses by post type
- `get_post` — get details of a specific product, membership, or course
- `create_post` — create new products, memberships, or courses (basic fields only)
- `update_post` — update titles, content, status
- `delete_post` — delete items (requires confirmation)
- `search_content` — search across product/course content
- `get_option` — read plugin settings

## Workflows

### Detect Active Plugin
1. Call `list_plugins`
2. Look for: `easy-digital-downloads`, `memberpress`, `sfwd-lms` (LearnDash)

### Easy Digital Downloads — List Products
1. Call `list_posts` with `post_type: 'download'`
2. Products have categories (`download_category`) and tags (`download_tag`)
3. Pricing meta (`edd_price`) may appear in post meta

### Easy Digital Downloads — Read Settings
1. Call `get_option` with key `edd_settings`
2. Key settings: `currency`, `test_mode`, `purchase_page`, `success_page`

### MemberPress — List Memberships
1. Call `list_posts` with `post_type: 'memberpressproduct'`
2. Each membership level is a post with pricing in postmeta

### MemberPress — List Access Rules
1. Call `list_posts` with `post_type: 'memberpressrule'`
2. Rules link memberships to protected content

### MemberPress — Read Settings
1. Call `get_option` with key `mepr_options`
2. Key settings: `account_page_id`, `login_page_id`, `thankyou_page_id`

### LearnDash — List Courses
1. Call `list_posts` with `post_type: 'sfwd-courses'`

### LearnDash — List Lessons for a Course
1. Call `list_posts` with `post_type: 'sfwd-lessons'`, `meta_key: 'course_id'`, `meta_value: '<course_id>'`

### LearnDash — List Quizzes
1. Call `list_posts` with `post_type: 'sfwd-quiz'`

### LearnDash — Read Settings
1. Call `get_option` with key starting with `learndash_settings_*`
2. Example: `learndash_settings_courses_cpt` for course settings

## Important Notes
- All three plugins use Custom Post Types — accessible via `list_posts` with the right `post_type`
- Pricing, inventory, and payment data are stored in postmeta — some fields may not be writable via Wally
- Orders and transactions use custom tables (EDD 3.0+) or CPTs — not directly manageable via Wally
- User progress, enrollments, and subscriptions are in usermeta or custom tables — guide user to admin for these
- For payment gateway configuration, guide user to each plugin's admin settings page
