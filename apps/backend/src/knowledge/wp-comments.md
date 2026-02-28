# WordPress Comments System Reference

## Database Tables

**`wp_comments`** columns: comment_ID, comment_post_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_date_gmt, comment_content, comment_karma, comment_approved, comment_agent, comment_type, comment_parent, user_id

**`wp_commentmeta`** columns: meta_id, comment_id, meta_key, meta_value

## CRUD Functions

```php
// GET single comment
$comment = get_comment( $comment_id );  // WP_Comment object
// Properties: comment_ID, comment_post_ID, comment_author, comment_content,
//             comment_approved, comment_date, comment_parent, user_id

// GET multiple comments
$comments = get_comments( [
    'post_id'    => 42,
    'status'     => 'approve',         // approve, hold, spam, trash, all
    'type'       => 'comment',         // comment, pingback, trackback, or custom
    'parent'     => 0,                 // Top-level only
    'number'     => 20,
    'offset'     => 0,
    'orderby'    => 'comment_date_gmt',
    'order'      => 'DESC',
    'author_email' => 'user@example.com',
    'search'     => 'keyword',
    'date_query' => [ [ 'after' => '2024-01-01' ] ],
    'meta_query' => [ [ 'key' => 'rating', 'value' => 5, 'type' => 'NUMERIC' ] ],
    'count'      => false,             // true to return count instead of objects
] );

// INSERT
$comment_id = wp_insert_comment( [
    'comment_post_ID'      => 42,
    'comment_author'       => 'John',
    'comment_author_email' => 'john@example.com',
    'comment_content'      => 'Great post!',
    'comment_approved'     => 1,        // 1 = approved, 0 = pending
    'comment_parent'       => 0,        // 0 = top-level, or parent comment ID
    'user_id'              => 5,        // 0 for anonymous
    'comment_type'         => '',       // '' for normal comment
] );

// Alternative: wp_new_comment() applies moderation filters + sends notifications
$comment_id = wp_new_comment( [
    'comment_post_ID'      => 42,
    'comment_content'      => 'Hello!',
    'comment_author'       => 'John',
    'comment_author_email' => 'john@example.com',
], true );  // true = check for wp_die on duplicates

// UPDATE
wp_update_comment( [
    'comment_ID'       => $comment_id,
    'comment_content'  => 'Updated content',
    'comment_approved' => 1,
] );

// DELETE
wp_delete_comment( $comment_id, $force_delete = false );
// $force_delete = false: moves to trash. true: permanently deletes.
```

## Comment Statuses

| Value | Meaning |
|---|---|
| `1` or `'approve'` | Approved/visible |
| `0` or `'hold'` | Pending moderation |
| `'spam'` | Marked as spam |
| `'trash'` | Trashed |

```php
// Change status
wp_set_comment_status( $comment_id, 'approve' );  // approve, hold, spam, trash
```

## Comment Meta

```php
get_comment_meta( $comment_id, $key, $single = true );
update_comment_meta( $comment_id, $key, $value );
add_comment_meta( $comment_id, $key, $value, $unique = false );
delete_comment_meta( $comment_id, $key );
```

## Comment Count

```php
// Get comment counts by status for a post
$counts = wp_count_comments( $post_id );
// Returns object: approved, awaiting_moderation, spam, trash, total_comments
// Pass 0 or omit for site-wide counts
```

## Hooks

```php
// After a comment is inserted
add_action( 'comment_post', function( $comment_id, $comment_approved, $commentdata ) {}, 10, 3 );

// Before approval decision
add_filter( 'pre_comment_approved', function( $approved, $commentdata ) {
    return $approved;  // 1, 0, 'spam', or WP_Error
}, 10, 2 );

// Status transitions
add_action( 'transition_comment_status', function( $new_status, $old_status, $comment ) {}, 10, 3 );

// Specific transitions: approved_to_spam, hold_to_approve, etc.
add_action( 'comment_unapproved_to_approved', function( $comment ) {} );

// After edit/delete
add_action( 'edit_comment', function( $comment_id, $data ) {}, 10, 2 );
add_action( 'delete_comment', function( $comment_id, $comment ) {}, 10, 2 );
```

## Discussion Settings (wp_options)

| Option Key | Values | Description |
|---|---|---|
| `default_comment_status` | `open` / `closed` | Default for new posts |
| `require_name_email` | `1` / `0` | Require name and email |
| `comment_moderation` | `1` / `0` | Hold all comments for moderation |
| `comment_previously_approved` | `1` / `0` | Must have prior approved comment |
| `comments_notify` | `1` / `0` | Email post author on new comment |
| `moderation_notify` | `1` / `0` | Email admin for moderation |
| `thread_comments` | `1` / `0` | Enable threaded comments |
| `thread_comments_depth` | `1`-`10` | Max threading depth |
| `comments_per_page` | int | Comments per page |
| `comment_order` | `asc` / `desc` | Display order |
| `close_comments_for_old_posts` | `1` / `0` | Auto-close comments |
| `close_comments_days_old` | int | Days before auto-close |

## Per-Post Comment Control

```php
// Check/set per-post comment status
$status = $post->comment_status;  // 'open' or 'closed'

// Update via wp_update_post
wp_update_post( [ 'ID' => $post_id, 'comment_status' => 'closed' ] );
```

## Gotchas

- `wp_insert_comment()` bypasses moderation and notification -- use `wp_new_comment()` for user-submitted comments.
- Comment `comment_type` is empty string for regular comments -- not `'comment'`. Filtering by `'comment'` type in `get_comments()` works because WP maps it internally.
- `wp_delete_comment()` with `$force_delete = false` only trashes. Trashed comments are auto-purged based on `EMPTY_TRASH_DAYS`.
- Akismet stores spam detection data in comment meta: `akismet_result`, `akismet_history`.
- `get_comments()` returns `WP_Comment` objects with magic property access to meta via `get_comment_meta()`.
