# General Forms Knowledge (WPForms, Ninja Forms, Fluent Forms)

## WPForms

### Storage
Forms stored as custom post type `wpforms` in `wp_posts`. Form structure is JSON in `post_content`. Entries in custom table `wp_wpforms_entries` with meta in `wp_wpforms_entry_meta`.

### API

```php
wpforms()->form->get( $form_id );           // Returns WP_Post object (form)
wpforms()->form->get( '', array( 'posts_per_page' => -1 ) ); // All forms
wpforms_get_form_fields( $form_id );        // Returns array of field objects
wpforms()->entry->get_entries( array( 'form_id' => $form_id ) ); // Get entries
wpforms()->entry->get( $entry_id );         // Single entry
wpforms()->entry->delete( $entry_id );      // Delete entry
```

### Field Types
text, textarea, email, url, number, phone, name (first/last), address, date-time, dropdown, checkbox, radio, file-upload, payment (Stripe), hidden, html, pagebreak, divider, rating, likert, net_promoter_score

### Key Hooks

```php
// After form submission processed
add_action( 'wpforms_process_complete', function( $fields, $entry, $form_data, $entry_id ) {}, 10, 4 );

// Before form renders on frontend
add_action( 'wpforms_frontend_output', function( $form_data, $form ) {}, 10, 2 );

// Custom validation
add_filter( 'wpforms_process_before_form_data', function( $form_data, $entry ) { return $form_data; }, 10, 2 );
```

### Settings
Stored in `wp_options` as `wpforms_settings`. License key in `wpforms_license`. Form-level settings embedded in the JSON form definition.

---

## Ninja Forms

### Storage
Forms in `wp_nf3_forms` table. Fields in `wp_nf3_fields`. Submissions in `wp_nf3_objects` with meta in `wp_nf3_objectmeta`. Actions in `wp_nf3_actions`.

### API

```php
Ninja_Forms()->form( $form_id )->get();                // Form object
Ninja_Forms()->form( $form_id )->get_fields();         // Array of field objects
Ninja_Forms()->form( $form_id )->get_subs();           // Submissions
Ninja_Forms()->form()->get_forms();                    // All forms
$field->get_setting( 'label' );                        // Field property
$field->get_setting( 'type' );                         // Field type
```

### Field Types
textbox, textarea, email, number, phone, listselect (dropdown), listmultiselect, listcheckbox, listradio, hidden, html, submit, date, firstname, lastname, address, city, zip, file_upload, starrating, password, confirm

### Key Hooks

```php
// After submission
add_action( 'ninja_forms_after_submission', function( $form_data ) {
    $form_id = $form_data['form_id'];
    $fields  = $form_data['fields']; // keyed by field ID, each has 'value', 'id', 'type'
} );

// Modify form display
add_filter( 'ninja_forms_display_form_settings', function( $settings, $form_id ) { return $settings; }, 10, 2 );
```

### Settings
Global settings in `wp_options` as `ninja_forms_settings`. Uses AJAX-driven frontend with Backbone.js models.

---

## Fluent Forms

### Storage
Forms in `fluentform_forms` table (JSON structure). Submissions in `fluentform_submissions` with meta in `fluentform_submission_meta`. Entry details in `fluentform_entry_details`.

### API

```php
// Via helper or global
$form = wpFluent()->table('fluentform_forms')->find( $form_id );
$submissions = wpFluent()->table('fluentform_submissions')->where('form_id', $form_id)->get();

// Manager classes
$formApi = fluentFormApi('forms');
$formApi->forms(['search' => '', 'status' => 'all']); // List forms
```

### REST API
Base: `/wp-json/fluentform/v1/`
- `GET /forms` -- list forms
- `GET /forms/{id}` -- single form
- `GET /forms/{id}/entries` -- form entries
- `POST /forms/{id}/entries` -- submit entry

### Key Hooks

