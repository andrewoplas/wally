# Tool Creation Process Specification

## Overview

Each WordPress tool file follows a strict verification-then-implement process. This exists because we discovered a critical bug where ACF tools used `acf_get_post_types()` (returns slug strings) instead of `acf_get_acf_post_types()` (returns config arrays), causing all post type tools to return empty data.

## Step-by-Step Process

### Step 1: Read the knowledge file
Read the corresponding `.md` file in `apps/backend/src/knowledge/` to understand the plugin's capabilities.

### Step 2: Resolve the library in context7
Use `mcp__plugin_context7_context7__resolve-library-id` to find the plugin's documentation:
- WordPress core: search "wordpress"
- WooCommerce: search "woocommerce"
- Gravity Forms: search "gravity forms"
- etc.

### Step 3: Query API docs via context7
Use `mcp__plugin_context7_context7__query-docs` with the resolved library ID. Query specific topics:
- "list products API" for WooCommerce
- "get_users function" for WordPress users
- "GFAPI class methods" for Gravity Forms
- "nav menu functions" for WordPress menus

**Verify for each function:**
- Exact function name (spelling, underscores, camelCase)
- Parameters (required vs optional, types)
- Return type (array of objects? strings? WP_Post objects? custom class?)
- Free vs Pro availability
- Minimum version requirements

### Step 4: Cross-check with web search if context7 is insufficient
Use `WebSearch` to find:
- Official developer documentation
- WordPress Developer Reference (developer.wordpress.org)
- Plugin GitHub source code for function signatures

Example searches:
- `"wc_get_products" site:woocommerce.com OR site:github.com/woocommerce`
- `"GFAPI::get_forms" site:docs.gravityforms.com`
- `"wp_get_nav_menus" site:developer.wordpress.org`

### Step 5: Verify `can_register()` check
- Prefer `function_exists('specific_function_you_will_call')`
- Fall back to `class_exists('PluginMainClass')` when no single function to check
- Core WordPress functions (users, menus, media, comments): skip override — always available

### Step 6: Create the tool file
Write the PHP code using ONLY verified function names, parameters, and return types.

### Step 7: Self-review
Re-read the file and verify:
- Every function call matches Steps 3-4 verification
- Return value handling matches actual return type
- Error handling covers `false`, `null`, or `WP_Error` returns

## Rules
- **NEVER** guess function names from memory — always verify first
- **NEVER** assume return types without confirmation
- **NEVER** skip the context7/web search step — even for "obvious" functions
- **NEVER** copy function names from knowledge `.md` files without verification

## Tool File Structure

```php
<?php
namespace Wally\Tools;

class MyFeatureTools extends ToolInterface {
    public function get_name(): string        { return 'my_tool_name'; }
    public function get_description(): string { return 'Detailed description for the LLM.'; }
    public function get_category(): string    { return 'content'; }
    public function get_action(): string      { return 'read'; }

    public function get_parameters_schema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'param_one' => [ 'type' => 'string', 'description' => 'Description for the LLM.' ],
            ],
            'required' => [ 'param_one' ],
        ];
    }

    public function get_required_capability(): string { return 'edit_posts'; }
    public function requires_confirmation(): bool { return false; }

    public function execute( array $params ): array {
        return [ 'success' => true, 'data' => [] ];
    }
}
```

## Conditional Registration

```php
public static function can_register(): bool {
    return function_exists( 'specific_plugin_function' );
}
```

## Confirmation Rules
- `requires_confirmation()` = `true` for: delete, reset, bulk operations
- `requires_confirmation()` = `false` for: read-only, safe create/update

## Capability Reference

| Capability | Who has it |
|-----------|-----------|
| `read` | Subscriber+ |
| `edit_posts` | Contributor+ |
| `publish_posts` | Author+ |
| `edit_others_posts` | Editor+ |
| `manage_options` | Administrator only |
| `activate_plugins` | Administrator only |

## Return Format
Always return: `[ 'success' => true, 'data' => [...] ]` or `[ 'success' => false, 'error' => '...' ]`

## Reference Files
Study these existing tools for patterns:

| File | Good reference for |
|------|-------------------|
| `class-content-tools.php` | Basic CRUD, WP_Query |
| `class-taxonomy-tools.php` | Simple CRUD, parameter schemas |
| `class-site-tools.php` | Reading site info, options API |
| `class-plugin-tools.php` | Plugin management, confirmation |
| `class-acf-tools.php` | Plugin-dependent, conditional registration, complex schemas |
| `class-elementor-tools.php` | Third-party page builder, JSON data |
