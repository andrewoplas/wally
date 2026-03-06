<?php
namespace Wally\Tools;

/**
 * Media library management tools for WordPress.
 *
 * Tools: list_media, get_media, update_media, delete_media, upload_media_from_url, set_featured_image.
 * Uses WordPress core attachment functions.
 */

/**
 * List media library items with optional filters.
 */
class ListMedia extends ToolInterface {

	public function get_name(): string {
		return 'list_media';
	}

	public function get_description(): string {
		return 'List items in the WordPress media library with optional filters by MIME type, search, date range, or parent post. Returns attachment ID, title, URL, type, and date.';
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
				'mime_type'   => [
					'type'        => 'string',
					'description' => 'Filter by MIME type prefix (e.g., "image", "image/jpeg", "video", "application/pdf").',
				],
				'search'      => [
					'type'        => 'string',
					'description' => 'Search keyword to filter media by title or filename.',
				],
				'parent_id'   => [
					'type'        => 'integer',
					'description' => 'Filter attachments by parent post ID. Use 0 for unattached media.',
				],
				'date_after'  => [
					'type'        => 'string',
					'description' => 'Return media uploaded after this date (YYYY-MM-DD).',
				],
				'date_before' => [
					'type'        => 'string',
					'description' => 'Return media uploaded before this date (YYYY-MM-DD).',
				],
				'per_page'    => [
					'type'        => 'integer',
					'description' => 'Number of items to return (max 100).',
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
		return 'upload_files';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		$args = [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => sanitize_key( $input['orderby'] ?? 'date' ),
			'order'          => strtoupper( $input['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC',
		];

		if ( ! empty( $input['mime_type'] ) ) {
			$args['post_mime_type'] = sanitize_text_field( $input['mime_type'] );
		}

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = sanitize_text_field( $input['search'] );
		}

		if ( isset( $input['parent_id'] ) ) {
			$args['post_parent'] = absint( $input['parent_id'] );
		}

		if ( ! empty( $input['date_after'] ) || ! empty( $input['date_before'] ) ) {
			$date_query = [ 'inclusive' => true ];
			if ( ! empty( $input['date_after'] ) ) {
				$date_query['after'] = sanitize_text_field( $input['date_after'] );
			}
			if ( ! empty( $input['date_before'] ) ) {
				$date_query['before'] = sanitize_text_field( $input['date_before'] );
			}
			$args['date_query'] = [ $date_query ];
		}

		$query = new \WP_Query( $args );
		$items = [];

		foreach ( $query->posts as $post ) {
			$url      = wp_get_attachment_url( $post->ID );
			$alt      = get_post_meta( $post->ID, '_wp_attachment_image_alt', true );
			$metadata = wp_get_attachment_metadata( $post->ID );

			$items[] = [
				'id'        => $post->ID,
				'title'     => $post->post_title,
				'caption'   => $post->post_excerpt,
				'alt'       => $alt ?: '',
				'url'       => $url ?: '',
				'mime_type' => $post->post_mime_type,
				'date'      => $post->post_date,
				'width'     => $metadata['width'] ?? null,
				'height'    => $metadata['height'] ?? null,
				'file_size' => $metadata['filesize'] ?? null,
			];
		}

		return [
			'items'       => $items,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Get full details of a single media item.
 */
class GetMedia extends ToolInterface {

	public function get_name(): string {
		return 'get_media';
	}

	public function get_description(): string {
		return 'Get full details of a single media library item by attachment ID. Returns URL, alt text, caption, metadata (dimensions, file size), and available image sizes.';
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
				'attachment_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the media attachment to retrieve.',
				],
			],
			'required'   => [ 'attachment_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'upload_files';
	}

	public function execute( array $input ): array {
		$attachment_id = absint( $input['attachment_id'] );
		$post          = get_post( $attachment_id );

		if ( ! $post || $post->post_type !== 'attachment' ) {
			return [ 'error' => "Media attachment not found: {$attachment_id}" ];
		}

		$url      = wp_get_attachment_url( $attachment_id );
		$alt      = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$metadata = wp_get_attachment_metadata( $attachment_id );

		// Build sizes list for images.
		$sizes = [];
		if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size_name => $size_data ) {
				$size_url = wp_get_attachment_image_src( $attachment_id, $size_name );
				$sizes[ $size_name ] = [
					'width'  => $size_data['width'],
					'height' => $size_data['height'],
					'url'    => $size_url ? $size_url[0] : '',
				];
			}
		}

		return [
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'caption'     => $post->post_excerpt,
			'description' => $post->post_content,
			'alt'         => $alt ?: '',
			'url'         => $url ?: '',
			'mime_type'   => $post->post_mime_type,
			'date'        => $post->post_date,
			'parent_id'   => (int) $post->post_parent,
			'width'       => $metadata['width'] ?? null,
			'height'      => $metadata['height'] ?? null,
			'file_size'   => $metadata['filesize'] ?? null,
			'file'        => $metadata['file'] ?? null,
			'sizes'       => $sizes,
		];
	}
}

/**
 * Update a media attachment's metadata fields.
 */
class UpdateMedia extends ToolInterface {

	public function get_name(): string {
		return 'update_media';
	}

	public function get_description(): string {
		return 'Update a WordPress media attachment\'s title, caption, description, or alt text. Provide attachment_id and any fields to change.';
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
				'attachment_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the media attachment to update.',
				],
				'title'         => [
					'type'        => 'string',
					'description' => 'New title for the media item.',
				],
				'caption'       => [
					'type'        => 'string',
					'description' => 'New caption for the media item (displayed below images).',
				],
				'description'   => [
					'type'        => 'string',
					'description' => 'New description (long description) for the media item.',
				],
				'alt'           => [
					'type'        => 'string',
					'description' => 'New alt text for image accessibility.',
				],
			],
			'required'   => [ 'attachment_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'upload_files';
	}

	public function execute( array $input ): array {
		$attachment_id = absint( $input['attachment_id'] );
		$post          = get_post( $attachment_id );

		if ( ! $post || $post->post_type !== 'attachment' ) {
			return [ 'error' => "Media attachment not found: {$attachment_id}" ];
		}

		$post_data = [ 'ID' => $attachment_id ];
		$changes   = [];

		if ( isset( $input['title'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $input['title'] );
			$changes[] = 'title';
		}
		if ( isset( $input['caption'] ) ) {
			$post_data['post_excerpt'] = sanitize_textarea_field( $input['caption'] );
			$changes[] = 'caption';
		}
		if ( isset( $input['description'] ) ) {
			$post_data['post_content'] = wp_kses_post( $input['description'] );
			$changes[] = 'description';
		}

		if ( empty( $changes ) && ! isset( $input['alt'] ) ) {
			return [ 'error' => 'No fields provided to update.' ];
		}

		if ( count( $post_data ) > 1 ) {
			$result = wp_update_post( $post_data, true );
			if ( is_wp_error( $result ) ) {
				return [ 'error' => $result->get_error_message() ];
			}
		}

		if ( isset( $input['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $input['alt'] ) );
			$changes[] = 'alt';
		}

		$updated_post = get_post( $attachment_id );

		return [
			'attachment_id' => $attachment_id,
			'title'         => $updated_post->post_title,
			'url'           => wp_get_attachment_url( $attachment_id ),
			'updated'       => $changes,
			'message'       => 'Media attachment updated successfully.',
		];
	}
}

/**
 * Delete a media attachment from the WordPress library. Requires confirmation.
 */
class DeleteMedia extends ToolInterface {

	public function get_name(): string {
		return 'delete_media';
	}

	public function get_description(): string {
		return 'Permanently delete a WordPress media attachment and its generated image sizes from the library. This is a destructive action that requires confirmation.';
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
				'attachment_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the media attachment to permanently delete.',
				],
			],
			'required'   => [ 'attachment_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'delete_others_posts';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$attachment_id = absint( $input['attachment_id'] );
		$post          = get_post( $attachment_id );

		if ( ! $post || $post->post_type !== 'attachment' ) {
			return [ 'error' => "Media attachment not found: {$attachment_id}" ];
		}

		$title  = $post->post_title;
		$url    = wp_get_attachment_url( $attachment_id );
		$result = wp_delete_attachment( $attachment_id, true );

		if ( ! $result ) {
			return [ 'error' => "Failed to delete media attachment: {$attachment_id}" ];
		}

		return [
			'attachment_id' => $attachment_id,
			'title'         => $title,
			'url'           => $url,
			'message'       => "Media \"{$title}\" deleted permanently.",
		];
	}
}

/**
 * Download an image from a URL and add it to the WordPress media library.
 */
class UploadMediaFromUrl extends ToolInterface {

	public function get_name(): string {
		return 'upload_media_from_url';
	}

	public function get_description(): string {
		return 'Download an image or file from a URL and import it into the WordPress media library. Optionally sets alt text and a description. Returns the new attachment ID and URL. Use this when the user wants to add a remote image to their site, import a logo, or attach a file to a post.';
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
				'url'         => [
					'type'        => 'string',
					'description' => 'The full URL of the image or file to download and import.',
				],
				'alt_text'    => [
					'type'        => 'string',
					'description' => 'Alt text for the image (for accessibility and SEO).',
				],
				'description' => [
					'type'        => 'string',
					'description' => 'Optional description/caption for the media item.',
				],
				'title'       => [
					'type'        => 'string',
					'description' => 'Optional title for the media item. Defaults to the filename.',
				],
			],
			'required'   => [ 'url' ],
		];
	}

	public function get_required_capability(): string {
		return 'upload_files';
	}

	public function execute( array $input ): array {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$url = esc_url_raw( $input['url'] );

		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			return [ 'success' => false, 'error' => 'Failed to download file: ' . $tmp->get_error_message() ];
		}

		$file_array = [
			'name'     => basename( parse_url( $url, PHP_URL_PATH ) ) ?: 'upload',
			'tmp_name' => $tmp,
		];

		$description = isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : '';
		$attachment_id = media_handle_sideload( $file_array, 0, $description );

		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp );
			return [ 'success' => false, 'error' => 'Failed to sideload media: ' . $attachment_id->get_error_message() ];
		}

		if ( ! empty( $input['alt_text'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $input['alt_text'] ) );
		}

		if ( ! empty( $input['title'] ) ) {
			wp_update_post( [
				'ID'         => $attachment_id,
				'post_title' => sanitize_text_field( $input['title'] ),
			] );
		}

		return [
			'success' => true,
			'data'    => [
				'attachment_id' => $attachment_id,
				'url'           => wp_get_attachment_url( $attachment_id ),
				'message'       => 'Media imported successfully.',
			],
		];
	}
}

