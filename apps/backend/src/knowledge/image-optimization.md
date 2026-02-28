## WordPress Image Optimization Plugins

### Smush (WP Smush)
- **Settings**: wp_options key `wp-smush-settings` (serialized). Sub-keys: `auto` (auto-smush on upload), `lossy` (lossy compression), `strip_exif`, `resize` (enable resize), `resize_sizes` (array with width/height max dimensions), `lazy_load`, `webp`, `backup` (keep originals).
- **Per-image data**: `wp-smush-image-stats` postmeta (savings per image — bytes_saved, size_before, size_after), `wp-smushed-image-id` postmeta.
- **Bulk smush**: Progress tracked in wp_options as `wp-smush-bulk_sent` and `wp-smush-bulk_received`.
- **CDN**: Optional Smush CDN serves optimized images via CDN. Status in `wp-smush-cdn_status` option.
- **Functions**: `WP_Smush::get_instance()`, `$smush->core->mod->smush->smush_file($file_path)`.
- **Detection**: `defined('WP_SMUSH_VERSION')` or `class_exists('WP_Smush')`.

### EWWW Image Optimizer
- **Settings**: wp_options keys prefixed `ewww_image_optimizer_*`. Key options: `ewww_image_optimizer_cloud_key` (API key), `ewww_image_optimizer_jpg_level` (compression level: 10=none, 20=lossless, 30=lossy, 40=max), `ewww_image_optimizer_png_level`, `ewww_image_optimizer_webp` (WebP conversion), `ewww_image_optimizer_lazy_load`, `ewww_image_optimizer_resize_existing`, `ewww_image_optimizer_maxmediawidth`, `ewww_image_optimizer_maxmediaheight`.
- **Custom table**: `{prefix}ewwwio_images` — columns: path, gallery, image_size, orig_size, results, converted, webp_size, backup. Optimization data tracked per file here, not in postmeta.
- **WebP conversion**: Creates `.webp` files alongside originals. Served via rewrite rules or `<picture>` tags.
- **Functions**: `ewww_image_optimizer_single_auto($id, $meta)`, `ewwwio_get_option($option_name)`.
- **Detection**: `defined('EWWW_IMAGE_OPTIMIZER_VERSION')`.

### ShortPixel Image Optimizer
- **Settings**: wp_options key `wp-short-pixel-options` (serialized). Sub-keys: `apiKey`, `compressionType` (lossy/glossy/lossless), `resizeImages`, `resizeWidth`, `resizeHeight`, `createWebp`, `createAvif`, `backupImages`.
- **Per-image data**: `_shortpixel_status` postmeta, `_shortpixel_meta` postmeta (serialized — original size, optimized size, compression type).
- **Custom table**: `{prefix}shortpixel_meta` — tracks optimization status for files outside media library.
- **Detection**: `defined('SHORTPIXEL_IMAGE_OPTIMISER_VERSION')`.

### Converter for Media (WebP/AVIF)
- **Settings**: wp_options key `webpc_settings` (serialized). Sub-keys: `output_formats` (array: webp, avif), `conversion_method` (gd/imagick), `quality_webp` (1-100), `quality_avif` (1-100), `dirs` (directories to convert).
- **Converted files**: Stored alongside originals in uploads/ with `.webp`/`.avif` extensions.
- **Delivery**: Uses `.htaccess` or nginx rewrite rules to serve converted format when browser supports it.
- **Detection**: `defined('WEBPC_VERSION')`.

### Common Patterns
- All image optimization plugins hook into `wp_handle_upload` and `wp_generate_attachment_metadata` to auto-optimize on upload.
- WebP/AVIF served via server rewrite rules or `<picture>` tags — no URL changes needed in content.
- Bulk optimization available for existing media library images. Long-running process uses AJAX or background cron.
- Original files optionally backed up (usually in a parallel directory structure). Restoring originals reverses optimization.
- After changing compression settings, re-optimizing existing images requires a bulk run — settings only affect new uploads automatically.
