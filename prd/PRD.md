# WP AI Assistant — Product Requirements Document

> Version: 1.0
> Author: Rex
> Date: 2026-02-23
> Status: Ready for Implementation

---

## 1. Overview

### Product Name
WP AI Assistant (working title)

### One-Liner
A WordPress plugin that lets users manage their site through natural language chat inside wp-admin.

### Problem Statement
Non-technical WordPress users (agency clients) can't navigate wp-admin. They submit support tickets for trivial tasks: changing a phone number, updating a page title, installing a plugin. Agencies waste hours on work that should take seconds.

### Solution
A chat sidebar in wp-admin. User types what they want. AI does it. No menus, no settings pages, no learning curve.

### Target Users
- **Primary:** Non-technical clients of WordPress agencies (content editors, marketing managers, business owners)
- **Secondary:** WordPress agencies themselves (faster site management, white-label resale)
- **Tertiary:** Solo WordPress site owners who want a faster admin experience

### Competitive Landscape
| Product | What It Does | Gap |
|---------|-------------|-----|
| CodeWP | AI generates WordPress code snippets | Doesn't execute anything |
| ManageWP | Multi-site dashboard management | No AI, no natural language |
| Jetrails | Managed hosting with tools | Infrastructure, not admin UX |
| Flavor WP | AI content generation | Content only, no admin actions |

**Our differentiation:** We don't generate code or content. We execute admin actions through conversation. First-mover in "conversational WordPress admin."

---

## 2. Architecture

### Hybrid SaaS Model
The plugin is a thin client. The intelligence lives server-side.

```
┌─────────────────────────────────────────────┐
│  WordPress Site (Plugin)                     │
│  ┌─────────┐  ┌──────────┐  ┌────────────┐ │
│  │ Chat UI │→ │ REST API │→ │ Tool Exec  │ │
│  │ (React) │  │ (PHP)    │  │ (PHP)      │ │
│  └─────────┘  └────┬─────┘  └────────────┘ │
│                     │                        │
└─────────────────────┼────────────────────────┘
                      │ HTTPS
┌─────────────────────┼────────────────────────┐
│  Backend API        ▼                        │
│  ┌──────────────────────────────────────┐   │
│  │ Orchestration Server                  │   │
│  │ - Prompt construction                 │   │
│  │ - Tool definitions & selection        │   │
│  │ - Conversation management             │   │
│  │ - License validation                  │   │
│  │ - Usage tracking & billing            │   │
│  └──────────────┬───────────────────────┘   │
│                  │                            │
│                  ▼                            │
│           LLM API (Claude / OpenAI)          │
└──────────────────────────────────────────────┘
```

**Why hybrid:**
- Plugin code is PHP (plaintext, easily cloned). Keeping orchestration server-side protects IP.
- Plugin without backend = useless shell.
- Enables license gating, usage tracking, and remote tool updates without plugin releases.
- BYOK users still route through our backend (we orchestrate, their key pays for tokens).

### Plugin Components
1. **Chat UI** — React sidebar, toggled via admin bar button
2. **REST Controller** — Receives messages, forwards to backend, streams responses
3. **Tool Executor** — Receives tool calls from backend, executes against WP REST API locally
4. **Settings Page** — API key, model selection, rate limits, permissions
5. **Audit Logger** — Logs every action to custom DB table

### Backend Components
1. **API Gateway** — Auth, rate limiting, routing
2. **Orchestrator** — Builds prompts, manages tool-use loop, handles conversation state
3. **License Service** — Validates keys, enforces tier limits
4. **Usage Tracker** — Token counts, cost estimates, billing

### Data Flow (Single Message)
1. User types message in chat sidebar
2. Plugin JS sends POST to `/wp-json/wp-ai-assistant/v1/chat`
3. Plugin PHP forwards message + site context to backend API
4. Backend builds prompt (system prompt + tool definitions + conversation history)
5. Backend calls LLM API with function calling enabled
6. LLM returns tool call(s)
7. Backend sends tool call(s) back to plugin
8. Plugin executes tool(s) locally against WordPress (with permission checks)
9. Plugin sends tool results back to backend
10. Backend feeds results to LLM for next iteration or final response
11. Final response streamed to chat UI via SSE

