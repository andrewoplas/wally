# WordPress AJAX

Complete reference for handling asynchronous requests in WordPress via admin-ajax.php, including the Heartbeat API.

---

## Overview

WordPress AJAX routes all requests through `wp-admin/admin-ajax.php`. This endpoint provides automatic access to all WordPress core functions, hooks, and user context. Plugins and themes register named **actions**, then JavaScript sends requests specifying which action to invoke.

---

## Server-Side: Action Hooks

### wp_ajax_{action} (logged-in users)

Fires when a logged-in user sends an AJAX request with the given action name.

```php
do_action( "wp_ajax_{$action}" )
```

- The dynamic `{action}` portion matches the `action` parameter sent from the client.
- Only fires for authenticated (logged-in) users.
- Introduced in WordPress 2.1.0.
- Source: `wp-admin/admin-ajax.php`.

```php
add_action( 'wp_ajax_my_custom_action', 'handle_my_custom_action' );

function handle_my_custom_action() {
    // Verify nonce
    check_ajax_referer( 'my_nonce_action', '_ajax_nonce' );

    // Process the request
    $result = do_something( sanitize_text_field( $_POST['data'] ) );

    // Send response
    wp_send_json_success( array( 'result' => $result ) );
}
```

### wp_ajax_nopriv_{action} (non-logged-in users)

Fires when a non-authenticated user sends an AJAX request with the given action name.

```php
do_action( "wp_ajax_nopriv_{$action}" )
```

- Same pattern as `wp_ajax_{action}` but for public/anonymous visitors.
- To support both logged-in and anonymous users, register both hooks:

```php
add_action( 'wp_ajax_search_posts', 'handle_search_posts' );
add_action( 'wp_ajax_nopriv_search_posts', 'handle_search_posts' );
```

---

## The admin-ajax.php Endpoint

All AJAX requests must target `wp-admin/admin-ajax.php`. The URL should never be hardcoded -- pass it to JavaScript via `wp_localize_script()` or `wp_add_inline_script()`.

```php
wp_localize_script( 'my-script', 'myAjax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' ),
    'nonce'    => wp_create_nonce( 'my_nonce_action' ),
) );
```

On the admin side, WordPress makes `ajaxurl` available globally as a JS variable (pointing to `admin-ajax.php`). On the frontend, you must pass it explicitly.

---

## Nonce Security

A nonce ("number used once") protects against CSRF attacks. WordPress nonces are valid for 12-24 hours (not truly single-use) unless the user logs out.

### Creating a nonce

```php
$nonce = wp_create_nonce( 'my_action_name' );
```

### Verifying a nonce

**Option A -- manual verification:**

```php
if ( ! wp_verify_nonce( $_POST['_ajax_nonce'], 'my_action_name' ) ) {
    wp_send_json_error( 'Invalid nonce', 403 );
}
```

**Option B -- `check_ajax_referer()` (recommended for AJAX):**

```php
check_ajax_referer( 'my_action_name', '_ajax_nonce' );
```

- First param: the nonce action name.
- Second param: the `$_REQUEST` key holding the nonce value.
- On failure, calls `wp_die()` with a `-1` response by default.

---

## Response Functions

### wp_send_json_success( $data, $status_code = null )

Sends a JSON response with `{ "success": true, "data": $data }`. Calls `wp_die()` internally.

```php
wp_send_json_success( array(
    'message' => 'Post created',
    'post_id' => 42,
) );
// Output: {"success":true,"data":{"message":"Post created","post_id":42}}
```

### wp_send_json_error( $data, $status_code = null )

Sends a JSON response with `{ "success": false, "data": $data }`. Calls `wp_die()` internally.

```php
wp_send_json_error( array(
    'message' => 'Permission denied',
), 403 );
// Output: {"success":false,"data":{"message":"Permission denied"}}
```

### wp_send_json( $response, $status_code = null, $flags = 0 )

Sends raw JSON response (no `success` wrapper). Sets `Content-Type: application/json` header and calls `wp_die()`.

```php
wp_send_json( array( 'status' => 'ok', 'count' => 10 ) );
```

### wp_die()

Terminates execution. Every AJAX handler MUST call `wp_die()` (or one of the `wp_send_json_*` functions which call it internally) to prevent trailing output.

---

## Client-Side: jQuery Pattern

The traditional WordPress AJAX pattern uses jQuery's `$.post()` or `$.ajax()`.

### Setup (PHP)

```php
function enqueue_ajax_script() {
    wp_enqueue_script(
        'my-ajax-script',
        plugins_url( 'js/ajax-handler.js', __FILE__ ),
        array( 'jquery' ),
        '1.0.0',
        true
    );
    wp_localize_script( 'my-ajax-script', 'myAjax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'my_nonce_action' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'enqueue_ajax_script' );
```

### Request (JavaScript)

