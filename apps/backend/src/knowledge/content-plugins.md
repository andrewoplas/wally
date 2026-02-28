## Content Display Plugins

### TablePress (tablepress)
- **Plugin slug**: `tablepress/tablepress.php`.
- **Custom post type**: `tablepress_table` — each table is stored as a post of this type. Post content contains the table data as JSON. Post title is the table name (internal, not necessarily displayed).
- **Shortcode**: `[table id=X /]` — renders table by ID. Attributes:
  - `[table id=1 /]` — render table with ID 1
  - `[table id=1 column_widths="100px|200px|150px" /]` — set column widths
  - `[table id=1 datatables_sort="false" /]` — disable sorting
  - `[table id=1 datatables_paginate="true" datatables_paginate_entries=25 /]` — enable pagination
  - `[table id=1 datatables_filter="true" /]` — enable search filter
  - `[table id=1 datatables_info="false" /]` — hide info text
  - `[table id=1 datatables_scrollx="true" /]` — horizontal scrolling
  - `[table id=1 first_column_th="true" /]` — make first column a header
  - `[table id=1 print_name="above" print_description="below" /]` — show name/description
  - `[table id=1 extra_css_classes="my-class" /]` — add custom CSS classes
- **Table data storage**: Table cell data stored as JSON in the post content of the `tablepress_table` CPT. Structure:
  ```json
  [
    ["Header 1", "Header 2", "Header 3"],
    ["Row 1 Col 1", "Row 1 Col 2", "Row 1 Col 3"],
    ["Row 2 Col 1", "Row 2 Col 2", "Row 2 Col 3"]
  ]
  ```
- **Table meta / options**: Table configuration stored in postmeta and a central option:
  - `_tablepress_table_options` — per-table options (serialized): `table_head` (first row as header), `table_foot` (last row as footer), `alternating_row_colors`, `row_hover`, `print_name`, `print_description`, `extra_css_classes`, `datatables_sort`, `datatables_filter`, `datatables_paginate`, `datatables_paginate_entries`, `datatables_lengthchange`, `datatables_info`, `datatables_scrollx`, `datatables_custom_commands`
  - `_tablepress_table_visibility` — row/column visibility settings (serialized): arrays of `rows` and `columns` with 0 (hidden) or 1 (visible)
  - `tablepress_tables` — wp_options key mapping table IDs to post IDs: `{ "1": 123, "2": 456, "last_id": 2 }`
- **Key functions**:
  ```php
  // Load table data
  $table = TablePress::$model_table->load( $table_id );
  // Returns: ['id', 'name', 'description', 'author', 'data' (2D array), 'options', 'visibility', 'last_modified']

  // Save/update table
  TablePress::$model_table->save( $table );

  // Delete table
  TablePress::$model_table->delete( $table_id );

  // Copy table
  TablePress::$model_table->copy( $table_id );

  // Get all table IDs
  TablePress::$model_table->load_all();

  // Render table programmatically
  echo do_shortcode( '[table id=' . $table_id . ' /]' );
  ```
- **Import / Export**: Supports CSV, Excel (XLSX), JSON, HTML, and ZIP formats.
  - Import: `TablePress::$model_table->import( $data, $format, $name, $description, $existing_table_id )`
  - Export: `TablePress::$model_table->export( $table_id, $format )`
  - Formats: `csv`, `json`, `html`, `xlsx`
- **DataTables JavaScript**: Uses the jQuery DataTables library for sorting, filtering, pagination, and search. DataTables options controlled per-table via `datatables_custom_commands` (raw DataTables JS config) and individual boolean options.
- **Hooks**:
  ```php
  // Filter raw table data before rendering
  apply_filters( 'tablepress_table_raw_render_data', $table, $render_options );

  // Filter individual cell content
  apply_filters( 'tablepress_cell_content', $content, $table_id, $row_idx, $col_idx );

  // Filter rendered table HTML output
  apply_filters( 'tablepress_table_output', $output, $table, $render_options );

  // Filter table options (before render)
  apply_filters( 'tablepress_table_render_options', $render_options, $table );

  // Before/after table render
  do_action( 'tablepress_before_render_table', $table );
  do_action( 'tablepress_after_render_table', $table );

  // Filter DataTables JS parameters
  apply_filters( 'tablepress_datatables_parameters', $parameters, $table_id, $html_id );

  // Filter cell content for formulas/math
  apply_filters( 'tablepress_cell_content_math', $content, $table_id );

  // Modify shortcode attributes
  apply_filters( 'tablepress_shortcode_table_default_shortcode_atts', $default_atts );
  ```
