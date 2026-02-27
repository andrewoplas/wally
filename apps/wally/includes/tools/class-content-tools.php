<?php
namespace Wally\Tools;

/**
 * Content tools for managing WordPress posts and pages.
 *
 * Tools: list_posts, get_post, create_post, update_post, delete_post.
 * All tools belong to the "content" category and use WordPress core
 * post functions with proper capability checks.
 */

/**
 * List posts/pages with optional filters.
 */
class ListPosts extends ToolInterface {

	public function get_name(): string {
		return 'list_posts';
	}

	public function get_description(): string {
		return 'List WordPress posts or pages with optional filters (status, type, date range, author, search).';
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
				'post_type'   => [
					'type'        => 'string',
					'description' => 'Post type to query (e.g. post, page, or custom post type).',
					'default'     => 'post',
				],
				'status'      => [
					'type'        => 'string',
					'description' => 'Post status filter.',
					'enum'        => [ 'publish', 'draft', 'pending', 'private', 'trash', 'any' ],
					'default'     => 'any',
				],
				'search'      => [
					'type'        => 'string',
					'description' => 'Search keyword to filter posts by title or content.',
				],
				'author'      => [
					'type'        => 'integer',
					'description' => 'Filter by author user ID.',
				],
				'date_after'  => [
					'type'        => 'string',
					'description' => 'Return posts published after this date (YYYY-MM-DD).',
				],
				'date_before' => [
					'type'        => 'string',
					'description' => 'Return posts published before this date (YYYY-MM-DD).',
				],
				'per_page'    => [
					'type'        => 'integer',
					'description' => 'Number of posts to return (max 100).',
					'default'     => 20,
				],
				'page'        => [
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				],
				'orderby'     => [
					'type'        => 'string',
					'description' => 'Field to order results by.',
					'enum'        => [ 'date', 'title', 'modified', 'ID' ],
					'default'     => 'date',
				],
				'order'       => [
					'type'        => 'string',
					'description' => 'Sort direction.',
					'enum'        => [ 'ASC', 'DESC' ],
					'default'     => 'DESC',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'read';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		$args = [
			'post_type'      => sanitize_key( $input['post_type'] ?? 'post' ),
			'post_status'    => sanitize_key( $input['status'] ?? 'any' ),
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => sanitize_key( $input['orderby'] ?? 'date' ),
			'order'          => strtoupper( $input['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC',
		];

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = sanitize_text_field( $input['search'] );
		}

		if ( ! empty( $input['author'] ) ) {
			$args['author'] = absint( $input['author'] );
		}

		// Date range filtering.
		if ( ! empty( $input['date_after'] ) || ! empty( $input['date_before'] ) ) {
			$date_query = [];
			if ( ! empty( $input['date_after'] ) ) {
				$date_query['after'] = sanitize_text_field( $input['date_after'] );
			}
			if ( ! empty( $input['date_before'] ) ) {
				$date_query['before'] = sanitize_text_field( $input['date_before'] );
			}
			$date_query['inclusive'] = true;
			$args['date_query']     = [ $date_query ];
		}

		$query = new \WP_Query( $args );
		$posts = [];

		foreach ( $query->posts as $post ) {
			$posts[] = [
				'id'        => $post->ID,
				'title'     => $post->post_title,
				'status'    => $post->post_status,
				'type'      => $post->post_type,
				'author'    => (int) $post->post_author,
				'date'      => $post->post_date,
				'modified'  => $post->post_modified,
				'permalink' => get_permalink( $post->ID ),
			];
		}

		return [
			'posts'       => $posts,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Get full details of a single post.
 */
class GetPost extends ToolInterface {

	public function get_name(): string {
		return 'get_post';
	}

	public function get_description(): string {
		return 'Get full post content including title, body, excerpt, featured image, taxonomies, and custom fields.';
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
				'post_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the post to retrieve.',
				],
			],
			'required'   => [ 'post_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'read';
	}

	public function execute( array $input ): array {
		$post_id = absint( $input['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return [ 'error' => "Post not found: {$post_id}" ];
		}

		// Featured image.
		$thumbnail_id  = get_post_thumbnail_id( $post_id );
		$featured_image = $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : null;

		// Taxonomies.
		$taxonomies    = get_object_taxonomies( $post->post_type, 'names' );
		$taxonomy_data = [];
		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_post_terms( $post_id, $taxonomy, [ 'fields' => 'all' ] );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$taxonomy_data[ $taxonomy ] = array_map(
					function ( $term ) {
						return [
							'id'   => $term->term_id,
							'name' => $term->name,
							'slug' => $term->slug,
						];
					},
					$terms
				);
			}
		}

		// Custom fields (exclude internal meta starting with _).
		$all_meta     = get_post_meta( $post_id );
		$custom_fields = [];
		foreach ( $all_meta as $key => $values ) {
			if ( str_starts_with( $key, '_' ) ) {
				continue;
			}
			$custom_fields[ $key ] = count( $values ) === 1 ? $values[0] : $values;
		}

		return [
			'id'              => $post->ID,
			'title'           => $post->post_title,
			'content'         => $post->post_content,
			'excerpt'         => $post->post_excerpt,
			'status'          => $post->post_status,
			'type'            => $post->post_type,
			'author'          => (int) $post->post_author,
			'date'            => $post->post_date,
			'modified'        => $post->post_modified,
			'permalink'       => get_permalink( $post_id ),
			'featured_image'  => $featured_image,
			'taxonomies'      => $taxonomy_data,
			'custom_fields'   => $custom_fields,
		];
	}
}

/**
 * Create a new post or page.
 */
class CreatePost extends ToolInterface {

	public function get_name(): string {
		return 'create_post';
	}

	public function get_description(): string {
		return 'Create a new WordPress post or page with title, content, status, type, categories, and tags.';
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
				'title'      => [
					'type'        => 'string',
					'description' => 'Post title.',
				],
				'content'    => [
					'type'        => 'string',
					'description' => 'Post content (HTML).',
				],
				'excerpt'    => [
					'type'        => 'string',
					'description' => 'Post excerpt.',
				],
				'status'     => [
					'type'        => 'string',
					'description' => 'Post status.',
					'enum'        => [ 'draft', 'publish', 'pending', 'private' ],
					'default'     => 'draft',
				],
				'post_type'  => [
					'type'        => 'string',
					'description' => 'Post type (post, page, or custom post type).',
					'default'     => 'post',
				],
				'categories' => [
					'type'        => 'array',
					'description' => 'Array of category IDs to assign.',
				],
				'tags'       => [
					'type'        => 'array',
					'description' => 'Array of tag IDs to assign.',
				],
			],
			'required'   => [ 'title' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $input ): array {
		$post_data = [
			'post_title'  => sanitize_text_field( $input['title'] ),
			'post_status' => sanitize_key( $input['status'] ?? 'draft' ),
			'post_type'   => sanitize_key( $input['post_type'] ?? 'post' ),
		];

		if ( isset( $input['content'] ) ) {
			$post_data['post_content'] = wp_kses_post( $input['content'] );
		}

		if ( isset( $input['excerpt'] ) ) {
			$post_data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
		}

		// Validate post type exists.
		if ( ! post_type_exists( $post_data['post_type'] ) ) {
			return [ 'error' => "Invalid post type: {$post_data['post_type']}" ];
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return [ 'error' => $post_id->get_error_message() ];
		}

		// Assign categories.
		if ( ! empty( $input['categories'] ) ) {
			$category_ids = array_map( 'absint', $input['categories'] );
			wp_set_post_categories( $post_id, $category_ids );
		}

		// Assign tags.
		if ( ! empty( $input['tags'] ) ) {
			$tag_ids = array_map( 'absint', $input['tags'] );
			wp_set_post_tags( $post_id, $tag_ids );
		}

		return [
			'post_id'   => $post_id,
			'title'     => get_the_title( $post_id ),
			'status'    => get_post_status( $post_id ),
			'type'      => get_post_type( $post_id ),
			'permalink' => get_permalink( $post_id ),
			'message'   => "Post created successfully.",
		];
	}
}

/**
 * Update an existing post's fields.
 */
class UpdatePost extends ToolInterface {

	public function get_name(): string {
		return 'update_post';
	}

	public function get_description(): string {
		return 'Update fields on an existing WordPress post or page (title, content, excerpt, status, categories, tags).';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'    => [
					'type'        => 'integer',
					'description' => 'The ID of the post to update.',
				],
				'title'      => [
					'type'        => 'string',
					'description' => 'New post title.',
				],
				'content'    => [
					'type'        => 'string',
					'description' => 'New post content (HTML).',
				],
				'excerpt'    => [
					'type'        => 'string',
					'description' => 'New post excerpt.',
				],
				'status'     => [
					'type'        => 'string',
					'description' => 'New post status.',
					'enum'        => [ 'draft', 'publish', 'pending', 'private', 'trash' ],
				],
				'categories' => [
					'type'        => 'array',
					'description' => 'Array of category IDs to replace existing categories.',
				],
				'tags'       => [
					'type'        => 'array',
					'description' => 'Array of tag IDs to replace existing tags.',
				],
			],
			'required'   => [ 'post_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $input ): array {
		$post_id = absint( $input['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return [ 'error' => "Post not found: {$post_id}" ];
		}

		$post_data = [ 'ID' => $post_id ];
		$changes   = [];

		if ( isset( $input['title'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $input['title'] );
			$changes[] = 'title';
		}

		if ( isset( $input['content'] ) ) {
			$post_data['post_content'] = wp_kses_post( $input['content'] );
			$changes[] = 'content';
		}

		if ( isset( $input['excerpt'] ) ) {
			$post_data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
			$changes[] = 'excerpt';
		}

		if ( isset( $input['status'] ) ) {
			$post_data['post_status'] = sanitize_key( $input['status'] );
			$changes[] = 'status';
		}

		if ( count( $post_data ) > 1 ) {
			$result = wp_update_post( $post_data, true );
			if ( is_wp_error( $result ) ) {
				return [ 'error' => $result->get_error_message() ];
			}
		}

		// Update categories.
		if ( isset( $input['categories'] ) ) {
			$category_ids = array_map( 'absint', $input['categories'] );
			wp_set_post_categories( $post_id, $category_ids );
			$changes[] = 'categories';
		}

		// Update tags.
		if ( isset( $input['tags'] ) ) {
			$tag_ids = array_map( 'absint', $input['tags'] );
			wp_set_post_tags( $post_id, $tag_ids );
			$changes[] = 'tags';
		}

		if ( empty( $changes ) ) {
			return [ 'error' => 'No fields provided to update.' ];
		}

		return [
			'post_id'   => $post_id,
			'title'     => get_the_title( $post_id ),
			'status'    => get_post_status( $post_id ),
			'type'      => get_post_type( $post_id ),
			'permalink' => get_permalink( $post_id ),
			'updated'   => $changes,
			'message'   => 'Post updated successfully.',
		];
	}
}

/**
 * Move a post to the trash. Requires user confirmation.
 */
class DeletePost extends ToolInterface {

	public function get_name(): string {
		return 'delete_post';
	}

	public function get_description(): string {
		return 'Move a WordPress post or page to the trash. This is a destructive action that requires confirmation.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'delete';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the post to trash.',
				],
			],
			'required'   => [ 'post_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'delete_posts';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$post_id = absint( $input['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return [ 'error' => "Post not found: {$post_id}" ];
		}

		$title  = $post->post_title;
		$type   = $post->post_type;
		$result = wp_trash_post( $post_id );

		if ( ! $result ) {
			return [ 'error' => "Failed to trash post: {$post_id}" ];
		}

		return [
			'post_id' => $post_id,
			'title'   => $title,
			'type'    => $type,
			'message' => "Post \"{$title}\" moved to trash.",
		];
	}
}
