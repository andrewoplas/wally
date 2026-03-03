<?php
namespace Wally\Tools;

/**
 * Complete ACF toolset — covers both ACF Free and ACF Pro.
 *
 * Sections:
 *   1. Post Type tools    (Pro 6.1+)  — list, create, update, delete
 *   2. Taxonomy tools     (Pro 6.1+)  — list, create, update, delete
 *   3. Field Group tools  (Free+Pro)  — list, get, create, update, delete
 *   4. Post field values  (Free+Pro)  — get all, update single
 *   5. Term field values  (Free+Pro)  — get all, update single
 *   6. User field values  (Free+Pro)  — get all, update single
 *   7. Options tools      (Pro)       — list pages, get fields, update field
 *
 * Each class overrides can_register() to skip registration silently when the
 * required ACF version is not active, so this file is safe to ship to all users.
 */

// ══════════════════════════════════════════════════════════════════════════════
// 1. POST TYPE TOOLS  (ACF Pro 6.1+)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * List all custom post types registered via ACF Pro.
 */
class AcfListPostTypes extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_get_acf_post_types' );
	}

	public function get_name(): string { return 'acf_list_post_types'; }

	public function get_description(): string {
		return 'List all custom post types registered via ACF Pro, showing slug, labels, icon, supports, and active status.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'read'; }

	public function get_parameters_schema(): array {
		return [ 'type' => 'object', 'properties' => [] ];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$post_types = acf_get_acf_post_types();
		$result     = [];

		foreach ( $post_types as $pt ) {
			$result[] = [
				'key'          => $pt['key'] ?? '',
				'slug'         => $pt['post_type'] ?? '',
				'singular'     => $pt['labels']['singular_name'] ?? ( $pt['singular_label'] ?? '' ),
				'plural'       => $pt['labels']['name'] ?? ( $pt['label'] ?? '' ),
				'description'  => $pt['description'] ?? '',
				'icon'         => $pt['menu_icon'] ?? '',
				'public'       => $pt['public'] ?? true,
				'hierarchical' => $pt['hierarchical'] ?? false,
				'has_archive'  => $pt['has_archive'] ?? false,
				'show_in_rest' => $pt['show_in_rest'] ?? true,
				'supports'     => $pt['supports'] ?? [],
				'active'       => ! empty( $pt['active'] ),
			];
		}

		return [ 'post_types' => $result, 'count' => count( $result ) ];
	}
}

/**
 * Create a new custom post type via ACF Pro.
 */
class AcfCreatePostType extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_update_post_type' );
	}

	public function get_name(): string { return 'acf_create_post_type'; }

	public function get_description(): string {
		return 'Create a new custom post type via ACF Pro. Immediately active on the site.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'create'; }
	public function requires_confirmation(): bool { return true; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'slug'           => [ 'type' => 'string', 'description' => 'Post type slug, lowercase with underscores, max 20 chars (e.g. "podcast").' ],
				'singular_label' => [ 'type' => 'string', 'description' => 'Singular label (e.g. "Podcast").' ],
				'plural_label'   => [ 'type' => 'string', 'description' => 'Plural label (e.g. "Podcasts").' ],
				'description'    => [ 'type' => 'string', 'description' => 'Optional description.' ],
				'icon'           => [ 'type' => 'string', 'description' => 'Dashicons class (e.g. "dashicons-microphone").', 'default' => 'dashicons-admin-post' ],
				'supports'       => [ 'type' => 'array', 'items' => [ 'type' => 'string' ], 'description' => 'Features: title, editor, thumbnail, excerpt, comments, revisions, author. Defaults to [title, editor, thumbnail, excerpt].' ],
				'public'         => [ 'type' => 'boolean', 'default' => true ],
				'hierarchical'   => [ 'type' => 'boolean', 'default' => false ],
				'has_archive'    => [ 'type' => 'boolean', 'default' => false ],
				'show_in_rest'   => [ 'type' => 'boolean', 'default' => true, 'description' => 'Required for Gutenberg/block editor support.' ],
			],
			'required'   => [ 'slug', 'singular_label', 'plural_label' ],
		];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$slug = sanitize_key( $input['slug'] );

		if ( empty( $slug ) ) {
			throw new \InvalidArgumentException( 'Post type slug cannot be empty.' );
		}
		if ( strlen( $slug ) > 20 ) {
			throw new \InvalidArgumentException( 'Post type slug must be 20 characters or fewer.' );
		}

		$singular = sanitize_text_field( $input['singular_label'] );
		$plural   = sanitize_text_field( $input['plural_label'] );
		$supports = ! empty( $input['supports'] ) ? array_map( 'sanitize_key', $input['supports'] ) : [ 'title', 'editor', 'thumbnail', 'excerpt' ];

		$result = acf_update_post_type( [
			'key'            => 'post_type_' . $slug,
			'title'          => $plural,
			'name'           => $slug,
			'post_type'      => $slug,
			'label'          => $plural,
			'singular_label' => $singular,
			'labels'         => [ 'name' => $plural, 'singular_name' => $singular ],
			'description'    => sanitize_text_field( $input['description'] ?? '' ),
			'public'         => isset( $input['public'] ) ? (bool) $input['public'] : true,
			'hierarchical'   => isset( $input['hierarchical'] ) ? (bool) $input['hierarchical'] : false,
			'has_archive'    => isset( $input['has_archive'] ) ? (bool) $input['has_archive'] : false,
			'show_in_rest'   => isset( $input['show_in_rest'] ) ? (bool) $input['show_in_rest'] : true,
			'supports'       => $supports,
			'menu_icon'      => sanitize_text_field( $input['icon'] ?? 'dashicons-admin-post' ),
			'rewrite'        => [ 'slug' => $slug ],
			'active'         => true,
		] );

		if ( ! $result ) {
			throw new \RuntimeException( "ACF failed to create post type '{$slug}'." );
		}

		return [
			'success' => true,
			'message' => "Post type '{$plural}' (slug: {$slug}) created successfully.",
			'slug'    => $slug,
		];
	}
}

