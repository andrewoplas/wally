<?php
namespace Wally\Tools;

/**
 * Wordfence security plugin management tools.
 *
 * Tools: get_wordfence_scan_status, list_wordfence_blocked_ips, run_wordfence_scan.
 * All tools require Wordfence to be active (class_exists('wordfence')).
 *
 * Uses Wordfence's wfConfig, wfIssues, and wfBlockedIPLog database tables.
 * Note: Security data is sensitive — read-only tools expose scan status and blocks only.
 */

/**
 * Get current Wordfence scan status and recent scan results.
 */
class GetWordfenceScanStatus extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'wordfence' );
	}

	public function get_name(): string {
		return 'get_wordfence_scan_status';
	}

	public function get_description(): string {
		return 'Get the current Wordfence scan status, last scan time, and summary of issues found (threats, vulnerabilities, file changes).';
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
			'properties' => [],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		global $wpdb;

		// Get config values from wfConfig table.
		$config_table = $wpdb->prefix . 'wfConfig';
		$issues_table = $wpdb->prefix . 'wfIssues';

		$get_config = function ( $key, $default = '' ) use ( $wpdb, $config_table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$value = $wpdb->get_var(
				$wpdb->prepare( "SELECT val FROM `{$config_table}` WHERE name = %s LIMIT 1", $key )
			);
			return $value !== null ? $value : $default;
		};

		// Check if tables exist.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $config_table ) ) ) {
			// Fallback to wfConfig::get if available.
			if ( class_exists( 'wfConfig' ) && method_exists( 'wfConfig', 'get' ) ) {
				$last_scan      = \wfConfig::get( 'lastScanCompleted', 0 );
				$scan_running   = \wfConfig::get( 'scanRunning', 0 );
				$issues_count   = 0;
			} else {
				return [ 'error' => 'Wordfence database tables not found. Ensure Wordfence is fully installed and configured.' ];
			}
		} else {
			$last_scan    = $get_config( 'lastScanCompleted', 0 );
			$scan_running = $get_config( 'scanRunning', 0 );

			// Count issues by type.
			$issues_count = 0;
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $issues_table ) ) ) {
				$issues_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$issues_table}` WHERE status = 'new'" );
			}
		}

		return [
			'scan_running'     => (bool) $scan_running,
			'last_scan'        => $last_scan ? gmdate( 'Y-m-d H:i:s', (int) $last_scan ) : null,
			'last_scan_age'    => $last_scan ? human_time_diff( (int) $last_scan ) . ' ago' : 'Never scanned',
			'open_issues'      => $issues_count,
			'firewall_mode'    => class_exists( 'wfConfig' ) && method_exists( 'wfConfig', 'get' ) ? \wfConfig::get( 'firewallMode', 'unknown' ) : 'unknown',
		];
	}
}

/**
 * List IPs blocked by Wordfence.
 */
class ListWordfenceBlockedIPs extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'wordfence' );
	}

	public function get_name(): string {
		return 'list_wordfence_blocked_ips';
	}

	public function get_description(): string {
		return 'List IP addresses currently blocked by Wordfence. Returns IP, reason for blocking, block time, and expiration.';
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
					'description' => 'Number of blocked IPs to return (max 100).',
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
		global $wpdb;

		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );
		$offset   = ( $page - 1 ) * $per_page;

		// Try wfBlock class first (Wordfence 7.x+).
		if ( class_exists( 'wfBlock' ) && method_exists( 'wfBlock', 'getBlocks' ) ) {
			$blocks = \wfBlock::getBlocks( $per_page, $offset );
			$total  = method_exists( 'wfBlock', 'getBlockCount' ) ? \wfBlock::getBlockCount() : count( $blocks );

			$result = [];
			foreach ( $blocks as $block ) {
				$data     = is_object( $block ) ? (array) $block : $block;
				$result[] = [
					'ip'         => $data['IP'] ?? $data['ip'] ?? '',
					'reason'     => $data['reason'] ?? $data['blockType'] ?? '',
					'created'    => isset( $data['blockedTime'] ) ? gmdate( 'Y-m-d H:i:s', (int) $data['blockedTime'] ) : '',
					'expires'    => isset( $data['expiration'] ) && $data['expiration'] ? gmdate( 'Y-m-d H:i:s', (int) $data['expiration'] ) : 'permanent',
				];
			}

			return [
				'blocked_ips' => $result,
				'total'       => (int) $total,
				'total_pages' => (int) ceil( $total / $per_page ),
				'page'        => $page,
				'per_page'    => $per_page,
			];
		}

		// Fallback: query wfBlockedIPLog table.
		$table = $wpdb->prefix . 'wfBlockedIPLog';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
			return [ 'error' => 'Wordfence blocked IP table not found.' ];
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				"SELECT IP, blockedTime, reason, blockCount FROM `{$table}` ORDER BY blockedTime DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

		$result = [];
		foreach ( $results ?: [] as $row ) {
			$result[] = [
				'ip'          => $row['IP'],
				'reason'      => $row['reason'],
				'created'     => gmdate( 'Y-m-d H:i:s', (int) $row['blockedTime'] ),
				'block_count' => (int) $row['blockCount'],
			];
		}

		return [
			'blocked_ips' => $result,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Trigger a Wordfence security scan.
 */
class RunWordfenceScan extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'wordfence' );
	}

	public function get_name(): string {
		return 'run_wordfence_scan';
	}

	public function get_description(): string {
		return 'Trigger a Wordfence security scan. The scan runs in the background via WP-Cron. Use get_wordfence_scan_status to check progress after triggering.';
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
			'properties' => [],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		// Check if scan is already running.
		$scan_running = false;
		if ( class_exists( 'wfConfig' ) && method_exists( 'wfConfig', 'get' ) ) {
			$scan_running = (bool) \wfConfig::get( 'scanRunning', 0 );
		}

		if ( $scan_running ) {
			return [
				'message' => 'A Wordfence scan is already running. Use get_wordfence_scan_status to check progress.',
				'started' => false,
			];
		}

		// Schedule scan via WP-Cron (Wordfence's standard approach).
		if ( class_exists( 'wordfence' ) && method_exists( 'wordfence', 'scheduleScan' ) ) {
			\wordfence::scheduleScan();
		} else {
			wp_schedule_single_event( time(), 'wordfence_start_scheduled_scan' );
			spawn_cron();
		}

		return [
			'message' => 'Wordfence scan has been scheduled and will start shortly. Use get_wordfence_scan_status to check progress.',
			'started' => true,
		];
	}
}
