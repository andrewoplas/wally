# Ralph Fix Plan — Express → NestJS Migration

## Phase 1: Foundation (High Priority)

- [x] **1.1 Set up ConfigModule** — Created `apps/backend/src/config/configuration.ts` with typed `WallyConfig` interface. Registered `ConfigModule.forRoot({ isGlobal: true })` in `AppModule`. Loads `.env.local` / `.env`.
- [x] **1.2 Create LoggerService** — Created `apps/backend/src/common/logger/wally-logger.service.ts`. Implements NestJS `LoggerService` interface with structured JSON output and level filtering.
- [x] **1.3 Create AuthGuard** — Created `apps/backend/src/common/guards/auth.guard.ts`. Reads `X-Site-ID` / `X-API-Key` headers or body. Attaches `siteId`/`apiKey` to request. Supports `SKIP_LICENSE_VALIDATION` dev mode.
- [x] **1.4 Create RateLimiterGuard** — Created `apps/backend/src/common/guards/rate-limiter.guard.ts`. In-memory sliding-window rate limiter with per-minute and per-day limits. Evicts stale windows every hour.

## Phase 2: Core Services (High Priority)

- [x] **2.1 Port ToolDefinitionsService** — Convert `backend/src/services/tool-definitions.js` to TypeScript. Define interfaces for tool schemas. Implement `getToolsForProvider('anthropic' | 'openai')` method. 30+ WordPress tool definitions with `requires_confirmation` flags.
- [x] **2.2 Port IntentClassifierService** — Convert `backend/src/services/intent-classifier.js` to TypeScript. Regex-based intent classification returning intent keys. Conversation context awareness (last 2 messages). Max 4 intents cap.
- [x] **2.3 Port KnowledgeLoaderService** — Converted to `apps/backend/src/knowledge/knowledge-loader.service.ts`. Copied all 63 `*.md` knowledge files to `apps/backend/src/knowledge/`. Loads at startup into memory cache. `getKnowledgeForIntents(intents)` method.
- [x] **2.4 Port PromptBuilderService** — Converted to `apps/backend/src/knowledge/prompt-builder.service.ts`. Depends on IntentClassifier + KnowledgeLoader. Builds system prompt with: base instructions, intent-based knowledge, site context, custom instructions.
- [x] **2.5 Port MessageBuilderService** — Converted to `apps/backend/src/common/message-builder.service.ts`. `buildToolResultMessages()` — constructs Anthropic-formatted message array with tool_use + tool_result blocks. Handle PHP empty-object-as-array normalization.
- [x] **2.6 Port ResponseValidatorService** — Converted to `apps/backend/src/common/response-validator.service.ts`. Heuristic checks: contradictory pre-action/ask, unexpected trigger language, confirmation language on success, self-introduction.

## Phase 3: LLM Integration (High Priority)

- [x] **3.1 Port LLM Service — Anthropic provider** — Converted Anthropic streaming logic in `apps/backend/src/llm/llm.service.ts`. SSE streaming with `content_block_start`, `content_block_delta`, `content_block_stop` events. Extended thinking support (configurable). Uses `@anthropic-ai/sdk`.
- [x] **3.2 Port LLM Service — OpenAI provider** — Converted OpenAI streaming logic in same file. Normalises OpenAI response format to Anthropic-like structure (text + tool_use blocks). Uses `openai` SDK.
- [x] **3.3 Create unified LlmService** — `sendToLLM({ model, systemPrompt, messages, res })` dispatcher. Model → provider routing from config. Injects ToolDefinitionsService for tool schemas.

## Phase 4: Controllers (High Priority)

- [x] **4.1 Create ChatController** — `POST /v1/chat` — Validates request body, sets up SSE headers. Calls PromptBuilder → LlmService → streams tool_call/usage/done events. Applies AuthGuard + RateLimiterGuard.
- [x] **4.2 Create ToolResultController** — `POST /v1/tool-result` — Validates tool_results array. Rebuilds message history with MessageBuilder. Continues LLM tool-use loop. Streams SSE response.
- [x] **4.3 Create LicenseController** — `POST /v1/license/validate` — Dev mode mock response. Production TODO placeholder.
- [x] **4.4 Create UsageController** — `GET /v1/usage/:site_id` — In-memory usage tracking with `recordUsage()`. Site isolation (can only view own usage).
- [x] **4.5 Create HealthController** — `GET /health` — Simple health check endpoint (no auth required).

## Phase 5: Module Organization (Medium Priority)

- [x] **5.1 Create feature modules** — Organized into NestJS modules: `ChatModule`, `LicenseModule`, `UsageModule`, `LlmModule`, `KnowledgeModule`. Wired up dependencies via module imports/exports.
- [x] **5.2 Configure main.ts** — Set global prefix `/api`, CORS config (dev origins only), JSON body limit, global ValidationPipe.
- [x] **5.3 Add DTOs and validation** — Created `ChatRequestDto` and `ToolResultRequestDto` with `class-validator` decorators. `ValidationPipe` applied globally and per-controller.

## Phase 6: Testing (Medium Priority)

- [x] **6.1 Unit tests for services** — Tests for IntentClassifierService, MessageBuilderService, ResponseValidatorService in `apps/backend/src/`.
- [ ] **6.2 Unit tests for guards** — Test AuthGuard and RateLimiterGuard with mock requests.
- [ ] **6.3 Integration tests for controllers** — Test ChatController and ToolResultController with mocked LLM service. Verify SSE event format.
- [ ] **6.4 E2e tests** — Add tests in `apps/backend-e2e/` for the full request lifecycle.

## Phase 7: Cleanup (Low Priority)

- [x] **7.1 Install NestJS dependencies** — `@nestjs/config`, `@anthropic-ai/sdk`, `openai`, `class-validator`, `class-transformer` all installed at workspace root.
- [ ] **7.2 Update CLAUDE.md** — Verify architecture docs match the migrated NestJS structure.
- [x] **7.3 Remove old Express backend** — MANUAL TASK: Owner will delete `backend/` directory after verifying `apps/backend/`. Do NOT use rm -rf.
- [x] **7.4 Update .env.example** — Created `apps/backend/.env.example` with all required variables including `KNOWLEDGE_DIR`.

## Completed
- [x] Project enabled for Ralph
- [x] NestJS scaffold created at `apps/backend/` via Nx
- [x] Ralph documents updated for Express → NestJS migration
- [x] Phase 1 Foundation: ConfigModule, WallyLoggerService, AuthGuard, RateLimiterGuard
- [x] `@nestjs/config` installed at workspace root
- [x] `main.ts` updated: CORS config, body parser, structured logging bootstrap
- [x] `AppModule` updated: imports ConfigModule globally, provides logger + guards
- [x] All 63 knowledge `.md` files present in `apps/backend/src/knowledge/`
- [x] Build passes: `npx nx build backend` compiles successfully

## Notes
- The global API prefix is `/api` (set in `main.ts`), so routes are `/api/v1/chat`, etc.
- SSE streaming is critical — the WordPress plugin expects `text/event-stream` responses
- Knowledge files (~63 .md files) are in `apps/backend/src/knowledge/` and copied to dist at build
- The old Express backend at `backend/` is pending deletion (task 7.3)
- Focus on correctness over optimization — match existing behavior first, refactor later