/**
 * Update an existing ACF Pro custom post type.
 */
class AcfUpdatePostType extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_update_post_type' );
	}

	public function get_name(): string { return 'acf_update_post_type'; }

	public function get_description(): string {
		return 'Update settings of an existing ACF Pro custom post type (labels, icon, supports, visibility, etc.).';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'update'; }
	public function requires_confirmation(): bool { return true; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'key'            => [ 'type' => 'string', 'description' => 'The ACF post type key (e.g. "post_type_podcast") or slug (e.g. "podcast"). Use acf_list_post_types to find it.' ],
				'singular_label' => [ 'type' => 'string', 'description' => 'New singular label.' ],
				'plural_label'   => [ 'type' => 'string', 'description' => 'New plural label.' ],
				'description'    => [ 'type' => 'string' ],
				'icon'           => [ 'type' => 'string', 'description' => 'New dashicons class.' ],
				'supports'       => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
				'public'         => [ 'type' => 'boolean' ],
				'hierarchical'   => [ 'type' => 'boolean' ],
				'has_archive'    => [ 'type' => 'boolean' ],
				'show_in_rest'   => [ 'type' => 'boolean' ],
				'active'         => [ 'type' => 'boolean', 'description' => 'Set false to disable without deleting.' ],
			],
			'required'   => [ 'key' ],
		];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$identifier = sanitize_text_field( $input['key'] );
		$existing   = $this->find_post_type( $identifier );

		if ( ! $existing ) {
			throw new \InvalidArgumentException( "Post type '{$identifier}' not found in ACF." );
		}

		// Apply only provided fields.
		if ( isset( $input['singular_label'] ) ) {
			$existing['singular_label']          = sanitize_text_field( $input['singular_label'] );
			$existing['labels']['singular_name'] = $existing['singular_label'];
		}
		if ( isset( $input['plural_label'] ) ) {
			$existing['label']          = sanitize_text_field( $input['plural_label'] );
			$existing['labels']['name'] = $existing['label'];
			$existing['title']          = $existing['label'];
		}
		if ( isset( $input['description'] ) ) {
			$existing['description'] = sanitize_text_field( $input['description'] );
		}
		if ( isset( $input['icon'] ) ) {
			$existing['menu_icon'] = sanitize_text_field( $input['icon'] );
		}
		if ( isset( $input['supports'] ) ) {
			$existing['supports'] = array_map( 'sanitize_key', $input['supports'] );
		}
		if ( isset( $input['public'] ) ) {
			$existing['public'] = (bool) $input['public'];
		}
		if ( isset( $input['hierarchical'] ) ) {
			$existing['hierarchical'] = (bool) $input['hierarchical'];
		}
		if ( isset( $input['has_archive'] ) ) {
			$existing['has_archive'] = (bool) $input['has_archive'];
		}
		if ( isset( $input['show_in_rest'] ) ) {
			$existing['show_in_rest'] = (bool) $input['show_in_rest'];
		}
		if ( isset( $input['active'] ) ) {
			$existing['active'] = (bool) $input['active'];
		}

		$result = acf_update_post_type( $existing );

		if ( ! $result ) {
			throw new \RuntimeException( "Failed to update post type '{$identifier}'." );
		}

		return [ 'success' => true, 'message' => "Post type '{$identifier}' updated." ];
	}

	private function find_post_type( string $identifier ): ?array {
		foreach ( acf_get_acf_post_types() as $pt ) {
			if ( ( $pt['key'] ?? '' ) === $identifier || ( $pt['post_type'] ?? '' ) === $identifier ) {
				return $pt;
			}
		}
		return null;
	}
}

/**
 * Delete an ACF Pro custom post type.
 */
class AcfDeletePostType extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_update_post_type' );
	}

	public function get_name(): string { return 'acf_delete_post_type'; }

	public function get_description(): string {
		return 'Permanently delete a custom post type registered via ACF Pro. This removes the ACF registration but does not delete existing posts of that type.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'delete'; }
	public function requires_confirmation(): bool { return true; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'key' => [ 'type' => 'string', 'description' => 'The ACF post type key (e.g. "post_type_podcast") or slug (e.g. "podcast"). Use acf_list_post_types to find it.' ],
			],
			'required'   => [ 'key' ],
		];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$identifier = sanitize_text_field( $input['key'] );
		$existing   = null;

		foreach ( acf_get_acf_post_types() as $pt ) {
			if ( ( $pt['key'] ?? '' ) === $identifier || ( $pt['post_type'] ?? '' ) === $identifier ) {
				$existing = $pt;
				break;
			}
		}

		if ( ! $existing ) {
			throw new \InvalidArgumentException( "Post type '{$identifier}' not found in ACF." );
		}

		$post_id = $existing['ID'] ?? 0;

		if ( function_exists( 'acf_delete_post_type' ) ) {
			$result = acf_delete_post_type( $post_id );
		} else {
			$result = wp_delete_post( $post_id, true );
		}

		if ( ! $result ) {
			throw new \RuntimeException( "Failed to delete post type '{$identifier}'." );
		}

		return [
			'success' => true,
			'message' => "Post type '{$identifier}' deleted from ACF. Existing posts of this type are unaffected.",
		];
	}
}

// ══════════════════════════════════════════════════════════════════════════════
// 2. TAXONOMY TOOLS  (ACF Pro 6.1+)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * List all custom taxonomies registered via ACF Pro.
 */
