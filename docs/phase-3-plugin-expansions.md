# Phase 3: Plugin-Specific Tool Expansions

> Conditional tools that only register when specific plugins are active. Each unlocks deep integration with a popular WordPress plugin ecosystem. All use `can_register()` to check plugin availability.

## Priority Order

### 1. WooCommerce Tools — `class-woocommerce-tools.php`
**Condition:** `class_exists('WooCommerce')`
**Impact:** Massive — WooCommerce powers ~40% of all online stores

| Tool | Action | Description | Confirmation |
|------|--------|-------------|-------------|
| `woo_list_products` | read | List products with filters (status, type, category, stock) | no |
| `woo_get_product` | read | Get full product details (price, stock, images, variations) | no |
| `woo_create_product` | create | Create simple/variable product with all fields | no |
| `woo_update_product` | update | Update price, stock, description, images, etc. | yes |
| `woo_delete_product` | delete | Trash a product | yes |
| `woo_list_orders` | read | List orders with filters (status, date, customer) | no |
| `woo_get_order` | read | Full order details (items, totals, customer, shipping) | no |
| `woo_update_order_status` | update | Change order status (processing, completed, refunded) | yes |
| `woo_create_coupon` | create | Create discount coupon (%, fixed, free shipping) | no |
| `woo_get_revenue_summary` | read | Revenue, order count, avg order value for date range | no |
| `woo_list_categories` | read | Product categories | no |
| `woo_manage_stock` | update | Bulk stock update (quantity, status) | yes |

**Key WooCommerce APIs:**
```php
// Products — use WC API classes
$product = wc_get_product($id);
$product->set_regular_price('29.99');
$product->set_sale_price('19.99');
$product->save();

// Create product
$product = new \WC_Product_Simple();
$product->set_name('Product Name');
$product->set_regular_price('49.99');
$product->set_description('...');
$product->set_short_description('...');
$product->set_category_ids([15, 16]);
$product->set_status('publish');
$id = $product->save();

// Orders
$orders = wc_get_orders(['status' => 'processing', 'limit' => 20]);
$order = wc_get_order($id);
$order->update_status('completed', 'Completed via Wally');

// Coupons
$coupon = new \WC_Coupon();
$coupon->set_code('SUMMER20');
$coupon->set_discount_type('percent');
$coupon->set_amount(20);
$coupon->save();

// Revenue query
$revenue = $wpdb->get_row("
    SELECT COUNT(*) as order_count, SUM(total) as total_revenue
    FROM {$wpdb->prefix}wc_orders
    WHERE status IN ('wc-completed', 'wc-processing')
    AND date_created >= '{$start_date}'
");
// Note: WC 8.0+ uses HPOS (wc_orders table). Fallback to wp_posts for older installs.
```

**Revenue summary output format:**
```php
[
    'period' => 'last_30_days',
    'total_revenue' => 12500.00,
    'order_count' => 85,
    'average_order_value' => 147.06,
    'top_products' => [['name' => '...', 'quantity' => 15, 'revenue' => 2250.00]],
]
```

---

### 2. Yoast SEO Tools — `class-yoast-tools.php`
**Condition:** `defined('WPSEO_VERSION')`
**Impact:** High — Yoast is the most popular SEO plugin

| Tool | Action | Description | Confirmation |
|------|--------|-------------|-------------|
| `yoast_get_meta` | read | Get SEO title, meta description, focus keyphrase for a post | no |
| `yoast_update_meta` | update | Set SEO title, meta description, focus keyphrase | yes |
| `yoast_get_seo_score` | read | Get SEO and readability scores for a post | no |
| `yoast_list_seo_issues` | read | Posts with poor SEO scores or missing meta | no |

