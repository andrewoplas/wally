<?php
namespace Wally\Tools;

/**
 * Site tools for reading and updating WordPress site information and options.
 *
 * Tools: get_site_info, get_site_health, get_option, update_option.
 * Category: "site" — restricted to administrators and editors (read) per permission matrix.
 */

/**
 * Get general site information.
 */
class GetSiteInfo extends ToolInterface {

	public function get_name(): string {
		return 'get_site_info';
	}

	public function get_description(): string {
		return 'Get WordPress site information: WP version, PHP version, active theme, server, permalink structure.';
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
		return 'read';
	}

	public function execute( array $input ): array {
		global $wp_version;

		$theme = wp_get_theme();

		return [
			'wp_version'          => $wp_version,
			'php_version'         => phpversion(),
			'server_software'     => sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ?? 'unknown' ),
			'site_url'            => get_site_url(),
			'home_url'            => get_home_url(),
			'site_title'          => get_bloginfo( 'name' ),
			'site_tagline'        => get_bloginfo( 'description' ),
			'admin_email'         => get_bloginfo( 'admin_email' ),
			'language'            => get_bloginfo( 'language' ),
			'timezone'            => wp_timezone_string(),
			'permalink_structure' => get_option( 'permalink_structure' ) ?: 'Plain',
			'is_multisite'        => is_multisite(),
			'active_theme'        => [
				'name'           => $theme->get( 'Name' ),
				'version'        => $theme->get( 'Version' ),
				'is_child_theme' => is_child_theme(),
				'parent_theme'   => is_child_theme() ? $theme->parent()->get( 'Name' ) : null,
			],
			'active_plugins'      => count( get_option( 'active_plugins', [] ) ),
			'post_count'          => (int) wp_count_posts( 'post' )->publish,
			'page_count'          => (int) wp_count_posts( 'page' )->publish,
			'user_count'          => (int) count_users()['total_users'],
			'debug_mode'          => defined( 'WP_DEBUG' ) && WP_DEBUG,
		];
	}
}

/**
 * Run WordPress Site Health checks and return a status summary.
 */
class GetSiteHealth extends ToolInterface {

	public function get_name(): string {
		return 'get_site_health';
	}

	public function get_description(): string {
		return 'Run WordPress Site Health checks and return a summary of passed, recommended, and critical issues.';
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
		if ( ! class_exists( 'WP_Site_Health' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
		}

		$health = WP_Site_Health::get_instance();
		$tests  = WP_Site_Health::get_tests();

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
			$results[ $status ][] = [
				'label'       => $result['label'] ?? '',
				'description' => wp_strip_all_tags( $result['description'] ?? '' ),
			];
		}

		return [
			'good'        => $results['good'],
			'recommended' => $results['recommended'],
			'critical'    => $results['critical'],
			'summary'     => sprintf(
				'%d passed, %d recommended, %d critical.',
				count( $results['good'] ),
				count( $results['recommended'] ),
				count( $results['critical'] )
			),
		];
	}
}

/**
 * Read a WordPress option value.
 */
class GetOption extends ToolInterface {

	/** Options that should never be exposed (security-sensitive). */
	private const BLOCKED_OPTIONS = [
		'auth_key',
		'secure_auth_key',
		'logged_in_key',
		'nonce_key',
		'auth_salt',
		'secure_auth_salt',
		'logged_in_salt',
		'nonce_salt',
		'db_user',
		'db_password',
		'db_host',
	];

	/** Option prefixes that are entirely blocked from AI access. */
	private const BLOCKED_PREFIXES = [
		'wally_',
	];

	public function get_name(): string {
		return 'get_option';
	}

	public function get_description(): string {
		return 'Read a WordPress option value by key. Security-sensitive options (salts, keys, API keys) are blocked.';
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
				'option_name' => [
					'type'        => 'string',
					'description' => 'The option key to retrieve (e.g. blogname, permalink_structure).',
				],
			],
			'required'   => [ 'option_name' ],
		];
	}

	public function get_required_capability(): string {
		return 'read';
	}

	public function execute( array $input ): array {
		$option_name = sanitize_key( $input['option_name'] );

		if ( self::is_blocked( $option_name ) ) {
			return [ 'error' => "Option \"{$option_name}\" is blocked for security reasons." ];
		}

		$value = get_option( $option_name );

		if ( false === $value ) {
			return [
				'option_name' => $option_name,
				'exists'      => false,
				'value'       => null,
			];
		}

		return [
			'option_name' => $option_name,
			'exists'      => true,
			'value'       => $value,
		];
	}

	private static function is_blocked( string $option_name ): bool {
		if ( in_array( $option_name, self::BLOCKED_OPTIONS, true ) ) {
			return true;
		}
		foreach ( self::BLOCKED_PREFIXES as $prefix ) {
			if ( str_starts_with( $option_name, $prefix ) ) {
				return true;
			}
		}
		return false;
	}
}

/**
 * Update a WordPress option. Requires confirmation.
 */
class UpdateOption extends ToolInterface {

	/** Options that must never be modified via the assistant. */
	private const BLOCKED_OPTIONS = [
		'auth_key',
		'secure_auth_key',
		'logged_in_key',
		'nonce_key',
		'auth_salt',
		'secure_auth_salt',
		'logged_in_salt',
		'nonce_salt',
		'siteurl',
		'home',
		'active_plugins',
		'db_user',
		'db_password',
		'db_host',
		'users_can_register',
		'default_role',
	];

	/** Option prefixes that are entirely blocked from AI modification. */
	private const BLOCKED_PREFIXES = [
		'wally_',
	];

	public function get_name(): string {
		return 'update_option';
	}

	public function get_description(): string {
		return 'Update a WordPress option value. Destructive action that requires confirmation. Some options are blocked for safety.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'site';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'option_name' => [
					'type'        => 'string',
					'description' => 'The option key to update.',
				],
				'value'       => [
					'type'        => 'string',
					'description' => 'The new value for the option.',
				],
			],
			'required'   => [ 'option_name', 'value' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$option_name = sanitize_key( $input['option_name'] );

		if ( self::is_blocked( $option_name ) ) {
			return [ 'error' => "Option \"{$option_name}\" cannot be modified via the assistant." ];
		}

		$old_value = get_option( $option_name );
		$new_value = $input['value'];

		$updated = update_option( $option_name, $new_value );

		if ( ! $updated ) {
			// update_option returns false if the value didn't change or on failure.
			$current = get_option( $option_name );
			if ( $current === $new_value ) {
				return [
					'option_name' => $option_name,
					'message'     => 'Option value is already set to the requested value.',
					'value'       => $current,
				];
			}
			return [ 'error' => "Failed to update option \"{$option_name}\"." ];
		}

		return [
			'option_name' => $option_name,
			'old_value'   => $old_value,
			'new_value'   => $new_value,
			'message'     => "Option \"{$option_name}\" updated successfully.",
		];
	}

	private static function is_blocked( string $option_name ): bool {
		if ( in_array( $option_name, self::BLOCKED_OPTIONS, true ) ) {
			return true;
		}
		foreach ( self::BLOCKED_PREFIXES as $prefix ) {
			if ( str_starts_with( $option_name, $prefix ) ) {
				return true;
			}
		}
		return false;
	}
}
