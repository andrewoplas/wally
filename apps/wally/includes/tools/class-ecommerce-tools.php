<?php
namespace Wally\Tools;

/**
 * EDD, MemberPress, and LearnDash management tools.
 *
 * Tools: list_edd_downloads, get_edd_download, list_edd_orders, list_memberpress_memberships, list_learndash_courses.
 * Each tool has per-tool conditional registration.
 */

/**
 * List Easy Digital Downloads products (downloads).
 */
class ListEddDownloads extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'edd_get_download' );
	}

	public function get_name(): string {
		return 'list_edd_downloads';
	}

	public function get_description(): string {
		return 'List Easy Digital Downloads (EDD) products/downloads. Returns download ID, title, price, and type (simple or variable pricing).';
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
				'per_page' => [
					'type'        => 'integer',
					'description' => 'Number of downloads to return (max 100).',
					'default'     => 20,
				],
				'page'     => [
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				],
				'search'   => [
					'type'        => 'string',
					'description' => 'Search downloads by title.',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		$args = [
			'post_type'      => 'download',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = sanitize_text_field( $input['search'] );
		}

		$query     = new \WP_Query( $args );
		$downloads = $query->posts;
		$total     = $query->found_posts;

		$result = [];
		foreach ( $downloads as $post ) {
			$is_variable = get_post_meta( $post->ID, 'edd_variable_prices', true );
			$price       = $is_variable
				? null
				: (float) get_post_meta( $post->ID, 'edd_price', true );

			$result[] = [
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'price'        => $price,
				'pricing_type' => $is_variable ? 'variable' : 'simple',
				'status'       => $post->post_status,
				'permalink'    => get_permalink( $post->ID ),
			];
		}

		return [
			'downloads'   => $result,
			'total'       => (int) $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Get details of a single EDD download.
 */
class GetEddDownload extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'edd_get_download' );
	}

	public function get_name(): string {
		return 'get_edd_download';
	}

	public function get_description(): string {
		return 'Get full details of an Easy Digital Downloads product by ID, including price options, download files, and sales statistics.';
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
				'download_id' => [
					'type'        => 'integer',
					'description' => 'The EDD download post ID.',
				],
			],
			'required'   => [ 'download_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$download_id = absint( $input['download_id'] );
		$download    = edd_get_download( $download_id );

		if ( ! $download ) {
			return [ 'error' => "EDD download not found: {$download_id}" ];
		}

		$post           = get_post( $download_id );
		$variable_prices = get_post_meta( $download_id, 'edd_variable_prices', true );
		$files           = get_post_meta( $download_id, '_edd_download_files', true );

		$price_options = [];
		if ( is_array( $variable_prices ) ) {
			foreach ( $variable_prices as $price ) {
				$price_options[] = [
					'name'  => $price['name'] ?? '',
					'price' => (float) ( $price['amount'] ?? 0 ),
				];
			}
		}

		$file_list = [];
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				$file_list[] = [
					'name' => $file['name'] ?? '',
					'file' => $file['file'] ?? '',
				];
			}
		}

		return [
			'id'           => $download_id,
			'title'        => $post->post_title,
			'description'  => $post->post_content,
			'pricing_type' => ! empty( $variable_prices ) ? 'variable' : 'simple',
			'price'        => ! empty( $variable_prices ) ? null : (float) get_post_meta( $download_id, 'edd_price', true ),
			'price_options' => $price_options,
			'files'        => $file_list,
			'download_limit' => (int) get_post_meta( $download_id, '_edd_download_limit', true ),
			'product_type'   => get_post_meta( $download_id, '_edd_product_type', true ) ?: 'default',
			'permalink'    => get_permalink( $download_id ),
			'status'       => $post->post_status,
		];
	}
}

/**
 * List EDD orders.
 */
