<?php
namespace Wally\Tools;

/**
 * Gravity Forms management tools.
 *
 * Tools: list_forms, get_form, list_entries, get_entry, delete_entry, update_entry_status.
 * All tools require Gravity Forms to be active (class_exists('GFAPI')).
 */

/**
 * List all Gravity Forms.
 */
class ListForms extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'GFAPI' );
	}

	public function get_name(): string {
		return 'list_forms';
	}

	public function get_description(): string {
		return 'List all Gravity Forms on the site. Returns form ID, title, entry count, active status, and creation date.';
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
				'active' => [
					'type'        => 'boolean',
					'description' => 'Filter by active status. True returns only active forms, false returns only inactive. Omit to return all forms.',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'gravityforms_view_entries';
	}

	public function execute( array $input ): array {
		// GFAPI::get_forms( $active, $trash, $sort_column, $sort_dir )
		// $active = null returns all; true returns active only; false returns inactive only.
		$active = isset( $input['active'] ) ? (bool) $input['active'] : null;
		$forms  = \GFAPI::get_forms( $active );

		if ( ! is_array( $forms ) ) {
			return [ 'forms' => [], 'total' => 0 ];
		}

		$result = [];
		foreach ( $forms as $form ) {
			$result[] = [
				'id'           => (int) $form['id'],
				'title'        => $form['title'],
				'description'  => $form['description'] ?? '',
				'is_active'    => (bool) $form['is_active'],
				'date_created' => $form['date_created'] ?? '',
				'field_count'  => count( $form['fields'] ?? [] ),
			];
		}

		return [
			'forms' => $result,
			'total' => count( $result ),
		];
	}
}

/**
 * Get full details of a single Gravity Form including its fields.
 */
class GetForm extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'GFAPI' );
	}

	public function get_name(): string {
		return 'get_form';
	}

	public function get_description(): string {
		return 'Get full details of a Gravity Form by ID, including all fields with their types, labels, and validation settings.';
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
					'description' => 'The Gravity Forms form ID.',
				],
			],
			'required'   => [ 'form_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'gravityforms_view_entries';
	}

	public function execute( array $input ): array {
		$form_id = absint( $input['form_id'] );
		$form    = \GFAPI::get_form( $form_id );

		if ( ! $form ) {
			return [ 'error' => "Form not found: {$form_id}" ];
		}

		// Summarize fields.
		$fields = [];
		foreach ( $form['fields'] as $field ) {
			$fields[] = [
				'id'          => $field->id,
				'type'        => $field->type,
				'label'       => $field->label,
				'is_required' => (bool) $field->isRequired,
				'visibility'  => $field->visibility ?? 'visible',
			];
		}

		return [
			'id'           => (int) $form['id'],
			'title'        => $form['title'],
			'description'  => $form['description'] ?? '',
			'is_active'    => (bool) $form['is_active'],
			'date_created' => $form['date_created'] ?? '',
			'fields'       => $fields,
		];
	}
}

/**
 * List entries for a Gravity Form.
 */
class ListEntries extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'GFAPI' );
	}

	public function get_name(): string {
		return 'list_entries';
	}

	public function get_description(): string {
		return 'List submission entries for a Gravity Form. Returns entry ID, submission date, status, and field values keyed by field ID.';
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
					'description' => 'The Gravity Forms form ID to get entries for.',
				],
				'status'   => [
					'type'        => 'string',
					'description' => 'Filter by entry status.',
					'enum'        => [ 'active', 'spam', 'trash' ],
					'default'     => 'active',
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
		return 'gravityforms_view_entries';
	}

	public function execute( array $input ): array {
		$form_id  = absint( $input['form_id'] );
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		$search_criteria = [
			'status' => sanitize_key( $input['status'] ?? 'active' ),
		];

		$sorting = [
			'key'        => 'date_created',
			'direction'  => 'DESC',
			'is_numeric' => false,
		];

		$paging = [
			'offset'    => ( $page - 1 ) * $per_page,
			'page_size' => $per_page,
		];

		$total_count = 0;
		$entries     = \GFAPI::get_entries( $form_id, $search_criteria, $sorting, $paging, $total_count );

		if ( is_wp_error( $entries ) ) {
			return [ 'error' => $entries->get_error_message() ];
		}

		$result = [];
		foreach ( $entries as $entry ) {
			// Collect field values (exclude internal meta keys starting with 'date_', 'status', etc.).
			$field_values = [];
			foreach ( $entry as $key => $value ) {
				if ( is_numeric( $key ) || strpos( $key, '.' ) !== false ) {
					$field_values[ $key ] = $value;
				}
			}

			$result[] = [
				'id'           => $entry['id'],
				'form_id'      => (int) $entry['form_id'],
				'date_created' => $entry['date_created'],
				'status'       => $entry['status'],
				'is_read'      => (bool) $entry['is_read'],
				'is_starred'   => (bool) $entry['is_starred'],
				'created_by'   => (int) $entry['created_by'],
				'ip'           => $entry['ip'],
				'source_url'   => $entry['source_url'],
				'field_values' => $field_values,
			];
		}

		return [
			'entries'     => $result,
			'total'       => (int) $total_count,
			'total_pages' => (int) ceil( $total_count / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Get a single Gravity Forms entry by ID.
 */
class GetEntry extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'GFAPI' );
	}

	public function get_name(): string {
		return 'get_entry';
	}

	public function get_description(): string {
		return 'Get a single Gravity Forms entry by entry ID. Returns all field values, submission date, status, and submitter information.';
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
				'entry_id' => [
					'type'        => 'integer',
					'description' => 'The Gravity Forms entry ID.',
				],
			],
			'required'   => [ 'entry_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'gravityforms_view_entries';
	}

	public function execute( array $input ): array {
		$entry_id = absint( $input['entry_id'] );
		$entry    = \GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			return [ 'error' => $entry->get_error_message() ];
		}

		if ( ! $entry ) {
			return [ 'error' => "Entry not found: {$entry_id}" ];
		}

		// Separate field values from entry meta.
		$field_values = [];
		$meta_keys    = [ 'id', 'form_id', 'date_created', 'date_updated', 'is_starred', 'is_read', 'ip', 'source_url', 'status', 'created_by', 'user_agent', 'payment_status', 'payment_date', 'payment_amount', 'payment_method', 'transaction_id', 'is_fulfilled', 'currency', 'transaction_type' ];

		foreach ( $entry as $key => $value ) {
			if ( ! in_array( $key, $meta_keys, true ) ) {
				$field_values[ $key ] = $value;
			}
		}

		return [
			'id'           => $entry['id'],
			'form_id'      => (int) $entry['form_id'],
			'date_created' => $entry['date_created'],
			'status'       => $entry['status'],
			'is_read'      => (bool) $entry['is_read'],
			'is_starred'   => (bool) $entry['is_starred'],
			'created_by'   => (int) $entry['created_by'],
			'ip'           => $entry['ip'],
			'source_url'   => $entry['source_url'],
			'field_values' => $field_values,
		];
	}
}

