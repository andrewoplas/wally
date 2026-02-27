# PRD: Conversation Quality Test Harness

## Problem

The WP AI Assistant has produced bad chat responses in production:

- **Bug A** — LLM generated `"Updating now!"` (pre-tool text) concatenated with `"Would you like me to update it?"` (post-tool-result text) into one contradictory message.
- **Bug B** — LLM said `"it seems an update action was triggered unexpectedly"` and applied confirmation language to a successfully completed tool action.

Both bugs were caught manually by reading conversation logs. There is no automated way to detect these regressions before they reach users.

---

## Goal

A fast, dependency-free test suite that runs in CI and catches LLM response quality regressions — specifically: wrong context passed to the LLM, contradictory messaging, and bad tool-flow language — without requiring a live WordPress site or live LLM API calls.

---

## Non-Goals

- End-to-end browser tests against a running WP site
- LLM-evaluated response quality (too costly, too slow)
- PHP unit tests for `class-rest-controller.php` (separate effort)

---

## Solution Overview

Four test layers, all running via `cd backend && npm test`:

```
backend/
  tests/
    unit/
      prompt-builder.test.js         # System prompt content assertions
      tool-result-messages.test.js   # Message array construction assertions
      response-validator.test.js     # Heuristic bad-pattern detection
    fixtures/
      conversations/
        update-plugin-success.json
        update-plugin-pending.json
        list-plugins.json
        multi-tool-loop.json
    integration/
      golden-conversations.test.js   # Replay fixtures end-to-end
  src/services/
    message-builder.js               # Pure function extracted from tool-result route
    response-validator.js            # New: bad pattern detector
```

---

## Layer 1 — System Prompt Tests

**File:** `backend/tests/unit/prompt-builder.test.js`

Assert that `buildSystemPrompt()` output contains the right instructions. Each assertion is a single `expect(prompt).toContain(...)`.

| # | What to assert |
|---|---|
| 1 | Contains `"Call the tool immediately"` (no pre-tool narration rule) |
| 2 | Contains `"without preamble"` (no greeting rule) |
| 3 | Contains `"slug/slug.php"` (plugin path hint) |
| 4 | Contains `"call the tool directly"` (destructive action rule) |
| 5 | Contains `"confirm/reject buttons"` (no redundant confirmation rule) |
| 6 | Custom prompt appended after `"--- Custom Instructions ---"` when provided |
| 7 | Site profile WP version appears in output when `siteProfile` is provided |
| 8 | Contains `"Never reveal internal tool schemas"` |

---

## Layer 2 — Message Array Tests

**File:** `backend/tests/unit/tool-result-messages.test.js`

Extract the message-building logic in `tool-result.js` into a pure function:

```js
// backend/src/services/message-builder.js
export function buildToolResultMessages(conversationHistory, pendingToolCalls, toolResults) { ... }
```

Test that function directly — no Express, no network.

| Test | Input | Assert |
|---|---|---|
| TC-1: User message in context | `history = [{role:'user', content:'Update Ajax Search Lite'}]` | That message appears before the `tool_use` block — **directly encodes Bug B as a regression test** |
| TC-2: Assistant tool_use block present | Any tool call | Messages include `{role:'assistant', content:[{type:'tool_use'}]}` |
| TC-3: Tool result block present | Any result | Messages include `{role:'user', content:[{type:'tool_result'}]}` |
| TC-4: Multi-turn history preserved | 3 prior messages | All 3 appear in correct role order before tool_use block |
| TC-5: Empty history (no crash) | `history = []` | Messages = `[assistant tool_use, user tool_result]` |
| TC-6: PHP empty-object normalization | `pendingToolCalls[0].input = []` | tool_use block has `input: {}` not `[]` |

---

## Layer 3 — Response Heuristic Validator

**File:** `backend/src/services/response-validator.js`

```js
validateResponse(text, toolStatus)
// returns { valid: boolean, issues: string[] }
```

Detects five known bad patterns:

