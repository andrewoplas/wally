# Ralph Agent Configuration

## Prerequisites
- Node.js v22.22.0 (see `.nvmrc`)
- npm (lockfile: `package-lock.json`)
- PHP 8.0+ (WordPress plugin code)

## Build Instructions

```bash
# Install dependencies (from repo root)
npm install

# Build the WordPress plugin JS (from apps/wally/)
cd apps/wally && npm run build && cd ../..

# Build the backend
npx nx build backend

# Build all apps
npx nx run-many -t build
```

## Test Instructions

```bash
# Run backend unit tests
npx nx test backend

# Run all tests
npx nx run-many -t test
```

## Lint Instructions

```bash
# Lint backend
npx nx lint backend
```

## Project Structure

```
apps/
  wally/                    # WordPress plugin (PRIMARY TARGET)
    includes/
      tools/                # Tool PHP classes (one file per feature)
        class-tool-interface.php   # Abstract base class
        class-content-tools.php    # Posts CRUD
        class-taxonomy-tools.php   # Categories, tags
        class-site-tools.php       # Site info, options
        class-plugin-tools.php     # Plugin management
        class-search-tools.php     # Search & replace
        class-elementor-tools.php  # Elementor tools
        class-acf-tools.php        # ACF tools
      class-tool-executor.php      # Registry: registers, validates, executes
      class-plugin.php             # Auto-discovers tool classes
      class-rest-controller.php    # Sends schemas to backend
    wally.php                      # Plugin entry point
  backend/                  # NestJS 11 orchestration API
    src/
      knowledge/            # 63 WordPress knowledge .md files
      tools/                # ToolDefinitionsService (parses dynamic schemas)
      chat/                 # Chat + ToolResult controllers
  frontend/                 # Next.js marketing site
```

## WordPress Plugin Conventions

- All tool classes use `namespace Wally\Tools;`
- Each class extends `ToolInterface`
- File naming: `class-<feature>-tools.php` (kebab-case)
- Class naming: `<Feature>Tools` or `<FeatureAction>` (PascalCase)
- Multiple tool classes per file (grouped by feature)
- Return format: `[ 'success' => true, 'data' => [...] ]` or `[ 'success' => false, 'error' => '...' ]`
- Conditional registration via `can_register()` static method
- `requires_confirmation()` = true for destructive actions

## Tool Categories

| Category | Description |
|----------|-------------|
| `content` | Posts, pages, media, comments |
| `site` | Site info, options, settings |
| `plugins` | Plugin management |
| `search` | Search and replace |
| `elementor` | Elementor page builder |
| `acf` | Advanced Custom Fields |
| `ecommerce` | WooCommerce, EDD |
| `forms` | Gravity Forms, CF7, WPForms |
| `seo` | Yoast, Rank Math |

## API Verification

**CRITICAL**: Before using any PHP function in a tool, verify it:
1. Search the plugin's official docs or source code
2. Confirm the function name, parameters, and return type
3. Check if it's Free vs Pro only
4. Use `function_exists()` or `class_exists()` in `can_register()`

## Notes
- All Nx commands run from the repo root
- Tool files are PHP — no build step needed (WordPress loads them directly)
- The backend auto-discovers tool schemas from the plugin, so no backend changes are needed
- Update this file when build process changes
