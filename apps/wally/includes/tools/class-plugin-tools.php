<?php
namespace Wally\Tools;

/**
 * Plugin management tools for WordPress.
 *
 * Tools: list_plugins, install_plugin, activate_plugin, deactivate_plugin,
 *        update_plugin, delete_plugin.
 * Category: "plugins" — restricted to administrators per permission matrix.
 */

/**
 * List all installed plugins with status and update availability.
 */
class ListPlugins extends ToolInterface {

	public function get_name(): string {
		return 'list_plugins';
	}

	public function get_description(): string {
		return 'List all installed WordPress plugins with name, version, status (active/inactive), and update availability.';
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
				'status' => [
					'type'        => 'string',
					'description' => 'Filter by plugin status.',
					'enum'        => [ 'active', 'inactive', 'all' ],
					'default'     => 'all',
				],
				'search' => [
					'type'        => 'string',
					'description' => 'Search plugins by name.',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'activate_plugins';
	}

	public function execute( array $input ): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins    = get_plugins();
		$active_plugins = get_option( 'active_plugins', [] );
		$updates        = get_site_transient( 'update_plugins' );
		$status_filter  = $input['status'] ?? 'all';
		$search         = ! empty( $input['search'] ) ? strtolower( sanitize_text_field( $input['search'] ) ) : '';

		$plugins = [];
		foreach ( $all_plugins as $file => $data ) {
			$is_active = in_array( $file, $active_plugins, true );

			// Status filter.
			if ( 'active' === $status_filter && ! $is_active ) {
				continue;
			}
			if ( 'inactive' === $status_filter && $is_active ) {
				continue;
			}

			// Search filter.
			if ( $search && false === strpos( strtolower( $data['Name'] ), $search ) ) {
				continue;
			}

			$has_update = isset( $updates->response[ $file ] );

			$plugins[] = [
				'file'             => $file,
				'name'             => $data['Name'],
				'version'          => $data['Version'],
				'author'           => wp_strip_all_tags( $data['Author'] ),
				'active'           => $is_active,
				'update_available' => $has_update,
				'new_version'      => $has_update ? $updates->response[ $file ]->new_version : null,
			];
		}

		return [
			'plugins' => $plugins,
			'total'   => count( $plugins ),
		];
	}
}

/**
 * Install a plugin from WordPress.org by slug. Requires confirmation.
 */
class InstallPlugin extends ToolInterface {

	public function get_name(): string {
		return 'install_plugin';
	}

	public function get_description(): string {
		return 'Install a WordPress plugin from the WordPress.org repository by slug. Requires confirmation.';
	}

	public function get_category(): string {
		return 'plugins';
	}

	public function get_action(): string {
		return 'plugins';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'slug' => [
					'type'        => 'string',
					'description' => 'WordPress.org plugin slug (e.g. "contact-form-7").',
				],
			],
			'required'   => [ 'slug' ],
		];
	}

	public function get_required_capability(): string {
		return 'install_plugins';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$slug = sanitize_title( $input['slug'] );

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		// Fetch plugin info from WP.org API.
		$api = plugins_api( 'plugin_information', [
			'slug'   => $slug,
			'fields' => [ 'short_description' => true, 'sections' => false ],
		]);

		if ( is_wp_error( $api ) ) {
			return [ 'error' => "Plugin not found on WordPress.org: {$slug}" ];
		}

		// Use a silent upgrader skin to suppress output.
		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}

		if ( ! $result ) {
			return [ 'error' => "Failed to install plugin: {$slug}" ];
		}

		return [
			'slug'    => $slug,
			'name'    => $api->name,
			'version' => $api->version,
			'message' => "Plugin \"{$api->name}\" installed successfully. Use activate_plugin to activate it.",
		];
	}
}

/**
 * Activate an installed plugin.
 */
class ActivatePlugin extends ToolInterface {

	public function get_name(): string {
		return 'activate_plugin';
	}

	public function get_description(): string {
		return 'Activate an installed WordPress plugin by its file path (e.g. "contact-form-7/wp-contact-form-7.php").';
	}

	public function get_category(): string {
		return 'plugins';
	}

	public function get_action(): string {
		return 'plugins';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'plugin' => [
					'type'        => 'string',
					'description' => 'Plugin file path relative to plugins directory (e.g. "akismet/akismet.php").',
				],
			],
			'required'   => [ 'plugin' ],
		];
	}

	public function get_required_capability(): string {
		return 'activate_plugins';
	}

	public function execute( array $input ): array {
		$plugin_file = sanitize_text_field( $input['plugin'] );

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Verify the plugin exists.
		$all_plugins = get_plugins();
		if ( ! isset( $all_plugins[ $plugin_file ] ) ) {
			return [ 'error' => "Plugin not found: {$plugin_file}" ];
		}

		if ( is_plugin_active( $plugin_file ) ) {
			return [
				'plugin'  => $plugin_file,
				'name'    => $all_plugins[ $plugin_file ]['Name'],
				'message' => 'Plugin is already active.',
			];
		}

		$result = activate_plugin( $plugin_file );

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}

		return [
			'plugin'  => $plugin_file,
			'name'    => $all_plugins[ $plugin_file ]['Name'],
			'message' => "Plugin \"{$all_plugins[ $plugin_file ]['Name']}\" activated successfully.",
		];
	}
}

