<?php
namespace Wally\Tools;

/**
 * WooCommerce management tools.
 *
 * Tools: list_products, get_product, create_product, update_product, delete_product,
 *        list_orders, get_order, update_order_status, list_coupons, get_coupon.
 *
 * All tools require WooCommerce to be active (class_exists('WooCommerce')).
 */

/**
 * List WooCommerce products.
 */
class ListProducts extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function get_name(): string {
		return 'list_products';
	}

	public function get_description(): string {
		return 'List WooCommerce products with optional filters by status, type, category, or search. Returns product ID, name, price, stock status, SKU, and type.';
	}

	public function get_category(): string {
		return 'ecommerce';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'status'   => [
					'type'        => 'string',
					'description' => 'Filter by product status.',
					'enum'        => [ 'publish', 'draft', 'pending', 'private', 'any' ],
					'default'     => 'publish',
				],
				'type'     => [
					'type'        => 'string',
					'description' => 'Filter by product type: simple, variable, grouped, or external.',
					'enum'        => [ 'simple', 'variable', 'grouped', 'external' ],
				],
				'category' => [
					'type'        => 'string',
					'description' => 'Filter by product category slug.',
				],
				'search'   => [
					'type'        => 'string',
					'description' => 'Search products by name or SKU.',
				],
				'per_page' => [
					'type'        => 'integer',
					'description' => 'Number of products to return (max 100).',
					'default'     => 20,
				],
				'page'     => [
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				],
				'orderby'  => [
					'type'        => 'string',
					'description' => 'Field to order results by.',
					'enum'        => [ 'date', 'title', 'price', 'ID' ],
					'default'     => 'date',
				],
				'order'    => [
					'type'        => 'string',
					'description' => 'Sort direction.',
					'enum'        => [ 'ASC', 'DESC' ],
					'default'     => 'DESC',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		$args = [
			'limit'   => $per_page,
			'paged'   => $page,
			'status'  => sanitize_key( $input['status'] ?? 'publish' ),
			'orderby' => sanitize_key( $input['orderby'] ?? 'date' ),
			'order'   => strtoupper( $input['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC',
			'return'  => 'objects',
		];

		if ( ! empty( $input['type'] ) ) {
			$args['type'] = sanitize_key( $input['type'] );
		}
		if ( ! empty( $input['category'] ) ) {
			$args['category'] = [ sanitize_text_field( $input['category'] ) ];
		}
		if ( ! empty( $input['search'] ) ) {
			$args['s'] = sanitize_text_field( $input['search'] );
		}

		$products = wc_get_products( $args );

		// Get total count.
		$count_args          = $args;
		$count_args['limit'] = -1;
		$count_args['return'] = 'ids';
		$all_ids             = wc_get_products( $count_args );
		$total               = count( $all_ids );

		$result = [];
		foreach ( $products as $product ) {
			$result[] = [
				'id'           => $product->get_id(),
				'name'         => $product->get_name(),
				'type'         => $product->get_type(),
				'status'       => $product->get_status(),
				'sku'          => $product->get_sku(),
				'price'        => $product->get_price(),
				'regular_price' => $product->get_regular_price(),
				'sale_price'   => $product->get_sale_price(),
				'stock_status' => $product->get_stock_status(),
				'stock_qty'    => $product->get_stock_quantity(),
				'is_on_sale'   => $product->is_on_sale(),
			];
		}

		return [
			'products'    => $result,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Get full details of a single WooCommerce product.
 */
class GetProduct extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function get_name(): string {
		return 'get_product';
	}

	public function get_description(): string {
		return 'Get full details of a single WooCommerce product by ID. Returns name, price, stock, SKU, description, categories, tags, images, and dimensions.';
	}

	public function get_category(): string {
		return 'ecommerce';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'product_id' => [
					'type'        => 'integer',
					'description' => 'The WooCommerce product ID.',
				],
			],
			'required'   => [ 'product_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function execute( array $input ): array {
		$product_id = absint( $input['product_id'] );
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			return [ 'error' => "Product not found: {$product_id}" ];
		}

		// Category names.
		$categories = [];
		foreach ( $product->get_category_ids() as $cat_id ) {
			$term = get_term( $cat_id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$categories[] = [ 'id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug ];
			}
		}

		// Tag names.
		$tags = [];
		foreach ( $product->get_tag_ids() as $tag_id ) {
			$term = get_term( $tag_id, 'product_tag' );
			if ( $term && ! is_wp_error( $term ) ) {
				$tags[] = [ 'id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug ];
			}
		}

		return [
			'id'             => $product->get_id(),
			'name'           => $product->get_name(),
			'slug'           => $product->get_slug(),
			'type'           => $product->get_type(),
			'status'         => $product->get_status(),
			'description'    => $product->get_description(),
			'short_description' => $product->get_short_description(),
			'sku'            => $product->get_sku(),
			'price'          => $product->get_price(),
			'regular_price'  => $product->get_regular_price(),
			'sale_price'     => $product->get_sale_price(),
			'is_on_sale'     => $product->is_on_sale(),
			'stock_status'   => $product->get_stock_status(),
			'stock_quantity' => $product->get_stock_quantity(),
			'manage_stock'   => $product->get_manage_stock(),
			'weight'         => $product->get_weight(),
			'is_virtual'     => $product->is_virtual(),
			'is_downloadable' => $product->is_downloadable(),
			'categories'     => $categories,
			'tags'           => $tags,
			'image_id'       => $product->get_image_id(),
			'permalink'      => get_permalink( $product->get_id() ),
		];
	}
}

/**
 * Create a new WooCommerce product.
 */
class CreateProduct extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function get_name(): string {
		return 'create_product';
	}

	public function get_description(): string {
		return 'Create a new WooCommerce simple product with name, price, stock, SKU, description, and status.';
	}

	public function get_category(): string {
		return 'ecommerce';
	}

	public function get_action(): string {
		return 'create';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'name'           => [
					'type'        => 'string',
					'description' => 'Product name.',
				],
				'regular_price'  => [
					'type'        => 'string',
					'description' => 'Regular price (e.g., "29.99").',
				],
				'sale_price'     => [
					'type'        => 'string',
					'description' => 'Sale price. Leave empty for no sale.',
				],
				'sku'            => [
					'type'        => 'string',
					'description' => 'Stock keeping unit (unique identifier).',
				],
				'description'    => [
					'type'        => 'string',
					'description' => 'Full product description (HTML allowed).',
				],
				'short_description' => [
					'type'        => 'string',
					'description' => 'Short product description.',
				],
				'status'         => [
					'type'        => 'string',
					'description' => 'Product status.',
					'enum'        => [ 'publish', 'draft', 'private', 'pending' ],
					'default'     => 'draft',
				],
				'stock_quantity' => [
					'type'        => 'integer',
					'description' => 'Stock quantity. Leave empty if not managing stock.',
				],
				'manage_stock'   => [
					'type'        => 'boolean',
					'description' => 'Whether to manage stock for this product.',
					'default'     => false,
				],
				'virtual'        => [
					'type'        => 'boolean',
					'description' => 'Whether the product is virtual (no shipping required).',
					'default'     => false,
				],
			],
			'required'   => [ 'name', 'regular_price' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function execute( array $input ): array {
		$product = new \WC_Product_Simple();

		$product->set_name( sanitize_text_field( $input['name'] ) );
		$product->set_regular_price( wc_format_decimal( $input['regular_price'] ) );
		$product->set_status( sanitize_key( $input['status'] ?? 'draft' ) );

		if ( isset( $input['sale_price'] ) && $input['sale_price'] !== '' ) {
			$product->set_sale_price( wc_format_decimal( $input['sale_price'] ) );
		}
		if ( ! empty( $input['sku'] ) ) {
			$product->set_sku( sanitize_text_field( $input['sku'] ) );
		}
		if ( isset( $input['description'] ) ) {
			$product->set_description( wp_kses_post( $input['description'] ) );
		}
		if ( isset( $input['short_description'] ) ) {
			$product->set_short_description( wp_kses_post( $input['short_description'] ) );
		}
		if ( ! empty( $input['manage_stock'] ) ) {
			$product->set_manage_stock( true );
			if ( isset( $input['stock_quantity'] ) ) {
				$product->set_stock_quantity( absint( $input['stock_quantity'] ) );
			}
		}
		if ( ! empty( $input['virtual'] ) ) {
			$product->set_virtual( true );
		}

		$product_id = $product->save();

		if ( ! $product_id ) {
			return [ 'error' => 'Failed to create product.' ];
		}

		return [
			'product_id'    => $product_id,
			'name'          => $product->get_name(),
			'status'        => $product->get_status(),
			'regular_price' => $product->get_regular_price(),
			'permalink'     => get_permalink( $product_id ),
			'message'       => "Product \"{$product->get_name()}\" created successfully.",
		];
	}
}

/**
 * Update an existing WooCommerce product.
 */
class UpdateProduct extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function get_name(): string {
		return 'update_product';
	}

	public function get_description(): string {
		return 'Update an existing WooCommerce product\'s name, price, stock, SKU, description, or status. Provide product_id and any fields to change.';
	}

	public function get_category(): string {
		return 'ecommerce';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'product_id'     => [
					'type'        => 'integer',
					'description' => 'The WooCommerce product ID to update.',
				],
				'name'           => [
					'type'        => 'string',
					'description' => 'New product name.',
				],
				'regular_price'  => [
					'type'        => 'string',
					'description' => 'New regular price (e.g., "29.99").',
				],
				'sale_price'     => [
					'type'        => 'string',
					'description' => 'New sale price. Pass empty string to remove the sale.',
				],
				'sku'            => [
					'type'        => 'string',
					'description' => 'New SKU.',
				],
				'description'    => [
					'type'        => 'string',
					'description' => 'New full product description.',
				],
				'short_description' => [
					'type'        => 'string',
					'description' => 'New short product description.',
				],
				'status'         => [
					'type'        => 'string',
					'description' => 'New product status.',
					'enum'        => [ 'publish', 'draft', 'private', 'pending', 'trash' ],
				],
				'stock_quantity' => [
					'type'        => 'integer',
					'description' => 'New stock quantity.',
				],
			],
			'required'   => [ 'product_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function execute( array $input ): array {
		$product_id = absint( $input['product_id'] );
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			return [ 'error' => "Product not found: {$product_id}" ];
		}

		$changes = [];

		if ( isset( $input['name'] ) ) {
			$product->set_name( sanitize_text_field( $input['name'] ) );
			$changes[] = 'name';
		}
		if ( isset( $input['regular_price'] ) ) {
			$product->set_regular_price( wc_format_decimal( $input['regular_price'] ) );
			$changes[] = 'regular_price';
		}
		if ( isset( $input['sale_price'] ) ) {
			$product->set_sale_price( $input['sale_price'] !== '' ? wc_format_decimal( $input['sale_price'] ) : '' );
			$changes[] = 'sale_price';
		}
		if ( isset( $input['sku'] ) ) {
			$product->set_sku( sanitize_text_field( $input['sku'] ) );
			$changes[] = 'sku';
		}
		if ( isset( $input['description'] ) ) {
			$product->set_description( wp_kses_post( $input['description'] ) );
			$changes[] = 'description';
		}
		if ( isset( $input['short_description'] ) ) {
			$product->set_short_description( wp_kses_post( $input['short_description'] ) );
			$changes[] = 'short_description';
		}
		if ( isset( $input['status'] ) ) {
			$product->set_status( sanitize_key( $input['status'] ) );
			$changes[] = 'status';
		}
		if ( isset( $input['stock_quantity'] ) ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( absint( $input['stock_quantity'] ) );
			$changes[] = 'stock_quantity';
		}

		if ( empty( $changes ) ) {
			return [ 'error' => 'No fields provided to update.' ];
		}

		$product->save();

		return [
			'product_id' => $product_id,
			'name'       => $product->get_name(),
			'status'     => $product->get_status(),
			'updated'    => $changes,
			'message'    => 'Product updated successfully.',
		];
	}
}

/**
 * Delete a WooCommerce product. Requires confirmation.
 */
class DeleteProduct extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function get_name(): string {
		return 'delete_product';
	}

	public function get_description(): string {
		return 'Move a WooCommerce product to trash. This is a destructive action that requires confirmation.';
	}

	public function get_category(): string {
		return 'ecommerce';
	}

	public function get_action(): string {
		return 'delete';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'product_id' => [
					'type'        => 'integer',
					'description' => 'The WooCommerce product ID to delete.',
				],
			],
			'required'   => [ 'product_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$product_id = absint( $input['product_id'] );
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			return [ 'error' => "Product not found: {$product_id}" ];
		}

		$name   = $product->get_name();
		$result = wp_trash_post( $product_id );

		if ( ! $result ) {
			return [ 'error' => "Failed to delete product: {$product_id}" ];
		}

		return [
			'product_id' => $product_id,
			'name'       => $name,
			'message'    => "Product \"{$name}\" moved to trash.",
		];
	}
}

/**
 * List WooCommerce orders.
 */
class ListOrders extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function get_name(): string {
		return 'list_orders';
	}

	public function get_description(): string {
		return 'List WooCommerce orders with optional filters by status, customer ID, or date range. Returns order ID, status, total, customer, and date.';
	}

	public function get_category(): string {
		return 'ecommerce';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'status'      => [
					'type'        => 'string',
					'description' => 'Filter by order status: pending, processing, on-hold, completed, cancelled, refunded, failed.',
					'enum'        => [ 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed', 'any' ],
					'default'     => 'any',
				],
				'customer_id' => [
					'type'        => 'integer',
					'description' => 'Filter orders by customer user ID.',
				],
				'date_after'  => [
					'type'        => 'string',
					'description' => 'Return orders created after this date (YYYY-MM-DD).',
				],
				'per_page'    => [
					'type'        => 'integer',
					'description' => 'Number of orders to return (max 100).',
					'default'     => 20,
				],
				'page'        => [
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				],
				'orderby'     => [
					'type'        => 'string',
					'description' => 'Field to order results by.',
					'enum'        => [ 'date', 'ID', 'total' ],
					'default'     => 'date',
				],
				'order'       => [
					'type'        => 'string',
					'description' => 'Sort direction.',
					'enum'        => [ 'ASC', 'DESC' ],
					'default'     => 'DESC',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		$args = [
			'limit'   => $per_page,
			'paged'   => $page,
			'orderby' => sanitize_key( $input['orderby'] ?? 'date' ),
			'order'   => strtoupper( $input['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC',
			'return'  => 'objects',
		];

		$status = sanitize_key( $input['status'] ?? 'any' );
		if ( $status !== 'any' ) {
			$args['status'] = $status;
		}

		if ( ! empty( $input['customer_id'] ) ) {
			$args['customer_id'] = absint( $input['customer_id'] );
		}
		if ( ! empty( $input['date_after'] ) ) {
			$args['date_created'] = '>' . sanitize_text_field( $input['date_after'] );
		}

		$orders = wc_get_orders( $args );

		// Total count.
		$count_args          = $args;
		$count_args['limit'] = -1;
		$count_args['return'] = 'ids';
		$count_args['paged'] = 1;
		$all_ids             = wc_get_orders( $count_args );
		$total               = count( $all_ids );

		$result = [];
		foreach ( $orders as $order ) {
			$result[] = [
				'id'           => $order->get_id(),
				'status'       => $order->get_status(),
				'total'        => $order->get_total(),
				'currency'     => $order->get_currency(),
				'customer_id'  => $order->get_customer_id(),
				'customer_email' => $order->get_billing_email(),
				'date_created' => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
				'payment_method' => $order->get_payment_method(),
			];
		}

		return [
			'orders'      => $result,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Get full details of a single WooCommerce order.
 */
class GetOrder extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function get_name(): string {
		return 'get_order';
	}

	public function get_description(): string {
		return 'Get full details of a WooCommerce order by ID. Returns status, totals, billing/shipping address, line items, payment method, and customer info.';
	}

	public function get_category(): string {
		return 'ecommerce';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'order_id' => [
					'type'        => 'integer',
					'description' => 'The WooCommerce order ID.',
				],
			],
			'required'   => [ 'order_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function execute( array $input ): array {
		$order_id = absint( $input['order_id'] );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			return [ 'error' => "Order not found: {$order_id}" ];
		}

		// Line items.
		$items = [];
		foreach ( $order->get_items() as $item ) {
			$items[] = [
				'product_id' => $item->get_product_id(),
				'name'       => $item->get_name(),
				'quantity'   => $item->get_quantity(),
				'total'      => $item->get_total(),
			];
		}

		return [
			'id'              => $order->get_id(),
			'status'          => $order->get_status(),
			'total'           => $order->get_total(),
			'subtotal'        => $order->get_subtotal(),
			'shipping_total'  => $order->get_shipping_total(),
			'total_tax'       => $order->get_total_tax(),
			'currency'        => $order->get_currency(),
			'payment_method'  => $order->get_payment_method(),
			'customer_id'     => $order->get_customer_id(),
			'date_created'    => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
			'billing'         => [
				'first_name' => $order->get_billing_first_name(),
				'last_name'  => $order->get_billing_last_name(),
				'email'      => $order->get_billing_email(),
				'phone'      => $order->get_billing_phone(),
				'address_1'  => $order->get_billing_address_1(),
				'city'       => $order->get_billing_city(),
				'state'      => $order->get_billing_state(),
				'postcode'   => $order->get_billing_postcode(),
				'country'    => $order->get_billing_country(),
			],
			'shipping'        => [
				'first_name' => $order->get_shipping_first_name(),
				'last_name'  => $order->get_shipping_last_name(),
				'address_1'  => $order->get_shipping_address_1(),
				'city'       => $order->get_shipping_city(),
				'state'      => $order->get_shipping_state(),
				'postcode'   => $order->get_shipping_postcode(),
				'country'    => $order->get_shipping_country(),
			],
			'items'           => $items,
		];
	}
}

/**
 * Update a WooCommerce order's status. Requires confirmation.
 */
class UpdateOrderStatus extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function get_name(): string {
		return 'update_order_status';
	}

	public function get_description(): string {
		return 'Update the status of a WooCommerce order (e.g., mark as completed, cancelled, or processing). Requires confirmation as it affects the order lifecycle.';
	}

	public function get_category(): string {
		return 'ecommerce';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'order_id' => [
					'type'        => 'integer',
					'description' => 'The WooCommerce order ID to update.',
				],
				'status'   => [
					'type'        => 'string',
					'description' => 'New order status.',
					'enum'        => [ 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' ],
				],
				'note'     => [
					'type'        => 'string',
					'description' => 'Optional note to add when changing the status.',
				],
			],
			'required'   => [ 'order_id', 'status' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$order_id = absint( $input['order_id'] );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			return [ 'error' => "Order not found: {$order_id}" ];
		}

		$old_status = $order->get_status();
		$new_status = sanitize_key( $input['status'] );
		$note       = isset( $input['note'] ) ? sanitize_text_field( $input['note'] ) : '';

		$order->update_status( $new_status, $note );

		return [
			'order_id'   => $order_id,
			'old_status' => $old_status,
			'new_status' => $new_status,
			'message'    => "Order #{$order_id} status updated from \"{$old_status}\" to \"{$new_status}\".",
		];
	}
}

/**
 * List WooCommerce coupons.
 */
class ListCoupons extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function get_name(): string {
		return 'list_coupons';
	}

	public function get_description(): string {
		return 'List WooCommerce discount coupons with optional search filter. Returns coupon code, discount type, amount, usage count, and expiry date.';
	}

	public function get_category(): string {
		return 'ecommerce';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'search'   => [
					'type'        => 'string',
					'description' => 'Search coupons by code.',
				],
				'per_page' => [
					'type'        => 'integer',
					'description' => 'Number of coupons to return (max 100).',
					'default'     => 20,
				],
				'page'     => [
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		$args = [
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = sanitize_text_field( $input['search'] );
		}

		$query   = new \WP_Query( $args );
		$coupons = [];

		foreach ( $query->posts as $post ) {
			$coupon   = new \WC_Coupon( $post->ID );
			$expires  = $coupon->get_date_expires();
			$coupons[] = [
				'id'            => $post->ID,
				'code'          => $coupon->get_code(),
				'discount_type' => $coupon->get_discount_type(),
				'amount'        => $coupon->get_amount(),
				'usage_count'   => $coupon->get_usage_count(),
				'usage_limit'   => $coupon->get_usage_limit(),
				'expires'       => $expires ? $expires->date( 'Y-m-d' ) : null,
				'free_shipping' => $coupon->get_free_shipping(),
			];
		}

		return [
			'coupons'     => $coupons,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Get full details of a single WooCommerce coupon.
 */
class GetCoupon extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function get_name(): string {
		return 'get_coupon';
	}

	public function get_description(): string {
		return 'Get full details of a WooCommerce coupon by ID or coupon code. Returns discount type, amount, expiry, usage limits, and restrictions.';
	}

	public function get_category(): string {
		return 'ecommerce';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'coupon_id'   => [
					'type'        => 'integer',
					'description' => 'The WooCommerce coupon post ID.',
				],
				'coupon_code' => [
					'type'        => 'string',
					'description' => 'The coupon code to look up. Used if coupon_id is not provided.',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function execute( array $input ): array {
		if ( ! empty( $input['coupon_id'] ) ) {
			$coupon = new \WC_Coupon( absint( $input['coupon_id'] ) );
		} elseif ( ! empty( $input['coupon_code'] ) ) {
			$coupon = new \WC_Coupon( sanitize_text_field( $input['coupon_code'] ) );
		} else {
			return [ 'error' => 'Provide coupon_id or coupon_code.' ];
		}

		if ( ! $coupon->get_id() ) {
			return [ 'error' => 'Coupon not found.' ];
		}

		$expires = $coupon->get_date_expires();

		return [
			'id'                    => $coupon->get_id(),
			'code'                  => $coupon->get_code(),
			'description'           => $coupon->get_description(),
			'discount_type'         => $coupon->get_discount_type(),
			'amount'                => $coupon->get_amount(),
			'free_shipping'         => $coupon->get_free_shipping(),
			'usage_count'           => $coupon->get_usage_count(),
			'usage_limit'           => $coupon->get_usage_limit(),
			'usage_limit_per_user'  => $coupon->get_usage_limit_per_user(),
			'minimum_amount'        => $coupon->get_minimum_amount(),
			'maximum_amount'        => $coupon->get_maximum_amount(),
			'individual_use'        => $coupon->get_individual_use(),
			'exclude_sale_items'    => $coupon->get_exclude_sale_items(),
			'expires'               => $expires ? $expires->date( 'Y-m-d' ) : null,
			'product_ids'           => $coupon->get_product_ids(),
			'excluded_product_ids'  => $coupon->get_excluded_product_ids(),
			'email_restrictions'    => $coupon->get_email_restrictions(),
		];
	}
}
