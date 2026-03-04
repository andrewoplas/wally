<?php
namespace Wally\Tools;

/**
 * Image optimization plugin tools for Smush and EWWW Image Optimizer.
 *
 * Tools: get_smush_stats, bulk_smush_status, get_ewww_stats.
 * All tools are read-only — no confirmation required.
 */

/**
 * Get overall Smush image optimization statistics.
 */
class SmushGetStats extends ToolInterface {

	public function get_name(): string {
		return 'get_smush_stats';
	}

	public function get_description(): string {
		return 'Get overall Smush image optimization statistics including total images optimized, bytes saved, and current Smush settings (auto-smush, lossy compression, lazy load, WebP). Useful for understanding the optimization state of the media library.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return class_exists( 'WP_Smush' );
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

		// Count total image attachments.
		$total_attachments = (int) $wpdb->get_var(
			"SELECT COUNT(ID) FROM {$wpdb->posts}
			 WHERE post_type = 'attachment'
			 AND post_mime_type LIKE 'image/%'
			 AND post_status = 'inherit'"
		);

		// Count smushed images (those with Smush stats postmeta).
		$smushed_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
				 WHERE meta_key = %s",
				'wp-smpro-smush-data'
			)
		);

		// Aggregate bytes saved from per-image meta (up to 2000 records).
		$meta_rows       = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta}
				 WHERE meta_key = %s
				 LIMIT 2000",
				'wp-smpro-smush-data'
			)
		);
		$total_bytes_saved = 0;
		$sample_size       = count( $meta_rows );
		foreach ( $meta_rows as $serialized ) {
			$data = maybe_unserialize( $serialized );
			if ( ! empty( $data['stats']['bytes'] ) ) {
				$total_bytes_saved += (int) $data['stats']['bytes'];
			}
		}

		// Read Smush settings.
		$settings    = get_option( 'wp-smush-settings', [] );
		$settings    = is_array( $settings ) ? $settings : [];

		return [
			'success' => true,
			'data'    => [
				'total_images'       => $total_attachments,
				'smushed_images'     => $smushed_count,
				'not_smushed'        => max( 0, $total_attachments - $smushed_count ),
				'bytes_saved'        => $total_bytes_saved,
				'bytes_saved_human'  => size_format( $total_bytes_saved ),
				'sample_size'        => $sample_size,
				'is_sample'          => $total_attachments > 2000,
				'settings'           => [
					'auto'        => ! empty( $settings['auto'] ),
					'lossy'       => ! empty( $settings['lossy'] ),
					'strip_exif'  => ! empty( $settings['strip_exif'] ),
					'lazy_load'   => ! empty( $settings['lazy_load'] ),
					'webp'        => ! empty( $settings['webp'] ),
					'backup'      => ! empty( $settings['backup'] ),
					'resize'      => ! empty( $settings['resize'] ),
				],
			],
		];
	}
}

/**
 * Get the status of unoptimized images and any active Smush bulk processing.
 */
class SmushBulkStatus extends ToolInterface {

	public function get_name(): string {
		return 'bulk_smush_status';
	}

	public function get_description(): string {
		return 'Get the bulk Smush optimization status: how many images are optimized, how many remain unoptimized, and whether a bulk smush process is currently active. Useful before starting or checking a bulk optimization run.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return class_exists( 'WP_Smush' );
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

		// Total image attachments.
		$total = (int) $wpdb->get_var(
			"SELECT COUNT(ID) FROM {$wpdb->posts}
			 WHERE post_type = 'attachment'
			 AND post_mime_type LIKE 'image/%'
			 AND post_status = 'inherit'"
		);

		// Smushed images count.
		$smushed = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
				 WHERE meta_key = %s",
				'wp-smpro-smush-data'
			)
		);

		$remaining = max( 0, $total - $smushed );

		// Check if a bulk process is currently in progress via transient.
		$bulk_in_progress = (bool) get_transient( 'wp_smushit_bulk_sent_count' );

		// Check CDN status if available.
		$cdn_status = get_option( 'wp-smush-cdn_status', false );

		return [
			'success' => true,
			'data'    => [
				'total_images'     => $total,
				'smushed'          => $smushed,
				'remaining'        => $remaining,
				'percent_complete' => $total > 0 ? round( ( $smushed / $total ) * 100, 1 ) : 0,
				'bulk_in_progress' => $bulk_in_progress,
				'cdn_active'       => ! empty( $cdn_status ) && ! empty( $cdn_status['plan_id'] ),
			],
		];
	}
}

