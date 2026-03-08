# Wally — AI Intelligence Technical Specification

**Version:** 1.1
**Date:** March 8, 2026
**Status:** Ready for Developer Handoff
**Product:** Wally — WordPress AI Assistant Plugin
**Scope:** 5 targeted fixes to the AI intelligence layer

---

> ## ⚠️ Important Note on Code Samples
>
> **All code in this document is illustrative pseudocode — not production-ready code.**
>
> Code samples reference real file paths, class names, and method signatures from the actual codebase, but they are **blueprints, not patches**. The developer must integrate each sample into the existing code rather than copy-pasting directly. Always review against the actual file before making changes.

---

## Codebase Map (Relevant Files)

For reference, here are the actual files each fix touches:

| File | Location | Role |
|------|----------|------|
| `chat.controller.ts` | `apps/backend/src/chat/` | Receives chat request, builds messages, calls LLmService |
| `tool-result.controller.ts` | `apps/backend/src/chat/` | Handles tool result loop |
| `prompt-builder.service.ts` | `apps/backend/src/knowledge/` | Assembles system prompt from all parts |
| `intent-classifier.service.ts` | `apps/backend/src/intent/` | Regex-based intent classification |
| `llm.service.ts` | `apps/backend/src/llm/` | Sends request to Anthropic/OpenAI, streams response |
| `configuration.ts` | `apps/backend/src/config/` | Model config, default model env var |
| `class-rest-controller.php` | `apps/wally/includes/` | WordPress-side: fetches history, forwards to backend |
| `class-tool-executor.php` | `apps/wally/includes/` | Validates and executes tool calls against WordPress |
| `class-audit-log.php` | `apps/wally/includes/` | Already logs tool executions to `wp_wally_actions` |
| `class-database.php` | `apps/wally/includes/` | DB table setup |

---

## Executive Summary

After reviewing the actual codebase, the architecture is well-built. The tool-use loop, stateless backend, DB-persisted history, SSE streaming, and audit logging are all solid. These five fixes address the specific gaps causing the "clueless" behavior, ranked by impact.

| # | Fix | Impact | Effort | Priority |
|---|-----|--------|--------|----------|
| 1 | Structured Action Memory | Highest | Medium | P0 — Critical |
| 2 | Smart Tool Result Summarization | High | Medium | P0 — Critical |
| 3 | Context Budget Audit & Guardrails | High | Low | P1 — High |
| 4 | Default Model: Sonnet | High | Trivial | P1 — High |
| 5 | Intent Classifier Improvement | Medium | High | P2 — Medium |

---

## Fix 1 — Structured Action Memory

**Impact:** Highest | **Effort:** Medium | **Priority:** P0 — Critical

### Problem

When a user says "undo what you just did" or "what plugins did you install?" the model reconstructs what happened from raw conversation text. There is no structured record of what was actually executed.

**Good news:** `class-audit-log.php` already logs every tool execution to `wp_wally_actions`. The data exists — it just isn't being fed back into the system prompt. Fix 1 is mostly about reading from this existing table and injecting a summary into `PromptBuilderService`.

### Solution

1. On each chat request, the plugin reads the last N actions from `wp_wally_actions` for the current conversation and includes them in the payload sent to the backend.
2. `PromptBuilderService` receives the action log and injects it as a compact block in the system prompt.

---

### 1.1 Read Recent Actions in `class-rest-controller.php`

The plugin already fetches history from the DB before forwarding to the backend. Add a similar fetch for recent actions from `wp_wally_actions`:

```php
// In handle_chat(), after fetching $history, add:
global $wpdb;
$actions_table = $wpdb->prefix . 'wally_actions';
$recent_actions = $wpdb->get_results( $wpdb->prepare(
    "SELECT tool_name, tool_input, tool_output, status, created_at
     FROM {$actions_table}
     WHERE conversation_id = %d AND status != 'pending'
     ORDER BY created_at DESC
     LIMIT 15",
    $conversation_id
), ARRAY_A );

// Reverse to chronological order (same as history)
$recent_actions = array_reverse( $recent_actions );
```

Then include `recent_actions` in the payload sent to the backend alongside `conversation_history`, `site_profile`, etc.

---

### 1.2 Add Action Summary Generator in `class-audit-log.php`

Add a static method to `AuditLog` that formats a raw action row into a one-line human-readable summary. No LLM call needed — this is deterministic:

```php
// Add to class-audit-log.php
public static function format_action_summary( array $action ): string {
    $tool   = $action['tool_name'];
    $input  = json_decode( $action['tool_input'] ?? '{}', true ) ?: [];
    $status = strtoupper( $action['status'] );
    $time   = $action['created_at'];

    $summaries = [
        'install_plugin'    => "Installed plugin: " . ( $input['slug'] ?? 'unknown' ),
        'activate_plugin'   => "Activated plugin: " . ( $input['slug'] ?? 'unknown' ),
        'deactivate_plugin' => "Deactivated plugin: " . ( $input['slug'] ?? 'unknown' ),
        'delete_plugin'     => "Deleted plugin: " . ( $input['slug'] ?? 'unknown' ),
        'update_post'       => "Updated post #{$input['post_id']}: " . ( $input['field'] ?? 'content' ),
        'create_post'       => "Created post: " . ( $input['title'] ?? 'untitled' ),
        'trash_post'        => "Trashed post: " . ( $input['title'] ?? "#{$input['post_id']}" ),
        'search_replace'    => "Search/replaced '{$input['search']}' → '{$input['replace']}' in " . ( $input['scope'] ?? 'content' ),
        'update_option'     => "Updated site option: " . ( $input['option_name'] ?? 'unknown' ),
    ];

    $description = $summaries[ $tool ] ?? "{$tool} executed";
    return "[{$time}] {$status} — {$description}";
}
```

---

### 1.3 Inject Action Memory in `prompt-builder.service.ts`

Update `buildSystemPrompt()` to accept and render the action log. The method signature already accepts `siteProfile`, `customPrompt`, `userMessage`, and `conversationHistory` — add `recentActions` as a fifth parameter:

```typescript
// prompt-builder.service.ts
buildSystemPrompt(
  siteProfile?: SiteProfile | null,
  customPrompt?: string | null,
  userMessage?: string | null,
  conversationHistory?: ConversationMessage[] | null,
  recentActions?: string[] | null,  // ADD THIS — array of formatted summary strings
): string {
  // ... existing prompt assembly ...

  // Add after Site Context, before Custom Instructions:
  if (recentActions && recentActions.length > 0) {
    parts.push('', '--- Recent Actions Taken ---');
    parts.push('Use this to answer questions like "what did you just do?" or "undo that".');
    parts.push(...recentActions);
  }
}
```

The `recentActions` array is the pre-formatted strings from `AuditLog::format_action_summary()`, serialized in the plugin payload.

**Expected output in system prompt:**
```
--- Recent Actions Taken ---
Use this to answer questions like "what did you just do?" or "undo that".
[2026-03-08 14:32:01] SUCCESS — Installed plugin: yoast-seo
[2026-03-08 14:33:15] SUCCESS — Activated plugin: yoast-seo
[2026-03-08 14:35:42] SUCCESS — Updated post #42: title
```

### Acceptance Criteria

- [ ] `class-rest-controller.php` fetches the last 15 actions from `wp_wally_actions` for the current conversation and includes them in the backend payload
- [ ] `AuditLog::format_action_summary()` produces a single-line string per action, no LLM call
- [ ] `buildSystemPrompt()` accepts `recentActions` and injects a `--- Recent Actions Taken ---` block
- [ ] Block adds no more than ~400 tokens to the system prompt
- [ ] Assistant correctly answers "what plugins did you install?" without hallucinating

---

## Fix 2 — Smart Tool Result Summarization

**Impact:** High | **Effort:** Medium | **Priority:** P0 — Critical

### Problem

In `chat.controller.ts`, conversation history is truncated at `MAX_HISTORY_CONTENT = 4_000` chars per entry (line defined at top of file). When a tool returns a large payload — plugin lists, post content, Elementor data — it gets hard-cut mid-record. The model then reads a broken fragment on the next turn.

```typescript
// chat.controller.ts — current behavior (the problem)
const content =
  typeof msg.content === 'string'
    ? msg.content.slice(0, MAX_HISTORY_CONTENT)  // ← hard truncation
    : '';
```

### Solution

Summarize tool results **before they are stored in `wp_wally_messages`** on the PHP side. This means what goes into the DB is always a clean, complete summary rather than a raw truncated blob. The current-loop tool result (passed back to the LLM via `tool-result.controller.ts`) still uses the full raw result — summarization only affects persistence.

