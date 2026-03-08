# Spec: Complex Task Intelligence

## Background

Wally is an AI-powered WordPress admin assistant. It works well for simple atomic tasks (create a post, install a plugin, get site info) but fails at complex multi-step tasks like building Elementor pages, setting up stores, or creating full content strategies.

## Root Causes

### 1. Elementor Save Bug (P0)
`save_elementor_data()` in `class-elementor-builder-tools.php` uses raw `update_post_meta()` + cache clearing instead of Elementor's Document API. This means:
- No CSS file generated → page has no styles
- No `post_content` updated → frontend may show nothing
- No Elementor hooks fired → other systems unaware of changes

### 2. No Planning Framework (P1)
The system prompt (built in `prompt-builder.service.ts`) tells the LLM to "brief the user on multi-step plans" but provides no structured framework. The LLM needs explicit instructions:
- WHEN to plan (complex tasks with 3+ steps)
- HOW to plan (outline steps, list tools, estimate scope)
- HOW to execute (step by step, verify each step)
- HOW to recover (when something fails, try different approach)

### 3. Reference vs Operational Knowledge (P1)
Current `elementor.md` describes the `_elementor_data` JSON structure abstractly. The LLM needs:
- A complete working example it can copy/adapt
- Step-by-step workflows for common tasks
- Common widget recipes with exact settings

### 4. Token Budget Too Small (P1)
8K total system prompt tokens, with only 2.5K for knowledge chunks. Complex Elementor tasks need detailed examples that don't fit in 2.5K.

### 5. No Working Examples in Tool Descriptions (P2)
`elementor_create_page` describes the element structure format but doesn't include a single complete working example. The LLM has to construct valid Elementor JSON from abstract rules.

### 6. No Feedback Loop (P3)
After creating a page, the LLM can only check `elementor_get_page_structure` which shows the data tree. It can't tell if the page actually renders. A verification tool would close this loop.

## Elementor Element Structure Reference

Valid Elementor JSON element:
```json
{
  "id": "a1b2c3d4",
  "elType": "container",
  "isInner": false,
  "settings": {
    "content_width": "full",
    "padding": {"unit": "px", "top": "60", "right": "30", "bottom": "60", "left": "30", "isLinked": false},
    "background_background": "classic",
    "background_color": "#1a1a2e"
  },
  "elements": [
    {
      "id": "e5f6a7b8",
      "elType": "widget",
      "widgetType": "heading",
      "isInner": false,
      "settings": {
        "title": "Welcome to Our Site",
        "header_size": "h1",
        "align": "center",
        "title_color": "#FFFFFF"
      },
      "elements": []
    }
  ]
}
```

Key rules:
- Every element needs a unique 8-char hex `id`
- `elType` is either `"container"` or `"widget"`
- Widgets need `widgetType` (heading, text-editor, button, icon-box, image, spacer, divider, html, shortcode, video)
- Containers hold other elements in `elements[]`
- Widgets have empty `elements: []`
- For multi-column layouts, use an outer container with inner containers (`isInner: true`)

## Planning Framework Template

For the system prompt, add instructions like:

```
## Complex Task Planning

When a user requests a complex task (building pages, setting up features, multi-step operations):

1. **Acknowledge and Plan** — Tell the user what you'll do before starting:
   "I'll create a Book a Demo page with 3 sections: hero, benefits, and CTA. Let me build that now."

2. **Execute in One Shot When Possible** — For page building, generate the COMPLETE layout in a single tool call. Don't create an empty page and try to add content later.

3. **Verify Your Work** — After creating or modifying content, use available tools to check:
   - elementor_get_page_structure to verify Elementor content
   - elementor_verify_page to check page health
   - get_post to verify post content

4. **If Something Fails** — Don't repeat the same action. Diagnose the issue:
   - Check error messages for clues
   - Use read/structure tools to inspect current state
   - Try a different approach (e.g., update_page_layout instead of recreating)

5. **Report Results** — After completion, tell the user what was created and provide links to edit/preview.
```
