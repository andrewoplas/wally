# Gutenberg Blocks

## When to Use
- User wants to create or update a page/post with Gutenberg block content
- User wants to build a landing page, about page, contact page, or blog post
- Site uses the WordPress block editor (Gutenberg) — no Elementor active

## Available Tools
- `create_post` — create a new page/post; pass block markup in the `content` field
- `update_post` — update a page/post with new block markup
- `search_content` — find pages/posts containing specific text
- `replace_content` — replace text across post content (requires confirmation)

## Workflows

### Create a Page with Block Content
1. Build the full block markup using the reference below
2. Call `create_post` with `post_type: 'page'`, `title`, `status: 'publish'`, and `content` (block HTML)
3. The entire block structure goes in the `content` field

### Update a Page's Content
1. Call `list_posts` with `post_type: 'page'` and `search` to find the page ID
2. Call `update_post` with `id` and the new `content` (complete block markup)

## Block Markup Reference

Block syntax: `<!-- wp:block-name {"attr":"value"} -->inner HTML<!-- /wp:block-name -->`
Self-closing: `<!-- wp:separator /-->`

### Heading
```html
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Section Title</h2><!-- /wp:heading -->
<!-- wp:heading {"level":2,"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Centered Title</h2><!-- /wp:heading -->
```

### Paragraph
```html
<!-- wp:paragraph --><p>Regular paragraph text.</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Centered text.</p><!-- /wp:paragraph -->
```

### Buttons
```html
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons">
<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact">Get in Touch</a></div><!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/learn-more">Learn More</a></div><!-- /wp:button -->
</div><!-- /wp:buttons -->
```

### Group (section wrapper — use for every page section)
```html
<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group">
<!-- inner blocks here -->
</div><!-- /wp:group -->
```
With background: `{"backgroundColor":"pale-cyan-blue","layout":{"type":"constrained"}}` + add `has-pale-cyan-blue-background-color has-background` to div class.

### Columns (3-column)
```html
<!-- wp:columns --><div class="wp-block-columns">
<!-- wp:column --><div class="wp-block-column"><!-- content --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- content --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- content --></div><!-- /wp:column -->
</div><!-- /wp:columns -->
```
Custom widths: `<!-- wp:column {"width":"66.66%"} --><div class="wp-block-column" style="flex-basis:66.66%">`.

### Cover (hero / banner with background color)
```html
<!-- wp:cover {"overlayColor":"black","minHeight":500,"isDark":true,"layout":{"type":"constrained"}} --><div class="wp-block-cover is-dark" style="min-height:500px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- heading, paragraph, buttons here -->
</div></div><!-- /wp:cover -->
```
With image: add `"url":"https://example.com/hero.jpg","dimRatio":60` to cover attributes.

### Media & Text (image + text side by side)
```html
<!-- wp:media-text {"mediaType":"image"} --><div class="wp-block-media-text is-stacked-on-mobile"><div class="wp-block-media-text__content">
<!-- heading and paragraph here -->
</div><figure class="wp-block-media-text__media"><img src="https://example.com/image.jpg" alt=""/></figure></div><!-- /wp:media-text -->
```

### Quote
```html
<!-- wp:quote --><blockquote class="wp-block-quote"><p>"Great testimonial text here."</p><cite>— Name, Company</cite></blockquote><!-- /wp:quote -->
```

### Shortcode (for forms, maps, etc.)
```html
<!-- wp:shortcode -->[contact-form-7 id="123" title="Contact Form"]<!-- /wp:shortcode -->
```

### Spacer / Separator
```html
<!-- wp:spacer {"height":"60px"} --><div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->
<!-- wp:separator /-->
```

## Page Section Patterns
- **Hero**: `cover` block with h1 heading, paragraph, and buttons; `minHeight` ≥ 400px
- **Features (3-up)**: 3-column `columns` inside a `group`; each column gets heading + paragraph
- **About/Story**: `media-text` block for image + text side by side
- **Testimonials**: `columns` inside a `group` with background color; `quote` block per column
- **CTA**: `group` with background color + centered heading + paragraph + buttons
- **Contact**: 2-column `columns`; contact details in one, `shortcode` form embed in the other
- **FAQ**: `group` with alternating h3 headings and paragraphs

## Key Rules
1. Every block must have matching open/close comments: `<!-- wp:name -->...<!-- /wp:name -->`
2. Block attributes in comments must be valid JSON with double quotes
3. Self-closing blocks use `/-->`: `<!-- wp:separator /-->`
4. Always wrap button blocks in a `buttons` container block
5. Always wrap page sections in a `group` block for proper layout
6. Class names in the HTML must match attributes (`backgroundColor: "vivid-cyan-blue"` → `has-vivid-cyan-blue-background-color has-background`)

## Preset Values
**Colors**: `black`, `white`, `vivid-cyan-blue`, `vivid-red`, `pale-cyan-blue`, `vivid-purple`, `light-green-cyan`, `pale-pink`
**Font sizes**: `small`, `medium`, `large`, `x-large`
