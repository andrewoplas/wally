# Image Optimization Plugins

## When to Use
- User mentions Smush, EWWW, ShortPixel, WebP, AVIF, or image compression
- User asks about image optimization, page speed, or large image files
- User wants to check or change image optimization settings

## Available Tools
- `list_plugins` — detect which image optimization plugin is active
- `get_option` — read optimization plugin settings
- `update_option` — change optimization settings (requires confirmation)
- `get_site_health` — check for image-related performance issues

## Workflows

### Detect Active Plugin
1. Call `list_plugins`
2. Look for: `wp-smushit` (Smush), `ewww-image-optimizer`, `shortpixel-image-optimiser`, `webp-converter-for-media` (Converter for Media)

### Smush — Read Settings
1. Call `get_option` with key `wp-smush-settings`
2. Key settings: `auto` (auto-optimize on upload), `lossy` (lossy compression), `strip_exif`, `lazy_load`, `webp`, `backup` (keep originals)
3. CDN status: `get_option` with key `wp-smush-cdn_status`

### EWWW — Read Settings
1. Call `get_option` with keys prefixed `ewww_image_optimizer_*`:
   - `ewww_image_optimizer_jpg_level` — compression level (10=none, 20=lossless, 30=lossy, 40=max)
   - `ewww_image_optimizer_webp` — WebP conversion enabled
   - `ewww_image_optimizer_lazy_load` — lazy loading enabled
2. Do NOT expose `ewww_image_optimizer_cloud_key` (API key)

### ShortPixel — Read Settings
1. Call `get_option` with key `wp-short-pixel-options`
2. Key settings: `compressionType` (lossy/glossy/lossless), `createWebp`, `createAvif`, `backupImages`
3. Do NOT expose `apiKey`

### Converter for Media — Read Settings
1. Call `get_option` with key `webpc_settings`
2. Key settings: `output_formats` (webp, avif), `quality_webp`, `quality_avif`

### Update Optimization Settings
1. Identify the correct option key for the active plugin (see above)
2. Call `update_option` with key and new value (requires confirmation)
3. Warn user: "Changing settings only affects new uploads. To re-optimize existing images, run a bulk optimization from the plugin's admin page."

### Check Image Performance
1. Call `get_site_health` for performance recommendations
2. Call `list_plugins` to verify an optimization plugin is active

## Important Notes
- Wally cannot trigger bulk optimization — guide user to the plugin's admin page (Media > Smush, Media > EWWW, etc.)
- API keys (EWWW cloud key, ShortPixel API key) are sensitive — do NOT expose them
- Changing compression settings only affects new uploads — existing images need a bulk re-optimization run
- WebP/AVIF files are served via server rewrite rules — no URL changes needed in content
- All plugins hook into uploads to auto-optimize — ensure `auto` setting is enabled for hands-off optimization
- Original images can be backed up for restoration — check `backup` setting in each plugin
- Multiple image optimization plugins should NOT be active simultaneously — check `list_plugins` and warn if duplicates found