**Key meta keys:**
```php
// Read
$title       = get_post_meta($post_id, '_yoast_wpseo_title', true);
$description = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
$keyphrase   = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);

// Write
update_post_meta($post_id, '_yoast_wpseo_title', $title);
update_post_meta($post_id, '_yoast_wpseo_metadesc', $description);
update_post_meta($post_id, '_yoast_wpseo_focuskw', $keyphrase);

// Score (0-100 range, stored as internal score)
$seo_score         = get_post_meta($post_id, '_yoast_wpseo_linkdex', true);
$readability_score = get_post_meta($post_id, '_yoast_wpseo_content_score', true);
```

---

### 3. RankMath SEO Tools — `class-rankmath-tools.php`
**Condition:** `class_exists('RankMath')`
**Impact:** Growing fast, second most popular SEO plugin

| Tool | Action | Description | Confirmation |
|------|--------|-------------|-------------|
| `rankmath_get_meta` | read | Get SEO title, description, focus keywords | no |
| `rankmath_update_meta` | update | Set SEO meta fields | yes |
| `rankmath_get_score` | read | Get SEO score for a post | no |

**Key meta keys:**
```php
$title       = get_post_meta($post_id, 'rank_math_title', true);
$description = get_post_meta($post_id, 'rank_math_description', true);
$keywords    = get_post_meta($post_id, 'rank_math_focus_keyword', true);
$score       = get_post_meta($post_id, 'rank_math_seo_score', true);
```

---

### 4. Contact Form 7 Tools — `class-cf7-tools.php`
**Condition:** `defined('WPCF7_VERSION')`
**Impact:** Most popular form plugin

| Tool | Action | Description | Confirmation |
|------|--------|-------------|-------------|
| `cf7_list_forms` | read | List all contact forms | no |
| `cf7_get_form` | read | Get form details (fields, settings, mail config) | no |
| `cf7_get_submissions` | read | Get Flamingo submissions (if Flamingo is active) | no |

**Implementation:**
```php
// List forms
$forms = \WPCF7_ContactForm::find(['posts_per_page' => -1]);

// Get form details
$form = \WPCF7_ContactForm::get_instance($id);
$properties = $form->get_properties();
// Returns: form markup, mail settings, messages, additional_settings

// Submissions (requires Flamingo plugin)
if (class_exists('Flamingo_Inbound_Message')) {
    $messages = Flamingo_Inbound_Message::find(['channel' => 'contact-form-7']);
}
```

---

### 5. WPForms Tools — `class-wpforms-tools.php`
**Condition:** `function_exists('wpforms')`

| Tool | Action | Description | Confirmation |
|------|--------|-------------|-------------|
| `wpforms_list_forms` | read | List all forms | no |
| `wpforms_get_entries` | read | Get form submissions | no |

---

### 6. Gravity Forms Tools — `class-gravityforms-tools.php`
**Condition:** `class_exists('GFAPI')`

| Tool | Action | Description | Confirmation |
|------|--------|-------------|-------------|
| `gf_list_forms` | read | List all forms | no |
| `gf_get_entries` | read | Get form entries with filters | no |
| `gf_get_entry` | read | Get single entry details | no |

---

### 7. Jetpack Stats Tools — `class-jetpack-tools.php`
**Condition:** `class_exists('Jetpack') && Jetpack::is_module_active('stats')`

| Tool | Action | Description | Confirmation |
|------|--------|-------------|-------------|
| `jetpack_get_stats` | read | Page views, visitors, top pages for date range | no |
| `jetpack_get_top_posts` | read | Most popular posts/pages | no |

---

## Implementation Notes

- Each file contains multiple tool classes for its plugin domain
- All use `can_register()` to gate on plugin availability
- Prefer `class_exists()` or `defined('VERSION_CONST')` over `is_plugin_active()` (works regardless of how plugin is loaded)
- WooCommerce tools are the clear #1 priority — largest market, highest willingness to pay
- SEO tools (Yoast + RankMath) are #2 — they directly complement the content creation workflow from Phase 1
- Form tools are read-only (showing submissions) — creating/editing forms is too complex and better done in the plugin UI
