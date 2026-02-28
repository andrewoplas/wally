# Gravity Forms

## GFAPI Class (Primary PHP API)

```php
GFAPI::get_form( $form_id );              // Returns form array or false
GFAPI::get_forms( $active, $trash, $sort_column, $sort_dir ); // Returns array of forms
GFAPI::get_entry( $entry_id );            // Returns entry array or WP_Error
GFAPI::get_entries( $form_id, $search_criteria, $sorting, $paging ); // Returns entries array
GFAPI::add_entry( $entry );               // Returns entry ID or WP_Error (does NOT trigger hooks)
GFAPI::update_entry( $entry );            // Returns true or WP_Error
GFAPI::delete_entry( $entry_id );         // Returns true or WP_Error
```

## Form Object Structure

Key properties: `id`, `title`, `description`, `is_active`, `date_created`, `is_trash`, `fields` (array of field objects), `notifications` (assoc array), `confirmations` (assoc array).

Each field object has: `id`, `type`, `label`, `isRequired`, `choices` (for select/radio/checkbox), `inputs` (for compound fields like name/address), `formId`, `pageNumber`, `visibility`.

## Field Types

Standard: `text`, `textarea`, `select`, `multiselect`, `checkbox`, `radio`, `number`, `hidden`, `html`, `section`, `page`
Advanced: `name`, `email`, `phone`, `address`, `website`, `date`, `time`, `fileupload`, `list`, `consent`, `captcha`
Post: `post_title`, `post_body`, `post_category`, `post_image`, `post_custom_field`
Pricing: `product`, `total`, `shipping`

## Entry Object

Properties: `id`, `form_id`, `date_created`, `is_starred`, `is_read`, `ip`, `source_url`, `status` (active/spam/trash), `created_by` (user ID).
Field values keyed by field ID: `$entry['1']` for simple fields, `$entry['2.3']` for sub-fields (e.g., name prefix = `X.2`, first = `X.3`, last = `X.6`; address street = `X.1`, city = `X.3`, state = `X.4`, zip = `X.5`, country = `X.6`).

## Key Hooks

```php
// After full submission (validation + notifications done)
add_action( 'gform_after_submission', function( $entry, $form ) {}, 10, 2 );
add_action( 'gform_after_submission_5', $callback, 10, 2 ); // form-specific (form ID 5)

// Modify form before rendering
add_filter( 'gform_pre_render', function( $form ) { return $form; } );

// Custom validation
add_filter( 'gform_validation', function( $validation_result ) { return $validation_result; } );

// Before submission processing
add_action( 'gform_pre_submission', function( $form ) {} );

// After entry is created in DB (before notifications)
add_action( 'gform_entry_created', function( $entry, $form ) {}, 10, 2 );

// Modify notifications
add_filter( 'gform_notification', function( $notification, $form, $entry ) { return $notification; }, 10, 3 );

// Modify confirmation
add_filter( 'gform_confirmation', function( $confirmation, $form, $entry ) { return $confirmation; }, 10, 3 );
```

## Notifications & Confirmations

Notifications are per-form, stored inside the form object. Default sends to `{admin_email}` on `form_submission`. Supports merge tags like `{all_fields}`, `{form_title}`, `{entry_id}`, `{field_id:1}`.

Confirmations support three types: `message` (inline text), `page` (redirect to WP page), `redirect` (external URL). Each has `id`, `name`, `isDefault`, `type`, `message`/`url`/`pageId`.

## Database Tables

- `wp_gf_form` -- form ID, title, active/trash status
- `wp_gf_form_meta` -- serialized form object (fields, notifications, confirmations)
- `wp_gf_entry` -- entry records with form_id, date, status, IP, user
- `wp_gf_entry_meta` -- field values stored as entry_id + meta_key (field ID) + meta_value

## Settings & Add-ons

Global settings stored in `wp_options` as `gravityformsaddon_*` keys. Common add-ons: PayPal, Stripe, Mailchimp, Zapier, HubSpot, Slack, Twilio. Add-on feeds are stored in `wp_gf_addon_feed` table.