### Database Schema

**Table: `{prefix}_ai_assistant_conversations`**
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | WP user ID |
| title | varchar(255) | Auto-generated from first message |
| created_at | datetime | |
| updated_at | datetime | |

**Table: `{prefix}_ai_assistant_messages`**
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| conversation_id | bigint | FK to conversations |
| role | enum | user, assistant, tool_call, tool_result |
| content | longtext | Message content or JSON |
| token_count | int | Tokens used |
| created_at | datetime | |

**Table: `{prefix}_ai_assistant_actions`**
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| conversation_id | bigint | FK to conversations |
| message_id | bigint | FK to messages |
| user_id | bigint | WP user ID |
| tool_name | varchar(100) | Tool executed |
| tool_input | longtext | JSON input |
| tool_output | longtext | JSON output |
| status | enum | success, failed, cancelled |
| created_at | datetime | |

---

## 3. Functional Requirements

### FR-00: Site Init (Auto-Scan on Activation)
On first activation (or first chat open), the plugin automatically scans the WordPress site and builds a site profile. No user action required.

**What it collects:**
- WordPress version, PHP version, server environment
- Active theme (name, version, child theme status)
- All installed plugins (active/inactive, versions, update status)
- Registered post types (built-in + custom: portfolio, testimonials, products, etc.)
- Registered taxonomies (categories, tags, custom)
- Content counts (posts, pages, media, custom post types)
- Elementor status: installed? version? Pro or Free? Number of Elementor-built pages
- Permalink structure
- Multisite or single site
- Active user roles and counts
- WooCommerce status (if installed: products count, order count)

**Storage:**
- Stored in `wp_options` as serialized JSON key `wp_ai_assistant_site_profile`
- Auto-refreshed every 24 hours via WP Cron
- Manual rescan button in settings page
- Compact version sent as context with every chat request to backend

**UX Flow:**
1. User installs and activates plugin
2. Admin notice directs to settings page (or settings page auto-opens)
3. User enters API key or license key
4. Plugin runs site scan in background (2-3 seconds)
5. Chat sidebar appears with welcome message incorporating scan results: *"Hey, I've scanned your site. You're running WordPress 6.5 with Elementor Pro and 47 pages. What can I help with?"*
6. Scan results power the LLM system prompt, so the assistant knows the site without discovery tool calls

**Why it matters:**
- LLM gets full site context in system prompt from message one
- No wasted tool calls on discovery ("what plugins do I have?")
- Enables proactive suggestions ("I see Yoast is installed but sitemap isn't configured")
- Faster, smarter responses from the first interaction

### FR-01: Chat Interface
- **FR-01.1:** Floating sidebar panel toggled via admin bar button (right side, 400px wide)
- **FR-01.2:** Resizable panel width (drag handle)
- **FR-01.3:** Message input with send button and Enter key support
- **FR-01.4:** Streaming response display (token by token via SSE)
- **FR-01.5:** Conversation history list (sidebar within sidebar, collapsible)
- **FR-01.6:** New conversation button
- **FR-01.7:** Markdown rendering in assistant responses
- **FR-01.8:** Loading/typing indicator during LLM processing
- **FR-01.9:** Mobile responsive (full-width overlay on <782px)

### FR-02: Content Management
- **FR-02.1:** List posts/pages with filters (status, type, date range, author, search query)
- **FR-02.2:** Get full post content (title, body, excerpt, featured image, taxonomies, meta)
- **FR-02.3:** Create new post or page (title, content, status, categories, tags)
- **FR-02.4:** Update existing post/page fields
- **FR-02.5:** Trash/delete post (requires confirmation)
- **FR-02.6:** List and create categories and tags

### FR-03: Search & Replace
- **FR-03.1:** Search across all post content (standard + Elementor `_elementor_data`)
- **FR-03.2:** Dry-run replace: show all matches with context before executing
- **FR-03.3:** Execute replace with confirmation (show count of changes)
- **FR-03.4:** Case-sensitive and case-insensitive modes
- **FR-03.5:** Regex support (advanced, behind a flag)
- **FR-03.6:** Elementor CSS cache clear after Elementor content changes