/**
 * Set the featured image (post thumbnail) for a post or page.
 */
class SetFeaturedImage extends ToolInterface {

	public function get_name(): string {
		return 'set_featured_image';
	}

	public function get_description(): string {
		return 'Set the featured image (post thumbnail) for a post, page, or custom post type. Requires the post ID and an existing media attachment ID. Use this after uploading an image with upload_media_from_url, or when the user wants to assign a featured image from the media library.';
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
				'post_id'       => [
					'type'        => 'integer',
					'description' => 'The ID of the post, page, or custom post type to set the featured image on.',
				],
				'attachment_id' => [
					'type'        => 'integer',
					'description' => 'The media attachment ID to use as the featured image.',
				],
			],
			'required'   => [ 'post_id', 'attachment_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $input ): array {
		$post_id       = absint( $input['post_id'] );
		$attachment_id = absint( $input['attachment_id'] );

		if ( ! get_post( $post_id ) ) {
			return [ 'success' => false, 'error' => "Post not found: {$post_id}" ];
		}

		$attachment = get_post( $attachment_id );
		if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
			return [ 'success' => false, 'error' => "Media attachment not found: {$attachment_id}" ];
		}

		$result = set_post_thumbnail( $post_id, $attachment_id );

		if ( false === $result ) {
			return [ 'success' => false, 'error' => 'Failed to set featured image.' ];
		}

		return [
			'success' => true,
			'data'    => [
				'post_id'       => $post_id,
				'attachment_id' => $attachment_id,
				'image_url'     => wp_get_attachment_url( $attachment_id ),
				'message'       => 'Featured image set successfully.',
			],
		];
	}
}