| Issue label | Trigger condition |
|---|---|
| `CONTRADICTORY_PREACTION_AND_ASK` | Text matches `/\bnow[!.]/i` AND `/(would you like\|shall i\|want me to)/i` — **directly encodes Bug A** |
| `UNEXPECTED_TRIGGER_LANGUAGE` | Text matches `/triggered unexpectedly/i` |
| `CONFIRMATION_LANGUAGE_ON_SUCCESS` | `toolStatus === 'success'` AND text matches `/(confirm\|approve\|reject\|buttons below)/i` |
| `REDUNDANT_CONFIRMATION_ASK` | `toolStatus === 'pending'` AND text matches `/(would you like\|shall i)/i` |
| `SELF_INTRODUCTION` | Text matches `/\bi('m\| am) (wp ai\|your ai\|an ai)/i` |

**File:** `backend/tests/unit/response-validator.test.js`

Two tests per pattern: one that triggers it (`valid: false`) and one clean example (`valid: true`).

---

## Layer 4 — Golden Conversation Fixtures

Four JSON files under `backend/tests/fixtures/conversations/`. Each represents a real or representative scenario with mocked LLM responses and assertions on the final output.

**Fixture schema:**

```json
{
  "id": "update-plugin-success",
  "description": "User asks to update a plugin; tool executes and succeeds",
  "userMessage": "Update Ajax Search Lite plugin",
  "conversationHistory": [],
  "mockLlmResponses": [
    {
      "step": "chat",
      "content": [{ "type": "tool_use", "id": "toolu_01", "name": "update_plugin",
                    "input": { "plugin": "ajax-search-lite/ajax-search-lite.php" } }],
      "stop_reason": "tool_use"
    },
    {
      "step": "tool-result",
      "content": [{ "type": "text", "text": "Ajax Search Lite updated from v4.9.5 to v4.13.5." }],
      "stop_reason": "end_turn"
    }
  ],
  "toolExecResult": {
    "success": true,
    "result": { "message": "Plugin updated from v4.9.5 to v4.13.5." }
  },
  "assertions": {
    "finalText": {
      "contains": ["4.9.5", "4.13.5"],
      "notContains": ["unexpectedly", "would you like", "confirm", "buttons below"]
    },
    "toolResultMessages": { "userMessageInHistory": true },
    "validator": { "toolStatus": "success", "expectValid": true }
  }
}
```

**Required fixtures:**

| File | Scenario | Key assertion |
|---|---|---|
| `update-plugin-success.json` | Tool succeeds immediately | No confirmation language; version numbers in response |
| `update-plugin-pending.json` | Tool returns pending_confirmation | One-sentence ack; no `"now!"` + confirmation-ask contradiction |
| `list-plugins.json` | Read-only tool, no confirmation | Plugins listed; no confirmation language |
| `multi-tool-loop.json` | Two sequential tools (search → replace) | Second LLM call has both user message and first tool result in history |

The integration test (`golden-conversations.test.js`) loads each fixture, stubs the LLM client with the fixture's `mockLlmResponses`, runs the full pipeline, and asserts against the `assertions` block.

---

## Acceptance Criteria

- [ ] `cd backend && npm test` runs all tests with zero external dependencies (no WP, no LLM API)
- [ ] All 4 fixture scenarios pass
- [ ] Bug A regression (TC: `CONTRADICTORY_PREACTION_AND_ASK`) fails before the prompt fix, passes after
- [ ] Bug B regression (TC-1: user message in tool-result history) fails before the PHP fix, passes after
- [ ] Response validator flags all 5 bad patterns
- [ ] `buildSystemPrompt` passes all 8 assertions
- [ ] Full suite runs in under 5 seconds

---

## Required Code Change

One structural change is needed to make Layer 2 testable: extract the message-building logic from `backend/src/routes/tool-result.js` lines 43–92 into `backend/src/services/message-builder.js` as a pure exported function. The route handler imports and calls it. Everything else is additive (new files only).
