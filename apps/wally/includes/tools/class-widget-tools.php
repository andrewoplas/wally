<?php
namespace Wally\Tools;

/**
 * Widget and sidebar management tools for classic WordPress themes.
 *
 * Tools: list_widget_areas, list_widgets, add_widget, remove_widget.
 * Only registered for classic (non-block) themes — block themes use template parts.
 * Category: "site" — restricted to editors and above (edit_theme_options).
 */

/**
 * List all registered widget areas (sidebars) for the active theme.
 */
class ListWidgetAreas extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'wp_is_block_theme' ) ? ! wp_is_block_theme() : true;
	}

	public function get_name(): string {
		return 'list_widget_areas';
	}

	public function get_description(): string {
		return 'List all registered widget areas (sidebars) for the active classic theme. Returns each area\'s ID, name, description, and how many widgets are currently assigned to it. Use this before add_widget or remove_widget to know valid sidebar IDs.';
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
		return 'edit_theme_options';
	}

	public function execute( array $params ): array {
		global $wp_registered_sidebars;

		$sidebars_widgets = wp_get_sidebars_widgets();
		$areas            = [];

		foreach ( $wp_registered_sidebars as $sidebar ) {
			$widget_ids      = $sidebars_widgets[ $sidebar['id'] ] ?? [];
			$widget_ids      = array_filter( (array) $widget_ids ); // strip empty values
			$areas[] = [
				'id'             => $sidebar['id'],
				'name'           => $sidebar['name'],
				'description'    => $sidebar['description'] ?? '',
				'widget_count'   => count( $widget_ids ),
			];
		}

		return [
			'success' => true,
			'data'    => [
				'areas' => $areas,
				'total' => count( $areas ),
			],
		];
	}
}

/**
 * List all active widgets in a specific widget area.
 */
class ListWidgets extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'wp_is_block_theme' ) ? ! wp_is_block_theme() : true;
	}

	public function get_name(): string {
		return 'list_widgets';
	}

	public function get_description(): string {
		return 'List all active widgets assigned to a specific widget area (sidebar). Returns widget IDs, types, and titles. Use list_widget_areas first to get valid sidebar IDs. The widget_id returned here can be used with remove_widget.';
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
				'sidebar_id' => [
					'type'        => 'string',
					'description' => 'The widget area ID to list widgets for (e.g., "sidebar-1", "footer-1"). Use list_widget_areas to get valid IDs.',
				],
			],
			'required'   => [ 'sidebar_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function execute( array $params ): array {
		global $wp_registered_sidebars, $wp_registered_widgets;

		$sidebar_id = sanitize_key( $params['sidebar_id'] );

		if ( ! isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
			return [ 'success' => false, 'error' => "Widget area not found: {$sidebar_id}" ];
		}

		$sidebars_widgets = wp_get_sidebars_widgets();
		$widget_ids       = array_filter( (array) ( $sidebars_widgets[ $sidebar_id ] ?? [] ) );
		$widgets          = [];

		foreach ( $widget_ids as $widget_id ) {
			$id_base  = preg_replace( '/-\d+$/', '', $widget_id );
			$number   = (int) str_replace( $id_base . '-', '', $widget_id );
			$settings = get_option( "widget_{$id_base}", [] );
			$instance = is_array( $settings ) && isset( $settings[ $number ] ) ? $settings[ $number ] : [];
			$title    = $instance['title'] ?? '';

			// Fall back to registered widget name if no title set.
			$display_name = $title ?: ( $wp_registered_widgets[ $widget_id ]['name'] ?? $id_base );

			$widgets[] = [
				'widget_id'    => $widget_id,
				'id_base'      => $id_base,
				'instance_num' => $number,
				'title'        => $title,
				'display_name' => $display_name,
			];
		}

		return [
			'success' => true,
			'data'    => [
				'sidebar_id'   => $sidebar_id,
				'sidebar_name' => $wp_registered_sidebars[ $sidebar_id ]['name'],
				'widgets'      => $widgets,
				'total'        => count( $widgets ),
			],
		];
	}
}

/**
 * Add a widget to a widget area.
 */
