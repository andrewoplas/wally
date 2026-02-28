# WordPress Widgets & Sidebars Reference

## Registering Sidebars

```php
add_action( 'widgets_init', function() {
    register_sidebar( [
        'name'          => 'Main Sidebar',
        'id'            => 'sidebar-main',        // Unique ID (lowercase, hyphens)
        'description'   => 'Widgets for main sidebar',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ] );
} );

// Unregister a sidebar
unregister_sidebar( 'sidebar-id' );
```

## Creating Custom Widgets

```php
class My_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'my_widget',                        // Base ID
            'My Custom Widget',                 // Display name
            [ 'description' => 'A short description.' ]
        );
    }

    // Frontend output
    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
        }
        echo '<p>' . esc_html( $instance['text'] ) . '</p>';
        echo $args['after_widget'];
    }

    // Admin form
    public function form( $instance ) {
        $title = $instance['title'] ?? '';
        $text  = $instance['text'] ?? '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input type="text" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   value="<?php echo esc_attr( $title ); ?>" class="widefat">
        </p>
        <?php
    }

    // Save/validate
    public function update( $new_instance, $old_instance ) {
        $instance = [];
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['text']  = sanitize_textarea_field( $new_instance['text'] );
        return $instance;
    }
}

add_action( 'widgets_init', function() {
    register_widget( 'My_Widget' );
} );
```

## Displaying Sidebars

```php
// In a template file
if ( is_active_sidebar( 'sidebar-main' ) ) {
    dynamic_sidebar( 'sidebar-main' );
}
```

## Querying Widget Data

```php
// Get all sidebars and their assigned widgets
$sidebars = wp_get_sidebars_widgets();
// Returns: [ 'sidebar-main' => [ 'my_widget-2', 'text-3' ], 'wp_inactive_widgets' => [...] ]

// Get settings for a specific widget type
$text_widgets = get_option( 'widget_text' );
// Returns: [ 2 => ['title' => '...', 'text' => '...'], '_multiwidget' => 1 ]

// Check if any widgets are assigned to a sidebar
is_active_sidebar( 'sidebar-main' );    // bool

// Check if a specific widget type is active (in any sidebar)
is_active_widget( false, false, 'my_widget' );  // Returns sidebar ID or false
```

## Widget Storage in Database

- **`sidebars_widgets` option:** Maps sidebar IDs to arrays of widget instance IDs (e.g., `text-3`).
- **`widget_{id_base}` options:** Each widget type stores all its instances in a single option. Keyed by instance number.
  - `widget_text` => `[ 2 => ['title' => 'About', 'text' => '...'], 3 => [...], '_multiwidget' => 1 ]`
- **`wp_inactive_widgets`** sidebar holds deactivated widgets that still retain their settings.

## Block-Based Widgets (WP 5.8+)

Since WP 5.8, widget areas support the block editor. Block widgets are stored as widget type `block` with HTML block content.

```php
// Disable block-based widget editor (revert to classic widgets)
add_filter( 'use_widgets_block_editor', '__return_false' );

// Block widgets stored in widget_block option:
// [ 2 => ['content' => '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->'] ]
```

## Built-in Widget Classes

| Class | ID Base | Description |
|---|---|---|
| `WP_Widget_Text` | `text` | Arbitrary text/HTML |
| `WP_Widget_Custom_HTML` | `custom_html` | Raw HTML |
| `WP_Widget_Recent_Posts` | `recent-posts` | Recent posts list |
| `WP_Widget_Categories` | `categories` | Category list/dropdown |
| `WP_Widget_Archives` | `archives` | Monthly archives |
| `WP_Widget_Search` | `search` | Search form |
| `WP_Widget_Calendar` | `calendar` | Post calendar |
| `WP_Widget_Media_Image` | `media_image` | Image widget |
| `WP_Widget_Media_Video` | `media_video` | Video widget |
| `WP_Widget_Media_Audio` | `media_audio` | Audio widget |
| `WP_Widget_Media_Gallery` | `media_gallery` | Gallery widget |
| `WP_Widget_Nav_Menu` | `nav_menu` | Navigation menu |
| `WP_Widget_RSS` | `rss` | RSS feed entries |
| `WP_Widget_Tag_Cloud` | `tag_cloud` | Tag cloud |

## Gotchas

- **Widget IDs** follow the pattern `{id_base}-{instance_number}` (e.g., `text-3`). The instance number is auto-incremented.
- **Unregistering widgets:** Use `unregister_widget('WP_Widget_Search')` on `widgets_init` to remove built-in widgets.
- **Widget settings persist** even when moved to inactive. Deleting from inactive removes settings.
- **`the_widget()`** renders a widget outside of a sidebar: `the_widget( 'WP_Widget_Recent_Posts', ['number' => 5], $args )`.
- Widget areas registered by themes disappear when switching themes -- widgets move to inactive.
