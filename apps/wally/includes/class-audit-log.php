<?php
namespace Wally;

/**
 * Audit log for tool executions.
 *
 * Every tool execution (success, failure, or cancellation) is recorded
 * in the wally_actions table. Admins can query the log for review.
 */
class AuditLog {

	/**
	 * Log a tool execution to the actions table.
	 *
	 * @param array $data {
	 *     @type int    $conversation_id  Conversation context.
	 *     @type int    $message_id       Message that triggered the action (optional).
	 *     @type int    $user_id          WordPress user who triggered it.
	 *     @type string $tool_name        Tool identifier.
	 *     @type array  $tool_input       Input parameters (stored as JSON).
	 *     @type array  $tool_output      Output result (stored as JSON).
	 *     @type string $status           'success', 'failed', 'cancelled', 'pending'.
	 * }
	 * @return int|false Inserted row ID or false on failure.
	 */
	public static function log_action( array $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wally_actions';

		$result = $wpdb->insert(
			$table,
			[
				'conversation_id' => $data['conversation_id'] ?? 0,
				'message_id'      => $data['message_id'] ?? null,
				'user_id'         => $data['user_id'],
				'tool_name'       => $data['tool_name'],
				'tool_input'      => wp_json_encode( $data['tool_input'] ?? [] ),
				'tool_output'     => wp_json_encode( $data['tool_output'] ?? [] ),
				'status'          => $data['status'] ?? 'success',
				'created_at'      => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get a single action by ID.
	 *
	 * @param int $action_id Action row ID.
	 * @return object|null Action row or null.
	 */
	public static function get_action( int $action_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wally_actions';

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $action_id )
		);
	}

	/**
	 * Update an action's status and output.
	 *
	 * Used to finalize pending actions after confirmation or rejection.
	 *
	 * @param int   $action_id Action row ID.
	 * @param array $updates   Fields to update (status, tool_output).
	 * @return bool Whether the update succeeded.
	 */
	public static function update_action( int $action_id, array $updates ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'wally_actions';

		$data   = [];
		$format = [];

		if ( isset( $updates['status'] ) ) {
			$data['status'] = $updates['status'];
			$format[]       = '%s';
		}

		if ( isset( $updates['tool_output'] ) ) {
			$data['tool_output'] = wp_json_encode( $updates['tool_output'] );
			$format[]            = '%s';
		}

		if ( empty( $data ) ) {
			return false;
		}

		$result = $wpdb->update( $table, $data, [ 'id' => $action_id ], $format, [ '%d' ] );

		return $result !== false;
	}

	/**
	 * Query actions with optional filters.
	 *
	 * @param array $filters {
	 *     @type int    $user_id     Filter by user.
	 *     @type string $tool_name   Filter by tool.
	 *     @type string $status      Filter by status.
	 *     @type string $date_from   Filter actions after this date (Y-m-d).
	 *     @type string $date_to     Filter actions before this date (Y-m-d).
	 *     @type int    $per_page    Results per page (default 50, max 100).
	 *     @type int    $page        Page number (default 1).
	 * }
	 * @return array { 'items' => array, 'total' => int }
	 */
	public static function get_actions( array $filters = [] ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'wally_actions';

		$where  = [];
		$values = [];

		if ( ! empty( $filters['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$values[] = (int) $filters['user_id'];
		}

		if ( ! empty( $filters['tool_name'] ) ) {
			$where[]  = 'tool_name = %s';
			$values[] = $filters['tool_name'];
		}

		if ( ! empty( $filters['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $filters['status'];
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$where[]  = 'created_at >= %s';
			$values[] = $filters['date_from'] . ' 00:00:00';
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where[]  = 'created_at <= %s';
			$values[] = $filters['date_to'] . ' 23:59:59';
		}

		$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$per_page = min( (int) ( $filters['per_page'] ?? 50 ), 100 );
		$page     = max( (int) ( $filters['page'] ?? 1 ), 1 );
		$offset   = ( $page - 1 ) * $per_page;

		// Count total matching rows.
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
		if ( $values ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$values ) );
		} else {
			$total = (int) $wpdb->get_var( $count_sql );
		}

		// Fetch page of results.
		$query_sql = "SELECT * FROM {$table} {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$all_values = array_merge( $values, [ $per_page, $offset ] );
		$items = $wpdb->get_results( $wpdb->prepare( $query_sql, ...$all_values ) );

		return [
			'items' => $items ?: [],
			'total' => $total,
		];
	}
}
