## Media & Attachments

### Attachment Data Model

Media files are stored as post type `attachment` in `wp_posts`. The file itself lives in `wp-content/uploads/` organized by year/month subdirectories. Key columns used: `post_mime_type` (e.g., `image/jpeg`, `application/pdf`), `post_title`, `post_content` (description), `post_excerpt` (caption), `guid` (full URL, not guaranteed unique or current). The attachment can be associated with a parent post via `post_parent`.

### Key Meta Fields

- `_wp_attached_file` — relative path from uploads/ (e.g., `2024/01/photo.jpg`)
- `_wp_attachment_metadata` — serialized array with `width`, `height`, `file`, `sizes` (array of generated thumbnails with width/height/file/mime-type), `image_meta` (EXIF data: aperture, camera, focal_length, etc.)
- `_wp_attachment_image_alt` — alt text for images

### Upload Directory

`wp_upload_dir()` returns an associative array:
- `path` — absolute server path to current upload directory (e.g., `/var/www/wp-content/uploads/2024/01`)
- `url` — full URL to current upload directory
- `subdir` — current subdirectory (e.g., `/2024/01`)
- `basedir` — absolute path to uploads base (e.g., `/var/www/wp-content/uploads`)
- `baseurl` — full URL to uploads base
- `error` — false or error message string

WordPress organizes uploads into `yyyy/mm` subdirectories by default. This can be disabled in Settings > Media ("Organize my uploads into month- and year-based folders").

### Uploading Files

**From form upload (browser `$_FILES`):**

`wp_handle_upload( $file, $overrides, $time )` — low-level upload handler.
- `$file` (array) — single element from `$_FILES` with keys: `name`, `type`, `tmp_name`, `size`, `error`
- `$overrides` (array|false) — options: `test_form` (bool, validate POST action), `test_size` (bool), `test_type` (bool, validate MIME), `mimes` (array, allowed MIMEs), `unique_filename_callback` (callable), `upload_error_handler` (callable)
- `$time` (string|null) — format `'yyyy/mm'` to override upload subdirectory
- Returns array with `file` (path), `url`, `type` on success; array with `error` (string) on failure

`media_handle_upload( $file_id, $post_id, $post_data, $overrides )` — high-level: handles upload, creates attachment post, generates metadata.
- `$file_id` (string) — index into `$_FILES` array
- `$post_id` (int) — parent post ID (use 0 for unattached)
- `$post_data` (array) — override attachment post fields (post_title, post_content, etc.)
- `$overrides` (array) — passed to `wp_handle_upload()`, defaults `test_form` to false
- Returns attachment ID (int) on success, `WP_Error` on failure

**From external URL (sideloading):**

`wp_handle_sideload( $file, $overrides, $time )` — same as `wp_handle_upload()` but for files not from a form submission (downloaded or programmatically created). Fires `wp_handle_sideload` action instead of `wp_handle_upload`.

`media_sideload_image( $file, $post_id, $desc, $return_type )` — downloads an image from URL, sideloads it, creates attachment.
- `$file` (string) — URL of the image to download
- `$post_id` (int) — parent post ID
- `$desc` (string|null) — sets the attachment title
- `$return_type` (string) — `'html'` (img tag), `'src'` (URL string), or `'id'` (attachment ID)
- Returns string|int|WP_Error depending on `$return_type`
- Requires these includes in non-admin context:
  ```php
  require_once ABSPATH . 'wp-admin/includes/media.php';
  require_once ABSPATH . 'wp-admin/includes/file.php';
  require_once ABSPATH . 'wp-admin/includes/image.php';
  ```

**Programmatic upload (raw file data):**

1. `wp_upload_bits( $filename, null, $file_contents )` — write raw data to uploads/
2. `wp_insert_attachment( $attachment_data, $file_path, $parent_post_id )` — create attachment post
3. `wp_generate_attachment_metadata( $attachment_id, $file_path )` — generate all image sizes
4. `wp_update_attachment_metadata( $attachment_id, $metadata )` — save generated metadata

### Image Sizes

WordPress generates multiple sizes on upload. Built-in sizes:
- `thumbnail` — 150x150, hard crop (default)
- `medium` — max 300x300, proportional
- `medium_large` — max 768px wide, proportional
- `large` — max 1024x1024, proportional
- `full` — original dimensions (no resizing)

**Registering custom sizes:**

```php
add_image_size( $name, $width, $height, $crop );
```
- `$name` (string) — size identifier (avoid reserved names: thumb, thumbnail, medium, medium_large, large, post-thumbnail)
- `$width` (int) — width in pixels (use 9999 for unlimited)
- `$height` (int) — height in pixels (use 9999 for unlimited)
- `$crop` (bool|array) — `false` = proportional scale (default), `true` = hard crop from center, or array for position: `['left'|'center'|'right', 'top'|'center'|'bottom']`

Must be called on `after_setup_theme` hook. Requires `add_theme_support('post-thumbnails')`. Existing images must be regenerated (e.g., via WP-CLI `wp media regenerate`).

**Querying available sizes:**
- `get_intermediate_image_sizes()` — returns array of all registered size names
- `wp_get_registered_image_subsizes()` — returns array with dimensions and crop settings

### Reading Media

`wp_get_attachment_url( $attachment_id )` — full URL to the original file.

`wp_get_attachment_image_src( $attachment_id, $size )` — returns array `[url, width, height, is_resized]` for a specific size, or false.

