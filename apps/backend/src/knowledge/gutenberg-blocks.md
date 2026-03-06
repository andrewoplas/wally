# Gutenberg Block Markup Reference for Page Building

Use this reference when generating page content with Gutenberg blocks via `create_post` or `update_post`.
All markup goes in the `post_content` field. WordPress stores blocks as HTML comments with JSON attributes.

## Block Syntax

```
<!-- wp:block-name {"attr":"value"} -->
  inner HTML content
<!-- /wp:block-name -->
```

Self-closing (no inner content): `<!-- wp:separator /-->`

## Essential Blocks for Page Building

### Heading
```html
<!-- wp:heading -->
<h2 class="wp-block-heading">Section Title</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Subsection</h3>
<!-- /wp:heading -->

<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Centered Title</h2>
<!-- /wp:heading -->
```

### Paragraph
```html
<!-- wp:paragraph -->
<p>Regular paragraph text.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Centered large text.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"18px"}}} -->
<p style="font-size:18px">Custom sized text.</p>
<!-- /wp:paragraph -->
```

### Image
```html
<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="https://example.com/image.jpg" alt="Description"/></figure>
<!-- /wp:image -->

<!-- wp:image {"align":"center","sizeSlug":"medium"} -->
<figure class="wp-block-image aligncenter size-medium"><img src="https://example.com/image.jpg" alt="Description"/><figcaption class="wp-element-caption">Caption text</figcaption></figure>
<!-- /wp:image -->
```

### Buttons
```html
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact">Get in Touch</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"vivid-cyan-blue"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-vivid-cyan-blue-background-color has-background wp-element-button" href="/signup">Sign Up</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/learn-more">Learn More</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
```

### List
```html
<!-- wp:list -->
<ul class="wp-block-list">
<li>First item</li>
<li>Second item</li>
<li>Third item</li>
</ul>
<!-- /wp:list -->

<!-- wp:list {"ordered":true} -->
<ol class="wp-block-list">
<li>Step one</li>
<li>Step two</li>
</ol>
<!-- /wp:list -->
```

## Layout Blocks

### Group (section wrapper — use for every page section)
```html
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:heading -->
<h2 class="wp-block-heading">Section Title</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Section content goes here.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

Group with background color:
```html
<!-- wp:group {"backgroundColor":"pale-cyan-blue","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-pale-cyan-blue-background-color has-background">
<!-- inner blocks -->
</div>
<!-- /wp:group -->
```

Group with padding:
```html
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
<!-- inner blocks -->
</div>
<!-- /wp:group -->
```

### Columns
```html
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Column 1</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Content for column 1.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Column 2</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Content for column 2.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Column 3</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Content for column 3.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
```

Custom column widths:
```html
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- content --></div>
<!-- /wp:column -->
<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%"><!-- content --></div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
```

### Cover (hero sections, banners)
```html
<!-- wp:cover {"overlayColor":"black","minHeight":500,"isDark":true,"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="min-height:500px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"color":{"text":"#ffffff"}}} -->
<h1 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff">Welcome to Our Business</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffffcc"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffffcc">Your tagline or description goes here.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"white","textColor":"black"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button" href="/contact">Get Started</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div></div>
<!-- /wp:cover -->
```

Cover with image:
```html
<!-- wp:cover {"url":"https://example.com/hero.jpg","dimRatio":60,"overlayColor":"black","minHeight":600,"isDark":true,"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="min-height:600px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-60 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="https://example.com/hero.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
<!-- inner blocks -->
</div></div>
<!-- /wp:cover -->
```

### Media & Text (image + text side by side)
```html
<!-- wp:media-text {"mediaPosition":"right","mediaType":"image"} -->
<div class="wp-block-media-text has-media-on-the-right is-stacked-on-mobile"><div class="wp-block-media-text__content">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">About Us</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Tell your story here. This layout puts text and an image side by side.</p>
<!-- /wp:paragraph -->
</div><figure class="wp-block-media-text__media"><img src="https://example.com/about.jpg" alt="About us"/></figure></div>
<!-- /wp:media-text -->
```

### Separator
```html
<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:separator {"className":"is-style-wide"} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>
<!-- /wp:separator -->
```

### Spacer
```html
<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
```

### Quote / Testimonial
```html
<!-- wp:quote -->
<blockquote class="wp-block-quote">
<p>"This product changed my life! Highly recommend to everyone."</p>
<cite>— Jane Doe, CEO of Example Corp</cite>
</blockquote>
<!-- /wp:quote -->
```

### Table
```html
<!-- wp:table -->
<figure class="wp-block-table"><table class="has-fixed-layout"><thead><tr><th>Feature</th><th>Basic</th><th>Pro</th></tr></thead><tbody><tr><td>Storage</td><td>10 GB</td><td>100 GB</td></tr><tr><td>Support</td><td>Email</td><td>Priority</td></tr></tbody></table></figure>
<!-- /wp:table -->
```

### Embed (YouTube, Vimeo, etc.)
```html
<!-- wp:embed {"url":"https://www.youtube.com/watch?v=VIDEO_ID","type":"video","providerNameSlug":"youtube","responsive":true} -->
<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube"><div class="wp-block-embed__wrapper">
https://www.youtube.com/watch?v=VIDEO_ID
</div></figure>
<!-- /wp:embed -->
```

### Shortcode (for embedding forms, maps, etc.)
```html
<!-- wp:shortcode -->
[contact-form-7 id="123" title="Contact Form"]
<!-- /wp:shortcode -->
```

## WordPress Preset Colors

These color names work in `backgroundColor` and `textColor` attributes:
black, white, pale-pink, vivid-red, luminous-vivid-orange, luminous-vivid-amber,
light-green-cyan, vivid-green-cyan, pale-cyan-blue, vivid-cyan-blue, vivid-purple,
cyan-bluish-gray, light-gray (custom-background)

## WordPress Preset Font Sizes

Use in `fontSize` attribute: small, medium, large, x-large

## Page Building Patterns

### Hero Section Pattern
Use a `cover` block with heading, paragraph, and buttons. Set minHeight to at least 400px.

### Features / Services Section Pattern
Use `columns` (3-column) inside a `group`. Each column gets an icon/image, heading, and paragraph.

### About / Story Section Pattern
Use `media-text` block for image + text side by side.

### Testimonials Section Pattern
Use `columns` inside a `group` with background color. Each column contains a `quote` block.

### CTA (Call to Action) Section Pattern
Use a `group` with background color, containing centered heading, paragraph, and buttons.

### Contact Section Pattern
Use `columns` (2-column): one with contact details (paragraphs), one with a form embed (shortcode block).

### FAQ Section Pattern
Use a `group` with multiple `heading` (h3) + `paragraph` pairs, or the `details` block if available.

## Key Rules

1. Every block must have matching opening and closing comments: `<!-- wp:name -->...<!-- /wp:name -->`
2. Attributes in the comment are JSON — must be valid JSON with double quotes
3. Self-closing blocks use `/-->` at the end: `<!-- wp:separator /-->`
4. Nested blocks (columns, groups, cover) contain other blocks in their innerHTML
5. Class names in the HTML must match the attributes (e.g., `backgroundColor: "vivid-red"` → `has-vivid-red-background-color has-background`)
6. Always use `wp-block-heading` class on headings
7. Always wrap button blocks inside a buttons container block
8. For page layouts, wrap each section in a `group` block for proper spacing
