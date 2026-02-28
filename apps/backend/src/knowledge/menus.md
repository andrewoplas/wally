## Navigation Menus

### Menu Data Model

WordPress menus are stored as a custom taxonomy `nav_menu` (each menu is a term). Menu items are stored as post type `nav_menu_item` and associated with their menu via the `nav_menu` taxonomy. Each menu item is a post with meta fields defining its properties, and `menu_order` defines position.

### Menu Item Meta Fields

Each `nav_menu_item` post has these meta keys:
- `_menu_item_type` — `"post_type"`, `"taxonomy"`, or `"custom"`
- `_menu_item_object` — object type: `"page"`, `"post"`, `"category"`, `"custom"`, or any registered post type/taxonomy
- `_menu_item_object_id` — ID of the linked object (post ID, term ID, etc.)
- `_menu_item_url` — URL for custom links (only used when type is `"custom"`)
- `_menu_item_target` — link target: `""` (default) or `"_blank"` for new tab
- `_menu_item_menu_item_parent` — parent menu item ID (`"0"` for top-level items)
- `_menu_item_classes` — serialized array of custom CSS classes
- `_menu_item_xfn` — XFN relationship link value
- `menu_order` — integer position in the menu

### Registration

**`register_nav_menus( $locations )`** — register multiple menu locations for a theme.
- `$locations` (string[]) — associative array: `location_slug => description`
- Automatically enables theme support for menus (no need for separate `add_theme_support('menus')`)

```php
function mytheme_register_menus() {
    register_nav_menus( array(
        'primary'   => __( 'Primary Menu', 'my-theme' ),
        'footer'    => __( 'Footer Menu', 'my-theme' ),
        'sidebar'   => __( 'Sidebar Menu', 'my-theme' ),
    ) );
}
add_action( 'after_setup_theme', 'mytheme_register_menus' );
```

**`register_nav_menu( $location, $description )`** — register a single menu location.

**`unregister_nav_menu( $location )`** — remove a registered menu location.

**`get_registered_nav_menus()`** — returns array of all registered locations (slug => description).

### Display

**`wp_nav_menu( $args )`** — display or return a navigation menu.

All arguments with defaults:

| Argument | Type | Default | Description |
|----------|------|---------|-------------|
| `menu` | int\|string\|WP_Term | `''` | Menu ID, slug, name, or WP_Term object |
| `theme_location` | string | `''` | Registered theme location slug |
| `container` | string | `'div'` | Wrapping element tag (`'div'`, `'nav'`, or `false` for none) |
| `container_class` | string | `''` | CSS class on container (default: `menu-{slug}-container`) |
| `container_id` | string | `''` | ID attribute on container |
| `container_aria_label` | string | `''` | aria-label when container is `<nav>` |
| `menu_class` | string | `'menu'` | CSS class on the `<ul>` element |
| `menu_id` | string | `''` | ID on the `<ul>` (default: menu slug, incremented) |
| `echo` | bool | `true` | Whether to echo (`true`) or return (`false`) |
| `fallback_cb` | callable\|false | `'wp_page_menu'` | Callback if menu doesn't exist; `false` for no fallback |
| `before` | string | `''` | HTML before each `<a>` tag (between `<li>` and `<a>`) |
| `after` | string | `''` | HTML after each `<a>` tag |
| `link_before` | string | `''` | HTML before the link text (inside `<a>`) |
| `link_after` | string | `''` | HTML after the link text (inside `<a>`) |
| `items_wrap` | string | `'<ul id="%1$s" class="%2$s">%3$s</ul>'` | printf format: `%1$s` = menu_id, `%2$s` = menu_class, `%3$s` = items |
| `item_spacing` | string | `'preserve'` | Whitespace handling: `'preserve'` or `'discard'` |
| `depth` | int | `0` | Hierarchy depth: `0` = all levels, `1` = top-level only, `2` = two levels, etc. |
| `walker` | object | `''` | Custom Walker instance (default: `Walker_Nav_Menu`) |

Returns: void if `echo` is true; menu HTML string if `echo` is false; `false` if no items found.

Example:
```php
wp_nav_menu( array(
    'theme_location' => 'primary',
    'container'      => 'nav',
    'container_class' => 'main-navigation',
    'container_aria_label' => 'Primary',
    'menu_class'     => 'nav-list',
    'depth'          => 2,
    'fallback_cb'    => false,
) );
```

### Walker_Nav_Menu

The `Walker_Nav_Menu` class renders menu items as an HTML list. Extend it to customize menu output.

**Core methods to override:**

`start_lvl( &$output, $depth, $args )` — opens a submenu `<ul>`. Default outputs `<ul class="sub-menu">`.

`end_lvl( &$output, $depth, $args )` — closes the submenu `</ul>`.

`start_el( &$output, $data_object, $depth, $args, $current_object_id )` — renders each menu item `<li>` and `<a>`. This is where most customization happens.

`end_el( &$output, $data_object, $depth, $args )` — closes the menu item `</li>`.

**Custom walker example:**
```php
class My_Custom_Walker extends Walker_Nav_Menu {
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $classes = implode( ' ', $item->classes );
        $output .= '<li class="' . esc_attr( $classes ) . '">';
        $output .= '<a href="' . esc_url( $item->url ) . '"';
        if ( $item->target ) {
            $output .= ' target="' . esc_attr( $item->target ) . '"';
        }
        $output .= '>' . esc_html( $item->title ) . '</a>';
    }
}

// Usage:
wp_nav_menu( array(
    'theme_location' => 'primary',
    'walker'         => new My_Custom_Walker(),
) );
```

