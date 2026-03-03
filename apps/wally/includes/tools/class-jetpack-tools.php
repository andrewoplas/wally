<?php
namespace Wally\Tools;

/**
 * Jetpack plugin management tools.
 *
 * Tools: list_jetpack_modules, activate_jetpack_module, deactivate_jetpack_module, get_jetpack_stats.
 * All tools require Jetpack to be active (class_exists('Jetpack')).
 */

/**
 * List all available Jetpack modules with their active status.
 */
class ListJetpackModules extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'Jetpack' );
	}

	public function get_name(): string {
		return 'list_jetpack_modules';
	}

	public function get_description(): string {
		return 'List all available Jetpack modules with their slug, name, description, and whether they are currently active.';
	}

	public function get_category(): string {
		return 'plugins';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'active_only' => [
					'type'        => 'boolean',
					'description' => 'If true, return only active modules. Default: false (returns all modules).',
					'default'     => false,
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$active_only = ! empty( $input['active_only'] );

		$available_modules = \Jetpack::get_available_modules();
		$active_modules    = \Jetpack::get_active_modules();

		$result = [];
		foreach ( $available_modules as $slug ) {
			$is_active = in_array( $slug, $active_modules, true );

			if ( $active_only && ! $is_active ) {
				continue;
			}

			$module_info = \Jetpack::get_module( $slug );

			$result[] = [
				'slug'        => $slug,
				'name'        => $module_info['name'] ?? $slug,
				'description' => $module_info['description'] ?? '',
				'active'      => $is_active,
				'module_tags' => $module_info['module_tags'] ?? [],
			];
		}

		// Sort by active status, then alphabetically.
		usort(
			$result,
			function ( $a, $b ) {
				if ( $a['active'] !== $b['active'] ) {
					return $a['active'] ? -1 : 1;
				}
				return strcmp( $a['slug'], $b['slug'] );
			}
		);

		return [
			'modules'        => $result,
			'total'          => count( $result ),
			'active_count'   => count( $active_modules ),
			'available_count' => count( $available_modules ),
		];
	}
}

/**
 * Activate a Jetpack module.
 */
class ActivateJetpackModule extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'Jetpack' );
	}

	public function get_name(): string {
		return 'activate_jetpack_module';
	}

	public function get_description(): string {
		return 'Activate a Jetpack module by its slug. Use list_jetpack_modules to find available module slugs.';
	}

	public function get_category(): string {
		return 'plugins';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'module_slug' => [
					'type'        => 'string',
					'description' => 'The Jetpack module slug to activate (e.g., "stats", "protect", "photon", "publicize").',
				],
			],
			'required'   => [ 'module_slug' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$slug = sanitize_key( $input['module_slug'] );

		$available = \Jetpack::get_available_modules();
		if ( ! in_array( $slug, $available, true ) ) {
			return [ 'error' => "Jetpack module not found: {$slug}. Use list_jetpack_modules to see available modules." ];
		}

		if ( \Jetpack::is_module_active( $slug ) ) {
			return [
				'module_slug' => $slug,
				'message'     => "Jetpack module \"{$slug}\" is already active.",
				'active'      => true,
			];
		}

		$result = \Jetpack::activate_module( $slug, false, false );

		return [
			'module_slug' => $slug,
			'active'      => \Jetpack::is_module_active( $slug ),
			'message'     => "Jetpack module \"{$slug}\" activated successfully.",
		];
	}
}

/**
 * Deactivate a Jetpack module.
 */
class DeactivateJetpackModule extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'Jetpack' );
	}

	public function get_name(): string {
		return 'deactivate_jetpack_module';
	}

	public function get_description(): string {
		return 'Deactivate a Jetpack module by its slug. Use list_jetpack_modules to find active module slugs.';
	}

	public function get_category(): string {
		return 'plugins';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'module_slug' => [
					'type'        => 'string',
					'description' => 'The Jetpack module slug to deactivate (e.g., "stats", "protect", "photon", "publicize").',
				],
			],
			'required'   => [ 'module_slug' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$slug = sanitize_key( $input['module_slug'] );

		$available = \Jetpack::get_available_modules();
		if ( ! in_array( $slug, $available, true ) ) {
			return [ 'error' => "Jetpack module not found: {$slug}. Use list_jetpack_modules to see available modules." ];
		}

		if ( ! \Jetpack::is_module_active( $slug ) ) {
			return [
				'module_slug' => $slug,
				'message'     => "Jetpack module \"{$slug}\" is already inactive.",
				'active'      => false,
			];
		}

		\Jetpack::deactivate_module( $slug );

		return [
			'module_slug' => $slug,
			'active'      => \Jetpack::is_module_active( $slug ),
			'message'     => "Jetpack module \"{$slug}\" deactivated successfully.",
		];
	}
}

/**
 * Get Jetpack site stats (requires Stats module to be active).
 */
class GetJetpackStats extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'Jetpack' );
	}

	public function get_name(): string {
		return 'get_jetpack_stats';
	}

	public function get_description(): string {
		return 'Get Jetpack site statistics including page views. Requires the Jetpack Stats module to be active and a WordPress.com connection.';
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
				'days' => [
					'type'        => 'integer',
					'description' => 'Number of days of stats to retrieve (1-30). Default: 7.',
					'default'     => 7,
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		if ( ! \Jetpack::is_module_active( 'stats' ) ) {
			return [ 'error' => 'The Jetpack Stats module is not active. Activate it via activate_jetpack_module to use this tool.' ];
		}

		if ( ! function_exists( 'stats_get_csv' ) ) {
			// Try to load the stats functions.
			$stats_file = WP_PLUGIN_DIR . '/jetpack/modules/stats.php';
			if ( file_exists( $stats_file ) ) {
				require_once $stats_file;
			}
		}

		if ( ! function_exists( 'stats_get_csv' ) ) {
			return [ 'error' => 'Jetpack stats functions are not available. Ensure the Stats module is active.' ];
		}

		$days = min( max( (int) ( $input['days'] ?? 7 ), 1 ), 30 );

		$views = stats_get_csv(
			'views',
			[
				'days'   => $days,
				'end'    => gmdate( 'Y-m-d' ),
				'format' => 'array',
			]
		);

		$total_views = 0;
		$daily_views = [];
		if ( is_array( $views ) ) {
			foreach ( $views as $day ) {
				$total_views        += (int) ( $day['views'] ?? 0 );
				$daily_views[] = [
					'date'  => $day['period'] ?? '',
					'views' => (int) ( $day['views'] ?? 0 ),
				];
			}
		}

		return [
			'days'        => $days,
			'total_views' => $total_views,
			'daily_views' => $daily_views,
			'note'        => 'Stats are fetched from WordPress.com and may be delayed by a few minutes.',
		];
	}
}
