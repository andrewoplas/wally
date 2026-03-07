# Tool & Feature Implementation Process

## For PHP Tool Tasks

### Step 1: Read the spec
Check the relevant phase doc for the tool schema, WordPress APIs, and implementation snippets:
- `docs/phase-2-strategic-tools.md`
- `docs/phase-3-plugin-expansions.md`
- `docs/phase-4-advanced-features.md`

### Step 2: Read an existing tool file
Match the exact style. Good reference files:
- `class-content-tools.php` — Basic CRUD, WP_Query, multi-tool file pattern
- `class-site-tools.php` — Reading site info, options API
- `class-woocommerce-tools.php` — Plugin-dependent, conditional registration
- If adding to an existing file, read THAT file first

### Step 3: Verify WordPress APIs
Use context7 (`resolve-library-id` → `query-docs`) or `WebSearch` to confirm:
- Exact function name (spelling, underscores)
- Parameters (required vs optional, types)
- Return type (array? WP_Post? custom class?)
- Free vs Pro availability

**NEVER guess function names. NEVER assume return types without verification.**

### Step 4: Implement
Create or extend the tool file. Each class needs:
- `namespace Wally\Tools;`
- `extends ToolInterface`
- Complete `get_name()`, `get_description()`, `get_category()`, `get_action()`, `get_parameters_schema()`, `get_required_capability()`
- `requires_confirmation()` for destructive actions
- `can_register()` for conditional tools
- Full `execute()` with error handling

### Step 5: Commit
`git add` and commit with descriptive message.

## For Knowledge File Tasks

1. Read the spec in `docs/phase-4-advanced-features.md`
2. Read existing knowledge files to match format (`gutenberg-blocks.md`, `general.md`)
3. Write concise, example-rich content directly useful as LLM context
4. Commit with descriptive message

## For Backend Code Tasks (TypeScript)

1. Read the spec and the existing file
2. Make minimal changes — add interfaces, patterns, or methods without refactoring
3. Commit with descriptive message

## For Cross-Cutting Tasks (Rollback/Undo)

1. Read all related files first — Database class, ToolExecutor, existing tool files
2. Implement in order — DB table → helper class → tools → integration
3. Trace the flow mentally: tool execute → snapshot save → undo tool → snapshot restore

## Patterns & Conventions

### Return Format
```php
// Success
return [ 'success' => true, 'data' => [ 'key' => 'value' ] ];
// Error
return [ 'success' => false, 'error' => 'Human-readable error message.' ];
```

### Conditional Registration
```php
public static function can_register(): bool {
    return class_exists( 'WooCommerce' );       // Class check
    return defined( 'WPSEO_VERSION' );           // Constant check
    return function_exists( 'wpforms' );         // Function check
    return ! wp_is_block_theme();                // Theme check
}
```

### Confirmation
```php
public function requires_confirmation(): bool {
    return true; // delete, bulk update, reset operations
}
```

### Capability Reference
| Capability | Who has it |
|-----------|-----------|
| `read` | Subscriber+ |
| `edit_posts` | Contributor+ |
| `publish_posts` | Author+ |
| `edit_others_posts` | Editor+ |
| `manage_options` | Administrator only |
| `activate_plugins` | Administrator only |
| `edit_theme_options` | Administrator only |
| `upload_files` | Author+ |
| `moderate_comments` | Editor+ |
| `list_users` | Administrator only |

### Tool File Structure
```php
<?php
namespace Wally\Tools;

class MyToolName extends ToolInterface {
    public function get_name(): string        { return 'my_tool_name'; }
    public function get_description(): string { return 'Detailed description for the LLM.'; }
    public function get_category(): string    { return 'content'; }
    public function get_action(): string      { return 'read'; }

    public function get_parameters_schema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'param_one' => [ 'type' => 'string', 'description' => 'Description for LLM.' ],
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