`wp_get_attachment_image( $attachment_id, $size, $icon, $attr )` — returns complete `<img>` HTML tag.
- `$attachment_id` (int) — attachment ID
- `$size` (string|int[]) — registered size name or `[width, height]` array; default `'thumbnail'`
- `$icon` (bool) — treat as icon; default false
- `$attr` (string|array) — HTML attributes: `src`, `class`, `alt`, `srcset`, `sizes`, `loading` (lazy/eager), `decoding` (async/sync/auto), `fetchpriority` (high/low/auto)
- Returns HTML string or empty string on failure

`wp_get_attachment_metadata( $attachment_id )` — full metadata array (width, height, file, sizes, image_meta).

`get_attached_media( $mime_type, $post_id )` — get all media of a MIME type attached to a post.

`wp_get_attachment_image_url( $attachment_id, $size )` — shortcut returning just the URL for a given size.

### Image Editing

`wp_get_image_editor( $path, $args )` — instantiates a `WP_Image_Editor` and loads the file.
- `$path` (string) — absolute file path to the image
- `$args` (array) — optional arguments
- Returns `WP_Image_Editor` on success, `WP_Error` on failure

**WP_Image_Editor methods:**
- `resize( $max_w, $max_h, $crop )` — scale image to fit within dimensions
- `crop( $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, $src_abs )` — crop a region
- `rotate( $angle )` — rotate by angle in degrees (counterclockwise)
- `flip( $horz, $vert )` — flip horizontally and/or vertically
- `save( $destfilename, $mime_type )` — write to disk
- `get_size()` — returns `['width' => int, 'height' => int]`
- `get_suffix()` — returns dimension suffix (e.g., `300x200`)
- `set_quality( $quality )` — set compression quality (1-100)

Usage pattern:
```php
$image = wp_get_image_editor( '/path/to/image.jpg' );
if ( ! is_wp_error( $image ) ) {
    $image->rotate( 90 );
    $image->resize( 300, 300, true );
    $image->save( '/path/to/new_image.jpg' );
}
```

WordPress ships two implementations: `WP_Image_Editor_GD` (GD library) and `WP_Image_Editor_Imagick` (ImageMagick). Imagick is preferred when available.

### Featured Images (Post Thumbnails)

Requires `add_theme_support('post-thumbnails')` in theme setup.

- `set_post_thumbnail( $post_id, $attachment_id )` — assign featured image
- `get_post_thumbnail_id( $post_id )` — returns attachment ID or empty string
- `has_post_thumbnail( $post_id )` — check if a featured image is set
- `delete_post_thumbnail( $post_id )` — remove featured image
- `the_post_thumbnail( $size, $attr )` — display featured image (must be in the Loop)
- `get_the_post_thumbnail( $post_id, $size, $attr )` — return featured image HTML
- `get_the_post_thumbnail_url( $post_id, $size )` — return featured image URL

### MIME Types & File Validation

`get_allowed_mime_types( $user )` — returns associative array of allowed MIME types keyed by extension regex (e.g., `'jpg|jpeg|jpe' => 'image/jpeg'`).

`wp_check_filetype( $filename, $mimes )` — check a filename against allowed MIME types.
- `$filename` (string) — file name or path
- `$mimes` (string[]|null) — allowed MIMEs array, defaults to `get_allowed_mime_types()`
- Returns array: `['ext' => string|false, 'type' => string|false]`

`wp_check_filetype_and_ext( $file, $filename, $mimes )` — more thorough check that also validates file content matches extension.

**Filtering allowed types:**
```php
add_filter( 'upload_mimes', function( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';       // Add SVG support
    unset( $mimes['exe'] );                 // Remove exe
    return $mimes;
} );
```

### Responsive Images

WordPress automatically adds `srcset` and `sizes` attributes to images since version 4.4. The `srcset` attribute lists all available sizes of the same image so the browser can pick the best one.

- `wp_get_attachment_image_srcset( $attachment_id, $size )` — returns the srcset string
- `wp_get_attachment_image_sizes( $attachment_id, $size )` — returns the sizes attribute string
- `wp_calculate_image_srcset( $size_array, $image_src, $image_meta, $attachment_id )` — calculate srcset for given size
- `wp_calculate_image_sizes( $size, $image_src, $image_meta, $attachment_id )` — calculate sizes attribute

Filter `wp_calculate_image_srcset` to modify or disable srcset output. Filter `max_srcset_image_width` (default 2048) to control maximum width included.

### Hooks

**Upload hooks:**
- `wp_handle_upload_prefilter` — filter file array before upload (validate, rename, reject)
- `wp_handle_upload` — action after successful upload via form
- `wp_handle_sideload` — action after successful sideload
- `wp_upload_dir` — filter upload directory paths

**Attachment hooks:**
- `add_attachment` — action when attachment is first created
- `edit_attachment` — action when attachment metadata is updated
- `delete_attachment` — action before an attachment is deleted
- `wp_generate_attachment_metadata` — filter generated metadata (can modify sizes, add custom meta)
- `wp_get_attachment_image_attributes` — filter img tag attributes before rendering
- `wp_get_attachment_url` — filter attachment URL
- `image_downsize` — filter to short-circuit image downsizing

**Image size hooks:**
- `intermediate_image_sizes` — filter array of generated size names
- `intermediate_image_sizes_advanced` — filter sizes array with full details (width, height, crop)
- `big_image_size_threshold` — filter max dimension before WordPress auto-scales (default 2560px)