/**
 * Delete a Gravity Forms entry. Requires confirmation.
 */
class DeleteEntry extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'GFAPI' );
	}

	public function get_name(): string {
		return 'delete_entry';
	}

	public function get_description(): string {
		return 'Permanently delete a Gravity Forms entry by ID. This is a destructive action that requires confirmation.';
	}

	public function get_category(): string {
		return 'forms';
	}

	public function get_action(): string {
		return 'delete';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'entry_id' => [
					'type'        => 'integer',
					'description' => 'The Gravity Forms entry ID to delete.',
				],
			],
			'required'   => [ 'entry_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'gravityforms_delete_entries';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$entry_id = absint( $input['entry_id'] );

		// Verify entry exists first.
		$entry = \GFAPI::get_entry( $entry_id );
		if ( is_wp_error( $entry ) || ! $entry ) {
			return [ 'error' => "Entry not found: {$entry_id}" ];
		}

		$form_id = (int) $entry['form_id'];
		$result  = \GFAPI::delete_entry( $entry_id );

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}

		return [
			'entry_id' => $entry_id,
			'form_id'  => $form_id,
			'message'  => "Entry #{$entry_id} deleted successfully.",
		];
	}
}

/**
 * Update the status of a Gravity Forms entry.
 */
class UpdateEntryStatus extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'GFAPI' );
	}

	public function get_name(): string {
		return 'update_entry_status';
	}

	public function get_description(): string {
		return 'Update the status of a Gravity Forms entry: mark as active, spam, or trash. Also supports marking as read/starred.';
	}

	public function get_category(): string {
		return 'forms';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'entry_id'   => [
					'type'        => 'integer',
					'description' => 'The Gravity Forms entry ID to update.',
				],
				'status'     => [
					'type'        => 'string',
					'description' => 'New status: "active" to restore, "spam" to mark as spam, "trash" to move to trash.',
					'enum'        => [ 'active', 'spam', 'trash' ],
				],
				'is_read'    => [
					'type'        => 'boolean',
					'description' => 'Mark entry as read (true) or unread (false).',
				],
				'is_starred' => [
					'type'        => 'boolean',
					'description' => 'Mark entry as starred (true) or unstarred (false).',
				],
			],
			'required'   => [ 'entry_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'gravityforms_view_entries';
	}

	public function execute( array $input ): array {
		$entry_id = absint( $input['entry_id'] );
		$entry    = \GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) || ! $entry ) {
			return [ 'error' => "Entry not found: {$entry_id}" ];
		}

		$changes = [];

		if ( isset( $input['status'] ) ) {
			$entry['status'] = sanitize_key( $input['status'] );
			$changes[]       = 'status';
		}
		if ( isset( $input['is_read'] ) ) {
			$entry['is_read'] = $input['is_read'] ? '1' : '0';
			$changes[]        = 'is_read';
		}
		if ( isset( $input['is_starred'] ) ) {
			$entry['is_starred'] = $input['is_starred'] ? '1' : '0';
			$changes[]           = 'is_starred';
		}

		if ( empty( $changes ) ) {
			return [ 'error' => 'No fields provided to update.' ];
		}

		$result = \GFAPI::update_entry( $entry );

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}

		return [
			'entry_id' => $entry_id,
			'updated'  => $changes,
			'message'  => "Entry #{$entry_id} updated successfully.",
		];
	}
}