```php
// After submission inserted
add_action( 'fluentform/submission_inserted', function( $submissionId, $formData, $form ) {}, 10, 3 );

// Before form render
add_filter( 'fluentform/rendering_form', function( $form ) { return $form; } );

// Validation
add_filter( 'fluentform/validation_errors', function( $errors, $formData, $form, $fields ) { return $errors; }, 10, 4 );
```

---

## Formidable Forms

### Storage
Forms stored in `{prefix}frm_forms` table. Fields in `{prefix}frm_fields`. Entries (items) in `{prefix}frm_items` with field values in `{prefix}frm_item_metas` (one row per field per entry). Additional tables: `{prefix}frm_item_entry_values` (Pro — for repeater/embedded form data).

### Database Tables
- `{prefix}frm_forms` — `id`, `form_key`, `name`, `description`, `status` (published/draft/trash), `options` (serialized — form settings, styling, actions), `created_at`
- `{prefix}frm_fields` — `id`, `field_key`, `name`, `description`, `type`, `default_value`, `options` (serialized — choices, validation rules), `field_order`, `form_id`, `required`
- `{prefix}frm_items` — `id`, `item_key`, `name`, `ip`, `form_id`, `post_id`, `user_id`, `parent_item_id`, `is_draft`, `created_at`, `updated_at`
- `{prefix}frm_item_metas` — `id`, `meta_value`, `field_id`, `item_id` (entry ID)

### API

```php
// Forms
FrmForm::getOne( $form_id );                      // Get form object
FrmForm::getAll( array( 'is_template' => 0 ) );   // Get all non-template forms
FrmForm::get_published_forms();                    // Published forms only

// Fields
FrmField::getAll( array( 'form_id' => $form_id ), 'field_order' );  // Form fields
FrmField::getOne( $field_id );                     // Single field

// Entries
FrmEntry::getOne( $entry_id, true );               // Entry with meta (true = include metas)
FrmEntry::getAll( array( 'form_id' => $form_id ) ); // All entries for form
FrmEntry::create( $values );                        // Create entry programmatically
FrmEntry::destroy( $entry_id );                     // Delete entry

// Entry meta (field values)
FrmEntryMeta::get_entry_meta_by_field( $entry_id, $field_id );  // Single field value
FrmEntryMeta::get_entry_metas_for_field( $field_id );           // All values for a field
```

### Shortcodes
- `[formidable id=X]` — display form by ID
- `[formidable key=form_key]` — display form by key
- `[display-frm-data id=X]` — display a View (Pro — renders entries on frontend)
- `[frm-field-value field_id=X entry=Y]` — display a single field value

### Views (Pro)
Views allow displaying form entries on the frontend as directories, tables, calendars, or custom layouts. Stored in `wp_posts` as custom post type `frm_display`. View configuration in `post_content` and `post_meta` with `frm_*` keys.

### Key Hooks

```php
// After entry is created
add_action( 'frm_after_create_entry', function( $entry_id, $form_id ) {}, 10, 2 );

// After entry is updated
add_action( 'frm_after_update_entry', function( $entry_id, $form_id ) {}, 10, 2 );

// Before entry is deleted
add_action( 'frm_before_destroy_entry', function( $entry_id ) {} );

// Field validation
add_filter( 'frm_validate_field_entry', function( $errors, $posted_field, $posted_value, $args ) {
    return $errors;
}, 10, 4 );

// Custom form validation
add_filter( 'frm_validate_entry', function( $errors, $values ) { return $errors; }, 10, 2 );

// Modify field value before save
add_filter( 'frm_pre_create_entry', function( $values ) { return $values; } );

// After form HTML renders
add_action( 'frm_after_form', function( $form ) {} );
```

### Application Framework (Pro)
Formidable Pro includes an application framework for building custom apps (project trackers, CRMs, directories). Applications combine forms, Views, and pages into cohesive solutions. Templates available for common use cases.

### Settings
- `frm_options` in wp_options — global settings (serialized FrmSettings object)
- `frm_db_version` — current database schema version
- Form-level settings stored in `frm_forms.options` column (serialized)

