<?php
namespace Wally\Tools;

/**
 * Navigation menu management tools for WordPress.
 *
 * Tools: list_menus, get_menu, create_menu, delete_menu, add_menu_item, update_menu_item, delete_menu_item.
 * Uses WordPress core nav menu functions; requires edit_theme_options capability.
 */

/**
 * List all WordPress navigation menus.
 */
class ListMenus extends ToolInterface {

	public function get_name(): string {
		return 'list_menus';
	}

	public function get_description(): string {
		return 'List all WordPress navigation menus. Returns menu ID, name, slug, item count, and assigned theme locations.';
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
		return 'edit_theme_options';
	}

	public function execute( array $input ): array {
		$menus     = wp_get_nav_menus();
		$locations = get_nav_menu_locations(); // theme_location => menu_id
		// Flip so we can look up location by menu ID.
		$menu_locations = [];
		foreach ( $locations as $location => $menu_id ) {
			if ( $menu_id ) {
				$menu_locations[ $menu_id ][] = $location;
			}
		}

		$result = [];
		foreach ( $menus as $menu ) {
			$result[] = [
				'id'         => $menu->term_id,
				'name'       => $menu->name,
				'slug'       => $menu->slug,
				'item_count' => (int) $menu->count,
				'locations'  => $menu_locations[ $menu->term_id ] ?? [],
			];
		}

		return [
			'menus' => $result,
			'total' => count( $result ),
		];
	}
}

/**
 * Get all items in a WordPress navigation menu.
 */
class GetMenu extends ToolInterface {

	public function get_name(): string {
		return 'get_menu';
	}

	public function get_description(): string {
		return 'Get all items in a WordPress navigation menu by menu ID or slug. Returns each item\'s title, URL, type, parent ID, and position.';
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
				'menu_id' => [
					'type'        => 'integer',
					'description' => 'The menu ID to retrieve items for.',
				],
				'slug'    => [
					'type'        => 'string',
					'description' => 'The menu slug to retrieve items for. Used if menu_id is not provided.',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function execute( array $input ): array {
		if ( ! empty( $input['menu_id'] ) ) {
			$menu = wp_get_nav_menu_object( absint( $input['menu_id'] ) );
		} elseif ( ! empty( $input['slug'] ) ) {
			$menu = wp_get_nav_menu_object( sanitize_key( $input['slug'] ) );
		} else {
			return [ 'error' => 'Provide menu_id or slug.' ];
		}

		if ( ! $menu ) {
			return [ 'error' => 'Menu not found.' ];
		}

		$items = wp_get_nav_menu_items( $menu->term_id );
		if ( ! $items ) {
			$items = [];
		}

		$result_items = [];
		foreach ( $items as $item ) {
			$result_items[] = [
				'id'        => $item->ID,
				'title'     => $item->title,
				'url'       => $item->url,
				'type'      => $item->type,       // custom, post_type, taxonomy
				'object'    => $item->object,     // page, post, category, etc.
				'object_id' => (int) $item->object_id,
				'parent_id' => (int) $item->menu_item_parent,
				'position'  => (int) $item->menu_order,
				'target'    => $item->target,
				'classes'   => implode( ' ', (array) $item->classes ),
			];
		}

		return [
			'menu_id'    => $menu->term_id,
			'menu_name'  => $menu->name,
			'menu_slug'  => $menu->slug,
			'items'      => $result_items,
			'item_count' => count( $result_items ),
		];
	}
}

/**
 * Create a new WordPress navigation menu.
 */
class CreateMenu extends ToolInterface {

	public function get_name(): string {
		return 'create_menu';
	}

	public function get_description(): string {
		return 'Create a new WordPress navigation menu with a given name. Optionally assign it to a registered theme location.';
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
				'name'     => [
					'type'        => 'string',
					'description' => 'The display name for the new menu.',
				],
				'location' => [
					'type'        => 'string',
					'description' => 'Optional theme location slug to assign this menu to (e.g., primary, footer). Use list_menus to see existing locations.',
				],
			],
			'required'   => [ 'name' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function execute( array $input ): array {
		$menu_name = sanitize_text_field( $input['name'] );
		$menu_id   = wp_create_nav_menu( $menu_name );

		if ( is_wp_error( $menu_id ) ) {
			return [ 'error' => $menu_id->get_error_message() ];
		}

		// Optionally assign to a theme location.
		if ( ! empty( $input['location'] ) ) {
			$location  = sanitize_key( $input['location'] );
			$locations = get_nav_menu_locations();
			$locations[ $location ] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}

		$menu = wp_get_nav_menu_object( $menu_id );

		return [
			'menu_id'  => $menu_id,
			'name'     => $menu->name,
			'slug'     => $menu->slug,
			'location' => $input['location'] ?? null,
			'message'  => "Menu \"{$menu_name}\" created successfully.",
		];
	}
}

/**
 * Delete a WordPress navigation menu and all its items. Requires confirmation.
 */
class DeleteMenu extends ToolInterface {

	public function get_name(): string {
		return 'delete_menu';
	}

	public function get_description(): string {
		return 'Delete a WordPress navigation menu and all its items. This is a destructive action that requires confirmation.';
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
				'menu_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the menu to delete.',
				],
			],
			'required'   => [ 'menu_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$menu_id = absint( $input['menu_id'] );
		$menu    = wp_get_nav_menu_object( $menu_id );

		if ( ! $menu ) {
			return [ 'error' => "Menu not found: {$menu_id}" ];
		}

		$menu_name = $menu->name;
		$result    = wp_delete_nav_menu( $menu_id );

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}

		if ( ! $result ) {
			return [ 'error' => "Failed to delete menu: {$menu_id}" ];
		}

		return [
			'menu_id' => $menu_id,
			'name'    => $menu_name,
			'message' => "Menu \"{$menu_name}\" deleted successfully.",
		];
	}
}

/**
 * Add a new item to a WordPress navigation menu.
 */
class AddMenuItem extends ToolInterface {