### FR-04: Plugin Management
- **FR-04.1:** List installed plugins with status (active/inactive, version, update available)
- **FR-04.2:** Install plugin from WordPress.org repo by name or slug
- **FR-04.3:** Activate/deactivate plugin
- **FR-04.4:** Update plugin(s) (single or bulk)
- **FR-04.5:** Delete plugin (requires confirmation)

### FR-05: Site Information
- **FR-05.1:** Get site info (WP version, PHP version, active theme, server info)
- **FR-05.2:** Read WordPress options (site title, tagline, permalink structure, etc.)
- **FR-05.3:** Update WordPress options (requires confirmation)

### FR-06: Elementor Integration
- **FR-06.1:** Search text within Elementor widget data across all Elementor pages
- **FR-06.2:** Replace text in Elementor widgets (with dry-run preview)
- **FR-06.3:** Get page structure (section/column/widget tree, human-readable)
- **FR-06.4:** Clear Elementor CSS cache after modifications

### FR-07: Confirmation Flow
- **FR-07.1:** Destructive or high-impact actions require explicit user confirmation
- **FR-07.2:** Confirmation shows exactly what will change (diff preview where applicable)
- **FR-07.3:** User can approve, reject, or modify before execution
- **FR-07.4:** Confirmation rendered as inline UI component in chat (not a modal)

### FR-08: Conversation Management
- **FR-08.1:** Conversations persisted per user in custom DB tables
- **FR-08.2:** Conversation history sent to LLM (last 20 messages, configurable)
- **FR-08.3:** Users can view past conversations
- **FR-08.4:** Users can delete their own conversations
- **FR-08.5:** Admins can view/delete any conversation

### FR-09: Settings & Configuration
- **FR-09.1:** Settings page under WP Settings menu
- **FR-09.2:** API key input (encrypted storage)
- **FR-09.3:** Model selection (Claude Sonnet, Claude Haiku, GPT-4o, GPT-4o-mini)
- **FR-09.4:** Rate limit configuration (messages per hour/day per user)
- **FR-09.5:** Monthly token budget cap
- **FR-09.6:** Enable/disable specific tool categories per user role
- **FR-09.7:** Custom system prompt append (for agencies to add context)

---

## 4. Non-Functional Requirements

### NFR-01: Performance
- Chat response start (first token) within 2 seconds
- Tool execution within 5 seconds for standard operations
- Plugin adds <50ms to wp-admin page load (lazy-load chat assets)
- Chat assets loaded only on admin pages, not frontend

### NFR-02: Security
- All REST endpoints require valid nonce + logged-in user
- API keys encrypted at rest (AES-256 with `wp_salt('auth')`)
- Tool inputs validated against schemas before execution
- Every action logged to audit table
- No arbitrary PHP/SQL execution, ever
- Prompt injection mitigation: tool parameters validated independently of LLM output
- CORS restricted to same-origin

### NFR-03: Compatibility
- WordPress 6.0+
- PHP 8.0+
- Elementor 3.0+ (for Elementor tools)
- Modern browsers (Chrome, Firefox, Safari, Edge, last 2 versions)
- Compatible with major hosting providers (WP Engine, Kinsta, SiteGround, Cloudways)

### NFR-04: Scalability
- Plugin handles single-site load (1-50 concurrent admin users)
- Backend API designed for multi-tenant (thousands of sites)
- Conversation history auto-pruned after 90 days (configurable)

### NFR-05: Accessibility
- Chat UI follows WCAG 2.1 AA
- Keyboard navigable (Tab, Enter, Escape)
- Screen reader compatible (ARIA labels on all interactive elements)
- Respects user's WP admin color scheme

---

## 5. Permission Matrix

| Capability | Admin | Editor | Author | Contributor |
|-----------|:-----:|:------:|:------:|:-----------:|
| Content: list all | yes | yes | own | own |
| Content: create | yes | yes | yes | drafts |
| Content: edit all | yes | yes | own | own drafts |
| Content: delete | yes | yes | own | no |
| Search & replace | yes | yes | no | no |
| Plugins: list | yes | no | no | no |
| Plugins: install/activate | yes | no | no | no |
| Site info: read | yes | yes | no | no |
| Site options: update | yes | no | no | no |
| Elementor: search | yes | yes | no | no |
| Elementor: replace | yes | yes | no | no |
| Media: manage | yes | yes | own | no |
| Users: manage | yes | no | no | no |
| Settings: configure | yes | no | no | no |
| View action log | yes | no | no | no |

