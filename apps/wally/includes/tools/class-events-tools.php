<?php
namespace Wally\Tools;

/**
 * The Events Calendar plugin management tools.
 *
 * Tools: list_events, get_event, create_event, update_event, delete_event.
 * All tools require The Events Calendar to be active (function_exists('tribe_get_events')).
 *
 * Events are stored as 'tribe_events' custom post type.
 * Event meta keys: _EventStartDate, _EventEndDate, _EventAllDay, _EventVenueID, _EventCost, etc.
 */

/**
 * List events from The Events Calendar.
 */
class ListEvents extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'tribe_get_events' );
	}

	public function get_name(): string {
		return 'list_events';
	}

	public function get_description(): string {
		return 'List events from The Events Calendar plugin. Returns event ID, title, start/end date, venue, and cost. Supports filtering by date range and display type.';
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
				'start_date'   => [
					'type'        => 'string',
					'description' => 'Filter events starting from this date (YYYY-MM-DD). Default: today.',
				],
				'end_date'     => [
					'type'        => 'string',
					'description' => 'Filter events ending before this date (YYYY-MM-DD).',
				],
				'display'      => [
					'type'        => 'string',
					'description' => 'Display mode: "upcoming" (default), "past", "all".',
					'enum'        => [ 'upcoming', 'past', 'all' ],
					'default'     => 'upcoming',
				],
				'per_page'     => [
					'type'        => 'integer',
					'description' => 'Number of events to return (max 100).',
					'default'     => 20,
				],
				'page'         => [
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'edit_tribe_events';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );
		$display  = sanitize_key( $input['display'] ?? 'upcoming' );

		$args = [
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'eventDisplay'   => $display === 'all' ? 'list' : $display,
		];

		if ( ! empty( $input['start_date'] ) ) {
			$args['start_date'] = sanitize_text_field( $input['start_date'] );
		}
		if ( ! empty( $input['end_date'] ) ) {
			$args['end_date'] = sanitize_text_field( $input['end_date'] );
		}

		$events = tribe_get_events( $args );
		if ( ! is_array( $events ) ) {
			$events = [];
		}

		// Get total count.
		$count_args            = $args;
		$count_args['fields']  = 'ids';
		$count_args['paged']   = 1;
		$count_args['posts_per_page'] = -1;
		$all_ids = tribe_get_events( $count_args );
		$total   = is_array( $all_ids ) ? count( $all_ids ) : 0;

		$result = [];
		foreach ( $events as $event ) {
			$result[] = [
				'id'           => $event->ID,
				'title'        => $event->post_title,
				'start_date'   => get_post_meta( $event->ID, '_EventStartDate', true ),
				'end_date'     => get_post_meta( $event->ID, '_EventEndDate', true ),
				'all_day'      => get_post_meta( $event->ID, '_EventAllDay', true ) === 'yes',
				'cost'         => get_post_meta( $event->ID, '_EventCost', true ),
				'venue_id'     => (int) get_post_meta( $event->ID, '_EventVenueID', true ),
				'event_url'    => get_post_meta( $event->ID, '_EventURL', true ),
				'permalink'    => get_permalink( $event->ID ),
				'status'       => $event->post_status,
			];
		}

		return [
			'events'      => $result,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Get full details of a single event.
 */
class GetEvent extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'tribe_get_events' );
	}

	public function get_name(): string {
		return 'get_event';
	}

	public function get_description(): string {
		return 'Get full details of a single event from The Events Calendar by post ID, including dates, venue, organizer, cost, and description.';
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
				'event_id' => [
					'type'        => 'integer',
					'description' => 'The event post ID.',
				],
			],
			'required'   => [ 'event_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_tribe_events';
	}

	public function execute( array $input ): array {
		$event_id = absint( $input['event_id'] );
		$event    = get_post( $event_id );

		if ( ! $event || $event->post_type !== 'tribe_events' ) {
			return [ 'error' => "Event not found: {$event_id}" ];
		}

		$venue_id     = (int) get_post_meta( $event_id, '_EventVenueID', true );
		$organizer_id = (int) get_post_meta( $event_id, '_EventOrganizerID', true );

		$venue_name     = $venue_id ? get_the_title( $venue_id ) : '';
		$organizer_name = $organizer_id ? get_the_title( $organizer_id ) : '';

		return [
			'id'             => $event->ID,
			'title'          => $event->post_title,
			'description'    => $event->post_content,
			'status'         => $event->post_status,
			'permalink'      => get_permalink( $event_id ),
			'start_date'     => get_post_meta( $event_id, '_EventStartDate', true ),
			'end_date'       => get_post_meta( $event_id, '_EventEndDate', true ),
			'start_date_utc' => get_post_meta( $event_id, '_EventStartDateUTC', true ),
			'end_date_utc'   => get_post_meta( $event_id, '_EventEndDateUTC', true ),
			'all_day'        => get_post_meta( $event_id, '_EventAllDay', true ) === 'yes',
			'timezone'       => get_post_meta( $event_id, '_EventTimezone', true ),
			'cost'           => get_post_meta( $event_id, '_EventCost', true ),
			'currency'       => get_post_meta( $event_id, '_EventCurrencySymbol', true ),
			'event_url'      => get_post_meta( $event_id, '_EventURL', true ),
			'venue'          => [
				'id'   => $venue_id,
				'name' => $venue_name,
			],
			'organizer'      => [
				'id'   => $organizer_id,
				'name' => $organizer_name,
			],
		];
	}
}