---

### 2.1 Add `class-result-summarizer.php` (new file in `apps/wally/includes/`)

```php
<?php
namespace Wally;

class ResultSummarizer {

    public static function summarize( string $tool_name, array $result ): string {
        switch ( $tool_name ) {

            case 'list_plugins':
                $plugins      = $result['plugins'] ?? [];
                $total        = count( $plugins );
                $active_names = array_column(
                    array_filter( $plugins, fn($p) => $p['active'] ?? false ),
                    'name'
                );
                $active_count = count( $active_names );
                $preview      = implode( ', ', array_slice( $active_names, 0, 5 ) );
                return "{$total} plugins found, {$active_count} active. Active (first 5): {$preview}.";

            case 'list_posts':
            case 'search_posts':
                $posts  = $result['posts'] ?? [];
                $count  = count( $posts );
                $titles = implode( ', ', array_column( array_slice( $posts, 0, 5 ), 'title' ) );
                return "{$count} posts returned. First 5: {$titles}.";

            case 'get_post':
            case 'get_post_content':
                $words = str_word_count( strip_tags( $result['content'] ?? '' ) );
                return "Post #{$result['id']} \"{$result['title']}\" retrieved. {$words} words.";

            case 'search_replace':
                $count = $result['replacements'] ?? $result['count'] ?? 0;
                $scope = $result['scope'] ?? 'content';
                return "Search/replace completed: {$count} replacement(s) in {$scope}.";

            case 'install_plugin':
                return "Plugin \"{$result['name']}\" installed. Version: {$result['version']}.";

            case 'create_post':
            case 'update_post':
                return "Post #{$result['id']} \"{$result['title']}\" saved successfully.";

            case 'get_site_info':
            case 'get_option':
                // These are small — encode as-is up to 300 chars
                $json = wp_json_encode( $result );
                return strlen( $json ) > 300 ? substr( $json, 0, 297 ) . '...' : $json;

            default:
                $json = wp_json_encode( $result );
                return strlen( $json ) > 400 ? substr( $json, 0, 397 ) . '...' : $json;
        }
    }
}
```

---

### 2.2 Wire Summarizer to DB Write in `class-rest-controller.php`

Find where the assistant message (tool result) is stored in `wp_wally_messages` and wrap the content with the summarizer before writing:

```php
// Before storing the assistant/tool-result message in wp_wally_messages:
$summary = ResultSummarizer::summarize( $tool_name, $tool_result_array );

// Store $summary instead of the raw $tool_result string
```

The exact location in `class-rest-controller.php` will be where the tool result is persisted after execution. The key rule: **raw result goes to the LLM loop, summary goes to the DB**.

---

### 2.3 Do NOT Change `chat.controller.ts` Truncation

The `MAX_HISTORY_CONTENT = 4_000` truncation in `chat.controller.ts` can remain as a safety net. With summarization in place, stored tool results will be well under 400 chars, so the truncation will never trigger on tool results. It still protects against oversized user/assistant text messages.

| | Before | After |
|---|---|---|
| Stored in DB | 4,000-char hard cut of raw result — may be mid-record | Clean ≤400 char summary, always complete |
| Token cost in history | ~1,000 tokens per tool result | ~50–100 tokens per tool result |
| Model comprehension | Confused by broken data | Clear, complete summary |
| Current tool-use loop | Full raw result (unchanged) | Full raw result (unchanged) |

### Acceptance Criteria

- [ ] `ResultSummarizer::summarize()` exists and handles all major tool types
- [ ] Tool results in `wp_wally_messages` are summaries, never raw truncated blobs
- [ ] No stored tool result exceeds 400 characters
- [ ] The LLM still receives full raw results during the active tool-use loop
- [ ] The `MAX_HISTORY_CONTENT` truncation in `chat.controller.ts` is left as-is

---

## Fix 3 — Context Budget Audit & Guardrails

**Impact:** High | **Effort:** Low | **Priority:** P1 — High

### Problem

`PromptBuilderService.buildSystemPrompt()` assembles the system prompt from multiple dynamic sources with no token budget enforcement. On sites with many plugins, Elementor pages, ACF groups, and active knowledge chunks, the system prompt can grow very large and crowd out conversation history.

Currently there is no logging of how large the system prompt is on each request.

### Solution

