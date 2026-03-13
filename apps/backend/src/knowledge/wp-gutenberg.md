# WordPress Block Editor (Gutenberg)

## When to Use
- User asks about blocks, the block editor, Gutenberg, or block-based content
- User asks about reusable blocks, block patterns, or Full Site Editing (FSE)
- User wants to understand how block content is stored or structured

## Key Patterns

### Block Storage Format
Blocks are stored in `post_content` as HTML comments with JSON attributes:
```
<!-- wp:paragraph -->
<p>Text content here.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>Title</h2>
<!-- /wp:heading -->
```
Self-closing blocks: `<!-- wp:separator /-->`

### Core Block Names (wp: namespace)
- **Text**: paragraph, heading, list, quote, code, preformatted
- **Media**: image, gallery, audio, video, file, cover, media-text
- **Layout**: group, columns, column, buttons, separator, spacer
- **Embeds**: embed, html, shortcode
- **Widgets**: latest-posts, categories, search, navigation, page-list
- **Theme (FSE)**: site-title, site-logo, query, post-template, post-title, post-content, template-part

### Reusable Blocks
- Stored as post type `wp_block`
- Referenced in content as: `<!-- wp:block {"ref":123} /-->`
- Accessible via `list_posts` with `post_type: 'wp_block'`

### Template Parts (FSE/Block Themes)
- Post type `wp_template_part` stored in the database
- File-based templates in theme's `templates/` and `parts/` directories
- Accessible via `list_posts` with `post_type: 'wp_template_part'`

### Block Content in Wally Tools
- `get_post` returns `post_content` with block markup included
- `search_content` searches through block content (HTML + block comments)
- `replace_content` can modify text within blocks but does NOT understand block structure
- `create_post` / `update_post` accept block markup in the `content` field

### Classic vs Block Editor
- Classic editor posts have no block delimiters — `has_blocks()` returns false
- Mixed content (classic + blocks) can exist in the same post
- Classic block: `<!-- wp:freeform -->` wraps non-block content

## Relevant Wally Tools
- `list_posts` with `post_type: 'wp_block'` — list reusable blocks
- `get_post` — read block content from any post/page
- `search_content` — search across block-based content
- `replace_content` — find/replace text in block content (requires confirmation)
- `create_post` / `update_post` — create/update posts with block markup

## Important Notes
- Wally cannot add/remove individual blocks — it works with the full post content as a string
- Block attributes in HTML comments are JSON — invalid JSON breaks the block in the editor
- Nested blocks (columns, groups) contain child blocks — content can be deeply nested
- For Elementor pages, use Elementor-specific tools instead — Elementor stores data in postmeta, not block markup
