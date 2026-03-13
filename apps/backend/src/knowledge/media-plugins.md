# Media Management Plugins

## When to Use
- User mentions Regenerate Thumbnails, FileBird, media folders, or thumbnail issues
- User asks about image sizes, media organization, or broken thumbnails
- User wants to understand media library structure

## Available Tools
- `list_plugins` — detect which media plugin is active
- `list_posts` — list media attachments with `post_type: 'attachment'`
- `get_post` — get attachment details and metadata
- `get_option` — read plugin settings
- `get_site_info` — check theme (different themes register different image sizes)

## Workflows

### Detect Active Plugin
1. Call `list_plugins`
2. Look for: `regenerate-thumbnails`, `filebird`

### List Media Library Items
1. Call `list_posts` with `post_type: 'attachment'`
2. Each attachment includes title, URL, mime type, and metadata

### Get Media Item Details
1. Call `get_post` with the attachment ID
2. Returns image metadata including registered sizes in `_wp_attachment_metadata` meta

### Check Current Theme's Image Sizes
1. Call `get_site_info` to see the active theme
2. Image sizes are registered by the theme — different themes have different sizes
3. Standard WordPress sizes: `thumbnail` (150x150), `medium` (300x300), `medium_large` (768x0), `large` (1024x1024)

### FileBird — Check Folder Settings
1. Call `get_option` with key `njt_fbv_folder_per_user` — separate folders per user (1/0)
2. Call `get_option` with key `njt_fbv_default_folder` — default upload folder

### When User Reports Broken Thumbnails
1. Call `list_plugins` to check if Regenerate Thumbnails is installed
2. If not installed, suggest: "Install and run Regenerate Thumbnails to fix missing image sizes"
3. If installed, tell user: "Go to Tools > Regenerate Thumbnails and run a bulk regeneration"

## Important Notes
- Wally cannot trigger thumbnail regeneration — guide user to Tools > Regenerate Thumbnails or WP-CLI `wp media regenerate`
- Regenerate Thumbnails should be run after: switching themes, adding new image sizes, or changing size dimensions in Settings > Media
- FileBird uses virtual folders (database only) — no filesystem directories are created; moving files between folders doesn't change URLs
- FileBird folder data is in custom tables (`fbv`, `fbv_attachment_folder`) — not accessible via standard Wally tools
- For bulk media operations, folder management, or thumbnail regeneration, guide user to the respective plugin's admin page
- Media files are stored in `wp-content/uploads/{year}/{month}/` — this path doesn't change regardless of FileBird folder assignments
