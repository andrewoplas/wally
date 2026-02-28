# WordPress Block Editor (Gutenberg) Reference

## Block Storage Format

Blocks are stored in `post_content` as HTML comments with JSON attributes:

```html
<!-- wp:paragraph -->
<p>Text content here.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Title</h2>
<!-- /wp:heading -->

<!-- wp:image {"id":42,"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img src="image.jpg" alt="" class="wp-image-42"/></figure>
<!-- /wp:image -->
```

**Structure:** `<!-- wp:namespace/block-name {"attr":"value"} -->innerHTML<!-- /wp:namespace/block-name -->`
Self-closing blocks: `<!-- wp:separator /-->`

## Core Block Names (wp: namespace)

**Text:** paragraph, heading, list, list-item, quote, pullquote, verse, preformatted, code
**Media:** image, gallery, audio, video, file, cover, media-text
**Layout:** group, columns, column, buttons, button, separator, spacer
**Embeds:** embed, html, shortcode
**Widgets:** latest-posts, categories, archives, search, rss, social-links, social-link, navigation, page-list
**Theme (FSE):** site-title, site-logo, site-tagline, query, post-template, post-title, post-content, post-excerpt, post-date, post-featured-image, template-part

## Parsing & Serialization

```php
// Parse post_content into structured array
$blocks = parse_blocks( $post->post_content );
// Each block: [ 'blockName' => 'core/paragraph', 'attrs' => [...], 'innerHTML' => '...', 'innerBlocks' => [...] ]

// Convert blocks back to HTML string
$html = serialize_blocks( $blocks );

// Check for blocks
has_blocks( $content );            // Does content contain any blocks?
has_block( 'core/image', $post );  // Does post contain specific block?
get_dynamic_block_names();         // List of server-rendered block names
```

## Reusable Blocks & Patterns

```php
// Reusable blocks are stored as post type 'wp_block'
// Referenced in content as: <!-- wp:block {"ref":123} /-->

// Register a block pattern
register_block_pattern( 'my-plugin/hero', [
    'title'       => 'Hero Section',
    'description' => 'A hero with heading and CTA',
    'categories'  => [ 'featured' ],
    'content'     => '<!-- wp:group --><!-- wp:heading -->...',
] );

register_block_pattern_category( 'my-category', [ 'label' => 'My Category' ] );
```

## Block Registration (PHP)

```php
register_block_type( 'my-plugin/custom-block', [
    'render_callback' => 'render_my_block',    // Server-side rendering
    'attributes'      => [
        'title' => [ 'type' => 'string', 'default' => '' ],
        'count' => [ 'type' => 'number', 'default' => 5 ],
    ],
] );

// Or register from block.json (recommended since WP 5.8)
register_block_type( __DIR__ . '/build/my-block' );
```

## Template Parts (FSE Themes)

Template parts are post type `wp_template_part` stored in the database. Referenced in templates as `<!-- wp:template-part {"slug":"header","tagName":"header"} /-->`. File-based templates live in the theme's `templates/` and `parts/` directories.

## Modifying Gutenberg Content Programmatically

```php
// Pattern: parse -> modify -> serialize -> save
$blocks = parse_blocks( $post->post_content );
foreach ( $blocks as &$block ) {
    if ( $block['blockName'] === 'core/heading' ) {
        $block['innerHTML'] = '<h2>New Title</h2>';
        $block['innerContent'] = [ '<h2>New Title</h2>' ];
    }
}
$new_content = serialize_blocks( $blocks );
wp_update_post( [ 'ID' => $post_id, 'post_content' => $new_content ] );
```

## Gotchas

- Classic editor posts have no block delimiters -- `has_blocks()` returns false.
- `innerContent` array contains strings and nulls (nulls represent where `innerBlocks` are inserted).
- Nested blocks (columns, groups) store children in `innerBlocks` -- must recurse to process all blocks.
- Block attributes in the HTML comment are JSON -- invalid JSON will break the block in the editor.
- `parse_blocks()` returns a trailing empty block with `blockName => null` for whitespace between blocks.
