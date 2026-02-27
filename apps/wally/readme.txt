=== WP AI Assistant ===
Contributors: yourvendor
Tags: ai, chatbot, assistant, content management, admin
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered chat assistant inside wp-admin. Manage your WordPress site with natural language.

== Description ==

WP AI Assistant adds an intelligent chat sidebar to your WordPress admin panel. Manage content, plugins, search and replace text, and configure site settings — all through conversational natural language.

**Key Features:**

* **Content Management** — List, create, update, and delete posts and pages through chat
* **Plugin Management** — Install, activate, deactivate, update, and delete plugins
* **Search & Replace** — Find and replace text across all content, including Elementor page data
* **Site Settings** — View and update WordPress options with confirmation for safety
* **Elementor Integration** — Search, replace, and inspect Elementor widget data across pages
* **Streaming Responses** — Real-time token-by-token AI responses via Server-Sent Events
* **Confirmation Flow** — Destructive actions require explicit approval before execution
* **Audit Log** — Every tool execution is logged with user, input, output, and status
* **Role-Based Permissions** — Control which tool categories each WordPress role can access
* **Rate Limiting** — Per-user daily message limits and site-wide monthly token budget

**Security First:**

* All actions gated by WordPress capability checks
* API keys encrypted at rest with AES-256
* Tool inputs validated against JSON schemas before execution
* Full audit trail of every action taken

== Installation ==

1. Upload the `wp-ai-assistant` directory to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > AI Assistant and enter your API key
4. Click "AI Assistant" in the admin bar to open the chat sidebar

== Frequently Asked Questions ==

= What AI models are supported? =

Claude Sonnet 4.6, Claude Haiku 4.5, GPT-4o, and GPT-4o Mini.

= Is my API key stored securely? =

Yes. API keys are encrypted with AES-256-CBC using your WordPress auth salt and stored in the options table. They are only decrypted when making API calls.

= Can I limit which users have access? =

Yes. The plugin uses WordPress role-based permissions. Administrators can configure which tool categories each role can access from the settings page.

= Does it work with Elementor? =

Yes. The plugin can search and replace text within Elementor page builder data, view page structures, and clear Elementor CSS caches.

= What happens with destructive actions? =

Actions like deleting posts, installing plugins, or updating site options require explicit confirmation through an inline approval UI in the chat. No destructive action executes without your approval.

== Changelog ==

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
* Audit log viewer
* Settings page with encrypted API key storage

== Upgrade Notice ==

= 0.1.0 =
Initial release.
