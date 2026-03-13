# WordPress Comments System

## When to Use
- User asks about comments, discussion settings, or comment moderation
- User wants to enable/disable comments on posts or site-wide
- User asks about spam handling or comment notifications

## Key Patterns

### Comment Statuses
| Status | Meaning |
|--------|---------|
| `1` / `approve` | Approved and visible |
| `0` / `hold` | Pending moderation |
| `spam` | Marked as spam |
| `trash` | Trashed |

### Discussion Settings (wp_options)
These control site-wide comment behavior. All readable via `get_option`, updatable via `update_option`.

| Option Key | Values | Description |
|------------|--------|-------------|
| `default_comment_status` | `open` / `closed` | Default for new posts |
| `require_name_email` | `1` / `0` | Require name and email |
| `comment_moderation` | `1` / `0` | Hold all comments for moderation |
| `comment_previously_approved` | `1` / `0` | Must have prior approved comment |
| `comments_notify` | `1` / `0` | Email post author on new comment |
| `moderation_notify` | `1` / `0` | Email admin for moderation |
| `thread_comments` | `1` / `0` | Enable threaded (nested) comments |
| `thread_comments_depth` | `1`-`10` | Max threading depth |
| `comments_per_page` | int | Comments per page |
| `comment_order` | `asc` / `desc` | Display order |
| `close_comments_for_old_posts` | `1` / `0` | Auto-close comments on old posts |
| `close_comments_days_old` | int | Days before auto-close |

### Per-Post Comment Control
- Each post has a `comment_status` field: `open` or `closed`
- Readable via `get_post` — check the `comment_status` field
- Changeable via `update_post` with `comment_status: 'open'` or `'closed'`

## Workflows

### Check Site-Wide Discussion Settings
1. Call `get_option` with key `default_comment_status`
2. Call `get_option` with key `comment_moderation`
3. Report current settings to user

### Enable/Disable Comments on a Specific Post
1. Call `get_post` with the post ID to check current `comment_status`
2. Call `update_post` with `comment_status: 'open'` or `'closed'` (requires confirmation)

### Change Site-Wide Comment Default
1. Call `update_option` with key `default_comment_status`, value `open` or `closed` (requires confirmation)
2. Note: This only affects NEW posts — existing posts keep their current setting

### Check Comment Counts
- Comment counts are included in `get_post` response for each post

## Relevant Wally Tools
- `get_option` — read discussion settings from `wp_options`
- `update_option` — change discussion settings (requires confirmation)
- `get_post` — check per-post comment status and comment count
- `update_post` — change per-post comment status (requires confirmation)

## Important Notes
- Wally cannot read, create, approve, or delete individual comments — guide user to Comments admin page
- Changing `default_comment_status` does NOT retroactively change existing posts
- Akismet (anti-spam) settings are in `wordpress_api_key` option — do NOT expose the API key
- For bulk comment operations (close comments on all old posts), guide user to Discussion Settings admin page