Add per-section token estimation and hard caps inside `buildSystemPrompt()`. Log actual usage on every request so the team can see real numbers from production.

---

### 3.1 Add Token Budget Constants to `prompt-builder.service.ts`

```typescript
// prompt-builder.service.ts — add near the top
const TOKEN_BUDGET = {
  SYSTEM_TOTAL_MAX:    8_000,   // hard cap on entire system prompt
  KNOWLEDGE_CHUNKS:    2_500,   // intent-classified .md files
  SITE_CONTEXT:        2_000,   // WP version, plugins, theme, etc.
  ACTION_MEMORY:         400,   // recent actions log (Fix 1)
  CONTENT_STYLE:         600,   // recent post samples
  CUSTOM_INSTRUCTIONS:   300,   // custom_system_prompt from plugin settings
} as const;

function estimateTokens(text: string): number {
  return Math.ceil(text.length / 4);  // chars/4 is a safe approximation
}

function trimToTokenBudget(text: string, maxTokens: number): string {
  const maxChars = maxTokens * 4;
  if (text.length <= maxChars) return text;
  const cutPoint = text.lastIndexOf('\n', maxChars);
  return text.slice(0, cutPoint > 0 ? cutPoint : maxChars) + '\n[...trimmed for length]';
}
```

---

### 3.2 Apply Budget Enforcement in `buildSystemPrompt()`

The method currently pushes all sections into a `parts: string[]` array and joins them. Wrap the variable sections before they are pushed:

```typescript
// Knowledge chunks — currently injected without a cap
const knowledge = this.knowledgeLoader.getKnowledgeForIntents(intents);
const knowledgeTrimmed = trimToTokenBudget(knowledge ?? '', TOKEN_BUDGET.KNOWLEDGE_CHUNKS);

// Site context — currently built inline with no cap
// After assembling the site context string, trim it:
const siteContextStr = siteContextParts.join('\n');
const siteContextTrimmed = trimToTokenBudget(siteContextStr, TOKEN_BUDGET.SITE_CONTEXT);

// Content style reference — currently uncapped
const styleStr = styleLines.join('\n');
const styleTrimmed = trimToTokenBudget(styleStr, TOKEN_BUDGET.CONTENT_STYLE);

// Custom instructions — currently uncapped
const customTrimmed = trimToTokenBudget(customPrompt ?? '', TOKEN_BUDGET.CUSTOM_INSTRUCTIONS);

// Action memory (Fix 1) — already small, but cap for safety
const actionsTrimmed = trimToTokenBudget(actionsBlock, TOKEN_BUDGET.ACTION_MEMORY);
```

---

### 3.3 Log Token Usage Per Section

`LlmService` already logs `systemPromptChars` on every Anthropic request. Expand this to log per-section estimates inside `buildSystemPrompt()`. Use the `WallyLoggerService` or `Logger` that is already available:

```typescript
// At the end of buildSystemPrompt(), before returning:
const sections = {
  base_instructions: estimateTokens(basePart),
  knowledge: estimateTokens(knowledgeTrimmed),
  site_context: estimateTokens(siteContextTrimmed),
  action_memory: estimateTokens(actionsTrimmed),
  content_style: estimateTokens(styleTrimmed),
  custom: estimateTokens(customTrimmed),
};
const totalEstimate = Object.values(sections).reduce((a, b) => a + b, 0);

// Log as a single line for easy grep
console.log('[PromptBudget]', JSON.stringify({ ...sections, total: totalEstimate }));
```

### Acceptance Criteria

- [ ] `TOKEN_BUDGET` constants are defined at the top of `prompt-builder.service.ts`
- [ ] Knowledge chunks, site context, content style, and custom instructions are each trimmed before assembly
- [ ] Trimming uses newline-aware cutting, not mid-sentence truncation
- [ ] Token usage is logged per-section on every request (searchable in logs as `[PromptBudget]`)
- [ ] System prompt total reliably stays under 8,000 tokens on typical requests

---

## Fix 4 — Default Model: Sonnet

**Impact:** High | **Effort:** Trivial | **Priority:** P1 — High

### Current State

`configuration.ts` already has this:

```typescript
defaultModel: process.env['DEFAULT_MODEL'] ?? 'claude-sonnet-4-6',
```

`claude-sonnet-4-6` is already the default. **This fix may already be done** — verify by checking the `DEFAULT_MODEL` environment variable in the production/staging `.env` file.

