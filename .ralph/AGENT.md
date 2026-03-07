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
  wally/                    # WordPress plugin
    includes/
      tools/                # Tool PHP classes (one file per feature)
        class-tool-interface.php   # Abstract base class — READ THIS FIRST
        class-content-tools.php    # Posts CRUD (example of multi-tool file)
        class-menu-tools.php       # Menu management
        class-media-tools.php      # Media library
        class-woocommerce-tools.php # WooCommerce (conditional)
        class-yoast-seo-tools.php  # Yoast SEO (conditional)
        class-comment-tools.php    # Comments
        class-user-tools.php       # Users
        ... (35 tool files total)
      class-tool-executor.php      # Registry: registers, validates, executes
      class-plugin.php             # Auto-discovers tool classes + cron
      class-rest-controller.php    # Sends schemas to backend
      class-site-scanner.php       # Scans site profile (21 fields)
      class-database.php           # Creates custom DB tables
    wally.php                      # Plugin entry point
  backend/                  # NestJS 11 orchestration API
    src/
      knowledge/            # 64 WordPress knowledge .md files
        prompt-builder.service.ts   # Builds system prompt
        knowledge-loader.service.ts # Loads knowledge files
      intent/
        intent-classifier.service.ts # Regex-based intent classification
      tools/                # ToolDefinitionsService
      chat/                 # Chat + ToolResult controllers
  frontend/                 # Next.js marketing site
docs/
  phase-2-strategic-tools.md   # Tool specs with WordPress API references
  phase-3-plugin-expansions.md # Plugin-conditional tool specs
  phase-4-advanced-features.md # Advanced feature specs
```

## WordPress Plugin Conventions

- All tool classes use `namespace Wally\Tools;`
- Each class extends `ToolInterface`
- File naming: `class-<feature>-tools.php` (kebab-case)
- Class naming: `<FeatureAction>` (PascalCase, e.g., `ListMenus`, `GetErrorLog`)
- Multiple tool classes per file (grouped by feature domain)
- Return format: `[ 'success' => true, 'data' => [...] ]` or `[ 'success' => false, 'error' => '...' ]`
- Conditional registration via `can_register()` static method
- `requires_confirmation()` = true for destructive actions

## Tool Categories

| Category | Description |
|----------|-------------|
| `content` | Posts, pages, media, comments |
| `site` | Site info, options, settings, debug |
| `plugins` | Plugin management |
| `search` | Search and replace |
| `elementor` | Elementor page builder |
| `acf` | Advanced Custom Fields |
| `ecommerce` | WooCommerce, EDD |
| `forms` | Gravity Forms, CF7, WPForms |
| `seo` | Yoast, Rank Math |

## Knowledge File Conventions

- Markdown format, stored in `apps/backend/src/knowledge/`
- File name (without .md) maps 1:1 to intent keys in the classifier
- Content should be concise, example-rich, directly useful for LLM context
- Block markup examples must use exact WordPress comment delimiter syntax

## API Verification

**CRITICAL**: Before using any PHP function in a tool, verify it:
1. Search the plugin's official docs or source code
2. Confirm the function name, parameters, and return type
3. Check if it's Free vs Pro only
4. Use `function_exists()` or `class_exists()` in `can_register()`

## Notes
- All Nx commands run from the repo root
- Tool files are PHP — no build step needed (WordPress loads them directly)
- Knowledge files are loaded at NestJS startup — restart backend to pick up changes
- The backend auto-discovers tool schemas from the plugin, so no backend changes needed for new tools