class AcfListTaxonomies extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_get_acf_taxonomies' );
	}

	public function get_name(): string { return 'acf_list_taxonomies'; }

	public function get_description(): string {
		return 'List all custom taxonomies registered via ACF Pro, showing slug, labels, attached post types, and active status.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'read'; }

	public function get_parameters_schema(): array {
		return [ 'type' => 'object', 'properties' => [] ];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$taxonomies = acf_get_acf_taxonomies();
		$result     = [];

		foreach ( $taxonomies as $tax ) {
			$result[] = [
				'key'          => $tax['key'] ?? '',
				'slug'         => $tax['taxonomy'] ?? '',
				'singular'     => $tax['labels']['singular_name'] ?? ( $tax['singular_label'] ?? '' ),
				'plural'       => $tax['labels']['name'] ?? ( $tax['label'] ?? '' ),
				'description'  => $tax['description'] ?? '',
				'hierarchical' => $tax['hierarchical'] ?? true,
				'show_in_rest' => $tax['show_in_rest'] ?? true,
				'post_types'   => $tax['object_type'] ?? [],
				'active'       => ! empty( $tax['active'] ),
			];
		}

		return [ 'taxonomies' => $result, 'count' => count( $result ) ];
	}
}

/**
 * Create a new custom taxonomy via ACF Pro.
 */
class AcfCreateTaxonomy extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_update_taxonomy' );
	}

	public function get_name(): string { return 'acf_create_taxonomy'; }

	public function get_description(): string {
		return 'Create a new custom taxonomy via ACF Pro and attach it to one or more post types.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'create'; }
	public function requires_confirmation(): bool { return true; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'slug'           => [ 'type' => 'string', 'description' => 'Taxonomy slug, lowercase with underscores, max 32 chars (e.g. "podcast_category").' ],
				'singular_label' => [ 'type' => 'string', 'description' => 'Singular label (e.g. "Podcast Category").' ],
				'plural_label'   => [ 'type' => 'string', 'description' => 'Plural label (e.g. "Podcast Categories").' ],
				'post_types'     => [ 'type' => 'array', 'items' => [ 'type' => 'string' ], 'description' => 'Post type slugs to attach to (e.g. ["podcast"]).' ],
				'hierarchical'   => [ 'type' => 'boolean', 'default' => true, 'description' => 'true = category-like, false = tag-like.' ],
				'show_in_rest'   => [ 'type' => 'boolean', 'default' => true ],
				'description'    => [ 'type' => 'string' ],
			],
			'required'   => [ 'slug', 'singular_label', 'plural_label' ],
		];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$slug = sanitize_key( $input['slug'] );

		if ( empty( $slug ) ) {
			throw new \InvalidArgumentException( 'Taxonomy slug cannot be empty.' );
		}
		if ( strlen( $slug ) > 32 ) {
			throw new \InvalidArgumentException( 'Taxonomy slug must be 32 characters or fewer.' );
		}

		$singular   = sanitize_text_field( $input['singular_label'] );
		$plural     = sanitize_text_field( $input['plural_label'] );
		$post_types = ! empty( $input['post_types'] ) ? array_map( 'sanitize_key', $input['post_types'] ) : [];

		$result = acf_update_taxonomy( [
			'key'            => 'taxonomy_' . $slug,
			'title'          => $plural,
			'name'           => $slug,
			'taxonomy'       => $slug,
			'label'          => $plural,
			'singular_label' => $singular,
			'labels'         => [ 'name' => $plural, 'singular_name' => $singular ],
			'description'    => sanitize_text_field( $input['description'] ?? '' ),
			'hierarchical'   => isset( $input['hierarchical'] ) ? (bool) $input['hierarchical'] : true,
			'show_in_rest'   => isset( $input['show_in_rest'] ) ? (bool) $input['show_in_rest'] : true,
			'object_type'    => $post_types,
			'rewrite'        => [ 'slug' => $slug ],
			'active'         => true,
		] );

		if ( ! $result ) {
			throw new \RuntimeException( "ACF failed to create taxonomy '{$slug}'." );
		}

		$attached = ! empty( $post_types ) ? implode( ', ', $post_types ) : 'none';

		return [
			'success'    => true,
			'message'    => "Taxonomy '{$plural}' (slug: {$slug}) created and attached to: {$attached}.",
			'slug'       => $slug,
			'post_types' => $post_types,
		];
	}
}

/**
 * Update an existing ACF Pro taxonomy.
 */
class AcfUpdateTaxonomy extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_update_taxonomy' );
	}

	public function get_name(): string { return 'acf_update_taxonomy'; }

	public function get_description(): string {
		return 'Update settings of an existing ACF Pro taxonomy (labels, attached post types, hierarchy, etc.).';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'update'; }
	public function requires_confirmation(): bool { return true; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'key'            => [ 'type' => 'string', 'description' => 'ACF taxonomy key (e.g. "taxonomy_podcast_category") or slug. Use acf_list_taxonomies to find it.' ],
				'singular_label' => [ 'type' => 'string' ],
				'plural_label'   => [ 'type' => 'string' ],
				'description'    => [ 'type' => 'string' ],
				'post_types'     => [ 'type' => 'array', 'items' => [ 'type' => 'string' ], 'description' => 'Replace the full list of attached post type slugs.' ],
				'hierarchical'   => [ 'type' => 'boolean' ],
				'show_in_rest'   => [ 'type' => 'boolean' ],
				'active'         => [ 'type' => 'boolean', 'description' => 'Set false to disable without deleting.' ],
			],
			'required'   => [ 'key' ],
		];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$identifier = sanitize_text_field( $input['key'] );
		$existing   = null;

		foreach ( acf_get_acf_taxonomies() as $tax ) {
			if ( ( $tax['key'] ?? '' ) === $identifier || ( $tax['taxonomy'] ?? '' ) === $identifier ) {
				$existing = $tax;
				break;
			}
		}

		if ( ! $existing ) {
			throw new \InvalidArgumentException( "Taxonomy '{$identifier}' not found in ACF." );
		}

		if ( isset( $input['singular_label'] ) ) {
			$existing['singular_label']          = sanitize_text_field( $input['singular_label'] );
			$existing['labels']['singular_name'] = $existing['singular_label'];
		}
		if ( isset( $input['plural_label'] ) ) {
			$existing['label']          = sanitize_text_field( $input['plural_label'] );
			$existing['labels']['name'] = $existing['label'];
			$existing['title']          = $existing['label'];
		}
		if ( isset( $input['description'] ) ) {
			$existing['description'] = sanitize_text_field( $input['description'] );
		}
		if ( isset( $input['post_types'] ) ) {
			$existing['object_type'] = array_map( 'sanitize_key', $input['post_types'] );
		}
		if ( isset( $input['hierarchical'] ) ) {
			$existing['hierarchical'] = (bool) $input['hierarchical'];
		}
		if ( isset( $input['show_in_rest'] ) ) {
			$existing['show_in_rest'] = (bool) $input['show_in_rest'];
		}
		if ( isset( $input['active'] ) ) {
			$existing['active'] = (bool) $input['active'];
		}

		$result = acf_update_taxonomy( $existing );

		if ( ! $result ) {
			throw new \RuntimeException( "Failed to update taxonomy '{$identifier}'." );
		}

		return [ 'success' => true, 'message' => "Taxonomy '{$identifier}' updated." ];
	}
}

