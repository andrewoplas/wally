# WordPress Security Patterns Reference

## Nonce System (CSRF Protection)

Nonces are single-use tokens that verify request intent. They are tied to the current user and action, valid for 24 hours (12-hour tick window).

### Creating Nonces

```php
// Hidden form field (outputs both nonce field and referer field)
wp_nonce_field( string $action = -1, string $name = '_wpnonce', bool $referer = true, bool $echo = true );

// URL with nonce appended
$url = wp_nonce_url( $base_url, string $action = -1, string $name = '_wpnonce' );

// Raw nonce value (for AJAX, REST, custom use)
$nonce = wp_create_nonce( string $action = -1 );
```

### Verifying Nonces

```php
// Verify from $_REQUEST (form or URL) -- dies on failure
check_admin_referer( string $action = -1, string $query_arg = '_wpnonce' );

// Verify AJAX nonce -- sends 403 on failure
check_ajax_referer( string $action = -1, string $query_arg = false, bool $stop = true );

// Manual verification -- returns 1 (0-12h old), 2 (12-24h old), or false
$valid = wp_verify_nonce( string $nonce, string $action = -1 );
```

### REST API Nonces

```php
// Create
wp_create_nonce( 'wp_rest' );  // Special action for REST API

// Verify: automatically checked when X-WP-Nonce header is sent
// Sets current user context for permission_callback
```

## Input Sanitization

Always sanitize input BEFORE processing or storing. Use the function matching the data type.

| Function | Use For | Example |
|---|---|---|
| `sanitize_text_field( $str )` | Single-line plain text | Form text input |
| `sanitize_textarea_field( $str )` | Multi-line plain text | Textarea input |
| `sanitize_email( $str )` | Email addresses | Returns `''` if invalid |
| `sanitize_title( $str )` | URL slugs | Converts to lowercase, strips special chars |
| `sanitize_file_name( $str )` | File names | Removes special chars, spaces |
| `sanitize_key( $str )` | Keys/identifiers | Lowercase alphanumeric + dashes + underscores |
| `sanitize_mime_type( $str )` | MIME types | e.g., `image/jpeg` |
| `sanitize_url( $str )` | URLs (was `esc_url_raw`) | For DB storage; use `esc_url()` for output |
| `absint( $val )` | Non-negative integers | Returns absolute integer |
| `intval( $val )` | Integers | Standard PHP cast |
| `wp_kses( $str, $allowed_html )` | HTML with whitelist | Strips disallowed tags/attributes |
| `wp_kses_post( $str )` | Post-level HTML | Allows tags permitted in post content |
| `wp_strip_all_tags( $str )` | Remove all HTML | Strips every tag |
| `sanitize_hex_color( $str )` | CSS hex colors | Returns `null` if invalid |

## Output Escaping

Always escape output IMMEDIATELY before rendering. Late escaping is the rule.

| Function | Context | Example |
|---|---|---|
| `esc_html( $str )` | Inside HTML tags | `<p><?php echo esc_html( $text ); ?></p>` |
| `esc_attr( $str )` | HTML attributes | `<input value="<?php echo esc_attr( $val ); ?>">` |
| `esc_url( $str )` | href, src attributes | `<a href="<?php echo esc_url( $url ); ?>">` |
| `esc_js( $str )` | Inline JavaScript | `<script>var x = '<?php echo esc_js( $val ); ?>';</script>` |
| `esc_textarea( $str )` | Inside `<textarea>` | `<textarea><?php echo esc_textarea( $content ); ?></textarea>` |
| `wp_kses( $html, $allowed )` | Allow specific HTML | Custom whitelist |
| `wp_kses_post( $html )` | Allow post HTML | Same as post content tags |

### Shortcut functions (escape + translate)

```php
esc_html__( 'Text', 'textdomain' );    // Return escaped translation
esc_html_e( 'Text', 'textdomain' );    // Echo escaped translation
esc_attr__( 'Text', 'textdomain' );    // Return escaped attr translation
esc_attr_e( 'Text', 'textdomain' );    // Echo escaped attr translation
```

## Capability Checks

```php
// Check current user
if ( current_user_can( 'manage_options' ) ) { /* admin-level */ }
if ( current_user_can( 'edit_posts' ) ) { /* editor-level */ }
if ( current_user_can( 'edit_post', $post_id ) ) { /* meta-cap for specific post */ }

// Check arbitrary user
if ( user_can( $user_id, 'delete_plugins' ) ) { /* ... */ }
```

### Key Capabilities

| Capability | Role | Typical Use |
|---|---|---|
| `manage_options` | Administrator | Settings pages, site config |
| `activate_plugins` | Administrator | Plugin management |
| `edit_others_posts` | Editor+ | Edit any post |
| `publish_posts` | Author+ | Publish own posts |
| `edit_posts` | Contributor+ | Create/edit own drafts |
| `upload_files` | Author+ | Media library |
| `edit_theme_options` | Administrator | Menus, widgets, customizer |
| `manage_categories` | Editor+ | Taxonomy management |
| `edit_users` | Administrator | User management |

## Security Validation Pattern (Complete Example)

```php
function handle_form_submission() {
    // 1. Verify nonce
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'my_action' ) ) {
        wp_die( 'Security check failed.' );
    }

    // 2. Check capabilities
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( 'Insufficient permissions.' );
    }

    // 3. Sanitize input
    $title = sanitize_text_field( $_POST['title'] ?? '' );
    $email = sanitize_email( $_POST['email'] ?? '' );
    $content = wp_kses_post( $_POST['content'] ?? '' );
    $count = absint( $_POST['count'] ?? 0 );

    // 4. Validate
    if ( empty( $title ) || ! is_email( $email ) ) {
        wp_die( 'Invalid input.' );
    }

    // 5. Process ...
}
```

## Gotchas

- Nonces are user-specific: a nonce created for user A will fail verification for user B.
- Nonces are NOT single-use in WordPress -- they remain valid for their lifetime window.
- `esc_url()` strips invalid protocols. Default allowed: `http`, `https`, `ftp`, `ftps`, `mailto`, `tel`.
- `wp_kses_post()` does NOT allow `<script>`, `<style>`, `<iframe>`, `<form>` tags.
- `sanitize_text_field()` strips tags and removes extra whitespace -- unsuitable for HTML content.
- **Validate, then sanitize, then escape.** Sanitize on input, escape on output.
- Never trust `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`, or `$_SERVER` values without sanitization.
- `check_admin_referer()` calls `wp_die()` on failure -- use `wp_verify_nonce()` for custom error handling.
