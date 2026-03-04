<?php
namespace Wally\Tools;

/**
 * Slider Revolution (RevSlider) tools for listing, reading, and updating sliders.
 *
 * Tools: list_sliders, get_slider, update_slider_status.
 * Conditional: RevSlider plugin must be active.
 * Data source: revslider_sliders and revslider_slides custom DB tables.
 */

/**
 * List all Slider Revolution sliders on the site.
 */
class RevSliderList extends ToolInterface {

	public function get_name(): string {
		return 'list_sliders';
	}

	public function get_description(): string {
		return 'List all Slider Revolution (RevSlider) sliders installed on the site. Returns each slider\'s ID, title, alias (used in shortcodes), type, and slide count. Use this to find slider IDs or aliases before getting details or editing a slider.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return class_exists( 'RevSlider' );
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => (object) [],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $params ): array {
		global $wpdb;

		$sliders_table = $wpdb->prefix . 'revslider_sliders';
		$slides_table  = $wpdb->prefix . 'revslider_slides';

		// Verify the table exists.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$sliders_table}'" ) ) {
			return [
				'success' => false,
				'error'   => 'RevSlider tables not found. The plugin may not have been activated.',
			];
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT s.id, s.title, s.alias, s.type,
			        COUNT(sl.id) AS slide_count
			 FROM {$sliders_table} s
			 LEFT JOIN {$slides_table} sl ON sl.slider_id = s.id
			 GROUP BY s.id
			 ORDER BY s.id ASC"
		);

		$sliders = [];
		foreach ( $rows as $row ) {
			$sliders[] = [
				'id'          => (int) $row->id,
				'title'       => $row->title,
				'alias'       => $row->alias,
				'type'        => $row->type,
				'slide_count' => (int) $row->slide_count,
				'shortcode'   => '[rev_slider alias="' . esc_attr( $row->alias ) . '"]',
			];
		}

		return [
			'success' => true,
			'data'    => [
				'sliders' => $sliders,
				'total'   => count( $sliders ),
			],
		];
	}
}

/**
 * Get detailed information about a specific Slider Revolution slider.
 */
class RevSliderGet extends ToolInterface {

	public function get_name(): string {
		return 'get_slider';
	}

	public function get_description(): string {
		return 'Get detailed information about a specific Slider Revolution (RevSlider) slider by its ID or alias. Returns the slider title, alias, type, shortcode, slide count, and key settings from its params (layout size, navigation, autoplay).';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return class_exists( 'RevSlider' );
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'slider_id' => [
					'type'        => 'integer',
					'description' => 'The numeric slider ID. Use list_sliders to find IDs.',
				],
				'alias'     => [
					'type'        => 'string',
					'description' => 'The slider alias (slug used in shortcodes). Use either slider_id or alias.',
				],
			],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $params ): array {
		global $wpdb;

		$sliders_table = $wpdb->prefix . 'revslider_sliders';
		$slides_table  = $wpdb->prefix . 'revslider_slides';

		if ( empty( $params['slider_id'] ) && empty( $params['alias'] ) ) {
			return [ 'success' => false, 'error' => 'Provide either slider_id or alias.' ];
		}

		if ( ! empty( $params['slider_id'] ) ) {
			$slider = $wpdb->get_row(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM {$sliders_table} WHERE id = %d",
					(int) $params['slider_id']
				)
			);
		} else {
			$slider = $wpdb->get_row(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM {$sliders_table} WHERE alias = %s",
					sanitize_key( $params['alias'] )
				)
			);
		}

		if ( ! $slider ) {
			return [ 'success' => false, 'error' => 'Slider not found.' ];
		}

		// Get slide count.
		$slide_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$slides_table} WHERE slider_id = %d",
				(int) $slider->id
			)
		);

		// Parse params JSON for key settings.
		$raw_params   = json_decode( $slider->params ?? '{}', true );
		$raw_params   = is_array( $raw_params ) ? $raw_params : [];

		// Extract commonly useful settings (keys may vary by RevSlider version).
		$useful_params = [];
		$keys_of_interest = [
			'width', 'height', 'responsiveLevels', 'gridwidth', 'gridheight',
			'autoplay', 'stopAtSlide', 'loop', 'shuffle', 'delay',
			'navigation', 'navigationType', 'touch', 'keyboard',
			'lazyType', 'shadow',
		];
		foreach ( $keys_of_interest as $key ) {
			if ( isset( $raw_params[ $key ] ) ) {
				$useful_params[ $key ] = $raw_params[ $key ];
			}
		}

		return [
			'success' => true,
			'data'    => [
				'id'          => (int) $slider->id,
				'title'       => $slider->title,
				'alias'       => $slider->alias,
				'type'        => $slider->type,
				'slide_count' => $slide_count,
				'shortcode'   => '[rev_slider alias="' . esc_attr( $slider->alias ) . '"]',
				'params'      => $useful_params,
			],
		];
	}
}

/**
 * Update a Slider Revolution slider's type/display mode.
 */
class RevSliderUpdateStatus extends ToolInterface {

	/** Valid RevSlider slider types. */
	private const VALID_TYPES = [ 'standard', 'hero', 'carousel', 'special' ];

	public function get_name(): string {
		return 'update_slider_status';
	}

	public function get_description(): string {
		return 'Update a Slider Revolution (RevSlider) slider\'s type/display mode. Valid types are: "standard" (traditional sliding), "hero" (single-slide animated), "carousel" (3D carousel), "special" (scroll-scene). Use list_sliders to find the slider ID first.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'update';
	}

	public static function can_register(): bool {
		return class_exists( 'RevSlider' );
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'slider_id' => [
					'type'        => 'integer',
					'description' => 'The numeric ID of the slider to update. Use list_sliders to find IDs.',
				],
				'type'      => [
					'type'        => 'string',
					'description' => 'The new slider type/mode. One of: "standard", "hero", "carousel", "special".',
					'enum'        => self::VALID_TYPES,
				],
			],
			'required'   => [ 'slider_id', 'type' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $params ): array {
		global $wpdb;

		$slider_id   = (int) $params['slider_id'];
		$new_type    = sanitize_key( $params['type'] );
		$table       = $wpdb->prefix . 'revslider_sliders';

		if ( ! in_array( $new_type, self::VALID_TYPES, true ) ) {
			return [
				'success' => false,
				'error'   => 'Invalid type. Must be one of: ' . implode( ', ', self::VALID_TYPES ),
			];
		}

		// Fetch current slider to verify it exists.
		$slider = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id, title, alias, type FROM {$table} WHERE id = %d",
				$slider_id
			)
		);

		if ( ! $slider ) {
			return [ 'success' => false, 'error' => "Slider with ID {$slider_id} not found." ];
		}

		if ( $slider->type === $new_type ) {
			return [
				'success' => true,
				'data'    => [
					'message' => "Slider \"{$slider->title}\" is already set to type \"{$new_type}\". No change made.",
				],
			];
		}

		$updated = $wpdb->update(
			$table,
			[ 'type' => $new_type ],
			[ 'id'   => $slider_id ],
			[ '%s' ],
			[ '%d' ]
		);

		if ( false === $updated ) {
			return [ 'success' => false, 'error' => 'Database update failed: ' . $wpdb->last_error ];
		}

		return [
			'success' => true,
			'data'    => [
				'slider_id' => $slider_id,
				'title'     => $slider->title,
				'alias'     => $slider->alias,
				'old_type'  => $slider->type,
				'new_type'  => $new_type,
				'message'   => "Slider \"{$slider->title}\" type updated from \"{$slider->type}\" to \"{$new_type}\".",
			],
		];
	}
}
