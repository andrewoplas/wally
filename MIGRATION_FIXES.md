# Migration Fixes — Partial & Missing Items

Each item below describes **what to fix**, **which NestJS file to edit**, and **how the original Express backend did it** (with file references).

**Original Express backend:** `/Users/andrewoplas/Local Sites/goodtime/app/public/backend`

---

## Partial Items (13)

---

### 1. `GET /health` — Wrong response shape

**NestJS file:** `apps/backend/src/health/health.controller.ts`

**Problem:** Returns `{ status, timestamp }` instead of `{ status, version }`.

**Original:** `src/index.js:26-28`
```js
app.get('/health', (_req, res) => {
  res.json({ status: 'ok', version: '0.1.0' });
});
```

**Fix:** Change the health controller to return `version` instead of `timestamp`:
```ts
@Get()
check(): { status: string; version: string } {
  return {
    status: 'ok',
    version: '0.1.0',
  };
}
```

---

### 2. `POST /api/v1/license/validate` — 501 stub in production

**NestJS file:** `apps/backend/src/license/license.controller.ts`

**Problem:** Dev bypass works correctly; production path returns `501 NOT_IMPLEMENTED`. This matches the original — it is expected behavior for now.

**Original:** `src/routes/license.js:16-44`
```js
if (config.skipLicenseValidation) {
  return res.json({
    valid: true,
    tier: 'pro',
    features: {
      max_messages_per_day: config.rateLimitPerSitePerDay,
      models_available: Object.keys(config.models),
      tool_categories: ['content', 'site', 'plugins', 'search', 'elementor'],
    },
    expires_at: null,
  });
}
// ...
return res.status(501).json({
  error: 'not_implemented',
  message: 'License validation is not yet configured for production',
});
```

**Fix:** The NestJS implementation already matches the original. The dev bypass response shape and the 501 stub body are identical. No code change needed — mark as **expected stub** in the checklist.

---

### 3. Rate limiter 429 — Missing `Retry-After` HTTP header

**NestJS file:** `apps/backend/src/common/guards/rate-limiter.guard.ts`

**Problem:** `retry_after` is only in the JSON body; the HTTP `Retry-After` header is never set.

**Original:** `src/middleware/rate-limiter.js:50-66` — The original also does NOT set the `Retry-After` header. It only includes `retry_after` in the JSON body:
```js
return res.status(429).json({
  error: 'rate_limit_exceeded',
  message: `Rate limit exceeded: ${config.rateLimitPerSitePerMinute} requests per minute`,
  retry_after: 60,
});
```

**Fix:** The NestJS implementation matches the original behavior exactly. If you want to add the HTTP header (improvement over original), you'd need to access the response object in the guard via `ExecutionContext`:
```ts
const res = context.switchToHttp().getResponse<Response>();
res.setHeader('Retry-After', '60');
```
Otherwise, mark as **matches original** — no fix needed.

---

### 4. `PORT` default — Wrong default value

**NestJS files:** `apps/backend/src/config/configuration.ts:18` and `apps/backend/src/main.ts:51`

**Problem:** Defaults to `3000`; correct default is `3100`.

**Original:** `src/config.js:4`
```js
port: parseInt(process.env.PORT || '3100', 10),
```

**Fix:** In `configuration.ts`, change:
```ts
port: parseInt(process.env['PORT'] ?? '3000', 10),
// → change to:
port: parseInt(process.env['PORT'] ?? '3100', 10),
```

In `main.ts`, change:
```ts
const port = process.env['PORT'] ?? 3000;
// → change to:
const port = process.env['PORT'] ?? 3100;
```

---

### 5. `ENABLE_THINKING` — Hardcoded, not wired to env

**NestJS file:** `apps/backend/src/llm/llm.service.ts:45-48`

**Problem:** `const ENABLE_THINKING = false` is hardcoded and ignores the environment variable.

**Original:** `src/services/llm.js:57-60` — also hardcodes it:
```js
const ENABLE_THINKING = false;
const THINKING_BUDGET = 6000;
const modelSupportsThinking = (modelId) => ENABLE_THINKING && !modelId.includes('haiku');
```

**Fix:** Wire it through the config service. In `apps/backend/src/config/configuration.ts`, add `enableThinking` to `WallyConfig` interface and config factory:
```ts
enableThinking: process.env['ENABLE_THINKING'] === 'true',
```

