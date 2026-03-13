=== Wally ===
Contributors: andrewoplas
Tags: ai, chatbot, assistant, content management, admin
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 0.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered chat assistant inside wp-admin. Manage your WordPress site with natural language.

== Description ==

Wally adds an intelligent chat sidebar to your WordPress admin panel. Manage content, plugins, search and replace text, and configure site settings — all through conversational natural language.

**Key Features:**

* **Content Management** — List, create, update, and delete posts and pages through chat
* **Plugin Management** — Install, activate, deactivate, update, and delete plugins
* **Search & Replace** — Find and replace text across all content, including Elementor page data
* **Site Settings** — View and update WordPress options with confirmation for safety
* **Elementor Integration** — Search, replace, and inspect Elementor widget data across pages
* **ACF Support** — Manage Advanced Custom Fields post types, taxonomies, field groups, and field values
* **Streaming Responses** — Real-time token-by-token AI responses via Server-Sent Events
* **Confirmation Flow** — Destructive actions require explicit approval before execution
* **Audit Log** — Every tool execution is logged with user, input, output, and status
* **Role-Based Permissions** — Control which tool categories each WordPress role can access
* **Rate Limiting** — Per-user daily message limits and site-wide monthly token budget

**Security First:**

* All actions gated by WordPress capability checks
* License keys encrypted at rest with AES-256
* Tool inputs validated against JSON schemas before execution
* Full audit trail of every action taken

== External Services ==

This plugin connects to two external services to provide AI-powered functionality:

= Wally Backend API =

The plugin sends chat messages and site context to the Wally orchestration server at `https://wally.up.railway.app`. This server processes your messages, coordinates AI responses, and returns tool call instructions. Data sent includes: chat messages, site profile information (WordPress version, active plugins, theme), and tool execution results.

* Service URL: [https://wally.up.railway.app](https://wally.up.railway.app)
* Privacy Policy: [https://www.wallychat.com/privacy](https://www.wallychat.com/privacy)
* Terms of Service: [https://www.wallychat.com/terms](https://www.wallychat.com/terms)

= Anthropic Claude API =

The Wally backend uses the Anthropic Claude API to generate AI responses. Your chat messages are forwarded to Anthropic's API for processing. No data is stored by Anthropic beyond what is needed to process the request.

* Service URL: [https://api.anthropic.com](https://api.anthropic.com)
* Privacy Policy: [https://www.anthropic.com/privacy](https://www.anthropic.com/privacy)
* Terms of Service: [https://www.anthropic.com/terms](https://www.anthropic.com/terms)

== Installation ==

1. Upload the `wally` directory to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Wally — AI Assistant in the admin menu and enter your license key
4. Click "Wally — AI Assistant" in the admin bar to open the chat sidebar

== Frequently Asked Questions ==

= What AI model does Wally use? =

Wally uses Anthropic's Claude, a state-of-the-art AI model. The specific model is managed by the Wally service and may be updated over time for improved performance.

= How do I get a license key? =

Sign up at [wallychat.com](https://www.wallychat.com) to get your license key. Enter it in the plugin settings to activate.

= Is my license key stored securely? =

Yes. License keys are encrypted with AES-256-CBC using your WordPress auth salt and stored in the options table. They are only decrypted when making API calls.

= Can I limit which users have access? =

Yes. The plugin uses WordPress role-based permissions. Administrators can configure which tool categories each role can access from the settings page.

= Does it work with Elementor? =

Yes. The plugin can search and replace text within Elementor page builder data, view page structures, and clear Elementor CSS caches.

= Does it work with Advanced Custom Fields (ACF)? =

Yes. Wally can manage ACF post types, taxonomies, field groups, and read/update field values on posts, terms, users, and options pages. Works with both ACF Free and ACF Pro.

= What happens with destructive actions? =

Actions like deleting posts, installing plugins, or updating site options require explicit confirmation through an inline approval UI in the chat. No destructive action executes without your approval.

= Does deactivating the plugin delete my data? =

No. Deactivating the plugin only clears scheduled cron jobs. All conversations, settings, and audit logs are preserved.

== Screenshots ==

1. Chat sidebar inside wp-admin — manage your site with natural language
2. Settings page — configure license key, usage limits, and behavior
3. Permissions — control which roles can access which tool categories
4. Audit log — review every action the AI assistant has performed
5. Conversation browser — browse and export full conversation transcripts

== Changelog ==

= 0.1.4 =
* Dropped OpenAI support — all-in on Anthropic Claude for better tool-use performance
* Added Agent SDK integration with streaming support
* Improved knowledge system with 60+ WordPress topic files
* All PHP strings internationalized for translation readiness

= 0.1.3 =
* Full ACF support: post types, taxonomies, field groups, field values (CRUD)
* ACF options page fields support (list, get, update)
* Conditional tool registration — ACF tools only load when ACF is active

= 0.1.2 =
* Plugin header updated with real author and URI information
* License key activation flow with backend validation
* Encrypted license key storage with AES-256-CBC
* Site activation/deactivation tracking

= 0.1.1 =
* Conversation browser with full transcript view and JSON export
* Audit log page with filtering by tool, status, user, and date range
* Data retention setting with automatic conversation pruning
* Custom system prompt support

= 0.1.0 =
* Initial release
* Chat sidebar with SSE streaming and markdown rendering
* Content tools: list, create, update, delete posts/pages
* Taxonomy tools: list and create categories and tags
* Plugin tools: list, install, activate, deactivate, update, delete
* Site tools: get info, read/write options
* Search tools: search and replace across content and Elementor data
* Elementor tools: search, replace, page structure, clear CSS cache
* Confirmation flow for destructive actions
* Conversation history with persistence
* Per-user rate limiting and monthly token budget
* Per-role tool category permissions
* Settings page with encrypted key storage

== Upgrade Notice ==

= 0.1.4 =
Major update: switched to Anthropic Claude exclusively for improved AI performance. All strings now translatable.

= 0.1.0 =
Initial release.