class ListEddOrders extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'edd_get_orders' ) || function_exists( 'edd_get_payments' );
	}

	public function get_name(): string {
		return 'list_edd_orders';
	}

	public function get_description(): string {
		return 'List Easy Digital Downloads orders/payments. Returns order ID, customer, amount, status, and date. Uses EDD 3.0+ orders API if available.';
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
					'description' => 'Filter by order status: "complete", "pending", "failed", "refunded", "abandoned".',
					'enum'        => [ 'complete', 'pending', 'failed', 'refunded', 'abandoned' ],
				],
				'per_page' => [
					'type'        => 'integer',
					'description' => 'Number of orders to return (max 100).',
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
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		// EDD 3.0+ uses edd_get_orders().
		if ( function_exists( 'edd_get_orders' ) ) {
			$args = [
				'number' => $per_page,
				'offset' => ( $page - 1 ) * $per_page,
				'order'  => 'DESC',
			];

			if ( ! empty( $input['status'] ) ) {
				$args['status'] = sanitize_key( $input['status'] );
			}

			$orders = edd_get_orders( $args );
			$total  = (int) edd_get_orders( array_merge( $args, [ 'count' => true, 'number' => 0 ] ) );

			$result = [];
			foreach ( $orders as $order ) {
				$result[] = [
					'id'       => $order->id,
					'status'   => $order->status,
					'email'    => $order->email,
					'total'    => (float) $order->total,
					'currency' => $order->currency,
					'date'     => $order->date_created,
					'ip'       => $order->ip,
				];
			}
		} else {
			// Legacy EDD 2.x payments.
			$args = [
				'number' => $per_page,
				'page'   => $page,
				'order'  => 'DESC',
			];

			if ( ! empty( $input['status'] ) ) {
				$args['status'] = sanitize_key( $input['status'] );
			}

			$payments = edd_get_payments( $args );
			$total    = edd_count_payments()->complete + edd_count_payments()->pending;

			$result = [];
			foreach ( $payments as $payment ) {
				$result[] = [
					'id'     => $payment->ID,
					'status' => get_post_status( $payment->ID ),
					'email'  => get_post_meta( $payment->ID, '_edd_payment_user_email', true ),
					'total'  => (float) get_post_meta( $payment->ID, '_edd_payment_total', true ),
					'date'   => $payment->post_date,
				];
			}
		}

		return [
			'orders'      => $result,
			'total'       => (int) $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * List MemberPress membership levels.
 */
class ListMemberpressMemberships extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'MeprUser' );
	}

	public function get_name(): string {
		return 'list_memberpress_memberships';
	}

	public function get_description(): string {
		return 'List MemberPress membership levels/products. Returns membership ID, title, price, billing period, and member count.';
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
				'per_page' => [
					'type'        => 'integer',
					'description' => 'Number of memberships to return (max 100).',
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
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		$query = new \WP_Query(
			[
				'post_type'      => 'memberpressproduct',
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$result = [];
		foreach ( $query->posts as $post ) {
			$product = new \MeprProduct( $post->ID );
			$result[] = [
				'id'          => $post->ID,
				'title'       => $post->post_title,
				'price'       => (float) $product->price,
				'period'      => (int) $product->period,
				'period_type' => $product->period_type,
				'trial'       => (bool) $product->trial,
				'permalink'   => get_permalink( $post->ID ),
			];
		}

		return [
			'memberships' => $result,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) ceil( $query->found_posts / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * List LearnDash courses.
 */
class ListLearnDashCourses extends ToolInterface {

	public static function can_register(): bool {
		return post_type_exists( 'sfwd-courses' ) || function_exists( 'learndash_get_course_steps' );
	}

	public function get_name(): string {
		return 'list_learndash_courses';
	}

	public function get_description(): string {
		return 'List LearnDash courses. Returns course ID, title, lesson count, enrollment count, and course status.';
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
				'per_page' => [
					'type'        => 'integer',
					'description' => 'Number of courses to return (max 100).',
					'default'     => 20,
				],
				'page'     => [
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				],
				'search'   => [
					'type'        => 'string',
					'description' => 'Search courses by title.',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		$args = [
			'post_type'      => 'sfwd-courses',
			'post_status'    => [ 'publish', 'draft' ],
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = sanitize_text_field( $input['search'] );
		}

		$query   = new \WP_Query( $args );
		$courses = $query->posts;

		$result = [];
		foreach ( $courses as $post ) {
			$steps = function_exists( 'learndash_get_course_steps' )
				? learndash_get_course_steps( $post->ID )
				: [];

			$result[] = [
				'id'          => $post->ID,
				'title'       => $post->post_title,
				'status'      => $post->post_status,
				'step_count'  => count( $steps ),
				'permalink'   => get_permalink( $post->ID ),
				'date_created' => $post->post_date,
			];
		}

		return [
			'courses'     => $result,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) ceil( $query->found_posts / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}
