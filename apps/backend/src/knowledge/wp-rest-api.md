# WordPress REST API

## When to Use
- User asks about REST API endpoints, custom routes, or API authentication
- User asks how WordPress content is accessed programmatically
- User wants to understand how Wally's tool system interacts with WordPress

## Key Patterns

### Wally Uses the REST API Internally
Wally's WordPress plugin registers its own REST endpoints and executes tools via the REST API. All Wally tools (e.g., `list_posts`, `get_option`, `update_post`) are PHP handlers called through this system.

### Default Endpoints (wp/v2 namespace)
- `/wp/v2/posts`, `/wp/v2/pages` — content CRUD
- `/wp/v2/media` — media library
- `/wp/v2/users` — user management
- `/wp/v2/categories`, `/wp/v2/tags` — taxonomies
- `/wp/v2/comments` — comments
- `/wp/v2/settings` — site settings
- `/wp/v2/plugins` — plugin management
- `/wp/v2/search` — cross-type search

Custom post types registered with `'show_in_rest' => true` get `/wp/v2/{rest_base}` automatically.

### Authentication Methods
- **Nonce (cookie-based)**: For logged-in users within wp-admin. Uses `X-WP-Nonce` header with `wp_create_nonce('wp_rest')`.
- **Application Passwords (WP 5.6+)**: Basic auth for external/headless integrations.

### Key Rules for Custom Routes
- Register routes inside `rest_api_init` hook (not `init`)
- Every route MUST have a `permission_callback` (required since WP 5.5)
- Namespace format: `vendor/v1` — always version the API
- `_fields=id,title` limits response fields for performance
- `_embed` inlines linked resources (author, featured media)
- Internal requests: `rest_do_request()` — no HTTP overhead

### Response Patterns
- Success: `new WP_REST_Response($data, 200)`
- Error: `new WP_Error('code', 'message', ['status' => 404])`
- `rest_ensure_response()` wraps arrays/WP_Error appropriately

## Relevant Wally Tools
- `get_site_info` — returns site URL, WP version, permalink structure (REST API context)
- `list_posts` / `get_post` — access content that REST API exposes
- `list_plugins` — check plugin status (REST API endpoint exists for this)

## Important Notes
- Wally cannot register custom REST routes or modify existing ones — it uses the plugin's built-in endpoints
- REST API must be enabled for Wally to function — some security plugins disable it
- If user reports "REST API blocked," check security plugin settings via `get_option`