/**
 * Delete an ACF Pro taxonomy.
 */
class AcfDeleteTaxonomy extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_update_taxonomy' );
	}

	public function get_name(): string { return 'acf_delete_taxonomy'; }

	public function get_description(): string {
		return 'Permanently delete a taxonomy registered via ACF Pro. Does not delete existing term data.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'delete'; }
	public function requires_confirmation(): bool { return true; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'key' => [ 'type' => 'string', 'description' => 'ACF taxonomy key or slug. Use acf_list_taxonomies to find it.' ],
			],
			'required'   => [ 'key' ],
		];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$identifier = sanitize_text_field( $input['key'] );
		$existing   = null;

		foreach ( acf_get_acf_taxonomies() as $tax ) {
			if ( ( $tax['key'] ?? '' ) === $identifier || ( $tax['taxonomy'] ?? '' ) === $identifier ) {
				$existing = $tax;
				break;
			}
		}

		if ( ! $existing ) {
			throw new \InvalidArgumentException( "Taxonomy '{$identifier}' not found in ACF." );
		}

		$post_id = $existing['ID'] ?? 0;

		if ( function_exists( 'acf_delete_taxonomy' ) ) {
			$result = acf_delete_taxonomy( $post_id );
		} else {
			$result = wp_delete_post( $post_id, true );
		}

		if ( ! $result ) {
			throw new \RuntimeException( "Failed to delete taxonomy '{$identifier}'." );
		}

		return [
			'success' => true,
			'message' => "Taxonomy '{$identifier}' deleted from ACF. Existing term data is unaffected.",
		];
	}
}

// ══════════════════════════════════════════════════════════════════════════════
// 3. FIELD GROUP TOOLS  (ACF Free + Pro)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * List all ACF field groups.
 */
class AcfListFieldGroups extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_get_field_groups' );
	}

	public function get_name(): string { return 'acf_list_field_groups'; }

	public function get_description(): string {
		return 'List all ACF field groups showing title, key, location rules, field count, and active status. Optionally filter by post type.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'read'; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_type' => [ 'type' => 'string', 'description' => 'Filter by post type slug (optional).' ],
			],
		];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$args = [];
		if ( ! empty( $input['post_type'] ) ) {
			$args['post_type'] = sanitize_key( $input['post_type'] );
		}

		$groups = acf_get_field_groups( $args );
		$result = [];

		foreach ( $groups as $group ) {
			$fields = acf_get_fields( $group['key'] ) ?: [];

			$locations = [];
			foreach ( $group['location'] ?? [] as $rule_group ) {
				$parts = [];
				foreach ( $rule_group as $rule ) {
					$parts[] = "{$rule['param']} {$rule['operator']} {$rule['value']}";
				}
				$locations[] = implode( ' AND ', $parts );
			}

			$result[] = [
				'key'         => $group['key'],
				'title'       => $group['title'],
				'active'      => (bool) ( $group['active'] ?? true ),
				'field_count' => count( $fields ),
				'fields'      => array_map( fn( $f ) => [ 'label' => $f['label'], 'name' => $f['name'], 'type' => $f['type'] ], $fields ),
				'locations'   => $locations,
			];
		}

		return [ 'field_groups' => $result, 'count' => count( $result ) ];
	}
}

/**
 * Get full details of a single ACF field group including all field definitions.
 */
class AcfGetFieldGroup extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_get_field_group' );
	}

	public function get_name(): string { return 'acf_get_field_group'; }

	public function get_description(): string {
		return 'Get complete details of a single ACF field group including all field definitions (types, keys, settings).';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'read'; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'key' => [ 'type' => 'string', 'description' => 'Field group key (e.g. "group_podcast_details"). Use acf_list_field_groups to find it.' ],
			],
			'required'   => [ 'key' ],
		];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$key   = sanitize_text_field( $input['key'] );
		$group = acf_get_field_group( $key );

		if ( ! $group ) {
			throw new \InvalidArgumentException( "Field group '{$key}' not found." );
		}

		$fields = acf_get_fields( $key ) ?: [];

		$field_details = array_map( function ( $field ) {
			return [
				'key'          => $field['key'],
				'label'        => $field['label'],
				'name'         => $field['name'],
				'type'         => $field['type'],
				'required'     => ! empty( $field['required'] ),
				'instructions' => $field['instructions'] ?? '',
			];
		}, $fields );

		$locations = [];
		foreach ( $group['location'] ?? [] as $rule_group ) {
			$parts = [];
			foreach ( $rule_group as $rule ) {
				$parts[] = "{$rule['param']} {$rule['operator']} {$rule['value']}";
			}
			$locations[] = implode( ' AND ', $parts );
		}

		return [
			'key'       => $group['key'],
			'title'     => $group['title'],
			'active'    => (bool) ( $group['active'] ?? true ),
			'position'  => $group['position'] ?? 'normal',
			'locations' => $locations,
			'fields'    => $field_details,
		];
	}
}

