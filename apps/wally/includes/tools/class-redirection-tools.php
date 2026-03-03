<?php
namespace Wally\Tools;

/**
 * Redirection plugin management tools.
 *
 * Tools: list_redirects, create_redirect, update_redirect, delete_redirect, get_404_logs.
 * All tools require the Redirection plugin to be active (class_exists('Red_Item')).
 */

/**
 * List URL redirects managed by the Redirection plugin.
 */
class ListRedirects extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'Red_Item' );
	}

	public function get_name(): string {
		return 'list_redirects';
	}

	public function get_description(): string {
		return 'List URL redirects managed by the Redirection plugin. Returns source URL, destination URL, redirect code, status, and hit count.';
	}

	public function get_category(): string {
		return 'site';
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
					'description' => 'Filter redirects by source URL.',
				],
				'status'   => [
					'type'        => 'string',
					'description' => 'Filter by status: "enabled" or "disabled".',
					'enum'        => [ 'enabled', 'disabled' ],
				],
				'per_page' => [
					'type'        => 'integer',
					'description' => 'Number of redirects to return (max 100).',
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

		$params = [
			'per_page' => $per_page,
			'page'     => $page,
			'orderby'  => 'id',
			'direction' => 'DESC',
			'filter'   => [],
		];

		if ( ! empty( $input['search'] ) ) {
			$params['filter']['url'] = sanitize_text_field( $input['search'] );
		}
		if ( ! empty( $input['status'] ) ) {
			$params['filter']['status'] = sanitize_key( $input['status'] );
		}

		$results = \Red_Item::get_filtered( $params );

		$redirects = [];
		$total     = 0;

		if ( isset( $results['items'] ) && is_array( $results['items'] ) ) {
			$total = $results['total'] ?? count( $results['items'] );
			foreach ( $results['items'] as $item ) {
				$data = $item->to_json();
				$redirects[] = [
					'id'          => $data['id'],
					'source_url'  => $data['url'],
					'target_url'  => $data['action_data']['url'] ?? '',
					'code'        => $data['action_code'],
					'regex'       => (bool) $data['regex'],
					'status'      => $data['status'],
					'hit_count'   => $data['last_count'] ?? 0,
					'group_id'    => $data['group_id'],
				];
			}
		}

		return [
			'redirects'   => $redirects,
			'total'       => (int) $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Create a new URL redirect using the Redirection plugin.
 */
class CreateRedirect extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'Red_Item' );
	}

	public function get_name(): string {
		return 'create_redirect';
	}

	public function get_description(): string {
		return 'Create a new URL redirect using the Redirection plugin. Supports 301, 302, 307, 410 redirect codes and optional regex source URLs.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'create';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'source_url' => [
					'type'        => 'string',
					'description' => 'Source URL to redirect from (relative path, e.g., /old-page/).',
				],
				'target_url' => [
					'type'        => 'string',
					'description' => 'Destination URL to redirect to.',
				],
				'code'       => [
					'type'        => 'integer',
					'description' => 'HTTP redirect code: 301 (permanent), 302 (temporary), 307 (temporary, preserve method), 410 (gone).',
					'enum'        => [ 301, 302, 307, 308, 410 ],
					'default'     => 301,
				],
				'regex'      => [
					'type'        => 'boolean',
					'description' => 'Whether the source URL uses regex pattern matching. Default: false.',
					'default'     => false,
				],
			],
			'required'   => [ 'source_url', 'target_url' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$source = sanitize_text_field( $input['source_url'] );
		$target = esc_url_raw( $input['target_url'] );
		$code   = (int) ( $input['code'] ?? 301 );
		$regex  = ! empty( $input['regex'] );

		$details = [
			'url'         => $source,
			'action_type' => 'url',
			'action_code' => $code,
			'action_data' => [ 'url' => $target ],
			'regex'       => $regex ? 1 : 0,
			'group_id'    => 1, // Default group.
			'match_type'  => 'url',
		];

		$result = \Red_Item::create( $details );

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}

		if ( ! $result ) {
			return [ 'error' => 'Failed to create redirect.' ];
		}

		$data = $result->to_json();

		return [
			'id'         => $data['id'],
			'source_url' => $source,
			'target_url' => $target,
			'code'       => $code,
			'message'    => "Redirect from \"{$source}\" to \"{$target}\" created successfully.",
		];
	}
}

/**
 * Update an existing Redirection plugin redirect.
 */