### Action Required

Check the deployed `.env` on Railway/Vercel (wherever the backend is hosted):

```bash
# If DEFAULT_MODEL is set to haiku, change it:
DEFAULT_MODEL=claude-sonnet-4-6
```

If `DEFAULT_MODEL` is not set at all, the code already defaults to `claude-sonnet-4-6` — no change needed.

### Model Routing (Deferred)

A full Haiku/Sonnet routing system (routing simple reads to Haiku for cost savings) is deferred until after launch. At beta scale the cost difference is negligible. Add routing later once production usage data shows which request types are safe to downgrade.

### Acceptance Criteria

- [ ] Verify `DEFAULT_MODEL=claude-sonnet-4-6` is set in the deployed environment, or confirm it is not set (which defaults to Sonnet)
- [ ] Every request logs the model being used (`LlmService` already logs this as `model: modelId`)
- [ ] No code changes required if the env var is already correct

---

## Fix 5 — Intent Classifier Improvement

**Impact:** Medium | **Effort:** High | **Priority:** P2 — Medium

### Current State

`intent-classifier.service.ts` is a large, well-structured regex classifier with detailed pattern definitions. It covers many intents well. The gaps are: (1) common phrasings that don't match existing patterns, (2) no always-on knowledge injection, and (3) no fallback when classification misses.

### Solution

Two phases. Phase A is a quick win with no architecture changes. Phase B replaces the regex engine with an LLM call.

---

### Phase A — Expand Patterns + Always-On Knowledge (1–2 days)

**Step 1:** Add an `ALWAYS_ON_KNOWLEDGE` constant — a set of knowledge files injected on every request regardless of intent. These should be the files covering Wally capabilities, general WP basics, and confirmation flows:

```typescript
// knowledge-loader.service.ts (or intent-classifier.service.ts)
const ALWAYS_ON_KNOWLEDGE_KEYS = [
  'general',           // general WordPress knowledge
  'wally-capabilities', // what Wally can and can't do (if this file exists)
];
```

Then in `PromptBuilderService.buildSystemPrompt()`, merge always-on keys with classified intents before calling `knowledgeLoader.getKnowledgeForIntents()`:

```typescript
const classifiedIntents = this.intentClassifier.classifyIntent(userMessage, recentMessages);
const allIntents = [...new Set([...ALWAYS_ON_KNOWLEDGE_KEYS, ...classifiedIntents])];
const knowledge = this.knowledgeLoader.getKnowledgeForIntents(allIntents);
```

**Step 2:** In `intent-classifier.service.ts`, expand patterns to catch common synonyms that currently miss. Key gaps to fill:

```typescript
// Add to existing plugins intent patterns:
/\b(?:get rid of|remove|uninstall|deactivate)\b/i,
/\byoast|akismet|jetpack|wordfence|rankmath|elementor|woocommerce\b/i,  // plugin names as trigger

// Add to existing content intent patterns:
/\bfix\s+(?:my\s+)?(?:title|heading|text|copy|content)\b/i,
/\bchange\s+(?:the\s+)?(?:wording|language|text)\b/i,

// Add to existing SEO patterns (if seo intent exists):
/\bseo|meta\s+desc|search\s+ranking|google\b/i,
/\bfix\s+(?:my\s+)?seo\b/i,
```

---

### Phase B — LLM-Based Classification (1 week)

Replace the regex engine with a Haiku classification call. The interface to the rest of the system (`classifyIntent()` returning `string[]`) stays identical — this is a drop-in swap:

```typescript
// intent-classifier.service.ts
async classifyWithLLM(message: string, recentHistory: string[]): Promise<string[]> {
  const response = await this.anthropic.messages.create({
    model: 'claude-haiku-4-5-20251001',
    max_tokens: 100,
    system: `Classify the WordPress admin request into one or more intents.
Available intents: plugins, content, elementor, seo, settings, woocommerce,
gutenberg-blocks, page-templates, media, users, general.
Reply ONLY with a JSON array, e.g. ["plugins", "content"].
If unsure, return ["general"].`,
    messages: [
      ...recentHistory.slice(-3).map(h => ({ role: 'user' as const, content: h })),
      { role: 'user', content: message }
    ]
  });

  try {
    return JSON.parse(response.content[0].text) as string[];
  } catch {
    // Fall back to regex classifier on parse failure
    return this.classifyIntent(message, recentHistory);
  }
}
```

