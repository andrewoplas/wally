<?php
namespace Wally\Tools;

/**
 * Taxonomy tools for managing WordPress categories and tags.
 *
 * Tools: list_categories, list_tags, create_category, create_tag.
 * All tools belong to the "content" category per the spec §7.
 */

/**
 * List categories with optional filters.
 */
class ListCategories extends ToolInterface {

	public function get_name(): string {
		return 'list_categories';
	}

	public function get_description(): string {
		return 'List WordPress categories with optional filters (search, parent, hide_empty).';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'search'     => [
					'type'        => 'string',
					'description' => 'Search categories by name.',
				],
				'parent'     => [
					'type'        => 'integer',
					'description' => 'Filter by parent category ID (0 for top-level only).',
				],
				'hide_empty' => [
					'type'        => 'boolean',
					'description' => 'Whether to hide categories with no posts.',
					'default'     => false,
				],
				'per_page'   => [
					'type'        => 'integer',
					'description' => 'Number of categories to return (max 100).',
					'default'     => 100,
				],
				'orderby'    => [
					'type'        => 'string',
					'description' => 'Field to order by.',
					'enum'        => [ 'name', 'count', 'id', 'slug' ],
					'default'     => 'name',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'read';
	}

	public function execute( array $input ): array {
		$args = [
			'taxonomy'   => 'category',
			'hide_empty' => ! empty( $input['hide_empty'] ),
			'number'     => min( (int) ( $input['per_page'] ?? 100 ), 100 ),
			'orderby'    => sanitize_key( $input['orderby'] ?? 'name' ),
			'order'      => 'ASC',
		];

		if ( ! empty( $input['search'] ) ) {
			$args['search'] = sanitize_text_field( $input['search'] );
		}

		if ( isset( $input['parent'] ) ) {
			$args['parent'] = absint( $input['parent'] );
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return [ 'error' => $terms->get_error_message() ];
		}

		$categories = array_map(
			function ( $term ) {
				return [
					'id'     => $term->term_id,
					'name'   => $term->name,
					'slug'   => $term->slug,
					'parent' => $term->parent,
					'count'  => $term->count,
				];
			},
			$terms
		);

		return [
			'categories' => array_values( $categories ),
			'total'      => count( $categories ),
		];
	}
}

/**
 * List tags with optional filters.
 */
class ListTags extends ToolInterface {

	public function get_name(): string {
		return 'list_tags';
	}

	public function get_description(): string {
		return 'List WordPress tags with optional filters (search, hide_empty).';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'search'     => [
					'type'        => 'string',
					'description' => 'Search tags by name.',
				],
				'hide_empty' => [
					'type'        => 'boolean',
					'description' => 'Whether to hide tags with no posts.',
					'default'     => false,
				],
				'per_page'   => [
					'type'        => 'integer',
					'description' => 'Number of tags to return (max 100).',
					'default'     => 100,
				],
				'orderby'    => [
					'type'        => 'string',
					'description' => 'Field to order by.',
					'enum'        => [ 'name', 'count', 'id', 'slug' ],
					'default'     => 'name',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'read';
	}

	public function execute( array $input ): array {
		$args = [
			'taxonomy'   => 'post_tag',
			'hide_empty' => ! empty( $input['hide_empty'] ),
			'number'     => min( (int) ( $input['per_page'] ?? 100 ), 100 ),
			'orderby'    => sanitize_key( $input['orderby'] ?? 'name' ),
			'order'      => 'ASC',
		];

		if ( ! empty( $input['search'] ) ) {
			$args['search'] = sanitize_text_field( $input['search'] );
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return [ 'error' => $terms->get_error_message() ];
		}

		$tags = array_map(
			function ( $term ) {
				return [
					'id'    => $term->term_id,
					'name'  => $term->name,
					'slug'  => $term->slug,
					'count' => $term->count,
				];
			},
			$terms
		);

		return [
			'tags'  => array_values( $tags ),
			'total' => count( $tags ),
		];
	}
}

/**
 * Create a new category.
 */
class CreateCategory extends ToolInterface {

	public function get_name(): string {
		return 'create_category';
	}

	public function get_description(): string {
		return 'Create a new WordPress category with name, slug, description, and optional parent.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'create';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'name'        => [
					'type'        => 'string',
					'description' => 'Category name.',
				],
				'slug'        => [
					'type'        => 'string',
					'description' => 'Category slug (auto-generated from name if omitted).',
				],
				'description' => [
					'type'        => 'string',
					'description' => 'Category description.',
				],
				'parent'      => [
					'type'        => 'integer',
					'description' => 'Parent category ID (0 for top-level).',
					'default'     => 0,
				],
			],
			'required'   => [ 'name' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_categories';
	}

	public function execute( array $input ): array {
		$args = [
			'slug'        => sanitize_title( $input['slug'] ?? $input['name'] ),
			'description' => sanitize_textarea_field( $input['description'] ?? '' ),
			'parent'      => absint( $input['parent'] ?? 0 ),
		];

		$result = wp_insert_term( sanitize_text_field( $input['name'] ), 'category', $args );

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}

		$term = get_term( $result['term_id'], 'category' );

		return [
			'id'      => $term->term_id,
			'name'    => $term->name,
			'slug'    => $term->slug,
			'parent'  => $term->parent,
			'message' => "Category \"{$term->name}\" created successfully.",
		];
	}
}

/**
 * Create a new tag.
 */
class CreateTag extends ToolInterface {

	public function get_name(): string {
		return 'create_tag';
	}

	public function get_description(): string {
		return 'Create a new WordPress tag with name, slug, and optional description.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'create';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'name'        => [
					'type'        => 'string',
					'description' => 'Tag name.',
				],
				'slug'        => [
					'type'        => 'string',
					'description' => 'Tag slug (auto-generated from name if omitted).',
				],
				'description' => [
					'type'        => 'string',
					'description' => 'Tag description.',
				],
			],
			'required'   => [ 'name' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_categories';
	}

	public function execute( array $input ): array {
		$args = [
			'slug'        => sanitize_title( $input['slug'] ?? $input['name'] ),
			'description' => sanitize_textarea_field( $input['description'] ?? '' ),
		];

		$result = wp_insert_term( sanitize_text_field( $input['name'] ), 'post_tag', $args );

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}

		$term = get_term( $result['term_id'], 'post_tag' );

		return [
			'id'      => $term->term_id,
			'name'    => $term->name,
			'slug'    => $term->slug,
			'message' => "Tag \"{$term->name}\" created successfully.",
		];
	}
}
