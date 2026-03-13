# The Events Calendar

## When to Use
- User mentions The Events Calendar, events, venues, organizers, or event tickets
- User wants to create, list, or manage events on their site
- User asks about event categories, dates, or calendar settings

## Available Tools
- `list_plugins` — detect if The Events Calendar is active
- `list_posts` — list events, venues, or organizers by post type
- `get_post` — get event details including date/venue meta
- `create_post` — create a new event (basic fields; dates via meta)
- `update_post` — update event details and meta
- `delete_post` — delete an event (requires confirmation)
- `search_content` — search across event content
- `get_option` — read calendar settings

## Workflows

### Detect Plugin
1. Call `list_plugins`
2. Look for: `the-events-calendar`

### List Upcoming Events
1. Call `list_posts` with `post_type: 'tribe_events'`
2. Events include meta: `_EventStartDate`, `_EventEndDate`, `_EventVenueID`

### Get Event Details
1. Call `get_post` with the event ID
2. Key meta fields: `_EventStartDate`, `_EventEndDate`, `_EventAllDay`, `_EventVenueID`, `_EventOrganizerID`, `_EventURL`, `_EventCost`

### Create an Event
1. Call `create_post` with `post_type: 'tribe_events'`, `title`, `content` (description), `status: 'publish'`
2. Set dates via `meta`: `_EventStartDate: '2024-06-15 09:00:00'`, `_EventEndDate: '2024-06-15 17:00:00'`
3. Optionally set `_EventVenueID` and `_EventOrganizerID` (must be existing venue/organizer post IDs)
4. Tell user: "Event created. Verify the dates and venue in Events > Edit."

### Update Event Date/Time
1. Call `update_post` with the event ID and `meta: { _EventStartDate: 'YYYY-MM-DD HH:MM:SS', _EventEndDate: 'YYYY-MM-DD HH:MM:SS' }`

### List Venues
1. Call `list_posts` with `post_type: 'tribe_venue'`
2. Venue meta: `_VenueAddress`, `_VenueCity`, `_VenueState`, `_VenueCountry`

### List Organizers
1. Call `list_posts` with `post_type: 'tribe_organizer'`
2. Organizer meta: `_OrganizerPhone`, `_OrganizerEmail`, `_OrganizerWebsite`

### Create a Venue
1. Call `create_post` with `post_type: 'tribe_venue'`, `title` (venue name)
2. Set address via `meta`: `_VenueAddress`, `_VenueCity`, `_VenueState`, `_VenueCountry`, `_VenueZip`

### Read Calendar Settings
1. Call `get_option` with key `tribe_events_calendar_options`
2. Key settings: `eventsSlug` (URL base), `defaultCurrencySymbol`, `stylesheet_option`

## Important Notes
- Events use post type `tribe_events`; venues use `tribe_venue`; organizers use `tribe_organizer`
- Date format must be `Y-m-d H:i:s` (e.g., `2024-06-15 09:00:00`)
- Event categories use taxonomy `tribe_events_cat` — not standard WordPress categories
- Tickets (Event Tickets plugin) use separate post types — not manageable via standard tools
- For recurring events, ticket sales, or calendar display settings, guide user to Events admin
- The Events Calendar has its own REST API at `/wp-json/tribe/events/v1/` but Wally uses WordPress tools instead