```javascript
jQuery(document).ready(function($) {
    $('.my-button').on('click', function() {
        $.post(myAjax.ajax_url, {
            action: 'my_custom_action',
            _ajax_nonce: myAjax.nonce,
            post_id: $(this).data('id')
        }, function(response) {
            if (response.success) {
                console.log(response.data);
            } else {
                console.error(response.data);
            }
        });
    });
});
```

### Using $.ajax() for more control

```javascript
$.ajax({
    url: myAjax.ajax_url,
    type: 'POST',
    data: {
        action: 'my_custom_action',
        _ajax_nonce: myAjax.nonce,
        search_term: query
    },
    dataType: 'json',
    success: function(response) { /* ... */ },
    error: function(xhr, status, error) { /* ... */ }
});
```

---

## Client-Side: Fetch API Pattern (Modern)

```javascript
fetch(myAjax.ajax_url, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        action: 'my_custom_action',
        _ajax_nonce: myAjax.nonce,
        post_id: '42',
    }),
})
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log(data.data);
        } else {
            console.error(data.data);
        }
    })
    .catch(error => console.error('Request failed:', error));
```

---

## Client-Side: wp.apiFetch Pattern

WordPress ships `wp-api-fetch` (the `@wordpress/api-fetch` package) which wraps `fetch()` with automatic nonce handling for the REST API. This is the recommended approach for REST API requests but does NOT target admin-ajax.php.

```javascript
import apiFetch from '@wordpress/api-fetch';

// For REST API endpoints (not admin-ajax.php)
apiFetch({ path: '/wp/v2/posts', method: 'GET' })
    .then(posts => console.log(posts));

apiFetch({
    path: '/my-plugin/v1/action',
    method: 'POST',
    data: { key: 'value' },
}).then(response => console.log(response));
```

`wp.apiFetch` automatically includes the `X-WP-Nonce` header using the nonce generated by `wp_create_nonce( 'wp_rest' )`.

---

## admin-ajax.php vs REST API

| Feature | admin-ajax.php | REST API |
|---------|----------------|----------|
| URL pattern | `/wp-admin/admin-ajax.php` | `/wp-json/namespace/v1/route` |
| Auth | Cookie + nonce (manual) | Cookie + nonce or Application Passwords |
| Response format | Free-form (typically JSON) | JSON with schema |
| Discovery | Not discoverable | Self-documenting (OPTIONS, schema) |
| Caching | Not cacheable by default | HTTP cache headers supported |
| Permissions | Manual capability checks | Built-in `permission_callback` |
| External access | Difficult | Designed for external clients |
| Best for | Legacy code, simple one-off handlers | Public APIs, structured data, Gutenberg |

**Use admin-ajax.php when:**
- Maintaining existing AJAX handlers.
- Simple internal operations that do not need external access.
- Quick prototyping.

**Use REST API when:**
- Building new features (especially Gutenberg-related).
- External applications need access.
- You need structured, discoverable endpoints.
- You want proper HTTP caching.

---

## Heartbeat API

The Heartbeat API is a built-in server polling mechanism that enables near-real-time communication between the browser and WordPress at regular intervals.

### How it works

1. **Client sends data** -- JavaScript fires a `heartbeat-send` event; plugins can attach custom data.
2. **Server processes** -- PHP receives the data via the `heartbeat_received` filter and prepares a response.
3. **Client receives** -- JavaScript fires a `heartbeat-tick` event with the server's response.

The heartbeat runs on a configurable interval of 15-120 seconds.

### Sending data from JavaScript

```javascript
jQuery(document).on('heartbeat-send', function(event, data) {
    data.my_plugin_key = 'custom_value';
    data.my_plugin_check_id = 42;
});
```

### Processing on the server (PHP)

```php
add_filter( 'heartbeat_received', 'my_heartbeat_handler', 10, 2 );

function my_heartbeat_handler( $response, $data ) {
    if ( ! empty( $data['my_plugin_key'] ) ) {
        // Process the incoming data
        $response['my_plugin_response'] = 'processed: ' . sanitize_text_field( $data['my_plugin_key'] );
    }
    return $response;
}
```

### Receiving data in JavaScript

```javascript
jQuery(document).on('heartbeat-tick', function(event, data) {
    if (data.my_plugin_response) {
        console.log('Server said:', data.my_plugin_response);
    }
});
```

### Heartbeat hooks

| Hook / Event | Type | Context | Description |
|-------------|------|---------|-------------|
| `heartbeat-send` | JS event | Client | Attach data before sending to server. |
| `heartbeat-tick` | JS event | Client | Handle server response. |
| `heartbeat_received` | PHP filter | Server (logged-in frontend) | Process data from frontend heartbeat. |
| `heartbeat_nopriv_received` | PHP filter | Server (non-logged-in) | Process data from non-authenticated heartbeat. |
| `heartbeat_send` | PHP filter | Server | Modify data before sending to client. |
| `heartbeat_nopriv_send` | PHP filter | Server | Modify data for non-authenticated response. |
| `heartbeat_settings` | PHP filter | Server | Modify heartbeat settings (interval, etc.). |

### Controlling the tick interval

