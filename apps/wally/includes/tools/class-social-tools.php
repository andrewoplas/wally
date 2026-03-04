<?php
namespace Wally\Tools;

/**
 * Social plugin tools for Smash Balloon Instagram Feed and AddToAny Share Buttons.
 *
 * Tools: get_instagram_feed_settings, list_social_share_counts.
 * Each tool has its own can_register() check — they activate independently.
 * Read-only tools — no confirmation needed.
 */

/**
 * Get Smash Balloon Instagram Feed settings and connected account status.
 */
class SmashBalloonGetInstagramSettings extends ToolInterface {

	public function get_name(): string {
		return 'get_instagram_feed_settings';
	}

	public function get_description(): string {
		return 'Get Smash Balloon Instagram Feed plugin settings and connected account status. Returns display configuration (photo count, columns, image size, sort order, cache settings) and a list of connected Instagram accounts (without exposing access tokens). Useful for diagnosing feed display issues or reviewing the current feed configuration.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return class_exists( 'SB_Instagram_Feed' );
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
		global $wpdb;

		// Read main settings option.
		$raw_settings = get_option( 'sb_instagram_settings', [] );
		$settings     = is_array( $raw_settings ) ? $raw_settings : [];

		// Extract display settings — omit access tokens and sensitive auth data.
		$display = [
			'num_photos'       => isset( $settings['sb_instagram_num'] ) ? (int) $settings['sb_instagram_num'] : null,
			'columns'          => isset( $settings['sb_instagram_cols'] ) ? (int) $settings['sb_instagram_cols'] : null,
			'width'            => $settings['sb_instagram_width'] ?? null,
			'height'           => $settings['sb_instagram_height'] ?? null,
			'image_resolution' => $settings['sb_instagram_image_res'] ?? null,
			'sort_order'       => $settings['sb_instagram_sort'] ?? null,
			'cache_time'       => isset( $settings['sb_instagram_cache_time'] ) ? (int) $settings['sb_instagram_cache_time'] : null,
			'cache_time_unit'  => $settings['sb_instagram_cache_time_unit'] ?? null,
			'backup_images'    => ! empty( $settings['sb_instagram_backup'] ),
		];

		// Check for stored API errors.
		$errors = get_option( 'sb_instagram_errors', [] );

		// Read connected accounts from the sbi_sources table (v6+).
		$accounts      = [];
		$sources_table = $wpdb->prefix . 'sbi_sources';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$sources_table}'" ) ) {
			$rows = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id, account_id, username, error, expires
				 FROM {$sources_table}
				 ORDER BY id ASC"
			);
			foreach ( $rows as $row ) {
				$accounts[] = [
					'id'         => (int) $row->id,
					'account_id' => $row->account_id,
					'username'   => $row->username,
					'has_error'  => ! empty( $row->error ),
					'error'      => ! empty( $row->error ) ? $row->error : null,
					'expires'    => $row->expires,
					// Token intentionally omitted — never expose access tokens.
				];
			}
		} else {
			// Older versions store connected account in the main settings option.
			if ( ! empty( $settings['sb_instagram_user_id'] ) ) {
				$accounts[] = [
					'account_id' => $settings['sb_instagram_user_id'],
					'username'   => $settings['sb_instagram_username'] ?? null,
					'has_error'  => false,
				];
			}
		}

		return [
			'success' => true,
			'data'    => [
				'connected_accounts' => $accounts,
				'account_count'      => count( $accounts ),
				'display_settings'   => $display,
				'has_api_errors'     => ! empty( $errors ),
				'api_errors'         => is_array( $errors ) ? $errors : [],
				'shortcode'          => '[instagram-feed]',
			],
		];
	}
}

/**
 * Get AddToAny Share Buttons configuration and share count display settings.
 */
class AddToAnyListShareCounts extends ToolInterface {

	public function get_name(): string {
		return 'list_social_share_counts';
	}

	public function get_description(): string {
		return 'Get AddToAny Share Buttons plugin configuration: which social services are enabled, where buttons are displayed (above/below posts, pages, excerpts), floating bar settings, icon size, and whether share counts are shown per service. Note: actual real-time share counts are fetched client-side from AddToAny\'s API and are not stored in WordPress.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return defined( 'A2A_SHARE_SAVE_VERSION' );
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
		$options = get_option( 'addtoany_options', [] );
		$options = is_array( $options ) ? $options : [];

		// Active sharing services.
		$active_services = $options['active_services'] ?? [];
		if ( is_string( $active_services ) ) {
			$active_services = array_filter( array_map( 'trim', explode( ',', $active_services ) ) );
		}

		// Button placement.
		$placement = [
			'position'            => $options['position'] ?? '',
			'display_in_posts'    => ! empty( $options['display_in_posts'] ),
			'display_in_pages'    => ! empty( $options['display_in_pages'] ),
			'display_in_excerpts' => ! empty( $options['display_in_excerpts'] ),
			'display_in_feed'     => ! empty( $options['display_in_feed'] ),
			'floating_vertical'   => $options['floating_vertical'] ?? '',
			'floating_horizontal' => $options['floating_horizontal'] ?? '',
		];

		// Share count settings per service.
		$services_with_counts = [];
		$special_services     = [ 'facebook', 'twitter', 'pinterest', 'linkedin', 'reddit' ];
		foreach ( $special_services as $service ) {
			$service_opts = $options[ 'special_' . $service . '_options' ] ?? [];
			if ( ! empty( $service_opts ) ) {
				$show_count = isset( $service_opts['show_count'] )
					? ( '1' === (string) $service_opts['show_count'] )
					: false;

				$services_with_counts[ $service ] = [
					'show_count' => $show_count,
				];
			}
		}

		// Global share counts toggle.
		$share_counts_enabled = ! empty( $options['share_counts'] );

		return [
			'success' => true,
			'data'    => [
				'plugin_version'       => defined( 'A2A_SHARE_SAVE_VERSION' ) ? A2A_SHARE_SAVE_VERSION : null,
				'active_services'      => array_values( $active_services ),
				'service_count'        => count( $active_services ),
				'share_counts_enabled' => $share_counts_enabled,
				'service_count_config' => $services_with_counts,
				'placement'            => $placement,
				'icon_size'            => isset( $options['icon_size'] ) ? (int) $options['icon_size'] : 32,
				'note'                 => 'Actual share counts are fetched client-side from AddToAny\'s API and are not stored in WordPress.',
			],
		];
	}
}
