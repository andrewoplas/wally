<?php
namespace Wally\Tools;

/**
 * TablePress tools for listing, reading, and updating tables.
 *
 * Tools: list_tables, get_table, update_table_cell.
 * Conditional: TablePress plugin must be active.
 */

/**
 * List all TablePress tables with their names and descriptions.
 */
class TablePressList extends ToolInterface {

	public function get_name(): string {
		return 'list_tables';
	}

	public function get_description(): string {
		return 'List all TablePress tables installed on the site. Returns each table\'s ID, name, description, and row/column count. Use this to discover available tables before reading or editing one.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return class_exists( 'TablePress' );
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
		// Load all table IDs; prime the post meta cache for efficient per-table loads.
		$table_ids = TablePress::$model_table->load_all( true );

		if ( empty( $table_ids ) ) {
			return [
				'success' => true,
				'data'    => [
					'tables' => [],
					'total'  => 0,
				],
			];
		}

		$tables = [];
		foreach ( $table_ids as $table_id ) {
			// Load without data and options — we only need the header fields.
			$table = TablePress::$model_table->load( $table_id, true, false );

			if ( is_wp_error( $table ) ) {
				continue;
			}

			$rows    = is_array( $table['data'] ) ? count( $table['data'] ) : 0;
			$columns = ( $rows > 0 && is_array( $table['data'][0] ) ) ? count( $table['data'][0] ) : 0;

			$tables[] = [
				'id'          => $table['id'],
				'name'        => $table['name'],
				'description' => $table['description'],
				'rows'        => $rows,
				'columns'     => $columns,
				'shortcode'   => '[table id=' . $table['id'] . ' /]',
			];
		}

		return [
			'success' => true,
			'data'    => [
				'tables' => $tables,
				'total'  => count( $tables ),
			],
		];
	}
}

/**
 * Get the full data and options of a specific TablePress table.
 */
class TablePressGet extends ToolInterface {

	public function get_name(): string {
		return 'get_table';
	}

	public function get_description(): string {
		return 'Get the full content and configuration of a specific TablePress table by its ID. Returns the table name, description, 2D cell data array (first row is usually the header), display options (sorting, filtering, pagination), and row/column visibility settings.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public static function can_register(): bool {
		return class_exists( 'TablePress' );
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'table_id' => [
					'type'        => 'string',
					'description' => 'The TablePress table ID (e.g., "1", "2"). Use list_tables to find available IDs.',
				],
			],
			'required'   => [ 'table_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $params ): array {
		$table_id = sanitize_key( $params['table_id'] );

		// Load full table data and options.
		$table = TablePress::$model_table->load( $table_id, true, true );

		if ( is_wp_error( $table ) ) {
			return [
				'success' => false,
				'error'   => 'Table not found: ' . $table->get_error_message(),
			];
		}

		$data    = $table['data'] ?? [];
		$rows    = count( $data );
		$columns = ( $rows > 0 && is_array( $data[0] ) ) ? count( $data[0] ) : 0;
		$options = $table['options'] ?? [];

		return [
			'success' => true,
			'data'    => [
				'id'            => $table['id'],
				'name'          => $table['name'],
				'description'   => $table['description'],
				'rows'          => $rows,
				'columns'       => $columns,
				'shortcode'     => '[table id=' . $table['id'] . ' /]',
				'data'          => $data,
				'options'       => [
					'table_head'              => ! empty( $options['table_head'] ),
					'table_foot'              => ! empty( $options['table_foot'] ),
					'alternating_row_colors'  => ! empty( $options['alternating_row_colors'] ),
					'row_hover'               => ! empty( $options['row_hover'] ),
					'datatables_sort'         => ! empty( $options['datatables_sort'] ),
					'datatables_filter'       => ! empty( $options['datatables_filter'] ),
					'datatables_paginate'     => ! empty( $options['datatables_paginate'] ),
					'datatables_paginate_entries' => (int) ( $options['datatables_paginate_entries'] ?? 10 ),
					'datatables_scrollx'      => ! empty( $options['datatables_scrollx'] ),
					'extra_css_classes'       => $options['extra_css_classes'] ?? '',
				],
			],
		];
	}
}

/**
 * Update the content of a single cell in a TablePress table.
 */
class TablePressUpdateCell extends ToolInterface {

	public function get_name(): string {
		return 'update_table_cell';
	}

	public function get_description(): string {
		return 'Update the content of a specific cell in a TablePress table. Specify the table ID, the 0-based row and column indices, and the new cell value. The first row (row_index 0) is typically the header row. Use get_table first to see the current data and identify the correct row/column indices.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'update';
	}

	public static function can_register(): bool {
		return class_exists( 'TablePress' );
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'table_id'  => [
					'type'        => 'string',
					'description' => 'The TablePress table ID (e.g., "1"). Use list_tables to find available IDs.',
				],
				'row_index' => [
					'type'        => 'integer',
					'description' => 'The 0-based row index of the cell to update. Row 0 is typically the header row.',
				],
				'col_index' => [
					'type'        => 'integer',
					'description' => 'The 0-based column index of the cell to update.',
				],
				'value'     => [
					'type'        => 'string',
					'description' => 'The new content for the cell. Can contain plain text or HTML.',
				],
			],
			'required'   => [ 'table_id', 'row_index', 'col_index', 'value' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_posts';
	}

	public function execute( array $params ): array {
		$table_id  = sanitize_key( $params['table_id'] );
		$row_index = (int) $params['row_index'];
		$col_index = (int) $params['col_index'];
		$value     = $params['value'];

		if ( $row_index < 0 || $col_index < 0 ) {
			return [ 'success' => false, 'error' => 'Row and column indices must be 0 or greater.' ];
		}

		// Load the full table with data and options.
		$table = TablePress::$model_table->load( $table_id, true, true );

		if ( is_wp_error( $table ) ) {
			return [
				'success' => false,
				'error'   => 'Table not found: ' . $table->get_error_message(),
			];
		}

		$data = $table['data'] ?? [];

		// Validate bounds.
		if ( ! isset( $data[ $row_index ] ) ) {
			return [
				'success' => false,
				'error'   => "Row index {$row_index} is out of bounds. Table has " . count( $data ) . ' row(s).',
			];
		}
		if ( ! isset( $data[ $row_index ][ $col_index ] ) ) {
			$col_count = count( $data[ $row_index ] );
			return [
				'success' => false,
				'error'   => "Column index {$col_index} is out of bounds. Table has {$col_count} column(s).",
			];
		}

		$old_value = $data[ $row_index ][ $col_index ];

		// Update the cell.
		$table['data'][ $row_index ][ $col_index ] = $value;

		// Save using TablePress model to ensure JSON encoding and index map are updated.
		$saved = TablePress::$model_table->save( $table );

		if ( is_wp_error( $saved ) ) {
			return [
				'success' => false,
				'error'   => 'Failed to save table: ' . $saved->get_error_message(),
			];
		}

		return [
			'success' => true,
			'data'    => [
				'table_id'  => $table_id,
				'row_index' => $row_index,
				'col_index' => $col_index,
				'old_value' => $old_value,
				'new_value' => $value,
				'message'   => "Cell [{$row_index}][{$col_index}] in table {$table_id} updated successfully.",
			],
		];
	}
}