class AddWidget extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'wp_is_block_theme' ) ? ! wp_is_block_theme() : true;
	}

	public function get_name(): string {
		return 'add_widget';
	}

	public function get_description(): string {
		return 'Add a widget to a widget area (sidebar). Specify the sidebar ID and the widget type (id_base). Common widget types: "recent-posts", "recent-comments", "archives", "categories", "text", "search", "meta", "pages", "tag_cloud", "calendar", "rss". An optional title can be set. Returns the new widget_id assigned.';
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
				'sidebar_id'  => [
					'type'        => 'string',
					'description' => 'The widget area ID to add the widget to. Use list_widget_areas to get valid IDs.',
				],
				'widget_type' => [
					'type'        => 'string',
					'description' => 'The widget type identifier (id_base), e.g., "recent-posts", "text", "search", "categories", "archives", "recent-comments", "tag_cloud".',
				],
				'title'       => [
					'type'        => 'string',
					'description' => 'Optional display title for the widget.',
				],
			],
			'required'   => [ 'sidebar_id', 'widget_type' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function execute( array $params ): array {
		global $wp_registered_sidebars, $wp_registered_widgets;

		$sidebar_id = sanitize_key( $params['sidebar_id'] );
		$id_base    = sanitize_key( $params['widget_type'] );

		if ( ! isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
			return [ 'success' => false, 'error' => "Widget area not found: {$sidebar_id}" ];
		}

		// Verify the widget type exists.
		$type_found = false;
		foreach ( $wp_registered_widgets as $registered ) {
			if ( isset( $registered['id_base'] ) && $registered['id_base'] === $id_base ) {
				$type_found = true;
				break;
			}
		}
		if ( ! $type_found ) {
			return [ 'success' => false, 'error' => "Widget type not found: {$id_base}. Check available widgets using list_widget_areas." ];
		}

		// Find the next available instance number.
		$existing  = get_option( "widget_{$id_base}", [] );
		if ( ! is_array( $existing ) ) {
			$existing = [];
		}
		$nums       = array_filter( array_keys( $existing ), 'is_int' );
		$next_num   = empty( $nums ) ? 1 : ( max( $nums ) + 1 );

		// Create the new instance.
		$new_instance = [];
		if ( ! empty( $params['title'] ) ) {
			$new_instance['title'] = sanitize_text_field( $params['title'] );
		}
		$existing[ $next_num ] = $new_instance;
		update_option( "widget_{$id_base}", $existing );

		// Add to the sidebar.
		$widget_id        = "{$id_base}-{$next_num}";
		$sidebars_widgets = wp_get_sidebars_widgets();
		if ( ! is_array( $sidebars_widgets[ $sidebar_id ] ?? null ) ) {
			$sidebars_widgets[ $sidebar_id ] = [];
		}
		$sidebars_widgets[ $sidebar_id ][] = $widget_id;
		wp_set_sidebars_widgets( $sidebars_widgets );

		return [
			'success' => true,
			'data'    => [
				'widget_id'  => $widget_id,
				'sidebar_id' => $sidebar_id,
				'id_base'    => $id_base,
				'message'    => "Widget \"{$widget_id}\" added to \"{$sidebar_id}\".",
			],
		];
	}
}

/**
 * Remove a widget from a widget area. Requires confirmation.
 */
class RemoveWidget extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'wp_is_block_theme' ) ? ! wp_is_block_theme() : true;
	}

	public function get_name(): string {
		return 'remove_widget';
	}

	public function get_description(): string {
		return 'Remove a widget from a widget area (sidebar). Use list_widgets to get the widget_id (e.g., "recent-posts-2") and sidebar_id. This is a destructive action that requires confirmation.';
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
				'sidebar_id' => [
					'type'        => 'string',
					'description' => 'The widget area ID containing the widget.',
				],
				'widget_id'  => [
					'type'        => 'string',
					'description' => 'The full widget ID to remove (e.g., "recent-posts-2", "text-3"). Use list_widgets to find it.',
				],
			],
			'required'   => [ 'sidebar_id', 'widget_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $params ): array {
		global $wp_registered_sidebars;

		$sidebar_id = sanitize_key( $params['sidebar_id'] );
		$widget_id  = sanitize_text_field( $params['widget_id'] );

		if ( ! isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
			return [ 'success' => false, 'error' => "Widget area not found: {$sidebar_id}" ];
		}

		$sidebars_widgets = wp_get_sidebars_widgets();
		$current          = array_filter( (array) ( $sidebars_widgets[ $sidebar_id ] ?? [] ) );

		if ( ! in_array( $widget_id, $current, true ) ) {
			return [ 'success' => false, 'error' => "Widget \"{$widget_id}\" not found in sidebar \"{$sidebar_id}\"." ];
		}

		// Remove from sidebar.
		$sidebars_widgets[ $sidebar_id ] = array_values( array_diff( $current, [ $widget_id ] ) );
		wp_set_sidebars_widgets( $sidebars_widgets );

		return [
			'success' => true,
			'data'    => [
				'widget_id'  => $widget_id,
				'sidebar_id' => $sidebar_id,
				'message'    => "Widget \"{$widget_id}\" removed from \"{$sidebar_id}\".",
			],
		];
	}
}
