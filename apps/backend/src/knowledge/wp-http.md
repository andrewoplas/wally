# WordPress HTTP API

Complete reference for making outbound HTTP requests from WordPress using the HTTP API.

---

## Overview

The WordPress HTTP API provides a unified interface for making remote HTTP requests. It abstracts away the underlying PHP transport (cURL, streams, etc.) and provides consistent error handling, response parsing, and security features.

---

## Request Functions

### wp_remote_get()

Performs an HTTP GET request.

```php
wp_remote_get( string $url, array $args = array() ): array|WP_Error
```

**Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `$url` | `string` | Yes | -- | The URL to retrieve. |
| `$args` | `array` | No | `array()` | Request arguments (see `$args` reference below). |

**Returns:** Response array on success, `WP_Error` on failure.

```php
$response = wp_remote_get( 'https://api.example.com/posts' );

if ( is_wp_error( $response ) ) {
    error_log( $response->get_error_message() );
    return;
}

$body = wp_remote_retrieve_body( $response );
$data = json_decode( $body, true );
```

---

### wp_remote_post()

Performs an HTTP POST request.

```php
wp_remote_post( string $url, array $args = array() ): array|WP_Error
```

**Parameters:** Same as `wp_remote_get()`. The `method` arg defaults to `'POST'`.

```php
// Form-encoded POST
$response = wp_remote_post( 'https://api.example.com/submit', array(
    'body' => array(
        'name'  => 'John Doe',
        'email' => 'john@example.com',
    ),
) );

// JSON POST
$response = wp_remote_post( 'https://api.example.com/submit', array(
    'headers' => array( 'Content-Type' => 'application/json' ),
    'body'    => wp_json_encode( array(
        'name'  => 'John Doe',
        'email' => 'john@example.com',
    ) ),
) );
```

---

### wp_remote_head()

Performs an HTTP HEAD request. Useful for checking resource metadata (existence, last-modified date, content length) without downloading the body.

```php
wp_remote_head( string $url, array $args = array() ): array|WP_Error
```

```php
$response = wp_remote_head( 'https://api.example.com/resource' );
$last_modified  = wp_remote_retrieve_header( $response, 'last-modified' );
$content_length = wp_remote_retrieve_header( $response, 'content-length' );
```

---

### wp_remote_request()

Performs an HTTP request with any method. Use this for PUT, DELETE, PATCH, OPTIONS, or TRACE.

```php
wp_remote_request( string $url, array $args = array() ): array|WP_Error
```

```php
// PUT request
$response = wp_remote_request( 'https://api.example.com/posts/42', array(
    'method'  => 'PUT',
    'headers' => array( 'Content-Type' => 'application/json' ),
    'body'    => wp_json_encode( array( 'title' => 'Updated Title' ) ),
) );

// DELETE request
$response = wp_remote_request( 'https://api.example.com/posts/42', array(
    'method' => 'DELETE',
) );
```

---

## $args Reference

All request functions accept the same `$args` array. These are passed through to `WP_Http::request()`.

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `method` | `string` | `'GET'` | HTTP method: `GET`, `POST`, `HEAD`, `PUT`, `DELETE`, `PATCH`, `TRACE`, `OPTIONS`. Automatically set by the convenience functions (`wp_remote_get` = GET, `wp_remote_post` = POST, etc.). |
| `timeout` | `float` | `5` | Maximum time in seconds to wait for the request to complete. Increase for slow APIs. |
| `redirection` | `int` | `5` | Maximum number of redirects to follow. Set to `0` to disable redirect following. |
| `httpversion` | `string` | `'1.0'` | HTTP protocol version: `'1.0'` or `'1.1'`. |
| `user-agent` | `string` | `'WordPress/{version}'` | User-Agent header sent with the request. Some APIs require a specific user agent. |
| `reject_unsafe_urls` | `bool` | `false` | When `true`, validates URLs via `wp_http_validate_url()` to prevent SSRF. Automatically `true` for `wp_safe_remote_*` functions. |
| `blocking` | `bool` | `true` | Whether to wait for the response. Set to `false` for fire-and-forget requests (no response data returned). |
| `headers` | `string\|array` | `array()` | HTTP headers as an associative array: `array( 'Authorization' => 'Bearer TOKEN', 'Accept' => 'application/json' )`. |
| `cookies` | `array` | `array()` | Array of `WP_Http_Cookie` objects or cookie arrays to send with the request. |
| `body` | `string\|array` | `null` | Request body. Arrays are automatically form-encoded (`application/x-www-form-urlencoded`). Pass a string for JSON or other content types. |
| `compress` | `bool` | `false` | Whether to compress the request body when sending. |
| `decompress` | `bool` | `true` | Whether to automatically decompress gzip/deflate responses. |
| `sslverify` | `bool` | `true` | Whether to verify the SSL certificate of the remote host. **Never set to `false` in production** -- it disables certificate verification and opens the door to MITM attacks. |
| `sslcertificates` | `string` | Path to WP ca-bundle.crt | Path to the CA certificate bundle file for SSL verification. |
| `stream` | `bool` | `false` | Whether to stream the response body directly to a file instead of memory. |
| `filename` | `string\|null` | `null` | File path to write the streamed response to (requires `stream => true`). |
| `limit_response_size` | `int\|null` | `null` | Maximum number of bytes to read from the response. `null` = no limit. |

