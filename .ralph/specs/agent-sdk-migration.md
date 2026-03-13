# Agent SDK Migration — Architecture Spec

## Overview

Migrate Wally's backend from raw Anthropic/OpenAI API calls to the Claude Agent SDK. The backend becomes the "brain" (runs Agent SDK `query()`), the WordPress plugin remains the "arms and feet" (executes tools locally).

## Current Architecture (Before)

```
Plugin → POST /chat → ChatController → LlmService.sendToLLM()
                                              ↓
                                        Anthropic API stream
                                              ↓
                                        SSE: tokens + tool_calls
                                              ↓
Plugin receives tool_calls → executes tools → POST /tool-result
                                              ↓
                                        ToolResultController → LlmService.sendToLLM() (continue)
                                              ↓
                                        SSE: more tokens or done
```

**Problems:**
- Manual tool-use loop (chat → tool_calls → tool-result → chat → ...)
- Dual provider (Anthropic + OpenAI) adds complexity
- Knowledge files are reference docs, not actionable skills
- No thinking/reasoning for complex tasks

## New Architecture (After)

```
Plugin → POST /chat → ChatController → AgentBridgeService.runChat()
                                              ↓
                                        query() starts (Agent SDK)
                                        MCP tools registered
                                              ↓
                                        Claude streams text → SSE token events → Plugin
                                        Claude calls tool → MCP handler fires
                                              ↓
                                        MCP handler emits tool_execute → SSE → Plugin
                                        MCP handler awaits Promise
                                              ↓
                                        Plugin executes tool → POST /tool-callback
                                              ↓
                                        Promise resolves → Agent SDK continues
                                        Claude streams more text or calls more tools
                                              ↓
                                        Done → SSE done event → Plugin
```

**Benefits:**
- Agent SDK manages the tool loop internally
- No more tool-result endpoint / manual loop
- Single provider (Anthropic only)
- Adaptive thinking for complex reasoning
- Clean MCP tool abstraction

## Service Architecture

```
AgentModule
├── AgentBridgeService      # Core: wraps query(), manages bridge
├── McpToolFactory          # Converts JSON Schema tools → MCP tools
├── ToolCallbackStore       # In-memory Promise store
└── ToolCallbackController  # POST /api/v1/tool-callback

KnowledgeModule (kept)
├── PromptBuilderService    # Builds system prompt with skills
├── KnowledgeLoaderService  # Loads skill .md files
└── IntentClassifierService # Selects relevant skills

ChatModule (modified)
└── ChatController          # Uses AgentBridgeService instead of LlmService

REMOVED:
├── LlmModule / LlmService
├── ToolResultController
├── MessageBuilderService
└── ToolDefinitionsService
```

## SSE Event Types

### Events the WordPress Plugin Receives

| Event | Source | Data |
|-------|--------|------|
| `token` | AgentBridgeService (from stream_event text_delta) | `{ type: 'token', content: '...' }` |
| `tool_execute` | AgentBridgeService (from MCP handler) | `{ type: 'tool_execute', call_id: '...', tool: '...', input: {...}, requires_confirmation: bool }` |
| `tool_status` | AgentBridgeService (optional) | `{ type: 'tool_status', call_id: '...', status: 'executing' }` |
| `usage` | AgentBridgeService (from result) | `{ type: 'usage', input_tokens: N, output_tokens: N }` |
| `done` | AgentBridgeService (from result) | `{ type: 'done', stop_reason: '...' }` |
| `error` | AgentBridgeService (on failure) | `{ type: 'error', message: '...' }` |

### Events the Plugin Sends Back

| Endpoint | When | Body |
|----------|------|------|
| `POST /api/v1/tool-callback` | After executing a tool | `{ call_id, result: { success, data?, error? } }` |
| `POST /api/v1/tool-callback` | After user confirms action | `{ call_id, result: { success, data } }` |
| `POST /api/v1/tool-callback` | After user rejects action | `{ call_id, result: { success: false, error: 'User rejected' } }` |

## JSON Schema → Zod Conversion

The WordPress plugin sends tool definitions with JSON Schema format:
```json
{
  "type": "object",
  "properties": {
    "title": { "type": "string", "description": "Post title" },
    "status": { "type": "string", "enum": ["draft", "publish"], "description": "Post status" },
    "content": { "type": "string", "description": "Post content" }
  },
  "required": ["title"]
}
```

McpToolFactory converts this to Zod:
```typescript
z.object({
  title: z.string().describe("Post title"),
  status: z.enum(["draft", "publish"]).describe("Post status").optional(),
  content: z.string().describe("Post content").optional(),
})
```

## Confirmation Flow

1. MCP handler emits `tool_execute` with `requires_confirmation: true`
2. SSE event reaches WordPress plugin
3. Plugin creates pending audit action, stores `call_id`
4. Plugin POSTs callback: `{ call_id, result: { status: 'pending_confirmation', action_id } }`
5. Agent SDK receives "pending confirmation" as tool result → Claude tells user "I need your confirmation..."
6. User clicks confirm in React UI → Plugin hits `/wally/v1/confirm/{action_id}`
7. Plugin executes tool → POSTs new callback with actual result
8. BUT: The Agent SDK query() has already moved on by this point

**Alternative (simpler):**
1. MCP handler emits `tool_execute` with `requires_confirmation: true`
2. Plugin shows confirmation dialog to user
3. User confirms → Plugin executes tool → POSTs callback with result
4. User rejects → Plugin POSTs callback with `{ success: false, error: 'User rejected this action' }`
5. MCP handler was still waiting on the Promise → resolves/rejects
6. Agent SDK continues with the result

This is simpler because the MCP handler just waits. The confirmation happens on the WordPress side before the callback POST. The Agent SDK doesn't know or care about confirmation — it just gets a result or error.

## Conversation History

The Agent SDK's `query()` function takes either a string prompt or an async generator. For conversation history, we include it in the system prompt:

```
## Conversation History
User: Build me a landing page
Assistant: I'll create a landing page with a hero section, features, and CTA. Let me set that up now.
[Tool: elementor_create_page → success]
Assistant: I've created your landing page with 3 sections. You can preview it at /my-page.

## Current Request
(The user's latest message is passed as the prompt to query())
```

This approach is simple and keeps the system prompt self-contained.
