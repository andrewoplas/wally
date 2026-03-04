<?php
namespace Wally\Tools;

/**
 * Regenerate Thumbnails plugin tools for thumbnail regeneration and status reporting.
 *
 * Tools: regenerate_thumbnails, get_regeneration_status.
 * Conditional: Regenerate Thumbnails plugin must be active.
 */

/**
 * Regenerate thumbnail sizes for one or more media library attachments.
 */
class RegenerateThumbnailsTrigger extends ToolInterface {

	public function get_name(): string {
		return 'regenerate_thumbnails';
	}

	public function get_description(): string {
		return 'Regenerate all registered image thumbnail sizes for specific media library attachments. Use this after changing theme image sizes, switching themes, or adding new add_image_size() definitions. Pass one or more attachment IDs (found in Media Library). Limited to 20 attachments per call to prevent timeout. Requires confirmation before executing.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'update';
	}

	public static function can_register(): bool {
		return class_exists( 'RegenerateThumbnails' );
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'attachment_ids' => [
					'type'        => 'array',
					'items'       => [ 'type' => 'integer' ],
					'description' => 'Array of media library attachment IDs to regenerate. Maximum 20 per call. Use list_media to find attachment IDs.',
				],
			],
			'required'   => [ 'attachment_ids' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $params ): array {
		// Ensure image processing functions are available.
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$ids = array_unique( array_map( 'absint', $params['attachment_ids'] ?? [] ) );
		$ids = array_filter( $ids ); // Remove zeros.

		if ( empty( $ids ) ) {
			return [ 'success' => false, 'error' => 'No valid attachment IDs provided.' ];
		}

		// Limit to 20 attachments per call to prevent request timeout.
		$ids = array_slice( $ids, 0, 20 );

		$results    = [];
		$successful = 0;
		$failed     = 0;

		foreach ( $ids as $attachment_id ) {
			$post = get_post( $attachment_id );

			if ( ! $post || 'attachment' !== $post->post_type ) {
				$results[] = [
					'attachment_id' => $attachment_id,
					'success'       => false,
					'error'         => 'Attachment not found or not a media file.',
				];
				$failed++;
				continue;
			}

			// Only process image attachments.
			if ( ! wp_attachment_is_image( $attachment_id ) ) {
				$results[] = [
					'attachment_id' => $attachment_id,
					'success'       => false,
					'error'         => "Attachment {$attachment_id} is not an image — skipping.",
				];
				$failed++;
				continue;
			}

			$file = get_attached_file( $attachment_id );
			if ( ! $file || ! file_exists( $file ) ) {
				$results[] = [
					'attachment_id' => $attachment_id,
					'success'       => false,
					'error'         => 'Image file not found on disk.',
				];
				$failed++;
				continue;
			}

			$metadata = wp_generate_attachment_metadata( $attachment_id, $file );

			if ( is_wp_error( $metadata ) ) {
				$results[] = [
					'attachment_id' => $attachment_id,
					'success'       => false,
					'error'         => $metadata->get_error_message(),
				];
				$failed++;
				continue;
			}

			if ( empty( $metadata ) ) {
				$results[] = [
					'attachment_id' => $attachment_id,
					'success'       => false,
					'error'         => 'Failed to generate metadata (check GD/Imagick support).',
				];
				$failed++;
				continue;
			}

			wp_update_attachment_metadata( $attachment_id, $metadata );

			$size_count = isset( $metadata['sizes'] ) ? count( $metadata['sizes'] ) : 0;
			$results[]  = [
				'attachment_id' => $attachment_id,
				'title'         => get_the_title( $attachment_id ),
				'success'       => true,
				'sizes_created' => $size_count,
			];
			$successful++;
		}

		return [
			'success' => true,
			'data'    => [
				'processed'  => count( $ids ),
				'successful' => $successful,
				'failed'     => $failed,
				'results'    => $results,
				'message'    => "Regenerated thumbnails for {$successful} of " . count( $ids ) . ' attachment(s).',
			],
		];
	}
}

/**
 * Get the status of registered image sizes and media library statistics.
 */
class RegenerateThumbnailsStatus extends ToolInterface {

	public function get_name(): string {
		return 'get_regeneration_status';
	}

	public function get_description(): string {
		return 'Get an overview of the media library and registered WordPress image sizes. Returns all registered thumbnail sizes (name, dimensions, crop mode) and the total count of image attachments. Use this to understand what sizes exist before deciding whether to run regenerate_thumbnails.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return class_exists( 'RegenerateThumbnails' );
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

		// Registered image sizes (WP 5.3+).
		$registered_sizes = [];
		if ( function_exists( 'wp_get_registered_image_subsizes' ) ) {
			$subsizes = wp_get_registered_image_subsizes();
			foreach ( $subsizes as $name => $size ) {
				$registered_sizes[] = [
					'name'   => $name,
					'width'  => $size['width'],
					'height' => $size['height'],
					'crop'   => $size['crop'],
				];
			}
		} else {
			// Fallback for WP < 5.3.
			foreach ( get_intermediate_image_sizes() as $name ) {
				$registered_sizes[] = [ 'name' => $name ];
			}
		}

		// Total image attachments.
		$total_images = (int) $wpdb->get_var(
			"SELECT COUNT(ID) FROM {$wpdb->posts}
			 WHERE post_type = 'attachment'
			 AND post_mime_type LIKE 'image/%'
			 AND post_status = 'inherit'"
		);

		// WordPress media size settings.
		$media_settings = [
			'thumbnail_size_w'   => (int) get_option( 'thumbnail_size_w', 150 ),
			'thumbnail_size_h'   => (int) get_option( 'thumbnail_size_h', 150 ),
			'medium_size_w'      => (int) get_option( 'medium_size_w', 300 ),
			'medium_size_h'      => (int) get_option( 'medium_size_h', 300 ),
			'large_size_w'       => (int) get_option( 'large_size_w', 1024 ),
			'large_size_h'       => (int) get_option( 'large_size_h', 1024 ),
			'uploads_use_yearmonth_folders' => (bool) get_option( 'uploads_use_yearmonth_folders', 1 ),
		];

		return [
			'success' => true,
			'data'    => [
				'total_images'       => $total_images,
				'registered_sizes'   => $registered_sizes,
				'size_count'         => count( $registered_sizes ),
				'media_settings'     => $media_settings,
				'upload_path'        => wp_get_upload_dir()['baseurl'],
				'note'               => 'Run regenerate_thumbnails with specific attachment IDs to rebuild sizes. Use list_media to find attachment IDs.',
			],
		];
	}
}