/**
 * Create a new ACF field group with fields.
 */
class AcfCreateFieldGroup extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_update_field_group' );
	}

	public function get_name(): string { return 'acf_create_field_group'; }

	public function get_description(): string {
		return 'Create a new ACF field group with custom fields, assigned to a post type (or other location). '
			. 'Each field object supports: label (required), name (optional slug auto-derived from label), '
			. 'type (text|textarea|number|email|url|image|file|wysiwyg|select|radio|checkbox|true_false|date_picker|color_picker|relationship|post_object|taxonomy|user|repeater — default: text), '
			. 'required (bool), instructions (help text), placeholder, choices ({"value":"Label"} for select/radio/checkbox).';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'create'; }
	public function requires_confirmation(): bool { return true; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'title'     => [ 'type' => 'string', 'description' => 'Field group title (e.g. "Podcast Details").' ],
				'post_type' => [ 'type' => 'string', 'description' => 'Post type slug to assign this group to (e.g. "podcast").' ],
				'fields'    => [ 'type' => 'array', 'items' => [ 'type' => 'object' ], 'description' => 'Array of field objects. Minimum: { label: "Audio URL", type: "url" }.' ],
				'position'  => [ 'type' => 'string', 'enum' => [ 'normal', 'side', 'acf_after_title' ], 'default' => 'normal' ],
			],
			'required'   => [ 'title', 'post_type', 'fields' ],
		];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$title     = sanitize_text_field( $input['title'] );
		$post_type = sanitize_key( $input['post_type'] );
		$position  = in_array( $input['position'] ?? 'normal', [ 'normal', 'side', 'acf_after_title' ], true ) ? $input['position'] : 'normal';

		if ( empty( $title ) || empty( $post_type ) ) {
			throw new \InvalidArgumentException( 'title and post_type are required.' );
		}
		if ( empty( $input['fields'] ) || ! is_array( $input['fields'] ) ) {
			throw new \InvalidArgumentException( 'At least one field definition is required.' );
		}

		$group_key = 'group_' . sanitize_key( $title ) . '_' . substr( md5( uniqid( '', true ) ), 0, 8 );
		$fields    = $this->build_fields( $input['fields'], $group_key );

		$result = acf_update_field_group( [
			'key'                   => $group_key,
			'title'                 => $title,
			'fields'                => $fields,
			'location'              => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => $post_type ] ] ],
			'menu_order'            => 0,
			'position'              => $position,
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		] );

		if ( ! $result ) {
			throw new \RuntimeException( "ACF failed to create field group '{$title}'." );
		}

		return [
			'success'     => true,
			'message'     => "Field group '{$title}' created with " . count( $fields ) . " field(s), assigned to '{$post_type}'.",
			'group_key'   => $group_key,
			'field_count' => count( $fields ),
			'field_names' => array_column( $fields, 'name' ),
		];
	}

	protected function build_fields( array $raw_fields, string $group_key ): array {
		$fields = [];
		foreach ( $raw_fields as $index => $raw ) {
			if ( empty( $raw['label'] ) ) {
				continue;
			}

			$label = sanitize_text_field( $raw['label'] );
			$name  = ! empty( $raw['name'] ) ? sanitize_key( $raw['name'] ) : sanitize_key( str_replace( ' ', '_', strtolower( $label ) ) );
			$type  = sanitize_key( $raw['type'] ?? 'text' );

			$field = [
				'key'          => 'field_' . sanitize_key( $group_key ) . '_' . $name . '_' . $index,
				'label'        => $label,
				'name'         => $name,
				'type'         => $type,
				'required'     => ! empty( $raw['required'] ) ? 1 : 0,
				'instructions' => sanitize_text_field( $raw['instructions'] ?? '' ),
			];

			if ( in_array( $type, [ 'text', 'url', 'email', 'password', 'textarea' ], true ) ) {
				$field['placeholder'] = sanitize_text_field( $raw['placeholder'] ?? '' );
			}
			if ( 'textarea' === $type ) {
				$field['rows'] = intval( $raw['rows'] ?? 4 );
			}
			if ( 'number' === $type ) {
				if ( isset( $raw['min'] ) ) $field['min'] = intval( $raw['min'] );
				if ( isset( $raw['max'] ) ) $field['max'] = intval( $raw['max'] );
			}
			if ( in_array( $type, [ 'select', 'radio', 'checkbox' ], true ) && ! empty( $raw['choices'] ) ) {
				$field['choices'] = (array) $raw['choices'];
			}

			$fields[] = $field;
		}
		return $fields;
	}
}

/**
 * Update an existing ACF field group (settings or add fields).
 */
class AcfUpdateFieldGroup extends AcfCreateFieldGroup {

	public function get_name(): string { return 'acf_update_field_group'; }

	public function get_description(): string {
		return 'Update an existing ACF field group: rename it, change position, toggle active state, or append new fields to it.';
	}

