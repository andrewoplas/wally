## The Events Calendar

### Post Types
- `tribe_events` — events
- `tribe_venue` — venues (linked to events)
- `tribe_organizer` — organizers (linked to events)

### Event Meta Keys
- `_EventStartDate`, `_EventEndDate` — local datetime (`Y-m-d H:i:s`)
- `_EventStartDateUTC`, `_EventEndDateUTC` — UTC datetime
- `_EventDuration` — duration in seconds
- `_EventAllDay` — "yes" if all-day event
- `_EventTimezone` — timezone string (e.g., "America/New_York")
- `_EventVenueID` — linked venue post ID
- `_EventOrganizerID` — linked organizer post ID
- `_EventURL` — external event URL
- `_EventCost`, `_EventCurrencySymbol`, `_EventCurrencyCode`, `_EventCurrencyPosition`
- `_EventShowMap`, `_EventShowMapLink` — map display toggles

### Venue Meta Keys
`_VenueAddress`, `_VenueCity`, `_VenueCountry`, `_VenueProvince`, `_VenueState`, `_VenueZip`, `_VenuePhone`, `_VenueURL`

### Organizer Meta Keys
`_OrganizerPhone`, `_OrganizerEmail`, `_OrganizerWebsite`

### Taxonomies
- `tribe_events_cat` — event categories
- Standard `post_tag` — event tags (shared with posts)

### Reading/Writing Events
```php
// Query events (date-aware WP_Query wrapper)
$events = tribe_get_events([
  'start_date'   => '2024-01-01',
  'end_date'     => '2024-12-31',
  'eventDisplay' => 'list', // past, upcoming, list, month, day
  'posts_per_page' => 10,
]);
// Get single event
$event = tribe_get_event($post_id);
// ORM builder (newer versions)
$events = tribe_events()->where('starts_after', 'now')->all();
// Write via postmeta
update_post_meta($post_id, '_EventStartDate', '2024-06-15 09:00:00');
update_post_meta($post_id, '_EventEndDate', '2024-06-15 17:00:00');
update_post_meta($post_id, '_EventVenueID', $venue_id);
```

### Settings in wp_options
- `tribe_events_calendar_options` (serialized) — main settings
  - `eventsSlug` — URL base (default: "events")
  - `singleEventSlug` — single event slug
  - `defaultCurrencySymbol` — default currency symbol
  - `stylesheet_option` — "full", "skeleton", or "tribe" CSS mode

### Template Overrides
Theme can override templates by copying from `plugins/the-events-calendar/src/views/` to `theme/tribe-events/`. Key templates: `default-template.php`, `list.php`, `month.php`, `single-event.php`.

### Hooks
- `tribe_events_before_html`, `tribe_events_after_html` — wrap event output
- `tribe_events_single_event_before_the_content` — before single event content
- `tribe_template_pre_html` — filter any tribe template before render
- `tribe_events_pre_get_posts` — modify event queries

### REST API
Base: `/wp-json/tribe/events/v1/`
- `GET /events` — list events (params: `start_date`, `end_date`, `categories`, `page`, `per_page`)
- `GET /events/{id}` — single event
- `GET /venues`, `GET /venues/{id}` — venues
- `GET /organizers`, `GET /organizers/{id}` — organizers

### iCal Export
- All events: `/events/?ical=1`
- Per-event iCal link available on single event pages
- Subscribe URL: `/events/?ical=1&tribe_display=list`

### Event Tickets (Separate Plugin)
Event Tickets / Event Tickets Plus adds: `tribe_tickets`, `tribe_rsvp` post types. Ticket meta prefixed `_tribe_tickets_*`. Attendee tracking via `{prefix}tec_attendees` table.

### Detection
```php
defined('TRIBE_EVENTS_FILE') // true if The Events Calendar is active
class_exists('Tribe__Events__Main') // alternative check
```