```php
add_filter( 'heartbeat_settings', function( $settings ) {
    $settings['interval'] = 30; // seconds (15-120)
    return $settings;
} );
```

### Disabling heartbeat

```php
add_action( 'init', function() {
    wp_deregister_script( 'heartbeat' );
} );
```

Or disable only on specific pages:

```php
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) {
        wp_deregister_script( 'heartbeat' );
    }
} );
```

### Common uses

- Post lock detection (preventing simultaneous editing).
- Autosave.
- Login expiration warnings.
- Real-time notification polling.
- Dashboard widget updates.

---

## Common Pitfalls & Debugging

### Response is `0`

The handler returned no data before `wp_die()`, or there was a PHP fatal error.

**Causes:**
- No action hook is registered for the given action name.
- The handler function does not exist or has a typo.
- A PHP fatal error occurs inside the handler.
- `wp_die()` was not called (use `wp_send_json_*` which calls it automatically).

**Fix:** Check `error_log` for fatal errors. Verify the action name matches between JS and PHP.

### Response is `403 Forbidden`

Nonce verification failed.

**Causes:**
- Nonce was not included in the request.
- Nonce expired (12-24 hour lifetime).
- Nonce action name mismatch between creation and verification.
- User session expired.

**Fix:** Ensure `wp_create_nonce()` action matches `wp_verify_nonce()` / `check_ajax_referer()` action. Check that the nonce field name in JS matches the expected `$_REQUEST` key.

### Response is `-1`

`check_ajax_referer()` failed and called `wp_die( -1 )`.

**Causes:**
- Same as 403 -- nonce mismatch or expiration.
- The second parameter of `check_ajax_referer()` does not match the key in `$_REQUEST`.

**Fix:** Double-check the nonce key name. Use browser dev tools Network tab to confirm the nonce value is being sent.

### Handler fires but returns empty response

**Causes:**
- Handler does not output anything or call a response function.
- Handler echoes output but does not call `wp_die()`.

**Fix:** Always end handlers with `wp_send_json_success()`, `wp_send_json_error()`, or `echo` + `wp_die()`.

### AJAX works for admins but not other roles

**Causes:**
- Missing `wp_ajax_nopriv_{action}` hook for non-logged-in users.
- Capability check inside the handler rejects the user role.

**Fix:** Register both `wp_ajax_` and `wp_ajax_nopriv_` hooks if needed. Review capability checks.

---

## Complete Example

### PHP (plugin file)

```php
// Enqueue script and pass AJAX data
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_script(
        'my-favorites',
        plugins_url( 'js/favorites.js', __FILE__ ),
        array( 'jquery' ),
        '1.0.0',
        array( 'in_footer' => true )
    );
    wp_localize_script( 'my-favorites', 'favoritesAjax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'toggle_favorite' ),
    ) );
} );

// Handle AJAX for logged-in users
add_action( 'wp_ajax_toggle_favorite', 'handle_toggle_favorite' );

function handle_toggle_favorite() {
    check_ajax_referer( 'toggle_favorite', 'nonce' );

    if ( ! current_user_can( 'read' ) ) {
        wp_send_json_error( 'Insufficient permissions', 403 );
    }

    $post_id = absint( $_POST['post_id'] );
    if ( ! $post_id || ! get_post( $post_id ) ) {
        wp_send_json_error( 'Invalid post ID' );
    }

    $user_id = get_current_user_id();
    $favorites = get_user_meta( $user_id, '_favorites', true ) ?: array();

    if ( in_array( $post_id, $favorites, true ) ) {
        $favorites = array_diff( $favorites, array( $post_id ) );
        $action = 'removed';
    } else {
        $favorites[] = $post_id;
        $action = 'added';
    }

    update_user_meta( $user_id, '_favorites', array_values( $favorites ) );
    wp_send_json_success( array( 'action' => $action, 'count' => count( $favorites ) ) );
}
```

### JavaScript (favorites.js)

```javascript
jQuery(function($) {
    $('.favorite-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);

        $.post(favoritesAjax.ajax_url, {
            action: 'toggle_favorite',
            nonce: favoritesAjax.nonce,
            post_id: $btn.data('post-id')
        }, function(response) {
            if (response.success) {
                $btn.toggleClass('is-active');
                $btn.find('.count').text(response.data.count);
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
});
```

---

## Quick Reference

| Task | Code |
|------|------|
| Register handler (logged-in) | `add_action( 'wp_ajax_{action}', 'callback' )` |
| Register handler (public) | `add_action( 'wp_ajax_nopriv_{action}', 'callback' )` |
| Get AJAX URL | `admin_url( 'admin-ajax.php' )` |
| Create nonce | `wp_create_nonce( 'action_name' )` |
| Verify nonce | `check_ajax_referer( 'action_name', 'nonce_key' )` |
| Success response | `wp_send_json_success( $data )` |
| Error response | `wp_send_json_error( $data, $status )` |
| Raw JSON response | `wp_send_json( $data )` |
| Terminate handler | `wp_die()` (automatic with `wp_send_json_*`) |
