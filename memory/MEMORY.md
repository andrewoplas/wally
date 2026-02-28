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

## NestJS Backend Migration Status (2026-02-28)
Express→NestJS migration is essentially complete. Phases 1-6.2 done:
- All services: IntentClassifier, KnowledgeLoader, PromptBuilder, MessageBuilder, ResponseValidator, ToolDefinitions, LlmService (Anthropic+OpenAI)
- All controllers: ChatController, ToolResultController, LicenseController, UsageController, HealthController
- 32 unit tests passing — Jest config at `apps/backend/jest.config.cts` using ts-jest+moduleNameMapper
- Build: `npx nx build backend` compiles cleanly, lint has only non-null assertion warnings
- Knowledge files: ~63 .md files at `apps/backend/src/knowledge/`
- Remaining: task 7.3 (remove old `backend/` dir) requires manual `rm -rf backend/`; tasks 6.3/6.4 (integration+e2e tests) deferred
