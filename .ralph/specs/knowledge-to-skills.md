# Knowledge to Skills Conversion — Spec

## Goal
Transform 64 knowledge .md files from reference documentation into prescriptive skills that tell the LLM exactly how to use Wally's tools to accomplish WordPress tasks.

## Before/After Example

### Before (reference doc style — `woocommerce.md`):
```markdown
# WooCommerce

## Data Model
Products are stored as `product` post type in wp_posts.
- `_price` — product price (postmeta)
- `_regular_price` — regular price
- `_sale_price` — sale price
- `_sku` — stock keeping unit
- `_stock` — stock quantity
- `_stock_status` — instock, outofstock, onbackorder

Orders are stored as `shop_order` post type...

## Database Tables
- `wp_wc_orders` — order data (HPOS)
- `wp_wc_order_items` — line items
...
```

### After (skill style — `woocommerce.md`):
```markdown
# WooCommerce

## When to Use
- User wants to manage products, orders, or store settings
- User mentions WooCommerce, products, prices, inventory, or shop
- Site has WooCommerce active (check via `list_plugins`)

## Available Tools
- `list_posts` with `post_type: 'product'` — list products
- `get_post` — get product details
- `create_post` with `post_type: 'product'` — create product (draft)
- `update_post` — update product title, content, status
- `search_content` — find products by content
- `get_option` — read WooCommerce settings (e.g., `woocommerce_currency`)
- `update_option` — change WooCommerce settings

## Workflows

### List Products
1. Call `list_posts` with `post_type: 'product'`
2. Results include title, status, and meta (price, sku if available)

### Create a Product
1. Call `create_post` with: `post_type: 'product'`, `title`, `content` (description), `status: 'draft'`
2. Note: Price, SKU, and inventory must be set in WooCommerce admin — Wally cannot set product meta directly
3. Tell the user: "I've created the product as a draft. To set the price and inventory, go to WooCommerce > Products."

### Check Store Settings
1. Call `get_option` with key `woocommerce_currency` for currency
2. Call `get_option` with key `woocommerce_default_country` for location
3. Other useful options: `woocommerce_calc_taxes`, `woocommerce_enable_coupons`

## Important Notes
- Products use `post_type: 'product'` — always specify this in tool calls
- Orders use HPOS (High-Performance Order Storage) in newer WooCommerce — not accessible via standard post tools
- WooCommerce settings are in wp_options with `woocommerce_` prefix
- For complex store setup (payment gateways, shipping zones), guide user to WooCommerce admin
```

## File Categories

### Full Skill Conversion (Loops 1-7): ~46 files
Plugin-specific and content management files. These describe things the LLM actively helps users DO.

### Light Touch Reference (Loops 8-9): ~18 wp-*.md files
WordPress API reference files. These support the LLM's understanding but don't map directly to tool workflows. Just add "When to Use" and "Key Patterns" sections.

## Constraints
- File names must NOT change (intent classifier maps by filename stem)
- Each file shares a 5000-token knowledge budget — keep files 50-150 lines
- Only reference tools that actually exist (see AGENT.md for full list)
- Don't modify general.md, content.md, or wally-capabilities.md (already done)