---

## Response Helpers

The HTTP API returns a raw response array. Use these helper functions to extract data safely.

### wp_remote_retrieve_body()

Extracts the response body as a string.

```php
wp_remote_retrieve_body( array|WP_Error $response ): string
```

Returns the body string, or an empty string if the response is a `WP_Error` or has no body.

```php
$response = wp_remote_get( 'https://api.example.com/data' );
$body = wp_remote_retrieve_body( $response );
$data = json_decode( $body, true );
```

### wp_remote_retrieve_response_code()

Extracts the HTTP status code.

```php
wp_remote_retrieve_response_code( array|WP_Error $response ): int|string
```

```php
$code = wp_remote_retrieve_response_code( $response );
if ( $code !== 200 ) {
    // Handle non-200 response
}
```

### wp_remote_retrieve_response_message()

Extracts the HTTP status message (e.g. "OK", "Not Found").

```php
wp_remote_retrieve_response_message( array|WP_Error $response ): string
```

### wp_remote_retrieve_headers()

Extracts all response headers as a `Requests_Utility_CaseInsensitiveDictionary` object (acts like an array).

```php
wp_remote_retrieve_headers( array|WP_Error $response ): array|Requests_Utility_CaseInsensitiveDictionary
```

```php
$headers = wp_remote_retrieve_headers( $response );
$content_type = $headers['content-type'];
```

### wp_remote_retrieve_header()

Extracts a single response header by name.

```php
wp_remote_retrieve_header( array|WP_Error $response, string $header ): array|string
```

```php
$etag         = wp_remote_retrieve_header( $response, 'etag' );
$rate_limit   = wp_remote_retrieve_header( $response, 'x-ratelimit-remaining' );
$cache_control = wp_remote_retrieve_header( $response, 'cache-control' );
```

---

## Response Array Structure

A successful response is an associative array:

```php
array(
    'headers'  => Requests_Utility_CaseInsensitiveDictionary,  // Response headers
    'body'     => '',                                           // Response body string
    'response' => array(
        'code'    => 200,   // HTTP status code (int)
        'message' => 'OK',  // HTTP status message (string)
    ),
    'cookies'  => array(),  // Array of WP_Http_Cookie objects
    'filename' => null,     // File path if stream was used
    'http_response' => WP_HTTP_Requests_Response,  // Full response object
)
```

---

## Error Handling

### is_wp_error()

Always check for errors before accessing response data.

```php
$response = wp_remote_get( $url );

if ( is_wp_error( $response ) ) {
    $error_message = $response->get_error_message();
    $error_code    = $response->get_error_code();
    error_log( "HTTP request failed: [{$error_code}] {$error_message}" );
    return false;
}

$code = wp_remote_retrieve_response_code( $response );
if ( $code < 200 || $code >= 300 ) {
    error_log( "HTTP request returned status {$code}" );
    return false;
}

$body = wp_remote_retrieve_body( $response );
```

### WP_Error object

`WP_Error` is returned when the request itself fails (network error, timeout, DNS failure, etc.). It is NOT returned for HTTP error status codes like 404 or 500 -- those are valid responses.

```php
$error = new WP_Error( 'http_request_failed', 'Connection timed out' );
$error->get_error_code();     // 'http_request_failed'
$error->get_error_message();  // 'Connection timed out'
$error->get_error_messages(); // array of all messages
$error->get_error_data();     // additional error data
```

---

## Safe Request Functions

For user-controlled or untrusted URLs, use the safe variants to prevent Server-Side Request Forgery (SSRF) attacks.

### wp_safe_remote_get()

```php
wp_safe_remote_get( string $url, array $args = array() ): array|WP_Error
```

### wp_safe_remote_post()

```php
wp_safe_remote_post( string $url, array $args = array() ): array|WP_Error
```

