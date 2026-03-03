<?php
namespace Wally\Tools;

/**
 * WooCommerce Subscriptions management tools.
 *
 * Tools: list_subscriptions, get_subscription, update_subscription_status.
 * All tools require WooCommerce Subscriptions to be active (class_exists('WC_Subscriptions')).
 * APIs: wcs_get_subscriptions(), wcs_get_subscription(), WC_Subscription methods.
 */

/**
 * List WooCommerce Subscriptions.
 */
class ListSubscriptions extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WC_Subscriptions' );
	}

	public function get_name(): string {
		return 'list_subscriptions';
	}

	public function get_description(): string {
		return 'List WooCommerce Subscriptions. Returns subscription ID, status, customer, billing schedule, next payment date, and total. Supports filtering by status and customer.';
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
					'description' => 'Filter by subscription status: "active", "cancelled", "pending", "pending-cancel", "expired", "on-hold". Leave empty for all statuses.',
					'enum'        => [ 'active', 'cancelled', 'pending', 'pending-cancel', 'expired', 'on-hold' ],
				],
				'customer_id' => [
					'type'        => 'integer',
					'description' => 'Filter subscriptions by customer user ID.',
				],
				'per_page'    => [
					'type'        => 'integer',
					'description' => 'Number of subscriptions to return (max 100).',
					'default'     => 20,
				],
				'page'        => [
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
			'subscriptions_per_page' => $per_page,
			'paged'                  => $page,
			'orderby'                => 'start_date',
			'order'                  => 'DESC',
		];

		if ( ! empty( $input['status'] ) ) {
			$args['subscription_status'] = sanitize_key( $input['status'] );
		}
		if ( ! empty( $input['customer_id'] ) ) {
			$args['customer_id'] = absint( $input['customer_id'] );
		}

		$subscriptions = wcs_get_subscriptions( $args );

		// Get total for pagination.
		$total_args                         = $args;
		$total_args['subscriptions_per_page'] = -1;
		$total_args['paged']                = 1;
		$all_subs = wcs_get_subscriptions( $total_args );
		$total    = count( $all_subs );

		$result = [];
		foreach ( $subscriptions as $subscription ) {
			$result[] = [
				'id'               => $subscription->get_id(),
				'status'           => $subscription->get_status(),
				'customer_id'      => $subscription->get_customer_id(),
				'billing_period'   => $subscription->get_billing_period(),
				'billing_interval' => $subscription->get_billing_interval(),
				'total'            => $subscription->get_total(),
				'currency'         => $subscription->get_currency(),
				'start_date'       => $subscription->get_date( 'start' ),
				'next_payment'     => $subscription->get_date( 'next_payment' ),
				'end_date'         => $subscription->get_date( 'end' ),
				'trial_end'        => $subscription->get_date( 'trial_end' ),
				'parent_order_id'  => $subscription->get_parent_id(),
			];
		}

		return [
			'subscriptions' => $result,
			'total'         => $total,
			'total_pages'   => (int) ceil( $total / $per_page ),
			'page'          => $page,
			'per_page'      => $per_page,
		];
	}
}

/**
 * Get details of a single WooCommerce Subscription.
 */
class GetSubscription extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WC_Subscriptions' );
	}

	public function get_name(): string {
		return 'get_subscription';
	}

	public function get_description(): string {
		return 'Get full details of a WooCommerce Subscription by ID, including items, billing schedule, dates, customer info, and payment history.';
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
				'subscription_id' => [
					'type'        => 'integer',
					'description' => 'The WooCommerce Subscription ID.',
				],
			],
			'required'   => [ 'subscription_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function execute( array $input ): array {
		$subscription_id = absint( $input['subscription_id'] );
		$subscription    = wcs_get_subscription( $subscription_id );

		if ( ! $subscription ) {
			return [ 'error' => "Subscription not found: {$subscription_id}" ];
		}

		// Get line items.
		$items = [];
		foreach ( $subscription->get_items() as $item ) {
			$items[] = [
				'product_id'   => $item->get_product_id(),
				'variation_id' => $item->get_variation_id(),
				'name'         => $item->get_name(),
				'quantity'     => $item->get_quantity(),
				'total'        => $item->get_total(),
			];
		}

		// Get related orders.
		$related_orders = [];
		foreach ( $subscription->get_related_orders( 'all', [ 'renewal', 'resubscribe', 'switch' ] ) as $order_id => $order_type ) {
			$related_orders[] = [
				'id'   => $order_id,
				'type' => $order_type,
			];
		}

		return [
			'id'                 => $subscription->get_id(),
			'status'             => $subscription->get_status(),
			'customer_id'        => $subscription->get_customer_id(),
			'billing_period'     => $subscription->get_billing_period(),
			'billing_interval'   => $subscription->get_billing_interval(),
			'total'              => $subscription->get_total(),
			'currency'           => $subscription->get_currency(),
			'payment_method'     => $subscription->get_payment_method(),
			'payment_method_title' => $subscription->get_payment_method_title(),
			'dates'              => [
				'start'        => $subscription->get_date( 'start' ),
				'trial_end'    => $subscription->get_date( 'trial_end' ),
				'next_payment' => $subscription->get_date( 'next_payment' ),
				'last_payment' => $subscription->get_date( 'last_payment' ),
				'end'          => $subscription->get_date( 'end' ),
			],
			'billing_address'    => [
				'first_name' => $subscription->get_billing_first_name(),
				'last_name'  => $subscription->get_billing_last_name(),
				'email'      => $subscription->get_billing_email(),
				'phone'      => $subscription->get_billing_phone(),
			],
			'items'              => $items,
			'related_orders'     => $related_orders,
			'parent_order_id'    => $subscription->get_parent_id(),
		];
	}
}

/**
 * Update WooCommerce Subscription status.
 */
class UpdateSubscriptionStatus extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WC_Subscriptions' );
	}

	public function get_name(): string {
		return 'update_subscription_status';
	}

	public function get_description(): string {
		return 'Update a WooCommerce Subscription\'s status (activate, cancel, put on hold, etc.). This is a confirmation-required action as it affects recurring billing.';
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
				'subscription_id' => [
					'type'        => 'integer',
					'description' => 'The WooCommerce Subscription ID to update.',
				],
				'status'          => [
					'type'        => 'string',
					'description' => 'New subscription status.',
					'enum'        => [ 'active', 'cancelled', 'pending', 'pending-cancel', 'expired', 'on-hold' ],
				],
			],
			'required'   => [ 'subscription_id', 'status' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_woocommerce';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$subscription_id = absint( $input['subscription_id'] );
		$new_status      = sanitize_key( $input['status'] );

		$subscription = wcs_get_subscription( $subscription_id );

		if ( ! $subscription ) {
			return [ 'error' => "Subscription not found: {$subscription_id}" ];
		}

		$old_status = $subscription->get_status();

		if ( $old_status === $new_status ) {
			return [
				'subscription_id' => $subscription_id,
				'status'          => $new_status,
				'message'         => "Subscription #{$subscription_id} is already in status \"{$new_status}\".",
			];
		}

		$subscription->update_status( $new_status );

		return [
			'subscription_id' => $subscription_id,
			'old_status'      => $old_status,
			'new_status'      => $subscription->get_status(),
			'message'         => "Subscription #{$subscription_id} status updated from \"{$old_status}\" to \"{$new_status}\" successfully.",
		];
	}
}
