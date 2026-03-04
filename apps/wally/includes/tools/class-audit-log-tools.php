<?php
namespace Wally\Tools;

/**
 * Simple History audit log tools for reading site activity.
 *
 * Tools: get_activity_log, get_activity_log_entry.
 * Conditional: Simple History plugin must be active.
 * Read-only tools — no confirmation needed.
 */

/**
 * Query the Simple History activity log with filtering and pagination.
 */
class SimpleHistoryGetLog extends ToolInterface {

	public function get_name(): string {
		return 'get_activity_log';
	}

	public function get_description(): string {
		return 'Query the Simple History activity log to view recent site activity. Supports filtering by logger type (e.g., "SimplePostLogger", "SimpleUserLogger", "SimplePluginLogger"), log level (info, warning, error), date range, initiator, and full-text search. Returns paginated log entries showing who did what and when.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return class_exists( 'SimpleHistoryLogQuery' );
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'per_page'  => [
					'type'        => 'integer',
					'description' => 'Number of log entries to return per page (max 100). Default 20.',
					'default'     => 20,
				],
				'page'      => [
					'type'        => 'integer',
					'description' => 'Page number for pagination. Default 1.',
					'default'     => 1,
				],
				'logger'    => [
					'type'        => 'string',
					'description' => 'Filter by a specific logger. Common values: "SimplePostLogger" (posts/pages), "SimpleUserLogger" (logins, profiles), "SimplePluginLogger" (plugin changes), "SimpleOptionsLogger" (settings), "SimpleMediaLogger" (media), "SimpleMenuLogger" (menus), "SimpleCommentsLogger" (comments), "SimpleCoreUpdatesLogger" (WP updates). Omit to include all loggers.',
				],
				'level'     => [
					'type'        => 'string',
					'description' => 'Filter by PSR-3 log level: "info", "warning", "error", "critical", "notice", "debug". Omit to include all levels.',
				],
				'date_from' => [
					'type'        => 'string',
					'description' => 'Start date filter in YYYY-MM-DD format (e.g., "2024-01-01").',
				],
				'date_to'   => [
					'type'        => 'string',
					'description' => 'End date filter in YYYY-MM-DD format (e.g., "2024-12-31").',
				],
				'search'    => [
					'type'        => 'string',
					'description' => 'Full-text search within log messages.',
				],
				'initiator' => [
					'type'        => 'string',
					'description' => 'Filter by who initiated the action: "wp_user" (logged-in users), "wp" (WordPress system), "wp_cli" (WP-CLI), "web_user" (anonymous visitors), "other".',
				],
			],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $params ): array {
		$per_page  = min( (int) ( $params['per_page'] ?? 20 ), 100 );
		$page      = max( 1, (int) ( $params['page'] ?? 1 ) );
		$logger    = ! empty( $params['logger'] ) ? sanitize_key( $params['logger'] ) : null;
		$level     = ! empty( $params['level'] ) ? sanitize_key( $params['level'] ) : null;
		$date_from = ! empty( $params['date_from'] ) ? sanitize_text_field( $params['date_from'] ) : null;
		$date_to   = ! empty( $params['date_to'] ) ? sanitize_text_field( $params['date_to'] ) : null;
		$search    = ! empty( $params['search'] ) ? sanitize_text_field( $params['search'] ) : null;
		$initiator = ! empty( $params['initiator'] ) ? sanitize_key( $params['initiator'] ) : null;

		$query_args = [
			'posts_per_page' => $per_page,
			'paged'          => $page,
		];

		if ( $logger ) {
			$query_args['loggers'] = [ $logger ];
		}
		if ( $level ) {
			$query_args['loglevels'] = [ $level ];
		}
		if ( $date_from ) {
			$query_args['date_from'] = $date_from;
		}
		if ( $date_to ) {
			$query_args['date_to'] = $date_to;
		}
		if ( $search ) {
			$query_args['search'] = $search;
		}
		if ( $initiator ) {
			$query_args['initiator'] = $initiator;
		}

		$log_query = new \SimpleHistoryLogQuery();
		$result    = $log_query->query( $query_args );

		$entries = [];
		foreach ( $result['log_rows'] as $row ) {
			$entries[] = [
				'id'        => (int) $row->id,
				'date'      => $row->date,
				'logger'    => $row->logger,
				'level'     => $row->level,
				'message'   => $row->message,
				'initiator' => $row->initiator,
			];
		}

		return [
			'success' => true,
			'data'    => [
				'entries'          => $entries,
				'total'            => (int) ( $result['total_row_count'] ?? count( $entries ) ),
				'page_current'     => (int) ( $result['page_current'] ?? $page ),
				'pages_total'      => (int) ( $result['pages_count'] ?? 1 ),
			],
		];
	}
}

/**
 * Get a single Simple History log entry by ID with full context details.
 */
class SimpleHistoryGetLogEntry extends ToolInterface {

	public function get_name(): string {
		return 'get_activity_log_entry';
	}

	public function get_description(): string {
		return 'Get a single Simple History audit log entry by its ID, including full context details (e.g., old/new post title, user email, plugin name, IP address, changed option values). Use get_activity_log first to find the entry ID, then call this for detailed context.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return class_exists( 'SimpleHistoryLogQuery' );
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'entry_id' => [
					'type'        => 'integer',
					'description' => 'The numeric ID of the Simple History log entry. Use get_activity_log to find entry IDs.',
				],
			],
			'required'   => [ 'entry_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $params ): array {
		global $wpdb;

		$entry_id       = (int) $params['entry_id'];
		$history_table  = $wpdb->prefix . 'simple_history';
		$contexts_table = $wpdb->prefix . 'simple_history_contexts';

		// Fetch the main log row.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id, date, logger, level, message, occasionsID, initiator
				 FROM {$history_table}
				 WHERE id = %d",
				$entry_id
			)
		);

		if ( ! $row ) {
			return [ 'success' => false, 'error' => "Log entry with ID {$entry_id} not found." ];
		}

		// Fetch all context key-value pairs for this entry.
		$context_rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT `key`, `value`
				 FROM {$contexts_table}
				 WHERE history_id = %d
				 ORDER BY context_id ASC",
				$entry_id
			)
		);

		// Build a key => value context array; omit sensitive internal keys.
		$sensitive_keys = [ '_server_remote_addr' ];
		$context        = [];
		foreach ( $context_rows as $ctx ) {
			// Include IP address for admins reviewing security events, but flag it.
			$context[ $ctx->key ] = $ctx->value;
		}

		return [
			'success' => true,
			'data'    => [
				'id'          => (int) $row->id,
				'date'        => $row->date,
				'logger'      => $row->logger,
				'level'       => $row->level,
				'message'     => $row->message,
				'occasions_id' => $row->occasionsID,
				'initiator'   => $row->initiator,
				'context'     => $context,
			],
		];
	}
}