### wp_safe_remote_request()

```php
wp_safe_remote_request( string $url, array $args = array() ): array|WP_Error
```

### wp_safe_remote_head()

```php
wp_safe_remote_head( string $url, array $args = array() ): array|WP_Error
```

**Key differences from standard functions:**

- Automatically sets `reject_unsafe_urls` to `true`.
- Validates the URL and every redirect URL via `wp_http_validate_url()`.
- Only allows `http` and `https` protocols.
- Blocks requests to private/reserved IP ranges (prevents SSRF to internal services).

**When to use:**
- The URL comes from user input, a database field, or any untrusted source.
- The URL is constructed from user-submitted data.

**When standard functions are fine:**
- The URL is hardcoded or from a trusted configuration.
- You are calling a known, trusted API endpoint.

---

## Caching Responses with Transients

Remote API calls are expensive. Cache responses using WordPress transients to reduce load and improve performance.

### Basic caching pattern

```php
function get_api_data() {
    $cache_key = 'my_api_data';
    $data = get_transient( $cache_key );

    if ( false !== $data ) {
        return $data; // Return cached data
    }

    $response = wp_remote_get( 'https://api.example.com/data' );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    // Cache for 1 hour
    set_transient( $cache_key, $data, HOUR_IN_SECONDS );

    return $data;
}
```

### Cache with stale-while-revalidate pattern

```php
function get_api_data_with_fallback() {
    $cache_key     = 'my_api_data';
    $fallback_key  = 'my_api_data_fallback';

    $data = get_transient( $cache_key );
    if ( false !== $data ) {
        return $data;
    }

    // Cache expired -- try to refresh
    $response = wp_remote_get( 'https://api.example.com/data' );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        // API failed -- return stale data if available
        $stale = get_option( $fallback_key );
        return $stale ?: false;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    set_transient( $cache_key, $data, HOUR_IN_SECONDS );
    update_option( $fallback_key, $data ); // Long-term fallback

    return $data;
}
```

### WordPress time constants

| Constant | Value (seconds) |
|----------|----------------|
| `MINUTE_IN_SECONDS` | 60 |
| `HOUR_IN_SECONDS` | 3600 |
| `DAY_IN_SECONDS` | 86400 |
| `WEEK_IN_SECONDS` | 604800 |
| `MONTH_IN_SECONDS` | 2592000 |
| `YEAR_IN_SECONDS` | 31536000 |

### Clearing cached data

```php
delete_transient( 'my_api_data' );
```

---

## Filters

### pre_http_request

Short-circuits the HTTP request entirely. Return a non-false value to skip the actual request.

```php
add_filter( 'pre_http_request', function( $preempt, $parsed_args, $url ) {
    // Return cached response or mock data for testing
    if ( strpos( $url, 'api.example.com' ) !== false ) {
        return array(
            'response' => array( 'code' => 200, 'message' => 'OK' ),
            'body'     => wp_json_encode( array( 'mock' => true ) ),
            'headers'  => array(),
            'cookies'  => array(),
        );
    }
    return $preempt; // false = proceed with real request
}, 10, 3 );
```

**Use cases:**
- Unit testing (mock responses without network calls).
- Rate limiting (return cached response when limit is reached).
- Request blocking (prevent requests to certain domains).

### http_request_args

Modifies the request arguments before the request is sent.

```php
add_filter( 'http_request_args', function( $args, $url ) {
    // Add a custom header to all requests to a specific API
    if ( strpos( $url, 'api.example.com' ) !== false ) {
        $args['headers']['X-API-Key'] = 'my-secret-key';
        $args['timeout'] = 30;
    }
    return $args;
}, 10, 2 );
```

### http_response

Modifies the response after it is received.

```php
add_filter( 'http_response', function( $response, $parsed_args, $url ) {
    // Log all API responses
    if ( strpos( $url, 'api.example.com' ) !== false ) {
        $code = wp_remote_retrieve_response_code( $response );
        error_log( "API response from {$url}: HTTP {$code}" );
    }
    return $response;
}, 10, 3 );
```

### http_request_timeout

Modifies the default timeout for all requests.

```php
add_filter( 'http_request_timeout', function( $timeout ) {
    return 15; // 15 seconds
} );
```

---

## File Downloads

### download_url()

Downloads a remote file to a temporary local file.

```php
download_url( string $url, int $timeout = 300, bool $signature_verification = false ): string|WP_Error
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$url` | `string` | -- | The URL of the file to download. |
| `$timeout` | `int` | `300` | Timeout in seconds. |
| `$signature_verification` | `bool` | `false` | Whether to verify file signatures. |

