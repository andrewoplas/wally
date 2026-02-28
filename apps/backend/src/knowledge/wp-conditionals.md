## WordPress Conditional Tags

Conditional tags return `true` or `false` based on the current request context. They rely on `WP_Query` and **do not work before the query is run** (they always return `false` before then).

---

### Single Content

| Function | Description | Parameters |
|----------|-------------|------------|
| `is_single()` | Single post (any post type except pages and attachments) | `$post` (int\|string\|array) -- ID, title, slug, or array |
| `is_single('beef-stew')` | Specific post by slug | slug string |
| `is_single(17)` | Specific post by ID | post ID int |
| `is_single(array(17, 'beef-stew', 'Irish Stew'))` | Any of several posts | array of IDs/slugs/titles |
| `is_singular()` | Any single post, page, or attachment | `$post_types` (string\|array) -- optional post type(s) |
| `is_singular('book')` | Single post of custom post type | post type string |
| `is_page()` | Single page (page post type only) | `$page` (int\|string\|array) -- ID, title, slug, or array |
| `is_page('about-me')` | Specific page by slug | slug string |
| `is_page(42)` | Specific page by ID | page ID int |
| `is_page(array(42, 'about-me', 'Contact'))` | Any of several pages | array of IDs/slugs/titles |
| `is_attachment()` | Single attachment page | `$attachment` (int\|string\|array) -- optional |

---

### Archives

| Function | Description | Parameters |
|----------|-------------|------------|
| `is_archive()` | Any archive page (category, tag, author, date, CPT, taxonomy) | none |
| `is_category()` | Category archive | `$category` (int\|string\|array) -- ID, name, slug, or array |
| `is_category('news')` | Specific category by slug | slug string |
| `is_category(array(9, 'blue-cheese', 'Stinky Cheeses'))` | Any of several categories | array |
| `is_tag()` | Tag archive | `$tag` (int\|string\|array) -- ID, name, slug, or array |
| `is_tag('mild')` | Specific tag by slug | slug string |
| `is_tax()` | Custom taxonomy archive | `$taxonomy` (string\|array), `$term` (int\|string\|array) |
| `is_tax('flavor')` | Specific taxonomy archive | taxonomy slug |
| `is_tax('flavor', 'sweet')` | Specific term in taxonomy | taxonomy slug, term slug |
| `is_author()` | Author archive | `$author` (int\|string\|array) -- ID, nicename, or array |
| `is_author(5)` | Specific author by ID | author ID int |
| `is_date()` | Any date-based archive (year, month, day) | none |
| `is_year()` | Yearly archive | none |
| `is_month()` | Monthly archive | none |
| `is_day()` | Daily archive | none |

---

### Post Type Archives

| Function | Description | Parameters |
|----------|-------------|------------|
| `is_post_type_archive()` | Any custom post type archive | `$post_types` (string\|array) -- optional |
| `is_post_type_archive('product')` | Specific CPT archive | post type slug |
| `is_post_type_archive(array('product', 'book'))` | Any of several CPT archives | array of post type slugs |

---

### Special Pages

| Function | Description | Parameters |
|----------|-------------|------------|
| `is_front_page()` | Site front page (respects Settings > Reading) | none |
| `is_home()` | Blog posts index page | none |
| `is_search()` | Search results page | none |
| `is_404()` | 404 Not Found page | none |
| `is_privacy_policy()` | Privacy policy page (WP 5.2+) | none |

**Front page vs. home page behavior:**
- Static front page + separate posts page: `is_front_page()` on static page, `is_home()` on posts page
- Static front page only: `is_front_page()` true, `is_home()` false
- Default (latest posts as front page): both `is_front_page()` and `is_home()` return true

---

### Admin & Context

| Function | Description | Parameters |
|----------|-------------|------------|
| `is_admin()` | Admin dashboard area (also true during admin-ajax.php requests) | none |
| `is_user_logged_in()` | Current visitor is logged in | none |
| `is_ssl()` | Page loaded over HTTPS | none |
| `is_multisite()` | WordPress Multisite installation | none |
| `is_main_site()` | Current site is the main site in a Multisite network | `$site_id` (int) -- optional |
| `is_super_admin()` | Current user is a network administrator | `$user_id` (int) -- optional |

**Important caveat:** `is_admin()` does NOT check user permissions. It only checks the request context. Use `current_user_can()` for capability checks. Also, `is_admin()` returns `true` during AJAX requests originating from the admin, even for frontend-facing AJAX handlers.

---

### Loop & Query