/**
 * Get EWWW Image Optimizer statistics from its custom tracking table.
 */
class EwwwGetStats extends ToolInterface {

	public function get_name(): string {
		return 'get_ewww_stats';
	}

	public function get_description(): string {
		return 'Get EWWW Image Optimizer statistics: total files optimized, bytes saved, average compression ratio, and key settings (compression level, WebP conversion, lazy load, API key status). Reads from EWWW\'s custom ewwwio_images tracking table.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return defined( 'EWWW_IMAGE_OPTIMIZER_VERSION' );
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

		$table = $wpdb->prefix . 'ewwwio_images';

		// Verify the table exists before querying.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
		if ( ! $table_exists ) {
			return [
				'success' => false,
				'error'   => 'EWWW tracking table not found. The plugin may not have been fully activated.',
			];
		}

		// Aggregate stats from the ewwwio_images table.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$stats = $wpdb->get_row(
			"SELECT
				COUNT(*) AS total_files,
				SUM(orig_size) AS total_orig_size,
				SUM(image_size) AS total_optimized_size,
				SUM(orig_size - image_size) AS total_bytes_saved
			 FROM {$table}
			 WHERE image_size > 0 AND orig_size > 0"
		);

		$total_files     = (int) ( $stats->total_files ?? 0 );
		$total_orig      = (int) ( $stats->total_orig_size ?? 0 );
		$total_optimized = (int) ( $stats->total_optimized_size ?? 0 );
		$bytes_saved     = (int) ( $stats->total_bytes_saved ?? 0 );
		$avg_ratio       = $total_orig > 0 ? round( ( $bytes_saved / $total_orig ) * 100, 1 ) : 0;

		// Read key EWWW settings via the verified function.
		$jpg_level  = ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_level' );
		$png_level  = ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_level' );
		$webp       = ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' );
		$lazy_load  = ewww_image_optimizer_get_option( 'ewww_image_optimizer_lazy_load' );
		$api_key    = ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' );
		$resize     = ewww_image_optimizer_get_option( 'ewww_image_optimizer_resize_existing' );
		$max_width  = ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxmediawidth' );
		$max_height = ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxmediaheight' );

		// Map compression levels to human-readable labels.
		$level_labels = [
			10 => 'none',
			20 => 'lossless',
			30 => 'lossy',
			40 => 'maximum lossy',
		];

		return [
			'success' => true,
			'data'    => [
				'total_files_optimized' => $total_files,
				'original_size'         => $total_orig,
				'original_size_human'   => size_format( $total_orig ),
				'optimized_size'        => $total_optimized,
				'optimized_size_human'  => size_format( $total_optimized ),
				'bytes_saved'           => $bytes_saved,
				'bytes_saved_human'     => size_format( $bytes_saved ),
				'average_savings_pct'   => $avg_ratio,
				'settings'              => [
					'jpg_level'       => $level_labels[ (int) $jpg_level ] ?? (int) $jpg_level,
					'png_level'       => $level_labels[ (int) $png_level ] ?? (int) $png_level,
					'webp'            => (bool) $webp,
					'lazy_load'       => (bool) $lazy_load,
					'api_key_set'     => ! empty( $api_key ),
					'resize_existing' => (bool) $resize,
					'max_width'       => (int) $max_width,
					'max_height'      => (int) $max_height,
				],
			],
		];
	}
}
