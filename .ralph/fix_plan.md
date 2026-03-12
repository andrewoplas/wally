# Fix Plan — Make Wally Smart at Complex Tasks

## Loop 1 (P0): Fix Elementor Save Bug

- [x] **1.1 Fix `save_elementor_data()` to use Elementor's Document API** — `apps/wally/includes/tools/class-elementor-builder-tools.php`
  - **Problem:** The current `save_elementor_data()` method calls `update_post_meta()` to store `_elementor_data` and then `clear_elementor_css()` to delete the CSS cache. But this bypasses Elementor's rendering pipeline — no CSS file is generated, no `post_content` is updated with rendered HTML. Pages created programmatically appear completely blank on the frontend even though the JSON data is in the database.
  - **Fix:** Replace the method body with a 3-tier approach:
    1. **Primary:** Use Elementor's Document API — `\Elementor\Plugin::$instance->documents->get($post_id)` then `$document->save(['elements' => $data])`. This triggers Elementor's full save pipeline: persists `_elementor_data`, generates per-post CSS file, updates `post_content` with rendered HTML, fires hooks. Verify this API exists via context7/WebSearch first.
    2. **Fallback:** If Document API unavailable (older Elementor), save raw meta via `update_post_meta()` then explicitly regenerate CSS via `new \Elementor\Core\Files\CSS\Post($post_id)` → `$post_css->update()`. Verify this class/method exists.
    3. **Last resort:** Keep current behavior (raw meta save + cache clear) as final fallback.
  - **Important:** `init_elementor_meta()` is called BEFORE `save_elementor_data()` in all tool execute methods — this sets `_elementor_edit_mode` to `'builder'` which is required for `documents->get()` to find the document. Verify this ordering is preserved.
  - **Files to read first:** The full `class-elementor-builder-tools.php` (already know this), check how `ElementorCreatePage::execute()` and `ElementorUpdatePageLayout::execute()` call the methods.

## Loop 2 (P1): Improve System Prompt & Knowledge

- [x] **2.1 Expand token budget, add planning framework, rewrite Elementor knowledge** — Multiple files
  - **Part A: Expand token budget** — `apps/backend/src/knowledge/prompt-builder.service.ts`
    - Increase `SYSTEM_TOTAL_MAX` from 8000 to 14000 tokens
    - Increase `KNOWLEDGE_CHUNKS` from 2500 to 5000 tokens
    - Keep other budgets (SITE_CONTEXT: 2000, ACTION_MEMORY: 400, CONTENT_STYLE: 600, CUSTOM_INSTRUCTIONS: 300) the same
    - This gives the LLM much more room for deep knowledge on complex topics
  - **Part B: Add planning framework to system prompt** — same file, in the `buildIdentitySection()` or equivalent method that produces the "Core Behavior" / "Complex Tasks" section
    - Add clear instructions teaching the LLM to:
      1. For complex tasks (multi-step, page building, site setup): PLAN FIRST — outline the steps to the user before executing any tools
      2. Break down into concrete tool calls — list what tools will be called in what order
      3. Execute step by step — complete one step, verify the result, then proceed to the next
      4. Verify after completion — use available tools to check the result (e.g., `elementor_get_page_structure` after creating a page)
      5. If something fails, diagnose and retry with a different approach — don't repeat the same failing action
    - Make this a prominent section, not buried in fine print
  - **Part C: Rewrite Elementor knowledge** — `apps/backend/src/knowledge/elementor.md`
    - Read the current file first
    - Transform from API reference to operational playbook
    - Must include:
      - Complete working JSON example of a full Elementor page (hero + content + CTA) with valid element IDs, proper nesting, correct widget types and settings
      - Step-by-step workflow: "To build an Elementor page: 1. Use elementor_create_page with full elements array 2. Verify with elementor_get_page_structure 3. Fix any issues with elementor_update_widget"
      - Common widget examples with their exact settings (heading, text-editor, button, icon-box, image, spacer, container with columns)
      - Container layout patterns: single column, two columns (use inner containers), three columns
      - Styling guidance: background colors via `background_background: 'classic'` + `background_color`, padding/margin via `padding: {unit: 'px', top: '60', ...}`, typography
    - Keep it concise enough to fit within the expanded 5000-token knowledge budget
  - **Part D: Update capabilities knowledge** — `apps/backend/src/knowledge/wally-capabilities.md`
    - Read current file first
    - Add a section on complex task awareness:
      - "For page building tasks: always generate the COMPLETE elements array with all sections in a single elementor_create_page call. Do not create empty pages and try to add content later."
      - "For multi-step tasks: tell the user your plan BEFORE executing. Example: 'I'll create the page with 3 sections: hero, features, and CTA. Let me build that now.'"
      - "After creating or modifying Elementor pages: verify with elementor_get_page_structure to confirm the content was saved correctly."

## Loop 3 (P2): Improve Tool Descriptions with Examples

- [x] **3.1 Add complete working examples to Elementor builder tool descriptions** — `apps/wally/includes/tools/class-elementor-builder-tools.php`
  - **`elementor_create_page` description** — Add a complete working example at the end of the description string showing a minimal but real page with:
    - A hero container with heading + text + button
    - A features container with 3 icon-boxes
    - A CTA container with heading + button
    - All element IDs must be valid 8-char hex strings
    - All required fields present (id, elType, widgetType, isInner, settings, elements)
    - Show container nesting for multi-column layouts (inner containers)
  - **`elementor_update_page_layout` description** — Reference the same example format, note that it fully replaces existing content
  - **`elementor_add_widget` description** — Add 2-3 complete widget examples (heading, text-editor, button) with their settings
  - **Error messages** — In `execute()` methods, when returning errors, add actionable suggestions. Example: Instead of just "Container not found: {id}" add "Container not found: {id}. Use elementor_get_page_structure to find valid container IDs first."
  - **Read the full file first** to understand current description lengths and style

## Loop 4 (P3): Add Page Verification Tool

- [x] **4.1 Create `elementor_verify_page` tool** — `apps/wally/includes/tools/class-elementor-builder-tools.php`
  - **Purpose:** After creating/modifying an Elementor page, the LLM can call this tool to verify the page has renderable content. This closes the feedback loop.
  - **What it checks:**
    1. `_elementor_data` exists and is valid JSON
    2. The elements array is not empty
    3. At least one widget exists in the tree (not just empty containers)
    4. `_elementor_edit_mode` is set to `'builder'`
    5. CSS file exists or can be generated (check `_elementor_css` meta)
    6. `post_content` is not empty (Elementor saves rendered HTML here)
  - **Returns:** A health check report: `{ success: true, data: { has_data: true, element_count: 12, widget_count: 8, has_css: true, has_rendered_content: true, status: 'healthy' } }` or identifies specific problems: `{ success: true, data: { ..., status: 'unhealthy', issues: ['No CSS generated — try clearing cache', 'post_content is empty — page may not render'] } }`
  - **Tool metadata:** name: `elementor_verify_page`, category: `elementor`, action: `read`, capability: `edit_posts`, no confirmation needed
  - **Description for LLM:** "Verify an Elementor page has renderable content. Call this after elementor_create_page or elementor_update_page_layout to confirm the page will display correctly. Reports issues if the page may appear blank."
  - Add the class to the existing `class-elementor-builder-tools.php` file, extending `ElementorBuilderBase`

## Discovered
<!-- Ralph adds discovered tasks here -->
