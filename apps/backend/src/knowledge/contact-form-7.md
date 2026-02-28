# Contact Form 7

## Form Storage

Forms are stored as the custom post type `wpcf7_contact_form` in `wp_posts`. Form markup, mail settings, and messages are stored as post meta. List all forms:

```php
$forms = WPCF7_ContactForm::find(); // Returns array of WPCF7_ContactForm objects
$form  = WPCF7_ContactForm::get_instance( $id ); // Single form by ID
```

## WPCF7_ContactForm Class

```php
$form = WPCF7_ContactForm::get_instance( 123 );
$form->id();                    // Post ID
$form->title();                 // Form title
$form->get_properties();        // Array: form, mail, mail_2, messages, additional_settings
$form->set_properties( $props );
$form->save();

// Properties structure:
// 'form'     => HTML form template with tags
// 'mail'     => array( subject, sender, body, recipient, additional_headers, attachments, use_html )
// 'mail_2'   => secondary mail (autoresponder), same structure
// 'messages' => array of validation/success messages keyed by message ID
```

## Shortcode

```
[contact-form-7 id="123" title="Contact Form"]
```

Also available as a Gutenberg block and via `wpcf7_contact_form_tag_func()`.

## Form Tags (Field Types)

`text`, `text*` (required), `email`, `email*`, `url`, `tel`, `textarea`, `date`, `number`, `range`, `drop-down menu` (select), `checkboxes`, `radio`, `acceptance`, `quiz`, `file` (upload), `submit`, `hidden`

Tag syntax in form template: `[text* your-name placeholder "Name"]`, `[email* your-email]`, `[select menu-item "Choice 1" "Choice 2"]`

## Mail Template

Uses `[field-name]` placeholders matching form tag names. Example:

```
Subject: New inquiry from [your-name]
Body: [your-message]
From: [your-name] <[your-email]>
```

### Special Mail Tags

`[_site_title]`, `[_site_url]`, `[_post_title]`, `[_post_url]`, `[_serial_number]`, `[_date]`, `[_time]`, `[_remote_ip]`, `[_user_agent]`, `[_url]`, `[_post_id]`, `[_post_name]`, `[_post_author]`, `[_post_author_email]`

## Key Hooks

```php
// Before mail is sent (access/modify submission data)
add_action( 'wpcf7_before_send_mail', function( $contact_form, &$abort, $submission ) {
    $data = $submission->get_posted_data();
}, 10, 3 );

// After mail sent successfully
add_action( 'wpcf7_mail_sent', function( $contact_form ) {} );

// When mail fails
add_action( 'wpcf7_mail_failed', function( $contact_form ) {} );

// Custom field validation (per field type)
add_filter( 'wpcf7_validate_text*', function( $result, $tag ) {
    $value = isset( $_POST[$tag->name] ) ? $_POST[$tag->name] : '';
    if ( strlen( $value ) < 3 ) {
        $result->invalidate( $tag, 'Too short.' );
    }
    return $result;
}, 10, 2 );

// Custom spam check
add_filter( 'wpcf7_spam', function( $spam ) { return $spam; } );
```

## Submission Object

```php
$submission = WPCF7_Submission::get_instance();
$submission->get_posted_data();           // All submitted data as array
$submission->get_posted_data( 'field' );  // Single field value
$submission->get_status();                // 'mail_sent', 'mail_failed', 'validation_failed', 'spam'
$submission->uploaded_files();            // Array of uploaded file paths
```

## REST API

Endpoint: `/wp-json/contact-form-7/v1/contact-forms/`
- `GET /contact-forms/` -- list all forms
- `GET /contact-forms/{id}` -- single form
- `POST /contact-forms/{id}/feedback` -- submit form (AJAX submission endpoint)

## AJAX Submission

Default behavior. Returns JSON with `status`, `message`, `posted_data_hash`, `invalid_fields`. Frontend JS (`wp-content/plugins/contact-form-7/includes/js/`) handles display.

## Spam Filtering

Built-in support for Akismet, reCAPTCHA v3, Cloudflare Turnstile. Custom spam filtering via `wpcf7_spam` filter. Disallowed list checked via `wpcf7_disallowed_list` filter.

## Configuration Storage

Global settings in `wp_options` key `wpcf7`. Per-form properties stored as post meta on the `wpcf7_contact_form` post. Flamingo plugin can log submissions to the database (CF7 does not store submissions by default).
