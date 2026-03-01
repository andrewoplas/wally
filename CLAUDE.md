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

## PRD Reference

Full product specification is in `prd/PRD.md`. It defines:
- 40+ WordPress tool definitions (posts, pages, plugins, search/replace, Elementor, etc.)
- Permission matrix mapping tools to WordPress roles
- Conversation quality test cases (`prd/PRD-conversation-quality-tests.md`)