**Returns:** Path to the temporary file (string) on success, or `WP_Error` on failure.

**Important:** The calling code MUST delete or move the temporary file. It is not cleaned up automatically.

**Requires:** `wp-admin/includes/file.php` must be included first.

```php
require_once ABSPATH . 'wp-admin/includes/file.php';

$tmp_file = download_url( 'https://example.com/data.csv' );

if ( is_wp_error( $tmp_file ) ) {
    error_log( 'Download failed: ' . $tmp_file->get_error_message() );
    return;
}

// Process the file
$contents = file_get_contents( $tmp_file );

// Clean up -- REQUIRED
wp_delete_file( $tmp_file );
```

### Streaming to a file

For large files, use the `stream` and `filename` args instead of `download_url()`:

```php
$response = wp_remote_get( 'https://example.com/large-file.zip', array(
    'timeout'  => 300,
    'stream'   => true,
    'filename' => '/tmp/large-file.zip',
) );

if ( is_wp_error( $response ) ) {
    error_log( $response->get_error_message() );
}
```

---

## WP_Http Class

All convenience functions (`wp_remote_get`, etc.) are wrappers around `WP_Http::request()`.

```php
$http = new WP_Http();
$response = $http->request( $url, $args );
```

The class handles:
- Transport selection (cURL, PHP streams).
- Proxy configuration.
- SSL certificate verification.
- Cookie handling.
- Redirect following.
- Response parsing.

In most cases, use the convenience functions rather than instantiating `WP_Http` directly.

---

## Common Patterns

### Authenticated API request

```php
$response = wp_remote_get( 'https://api.example.com/user', array(
    'headers' => array(
        'Authorization' => 'Bearer ' . $access_token,
        'Accept'        => 'application/json',
    ),
    'timeout' => 15,
) );
```

### Basic Authentication

```php
$response = wp_remote_get( 'https://api.example.com/data', array(
    'headers' => array(
        'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
    ),
) );
```

### POST with JSON body

```php
$response = wp_remote_post( 'https://api.example.com/webhook', array(
    'headers' => array(
        'Content-Type' => 'application/json',
    ),
    'body'    => wp_json_encode( array(
        'event' => 'order.created',
        'data'  => $order_data,
    ) ),
    'timeout' => 10,
) );
```

### Fire-and-forget (non-blocking)

```php
wp_remote_post( 'https://api.example.com/log', array(
    'blocking' => false,
    'body'     => array( 'event' => 'page_view', 'url' => home_url( $_SERVER['REQUEST_URI'] ) ),
) );
// Execution continues immediately -- no response is available
```

### HEAD request to check resource

```php
$response = wp_remote_head( 'https://cdn.example.com/asset.js' );

if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
    $size = wp_remote_retrieve_header( $response, 'content-length' );
    $type = wp_remote_retrieve_header( $response, 'content-type' );
}
```

### Respecting rate limits

```php
function api_request_with_rate_limit( $url ) {
    $response = wp_remote_get( $url );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $remaining = wp_remote_retrieve_header( $response, 'x-ratelimit-remaining' );
    $reset     = wp_remote_retrieve_header( $response, 'x-ratelimit-reset' );

    if ( (int) $remaining === 0 && $reset ) {
        // Store the reset time to avoid further requests
        set_transient( 'api_rate_limited', true, (int) $reset - time() );
    }

    return $response;
}
```

---

## Quick Reference

| Task | Function |
|------|----------|
| GET request | `wp_remote_get( $url, $args )` |
| POST request | `wp_remote_post( $url, $args )` |
| HEAD request | `wp_remote_head( $url, $args )` |
| Any HTTP method | `wp_remote_request( $url, $args )` |
| Safe GET (user URLs) | `wp_safe_remote_get( $url, $args )` |
| Safe POST (user URLs) | `wp_safe_remote_post( $url, $args )` |
| Get response body | `wp_remote_retrieve_body( $response )` |
| Get status code | `wp_remote_retrieve_response_code( $response )` |
| Get status message | `wp_remote_retrieve_response_message( $response )` |
| Get all headers | `wp_remote_retrieve_headers( $response )` |
| Get single header | `wp_remote_retrieve_header( $response, $name )` |
| Check for error | `is_wp_error( $response )` |
| Download file | `download_url( $url, $timeout )` |
| Cache response | `set_transient( $key, $data, $expiration )` |
| Get cached response | `get_transient( $key )` |
| Delete cached response | `delete_transient( $key )` |