| Function | Description | Parameters |
|----------|-------------|------------|
| `in_the_loop()` | Currently inside The Loop | none |
| `is_main_query()` | Current query is the main query (not a secondary `WP_Query`) | none |
| `have_posts()` | Current query has posts remaining | none |
| `is_feed()` | Current request is an RSS/Atom feed | `$feeds` (string\|array) -- optional |

---

### Template

| Function | Description | Parameters |
|----------|-------------|------------|
| `is_page_template()` | Current page uses any custom page template | none |
| `is_page_template('template-file.php')` | Current page uses specific template | template filename string |
| `is_page_template('templates/full-width.php')` | Template in subdirectory | path relative to theme root |
| `is_child_theme()` | Active theme is a child theme | none |
| `current_theme_supports('feature')` | Theme supports a specific feature | feature name string |

---

### Post Format & Sticky

| Function | Description | Parameters |
|----------|-------------|------------|
| `has_post_format()` | Current post has a post format | `$format` (string\|array) -- optional |
| `has_post_format('video')` | Current post has specific format | format string |
| `is_sticky()` | Post is marked as sticky | `$post_id` (int) -- optional, defaults to current post |

---

### Taxonomy & Term Checks

| Function | Description | Parameters |
|----------|-------------|------------|
| `has_term()` | Post has a specific term | `$term` (int\|string\|array), `$taxonomy` (string), `$post` (int\|WP_Post) |
| `has_category()` | Post belongs to category | `$category` (int\|string\|array), `$post` (int\|WP_Post) |
| `has_category('news')` | Post belongs to specific category | category slug |
| `has_tag()` | Post has a tag | `$tag` (int\|string\|array), `$post` (int\|WP_Post) |
| `has_tag('featured')` | Post has specific tag | tag slug |
| `in_category()` | Post is in category (alias-like behavior) | `$category` (int\|string\|array), `$post` (int\|WP_Post) |
| `taxonomy_exists()` | Taxonomy is registered | `$taxonomy` (string) |
| `post_type_exists()` | Post type is registered | `$post_type` (string) |

**Difference:** `is_category()` checks if viewing a category archive page. `has_category()` / `in_category()` checks if a specific post belongs to a category.

---

### Device, AJAX & Cron

| Function | Description | Parameters |
|----------|-------------|------------|
| `wp_is_mobile()` | Request from a mobile device (based on user agent) | none |
| `wp_doing_ajax()` | Current request is a WordPress AJAX request | none |
| `wp_doing_cron()` | Current request is a WP-Cron execution | none |
| `wp_is_json_request()` | Current request expects JSON response (WP 5.0+) | none |
| `wp_is_rest_request()` | Current request is a REST API request (WP 6.5+) | none |

---

### Combined Conditions & Common Patterns

```php
// Single post or page but not the front page
if ( is_singular() && ! is_front_page() ) { ... }

// Blog listing or single post
if ( is_home() || is_single() ) { ... }

// Any archive except author pages
if ( is_archive() && ! is_author() ) { ... }

// Specific custom post type single or archive
if ( is_singular('product') || is_post_type_archive('product') ) { ... }

// Front-end only (exclude admin, AJAX, cron)
if ( ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() ) { ... }

// Logged-in user on a specific page
if ( is_user_logged_in() && is_page('dashboard') ) { ... }

// Custom taxonomy with specific term
if ( is_tax('genre', 'jazz') ) { ... }

// Main query only (inside pre_get_posts hook)
function modify_main_query( $query ) {
    if ( $query->is_main_query() && ! is_admin() ) {
        // Modify main query only on front-end
    }
}
add_action( 'pre_get_posts', 'modify_main_query' );

// Category archive for multiple categories
if ( is_category( array('news', 'updates', 'announcements') ) ) { ... }

// Any single content type with a specific template
if ( is_singular() && is_page_template('templates/full-width.php') ) { ... }

// Check post type in The Loop
if ( get_post_type() === 'product' ) { ... }

// Sticky post handling in the loop
if ( is_sticky() && is_home() && ! is_paged() ) {
    // Featured sticky post on first page of blog
}
```

---

### Timing: When Conditionals Become Available

| Hook | Available Conditionals |
|------|----------------------|
| `init` | `is_admin()`, `is_user_logged_in()`, `is_ssl()`, `is_multisite()` |
| `template_redirect` | All query-dependent conditionals (`is_single()`, `is_page()`, etc.) |
| `wp` | All conditionals available (query has run) |
| `pre_get_posts` | `is_main_query()` available on the query object (`$query->is_main_query()`) |

Query-dependent tags like `is_single()`, `is_page()`, `is_archive()` etc. always return `false` before the main query runs (before `wp` hook). Context tags like `is_admin()` and `is_user_logged_in()` work earlier.
