## WordPress Media Management Plugins

### Regenerate Thumbnails
- **Plugin slug**: `regenerate-thumbnails/regenerate-thumbnails.php` (ID #32)
- **Purpose**: Bulk regenerate image thumbnails after changing registered image sizes, switching themes, or adding new `add_image_size()` definitions.
- **Settings**: No persistent settings in the database. The plugin is stateless and operates on-demand.
- **How it works**: Iterates through media library attachments and calls WordPress core's `wp_generate_attachment_metadata()` to regenerate all registered thumbnail sizes for each image.
- **Key WordPress functions used**:
  - `wp_generate_attachment_metadata($attachment_id, $file)` — core function that generates all registered image sizes
  - `wp_update_attachment_metadata($attachment_id, $metadata)` — saves regenerated metadata
  - `wp_get_registered_image_subsizes()` — returns all registered image sizes (WP 5.3+)
  - `get_intermediate_image_sizes()` — returns registered intermediate size names
  - `image_get_intermediate_size($post_id, $size)` — gets data for a specific image size
  - `wp_get_attachment_metadata($attachment_id)` — retrieves current image metadata
- **Registered image sizes**: Retrieved from `$_wp_additional_image_sizes` global and built-in sizes (`thumbnail`, `medium`, `medium_large`, `large`). Sizes registered via `add_image_size()` in theme/plugin.
- **REST API endpoints** (namespace `regenerate-thumbnails/v1`):
  - `GET /attachmentinfo/{id}` — get info about a single attachment's thumbnails
  - `POST /regenerate/{id}` — regenerate thumbnails for a single attachment
  - `GET /featuredimages` — get all attachment IDs used as featured images
- **Batch processing**: Processes images one at a time via AJAX/REST calls from the admin UI. Each request regenerates one image, preventing timeouts on large libraries.
- **Options during regeneration**:
  - Delete old unregistered thumbnail sizes (clean up orphaned files)
  - Skip existing correctly-sized thumbnails (faster, only regenerate missing sizes)
  - Regenerate only specific image sizes
- **WP-CLI integration**: `wp media regenerate` — the WordPress core CLI command for regenerating thumbnails.
  - `wp media regenerate --yes` — skip confirmation prompt
  - `wp media regenerate --only-missing` — only generate missing sizes
  - `wp media regenerate --image_size=thumbnail` — regenerate specific size only
  - `wp media regenerate 123 456` — regenerate specific attachment IDs
- **Image metadata structure** (stored in `wp_postmeta` as `_wp_attachment_metadata`):
  ```php
  [
      'width' => 1920,
      'height' => 1080,
      'file' => '2024/01/image.jpg',
      'sizes' => [
          'thumbnail' => ['file' => 'image-150x150.jpg', 'width' => 150, 'height' => 150, 'mime-type' => 'image/jpeg'],
          'medium' => ['file' => 'image-300x169.jpg', 'width' => 300, 'height' => 169, 'mime-type' => 'image/jpeg'],
          'large' => ['file' => 'image-1024x576.jpg', 'width' => 1024, 'height' => 576, 'mime-type' => 'image/jpeg'],
          // ... custom sizes
      ],
      'image_meta' => ['aperture' => '0', 'camera' => '', 'created_timestamp' => '0', ...]
  ]
  ```
- **File paths**:
  - Plugin directory: `wp-content/plugins/regenerate-thumbnails/`
  - Generated thumbnails: `wp-content/uploads/{year}/{month}/` — same directory as original image

### FileBird Media Folders
- **Plugin slug**: `filebird/filebird.php` (ID #93)
- **Purpose**: Organize the WordPress media library with a virtual folder/directory structure. Drag-and-drop interface for categorizing media files.
- **Custom DB tables**:
  - `{prefix}fbv` — folder definitions.
    - Columns: `id`, `name` (folder name), `parent` (parent folder ID, 0 for root), `type` (folder type), `ord` (sort order), `created_by` (user ID who created the folder).
  - `{prefix}fbv_attachment_folder` — attachment-to-folder relationships (many-to-many).
    - Columns: `id`, `folder_id` (FK to fbv.id), `attachment_id` (FK to wp_posts.ID).
- **Settings**: wp_options keys prefixed `fbv_*` and `njt_fbv_*`.
  - `njt_fbv_folder_per_user` — separate folder trees per user (1/0)
  - `njt_fbv_default_folder` — default folder for new uploads
  - `njt_fbv_sort_files` — file sort order within folders
  - `njt_fbv_import_from` — import folder structure from other plugins (Enhanced Media Library, etc.)
  - `fbv_db_version` — database schema version
- **REST API endpoints** (namespace `jesuspended/v1` or `jesuspended/v2`):
  - `GET /folders` — list all folders (hierarchical tree)
  - `POST /folders` — create a new folder
  - `PUT /folders/{id}` — rename or move a folder
  - `DELETE /folders/{id}` — delete a folder (does not delete media files)
  - `POST /folders/{id}/attachments` — move/assign attachments to a folder
  - `POST /folders/flat-tree` — get flat folder tree for dropdowns
- **Key classes**:
  - `FileBird\Model\Folder` — folder CRUD operations
    - `Folder::allFolders($order_by)` — get all folders
    - `Folder::createFolder($name, $parent, $type)` — create a folder
    - `Folder::updateFolder($id, $name, $parent)` — update folder
    - `Folder::deleteFolder($id)` — delete folder (keeps attachments)
    - `Folder::getFoldersByAttachmentId($attachment_id)` — get folders for an attachment
  - `FileBird\Model\Folder::setFoldersForPosts($attachment_ids, $folder_id)` — bulk assign attachments to folder
- **Hooks**:
  - `fbv_before_move_file` — action fired before a file is moved to a different folder. Receives attachment ID and target folder ID.
  - `fbv_after_move_file` — action fired after a file is moved. Receives attachment ID and target folder ID.
  - `fbv_before_set_folder` — action fired before assigning attachment(s) to a folder
  - `fbv_after_set_folder` — action fired after assigning attachment(s) to a folder
  - `fbv_before_delete_folder` — action fired before a folder is deleted
  - `fbv_after_delete_folder` — action fired after a folder is deleted
  - `fbv_in_not_in_created_by` — filter to modify folder ownership queries (for per-user folder mode)
- **Folder operations**:
  - **Create**: Creates virtual folder (no filesystem directory created)
  - **Move**: Reassigns `folder_id` in relationship table (no file system changes)
  - **Delete**: Removes folder from `fbv` table and relationships from `fbv_attachment_folder`. Media files remain in the media library (uncategorized).
  - **Nested folders**: Supports unlimited folder depth via `parent` column. Parent `0` = root level.
- **Smart folders** (Pro): Auto-categorization rules based on file type, upload date, or custom criteria. Dynamically populated based on conditions.
- **Drag-and-drop**: Custom JavaScript UI integrated into the WordPress media library modal and the Media Library grid/list views. Works inside Elementor, Gutenberg, and other page builders.
- **Import/Migration**: Can import folder structures from:
  - Enhanced Media Library
  - WordPress Media Library Folders (Premio)
  - Real Media Library
  - WP Media Folder
  - HappyFiles
- **Page builder compatibility**: Works with Elementor, Gutenberg, Beaver Builder, Divi, WPBakery, Oxygen, Bricks, Brizy. FileBird's folder tree appears in each builder's media selection modal.
- **Uncategorized count**: Attachments not assigned to any folder appear under a virtual "Uncategorized" folder in the sidebar.
- **File paths**:
  - Plugin directory: `wp-content/plugins/filebird/`
  - Models: `wp-content/plugins/filebird/app/Model/`
  - REST controllers: `wp-content/plugins/filebird/app/Controller/`

### Common Patterns
- **Regenerate Thumbnails** should be run after: changing themes (different registered image sizes), adding new `add_image_size()` calls, or changing existing size dimensions in Settings > Media. It does not need to run after uploading new images — WordPress generates thumbnails on upload automatically.
- **FileBird** uses virtual folders stored in custom database tables — it does not create physical directories on the filesystem. Moving a file between folders only updates the database relationship, not the actual file path in `wp-content/uploads/`. This is important for URL stability.
- When querying media by folder in custom code, join `{prefix}fbv_attachment_folder` on `attachment_id` to `wp_posts.ID` and filter by `folder_id`.
- For bulk operations on media (regenerate + organize), run Regenerate Thumbnails first, then organize into folders with FileBird, since regeneration does not affect folder assignments.
- Both plugins integrate with the standard WordPress media modal, so they work transparently with Elementor, Gutenberg, and other builders that use `wp.media`.