### Detection
```php
class_exists( 'FrmForm' )         // true if Formidable is active
defined( 'FORMIDABLE_VERSION' )   // alternative check
class_exists( 'FrmProDb' )        // true if Formidable Pro is active
```

---

## Forminator

### Storage
Forms, polls, and quizzes stored as custom post type `forminator_forms`, `forminator_polls`, `forminator_quizzes` in `wp_posts`. Form structure stored as JSON in post meta. Entries stored in custom database tables.

### Database Tables
- `{prefix}forminator_submissions` — `entry_id`, `entry_type` (form/poll/quiz), `form_id`, `is_spam`, `date_created_sql`, `date_updated_sql`
- `{prefix}forminator_submission_meta` — `meta_id`, `entry_id`, `meta_key` (field name/slug), `meta_value`, `date_created_sql`

### API

```php
// Get form model
$form = Forminator_Base_Form_Model::model()->load( $form_id );

// Get form fields
$fields = $form->get_fields();

// Get entries
$entries = Forminator_Form_Entry_Model::list_entries( $form_id );

// Get single entry
$entry = new Forminator_Form_Entry_Model( $entry_id );
$entry->get_meta( 'field_key' );

// Count entries
Forminator_Form_Entry_Model::count_entries( $form_id );

// Delete entry
Forminator_Form_Entry_Model::delete_by_entry( $entry_id );
```

### Shortcodes
- `[forminator_form id=X]` — display form
- `[forminator_poll id=X]` — display poll
- `[forminator_quiz id=X]` — display quiz

### Key Hooks

```php
// After form submission saved
add_action( 'forminator_form_after_save_entry', function( $form_id, $response ) {}, 10, 2 );

// After poll vote saved
add_action( 'forminator_poll_after_save_entry', function( $form_id ) {} );

// After quiz submission
add_action( 'forminator_quiz_after_save_entry', function( $form_id ) {} );

// Before form renders
add_filter( 'forminator_render_form_markup', function( $html, $form_id ) { return $html; }, 10, 2 );

// Custom validation
add_filter( 'forminator_custom_form_validate_field', function( $valid, $field, $data ) { return $valid; }, 10, 3 );

// Modify submitted data before save
add_filter( 'forminator_custom_form_submit_before_set_fields', function( $entry, $form_id, $field_data_array ) {
    return $entry;
}, 10, 3 );
```

### Features
- **Forms**: Drag-and-drop builder, conditional logic, multi-step forms, file uploads, calculations, Stripe/PayPal payments
- **Polls**: Single or multiple choice, customizable results display
- **Quizzes**: Knowledge quiz (right/wrong answers) or personality quiz (outcome-based)
- **Integrations**: Mailchimp, AWeber, Slack, Zapier, Google Sheets, HubSpot, ActiveCampaign, Trello, Webhook

### Settings
Stored in wp_options with `forminator_*` prefix:
- `forminator_pagination` — entries per page settings
- `forminator_captcha` — reCAPTCHA/hCaptcha settings
- `forminator_addon_*` — per-integration settings (API keys, tokens)
- `forminator_settings` — global plugin settings

### Detection
```php
defined( 'FORMINATOR_VERSION' )    // true if Forminator is active
class_exists( 'Forminator' )       // alternative check
```

---

## Common Patterns Across All Form Plugins

1. **Form structure** is stored as JSON or serialized data (post_content or custom table)
2. **Entries/submissions** are stored in custom database tables with meta tables for field values
3. **Hooks** follow a consistent pattern: pre-render, validation, post-submission
4. **Detection**: Check active plugins list or class/function existence:
   ```php
   class_exists('GFAPI')              // Gravity Forms
   class_exists('WPCF7')              // Contact Form 7
   function_exists('wpforms')         // WPForms
   class_exists('Ninja_Forms')        // Ninja Forms
   defined('FLUENTFORM')              // Fluent Forms
   ```
5. **All plugins** support notification emails, confirmation messages, and conditional logic
6. **Field values** are typically accessed by field ID (numeric) or field name (string) depending on the plugin