class UpdateRedirect extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'Red_Item' );
	}

	public function get_name(): string {
		return 'update_redirect';
	}

	public function get_description(): string {
		return 'Update an existing Redirection plugin redirect\'s source URL, target URL, or redirect code. Provide redirect_id and any fields to change.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'redirect_id' => [
					'type'        => 'integer',
					'description' => 'The Redirection plugin redirect ID.',
				],
				'source_url'  => [
					'type'        => 'string',
					'description' => 'New source URL.',
				],
				'target_url'  => [
					'type'        => 'string',
					'description' => 'New destination URL.',
				],
				'code'        => [
					'type'        => 'integer',
					'description' => 'New HTTP redirect code.',
					'enum'        => [ 301, 302, 307, 308, 410 ],
				],
				'enabled'     => [
					'type'        => 'boolean',
					'description' => 'Enable (true) or disable (false) the redirect.',
				],
			],
			'required'   => [ 'redirect_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$redirect_id = absint( $input['redirect_id'] );
		$redirect    = \Red_Item::get_by_id( $redirect_id );

		if ( ! $redirect ) {
			return [ 'error' => "Redirect not found: {$redirect_id}" ];
		}

		$data    = $redirect->to_json();
		$changes = [];

		$update_params = [
			'url'         => $data['url'],
			'action_code' => $data['action_code'],
			'action_type' => $data['action_type'],
			'action_data' => $data['action_data'],
			'regex'       => $data['regex'],
			'match_type'  => $data['match_type'],
			'group_id'    => $data['group_id'],
		];

		if ( isset( $input['source_url'] ) ) {
			$update_params['url'] = sanitize_text_field( $input['source_url'] );
			$changes[]            = 'source_url';
		}
		if ( isset( $input['target_url'] ) ) {
			$update_params['action_data'] = [ 'url' => esc_url_raw( $input['target_url'] ) ];
			$changes[]                    = 'target_url';
		}
		if ( isset( $input['code'] ) ) {
			$update_params['action_code'] = (int) $input['code'];
			$changes[]                    = 'code';
		}

		if ( isset( $input['enabled'] ) ) {
			if ( $input['enabled'] ) {
				$redirect->enable();
			} else {
				$redirect->disable();
			}
			$changes[] = 'enabled';
		}

		if ( ! empty( array_diff( $changes, [ 'enabled' ] ) ) ) {
			$redirect->update( $update_params );
		}

		if ( empty( $changes ) ) {
			return [ 'error' => 'No fields provided to update.' ];
		}

		return [
			'redirect_id' => $redirect_id,
			'updated'     => $changes,
			'message'     => "Redirect #{$redirect_id} updated successfully.",
		];
	}
}

/**
 * Delete a Redirection plugin redirect. Requires confirmation.
 */
class DeleteRedirect extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'Red_Item' );
	}

	public function get_name(): string {
		return 'delete_redirect';
	}

	public function get_description(): string {
		return 'Delete a Redirection plugin redirect by ID. This is a destructive action that requires confirmation.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'delete';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'redirect_id' => [
					'type'        => 'integer',
					'description' => 'The Redirection plugin redirect ID to delete.',
				],
			],
			'required'   => [ 'redirect_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$redirect_id = absint( $input['redirect_id'] );
		$redirect    = \Red_Item::get_by_id( $redirect_id );

		if ( ! $redirect ) {
			return [ 'error' => "Redirect not found: {$redirect_id}" ];
		}

		$data   = $redirect->to_json();
		$source = $data['url'];

		$redirect->delete();

		return [
			'redirect_id' => $redirect_id,
			'source_url'  => $source,
			'message'     => "Redirect #{$redirect_id} (\"{$source}\") deleted successfully.",
		];
	}
}

/**
 * Get recent 404 error logs from the Redirection plugin.
 */
class Get404Logs extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'Red_Item' );
	}

	public function get_name(): string {
		return 'get_404_logs';
	}

	public function get_description(): string {
		return 'Get recent 404 error logs tracked by the Redirection plugin. Returns the 404 URL, referrer, IP, and date/time of each error.';
	}

	public function get_category(): string {
		return 'site';
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
					'description' => 'Number of 404 log entries to return (max 100).',
					'default'     => 20,
				],
				'page'     => [
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				],
				'search'   => [
					'type'        => 'string',
					'description' => 'Filter 404 logs by URL.',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		global $wpdb;

		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );
		$offset   = ( $page - 1 ) * $per_page;

		$table = $wpdb->prefix . 'redirection_404';

		// Verify table exists.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $table_exists ) {
			return [ 'error' => 'Redirection 404 log table not found. Ensure the Redirection plugin is fully installed.' ];
		}

		if ( ! empty( $input['search'] ) ) {
			$search  = '%' . $wpdb->esc_like( sanitize_text_field( $input['search'] ) ) . '%';
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, created, url, referrer, ip FROM {$table} WHERE url LIKE %s ORDER BY id DESC LIMIT %d OFFSET %d",
					$search,
					$per_page,
					$offset
				),
				ARRAY_A
			);
			$total   = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE url LIKE %s", $search )
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, created, url, referrer, ip FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
					$per_page,
					$offset
				),
				ARRAY_A
			);
			$total   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}

		return [
			'logs'        => $results ?: [],
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}