- **CSS**: Tables use class `tablepress` with ID-specific class `tablepress-id-{id}`. Rows: `.row-1`, `.row-2`, etc. Alternating rows: `.even`, `.odd`. Header: `thead th`. DataTables adds `.dataTables_wrapper`, `.dataTables_filter`, `.dataTables_info`, `.dataTables_paginate`.
- **Custom CSS**: Admin area has a dedicated "Custom CSS" textarea. Stored in wp_options key `tablepress_custom_css`. Also saves a static CSS file in `wp-content/uploads/tablepress-combined.min.css` for performance.
- **Detection**:
  ```php
  class_exists( 'TablePress' ) // true if TablePress is active
  defined( 'TABLEPRESS_ABSPATH' ) // plugin path check
  ```

### WP Popular Posts (wordpress-popular-posts)
- **Plugin slug**: `wordpress-popular-posts/wordpress-popular-posts.php`.
- **Database table**: `{prefix}popularpostssummary` — stores page view counts. Columns: `postid` (bigint), `pageviews` (bigint, default 1), `view_date` (date), `view_datetime` (datetime), `last_viewed` (datetime). Indexed by `postid` and `view_date`. Each row represents views for one post on one date (daily granularity for time-range queries).
- **Additional tables**:
  - `{prefix}popularpostsdata` — aggregate data per post. Columns: `postid`, `pageviews` (total lifetime views), `pageviews_date` (date of last aggregate recalculation).
- **Settings**: wp_options key `wpp_settings` (serialized array). Key sub-keys:
  - `is_active` — tracking enabled (1/0)
  - `log_limit` — data retention period (0 = unlimited)
  - `log_limit_period` — retention unit (`day`, `week`, `month`)
  - `ajax` — use AJAX for tracking (recommended for cached sites) (1/0)
  - `cache` — enable output caching (1/0)
  - `cache_ttl` — cache time-to-live (in minutes)
  - `sampling` — enable sampling mode for high-traffic sites (1/0)
  - `sample_rate` — sampling rate (e.g., 100 = count 1 in 100 views)
  - `post_type` — post types to track (comma-separated, default `post`)
  - `freshness` — only show posts published within a time range (1/0)
  - `tools` — sub-array for data management settings: `log`, `thumbnails`, `css`
- **Shortcode**: `[wpp]` — renders popular posts list. Attributes:
  - `[wpp range="last7days" limit=10]` — top 10 from last 7 days
  - `[wpp range="last30days" post_type="post,page"]` — multiple post types
  - `[wpp range="all" order_by="views"]` — all-time by views
  - `[wpp range="custom" time_quantity=3 time_unit="day"]` — custom range
  - `[wpp thumbnail_width=75 thumbnail_height=75]` — with thumbnails
  - `[wpp stats_views=1 stats_comments=1]` — show view/comment counts
  - `[wpp cat=1,5,12]` — filter by category IDs
  - `[wpp tax="custom_taxonomy" term_id=3]` — filter by custom taxonomy
  - `[wpp pid="1,2,3"]` — exclude specific post IDs
  - `[wpp author_id="1,2"]` — filter by author
  - Range values: `last24hours`, `last7days`, `last30days`, `all`, `custom`
  - Order by: `views`, `comments`, `avg` (average daily views)
- **Widget**: `WPP_Widget` — configurable WordPress widget. Registered as "WordPress Popular Posts". Settings mirror shortcode attributes.
- **Gutenberg block**: "WordPress Popular Posts" block available in the block editor.
- **Template tags**:
  ```php
  // Get most popular posts (returns HTML)
  wpp_get_mostpopular( $args );
  // $args is an array with same keys as shortcode attributes

  // Get popular posts as array (for custom templates)
  $popular = new \WordPressPopularPosts\Query( $args );
  $posts = $popular->get_posts();
  // Each post: stdClass with ID, title, url, pageviews, comment_count, date, author, etc.

  // Get views for a specific post
  wpp_get_views( $post_id, $range, $time_quantity, $time_unit );
  // Returns integer view count

  // Track a view programmatically
  // (Not recommended — use the built-in tracking instead)
  ```
