# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Wally** is an AI-powered WordPress admin assistant — a chat sidebar inside wp-admin that lets users manage their site through natural language. It uses a **hybrid SaaS model**: the WordPress plugin is a thin client, and all AI orchestration runs on the backend server.

**Official domain:** `https://www.wallychat.com` — use this as the canonical site URL everywhere (fallback in sitemap, robots, metadata, JSON-LD, etc.).

## Monorepo Structure

This is an **Nx 22.5.3 monorepo** with these apps:

| App | Tech | Purpose |
|-----|------|---------|
| `apps/wally` | React (WordPress Scripts) + PHP | WordPress plugin (primary product) |
| `apps/backend` | NestJS 11 | Orchestration API server |
| `apps/frontend` | Next.js 16 | Marketing/landing page |
| `apps/frontend-e2e` | Playwright | E2E tests for frontend |
| `apps/backend-e2e` | Jest | E2E tests for backend |

## Commands

All commands use Nx from the repo root. Node v22.22.0 required (see `.nvmrc`).

```bash
# Development
npx nx dev frontend              # Next.js dev server
npx nx serve backend             # NestJS on port 3000 with /api prefix
npx nx serve wally               # Watch JS & CSS for WordPress plugin

# WordPress plugin (from apps/wally/)
npm run build                    # Build React JS to admin/js/build/
npm run start                    # Watch JS in dev mode
npm run build:css                # Compile Tailwind to admin/css/sidebar.css
npm run watch:css                # Watch CSS

# Build all
npx nx run-many -t build

# Testing
npx nx test <app>                # Unit tests (Jest)
npx nx e2e frontend-e2e          # Playwright e2e (against localhost:3000)
npx nx e2e backend-e2e           # Jest e2e for backend

# Linting
npx nx lint <app>

# Dependency graph
npx nx graph
```

## Architecture

### Data Flow
```
WordPress Plugin (thin client)
  ↓ HTTPS (REST API)
Backend Orchestration Server (NestJS)
  ↓
LLM API (Claude / OpenAI)
  ↓ Tool calls
Backend → Plugin REST endpoint
  ↓
WordPress executes tools locally
  ↓
Results return to backend → streamed response to UI
```

### WordPress Plugin (`apps/wally/`)

The plugin has two layers:

**PHP Backend** (`wally.php` + `src/` classes):
- `Plugin` — singleton bootstrapper
- `Database` — creates custom tables on activation
- `SiteScanner` — scans site on activation; runs on `wally_daily_site_scan` cron
- REST Controller receives chat messages, forwards to NestJS backend, streams SSE responses back
- Tool Executor runs WordPress REST API calls locally when instructed by backend

**React Frontend** (`src/components/`):
- `ChatSidebar.jsx` — main container, drag/resize, manages chat state
- `MessageList.jsx` — renders messages with markdown, handles streaming
- `ConversationList.jsx` — conversation history sidebar
- `ConfirmAction.jsx` — confirmation dialog for destructive actions
- `SettingsPanel.jsx` — plugin config UI
- Entry: `src/index.js` mounts to `#wpaia-chat-root`
- Styles: Tailwind CSS → `admin/css/sidebar.css`

### Backend (`apps/backend/`)

NestJS 11 app bootstrapped at port 3000 with `/api` prefix. Module structure:

- `ChatModule` — `POST /api/v1/chat`, `POST /api/v1/tool-result` — SSE streaming endpoints
- `LlmModule` — `LlmService` — Anthropic + OpenAI providers, streaming, tool-use loop
- `KnowledgeModule` — `KnowledgeLoaderService` (63 `.md` files in `src/knowledge/`) + `PromptBuilderService` + `IntentClassifierService`
- `LicenseModule` — `POST /api/v1/license/validate`
- `UsageModule` — `GET /api/v1/usage/:site_id` — in-memory token tracking
- `HealthModule` — `GET /health` (no auth, no `/api` prefix)
- `common/guards/` — `AuthGuard` (X-Site-ID + X-API-Key), `RateLimiterGuard` (sliding window)
- `common/` — `MessageBuilderService`, `ResponseValidatorService`, `WallyLoggerService`
- `tools/` — `ToolDefinitionsService` — 30+ WordPress tool schemas for Anthropic + OpenAI
- `config/configuration.ts` — typed `WallyConfig`; reads `.env.local` / `.env`

Copy `apps/backend/.env.example` to `apps/backend/.env.local` and fill in API keys.

### Key Conventions

- **TypeScript**: strict mode, ES2022 target, no unused locals
- **Formatting**: Prettier with single quotes; 2-space indentation (`.editorconfig`)
- **WordPress plugin**: uses `@wordpress/scripts` build toolchain; PHP requires 8.0+, WP 6.0+
- **Confirmation flow**: Destructive WordPress actions must go through `ConfirmAction.jsx` before execution
- **Audit logging**: All executed actions are logged via `\Wally\AuditLog`

## Adding New Tools

The plugin is the **single source of truth** for all tools. The backend discovers them dynamically — no backend changes are needed when adding a new tool.

### How It Works

