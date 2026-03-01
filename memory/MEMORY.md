# Wally Project Memory

## Project Summary
Wally is an AI-powered WordPress admin assistant. Nx monorepo with:
- `apps/wally` — WordPress plugin (React + PHP, primary product)
- `apps/backend` — NestJS orchestration server
- `apps/frontend` — Next.js marketing page

## Key Facts
- Node v22.22.0 (.nvmrc)
- Nx 22.5.3 monorepo
- PRD at `prd/PRD.md` (full product spec, 40+ tool definitions)
- WordPress plugin: PHP 8.0+, WP 6.0+, built with @wordpress/scripts
- Backend: NestJS on port 3000, /api prefix
- CLAUDE.md created at repo root (2026-02-28)

## Auth Architecture (2026-03-01)
- **License key is the sole credential** — removed API key entirely (bcrypt/api_key_hash flow gone)
- Plugin sends `X-License-Key` + `X-Site-ID` headers (was `X-API-Key` + `X-Site-ID`)
- `AuthGuard`: looks up `license_keys` by key → validates status + expiry → looks up `sites` by id + license_key_id → 403 codes: `license_invalid`, `license_expired`, `license_cancelled`, `site_not_activated`, `site_deactivated`
- `POST /v1/license/activate`: public endpoint; upserts site record, enforces max_sites per tier
- `POST /v1/license/validate`: now just takes `{ license_key }` (no more site_id/api_key)
- PHP class-settings.php: removed `wally_api_key` option; calls `/license/activate` on license key save; shows admin notice with tier + site count
- PHP class-rest-controller.php: all calls use `wally_license_key` option, `X-License-Key` header
- Migration `005_make_api_key_hash_nullable.sql`: makes api_key_hash nullable, adds perf indexes
- `SKIP_LICENSE_VALIDATION=true` still works — returns mock pro tier

## User Dashboard Implementation (2026-03-01)
- DB migration `002_user_schema.sql`: `license_keys` table (user_id, key, tier, max_sites, expires_at, status), alter `sites` (add user_id, license_key_id, activated_at), RLS policies, trigger auto-creates free license key on user signup
- Next.js API routes: `GET /api/user/license`, `DELETE /api/user/sites/[siteId]`
- License page is async server component — fetches Supabase directly, passes props to client components
- App layout fetches license tier server-side → passes `showUpgradeBanner` to AppSidebar
- Account form uses Supabase `user_metadata` for first_name/last_name (no profiles table needed)
- Subscriptions page fetches tier client-side from `/api/user/license`, shows "Current Plan" + "Your plan" badge

## NestJS Backend Migration Status (2026-02-28)
Express→NestJS migration is essentially complete. Phases 1-6.2 done:
- All services: IntentClassifier, KnowledgeLoader, PromptBuilder, MessageBuilder, ResponseValidator, ToolDefinitions, LlmService (Anthropic+OpenAI)
- All controllers: ChatController, ToolResultController, LicenseController, UsageController, HealthController
- 32 unit tests passing — Jest config at `apps/backend/jest.config.cts` using ts-jest+moduleNameMapper
- Build: `npx nx build backend` compiles cleanly, lint has only non-null assertion warnings
- Knowledge files: ~63 .md files at `apps/backend/src/knowledge/`
- Remaining: task 7.3 (remove old `backend/` dir) requires manual `rm -rf backend/`; tasks 6.3/6.4 (integration+e2e tests) deferred
