# Wally — Architecture & Technical Documentation

> **Last updated:** 2026-03-10
>
> This document is the single source of truth for Wally's architecture, data flows, API contracts, and codebase conventions. It must be kept up to date whenever the codebase changes.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Monorepo Structure](#2-monorepo-structure)
3. [High-Level Architecture](#3-high-level-architecture)
4. [WordPress Plugin (`apps/wally/`)](#4-wordpress-plugin-appswally)
5. [Backend (`apps/backend/`)](#5-backend-appsbackend)
6. [Frontend (`apps/frontend/`)](#6-frontend-appsfrontend)
7. [Shared Libraries (`libs/`)](#7-shared-libraries-libs)
8. [Database Schema](#8-database-schema)
9. [API Reference](#9-api-reference)
10. [Tool System](#10-tool-system)
11. [Knowledge & Prompt System](#11-knowledge--prompt-system)
12. [Authentication & Authorization](#12-authentication--authorization)
13. [Licensing & Usage Tracking](#13-licensing--usage-tracking)
14. [Testing](#14-testing)
15. [Developer Guide](#15-developer-guide)

---

## 1. Project Overview

**Wally** is an AI-powered WordPress admin assistant — a chat sidebar inside `wp-admin` that lets users manage their site through natural language.

**Hybrid SaaS model:**
- The **WordPress plugin** is a thin client (UI + local tool execution)
- The **NestJS backend** handles all AI orchestration (prompt building, LLM routing, streaming)
- The **Next.js frontend** is the marketing site + user dashboard

**Official domain:** `https://www.wallychat.com`

### Why Hybrid?

- PHP code is plaintext (cloning risk) → IP protection via server-side orchestration
- Backend enables license gating, usage tracking, remote updates
- BYOK users route through backend (our orchestration, their API key, their cost)

---

## 2. Monorepo Structure

**Nx 22.5.3** monorepo. Node v22.22.0 (see `.nvmrc`).

```
wally/
├── apps/
│   ├── wally/               # WordPress plugin (PHP + React)
│   ├── backend/             # NestJS 11 orchestration API
│   ├── frontend/            # Next.js 16 marketing site + user dashboard
│   ├── frontend-e2e/        # Playwright E2E tests for frontend
│   └── backend-e2e/         # Jest E2E tests for backend
├── libs/
│   └── api-client/          # Auto-generated API client (Orval → OpenAPI)
├── prd/
│   ├── PRD.md               # Product requirements document
│   └── PRD-conversation-quality-tests.md
├── docs/                    # Documentation
├── nx.json                  # Nx workspace config
├── tsconfig.base.json       # Shared TS config (ES2022, strict, bundler)
├── package.json             # Root workspace deps
├── .nvmrc                   # Node 22.22.0
├── .editorconfig            # Formatting rules
├── .prettierrc              # Single quotes, trailing commas
├── eslint.config.mjs        # ESLint (flat config)
├── jest.config.ts           # Jest preset
└── CLAUDE.md                # Claude Code instructions
```

### Nx Plugins

| Plugin | Purpose |
|--------|---------|
| `@nx/js/typescript` | TypeScript compilation + typecheck target |
| `@nx/next/plugin` | Next.js dev/build/start targets |
| `@nx/eslint/plugin` | ESLint linting |
| `@nx/playwright/plugin` | Playwright E2E tests |
| `@nx/jest/plugin` | Jest unit/integration tests |
| `@nx/webpack/plugin` | Webpack bundling for backend |

---

## 3. High-Level Architecture

### Data Flow

```
┌──────────────────────────────┐
│   WordPress Plugin (thin)    │
│   React UI + PHP REST API    │
└──────────┬───────────────────┘
           │ HTTPS (SSE streaming)
           │ Sends: message, conversation_history, site_profile, tool_definitions
           ▼
┌──────────────────────────────┐
│   NestJS Backend             │
│   Prompt Builder → LLM      │
│   Auth, Rate Limit, Usage   │
└──────────┬───────────────────┘
           │
     ┌─────┴─────┐
     ▼           ▼
┌─────────┐ ┌─────────┐
│ Claude  │ │ OpenAI  │
│ API     │ │ API     │
└────┬────┘ └────┬────┘
     └─────┬─────┘
           │ Tool calls (tool_use blocks)
           ▼
┌──────────────────────────────┐
│   Backend streams tool_call  │
│   event to plugin            │
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│   Plugin executes tool       │
│   locally in WordPress       │
│   (ToolExecutor pipeline)    │
└──────────┬───────────────────┘
           │ POST /api/v1/tool-result
           ▼
┌──────────────────────────────┐
│   Backend continues loop     │
│   (may call more tools or    │
│    generate final response)  │
└──────────────────────────────┘
```

### SSE Event Types

| Event | Payload | When |
|-------|---------|------|
| `token` | `{ content: string }` | Text chunk from LLM |
| `tool_call` | `{ tool_use_id, name, input, requires_confirmation }` | LLM wants to invoke a tool |
| `thinking` | `{ content: string }` | Extended thinking block (when enabled) |
| `usage` | `{ input_tokens, output_tokens }` | Token counts after response |
| `done` | `{ stop_reason }` | Final event |
| `error` | `{ message, code? }` | On failure |

---

## 4. WordPress Plugin (`apps/wally/`)

### Overview

- **Version:** 0.1.4
- **Requirements:** WordPress 6.0+, PHP 8.0+
- **Build:** `@wordpress/scripts` (Webpack) + Tailwind CSS 3.4
- **Entry:** `wally.php` → `Plugin::instance()` on `plugins_loaded`

### File Structure

```
apps/wally/
├── wally.php                          # Plugin header, constants, bootstrap
├── includes/
│   ├── class-plugin.php              # Singleton: hooks, tool auto-discovery, asset loading
│   ├── class-database.php            # DB table creation, conversation pruning
│   ├── class-rest-controller.php     # All REST endpoints (chat, conversations, tools, settings)
│   ├── class-tool-executor.php       # Tool registry, validation, execution, confirmation flow
│   ├── class-audit-log.php           # Action CRUD for wally_actions table
│   ├── class-permissions.php         # Role → action mapping
│   ├── class-site-scanner.php        # Scans WP site profile on activation + daily cron
│   ├── class-snapshot.php            # Pre-change snapshots for undo
│   ├── class-settings.php            # Admin settings page
│   ├── class-admin-log-page.php      # Audit log viewer page
│   ├── class-admin-conversations-page.php  # Conversation browser page
│   ├── class-wally-logger.php        # File-based logging (wp-content/wally-logs/)
│   ├── class-rate-limiter.php        # Sliding window rate limiting
│   ├── class-result-summarizer.php   # Tool output → LLM-friendly summaries
│   └── tools/
│       ├── class-tool-interface.php  # Abstract base class
│       ├── class-content-tools.php   # Posts/pages CRUD (5 tools)
│       ├── class-taxonomy-tools.php  # Categories/tags (4 tools)
│       ├── class-site-tools.php      # Site info, options (4 tools)
│       ├── class-plugin-tools.php    # Plugin management (6 tools)
│       ├── class-search-tools.php    # Search & replace (2 tools)
│       ├── class-elementor-tools.php # Elementor integration (4+ tools)
│       ├── class-elementor-builder-tools.php  # Elementor builder tools
│       ├── class-acf-tools.php       # ACF Free + Pro (15+ tools)
│       ├── class-woocommerce-tools.php
│       ├── class-user-tools.php
│       ├── class-media-tools.php
│       ├── class-menu-tools.php
│       ├── class-comment-tools.php
│       ├── class-caching-tools.php
│       └── ... (25+ more tool files)
├── src/
│   ├── index.js                     # Entry: mounts React to #wpaia-chat-root
│   ├── styles.css                   # Tailwind input (layers, keyframes)
│   └── components/
│       ├── ChatSidebar.jsx          # Main container (drag, resize, view switching)
│       ├── MessageList.jsx          # Chat display (markdown, tools, streaming cursor)
│       ├── MessageInput.jsx         # Auto-growing textarea + send/stop buttons
│       ├── ConversationList.jsx     # History sidebar (grouped by date, search)
│       ├── ConfirmAction.jsx        # Confirmation dialog for destructive actions
│       ├── SettingsPanel.jsx        # Settings view (behavior, permissions)
│       ├── FeedbackForm.jsx         # Bug report / feature request form
│       ├── MarkdownContent.jsx      # Markdown renderer (marked + sanitize)
│       ├── PanelHeader.jsx          # Draggable header with action slots
│       ├── ThinkingBlock.jsx        # Collapsible thinking process display
│       └── ToolOutputTable.jsx      # Renders tool results as HTML tables
├── admin/
│   ├── js/build/                    # Compiled React bundle + asset manifest
│   └── css/sidebar.css              # Compiled Tailwind CSS
├── tailwind.config.js               # Scoped to #wpaia-chat-root, preflight disabled
└── package.json                     # Plugin-specific deps + build scripts
```

### PHP Class Responsibilities

| Class | Responsibility |
|-------|----------------|
| `Plugin` | Singleton bootstrap, hook registration, tool auto-discovery, asset enqueueing, cron scheduling |
| `Database` | Creates 4 custom tables on activation, handles conversation auto-pruning |
| `RestController` | All REST endpoints: chat, conversations, tools, confirmation, audit, feedback, settings |
| `ToolExecutor` | Registers tools, validates input (JSON Schema), checks capabilities, executes, handles confirmation flow, logs to audit |
| `AuditLog` | CRUD for `wally_actions` table, formats summaries for LLM system prompt |
| `Permissions` | Maps WordPress roles (Admin/Editor/Author/Contributor/Subscriber) to action types (read/create/update/delete/plugins/site) |
| `SiteScanner` | Scans entire WP site on activation + daily cron: version, theme, plugins, post types, taxonomies, Elementor, ACF, menus, content counts |
| `Snapshot` | Saves pre-change state (post content, options) for undo capability |
| `Settings` | Admin settings page: license key, allowed roles, rate limits, confirmation toggles, permission overrides |
| `RateLimiter` | Sliding window rate limiting per site_id |
| `ResultSummarizer` | Converts tool output to concise LLM-friendly summaries |
| `WallyLogger` | File-based logging to `wp-content/wally-logs/` with daily rotation |

### React Components

| Component | Purpose |
|-----------|---------|
| `ChatSidebar` | Main container: draggable, resizable (420/600px), view switching (chat/history/settings/feedback), SSE streaming, abort |
| `MessageList` | Renders messages: user bubbles (purple), assistant (white + markdown), tool cards, error cards, suggestion chips, feedback buttons |
| `MessageInput` | Auto-growing textarea, Enter to send, Shift+Enter for newline, stop button during streaming |
| `ConversationList` | History sidebar: date-grouped list, search, delete, color-coded avatars, pagination |
| `ConfirmAction` | Inline confirmation for destructive actions: pending (orange) → confirmed (green) / rejected (red) |
| `SettingsPanel` | Toggles (confirm destructive, stream responses, sounds), role permissions display |
| `FeedbackForm` | Category selector (bug/feature/general), message, email, submit to backend |
| `MarkdownContent` | `marked` with GFM, HTML sanitization, code copy buttons, links open in new tab |
| `PanelHeader` | Draggable header bar with title, back button, action button slots |
| `ThinkingBlock` | Collapsible thinking process display (monospace, scrollable) |

### Tailwind CSS Isolation

All styles are scoped to `#wpaia-chat-root` via Tailwind's `important` config. Preflight is disabled to avoid clobbering wp-admin styles. Custom animations: `wpaia-spin`, `wpaia-dot-bounce`, `wpaia-shimmer`, `wpaia-cursor-blink`.

### Cron Jobs

| Hook | Schedule | Action |
|------|----------|--------|
| `wally_daily_site_scan` | Daily | `SiteScanner::scan()` + `Snapshot::cleanup_old()` |
| `wally_auto_prune` | Daily | `Database::prune_old_conversations()` (respects `wally_prune_days` setting) |

---

## 5. Backend (`apps/backend/`)

### Overview

- **Framework:** NestJS 11
- **Port:** 3000 (configurable)
- **Global prefix:** `/api` (excluded: `/health`)
- **Streaming:** Server-Sent Events (SSE)
- **LLM Providers:** Anthropic (Claude) + OpenAI (GPT)
- **Database:** Supabase (PostgreSQL)
- **Error tracking:** Sentry

### Module Architecture

| Module | Services / Controllers | Purpose |
|--------|----------------------|---------|
| `AppModule` | Root module | Imports all modules, global filters (Sentry) |
| `ChatModule` | `ChatController`, `ToolResultController` | SSE streaming chat + tool-result loop |
| `LlmModule` | `LlmService` | Unified Anthropic/OpenAI integration, streaming, retry logic |
| `KnowledgeModule` | `KnowledgeLoaderService`, `PromptBuilderService`, `IntentClassifierService` | Knowledge injection + system prompt construction |
| `LicenseModule` | `LicenseController` | License validation + site activation |
| `UsageModule` | `UsageService`, `UsageController` | Token tracking per site/month |
| `FeedbackModule` | `FeedbackService`, `FeedbackController` | Ratings + general feedback |
| `UserModule` | `UserController` | User dashboard (license & site management) |
| `HealthModule` | `HealthController` | Load balancer health check |
| `SupabaseModule` | `SupabaseService` | Supabase client initialization |

### Common Services

| Service | Purpose |
|---------|---------|
| `MessageBuilderService` | Converts tool-result payloads to Anthropic message format |
| `ResponseValidatorService` | Validates LLM output |
| `WallyLoggerService` | Structured JSON logging (info/warn/error/debug) |
| `ToolDefinitionsService` | Parses dynamic tool schemas from plugin, formats for Anthropic/OpenAI |

### Guards

| Guard | Applied To | Method |
|-------|-----------|--------|
| `AuthGuard` | Chat, Tool-Result, Usage, Feedback | Validates `X-License-Key` + `X-Site-ID` headers against Supabase |
| `RateLimiterGuard` | Chat, Tool-Result | Per-minute (in-memory sliding window) + per-day (Supabase) |
| `AdminGuard` | Feedback listing | Timing-safe comparison of `X-Admin-Key` header |
| `UserAuthGuard` | User module | Supabase Auth JWT from `Authorization: Bearer` header |

### LLM Service

**Supported Models:**

| Config Key | Provider | Model ID |
|-----------|----------|----------|
| `claude-sonnet-4-6` | Anthropic | `claude-sonnet-4-6` |
| `claude-haiku-4-5` | Anthropic | `claude-haiku-4-5-20251001` |
| `gpt-4o` | OpenAI | `gpt-4o` |
| `gpt-4o-mini` | OpenAI | `gpt-4o-mini` |

**Features:**
- Streaming responses via `client.messages.stream()` (Anthropic) or `client.chat.completions.create({ stream: true })` (OpenAI)
- Retry logic: 3 attempts on HTTP 429/529/503, exponential backoff (1s, 2s, 4s)
- Max tokens: 4096 base (+ 6000 if extended thinking enabled)
- Extended thinking: configurable (`ENABLE_THINKING = false` by default)
- Normalized `LlmResponse` for both providers:

```typescript
interface LlmResponse {
  content: LlmContentBlock[];  // text, tool_use, thinking blocks
  model: string;
  usage: { input_tokens: number; output_tokens: number } | null;
  stop_reason: string;
}
```

### Configuration

**Environment Variables:**

| Variable | Default | Purpose |
|----------|---------|---------|
| `NODE_ENV` | `development` | Environment mode |
| `PORT` | `3000` | Server port |
| `ANTHROPIC_API_KEY` | — | Anthropic API key (required) |
| `OPENAI_API_KEY` | — | OpenAI API key (optional) |
| `DEFAULT_MODEL` | `claude-3-5-sonnet` | Default LLM model |
| `RATE_LIMIT_PER_SITE_PER_MINUTE` | `30` | Per-minute request limit |
| `RATE_LIMIT_PER_SITE_PER_DAY` | `1000` | Per-day request limit |
| `SKIP_LICENSE_VALIDATION` | `false` | Dev mode: skip license checks |
| `ADMIN_API_KEY` | — | Shared secret for admin endpoints |
| `SUPABASE_URL` | — | Supabase project URL |
| `SUPABASE_SERVICE_ROLE_KEY` | — | Supabase service role key |
| `KNOWLEDGE_DIR` | auto-detect | Path to knowledge markdown files |

### Global Middleware

- **Validation Pipe:** `whitelist: true`, `transform: true` (strips unknown props, transforms to DTOs)
- **CORS:** Open in development, restricted in production; allowed headers: `Content-Type`, `X-Site-ID`, `X-License-Key`
- **Error Filter:** Sentry global filter for automatic error tracking
- **Swagger:** Available at `/api/docs` in non-production environments

### Key Files

| File | Role |
|------|------|
| `main.ts` | Bootstrap, CORS, validation, Swagger |
| `app/app.module.ts` | Root module, imports all feature modules |
| `config/configuration.ts` | Typed config schema, env loading |
| `chat/chat.controller.ts` | `POST /api/v1/chat` — SSE streaming |
| `chat/tool-result.controller.ts` | `POST /api/v1/tool-result` — tool-use loop |
| `llm/llm.service.ts` | Anthropic + OpenAI integration |
| `knowledge/knowledge-loader.service.ts` | Loads 70+ markdown files at startup |
| `knowledge/prompt-builder.service.ts` | Builds 14k-token system prompt |
| `knowledge/intent-classifier.service.ts` | Regex-based intent classification |
| `tools/tool-definitions.service.ts` | Dynamic tool parsing + provider formatting |
| `common/guards/auth.guard.ts` | License + site validation |
| `common/guards/rate-limiter.guard.ts` | Hybrid rate limiting |
| `common/message-builder.service.ts` | Anthropic message format builder |
| `common/logger/wally-logger.service.ts` | Structured JSON logging |

---

## 6. Frontend (`apps/frontend/`)

### Overview

- **Framework:** Next.js 16 (App Router)
- **Auth:** Supabase Auth (email/password + Google OAuth)
- **Styling:** Tailwind CSS 3.4 with HSL color system
- **Animations:** Framer Motion
- **Forms:** React Hook Form + Zod validation
- **Icons:** Lucide React
- **Error tracking:** Sentry
- **Analytics:** Vercel Analytics

### Route Structure

**Public Routes:**

| Route | Page | Description |
|-------|------|-------------|
| `/` | Landing page | Hero, features, pricing, demo, CTA |
| `/blog` | Blog listing | Dynamic `[slug]` pages |
| `/privacy` | Privacy policy | Legal page |
| `/terms` | Terms of service | Legal page |
| `/feedback` | Feedback form | Public feedback submission |

**Auth Routes (`(auth)` group):**

| Route | Page | Description |
|-------|------|-------------|
| `/login` | Sign in | Email/password + Google OAuth |
| `/register` | Sign up | Name, email, password |
| `/auth/callback` | OAuth callback | Supabase auth code exchange |

**Protected App Routes (`(app)` group — requires auth):**

| Route | Page | Description |
|-------|------|-------------|
| `/app/license` | License management | Key display, site activation, plugin download |
| `/app/account` | Account settings | Profile editing |
| `/app/subscriptions` | Subscription management | Plan management |
| `/app/faq` | FAQ | Help page |

**Admin Routes:**

| Route | Page | Description |
|-------|------|-------------|
| `/admin/feedback` | Feedback dashboard | Password-protected, lists all feedback |

### API Routes

| Method | Route | Purpose |
|--------|-------|---------|
| `POST` | `/api/waitlist` | Join waitlist |
| `POST` | `/api/feedback` | Submit feedback |
| `GET` | `/api/user/license` | Fetch user's license info |
| `DELETE` | `/api/user/sites/[siteId]` | Deactivate a site |
| `POST` | `/api/admin/auth` | Admin dashboard authentication |
| `GET` | `/api/admin/feedback` | List all feedback (admin only) |

### Component Architecture

```
src/components/
├── ui/                    # Primitives (button, input, checkbox, switch, radio)
├── auth/                  # Auth forms (left panel, form inputs, Google button)
├── landing/               # Landing page sections (hero, pricing, features, etc.)
│   └── shared/            # Container, section badge, animated section
├── app/                   # App components (sidebar, license card, sites card, etc.)
└── legal/                 # Legal footer
```

### SEO & Metadata

- Sitemap: Auto-generated (homepage, privacy, terms)
- Robots.txt: Allows `/`, disallows `/app/` and `/admin/`
- OG image: `/site-og.png` (1200x630)
- JSON-LD: WebSite + Organization schema on homepage
- App routes: `robots: { index: false }`

### Auth Flow

1. User registers at `/register` (email + password, or Google OAuth)
2. Supabase Auth creates user, stores session in cookies (SSR pattern)
3. Middleware checks auth state on every request to `/app/*`
4. Unauthenticated users redirected to `/register` with return URL
5. Authenticated users on `/login` or `/register` redirected to `/app/license`

### Environment Variables

| Variable | Purpose |
|----------|---------|
| `NEXT_PUBLIC_SUPABASE_URL` | Supabase project URL |
| `NEXT_PUBLIC_SUPABASE_ANON_KEY` | Supabase anon key |
| `BACKEND_URL` | Backend API URL (server components) |
| `NEXT_PUBLIC_SITE_URL` | Canonical URL (default: `https://www.wallychat.com`) |
| `NEXT_PUBLIC_GA_MEASUREMENT_ID` | Google Analytics (optional) |

---

## 7. Shared Libraries (`libs/`)

### API Client (`libs/api-client/`)

Auto-generated via **Orval** from the backend's OpenAPI spec.

- **Entry:** `src/index.ts`
- **Custom mutator:** `src/mutator/wally-fetch.ts`
- **Generated DTOs:** `validateLicenseDto`, `licenseFeaturesDto`, `licenseResponseDto`, `activateLicenseDto`, `activateResponseDto`, `usageResponseDto`, `healthResponseDto`, `createFeedbackDto`, `createRatingDto`, `createGeneralFeedbackDto`
- **Path alias:** `@wally/api-client` (configured in `tsconfig.base.json`)

**Regenerate:** `npm run generate:api` (from root)

---

## 8. Database Schema

### WordPress Plugin Tables

**`{prefix}_wally_conversations`**

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT (PK) | Auto-increment |
| `user_id` | BIGINT (FK) | `wp_users.ID` |
| `title` | VARCHAR(255) | Default empty |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | |

**`{prefix}_wally_messages`**

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT (PK) | Auto-increment |
| `conversation_id` | BIGINT (FK) | |
| `role` | VARCHAR(20) | `user`, `assistant` |
| `content` | LONGTEXT | Markdown or JSON |
| `token_count` | INT | Usage tracking |
| `created_at` | DATETIME | |

**`{prefix}_wally_actions`** (Audit log)

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT (PK) | Auto-increment |
| `conversation_id` | BIGINT | |
| `message_id` | BIGINT (nullable) | |
| `user_id` | BIGINT | |
| `tool_name` | VARCHAR(100) | |
| `tool_input` | LONGTEXT | JSON |
| `tool_output` | LONGTEXT | JSON |
| `status` | VARCHAR(20) | `success`, `failed`, `cancelled`, `pending`, `confirmed` |
| `created_at` | DATETIME | |

**`{prefix}_wally_snapshots`** (Pre-change state for undo)

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT (PK) | Auto-increment |
| `conversation_id` | BIGINT | |
| `snapshot_type` | VARCHAR(50) | `post`, `option`, `menu`, `plugin` |
| `object_id` | BIGINT (nullable) | Post/menu ID |
| `object_key` | VARCHAR(255) (nullable) | Option name, meta key |
| `previous_value` | LONGTEXT | Serialized |
| `created_at` | DATETIME | |

### Supabase Tables (Backend)

| Table | Purpose |
|-------|---------|
| `license_keys` | License key, tier, max_sites, expires_at, status |
| `sites` | site_id, license_key_id, domain, is_active, activated_at, license_tier, features |
| `rate_limits` | site_id, date, count (per-day) |
| `usage` | site_id, month, input_tokens, output_tokens, requests |
| `feedback` | type, rating, message, category, email, name, source, site_id, conversation_id, message_id |

**Supabase RPCs:**
- `increment_rate_limit(p_site_id, p_date)` — Atomic daily counter increment
- `increment_usage(p_site_id, p_month, p_input_tokens, p_output_tokens)` — Atomic monthly token upsert

---

## 9. API Reference

### Backend Endpoints (`/api/v1/`)

#### Chat

| Method | Route | Guards | Request | Response |
|--------|-------|--------|---------|----------|
| `POST` | `/api/v1/chat` | Auth, RateLimit | `ChatRequestDto` (message, conversation_history, site_profile, tool_definitions, custom_system_prompt, recent_actions) | SSE stream (token, tool_call, usage, done, error) |
| `POST` | `/api/v1/tool-result` | Auth, RateLimit | `ToolResultRequestDto` (conversation_history, pending_tool_calls, tool_results, site_profile, tool_definitions) | SSE stream |

#### License

| Method | Route | Guards | Purpose |
|--------|-------|--------|---------|
| `POST` | `/api/v1/license/validate` | None | Validate license key → tier, features, expiry |
| `POST` | `/api/v1/license/activate` | None | Activate/re-activate site under license |

#### Usage

| Method | Route | Guards | Purpose |
|--------|-------|--------|---------|
| `GET` | `/api/v1/usage/:site_id` | Auth | Monthly token usage for a site |

#### User

| Method | Route | Guards | Purpose |
|--------|-------|--------|---------|
| `GET` | `/api/v1/user/license` | UserAuth | Get user's license + activated sites |
| `DELETE` | `/api/v1/user/sites/:siteId` | UserAuth | Deactivate a site |

#### Feedback

| Method | Route | Guards | Purpose |
|--------|-------|--------|---------|
| `POST` | `/api/v1/feedback` | None | Public feedback from website |
| `POST` | `/api/v1/feedback/rating` | Auth | Per-message rating from plugin |
| `POST` | `/api/v1/feedback/general` | Auth | General feedback from plugin |
| `GET` | `/api/v1/feedback` | Admin | List all feedback (admin dashboard) |

#### Health

| Method | Route | Guards | Purpose |
|--------|-------|--------|---------|
| `GET` | `/health` | None | Health check (no `/api` prefix) |

### WordPress Plugin Endpoints (`/wp-json/wally/v1/`)

| Method | Route | Purpose |
|--------|-------|---------|
| `POST` | `/chat` | Forward message to backend, stream SSE |
| `GET` | `/conversations` | List user's conversations (paginated) |
| `GET` | `/conversations/{id}` | Load conversation with messages + audit actions |
| `DELETE` | `/conversations/{id}` | Delete conversation |
| `PATCH` | `/conversations/{id}/title` | Rename conversation |
| `GET` | `/site-profile` | Return cached SiteScanner result |
| `POST` | `/site-profile/rescan` | Force immediate site scan |
| `POST` | `/confirm/{action_id}` | Confirm pending tool execution |
| `DELETE` | `/actions/{action_id}` | Reject pending action |
| `GET` | `/actions` | Query audit log (filters: user, tool, status, date) |
| `POST` | `/feedback/rating` | Thumbs up/down on responses |
| `POST` | `/feedback/general` | Bug report / feature request |
| `GET/PATCH` | `/settings` | Get/update plugin settings |

---

## 10. Tool System

### Architecture

The plugin is the **single source of truth** for all tools. The backend discovers them dynamically.

```
Plugin boot → auto-discover class-*-tools.php → register in ToolExecutor
     ↓
Every chat request → ToolExecutor::get_tool_definitions() → send to backend
     ↓
Backend → ToolDefinitionsService.parseDynamicTools() → format for LLM provider
     ↓
LLM calls tool → backend streams tool_call event → plugin executes locally
     ↓
Result → POST /api/v1/tool-result → LLM continues or responds
```

### Tool Interface

```php
abstract class ToolInterface {
    abstract public function get_name(): string;
    abstract public function get_description(): string;
    abstract public function get_category(): string;           // content|site|plugins|search|elementor|acf
    abstract public function get_action(): string;             // read|create|update|delete|plugins|site
    abstract public function get_parameters_schema(): array;   // JSON Schema
    abstract public function get_required_capability(): string;
    public function requires_confirmation(): bool { return false; }
    public static function can_register(): bool { return true; }
    abstract public function execute(array $input): array;
}
```

### Tool Execution Pipeline

1. **Input validation** — JSON Schema check against `get_parameters_schema()`
2. **Capability check** — `current_user_can(get_required_capability())`
3. **Permission check** — `Permissions::can_use_action(role, get_action())`
4. **Confirmation** — If `requires_confirmation()` + setting enabled → create pending action
5. **Execution** — Call `execute($params)` → return `{ success, data }` or `{ success: false, error }`
6. **Audit logging** — Log to `wally_actions` table
7. **Snapshot** — Save pre-change state for undo (if applicable)

### Existing Tools (50+)

| File | Tools |
|------|-------|
| `class-content-tools.php` | list_posts, get_post, create_post, update_post, delete_post |
| `class-taxonomy-tools.php` | list_categories, list_tags, create_category, create_tag |
| `class-site-tools.php` | get_site_info, get_site_health, get_option, update_option |
| `class-plugin-tools.php` | list_plugins, install_plugin, activate_plugin, deactivate_plugin, update_plugin, delete_plugin |
| `class-search-tools.php` | search_content, replace_content |
| `class-elementor-tools.php` | elementor_search_content, elementor_replace_content, elementor_get_page_structure, elementor_clear_css_cache |
| `class-elementor-builder-tools.php` | Elementor builder-specific tools |
| `class-acf-tools.php` | Full ACF CRUD: post types, taxonomies, field groups, field values, options pages |
| `class-woocommerce-tools.php` | WooCommerce product/order tools |
| `class-user-tools.php` | User management tools |
| `class-media-tools.php` | Media upload/management |
| `class-menu-tools.php` | Navigation menu CRUD |
| `class-comment-tools.php` | Comment management |
| `class-caching-tools.php` | Cache clearing |
| `class-jetpack-tools.php` | Jetpack integration |
| + more | Security, redirects, backup, analytics, email, forms, etc. |

### Permission Matrix

| Role | read | create | update | delete | plugins | site |
|------|:----:|:------:|:------:|:------:|:-------:|:----:|
| Administrator | yes | yes | yes | yes | yes | yes |
| Editor | yes | yes | yes | yes | no | no |
| Author | yes | yes | yes | no | no | no |
| Contributor | yes | yes | no | no | no | no |
| Subscriber | yes | no | no | no | no | no |

### Adding New Tools

Create `apps/wally/includes/tools/class-<feature>-tools.php`. That's it — no registration code, no backend changes. The tool is live on the next request. See [CLAUDE.md](../CLAUDE.md#adding-new-tools) for the template.

For conditional tools (plugin-dependent), override `can_register()`:
```php
public static function can_register(): bool {
    return class_exists('WooCommerce');
}
```

---

## 11. Knowledge & Prompt System

### Knowledge Loader

- **70+ markdown files** in `apps/backend/src/knowledge/`
- Loaded into memory at startup as `Map<string, string>`
- Categories: WordPress core, plugins (WooCommerce, Yoast, Elementor, ACF, etc.), page builders, SEO, forms, security, email, analytics, WP core APIs

### Intent Classifier

- **100+ regex patterns** match user messages to knowledge file keys
- Checks current message + last 2 messages for context continuity
- Caps at 8 intents (MAX_INTENTS)
- Always includes `'general'` baseline

### System Prompt Builder

Constructs a **~14,000 token** system prompt with these sections:

| Section | Budget | Content |
|---------|--------|---------|
| Identity & Expertise | ~500 | Wally's role, capabilities, user mental model |
| Core Behavior | ~700 | Response style, tool usage, confirmation flow, proactivity |
| Complex Task Planning | ~600 | Multi-step workflows, Elementor guidance, verification |
| Page Creation | ~400 | Detects page builder, guides user on which to use |
| Intent-Based Knowledge | ~5000 | Classified intents → relevant knowledge file content |
| Site Context | ~2000 | WP version, theme, plugins, post types, Elementor, ACF, menus |
| Content Style Reference | ~600 | Recent post samples (title + excerpt) to match writing style |
| Recent Actions | ~400 | Pre-formatted action summaries from audit log |
| Custom Instructions | ~300 | From plugin settings `custom_system_prompt` field |

---

## 12. Authentication & Authorization

### Backend Auth Flow

```
Request arrives
     ↓
AuthGuard reads X-License-Key + X-Site-ID headers
     ↓
If SKIP_LICENSE_VALIDATION=true (dev):
  → Mock pro tier, attach licenseInfo, pass
     ↓
Else: Query Supabase
  → license_keys WHERE key = licenseKey (check active + not expired)
  → sites WHERE id = siteId AND license_key_id = license.id (check is_active)
     ↓
Attach req.siteId + req.licenseInfo to request
```

### License Tiers

| Tier | Messages/day | Models | Tool Categories |
|------|-------------|--------|-----------------|
| Free | 50 | Claude Haiku | content, site |
| Pro | 1000 | Claude Sonnet, Haiku, GPT-4o, GPT-4o-mini | content, site, plugins, search, elementor |
| Enterprise | 10,000 | All models | All categories |

### Rate Limiting

- **Per-minute** (in-memory sliding window): Default 30 requests/min per site
- **Per-day** (Supabase persistent): Default 1000 requests/day per site
- In-memory stale window cleanup every hour

### Plugin Auth

- REST endpoints secured via WordPress nonce + `current_user_can()` checks
- API keys stored with AES-256 encryption using `wp_salt('auth')`
- CORS: Same-origin only

---

## 13. Licensing & Usage Tracking

### License Validation

- `POST /api/v1/license/validate` — Validates key, returns tier + features + expiry
- `POST /api/v1/license/activate` — Activates site under license (respects `max_sites`)
- Plugin stores encrypted license key in `wally_license_key` option

### Usage Tracking

- After each LLM response, `UsageService.recordUsage()` fires-and-forgets to Supabase
- Atomic upsert via `increment_usage` RPC
- Monthly aggregation by `YYYY-MM`
- `GET /api/v1/usage/:site_id` returns current month stats

### Monetization Tiers

| Tier | Price | Sites | Key Features |
|------|-------|-------|-------------|
| Free | $0 | 1 | BYOK, content + site tools, 50 msgs/day |
| Pro | $12/mo | 1 | All tools, unlimited msgs, action log |
| Agency | $49/mo | 10 | White-label + bulk management |
| Enterprise | $149/mo | unlimited | Custom branding, SSO, SLA |

---

## 14. Testing

### Unit Tests (Jest)

```bash
npx nx test <app>       # Run unit tests for an app
npx nx test backend     # Backend unit tests
npx nx test frontend    # Frontend unit tests
```

### E2E Tests

```bash
npx nx e2e frontend-e2e    # Playwright (against localhost:3000)
npx nx e2e backend-e2e     # Jest API integration tests
```

**Frontend E2E (Playwright):**
- Config: `apps/frontend-e2e/playwright.config.ts`
- Base URL: `http://localhost:3000`
- Auto-starts dev server before tests
- Browsers: Chromium, Firefox, WebKit

**Backend E2E (Jest):**
- Config: `apps/backend-e2e/jest.config.cts`
- Depends on backend build + serve
- Uses `@nestjs/testing` utilities

---

## 15. Developer Guide

### Getting Started

```bash
# Prerequisites: Node 22.22.0
nvm use

# Install dependencies
npm install

# Copy env files
cp apps/backend/.env.example apps/backend/.env.local
# Fill in API keys

# Start development
npx nx dev frontend     # Next.js on :3000
npx nx serve backend    # NestJS on :3000/api
```

### Common Commands

| Command | Purpose |
|---------|---------|
| `npx nx dev frontend` | Next.js dev server |
| `npx nx serve backend` | NestJS dev server (port 3000) |
| `npx nx serve wally` | Watch JS & CSS for WordPress plugin |
| `npx nx run-many -t build` | Build all apps |
| `npx nx test <app>` | Unit tests |
| `npx nx lint <app>` | Linting |
| `npx nx graph` | Dependency graph |
| `npm run generate:api` | Regenerate API client from OpenAPI spec |

### Code Conventions

| Convention | Detail |
|-----------|--------|
| TypeScript | Strict mode, ES2022, no unused locals |
| Formatting | Prettier: single quotes, trailing commas, 2-space indent |
| PHP | 8.0+ required, PSR-4 autoloading via Composer |
| React | Functional components, hooks, JSX |
| CSS | Tailwind CSS, scoped to `#wpaia-chat-root` in plugin |
| API | RESTful, versioned (`/api/v1/`), SSE streaming for chat |

### Key Architectural Decisions

1. **Plugin = single source of truth for tools** — Backend discovers them dynamically. No backend changes needed for new tools.
2. **SSE streaming** — Real-time token-by-token output, not polling.
3. **Hybrid rate limiting** — In-memory for per-minute (fast, acceptable loss on restart), Supabase for per-day (persistent).
4. **Knowledge injection** — Intent-classified, budget-managed system prompt (~14k tokens).
5. **Confirmation flow** — Destructive actions require inline UI approval before execution.
6. **Tailwind isolation** — All plugin styles scoped to `#wpaia-chat-root` with preflight disabled.
7. **Supabase** — Auth (frontend), licensing + usage + rate limits (backend).