	public function get_name(): string {
		return 'add_menu_item';
	}

	public function get_description(): string {
		return 'Add a new item to a WordPress navigation menu. Supports custom URLs, links to posts/pages (post_type), or taxonomy terms. Returns the new menu item ID.';
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
				'menu_id'   => [
					'type'        => 'integer',
					'description' => 'The ID of the menu to add the item to.',
				],
				'title'     => [
					'type'        => 'string',
					'description' => 'The display title for the menu item.',
				],
				'url'       => [
					'type'        => 'string',
					'description' => 'URL for the menu item (required for type=custom).',
				],
				'type'      => [
					'type'        => 'string',
					'description' => 'Menu item type: "custom" for a custom URL, "post_type" to link a post/page, "taxonomy" to link a category/tag.',
					'enum'        => [ 'custom', 'post_type', 'taxonomy' ],
					'default'     => 'custom',
				],
				'object'    => [
					'type'        => 'string',
					'description' => 'For post_type: the post type slug (e.g., page, post). For taxonomy: the taxonomy slug (e.g., category, post_tag).',
				],
				'object_id' => [
					'type'        => 'integer',
					'description' => 'For post_type or taxonomy: the ID of the post or term to link.',
				],
				'parent_id' => [
					'type'        => 'integer',
					'description' => 'Menu item ID of the parent item for nested/submenu items. Use 0 for top-level.',
					'default'     => 0,
				],
				'position'  => [
					'type'        => 'integer',
					'description' => 'Position/order within the menu (1-based). Defaults to last position.',
				],
				'target'    => [
					'type'        => 'string',
					'description' => 'Link target: "" for same window, "_blank" for new tab.',
					'enum'        => [ '', '_blank' ],
					'default'     => '',
				],
			],
			'required'   => [ 'menu_id', 'title' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function execute( array $input ): array {
		$menu_id = absint( $input['menu_id'] );
		$menu    = wp_get_nav_menu_object( $menu_id );

		if ( ! $menu ) {
			return [ 'error' => "Menu not found: {$menu_id}" ];
		}

		$type = sanitize_key( $input['type'] ?? 'custom' );

		$item_data = [
			'menu-item-title'     => sanitize_text_field( $input['title'] ),
			'menu-item-type'      => $type,
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => absint( $input['parent_id'] ?? 0 ),
			'menu-item-target'    => in_array( $input['target'] ?? '', [ '_blank', '' ], true ) ? ( $input['target'] ?? '' ) : '',
		];

		if ( $type === 'custom' ) {
			$item_data['menu-item-url'] = esc_url_raw( $input['url'] ?? '' );
		} elseif ( in_array( $type, [ 'post_type', 'taxonomy' ], true ) ) {
			$item_data['menu-item-object']    = sanitize_key( $input['object'] ?? '' );
			$item_data['menu-item-object-id'] = absint( $input['object_id'] ?? 0 );
		}

		if ( ! empty( $input['position'] ) ) {
			$item_data['menu-item-position'] = absint( $input['position'] );
		}

		$item_id = wp_update_nav_menu_item( $menu_id, 0, $item_data );

		if ( is_wp_error( $item_id ) ) {
			return [ 'error' => $item_id->get_error_message() ];
		}

		return [
			'item_id' => $item_id,
			'menu_id' => $menu_id,
			'title'   => $input['title'],
			'message' => "Menu item \"{$input['title']}\" added successfully.",
		];
	}
}

/**
 * Update an existing item in a WordPress navigation menu.
 */
class UpdateMenuItem extends ToolInterface {