	public function get_action(): string { return 'update'; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'key'        => [ 'type' => 'string', 'description' => 'Field group key to update (e.g. "group_podcast_details"). Use acf_list_field_groups to find it.' ],
				'title'      => [ 'type' => 'string', 'description' => 'New title (optional).' ],
				'position'   => [ 'type' => 'string', 'enum' => [ 'normal', 'side', 'acf_after_title' ] ],
				'active'     => [ 'type' => 'boolean', 'description' => 'Toggle the group on/off.' ],
				'add_fields' => [ 'type' => 'array', 'items' => [ 'type' => 'object' ], 'description' => 'New field objects to append. Same format as acf_create_field_group fields.' ],
			],
			'required'   => [ 'key' ],
		];
	}

	public function execute( array $input ): array {
		$key   = sanitize_text_field( $input['key'] );
		$group = acf_get_field_group( $key );

		if ( ! $group ) {
			throw new \InvalidArgumentException( "Field group '{$key}' not found." );
		}

		if ( isset( $input['title'] ) ) {
			$group['title'] = sanitize_text_field( $input['title'] );
		}
		if ( isset( $input['position'] ) && in_array( $input['position'], [ 'normal', 'side', 'acf_after_title' ], true ) ) {
			$group['position'] = $input['position'];
		}
		if ( isset( $input['active'] ) ) {
			$group['active'] = (bool) $input['active'] ? 1 : 0;
		}

		$existing_fields = acf_get_fields( $key ) ?: [];

		if ( ! empty( $input['add_fields'] ) && is_array( $input['add_fields'] ) ) {
			$new_fields            = $this->build_fields( $input['add_fields'], $key );
			$group['fields']       = array_merge( $existing_fields, $new_fields );
			$added_count           = count( $new_fields );
		} else {
			$group['fields'] = $existing_fields;
			$added_count     = 0;
		}

		$result = acf_update_field_group( $group );

		if ( ! $result ) {
			throw new \RuntimeException( "Failed to update field group '{$key}'." );
		}

		$msg = "Field group '{$key}' updated.";
		if ( $added_count > 0 ) {
			$msg .= " Added {$added_count} new field(s).";
		}

		return [ 'success' => true, 'message' => $msg ];
	}
}

/**
 * Delete an ACF field group.
 */
class AcfDeleteFieldGroup extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_delete_field_group' );
	}

	public function get_name(): string { return 'acf_delete_field_group'; }

	public function get_description(): string {
		return 'Permanently delete an ACF field group and all its field definitions. Field values already saved to posts are unaffected.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'delete'; }
	public function requires_confirmation(): bool { return true; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'key' => [ 'type' => 'string', 'description' => 'Field group key (e.g. "group_podcast_details"). Use acf_list_field_groups to find it.' ],
			],
			'required'   => [ 'key' ],
		];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$key   = sanitize_text_field( $input['key'] );
		$group = acf_get_field_group( $key );

		if ( ! $group ) {
			throw new \InvalidArgumentException( "Field group '{$key}' not found." );
		}

		$result = acf_delete_field_group( $group['ID'] );

		if ( ! $result ) {
			throw new \RuntimeException( "Failed to delete field group '{$key}'." );
		}

		return [
			'success' => true,
			'message' => "Field group '{$group['title']}' deleted. Existing field values in posts are unaffected.",
		];
	}
}

// ══════════════════════════════════════════════════════════════════════════════
// 4. POST FIELD VALUES  (ACF Free + Pro)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get all ACF field values for a post.
 */
class AcfGetPostFields extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_get_field_groups' );
	}

	public function get_name(): string { return 'acf_get_post_fields'; }

	public function get_description(): string {
		return 'Get all ACF custom field values for a specific post or page, grouped by field group.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'read'; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id' => [ 'type' => 'integer', 'description' => 'The post ID.' ],
			],
			'required'   => [ 'post_id' ],
		];
	}

	public function get_required_capability(): string { return 'edit_posts'; }

	public function execute( array $input ): array {
		$post_id = absint( $input['post_id'] );

		if ( ! get_post( $post_id ) ) {
			throw new \InvalidArgumentException( "Post {$post_id} not found." );
		}

		$field_groups = acf_get_field_groups( [ 'post_id' => $post_id ] );
		$result       = [];

		foreach ( $field_groups as $group ) {
			$fields = acf_get_fields( $group['key'] ) ?: [];
			if ( empty( $fields ) ) {
				continue;
			}

			$group_fields = [];
			foreach ( $fields as $field ) {
				// false = raw value (returns IDs for relationship fields, not post objects).
				$value          = get_field( $field['key'], $post_id, false );
				$group_fields[] = [
					'name'  => $field['name'],
					'label' => $field['label'],
					'type'  => $field['type'],
					'value' => $value,
				];
			}

			$result[] = [
				'group_title' => $group['title'],
				'group_key'   => $group['key'],
				'fields'      => $group_fields,
			];
		}

		return [ 'post_id' => $post_id, 'field_groups' => $result ];
	}
}

/**
 * Update a single ACF field value on a post.
 */
class AcfUpdatePostField extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_get_field_groups' );
	}

	public function get_name(): string { return 'acf_update_post_field'; }

	public function get_description(): string {
		return 'Update a single ACF custom field value on a post. Use the field\'s name (slug) not its label. '
			. 'Pass the appropriate type: string for text/url/email, boolean for true_false, integer for number, '
			. 'array of IDs for relationship/gallery/checkbox, array of objects for repeater rows.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'update'; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'    => [ 'type' => 'integer', 'description' => 'The post ID.' ],
				'field_name' => [ 'type' => 'string', 'description' => 'The ACF field name/slug (e.g. "audio_url"). Use acf_get_post_fields to see available fields.' ],
				'value'      => [ 'description' => 'The new field value. Type depends on the field: string, boolean, integer, or array.' ],
			],
			'required'   => [ 'post_id', 'field_name', 'value' ],
		];
	}

	public function get_required_capability(): string { return 'edit_posts'; }

	public function execute( array $input ): array {
		$post_id    = absint( $input['post_id'] );
		$field_name = sanitize_text_field( $input['field_name'] );
		$value      = $input['value'];

		if ( ! get_post( $post_id ) ) {
			throw new \InvalidArgumentException( "Post {$post_id} not found." );
		}

		$result = update_field( $field_name, $value, $post_id );

		return [
			'success'    => $result !== false,
			'post_id'    => $post_id,
			'field_name' => $field_name,
			'message'    => $result !== false ? "Field '{$field_name}' updated on post {$post_id}." : "update_field returned false — verify the field name is correct.",
		];
	}
}