/**
 * Create a new event in The Events Calendar.
 */
class CreateEvent extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'tribe_get_events' );
	}

	public function get_name(): string {
		return 'create_event';
	}

	public function get_description(): string {
		return 'Create a new event in The Events Calendar. Requires title, start date, and end date. Optionally accepts description, cost, event URL, and all-day flag.';
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
				'title'       => [
					'type'        => 'string',
					'description' => 'Event title.',
				],
				'start_date'  => [
					'type'        => 'string',
					'description' => 'Event start date and time in format "YYYY-MM-DD HH:MM:SS".',
				],
				'end_date'    => [
					'type'        => 'string',
					'description' => 'Event end date and time in format "YYYY-MM-DD HH:MM:SS".',
				],
				'description' => [
					'type'        => 'string',
					'description' => 'Event description (HTML allowed).',
				],
				'all_day'     => [
					'type'        => 'boolean',
					'description' => 'Whether this is an all-day event. Default: false.',
					'default'     => false,
				],
				'cost'        => [
					'type'        => 'string',
					'description' => 'Event cost/price (e.g., "25.00" or "Free").',
				],
				'event_url'   => [
					'type'        => 'string',
					'description' => 'External URL for more event information.',
				],
				'timezone'    => [
					'type'        => 'string',
					'description' => 'Timezone for the event (e.g., "America/New_York"). Defaults to site timezone.',
				],
			],
			'required'   => [ 'title', 'start_date', 'end_date' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_tribe_events';
	}

	public function execute( array $input ): array {
		$title      = sanitize_text_field( $input['title'] );
		$start_date = sanitize_text_field( $input['start_date'] );
		$end_date   = sanitize_text_field( $input['end_date'] );

		$post_data = [
			'post_title'   => $title,
			'post_content' => isset( $input['description'] ) ? wp_kses_post( $input['description'] ) : '',
			'post_status'  => 'publish',
			'post_type'    => 'tribe_events',
		];

		$event_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $event_id ) ) {
			return [ 'error' => $event_id->get_error_message() ];
		}

		update_post_meta( $event_id, '_EventStartDate', $start_date );
		update_post_meta( $event_id, '_EventEndDate', $end_date );
		update_post_meta( $event_id, '_EventAllDay', ! empty( $input['all_day'] ) ? 'yes' : 'no' );

		if ( ! empty( $input['cost'] ) ) {
			update_post_meta( $event_id, '_EventCost', sanitize_text_field( $input['cost'] ) );
		}
		if ( ! empty( $input['event_url'] ) ) {
			update_post_meta( $event_id, '_EventURL', esc_url_raw( $input['event_url'] ) );
		}
		if ( ! empty( $input['timezone'] ) ) {
			update_post_meta( $event_id, '_EventTimezone', sanitize_text_field( $input['timezone'] ) );
		}

		return [
			'id'         => $event_id,
			'title'      => $title,
			'start_date' => $start_date,
			'end_date'   => $end_date,
			'permalink'  => get_permalink( $event_id ),
			'message'    => "Event \"{$title}\" created successfully.",
		];
	}
}

/**
 * Update an existing event in The Events Calendar.
 */
