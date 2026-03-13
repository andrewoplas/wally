# Wally Agent SDK Migration

## Overview

Wally's backend was migrated from direct Anthropic/OpenAI API calls to the **Claude Agent SDK** (`@anthropic-ai/claude-agent-sdk`). This document explains what changed, why, and how the new architecture works end-to-end.

---

## What Changed and Why

### Before: Raw API + Manual Tool Loop

The old architecture used a custom `LlmService` that called the Anthropic (and OpenAI) API directly. The tool-use loop was manual:

1. Send message → get response with `tool_use` blocks
2. Execute tools on the backend
3. POST `/api/v1/tool-result` with all results
4. Repeat until Claude stopped calling tools
5. Stream tokens back

This meant the backend had to manage conversation state, tool-result round trips, and multi-turn logic by hand.

### After: Agent SDK + MCP Bridge

The new architecture delegates the agentic loop entirely to the Claude Agent SDK's `query()` function. The backend acts as the brain; the WordPress plugin acts as the arms and feet.

**Dropped:** OpenAI support, `LlmService`, `ToolDefinitionsService`, `ToolResultController`, `MessageBuilderService`

**Added:** `AgentBridgeService`, `McpToolFactory`, `ToolCallbackStore`, `ToolCallbackController`

---

## Architecture: How It Works

