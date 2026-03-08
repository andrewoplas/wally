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
  wally/                    # WordPress plugin (thin client)
    includes/
      tools/                # Tool PHP classes (one file per feature)
        class-tool-interface.php       # Abstract base — READ THIS FIRST
        class-elementor-builder-tools.php  # PRIMARY TARGET — Elementor page builder tools
        class-elementor-tools.php      # Elementor search/replace/structure tools
        class-content-tools.php        # Posts CRUD (good reference for style)
        class-site-tools.php           # Site info, options
        ... (35+ tool files total)
      class-tool-executor.php          # Registry: registers, validates, executes
      class-plugin.php                 # Auto-discovers tool classes
      class-rest-controller.php        # Sends tool schemas to backend, streams SSE
      class-site-scanner.php           # Scans site profile
    wally.php                          # Plugin entry point
  backend/                  # NestJS 11 orchestration API
    src/
      knowledge/            # 63 WordPress knowledge .md files
        prompt-builder.service.ts   # Builds system prompt (token budgets here)
        knowledge-loader.service.ts # Loads knowledge files
        elementor.md                # Elementor knowledge (rewrite target)
        wally-capabilities.md       # Capabilities knowledge (update target)
        general.md                  # Always-on general knowledge
      intent/
        intent-classifier.service.ts # Regex-based intent classification
      llm/
        llm.service.ts              # Anthropic/OpenAI streaming orchestration
      tools/
        tool-definitions.service.ts # Parses dynamic tool schemas from plugin
      chat/
        chat.controller.ts          # POST /v1/chat endpoint
        tool-result.controller.ts   # POST /v1/tool-result endpoint
      common/
        message-builder.service.ts  # Converts to Anthropic message format
```

## Key Files for This Mission

| File | What to do |
|------|-----------|
| `apps/wally/includes/tools/class-elementor-builder-tools.php` | Fix `save_elementor_data()`, add examples to descriptions, add verify tool |
| `apps/backend/src/knowledge/prompt-builder.service.ts` | Expand token budget, add planning framework |
| `apps/backend/src/knowledge/elementor.md` | Rewrite as operational playbook |
| `apps/backend/src/knowledge/wally-capabilities.md` | Add complex task guidance |

## WordPress Plugin Conventions

- All tool classes use `namespace Wally\Tools;`
- Each class extends `ToolInterface`
- File naming: `class-<feature>-tools.php` (kebab-case)
- Class naming: PascalCase (e.g., `ElementorCreatePage`, `ElementorVerifyPage`)
- Multiple tool classes per file (grouped by feature domain)
- Return format: `[ 'success' => true, 'data' => [...] ]` or `[ 'success' => false, 'error' => '...' ]`
- Conditional registration via `can_register()` static method
- `requires_confirmation()` = true for destructive actions

## Elementor Architecture Notes

Elementor stores page data as JSON in `_elementor_data` post meta. Key meta keys:
- `_elementor_data` — JSON array of elements (sections/containers → columns → widgets)
- `_elementor_edit_mode` — must be `'builder'` for Elementor to render
- `_elementor_template_type` — usually `'wp-page'`
- `_elementor_version` — Elementor version
- `_elementor_css` — cached CSS data (delete to force regeneration)

When Elementor's editor saves, it goes through `Document::save()` which:
1. Persists `_elementor_data`
2. Generates per-post CSS file
3. Updates `post_content` with rendered HTML
4. Fires hooks

The current code skips steps 2-4, causing blank pages.

## API Verification

**CRITICAL**: Before using any Elementor PHP class/method, verify it exists:
1. Use context7 with library `elementor/elementor` or WebSearch
2. Confirm: `\Elementor\Plugin::$instance->documents->get($post_id)` returns a Document
3. Confirm: `$document->save(['elements' => $data])` triggers full pipeline
4. Confirm: `\Elementor\Core\Files\CSS\Post` class and `->update()` method exist
5. Check Elementor version compatibility (target Elementor 3.x+)

## Notes
- Tool files are PHP — no build step needed (WordPress loads them directly)
- Knowledge files are loaded at NestJS startup — restart backend to pick up changes
- The backend auto-discovers tool schemas from the plugin, so no backend changes needed for new PHP tools
- The prompt-builder.service.ts token budgets are defined as constants — search for `SYSTEM_TOTAL_MAX` or similar
