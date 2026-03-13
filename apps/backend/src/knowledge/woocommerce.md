# WooCommerce

## When to Use
- User wants to manage products, check store settings, or ask about inventory/pricing
- User mentions WooCommerce, products, orders, shop, or store
- Site has WooCommerce active (check via `list_plugins` → look for `woocommerce`)

## Available Tools
- `list_posts` with `post_type: 'product'` — list products
- `get_post` — get a single product's details
- `create_post` with `post_type: 'product'` — create a new product (draft)
- `update_post` — update product title, content (description), status, or meta
- `search_content` — find products by keyword
- `get_option` — read WooCommerce store settings
- `update_option` — change WooCommerce settings (requires confirmation)
- `list_categories` — list product categories (taxonomy: `product_cat`)

## Workflows

### List Products
1. Call `list_posts` with `post_type: 'product'`
2. Optionally filter by `search`, `status`, or `per_page`
3. Returns title, ID, status, and URL for each product

### Get Product Details
1. Call `get_post` with the product ID
2. Returns title, content (description), excerpt (short description), status, meta (price, SKU, stock)

### Create a Product
1. Call `create_post` with: `post_type: 'product'`, `title`, `content` (long description), `excerpt` (short description), `status: 'draft'`
2. Tell the user: "I've created the product as a draft. To set the price, SKU, and inventory, go to **WooCommerce > Products** and edit the product."

### Search for a Product
1. Call `search_content` with `keyword: '<product name>'`
2. Or call `list_posts` with `post_type: 'product'` and `search: '<keyword>'`

### Check Store Currency / Settings
1. Call `get_option` with key `woocommerce_currency` — store currency code (e.g., `USD`)
2. Call `get_option` with key `woocommerce_default_country` — store country/state (e.g., `US:CA`)
3. Call `get_option` with key `woocommerce_calc_taxes` — tax calculation enabled (`yes`/`no`)

### Check WooCommerce Page Assignments
1. Call `get_option` with key `woocommerce_shop_page_id` — ID of the shop/archive page
2. Call `get_option` with key `woocommerce_cart_page_id`, `woocommerce_checkout_page_id`, `woocommerce_myaccount_page_id`

### Update Store Settings
1. Call `update_option` with key `woocommerce_currency` and the new currency code (requires confirmation)
2. Other common options: `woocommerce_weight_unit` (`kg`/`lbs`), `woocommerce_dimension_unit` (`cm`/`in`), `woocommerce_manage_stock`

## Common WooCommerce Option Keys
| Setting | Option Key | Example |
|---------|-----------|---------|
| Currency | `woocommerce_currency` | `'USD'` |
| Store country | `woocommerce_default_country` | `'US:CA'` |
| Tax calculation | `woocommerce_calc_taxes` | `'yes'` |
| Prices include tax | `woocommerce_prices_include_tax` | `'no'` |
| Stock management | `woocommerce_manage_stock` | `'yes'` |
| Weight unit | `woocommerce_weight_unit` | `'kg'` |
| Dimension unit | `woocommerce_dimension_unit` | `'cm'` |

## Important Notes
- Products use `post_type: 'product'` — always specify this in `list_posts` calls
- Wally cannot set product price, SKU, stock, or inventory via tools — guide user to WooCommerce admin to edit these fields
- Orders are stored in HPOS (custom tables in WooCommerce 8.0+) — Wally has no tools to access or manage orders; guide user to **WooCommerce > Orders**
- Customers and subscriptions must also be managed via **WooCommerce > Customers**
- Product categories use taxonomy `product_cat` — use `list_categories` or query via `list_posts`
- Variable products and product variations cannot be managed via Wally — guide user to WooCommerce admin
