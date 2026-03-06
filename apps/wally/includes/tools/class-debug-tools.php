<?php
namespace Wally\Tools;

/**
 * Debug and diagnostics tools for investigating errors, performance issues, and site health.
 *
 * Tools: get_error_log, get_site_health_tests.
 * Category: "site" — restricted to administrators (manage_options).
 */

/**
 * Read the last N lines from the WordPress debug log file.
 */
class GetErrorLog extends ToolInterface {

	public function get_name(): string {
		return 'get_error_log';
	}

	public function get_description(): string {
		return 'Read the last N lines from the WordPress debug.log file (WP_CONTENT_DIR/debug.log). Use this when the user reports errors, unexpected behavior, or wants to investigate what went wrong. Returns file size, total line count, and the requested lines. Returns a message if debug logging is not enabled or the file does not exist.';
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
				'lines' => [
					'type'        => 'integer',
					'description' => 'Number of lines to read from the end of the log file. Defaults to 100. Maximum 500.',
				],
			],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $params ): array {
		$log_file = WP_CONTENT_DIR . '/debug.log';

		if ( ! file_exists( $log_file ) ) {
			return [
				'success' => true,
				'data'    => [
					'exists'  => false,
					'message' => 'No debug.log found. WP_DEBUG_LOG may not be enabled.',
				],
			];
		}

		$lines   = isset( $params['lines'] ) ? (int) $params['lines'] : 100;
		$lines   = max( 1, min( $lines, 500 ) );
		$content = file_get_contents( $log_file );

		if ( false === $content ) {
			return [
				'success' => false,
				'error'   => 'Could not read debug.log. Check file permissions.',
			];
		}

		$all_lines   = explode( "\n", trim( $content ) );
		$last_lines  = array_slice( $all_lines, -$lines );

		return [
			'success' => true,
			'data'    => [
				'exists'        => true,
				'file_size'     => filesize( $log_file ),
				'total_lines'   => count( $all_lines ),
				'lines_returned' => count( $last_lines ),
				'content'       => implode( "\n", $last_lines ),
			],
		];
	}
}

/**
 * Run WordPress Site Health direct tests and return categorized results.
 */
class GetSiteHealthTests extends ToolInterface {

	public function get_name(): string {
		return 'get_site_health_tests';
	}

	public function get_description(): string {
		return 'Run WordPress Site Health direct tests and return results categorized as good, recommended, or critical. Use this when the user asks why their site is slow, has security concerns, or wants a technical health overview. Async tests (which require a browser) are skipped — only synchronous direct tests are run.';
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
			'properties' => (object) [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $params ): array {
		if ( ! class_exists( 'WP_Site_Health' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
		}

		$health  = WP_Site_Health::get_instance();
		$tests   = WP_Site_Health::get_tests();
		$results = [
			'good'        => [],
			'recommended' => [],
			'critical'    => [],
		];

		foreach ( $tests['direct'] as $test ) {
			$callback = $test['test'];
			if ( is_string( $callback ) ) {
				$callback = [ $health, "get_test_{$callback}" ];
			}
			if ( ! is_callable( $callback ) ) {
				continue;
			}
			$result = call_user_func( $callback );
			$status = $result['status'] ?? 'good';
			if ( ! isset( $results[ $status ] ) ) {
				$status = 'good';
			}
			$results[ $status ][] = [
				'label'       => $result['label'] ?? '',
				'description' => wp_strip_all_tags( $result['description'] ?? '' ),
			];
		}

		return [
			'success' => true,
			'data'    => [
				'good'        => $results['good'],
				'recommended' => $results['recommended'],
				'critical'    => $results['critical'],
				'summary'     => sprintf(
					'%d passed, %d recommended, %d critical.',
					count( $results['good'] ),
					count( $results['recommended'] ),
					count( $results['critical'] )
				),
			],
		];
	}
}