In `llm.service.ts`, replace the hardcoded constant with a config read in the constructor or method:
```ts
const enableThinking = this.config.get<boolean>('enableThinking', false);
```

---

### 6. Anthropic extended thinking — Permanently disabled

**NestJS file:** `apps/backend/src/llm/llm.service.ts`

**Problem:** All thinking code exists but can never execute because `ENABLE_THINKING` is always `false`.

**Fix:** Automatically resolved by fixing item 5 above. Once `ENABLE_THINKING` is read from env, setting `ENABLE_THINKING=true` in `.env.local` will activate the thinking blocks. No additional code changes needed.

---

### 7. SSE events `thinking`, `thinking_start`, `thinking_end` — Dead code

**NestJS file:** `apps/backend/src/llm/llm.service.ts`

**Problem:** SSE emit logic for thinking events exists but can never fire because `ENABLE_THINKING` is always `false`.

**Fix:** Same as items 5 and 6 — resolves automatically once `ENABLE_THINKING` is wired to the env var.

---

### 8. Normalized `usage` for OpenAI — Always `null`

**NestJS file:** `apps/backend/src/llm/llm.service.ts` (OpenAI branch)

**Problem:** OpenAI streaming doesn't return token counts in chunks, so `usage` is `null`.

**Original:** `src/services/llm.js:208-213` — same behavior:
```js
return {
  content,
  model: modelId,
  usage: null, // OpenAI streaming doesn't return usage in chunks
  stop_reason: toolCalls.length > 0 ? 'tool_use' : 'end_turn',
};
```

**Fix:** This matches the original and is a known OpenAI streaming limitation. No fix needed — mark as **by design**. If token counts are needed later, use `stream_options: { include_usage: true }` in the OpenAI SDK call.

---

### 9. Dev CORS — Accepts all origins instead of localhost only

**NestJS file:** `apps/backend/src/main.ts:20-27`

**Problem:** `origin: true` accepts all origins. The original restricts dev to `localhost` and `127.0.0.1`.

**Original:** `src/index.js:15-20`
```js
const DEV_ORIGINS = /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/;
app.use(cors({
  origin: config.nodeEnv === 'production' ? false : DEV_ORIGINS,
  methods: ['GET', 'POST'],
  allowedHeaders: ['Content-Type', 'X-Site-ID', 'X-API-Key'],
}));
```

**Fix:** In `main.ts`, replace the CORS setup:
```ts
const DEV_ORIGINS = /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/;

app.enableCors({
  origin: process.env['NODE_ENV'] === 'production' ? false : DEV_ORIGINS,
  methods: ['GET', 'POST'],
  allowedHeaders: ['Content-Type', 'X-Site-ID', 'X-API-Key'],
});
```

---

### 10. `intent-classifier` tests — File extension / naming

**NestJS file:** `apps/backend/src/intent/intent-classifier.service.spec.ts`

**Problem:** Test file exists as `.spec.ts` with 10 tests instead of `.test.js`. This is **correct for NestJS** — `.spec.ts` is the NestJS/Jest convention.

**Original:** `tests/unit/intent-classifier.test.js` (443 lines)

**Fix:** No naming change needed. However, the original has significantly more test cases covering all 70+ intent patterns. The NestJS spec has only 10 tests — consider expanding coverage to match the original.

---

### 11. `response-validator` tests — File extension / naming

**NestJS file:** `apps/backend/src/common/response-validator.service.spec.ts`

**Problem:** Same as item 10 — `.spec.ts` with 8 tests is correct NestJS convention.

**Original:** `tests/unit/response-validator.test.js` (68 lines)

**Fix:** No naming change needed. Confirm the 8 tests cover all 5 validation rules from the original.

---

### 12. `tool-result-messages` tests — File naming

**NestJS file:** `apps/backend/src/common/message-builder.service.spec.ts`

**Problem:** Named `message-builder.service.spec.ts` (6 tests) instead of `tool-result-messages.test.js`. `.spec.ts` is correct for NestJS.

**Original:** `tests/unit/tool-result-messages.test.js` (89 lines)

**Fix:** No naming change needed. Confirm the 6 tests cover the same scenarios from the original (PHP empty array normalization, `pending_tool_calls` fallback, multi-tool scenarios).

---

### 13. CORS allowed methods — Includes `OPTIONS`