- **Tracking mechanism**: Views tracked via AJAX request to REST endpoint `wp-json/wordpress-popular-posts/v2/views/{post_id}` (when `ajax` enabled) or via `WPP_Cache::update_views()` on `template_redirect` (non-AJAX mode). AJAX mode is recommended when using page caching plugins.
- **Caching**: When `cache` is enabled, output cached in transients prefixed `wpp_`. Cache key includes the widget/shortcode parameters hash. For sites with object caching, uses the object cache instead of transients.
- **Sampling**: For high-traffic sites, sampling mode (`sampling` = 1) records only 1 out of every N views (controlled by `sample_rate`) and multiplies by the rate for estimates. Reduces database writes significantly.
- **Hooks**:
  ```php
  // Filter popular posts query arguments
  apply_filters( 'wpp_query_args', $args );

  // Filter the posts data before output
  apply_filters( 'wpp_posts', $posts, $instance_id );

  // Filter individual post HTML
  apply_filters( 'wpp_post', $html, $post, $instance );

  // Filter post title in output
  apply_filters( 'wpp_the_title', $title, $post_id, $instance_id );

  // Filter excerpt in output
  apply_filters( 'wpp_the_excerpt', $excerpt, $post_id, $instance_id );

  // After a view is counted
  do_action( 'wpp_post_views_counted', $post_id, $views );

  // Filter tracking: prevent specific views from being counted
  apply_filters( 'wpp_track_views', $track, $post_id );

  // Filter thumbnail HTML
  apply_filters( 'wpp_thumbnail', $thumbnail_html, $post_id, $instance );

  // Custom HTML output template
  apply_filters( 'wpp_custom_html', $html, $popular_posts );

  // Modify the time range for queries
  apply_filters( 'wpp_default_time_range', $range );
  ```
- **REST API endpoints**:
  - `GET /wp-json/wordpress-popular-posts/v2/posts` — retrieve popular posts (supports query parameters matching shortcode attributes)
  - `POST /wp-json/wordpress-popular-posts/v2/views/{id}` — record a page view
  - `GET /wp-json/wordpress-popular-posts/v2/views/{id}` — get view count for a post
- **CSS**: Default stylesheet `wordpress-popular-posts/style/wpp.css`. Widget container: `.popular-posts-sr`, `.wpp-list`. Individual items: `.wpp-list li`. Thumbnails: `.wpp-thumbnail`. Post title: `.wpp-post-title`. Views/comments: `.wpp-views`, `.wpp-comments`.
- **Custom theme templates**: Supports PHP-based custom templates. Template files placed in theme directory as `single-wpp.php` or specified in widget settings. Template receives `$popular_posts` array.
- **Detection**:
  ```php
  class_exists( 'WordPressPopularPosts\Plugin' ) // true if WP Popular Posts is active
  defined( 'WPP_VERSION' ) // version check
  function_exists( 'wpp_get_mostpopular' ) // template tag check
  ```

### Common Patterns
- Both plugins use shortcodes as the primary rendering mechanism, making them compatible with any page builder (Elementor Shortcode widget, Gutenberg Shortcode block, or direct template insertion).
- TablePress stores data in a custom post type while WP Popular Posts uses custom database tables — use each plugin's API functions rather than direct queries for data access.
- For cached sites, WP Popular Posts should use AJAX tracking mode (`ajax` = 1) to ensure accurate view counts even when pages are served from cache.
- TablePress tables can contain HTML, shortcodes, and links within cells. Cell content is processed through `tablepress_cell_content` filter before rendering.
- Both plugins support import/export: TablePress for table data (CSV, JSON, XLSX) and WP Popular Posts for view data maintenance via the admin tools panel.
- When programmatically creating or modifying tables, always use `TablePress::$model_table->save()` rather than direct `wp_update_post()` calls, as the model handles JSON encoding and option map updates.