// ══════════════════════════════════════════════════════════════════════════════
// 5. TERM FIELD VALUES  (ACF Free + Pro)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get all ACF field values for a taxonomy term.
 */
class AcfGetTermFields extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_get_field_groups' );
	}

	public function get_name(): string { return 'acf_get_term_fields'; }

	public function get_description(): string {
		return 'Get all ACF custom field values for a taxonomy term (e.g. a category or tag).';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'read'; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'taxonomy' => [ 'type' => 'string', 'description' => 'Taxonomy slug (e.g. "category", "post_tag", "podcast_category").' ],
				'term_id'  => [ 'type' => 'integer', 'description' => 'The term ID.' ],
			],
			'required'   => [ 'taxonomy', 'term_id' ],
		];
	}

	public function get_required_capability(): string { return 'edit_posts'; }

	public function execute( array $input ): array {
		$taxonomy = sanitize_key( $input['taxonomy'] );
		$term_id  = absint( $input['term_id'] );
		$term_key = "{$taxonomy}_{$term_id}";

		$term = get_term( $term_id, $taxonomy );
		if ( is_wp_error( $term ) || ! $term ) {
			throw new \InvalidArgumentException( "Term {$term_id} in taxonomy '{$taxonomy}' not found." );
		}

		$field_groups = acf_get_field_groups( [ 'taxonomy' => $taxonomy ] );
		$result       = [];

		foreach ( $field_groups as $group ) {
			$fields = acf_get_fields( $group['key'] ) ?: [];
			if ( empty( $fields ) ) {
				continue;
			}

			$group_fields = [];
			foreach ( $fields as $field ) {
				$group_fields[] = [
					'name'  => $field['name'],
					'label' => $field['label'],
					'type'  => $field['type'],
					'value' => get_field( $field['key'], $term_key, false ),
				];
			}

			$result[] = [
				'group_title' => $group['title'],
				'group_key'   => $group['key'],
				'fields'      => $group_fields,
			];
		}

		return [
			'taxonomy'     => $taxonomy,
			'term_id'      => $term_id,
			'term_name'    => $term->name,
			'field_groups' => $result,
		];
	}
}

/**
 * Update a single ACF field value on a taxonomy term.
 */
class AcfUpdateTermField extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_get_field_groups' );
	}

	public function get_name(): string { return 'acf_update_term_field'; }

	public function get_description(): string {
		return 'Update a single ACF custom field value on a taxonomy term.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'update'; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'taxonomy'   => [ 'type' => 'string', 'description' => 'Taxonomy slug (e.g. "category").' ],
				'term_id'    => [ 'type' => 'integer', 'description' => 'The term ID.' ],
				'field_name' => [ 'type' => 'string', 'description' => 'ACF field name/slug.' ],
				'value'      => [ 'description' => 'New field value.' ],
			],
			'required'   => [ 'taxonomy', 'term_id', 'field_name', 'value' ],
		];
	}

	public function get_required_capability(): string { return 'manage_categories'; }

	public function execute( array $input ): array {
		$taxonomy   = sanitize_key( $input['taxonomy'] );
		$term_id    = absint( $input['term_id'] );
		$field_name = sanitize_text_field( $input['field_name'] );
		$term_key   = "{$taxonomy}_{$term_id}";

		$term = get_term( $term_id, $taxonomy );
		if ( is_wp_error( $term ) || ! $term ) {
			throw new \InvalidArgumentException( "Term {$term_id} in taxonomy '{$taxonomy}' not found." );
		}

		$result = update_field( $field_name, $input['value'], $term_key );

		return [
			'success'    => $result !== false,
			'term_id'    => $term_id,
			'taxonomy'   => $taxonomy,
			'field_name' => $field_name,
			'message'    => $result !== false ? "Field '{$field_name}' updated on {$taxonomy} term {$term_id}." : "update_field returned false — verify the field name.",
		];
	}
}

// ══════════════════════════════════════════════════════════════════════════════
// 6. USER FIELD VALUES  (ACF Free + Pro)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get all ACF field values for a user.
 */
class AcfGetUserFields extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_get_field_groups' );
	}

	public function get_name(): string { return 'acf_get_user_fields'; }

	public function get_description(): string {
		return 'Get all ACF custom field values for a WordPress user.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'read'; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'user_id' => [ 'type' => 'integer', 'description' => 'The user ID.' ],
			],
			'required'   => [ 'user_id' ],
		];
	}

	public function get_required_capability(): string { return 'edit_users'; }

	public function execute( array $input ): array {
		$user_id  = absint( $input['user_id'] );
		$user_key = "user_{$user_id}";

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			throw new \InvalidArgumentException( "User {$user_id} not found." );
		}

		$field_groups = acf_get_field_groups( [ 'user_id' => $user_id ] );
		$result       = [];

		foreach ( $field_groups as $group ) {
			$fields = acf_get_fields( $group['key'] ) ?: [];
			if ( empty( $fields ) ) {
				continue;
			}

			$group_fields = [];
			foreach ( $fields as $field ) {
				$group_fields[] = [
					'name'  => $field['name'],
					'label' => $field['label'],
					'type'  => $field['type'],
					'value' => get_field( $field['key'], $user_key, false ),
				];
			}

			$result[] = [
				'group_title' => $group['title'],
				'group_key'   => $group['key'],
				'fields'      => $group_fields,
			];
		}

		return [
			'user_id'      => $user_id,
			'display_name' => $user->display_name,
			'field_groups' => $result,
		];
	}
}

/**
 * Update a single ACF field value on a user.
 */