---

## 6. MVP Definition

### What's In (4-week build)
1. Chat sidebar UI (React, SSE streaming)
2. Backend orchestration API
3. Content CRUD tools (posts, pages, categories, tags)
4. Search & replace (standard + Elementor)
5. Plugin management tools
6. Site info tools
7. Confirmation flow for destructive actions
8. Conversation history (persist + display)
9. Settings page (API key, model, rate limits)
10. Audit log (all actions)
11. Permission checks mapped to WP roles

### What's Out (Post-MVP)
- Media management via chat
- User management via chat
- WooCommerce integration
- Elementor layout creation/editing
- Multi-site support
- Content generation with brand voice
- Scheduled/recurring actions
- White-label mode
- Webhook/notification integrations

### Week-by-Week Plan

**Week 1: Foundation**
- Plugin scaffold (file structure, activation/deactivation hooks)
- Custom DB tables (conversations, messages, actions)
- Settings page (API key, model selector)
- Backend API scaffold (auth, routing, basic orchestrator)
- Basic chat UI (React sidebar, message send/receive, no streaming yet)

**Week 2: Core Tools**
- Tool executor framework (register tool, validate input, check permissions, execute, log)
- Content tools: list_posts, get_post, create_post, update_post, delete_post
- Taxonomy tools: list_categories, list_tags, create_category, create_tag
- Site tools: get_site_info, get_option
- Plugin tools: list_plugins, install_plugin, activate_plugin, deactivate_plugin
- Confirmation flow UI component

**Week 3: Search, Elementor, Polish**
- Search & replace: search_content, replace_content (with dry-run)
- Elementor tools: elementor_search_content, elementor_replace_content, elementor_get_page_structure
- Elementor CSS cache clearing
- SSE streaming for responses
- Conversation history UI (list, switch, new, delete)
- update_option, update_plugin, delete_plugin tools

**Week 4: Harden & Ship**
- Rate limiting (per-user message caps)
- Token budget tracking
- Error handling (timeouts, retries, graceful failures)
- Security audit (nonces, capability checks, input validation, SQL injection review)
- Action log viewer (admin page)
- Documentation (README, setup guide)
- Demo video / screenshots
- Beta deployment to 2-3 test sites

---

## 7. Technical Specs

### Plugin File Structure
```
wp-ai-assistant/
  wp-ai-assistant.php          # Main plugin file, hooks, activation
  includes/
    class-plugin.php            # Plugin singleton, init
    class-rest-controller.php   # REST API endpoints
    class-tool-executor.php     # Tool registration, validation, execution
    class-permissions.php       # Role-to-capability mapping
    class-database.php          # Table creation, queries
    class-settings.php          # Settings page, option management
    class-audit-log.php         # Action logging
    class-site-scanner.php      # Site profile scan & caching
    tools/
      class-tool-interface.php  # Abstract tool class
      class-content-tools.php   # Post/page CRUD
      class-taxonomy-tools.php  # Categories, tags
      class-plugin-tools.php    # Plugin management
      class-site-tools.php      # Site info, options
      class-search-tools.php    # Search & replace
      class-elementor-tools.php # Elementor-specific
  admin/
    js/                         # React app (built with @wordpress/scripts)
      src/
        index.js                # Entry point
        components/
          ChatSidebar.jsx       # Main sidebar container
          MessageList.jsx       # Message display
          MessageInput.jsx      # Input field
          ConfirmAction.jsx     # Confirmation UI
          ConversationList.jsx  # History sidebar
          Settings.jsx          # Inline settings
      build/                    # Compiled assets
    css/
      sidebar.css               # Chat UI styles
  languages/                    # i18n
  readme.txt                    # WP plugin repo readme
  package.json
  composer.json
```