**NestJS file:** `apps/backend/src/main.ts:25`

**Problem:** `OPTIONS` is included in allowed methods; the original only specifies `GET` and `POST`.

**Original:** `src/index.js:18`
```js
methods: ['GET', 'POST'],
```

**Fix:** In `app.enableCors()`, change to:
```ts
methods: ['GET', 'POST'],
```
Note: NestJS/Express CORS middleware may still handle `OPTIONS` preflight automatically under the hood, which is standard browser behavior. Removing `OPTIONS` from the explicit list simply means not advertising it, but it won't break preflight.

---

## Missing Items (7)

---

### 1. Body size limit: 1MB

**NestJS file:** `apps/backend/src/main.ts`

**Original:** `src/index.js:23`
```js
app.use(express.json({ limit: '1mb' }));
```

**Fix:** In `main.ts`, add before `app.listen()`:
```ts
import { json } from 'express';

app.use(json({ limit: '1mb' }));
```
And disable the built-in body parser to avoid double-parsing:
```ts
const app = await NestFactory.create(AppModule, {
  bodyParser: false,  // we configure our own below
});
// ...
app.use(json({ limit: '1mb' }));
```

---

### 2. Global error handler — No `ExceptionFilter`

**NestJS file:** Create `apps/backend/src/common/filters/all-exceptions.filter.ts`, register in `apps/backend/src/main.ts`

**Original:** `src/index.js:35-38`
```js
app.use((err, _req, res, _next) => {
  logger.error('Unhandled error', { error: err.message, stack: err.stack });
  res.status(500).json({ error: 'internal_error', message: 'An unexpected error occurred' });
});
```

**Fix:** Create the filter:
```ts
// apps/backend/src/common/filters/all-exceptions.filter.ts
import {
  ExceptionFilter,
  Catch,
  ArgumentsHost,
  HttpException,
  HttpStatus,
} from '@nestjs/common';
import { WallyLoggerService } from '../logger/wally-logger.service.js';

@Catch()
export class AllExceptionsFilter implements ExceptionFilter {
  constructor(private readonly logger: WallyLoggerService) {}

  catch(exception: unknown, host: ArgumentsHost) {
    const ctx = host.switchToHttp();
    const response = ctx.getResponse();

    const status =
      exception instanceof HttpException
        ? exception.getStatus()
        : HttpStatus.INTERNAL_SERVER_ERROR;

    if (status === HttpStatus.INTERNAL_SERVER_ERROR) {
      this.logger.logWithMeta('error', 'Unhandled error', {
        error: exception instanceof Error ? exception.message : String(exception),
        stack: exception instanceof Error ? exception.stack : undefined,
      });
    }

    // For known HTTP exceptions, let NestJS's response pass through
    if (exception instanceof HttpException) {
      response.status(status).json(exception.getResponse());
      return;
    }

    // For unexpected errors, return a generic message (matching original)
    response.status(500).json({
      error: 'internal_error',
      message: 'An unexpected error occurred',
    });
  }
}
```

Register in `main.ts`:
```ts
import { AllExceptionsFilter } from './common/filters/all-exceptions.filter.js';

// After app creation, get the logger from the DI container:
const logger = app.get(WallyLoggerService);
app.useGlobalFilters(new AllExceptionsFilter(logger));
```

---

### 3. `knowledge-loader` tests — No test file

**NestJS file:** Create `apps/backend/src/knowledge/knowledge-loader.service.spec.ts`

**Original:** `tests/unit/knowledge-loader.test.js` (165 lines)

The original tests cover:
1. All 63 knowledge files loaded (`hasKnowledge(key)` for each)
2. Total file count is 63
3. `getKnowledgeForIntents(['general'])` returns non-empty string (>100 chars)
4. Combined intents return longer content than single
5. Unknown intent returns empty string
6. 47 content quality spot-checks — each intent key contains an expected keyword/regex (e.g., `elementor` contains `_elementor_data`, `acf` contains `get_field`, etc.)

**Fix:** Port the original test structure to NestJS. Use `Test.createTestingModule()` to instantiate `KnowledgeLoaderService` and mirror all test cases from the original.

---

### 4. `prompt-builder` tests — No test file

**NestJS file:** Create `apps/backend/src/knowledge/prompt-builder.service.spec.ts`