class AcfUpdateUserField extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_get_field_groups' );
	}

	public function get_name(): string { return 'acf_update_user_field'; }

	public function get_description(): string {
		return 'Update a single ACF custom field value on a WordPress user.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'update'; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'user_id'    => [ 'type' => 'integer', 'description' => 'The user ID.' ],
				'field_name' => [ 'type' => 'string', 'description' => 'ACF field name/slug.' ],
				'value'      => [ 'description' => 'New field value.' ],
			],
			'required'   => [ 'user_id', 'field_name', 'value' ],
		];
	}

	public function get_required_capability(): string { return 'edit_users'; }

	public function execute( array $input ): array {
		$user_id    = absint( $input['user_id'] );
		$field_name = sanitize_text_field( $input['field_name'] );
		$user_key   = "user_{$user_id}";

		if ( ! get_userdata( $user_id ) ) {
			throw new \InvalidArgumentException( "User {$user_id} not found." );
		}

		$result = update_field( $field_name, $input['value'], $user_key );

		return [
			'success'    => $result !== false,
			'user_id'    => $user_id,
			'field_name' => $field_name,
			'message'    => $result !== false ? "Field '{$field_name}' updated on user {$user_id}." : "update_field returned false — verify the field name.",
		];
	}
}

// ══════════════════════════════════════════════════════════════════════════════
// 7. OPTIONS PAGE TOOLS  (ACF Pro)
// ══════════════════════════════════════════════════════════════════════════════

/**
 * List all registered ACF options pages.
 */
class AcfListOptionsPages extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_add_options_page' );
	}

	public function get_name(): string { return 'acf_list_options_pages'; }

	public function get_description(): string {
		return 'List all ACF Pro options pages registered on this site, with their slug, title, and parent page.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'read'; }

	public function get_parameters_schema(): array {
		return [ 'type' => 'object', 'properties' => [] ];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		// Discover options pages by scanning field group location rules.
		$all_groups   = acf_get_field_groups();
		$pages_found  = [];

		foreach ( $all_groups as $group ) {
			foreach ( $group['location'] ?? [] as $rule_group ) {
				foreach ( $rule_group as $rule ) {
					if ( 'options_page' === $rule['param'] && ! isset( $pages_found[ $rule['value'] ] ) ) {
						$pages_found[ $rule['value'] ] = $rule['value'];
					}
				}
			}
		}

		// Also try the ACF Pro internal store if available.
		$pages = [];
		if ( function_exists( 'acf' ) && isset( acf()->options_page ) && is_object( acf()->options_page ) ) {
			$registered = acf()->options_page->pages ?? [];
			foreach ( $registered as $slug => $page ) {
				$pages[] = [
					'slug'        => $slug,
					'page_title'  => $page['page_title'] ?? $slug,
					'menu_title'  => $page['menu_title'] ?? $slug,
					'parent_slug' => $page['parent_slug'] ?? '',
				];
				unset( $pages_found[ $slug ] );
			}
		}

		// Add any options pages found via location rules but not in the store.
		foreach ( $pages_found as $slug ) {
			$pages[] = [ 'slug' => $slug, 'page_title' => $slug, 'menu_title' => $slug, 'parent_slug' => '' ];
		}

		return [ 'options_pages' => $pages, 'count' => count( $pages ) ];
	}
}

/**
 * Get all ACF field values from an options page.
 */
class AcfGetOptionsFields extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_add_options_page' );
	}

	public function get_name(): string { return 'acf_get_options_fields'; }

	public function get_description(): string {
		return 'Get all ACF field values stored on an options page (site-wide settings). Use acf_list_options_pages to find the page slug.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'read'; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'page_slug' => [ 'type' => 'string', 'description' => 'Options page slug (e.g. "theme-settings"). Leave empty to get all options page fields.' ],
			],
		];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$page_slug    = sanitize_text_field( $input['page_slug'] ?? '' );
		$all_groups   = acf_get_field_groups();
		$result       = [];

		foreach ( $all_groups as $group ) {
			$is_options = false;
			foreach ( $group['location'] ?? [] as $rule_group ) {
				foreach ( $rule_group as $rule ) {
					if ( 'options_page' === $rule['param'] ) {
						if ( empty( $page_slug ) || $rule['value'] === $page_slug ) {
							$is_options = true;
							break 2;
						}
					}
				}
			}

			if ( ! $is_options ) {
				continue;
			}

			$fields = acf_get_fields( $group['key'] ) ?: [];
			if ( empty( $fields ) ) {
				continue;
			}

			$group_fields = [];
			foreach ( $fields as $field ) {
				$group_fields[] = [
					'name'  => $field['name'],
					'label' => $field['label'],
					'type'  => $field['type'],
					'value' => get_field( $field['key'], 'option', false ),
				];
			}

			$result[] = [
				'group_title' => $group['title'],
				'group_key'   => $group['key'],
				'fields'      => $group_fields,
			];
		}

		return [ 'page_slug' => $page_slug ?: 'all', 'field_groups' => $result ];
	}
}

/**
 * Update a single ACF field value on an options page.
 */
class AcfUpdateOptionsField extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'acf_add_options_page' );
	}

	public function get_name(): string { return 'acf_update_options_field'; }

	public function get_description(): string {
		return 'Update a single ACF field value on an options page (site-wide setting). Use acf_get_options_fields to see available fields and their names.';
	}

	public function get_category(): string { return 'acf'; }
	public function get_action(): string { return 'update'; }

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'field_name' => [ 'type' => 'string', 'description' => 'ACF field name/slug (e.g. "site_tagline").' ],
				'value'      => [ 'description' => 'New field value.' ],
			],
			'required'   => [ 'field_name', 'value' ],
		];
	}

	public function get_required_capability(): string { return 'manage_options'; }

	public function execute( array $input ): array {
		$field_name = sanitize_text_field( $input['field_name'] );
		$result     = update_field( $field_name, $input['value'], 'option' );

		return [
			'success'    => $result !== false,
			'field_name' => $field_name,
			'message'    => $result !== false ? "Options field '{$field_name}' updated." : "update_field returned false — verify the field name.",
		];
	}
}
