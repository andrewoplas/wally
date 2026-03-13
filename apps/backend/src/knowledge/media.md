# Media

## When to Use
- User asks to list, find, or view media files in the media library
- User wants to update an image's title, alt text, caption, or description
- User wants to set or remove a featured image on a post or page
- User asks about media settings (image sizes, upload folder organization)

## Available Tools
- `list_posts` with `post_type: 'attachment'` — list media files
- `get_post` — get a specific media file's details
- `update_post` — update attachment title, description, caption, or alt text
- `get_option` — read media settings (thumbnail sizes, upload organization)
- `update_option` — update media settings (requires confirmation)

## Workflows

### List Media Files
1. Call `list_posts` with `post_type: 'attachment'`
2. Optionally add `search: '<keyword>'` to filter by filename or title
3. Results include attachment ID, title, URL (guid), and mime type

### Update Image Alt Text / Title / Caption
1. Get the attachment ID from `list_posts` with `post_type: 'attachment'` and `search`
2. Call `update_post` with:
   - `title` — image title
   - `excerpt` — image caption
   - `content` — image description
   - `meta: { _wp_attachment_image_alt: '<alt text>' }` — alt text
3. Requires confirmation for meta updates

### Set Featured Image on a Post
1. Find the attachment ID: call `list_posts` with `post_type: 'attachment'` and `search`
2. Call `update_post` with post `id` and `meta: { _thumbnail_id: <attachment_id> }`
3. To remove the featured image: `meta: { _thumbnail_id: '' }`

### Check Media Settings
1. Call `get_option` with key `thumbnail_size_w` / `thumbnail_size_h` for thumbnail dimensions
2. Call `get_option` with key `medium_size_w` / `medium_size_h` for medium dimensions
3. Call `get_option` with key `large_size_w` / `large_size_h` for large dimensions
4. Call `get_option` with key `uploads_use_yearmonth_folders` for folder organization

### Update Media Image Sizes
1. Call `update_option` with `key: 'thumbnail_size_w'` and new pixel value (requires confirmation)
2. Repeat for `thumbnail_size_h`, `medium_size_w`, `medium_size_h`, etc.
3. Tell the user: size changes only apply to future uploads — existing images need regeneration

## Important Notes
- Wally cannot upload new media files — guide user to Media > Add New in WordPress admin
- Wally cannot delete media files — guide user to Media Library in WordPress admin
- Wally cannot regenerate image thumbnails — recommend a regenerate thumbnails plugin or WP-CLI
- Media files are attachments: always use `post_type: 'attachment'` in `list_posts`
- Alt text is in post meta `_wp_attachment_image_alt`, not in the post body