**Original:** `tests/unit/prompt-builder.test.js` (172 lines)

The original tests cover (20 test cases):
1. No-pre-tool rule: forbids explanatory text before tool calls
2. No-greet rule: forbids greeting/self-introduction
3. Plugin path hint: `slug/slug.php` pattern
4. Destructive-action rule: call tool directly, don't ask first
5. Confirmation-pending rule: neutral sentence when awaiting confirm
6. No schema leak rule: forbids revealing tool schemas
7. Custom prompt appended after `--- Custom Instructions ---` separator
8. Site profile injection: `wp_version`, `php_version`, post types
9. Knowledge injection when user message provided
10. General knowledge present even without user message
11. Elementor knowledge for Elementor-related messages
12. ACF knowledge for ACF-related messages
13. Plugin knowledge for plugin-related messages
14. Conversation context used for intent classification
15. Taxonomies rendering
16. Menus rendering
17. ACF field groups rendering
18. Front page info rendering
19. Active plugins summary rendering
20. Post types with counts rendering

**Fix:** Port all 20 test cases to NestJS spec format using `Test.createTestingModule()`.

---

### 5. `golden-conversations` integration tests — No integration test file

**NestJS file:** Create `apps/backend/test/golden-conversations.e2e-spec.ts`

**Original:** `tests/integration/golden-conversations.test.js` (196 lines)

The original loads 4 JSON fixture files from `tests/fixtures/conversations/`:
- `update-plugin-success.json` — Plugin update succeeds immediately
- `update-plugin-pending.json` — Plugin delete awaits confirmation
- `list-plugins.json` — Read-only list, no confirmation
- `multi-tool-loop.json` — Two sequential tools (search → replace)

For each fixture, the original runs 6 tests:
1. System prompt builds successfully
2. Message pipeline produces assistant `tool_use` + user `tool_result` blocks
3. User history message appears before assistant `tool_use` block
4. Final LLM text satisfies response validator expectation
5. Final text contains all required strings
6. Final text contains no forbidden strings

Plus 1 standalone test:
7. Multi-tool-loop: second `buildToolResultMessages` call includes first tool result

**Fix:** Copy fixture files from the original to `apps/backend/test/fixtures/conversations/` and port the integration test to NestJS e2e format. The tests use `buildToolResultMessages`, `buildSystemPrompt`, and `validateResponse` directly — no HTTP calls or LLM API needed.

---

### 6. 178 total tests — Only ~30 found

**Context:** The original Express backend has ~178 tests across 6 test files. The NestJS migration has ~30 tests across 4 spec files.

The gap breakdown:
- `knowledge-loader` — 0 tests (original has ~52 including 47 content spot-checks)
- `prompt-builder` — 0 tests (original has 20)
- `golden-conversations` — 0 tests (original has 25: 6 per fixture × 4 + 1)
- `intent-classifier` — 10 tests (could expand to cover all 46+ intent categories)
- `response-validator` — 8 tests (close to original's count)
- `message-builder` — 6 tests (close to original's count)
- `auth.guard` — 6 tests (NestJS-specific, no original equivalent)

**Fix:** Adding the 3 missing test files (items 3, 4, 5) adds ~97 tests. Expanding `intent-classifier` coverage to match all categories adds ~36 more. This would bring the total close to the original 178.

---

### 7. ESM support — Jest using CJS mode

**NestJS file:** `apps/backend/jest.config.cts`

**Original:** `package.json`
```json
"test": "node --experimental-vm-modules node_modules/.bin/jest"
```
With `"type": "module"` in `package.json` and `"transform": {}` in Jest config (no transpilation — runs native ESM).

**Current NestJS config:** `jest.config.cts` uses `ts-jest` with `useESM: false` (CJS transform mode):
```ts
transform: {
  '^.+\\.[tj]s$': ['ts-jest', { tsconfig: '<rootDir>/tsconfig.spec.json', useESM: false }],
},
```

**Fix:** For a TypeScript NestJS project, CJS mode with `ts-jest` is the standard approach and works correctly. The original needed `--experimental-vm-modules` because it runs native ESM (`.js` files with `"type": "module"`). Since NestJS uses TypeScript that gets transpiled by `ts-jest`, ESM flags are unnecessary unless importing ESM-only packages. **No fix needed** — mark as **not applicable to NestJS/TypeScript setup**.