> **Implementation note:** Phase A is standalone and should ship immediately. Phase B can be a separate sprint. The `classifyIntent()` method is synchronous today — Phase B makes it `async`, which will require updating callers in `PromptBuilderService`. Plan for that change.

### Acceptance Criteria

**Phase A:**
- [ ] Always-on knowledge keys are merged into every classification before knowledge loading
- [ ] Plugin name synonyms (Yoast, Akismet, etc.) trigger the plugins intent
- [ ] "get rid of", "remove", "uninstall" trigger the plugins intent
- [ ] "fix my SEO" triggers the seo intent

**Phase B:**
- [ ] LLM classifier returns valid JSON array on ≥99% of requests
- [ ] On parse failure or timeout (>500ms), falls back to existing regex `classifyIntent()`
- [ ] `buildSystemPrompt()` updated to handle the now-async `classifyIntent()` call

---

## Implementation Roadmap

| Sprint | Fix | File(s) Touched | Est. Days |
|--------|-----|-----------------|-----------|
| Sprint 1 | Fix 4: Verify Sonnet is default in env | `.env` on backend host | 0.5 days |
| Sprint 1 | Fix 5A: Always-on knowledge + expand regex | `intent-classifier.service.ts`, `prompt-builder.service.ts` | 1–2 days |
| Sprint 1 | Fix 3: Context budget + logging | `prompt-builder.service.ts` | 1–2 days |
| Sprint 2 | Fix 1: Read actions from DB + format summary | `class-rest-controller.php`, `class-audit-log.php` | 2 days |
| Sprint 2 | Fix 1: Inject action memory into prompt | `prompt-builder.service.ts` | 1 day |
| Sprint 3 | Fix 2: Add ResultSummarizer class | `class-result-summarizer.php` (new file) | 2 days |
| Sprint 3 | Fix 2: Wire to DB write path | `class-rest-controller.php` | 1 day |
| Sprint 4 | Fix 5B: LLM-based intent classifier | `intent-classifier.service.ts`, `prompt-builder.service.ts` | 3–5 days |

---

## What NOT to Change

These parts of the codebase are correct and should not be touched:

- `llm.service.ts` retry logic (3 retries with exponential backoff) — keep as-is
- `class-tool-executor.php` tool validation and capability checking — solid
- `class-audit-log.php` core `log_action()` method — keep, just add `format_action_summary()`
- SSE streaming pipeline — works well, don't touch
- `MAX_HISTORY_CONTENT = 4_000` in `chat.controller.ts` — leave as safety net
- `HISTORY_LIMIT = 40` in `class-rest-controller.php` — fine as-is once tool results are summarized
- Tool definitions structure (PHP `ToolInterface` classes) — well designed, single source of truth
- `AuthGuard` + `RateLimiterGuard` — keep as-is

---

## Testing Checklist

### Fix 1 — Action Memory

- [ ] Ask "What plugins did you just install?" after installing one — model answers correctly
- [ ] Ask "Undo that" after a single action — model identifies the right action
- [ ] Verify `wp_wally_actions` has records after tool execution (it already should — this is existing behavior)
- [ ] Verify system prompt in logs contains `--- Recent Actions Taken ---` block

### Fix 2 — Tool Result Summarization

- [ ] On a site with 20+ plugins, list them — check `wp_wally_messages` DB record is a summary, not raw JSON
- [ ] Run a search/replace — stored message says "X replacement(s) in content", not raw result
- [ ] Confirm the LLM still receives full raw results during the active tool-use loop

### Fix 3 — Context Budget

- [ ] Grep backend logs for `[PromptBudget]` — should appear on every request
- [ ] Total token estimate should be under 8,000 on a typical request
- [ ] On a site with 60+ plugins and large Elementor data, system prompt does not exceed budget

### Fix 4 — Default Model

- [ ] Check `DEFAULT_MODEL` env var on deployed backend
- [ ] Confirm `LlmService` logs show `claude-sonnet-4-6` on every request

### Fix 5 — Intent Classifier

- [ ] Ask "can you remove the hello dolly thing?" — check logs show `plugins` intent classified
- [ ] Ask "fix my SEO" — check logs show `seo` intent classified
- [ ] Every request injects always-on knowledge keys regardless of classified intent

---

*Wally — wallychat.com | Confidential | March 2026*