1. Each tool is a PHP class in `apps/wally/includes/tools/` implementing `Wally\Tools\ToolInterface`.
2. On every request, `ToolExecutor::get_tool_definitions()` exports the registered tool schemas.
3. `class-plugin.php` auto-discovers all `class-*-tools.php` files and registers eligible classes.
4. The REST controller sends the schemas to the NestJS backend with each chat/tool-result payload.
5. `ToolDefinitionsService.parseDynamicTools()` parses them; the controllers pass them to `LlmService`.
6. The LLM receives the full live toolset from the plugin and can call any registered tool.

### Creating a New Tool (one file only)

Create `apps/wally/includes/tools/class-<feature>-tools.php`:

```php
<?php
namespace Wally\Tools;

class MyFeatureTools extends ToolInterface {

    public function get_name(): string        { return 'my_tool_name'; }
    public function get_description(): string { return 'What this tool does (be specific for the LLM).'; }
    public function get_category(): string    { return 'content'; } // content | site | plugins | search | elementor | acf
    public function get_action(): string      { return 'read'; } // read | create | update | delete | plugins | site

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

    // Set to true for destructive actions (delete, reset, etc.)
    public function requires_confirmation(): bool { return false; }

    public function execute( array $params ): array {
        // Do the WordPress work here.
        // Return [ 'success' => true, 'data' => [...] ] or [ 'success' => false, 'error' => '...' ]
        return [ 'success' => true, 'data' => [] ];
    }
}
```

That's it — no registration code, no backend changes. The tool is live on the next request.

### Conditional Tools (plugin-dependent)

If a tool only makes sense when a specific plugin is active (e.g. WooCommerce, Yoast), override `can_register()`:

```php
public static function can_register(): bool {
    return class_exists( 'WooCommerce' ); // or is_plugin_active(...)
}
```

The auto-discovery loop skips the class when `can_register()` returns `false`.

Prefer `function_exists('acf_...')` over `class_exists(...)` for ACF checks — it targets the specific capability needed rather than just ACF being loaded.

### Existing Tool Files

| File | Tools covered |
|------|--------------|
| `class-content-tools.php` | list_posts, get_post, create_post, update_post, delete_post |
| `class-taxonomy-tools.php` | list_categories, list_tags, create_category, create_tag |
| `class-site-tools.php` | get_site_info, get_site_health, get_option, update_option |
| `class-plugin-tools.php` | list_plugins, install_plugin, activate_plugin, deactivate_plugin, update_plugin, delete_plugin |
| `class-search-tools.php` | search_content, replace_content |
| `class-elementor-tools.php` | elementor_search_content, elementor_replace_content, elementor_get_page_structure, elementor_clear_css_cache |
| `class-acf-tools.php` | Full ACF Free + Pro toolset: post types (CRUD), taxonomies (CRUD), field groups (CRUD), post/term/user field values (get + update), options page fields (list, get, update) |

### Key Files

| File | Role |
|------|------|
| `apps/wally/includes/tools/class-tool-interface.php` | Abstract base class all tools extend |
| `apps/wally/includes/class-tool-executor.php` | Registry: registers, validates, executes, exports schemas |
| `apps/wally/includes/class-plugin.php` | Auto-discovers tool classes on boot |
| `apps/wally/includes/class-rest-controller.php` | Attaches `tool_definitions` to every backend payload |
| `apps/backend/src/tools/tool-definitions.service.ts` | Parses dynamic schemas + formats for Anthropic/OpenAI |
| `apps/backend/src/chat/chat.controller.ts` | Resolves & passes dynamic tools to LLM |
| `apps/backend/src/chat/tool-result.controller.ts` | Same for the tool-result loop |

### Capability Reference

Common WordPress capabilities to use in `get_required_capability()`:

| Capability | Who has it |
|-----------|-----------|
| `read` | Subscriber+ |
| `edit_posts` | Contributor+ |
| `publish_posts` | Author+ |
| `edit_others_posts` | Editor+ |
| `manage_options` | Administrator only |
| `activate_plugins` | Administrator only |

## PRD Reference

Full product specification is in `prd/PRD.md`. It defines:
- 40+ WordPress tool definitions (posts, pages, plugins, search/replace, Elementor, etc.)
- Permission matrix mapping tools to WordPress roles
- Conversation quality test cases (`prd/PRD-conversation-quality-tests.md`)

## Documentation

Comprehensive architecture documentation lives in `docs/ARCHITECTURE.md`. It covers:
- High-level architecture and data flows
- All three apps (plugin, backend, frontend) in detail
- Database schemas (WordPress + Supabase)
- Full API reference (backend + plugin endpoints)
- Tool system architecture and execution pipeline
- Knowledge & prompt system (intent classification, prompt builder)
- Authentication, authorization, and rate limiting
- Licensing, usage tracking, and monetization tiers
- Testing setup and developer guide

### Keeping Documentation Updated

**IMPORTANT:** Whenever you make changes to the codebase, you MUST update `docs/ARCHITECTURE.md` to reflect those changes. This includes but is not limited to:

- Adding, removing, or modifying API endpoints
- Adding or changing database tables/columns
- Creating new modules, services, controllers, or components
- Changing authentication or authorization flows
- Adding or modifying tools (plugin tool system)
- Changing environment variables or configuration
- Modifying the knowledge/prompt system
- Adding new routes or pages to the frontend
- Changing the monorepo structure or build setup

Update the relevant section(s) in `docs/ARCHITECTURE.md` and bump the "Last updated" date at the top of the file. Keep changes minimal and focused — only update what actually changed.