The core design challenge: the Agent SDK runs the tool loop on the backend, but WordPress tools must execute inside the WordPress process (on the user's server). The backend can't call WordPress directly — WordPress is behind a firewall/NAT. So the plugin always initiates connections.

### Full Request Flow

```
User types message in WP admin
        │
        ▼
WordPress plugin
  POST /wally/v1/chat
  (includes tool_definitions, site_profile, history)
        │
        ▼
NestJS ChatController
  delegates to AgentBridgeService.runChat()
        │
        ▼
AgentBridgeService
  1. Builds system prompt (PromptBuilderService)
  2. Creates per-request EventEmitter (bridge)
  3. Creates MCP server (McpToolFactory)
  4. Sets SSE headers on response
  5. Starts query() — async iterable
        │
        ▼
Claude Agent SDK query()
  Claude reasons → decides to call a tool
        │
        ▼
MCP tool handler fires (McpToolFactory)
  1. Generates callId (UUID)
  2. Emits tool_execute on bridge EventEmitter
  3. Awaits callbackStore.register(callId) ← BLOCKS HERE
        │
        ▼ (SSE event forwarded to plugin)
AgentBridgeService sees tool_execute event
  Writes SSE: { type: 'tool_call', tool_call_id, tool, input, requires_confirmation }
        │
        ▼ (plugin receives SSE while cURL connection stays open)
WordPress plugin (stream_backend_sse cURL write callback)
  Receives tool_call event
  Calls ToolExecutor::execute(tool_name, input)
        │
        ├── If requires_confirmation:
        │     Store call_id in WP transient keyed to action_id
        │     Send 'confirmation' SSE to browser
        │     POST callback with { status: 'pending_confirmation' }
        │     (agent SDK continues, knowing confirmation is pending)
        │     ... user approves/rejects via /wally/v1/confirm/{action_id}
        │     confirm_action() retrieves call_id from transient
        │     Executes tool (or rejects)
        │     POST /api/v1/tool-callback with final result
        │
        └── If no confirmation needed:
              Execute tool immediately
              POST /api/v1/tool-callback with result
                      │
                      ▼
              NestJS ToolCallbackController
                Calls callbackStore.resolve(callId, result)
                      │
                      ▼
              MCP tool handler unblocks
              Returns result to Agent SDK
                      │
                      ▼
              Agent SDK continues loop
              Claude processes tool result → streams next tokens
                      │
                      ▼
              SSE token events → plugin → browser
                      │
              Claude finishes → SSE done event
```

---

## New Files

### `apps/backend/src/agent/tool-callback.store.ts`

Singleton in-memory store for pending tool execution Promises.

When an MCP tool handler fires, it registers a `callId` here and awaits the Promise. When WordPress POSTs the result to `/api/v1/tool-callback`, this store resolves the Promise to unblock the handler.

```
Map<callId, { resolve, reject, timeout }>

register(callId)  → returns Promise, stores resolve/reject/timeout
resolve(callId)   → resolves Promise, clears timeout
reject(callId)    → rejects Promise, clears timeout
cleanup()         → rejects all pending (called on module destroy)
```

Default timeout: 120 seconds. On NestJS shutdown, all pending callbacks are rejected.

---

### `apps/backend/src/agent/tool-callback.controller.ts`

`POST /api/v1/tool-callback` — WordPress calls this after executing a tool.

```typescript
// Request body
{ call_id: string, result: { success: boolean, data?: any, error?: string } }

// Response
{ ok: true }
```

Protected by `AuthGuard` (same X-Site-ID + X-License-Key as all other endpoints). Returns 404 if the `call_id` is not found in the store (e.g., timed out).

---

### `apps/backend/src/agent/mcp-tool.factory.ts`

Converts raw WordPress tool definitions (JSON Schema, sent with every chat request) into live MCP tools the Agent SDK can call.

**Key responsibilities:**

1. **Normalize PHP quirks** — PHP's `json_encode` serializes empty objects as `[]`. The factory detects this and converts to `{}`.

2. **JSON Schema → Zod** — The Agent SDK requires Zod schemas for MCP tools. `jsonSchemaToZodShape()` converts all JSON Schema types: `string`, `number`, `integer`, `boolean`, `array`, `object`, `enum`. Required/optional fields are handled via the `required` array on the parent object.

3. **Tool handler bridge** — Each MCP tool handler:
   - Generates a UUID `callId`
   - Emits `tool_execute` on the bridge EventEmitter
   - Awaits `callbackStore.register(callId)` (blocks until WordPress responds)
   - Returns the result to the Agent SDK

```typescript
createMcpServer(rawTools, bridge, callbackStore) → McpSdkServerConfigWithInstance
```

---

### `apps/backend/src/agent/agent-bridge.service.ts`

The core orchestration service. `runChat(params, res)` handles a full chat request:

1. Build system prompt via `PromptBuilderService` (unchanged)
2. Append conversation history to system prompt (since Agent SDK manages its own session per `query()` call, history is injected as a `## Conversation History` section)
3. Create per-request `EventEmitter` (bridge)
4. Create MCP server via `McpToolFactory`
5. Set SSE headers on the Express response
6. Listen for `tool_execute` events → write SSE `tool_call` events to response
7. Start `query()` with `includePartialMessages: true`, `maxTurns: 15`, `thinking: { type: 'adaptive' }`, `permissionMode: 'bypassPermissions'`
8. Stream `text_delta` events as SSE `token` events
9. On `result` → write `done` event, end response

**Disconnect handling:** An `AbortController` is passed to `query()`. On client disconnect, it aborts the Agent SDK loop and rejects all pending tool callbacks in the store.

**SSE events (backward-compatible with existing plugin frontend):**

| Event | Payload |
|-------|---------|
| `token` | `{ type, content }` |
| `tool_call` | `{ type, tool_call_id, tool, input, requires_confirmation, status, category, action }` |
| `done` | `{ type, stop_reason }` |
| `error` | `{ type, message }` |

---

### `apps/backend/src/agent/agent.module.ts`

NestJS module registering all agent services:

- **Providers:** `ToolCallbackStore`, `McpToolFactory`, `AgentBridgeService`, `AuthGuard`, `WallyLoggerService`
- **Controllers:** `ToolCallbackController`
- **Imports:** `KnowledgeModule` (for `PromptBuilderService`)
- **Exports:** `ToolCallbackStore`, `McpToolFactory`, `AgentBridgeService`

---

## Modified Files

### `apps/backend/src/chat/chat.controller.ts`

Simplified to a thin entry point. Injects `AgentBridgeService` instead of the old `LlmService`. The entire chat lifecycle is delegated to `agentBridge.runChat()`.

### `apps/backend/src/chat/chat.module.ts`

Now imports `AgentModule` instead of the old providers. `ToolResultController` removed.

### `apps/backend/src/app/app.module.ts`

Added `AgentModule` import. `LlmModule` removed.

### `apps/backend/src/config/configuration.ts`

OpenAI removed. Config is now Anthropic-only:

```typescript
models: {
  'claude-sonnet-4-6': { provider: 'anthropic', modelId: 'claude-sonnet-4-6' },
  'claude-haiku-4-5':  { provider: 'anthropic', modelId: 'claude-haiku-4-5-20251001' },
}
```

### `apps/backend/package.json`

- Added: `@anthropic-ai/claude-agent-sdk`, `zod`
- Removed: `openai`

### `apps/wally/includes/class-rest-controller.php`

The PHP plugin was updated to support the new tool execution flow:

**`stream_backend_sse()`** — The cURL write callback now handles `tool_call` events inline (while the cURL connection to the backend stays open):

- If `requires_confirmation`: stores `call_id` in a WordPress transient keyed to `action_id`, sends `confirmation` SSE to the browser, POSTs an intermediate callback to the backend with `{ status: 'pending_confirmation' }` so the Agent SDK can continue processing with context about the pending action.
- If no confirmation needed: executes via `ToolExecutor::execute()` immediately, POSTs result to `/api/v1/tool-callback`.

**`post_tool_callback()`** — New method. Sends tool execution results back to the NestJS backend:

```php
POST /api/v1/tool-callback
Headers: X-Site-ID, X-License-Key
Body: { call_id, result: { success, data?, error? } }
```

**`confirm_action()`** — Updated to support the deferred confirmation flow. When the user approves/rejects a destructive action, it retrieves the stored `call_id` from the transient, executes the tool (or skips it), then POSTs the final result via `post_tool_callback()`.

---

## Deleted Files

| File | Reason |
|------|--------|
| `src/llm/llm.service.ts` | Replaced by `AgentBridgeService` + Agent SDK |
| `src/llm/llm.module.ts` | Replaced by `AgentModule` |
| `src/chat/tool-result.controller.ts` | No more tool-result round trips; callback pattern replaces it |
| `src/common/message-builder.service.ts` | Agent SDK manages message format internally |
| `src/tools/tool-definitions.service.ts` | Replaced by `McpToolFactory` |

---

## Key Design Decisions

### Why promise-based blocking in MCP handlers?

The Agent SDK's `query()` loop naturally pauses when an MCP tool handler returns a Promise — it waits for the Promise to resolve before feeding the result back to Claude. This maps cleanly to the async nature of remote tool execution. The backend "blocks" (awaits the Promise) while WordPress executes the tool and POSTs back. No polling, no separate round trips.

### Why inject conversation history into the system prompt?

The Agent SDK's `query()` function starts a fresh session per call. There is no built-in multi-turn history parameter (unlike the raw Messages API). The cleanest approach is to append the prior conversation as a `## Conversation History` section in the system prompt. History is capped at 4,000 characters per message.

### Why `permissionMode: 'bypassPermissions'`?

The Agent SDK was designed for Claude Code use cases where it might try to request local filesystem permissions. On a server, there are no local permissions to manage — `bypassPermissions` skips these checks so the backend can run without user interaction.

### Why keep the SSE contract backward-compatible?

The WordPress plugin's React frontend (`MessageList.jsx`, `ConversationList.jsx`) already knew how to handle `token`, `tool_call`, `done`, and `error` SSE events. Keeping the same event format meant zero frontend changes were needed.

### Confirmation flow via transients

For destructive actions (e.g., delete a post), the tool's `requires_confirmation: true` flag triggers a two-phase flow:

1. **Phase 1 (immediate):** Plugin stores `call_id → action_id` in a WordPress transient. Backend receives an intermediate callback (`pending_confirmation`) and Claude continues its reasoning with that context.
2. **Phase 2 (deferred):** User clicks Confirm/Cancel in the React UI. `confirm_action()` retrieves the `call_id` from the transient, executes (or skips) the tool, and POSTs the final result to `/api/v1/tool-callback`.

Transient TTL is 2 hours — long enough for any reasonable confirmation window.

---

## Knowledge Base → Skills (In Progress)

Alongside the Agent SDK migration, Wally's 67 knowledge `.md` files in `apps/backend/src/knowledge/` are being converted from reference documentation into prescriptive skills — step-by-step workflows that tell the LLM exactly which tools to call and in what order.

**Three files already converted:** `general.md`, `content.md`, `wally-capabilities.md`

**Remaining 64 files:** Being converted in 9 batches via the Ralph autonomous agent loop (see `.ralph/fix_plan.md`).

The conversion format for each file:

```markdown
## When to Use
- [Trigger conditions]

## Available Tools
- `tool_name` — what it does in this context

## Workflows
### [Task Name]
1. Call `tool_name` with `param: 'value'`
2. [Next step]

## Important Notes
- [Gotchas and limitations]
```

This matters because the Agent SDK works best with actionable instructions. Reference docs make the LLM "know about" things; skills make it "know how to do" things. Better skills = fewer failed tool calls.
