<?php
namespace Wally\Tools;

/**
 * WPForms management tools.
 *
 * Tools: list_wpforms, get_wpform, list_wpform_entries.
 * All tools require WPForms to be active (function_exists('wpforms')).
 * Note: Entries are only accessible in WPForms Pro (Lite does not store entries).
 */

/**
 * List all WPForms forms.
 */
class ListWPForms extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'wpforms' );
	}

	public function get_name(): string {
		return 'list_wpforms';
	}

	public function get_description(): string {
		return 'List all WPForms forms on the site. Returns form ID, title, field count, and creation date.';
	}

	public function get_category(): string {
		return 'forms';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'per_page' => [
					'type'        => 'integer',
					'description' => 'Number of forms to return (max 100).',
					'default'     => 20,
				],
				'page'     => [
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		// Get all forms as WP_Post objects.
		$all_forms = wpforms()->form->get(
			'',
			[
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		if ( ! is_array( $all_forms ) ) {
			$all_forms = [];
		}

		$total       = count( $all_forms );
		$paged_forms = array_slice( $all_forms, ( $page - 1 ) * $per_page, $per_page );

		$result = [];
		foreach ( $paged_forms as $form_post ) {
			// Form structure is JSON in post_content.
			$form_data = ! empty( $form_post->post_content ) ? json_decode( $form_post->post_content, true ) : [];
			$fields    = $form_data['fields'] ?? [];

			$result[] = [
				'id'           => $form_post->ID,
				'title'        => $form_post->post_title,
				'field_count'  => count( $fields ),
				'date_created' => $form_post->post_date,
				'shortcode'    => '[wpforms id="' . $form_post->ID . '"]',
			];
		}

		return [
			'forms'       => $result,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Get full details of a single WPForms form including its fields.
 */
class GetWPForm extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'wpforms' );
	}

	public function get_name(): string {
		return 'get_wpform';
	}

	public function get_description(): string {
		return 'Get full details of a WPForms form by ID, including all fields with their types, labels, and whether they are required.';
	}

	public function get_category(): string {
		return 'forms';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id' => [
					'type'        => 'integer',
					'description' => 'The WPForms form ID (post ID).',
				],
			],
			'required'   => [ 'form_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$form_id  = absint( $input['form_id'] );
		$form_post = wpforms()->form->get( $form_id );

		if ( ! $form_post ) {
			return [ 'error' => "WPForms form not found: {$form_id}" ];
		}

		$form_data = ! empty( $form_post->post_content ) ? json_decode( $form_post->post_content, true ) : [];
		$fields    = $form_data['fields'] ?? [];

		$field_list = [];
		foreach ( $fields as $field ) {
			$field_list[] = [
				'id'          => $field['id'] ?? '',
				'type'        => $field['type'] ?? '',
				'label'       => $field['label'] ?? '',
				'required'    => ! empty( $field['required'] ),
				'description' => $field['description'] ?? '',
			];
		}

		return [
			'id'           => $form_post->ID,
			'title'        => $form_post->post_title,
			'date_created' => $form_post->post_date,
			'shortcode'    => '[wpforms id="' . $form_post->ID . '"]',
			'fields'       => $field_list,
			'field_count'  => count( $field_list ),
		];
	}
}

/**
 * List entries for a WPForms form.
 */
class ListWPFormEntries extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'wpforms' ) && method_exists( wpforms(), 'entry' );
	}

	public function get_name(): string {
		return 'list_wpform_entries';
	}

	public function get_description(): string {
		return 'List form submission entries for a WPForms form. Returns entry ID, date, status, and field values. Note: Requires WPForms Pro or higher tier.';
	}

	public function get_category(): string {
		return 'forms';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id'  => [
					'type'        => 'integer',
					'description' => 'The WPForms form ID to list entries for.',
				],
				'per_page' => [
					'type'        => 'integer',
					'description' => 'Number of entries to return (max 100).',
					'default'     => 20,
				],
				'page'     => [
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				],
			],
			'required'   => [ 'form_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		if ( ! method_exists( wpforms(), 'entry' ) ) {
			return [ 'error' => 'WPForms entry storage is not available. Entries require WPForms Pro or higher.' ];
		}

		$form_id  = absint( $input['form_id'] );
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		$entries = wpforms()->entry->get_entries(
			[
				'form_id'  => $form_id,
				'number'   => $per_page,
				'offset'   => ( $page - 1 ) * $per_page,
				'orderby'  => 'entry_id',
				'order'    => 'DESC',
			]
		);

		$total = (int) wpforms()->entry->get_entries(
			[
				'form_id' => $form_id,
				'count'   => true,
			]
		);

		$result = [];
		foreach ( $entries as $entry ) {
			$fields = ! empty( $entry->fields ) ? json_decode( $entry->fields, true ) : [];

			$field_values = [];
			if ( is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					$field_values[ $field['id'] ?? '' ] = [
						'label' => $field['name'] ?? '',
						'value' => $field['value'] ?? '',
					];
				}
			}

			$result[] = [
				'id'           => $entry->entry_id,
				'form_id'      => (int) $entry->form_id,
				'date_created' => $entry->date,
				'status'       => $entry->status,
				'ip_address'   => $entry->ip_address,
				'field_values' => $field_values,
			];
		}

		return [
			'entries'     => $result,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}