class UpdateEvent extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'tribe_get_events' );
	}

	public function get_name(): string {
		return 'update_event';
	}

	public function get_description(): string {
		return 'Update an existing event in The Events Calendar. Provide event_id and any fields to change (title, dates, description, cost, URL).';
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
				'event_id'    => [
					'type'        => 'integer',
					'description' => 'The event post ID to update.',
				],
				'title'       => [
					'type'        => 'string',
					'description' => 'New event title.',
				],
				'start_date'  => [
					'type'        => 'string',
					'description' => 'New start date and time (YYYY-MM-DD HH:MM:SS).',
				],
				'end_date'    => [
					'type'        => 'string',
					'description' => 'New end date and time (YYYY-MM-DD HH:MM:SS).',
				],
				'description' => [
					'type'        => 'string',
					'description' => 'New event description (HTML allowed).',
				],
				'all_day'     => [
					'type'        => 'boolean',
					'description' => 'Set to true for all-day event, false to require specific times.',
				],
				'cost'        => [
					'type'        => 'string',
					'description' => 'New event cost.',
				],
				'event_url'   => [
					'type'        => 'string',
					'description' => 'New external URL for the event.',
				],
			],
			'required'   => [ 'event_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_tribe_events';
	}

	public function execute( array $input ): array {
		$event_id = absint( $input['event_id'] );
		$event    = get_post( $event_id );

		if ( ! $event || $event->post_type !== 'tribe_events' ) {
			return [ 'error' => "Event not found: {$event_id}" ];
		}

		$changes   = [];
		$post_data = [ 'ID' => $event_id ];

		if ( isset( $input['title'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $input['title'] );
			$changes[]               = 'title';
		}
		if ( isset( $input['description'] ) ) {
			$post_data['post_content'] = wp_kses_post( $input['description'] );
			$changes[]                 = 'description';
		}

		if ( count( $post_data ) > 1 ) {
			wp_update_post( $post_data );
		}

		if ( isset( $input['start_date'] ) ) {
			update_post_meta( $event_id, '_EventStartDate', sanitize_text_field( $input['start_date'] ) );
			$changes[] = 'start_date';
		}
		if ( isset( $input['end_date'] ) ) {
			update_post_meta( $event_id, '_EventEndDate', sanitize_text_field( $input['end_date'] ) );
			$changes[] = 'end_date';
		}
		if ( isset( $input['all_day'] ) ) {
			update_post_meta( $event_id, '_EventAllDay', $input['all_day'] ? 'yes' : 'no' );
			$changes[] = 'all_day';
		}
		if ( isset( $input['cost'] ) ) {
			update_post_meta( $event_id, '_EventCost', sanitize_text_field( $input['cost'] ) );
			$changes[] = 'cost';
		}
		if ( isset( $input['event_url'] ) ) {
			update_post_meta( $event_id, '_EventURL', esc_url_raw( $input['event_url'] ) );
			$changes[] = 'event_url';
		}

		if ( empty( $changes ) ) {
			return [ 'error' => 'No fields provided to update.' ];
		}

		return [
			'event_id' => $event_id,
			'updated'  => $changes,
			'message'  => "Event #{$event_id} updated successfully.",
		];
	}
}

/**
 * Delete an event from The Events Calendar.
 */
class DeleteEvent extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'tribe_get_events' );
	}

	public function get_name(): string {
		return 'delete_event';
	}

	public function get_description(): string {
		return 'Delete an event from The Events Calendar by post ID. This is a destructive action that requires confirmation.';
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
				'event_id' => [
					'type'        => 'integer',
					'description' => 'The event post ID to delete.',
				],
			],
			'required'   => [ 'event_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'delete_tribe_events';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$event_id = absint( $input['event_id'] );
		$event    = get_post( $event_id );

		if ( ! $event || $event->post_type !== 'tribe_events' ) {
			return [ 'error' => "Event not found: {$event_id}" ];
		}

		$title  = $event->post_title;
		$result = wp_delete_post( $event_id, true );

		if ( ! $result ) {
			return [ 'error' => "Failed to delete event #{$event_id}." ];
		}

		return [
			'event_id' => $event_id,
			'title'    => $title,
			'message'  => "Event \"{$title}\" (#{$event_id}) deleted successfully.",
		];
	}
}