	public function get_name(): string {
		return 'update_menu_item';
	}

	public function get_description(): string {
		return 'Update an existing WordPress navigation menu item\'s title, URL, position, parent, or target. Provide menu_id, item_id, and any fields to change.';
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
				'menu_id'   => [
					'type'        => 'integer',
					'description' => 'The ID of the menu containing the item.',
				],
				'item_id'   => [
					'type'        => 'integer',
					'description' => 'The ID of the menu item to update.',
				],
				'title'     => [
					'type'        => 'string',
					'description' => 'New display title for the menu item.',
				],
				'url'       => [
					'type'        => 'string',
					'description' => 'New URL for the menu item (only for custom type items).',
				],
				'parent_id' => [
					'type'        => 'integer',
					'description' => 'New parent menu item ID. Use 0 to make it top-level.',
				],
				'position'  => [
					'type'        => 'integer',
					'description' => 'New position/order within the menu.',
				],
				'target'    => [
					'type'        => 'string',
					'description' => 'Link target: "" for same window, "_blank" for new tab.',
					'enum'        => [ '', '_blank' ],
				],
			],
			'required'   => [ 'menu_id', 'item_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function execute( array $input ): array {
		$menu_id = absint( $input['menu_id'] );
		$item_id = absint( $input['item_id'] );

		$menu = wp_get_nav_menu_object( $menu_id );
		if ( ! $menu ) {
			return [ 'error' => "Menu not found: {$menu_id}" ];
		}

		$item = get_post( $item_id );
		if ( ! $item || $item->post_type !== 'nav_menu_item' ) {
			return [ 'error' => "Menu item not found: {$item_id}" ];
		}

		// Build update data from existing meta + overrides.
		$item_data = [
			'menu-item-title'     => get_post_meta( $item_id, '_menu_item_title', true ) ?: $item->post_title,
			'menu-item-url'       => get_post_meta( $item_id, '_menu_item_url', true ),
			'menu-item-type'      => get_post_meta( $item_id, '_menu_item_type', true ),
			'menu-item-object'    => get_post_meta( $item_id, '_menu_item_object', true ),
			'menu-item-object-id' => (int) get_post_meta( $item_id, '_menu_item_object_id', true ),
			'menu-item-parent-id' => (int) get_post_meta( $item_id, '_menu_item_menu_item_parent', true ),
			'menu-item-position'  => (int) $item->menu_order,
			'menu-item-target'    => get_post_meta( $item_id, '_menu_item_target', true ),
			'menu-item-status'    => 'publish',
		];

		if ( isset( $input['title'] ) ) {
			$item_data['menu-item-title'] = sanitize_text_field( $input['title'] );
		}
		if ( isset( $input['url'] ) ) {
			$item_data['menu-item-url'] = esc_url_raw( $input['url'] );
		}
		if ( isset( $input['parent_id'] ) ) {
			$item_data['menu-item-parent-id'] = absint( $input['parent_id'] );
		}
		if ( isset( $input['position'] ) ) {
			$item_data['menu-item-position'] = absint( $input['position'] );
		}
		if ( isset( $input['target'] ) ) {
			$item_data['menu-item-target'] = in_array( $input['target'], [ '_blank', '' ], true ) ? $input['target'] : '';
		}

		$result = wp_update_nav_menu_item( $menu_id, $item_id, $item_data );

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}

		return [
			'item_id' => $item_id,
			'menu_id' => $menu_id,
			'title'   => $item_data['menu-item-title'],
			'message' => 'Menu item updated successfully.',
		];
	}
}

/**
 * Delete a single item from a WordPress navigation menu. Requires confirmation.
 */
class DeleteMenuItem extends ToolInterface {

	public function get_name(): string {
		return 'delete_menu_item';
	}

	public function get_description(): string {
		return 'Delete a single item from a WordPress navigation menu. This is a destructive action that requires confirmation.';
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
				'item_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the menu item to delete.',
				],
			],
			'required'   => [ 'item_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_theme_options';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$item_id = absint( $input['item_id'] );
		$item    = get_post( $item_id );

		if ( ! $item || $item->post_type !== 'nav_menu_item' ) {
			return [ 'error' => "Menu item not found: {$item_id}" ];
		}

		$title  = $item->post_title;
		$result = wp_delete_post( $item_id, true );

		if ( ! $result ) {
			return [ 'error' => "Failed to delete menu item: {$item_id}" ];
		}

		return [
			'item_id' => $item_id,
			'title'   => $title,
			'message' => "Menu item \"{$title}\" deleted successfully.",
		];
	}
}