### Reading Menus

- `wp_get_nav_menus( $args )` — list all menus (returns array of WP_Term objects)
- `wp_get_nav_menu_items( $menu_id_or_slug, $args )` — get all items in a menu (returns array of WP_Post objects with menu item properties)
- `wp_get_nav_menu_object( $menu )` — get a single menu term by ID, slug, or name

### Menu Locations

- `get_nav_menu_locations()` — returns array of `theme_location => menu_id` mappings
- `has_nav_menu( $location )` — check if a theme location has a menu assigned
- `get_nav_menu_locations()` returns the raw data from `get_theme_mod('nav_menu_locations')`

**Assigning a menu to a location programmatically:**
```php
$locations = get_nav_menu_locations();
$locations['primary'] = $menu_id;
set_theme_mod( 'nav_menu_locations', $locations );
```

### Programmatic CRUD

**Creating a menu:**
```php
$menu_id = wp_create_nav_menu( 'My Menu' );
```

**Adding/updating menu items:**
```php
wp_update_nav_menu_item( $menu_id, 0, array(  // 0 = create new item
    'menu-item-title'     => 'Home',
    'menu-item-url'       => home_url( '/' ),
    'menu-item-status'    => 'publish',
    'menu-item-type'      => 'custom',
    'menu-item-position'  => 1,
) );

// Link to a page:
wp_update_nav_menu_item( $menu_id, 0, array(
    'menu-item-object-id' => $page_id,
    'menu-item-object'    => 'page',
    'menu-item-type'      => 'post_type',
    'menu-item-status'    => 'publish',
    'menu-item-position'  => 2,
) );

// Link to a category:
wp_update_nav_menu_item( $menu_id, 0, array(
    'menu-item-object-id' => $cat_id,
    'menu-item-object'    => 'category',
    'menu-item-type'      => 'taxonomy',
    'menu-item-status'    => 'publish',
) );

// Create a child item (submenu):
wp_update_nav_menu_item( $menu_id, 0, array(
    'menu-item-title'      => 'Sub Page',
    'menu-item-url'        => '/sub-page/',
    'menu-item-type'       => 'custom',
    'menu-item-status'     => 'publish',
    'menu-item-parent-id'  => $parent_item_id,
) );
```

`wp_update_nav_menu_item( $menu_id, $item_id, $args )` — create (`$item_id = 0`) or update an existing item.

Available `$args` keys: `menu-item-title`, `menu-item-url`, `menu-item-object`, `menu-item-object-id`, `menu-item-parent-id`, `menu-item-position`, `menu-item-type`, `menu-item-target`, `menu-item-classes` (string, space-separated), `menu-item-xfn`, `menu-item-description`, `menu-item-attr-title`, `menu-item-status`.

Returns: menu item database ID (int) on success, `WP_Error` on failure.

**Deleting:**
- `wp_delete_nav_menu( $menu_id )` — delete an entire menu and all its items
- `wp_delete_nav_menu_item( $menu_id, $item_id )` — delete a single menu item (actually a `wp_delete_post` on the nav_menu_item)

### Hooks

**Filters for menu output:**
- `wp_nav_menu_items` — filter the HTML list of menu items (string); params: `$items`, `$args`
- `wp_nav_menu_{$menu->slug}_items` — same but specific to a menu slug
- `wp_nav_menu_objects` — filter the sorted array of menu item objects before rendering
- `wp_nav_menu_args` — filter the wp_nav_menu() arguments array before processing

**Filters for individual items:**
- `nav_menu_css_class` — filter the CSS classes applied to each `<li>`; params: `$classes`, `$item`, `$args`, `$depth`
- `nav_menu_item_id` — filter the ID attribute on each `<li>`
- `nav_menu_link_attributes` — filter the `<a>` tag attributes (href, title, target, rel, class, aria-current)
- `nav_menu_item_title` — filter the menu item title text
- `walker_nav_menu_start_el` — filter the `start_el()` output for each item

**Filters for submenus:**
- `nav_menu_submenu_css_class` — filter CSS classes on `<ul>` submenu elements
- `nav_menu_submenu_attributes` — filter attributes on submenu `<ul>` elements

**Actions:**
- `wp_add_nav_menu_item` — fires after a menu item is added
- `wp_update_nav_menu` — fires after a menu is updated
- `wp_update_nav_menu_item` — fires after a menu item is updated
- `wp_delete_nav_menu` — fires after a menu is deleted

### Block Theme Navigation

In block themes (FSE), navigation menus use the `wp:navigation` block instead of `wp_nav_menu()`. The block references a `wp_navigation` post type (introduced in WP 5.9).

```html
<!-- wp:navigation {"ref":123} /-->
```

The `wp_navigation` post type stores the menu structure as block markup in `post_content`. Individual items use `wp:navigation-link` blocks:

```html
<!-- wp:navigation-link {"label":"Home","url":"/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"About","url":"/about/","kind":"custom"} /-->
<!-- wp:navigation-submenu {"label":"Services","url":"/services/"} -->
    <!-- wp:navigation-link {"label":"Consulting","url":"/consulting/"} /-->
<!-- /wp:navigation-submenu -->
```

Classic menus (nav_menu taxonomy) can still be used alongside block navigation via the Classic Menu block or by converting them to wp_navigation posts.
