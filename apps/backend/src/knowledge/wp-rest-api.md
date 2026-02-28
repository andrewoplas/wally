# WordPress REST API Reference

## Default Endpoints (namespace: `wp/v2`)

| Endpoint | Methods | Description |
|---|---|---|
| `/wp/v2/posts` | GET, POST | List/create posts |
| `/wp/v2/posts/<id>` | GET, PUT, PATCH, DELETE | Single post CRUD |
| `/wp/v2/pages` | GET, POST | List/create pages |
| `/wp/v2/pages/<id>` | GET, PUT, PATCH, DELETE | Single page CRUD |
| `/wp/v2/media` | GET, POST | List/upload media |
| `/wp/v2/users` | GET, POST | List/create users |
| `/wp/v2/users/me` | GET, PUT, PATCH, DELETE | Current user |
| `/wp/v2/categories` | GET, POST | Taxonomies |
| `/wp/v2/tags` | GET, POST | Taxonomies |
| `/wp/v2/comments` | GET, POST | Comments |
| `/wp/v2/settings` | GET, PUT, PATCH | Site settings |
| `/wp/v2/plugins` | GET, POST | Plugin management |
| `/wp/v2/search` | GET | Search across types |
| `/wp/v2/types` | GET | Post types |
| `/wp/v2/statuses` | GET | Post statuses |
| `/wp/v2/taxonomies` | GET | Taxonomies list |

Custom post types registered with `'show_in_rest' => true` get `/wp/v2/{rest_base}` automatically.

## register_rest_route()

```php
register_rest_route( string $route_namespace, string $route, array $args, bool $override = false );
```

Must be called inside `rest_api_init` hook:

```php
add_action( 'rest_api_init', function() {
    register_rest_route( 'myplugin/v1', '/items', [
        [
            'methods'             => WP_REST_Server::READABLE,   // GET
            'callback'            => 'get_items_handler',
            'permission_callback' => 'check_permissions',
            'args'                => [
                'page' => [
                    'required'          => false,
                    'default'           => 1,
                    'type'              => 'integer',
                    'validate_callback' => function($v) { return is_numeric($v); },
                    'sanitize_callback' => 'absint',
                ],
            ],
        ],
        [
            'methods'             => WP_REST_Server::CREATABLE,  // POST
            'callback'            => 'create_item_handler',
            'permission_callback' => 'check_admin_permissions',
            'args'                => $schema_args,
        ],
        'schema' => 'get_item_schema',
    ]);

    // Route with URL parameter
    register_rest_route( 'myplugin/v1', '/items/(?P<id>[\d]+)', [
        'args' => [
            'id' => [ 'type' => 'integer', 'required' => true ],
        ],
        [
            'methods'  => WP_REST_Server::EDITABLE,    // POST, PUT, PATCH
            'callback' => 'update_item_handler',
            'permission_callback' => 'check_permissions',
        ],
        [
            'methods'  => WP_REST_Server::DELETABLE,   // DELETE
            'callback' => 'delete_item_handler',
            'permission_callback' => 'check_admin_permissions',
        ],
    ]);
});
```

**Method constants:** `WP_REST_Server::READABLE` (GET), `CREATABLE` (POST), `EDITABLE` (POST/PUT/PATCH), `DELETABLE` (DELETE), `ALLMETHODS`.

## WP_REST_Request

```php
function my_handler( WP_REST_Request $request ) {
    $id     = $request['id'];                        // URL param or body
    $id     = $request->get_param( 'id' );           // Same as above
    $params = $request->get_params();                 // All params merged
    $body   = $request->get_body();                   // Raw body string
    $json   = $request->get_json_params();            // Parsed JSON body
    $query  = $request->get_query_params();           // GET params only
    $body_p = $request->get_body_params();            // POST params only
    $files  = $request->get_file_params();            // $_FILES
    $header = $request->get_header( 'X-Custom' );    // Single header
    $method = $request->get_method();                 // HTTP method
    $route  = $request->get_route();                  // Matched route
}
```

## WP_REST_Response

```php
// Simple response
return new WP_REST_Response( $data, 200 );

// With headers
$response = new WP_REST_Response( $data, 200 );
$response->header( 'X-Total', $total );
$response->header( 'X-TotalPages', $pages );

// Error response
return new WP_Error( 'not_found', 'Item not found', [ 'status' => 404 ] );

// rest_ensure_response() wraps mixed data
return rest_ensure_response( $data );  // Converts arrays/WP_Error appropriately
```

## Authentication

**Nonce (cookie-based, for logged-in users):**
```php
// Localize nonce to JS
wp_localize_script( 'my-script', 'myApi', [
    'nonce'   => wp_create_nonce( 'wp_rest' ),
    'restUrl' => rest_url( 'myplugin/v1/' ),
]);

// JS fetch
fetch( myApi.restUrl + 'items', {
    headers: { 'X-WP-Nonce': myApi.nonce }
});
```

**Application Passwords (WP 5.6+):** Basic auth with `username:application_password`. For external/headless integrations.

## Permission Callbacks

```php
'permission_callback' => function( WP_REST_Request $request ) {
    return current_user_can( 'edit_posts' );       // Capability check
}
// Public endpoint:
'permission_callback' => '__return_true'
```

**IMPORTANT:** Every route MUST have a `permission_callback`. Omitting it triggers a `_doing_it_wrong` notice since WP 5.5.

## Gotchas

- `rest_api_init` fires on every REST request; use it (not `init`) for route registration.
- Internal requests: `rest_do_request( new WP_REST_Request(...) )` -- no HTTP overhead.
- Check `$response->is_error()` after `rest_do_request()`.
- Query param `_fields=id,title` limits response fields for performance.
- `_embed` query param inlines linked resources (author, featured media).
- Namespace format: `vendor/v1` -- always version your API.