### Backend API Endpoints (Plugin Side)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/wp-json/wp-ai-assistant/v1/chat` | Send message, get streamed response |
| GET | `/wp-json/wp-ai-assistant/v1/conversations` | List user's conversations |
| GET | `/wp-json/wp-ai-assistant/v1/conversations/{id}` | Get conversation messages |
| DELETE | `/wp-json/wp-ai-assistant/v1/conversations/{id}` | Delete conversation |
| POST | `/wp-json/wp-ai-assistant/v1/confirm/{action_id}` | Confirm/reject pending action |
| GET | `/wp-json/wp-ai-assistant/v1/actions` | Get action audit log (admin) |
| GET | `/wp-json/wp-ai-assistant/v1/site-profile` | Get cached site profile |
| POST | `/wp-json/wp-ai-assistant/v1/site-profile/rescan` | Trigger manual site rescan |

### Backend API Endpoints (Orchestration Server)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/chat` | Receive message + context, return tool calls or response |
| POST | `/api/v1/tool-result` | Receive tool execution result, continue loop |
| POST | `/api/v1/license/validate` | Validate license key |
| GET | `/api/v1/usage/{site_id}` | Get usage stats |

### Tool Definition Schema
Each tool registered with the executor follows this structure:
```json
{
  "name": "create_post",
  "description": "Create a new WordPress post or page",
  "category": "content",
  "requires_confirmation": false,
  "required_capability": "edit_posts",
  "parameters": {
    "type": "object",
    "properties": {
      "title": { "type": "string", "description": "Post title" },
      "content": { "type": "string", "description": "Post content (HTML)" },
      "status": { "type": "string", "enum": ["draft", "publish", "pending"], "default": "draft" },
      "post_type": { "type": "string", "enum": ["post", "page"], "default": "post" },
      "categories": { "type": "array", "items": { "type": "integer" } },
      "tags": { "type": "array", "items": { "type": "integer" } }
    },
    "required": ["title"]
  }
}
```

---

## 8. Monetization

### Pricing Tiers
| Tier | Price | Sites | Features |
|------|-------|-------|----------|
| Free | $0/mo | 1 | BYOK, content tools + site info only, 50 messages/day |
| Pro | $12/mo | 1 | All tools, unlimited messages, action log, priority support |
| Agency | $49/mo | 10 | Everything in Pro + white-label mode + bulk management |
| Enterprise | $149/mo | Unlimited | Custom branding, SSO, dedicated support, SLA |

### Revenue Model
- Free tier: user's own API key, we orchestrate (our cost ~$0 per user)
- Paid tiers: included token allowance OR BYOK with premium features
- White-label upsell is the real margin play for agencies

---

## 9. Success Metrics

### MVP Launch (Week 4)
- Plugin installs on 3+ beta sites
- <3 second average first-token time
- >90% tool execution success rate
- Zero security incidents

### Month 1
- 50+ plugin installs
- 10+ active daily users
- NPS >40 from beta testers

### Month 3
- 500+ installs
- 50+ paid subscribers
- $500+ MRR

### Month 6
- 2,000+ installs
- 150+ paid subscribers
- $1,500+ MRR

---

## 10. Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| LLM hallucination (wrong tool call) | Data corruption | Confirmation flow for destructive actions, dry-run previews, audit log |
| API cost overruns | Financial | Rate limits, budget caps, token tracking |
| Prompt injection via post content | Security breach | Tool params validated independently, no arbitrary execution |
| Elementor data corruption | Broken pages | Snapshot `_elementor_data` before modification, rollback capability |
| Hosting compatibility issues | Plugin doesn't work | Test on top 5 hosts during beta, document requirements |
| WordPress.org rejection | Distribution blocked | Follow plugin guidelines strictly, have direct distribution fallback |
| Competitor enters market | Market share | Move fast, build agency relationships, white-label lock-in |

---

## 11. Open Questions

1. **Plugin name for WP.org?** Needs to be unique and not conflict with existing plugins.
2. **Backend hosting?** Railway, Vercel, or Cloudflare Workers for the orchestration API?
3. **Do we support multisite from day one?** Adds complexity but agencies want it.
4. **Elementor Pro vs Free?** Do we need to handle Pro-only widgets differently?
5. **i18n priority?** WordPress is global. Do we support non-English prompts in MVP?

---

*This PRD is the implementation contract. Build to this spec.*