/**
 * Deactivate an active plugin.
 */
class DeactivatePlugin extends ToolInterface {

	public function get_name(): string {
		return 'deactivate_plugin';
	}

	public function get_description(): string {
		return 'Deactivate a currently active WordPress plugin.';
	}

	public function get_category(): string {
		return 'plugins';
	}

	public function get_action(): string {
		return 'plugins';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'plugin' => [
					'type'        => 'string',
					'description' => 'Plugin file path relative to plugins directory (e.g. "akismet/akismet.php").',
				],
			],
			'required'   => [ 'plugin' ],
		];
	}

	public function get_required_capability(): string {
		return 'activate_plugins';
	}

	public function execute( array $input ): array {
		$plugin_file = sanitize_text_field( $input['plugin'] );

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		if ( ! isset( $all_plugins[ $plugin_file ] ) ) {
			return [ 'error' => "Plugin not found: {$plugin_file}" ];
		}

		if ( ! is_plugin_active( $plugin_file ) ) {
			return [
				'plugin'  => $plugin_file,
				'name'    => $all_plugins[ $plugin_file ]['Name'],
				'message' => 'Plugin is already inactive.',
			];
		}

		deactivate_plugins( $plugin_file );

		return [
			'plugin'  => $plugin_file,
			'name'    => $all_plugins[ $plugin_file ]['Name'],
			'message' => "Plugin \"{$all_plugins[ $plugin_file ]['Name']}\" deactivated successfully.",
		];
	}
}

/**
 * Update a plugin to its latest version. Requires confirmation.
 */
class UpdatePlugin extends ToolInterface {

	public function get_name(): string {
		return 'update_plugin';
	}

	public function get_description(): string {
		return 'Update an installed WordPress plugin to its latest version. Requires confirmation.';
	}

	public function get_category(): string {
		return 'plugins';
	}

	public function get_action(): string {
		return 'plugins';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'plugin' => [
					'type'        => 'string',
					'description' => 'Plugin file path relative to plugins directory (e.g. "akismet/akismet.php").',
				],
			],
			'required'   => [ 'plugin' ],
		];
	}

	public function get_required_capability(): string {
		return 'update_plugins';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$plugin_file = sanitize_text_field( $input['plugin'] );

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$all_plugins = get_plugins();
		if ( ! isset( $all_plugins[ $plugin_file ] ) ) {
			return [ 'error' => "Plugin not found: {$plugin_file}" ];
		}

		$old_version = $all_plugins[ $plugin_file ]['Version'];

		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		$result   = $upgrader->upgrade( $plugin_file );

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}

		if ( false === $result ) {
			return [ 'error' => "No update available for {$all_plugins[ $plugin_file ]['Name']}." ];
		}

		// Re-read plugin data to get new version.
		$updated_plugins = get_plugins();
		$new_version     = $updated_plugins[ $plugin_file ]['Version'] ?? 'unknown';

		return [
			'plugin'      => $plugin_file,
			'name'        => $all_plugins[ $plugin_file ]['Name'],
			'old_version' => $old_version,
			'new_version' => $new_version,
			'message'     => "Plugin \"{$all_plugins[ $plugin_file ]['Name']}\" updated from v{$old_version} to v{$new_version}.",
		];
	}
}

/**
 * Delete (remove) a plugin permanently. Requires confirmation.
 */
class DeletePlugin extends ToolInterface {

	public function get_name(): string {
		return 'delete_plugin';
	}

	public function get_description(): string {
		return 'Permanently delete a WordPress plugin. The plugin must be deactivated first. Requires confirmation.';
	}

	public function get_category(): string {
		return 'plugins';
	}

	public function get_action(): string {
		return 'plugins';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'plugin' => [
					'type'        => 'string',
					'description' => 'Plugin file path relative to plugins directory (e.g. "akismet/akismet.php").',
				],
			],
			'required'   => [ 'plugin' ],
		];
	}

	public function get_required_capability(): string {
		return 'delete_plugins';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$plugin_file = sanitize_text_field( $input['plugin'] );

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$all_plugins = get_plugins();
		if ( ! isset( $all_plugins[ $plugin_file ] ) ) {
			return [ 'error' => "Plugin not found: {$plugin_file}" ];
		}

		// Safety: prevent deleting self.
		if ( str_starts_with( $plugin_file, 'wally/' ) ) {
			return [ 'error' => 'Cannot delete the Wally plugin via the assistant.' ];
		}

		// Must be deactivated before deletion.
		if ( is_plugin_active( $plugin_file ) ) {
			return [ 'error' => "Plugin \"{$all_plugins[ $plugin_file ]['Name']}\" must be deactivated before deletion." ];
		}

		$plugin_name = $all_plugins[ $plugin_file ]['Name'];
		$result      = delete_plugins( [ $plugin_file ] );

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}

		return [
			'plugin'  => $plugin_file,
			'name'    => $plugin_name,
			'message' => "Plugin \"{$plugin_name}\" deleted permanently.",
		];
	}
}
