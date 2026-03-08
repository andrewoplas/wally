## Elementor Integration

### Workflow: Building an Elementor Page

**Follow this sequence every time:**

1. Call `elementor_create_page` with the COMPLETE `elements` array in ONE call — include all sections, columns, and widgets up front. Never create an empty page and try to add content in separate calls.
2. After creating or updating, call `elementor_get_page_structure` to verify the content saved correctly.
3. If anything is missing or wrong, use `elementor_update_page_layout` to replace the full layout.

### Element Structure

All IDs must be exactly 8 hex characters (0-9, a-f). Every ID on the page must be unique — never reuse an ID.

```json
{ "id": "a1b2c3d4", "elType": "container", "isInner": false, "settings": {}, "elements": [] }
{ "id": "e5f6a7b8", "elType": "widget", "widgetType": "heading", "isInner": false, "settings": { "title": "Hello" }, "elements": [] }
```

### Complete Page Example (hero + features + CTA)

```json
[
  {
    "id": "aaa11111", "elType": "container", "isInner": false,
    "settings": {
      "background_background": "classic", "background_color": "#1a1a2e",
      "padding": {"unit":"px","top":"80","right":"40","bottom":"80","left":"40","isLinked":false}
    },
    "elements": [
      {"id":"aaa11112","elType":"widget","widgetType":"heading","isInner":false,
       "settings":{"title":"Your Powerful Headline","header_size":"h1","align":"center","title_color":"#ffffff"},"elements":[]},
      {"id":"aaa11113","elType":"widget","widgetType":"text-editor","isInner":false,
       "settings":{"editor":"<p style='text-align:center;color:#cccccc;'>A compelling sub-headline that explains your value.</p>"},"elements":[]},
      {"id":"aaa11114","elType":"widget","widgetType":"button","isInner":false,
       "settings":{"text":"Get Started","link":{"url":"#"},"align":"center"},"elements":[]}
    ]
  },
  {
    "id": "bbb22222", "elType": "container", "isInner": false,
    "settings": {
      "padding": {"unit":"px","top":"60","right":"40","bottom":"60","left":"40","isLinked":false},
      "flex_direction": "row"
    },
    "elements": [
      {"id":"bbb22223","elType":"container","isInner":true,"settings":{"width":{"unit":"%","size":33}},
       "elements":[{"id":"bbb22224","elType":"widget","widgetType":"icon-box","isInner":false,
          "settings":{"icon":{"value":"fas fa-star","library":"fa-solid"},"title_text":"Feature One","description_text":"Description of your first key feature."},"elements":[]}]},
      {"id":"bbb22225","elType":"container","isInner":true,"settings":{"width":{"unit":"%","size":33}},
       "elements":[{"id":"bbb22226","elType":"widget","widgetType":"icon-box","isInner":false,
          "settings":{"icon":{"value":"fas fa-bolt","library":"fa-solid"},"title_text":"Feature Two","description_text":"Description of your second key feature."},"elements":[]}]},
      {"id":"bbb22227","elType":"container","isInner":true,"settings":{"width":{"unit":"%","size":33}},
       "elements":[{"id":"bbb22228","elType":"widget","widgetType":"icon-box","isInner":false,
          "settings":{"icon":{"value":"fas fa-check","library":"fa-solid"},"title_text":"Feature Three","description_text":"Description of your third key feature."},"elements":[]}]}
    ]
  },
  {
    "id": "ccc33333", "elType": "container", "isInner": false,
    "settings": {
      "background_background": "classic", "background_color": "#0066cc",
      "padding": {"unit":"px","top":"60","right":"40","bottom":"60","left":"40","isLinked":false}
    },
    "elements": [
      {"id":"ccc33334","elType":"widget","widgetType":"heading","isInner":false,
       "settings":{"title":"Ready to Get Started?","header_size":"h2","align":"center","title_color":"#ffffff"},"elements":[]},
      {"id":"ccc33335","elType":"widget","widgetType":"button","isInner":false,
       "settings":{"text":"Contact Us","link":{"url":"/contact"},"align":"center"},"elements":[]}
    ]
  }
]
```

### Container Layout Patterns

**Single column (full-width section):**
Outer container with `"isInner": false` containing widgets directly in `elements`.

**Two columns:**
```json
{"id":"sec00001","elType":"container","isInner":false,"settings":{"flex_direction":"row"},"elements":[
  {"id":"col00011","elType":"container","isInner":true,"settings":{"width":{"unit":"%","size":50}},"elements":[...widgets...]},
  {"id":"col00012","elType":"container","isInner":true,"settings":{"width":{"unit":"%","size":50}},"elements":[...widgets...]}
]}
```

**Three columns:** Same pattern with `"size": 33` for each of three inner containers.

### Widget Settings Reference

| Widget | Key settings |
|--------|-------------|
| heading | `title`, `header_size` (h1-h6), `align` (left/center/right), `title_color` |
| text-editor | `editor` (HTML string — use inline styles for color/alignment) |
| button | `text`, `link.url`, `align`, `button_type` (default/info/success/warning/danger) |
| image | `image.url`, `image.id` (0 if no WP media ID), `align` |
| icon-box | `icon.value` ("fas fa-star"), `icon.library` ("fa-solid"), `title_text`, `description_text` |
| spacer | `space` ({"unit":"px","size":50,"sizes":[]}) |
| divider | `weight` ({"unit":"px","size":1}), `color` |
| html | `html` (raw HTML string) |
| shortcode | `shortcode` ("[contact-form-7 id=1]") |

### Styling Reference

- **Background color:** `"background_background": "classic", "background_color": "#1a1a2e"`
- **Padding/Margin:** `{"unit": "px", "top": "60", "right": "40", "bottom": "60", "left": "40", "isLinked": false}`
- **Heading text color:** `"title_color": "#ffffff"` in settings
- **Text color in editor:** Use inline HTML: `<p style='color:#333;text-align:center;'>text</p>`
- **Column width (inner containers):** `"width": {"unit": "%", "size": 50}`

### Searching Elementor Content

Standard `post_content` search misses Elementor text. Use `elementor_search_content` — it searches `_elementor_data` recursively. For replacing text in Elementor, use `elementor_replace_content`.
