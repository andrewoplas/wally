## WooCommerce (if installed)

### Data Model
- Products are post type 'product' with extensive meta fields. Product categories: taxonomy 'product_cat'. Product tags: taxonomy 'product_tag'. Product attributes: taxonomy 'pa_{attribute_name}'.
- Orders use custom wc_orders table (HPOS, WC 8.0+) or legacy post type 'shop_order'. Always use wc_get_order(), never get_post() for orders.
- Coupons are post type 'shop_coupon'.

### Product Types
simple, variable (parent + variation children), grouped (parent linking child products), external/affiliate (off-site purchase URL). Modifier flags: virtual (no shipping), downloadable (file access on purchase).

### Key Product Meta
- _price, _regular_price, _sale_price, _sale_price_dates_from, _sale_price_dates_to -- pricing
- _stock, _stock_status (instock/outofstock/onbackorder), _manage_stock (yes/no), _backorders
- _sku -- stock keeping unit (unique identifier)
- _weight, _length, _width, _height -- shipping dimensions
- _virtual, _downloadable -- product flags (yes/no strings)
- _product_image_gallery -- comma-separated attachment IDs
- _wc_average_rating, _wc_review_count -- aggregated ratings
- _tax_status (taxable/shipping/none), _tax_class

### Product CRUD
- wc_get_product($id) -- returns WC_Product (or subclass: WC_Product_Simple, WC_Product_Variable, etc.)
- WC_Product methods: get_id(), get_name(), get_slug(), get_type(), get_status(), get_price(), get_regular_price(), get_sale_price(), get_stock_quantity(), get_stock_status(), get_sku(), get_weight(), get_dimensions(), get_category_ids(), get_tag_ids(), get_image_id(), get_gallery_image_ids(), is_on_sale(), is_in_stock(), is_virtual(), is_downloadable()
- Setters mirror getters: set_name(), set_price(), set_stock_quantity(), set_status(), etc. Must call $product->save() after setting values.
- Product meta: $product->get_meta('key'), $product->update_meta_data('key', 'value'), $product->save_meta_data()
- wc_get_products(['limit' => 10, 'status' => 'publish', 'type' => 'simple', 'category' => ['shirts'], 'orderby' => 'date', 'order' => 'DESC'])
- Variable products: $product->get_children() returns variation IDs. wc_get_product($variation_id) returns WC_Product_Variation.

### Order Statuses
pending, processing, on-hold, completed, cancelled, refunded, failed. Prefixed with 'wc-' in database (e.g., 'wc-processing'). Use without prefix in wc_get_orders() status param.

### Order CRUD
- wc_get_order($id) -- returns WC_Order object (HPOS-compatible)
- wc_get_orders(['limit' => 10, 'status' => 'completed', 'customer_id' => 5, 'date_created' => '>2024-01-01', 'meta_query' => [...]])
- WC_Order methods: get_id(), get_status(), get_total(), get_subtotal(), get_shipping_total(), get_total_tax(), get_currency(), get_payment_method(), get_customer_id(), get_date_created(), get_date_modified()
- Billing: get_billing_first_name(), get_billing_last_name(), get_billing_email(), get_billing_phone(), get_billing_address_1(), get_billing_city(), get_billing_state(), get_billing_postcode(), get_billing_country()
- Shipping: get_shipping_first_name(), get_shipping_address_1(), etc. (mirrors billing)
- Line items: $order->get_items() returns WC_Order_Item_Product[]. Each item: get_product_id(), get_quantity(), get_total(), get_name()
- $order->update_status('completed', 'Order note here') -- changes status and optionally adds note
- $order->add_order_note('Internal note') -- adds admin note
- Order meta: $order->get_meta('key'), $order->update_meta_data('key', 'value'), $order->save()
- wc_create_order(['customer_id' => 1, 'status' => 'pending']) then add items/fees/shipping, then $order->calculate_totals() and $order->save()

### Customers
- new WC_Customer($user_id) -- wraps WP user with WC data
- Methods: get_billing_email(), get_billing_phone(), get_billing_address(), get_shipping_address(), get_order_count(), get_total_spent(), get_date_created()
- Customer meta stored as WordPress user meta (billing_first_name, billing_email, shipping_address_1, etc.)

### Cart (Frontend Context Only)
- WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_attrs)
- WC()->cart->get_cart() -- returns array of cart item arrays
- WC()->cart->get_cart_total(), get_subtotal(), get_total_tax()
- WC()->cart->remove_cart_item($cart_item_key)
- WC()->cart->empty_cart() -- clears cart
- WC()->cart->apply_coupon($code), remove_coupon($code)
- Note: Cart is session-based and only available on frontend requests, not in REST API or admin AJAX unless session is loaded.

### Key Hooks
- woocommerce_before_add_to_cart_button -- output HTML on product page before add-to-cart
- woocommerce_add_to_cart -- fires after item added to cart
- woocommerce_checkout_process -- validate during checkout (wp_die to block)
- woocommerce_checkout_update_order_meta -- save custom checkout fields to order meta
- woocommerce_order_status_changed($order_id, $from, $to, $order) -- fires on any status transition
- woocommerce_thankyou -- fires on order confirmation page
- woocommerce_product_query($query) -- modify main product query on shop/archive pages
- woocommerce_single_product_summary -- output content on single product page (priority 5: title, 10: price, 20: excerpt, 25: add-to-cart, 30: meta)
- woocommerce_process_product_meta($post_id) -- save custom product fields in admin
- woocommerce_before_calculate_totals -- modify cart item prices before total calculation

### Common Settings (wp_options)
- woocommerce_currency -- store currency code (e.g., 'USD')
- woocommerce_default_country -- store location (e.g., 'US:CA')
- woocommerce_calc_taxes -- 'yes'/'no' for tax calculation
- woocommerce_prices_include_tax -- 'yes'/'no'
- woocommerce_tax_display_shop, woocommerce_tax_display_cart -- 'incl' or 'excl'
- woocommerce_shop_page_id, woocommerce_cart_page_id, woocommerce_checkout_page_id, woocommerce_myaccount_page_id, woocommerce_terms_page_id
- woocommerce_weight_unit (kg/g/lbs/oz), woocommerce_dimension_unit (cm/m/mm/in/yd)
- woocommerce_manage_stock -- global stock management toggle
- woocommerce_notify_low_stock_amount, woocommerce_notify_no_stock_amount

### REST API (v3)
Base: /wp-json/wc/v3/. Requires consumer key/secret (Basic Auth) or application passwords.
- GET|POST /products, GET|PUT|DELETE /products/{id}
- GET|POST /orders, GET|PUT|DELETE /orders/{id}
- GET|POST /customers, GET|PUT|DELETE /customers/{id}
- GET|POST /coupons, GET|PUT|DELETE /coupons/{id}
- GET /products/categories, /products/tags, /products/attributes
- GET /reports/sales, /reports/top_sellers
- Batch operations: POST /products/batch with create[], update[], delete[] arrays.

### HPOS (High-Performance Order Storage)
WC 8.0+ stores orders in custom tables (wc_orders, wc_order_addresses, wc_order_operational_data, wc_orders_meta) instead of wp_posts/wp_postmeta. Compatibility mode can run both in parallel during migration. Always use wc_get_order() and WC_Order methods -- never direct post queries for orders. Check with: wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled().
