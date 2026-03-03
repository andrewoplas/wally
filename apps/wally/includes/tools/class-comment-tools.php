<?php
namespace Wally\Tools;

/**
 * Comment management tools for WordPress.
 *
 * Tools: list_comments, get_comment, update_comment_status, delete_comment, reply_to_comment.
 * Uses WordPress core comment functions with proper capability checks.
 */

/**
 * List WordPress comments with optional filters.
 */
class ListComments extends ToolInterface {

	public function get_name(): string {
		return 'list_comments';
	}

	public function get_description(): string {
		return 'List WordPress comments with optional filters by post, status, author email, or search keyword. Returns comment author, content, status, and date.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'      => [
					'type'        => 'integer',
					'description' => 'Filter comments by post ID.',
				],
				'status'       => [
					'type'        => 'string',
					'description' => 'Filter by comment status.',
					'enum'        => [ 'approve', 'hold', 'spam', 'trash', 'all' ],
					'default'     => 'all',
				],
				'type'         => [
					'type'        => 'string',
					'description' => 'Filter by comment type: "comment" for regular comments, "pingback", "trackback".',
					'enum'        => [ 'comment', 'pingback', 'trackback', 'all' ],
					'default'     => 'comment',
				],
				'author_email' => [
					'type'        => 'string',
					'description' => 'Filter comments by author email address.',
				],
				'search'       => [
					'type'        => 'string',
					'description' => 'Search keyword to filter comments by content or author.',
				],
				'per_page'     => [
					'type'        => 'integer',
					'description' => 'Number of comments to return (max 100).',
					'default'     => 20,
				],
				'page'         => [
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				],
				'order'        => [
					'type'        => 'string',
					'description' => 'Sort direction by date.',
					'enum'        => [ 'ASC', 'DESC' ],
					'default'     => 'DESC',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'moderate_comments';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		$args = [
			'number'  => $per_page,
			'offset'  => ( $page - 1 ) * $per_page,
			'orderby' => 'comment_date_gmt',
			'order'   => strtoupper( $input['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC',
			'status'  => sanitize_key( $input['status'] ?? 'all' ),
		];

		$type = sanitize_key( $input['type'] ?? 'comment' );
		if ( $type !== 'all' ) {
			$args['type'] = $type;
		}

		if ( ! empty( $input['post_id'] ) ) {
			$args['post_id'] = absint( $input['post_id'] );
		}
		if ( ! empty( $input['author_email'] ) ) {
			$args['author_email'] = sanitize_email( $input['author_email'] );
		}
		if ( ! empty( $input['search'] ) ) {
			$args['search'] = sanitize_text_field( $input['search'] );
		}

		$comments = get_comments( $args );

		// Get total count for pagination.
		$count_args          = $args;
		$count_args['count'] = true;
		unset( $count_args['number'], $count_args['offset'] );
		$total = (int) get_comments( $count_args );

		$result = [];
		foreach ( $comments as $comment ) {
			$result[] = [
				'id'           => (int) $comment->comment_ID,
				'post_id'      => (int) $comment->comment_post_ID,
				'author'       => $comment->comment_author,
				'author_email' => $comment->comment_author_email,
				'content'      => $comment->comment_content,
				'status'       => $comment->comment_approved,
				'date'         => $comment->comment_date,
				'parent_id'    => (int) $comment->comment_parent,
				'user_id'      => (int) $comment->user_id,
				'type'         => $comment->comment_type,
			];
		}

		return [
			'comments'    => $result,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Get full details of a single WordPress comment.
 */
class GetComment extends ToolInterface {

	public function get_name(): string {
		return 'get_comment';
	}

	public function get_description(): string {
		return 'Get full details of a single WordPress comment by ID. Returns author, email, content, status, parent comment, date, and the post it belongs to.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'comment_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the comment to retrieve.',
				],
			],
			'required'   => [ 'comment_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'moderate_comments';
	}

	public function execute( array $input ): array {
		$comment_id = absint( $input['comment_id'] );
		$comment    = get_comment( $comment_id );

		if ( ! $comment ) {
			return [ 'error' => "Comment not found: {$comment_id}" ];
		}

		// Map approved values to readable status.
		$status_map = [
			'1'    => 'approved',
			'0'    => 'pending',
			'spam' => 'spam',
			'trash' => 'trash',
		];
		$status = $status_map[ $comment->comment_approved ] ?? $comment->comment_approved;

		return [
			'id'           => (int) $comment->comment_ID,
			'post_id'      => (int) $comment->comment_post_ID,
			'post_title'   => get_the_title( (int) $comment->comment_post_ID ),
			'author'       => $comment->comment_author,
			'author_email' => $comment->comment_author_email,
			'author_url'   => $comment->comment_author_url,
			'author_ip'    => $comment->comment_author_IP,
			'content'      => $comment->comment_content,
			'status'       => $status,
			'date'         => $comment->comment_date,
			'parent_id'    => (int) $comment->comment_parent,
			'user_id'      => (int) $comment->user_id,
			'type'         => $comment->comment_type,
		];
	}
}

/**
 * Update the status of a WordPress comment.
 */
class UpdateCommentStatus extends ToolInterface {

	public function get_name(): string {
		return 'update_comment_status';
	}

	public function get_description(): string {
		return 'Update the status of a WordPress comment: approve it, put it on hold (pending), mark as spam, or move to trash.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'comment_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the comment to update.',
				],
				'status'     => [
					'type'        => 'string',
					'description' => 'New status: "approve" to make visible, "hold" to set as pending, "spam" to mark as spam, "trash" to move to trash.',
					'enum'        => [ 'approve', 'hold', 'spam', 'trash' ],
				],
			],
			'required'   => [ 'comment_id', 'status' ],
		];
	}

	public function get_required_capability(): string {
		return 'moderate_comments';
	}

	public function execute( array $input ): array {
		$comment_id = absint( $input['comment_id'] );
		$status     = sanitize_key( $input['status'] );

		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return [ 'error' => "Comment not found: {$comment_id}" ];
		}

		$result = wp_set_comment_status( $comment_id, $status );

		if ( ! $result ) {
			return [ 'error' => "Failed to update comment status to \"{$status}\"." ];
		}

		return [
			'comment_id' => $comment_id,
			'status'     => $status,
			'message'    => "Comment status updated to \"{$status}\".",
		];
	}
}

/**
 * Delete a WordPress comment. Requires confirmation.
 */
class DeleteComment extends ToolInterface {

	public function get_name(): string {
		return 'delete_comment';
	}

	public function get_description(): string {
		return 'Delete a WordPress comment. By default moves it to trash; set force_delete to true to permanently remove it. This is a destructive action that requires confirmation.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'delete';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'comment_id'   => [
					'type'        => 'integer',
					'description' => 'The ID of the comment to delete.',
				],
				'force_delete' => [
					'type'        => 'boolean',
					'description' => 'If true, permanently deletes the comment instead of moving it to trash. Default: false.',
					'default'     => false,
				],
			],
			'required'   => [ 'comment_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'moderate_comments';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$comment_id   = absint( $input['comment_id'] );
		$force_delete = ! empty( $input['force_delete'] );

		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return [ 'error' => "Comment not found: {$comment_id}" ];
		}

		$author  = $comment->comment_author;
		$result  = wp_delete_comment( $comment_id, $force_delete );

		if ( ! $result ) {
			return [ 'error' => "Failed to delete comment: {$comment_id}" ];
		}

		$action  = $force_delete ? 'permanently deleted' : 'moved to trash';
		$message = "Comment by \"{$author}\" {$action}.";

		return [
			'comment_id' => $comment_id,
			'author'     => $author,
			'message'    => $message,
		];
	}
}

/**
 * Post a reply to an existing WordPress comment.
 */
class ReplyToComment extends ToolInterface {

	public function get_name(): string {
		return 'reply_to_comment';
	}

	public function get_description(): string {
		return 'Post an admin reply to an existing WordPress comment. The reply is automatically approved and attributed to the current user.';
	}

	public function get_category(): string {
		return 'content';
	}

	public function get_action(): string {
		return 'create';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'comment_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the comment to reply to.',
				],
				'content'    => [
					'type'        => 'string',
					'description' => 'The text content of the reply.',
				],
			],
			'required'   => [ 'comment_id', 'content' ],
		];
	}

	public function get_required_capability(): string {
		return 'moderate_comments';
	}

	public function execute( array $input ): array {
		$parent_id = absint( $input['comment_id'] );
		$parent    = get_comment( $parent_id );

		if ( ! $parent ) {
			return [ 'error' => "Parent comment not found: {$parent_id}" ];
		}

		$current_user = wp_get_current_user();
		if ( ! $current_user->ID ) {
			return [ 'error' => 'Must be logged in to post a reply.' ];
		}

		$comment_data = [
			'comment_post_ID'      => (int) $parent->comment_post_ID,
			'comment_content'      => sanitize_textarea_field( $input['content'] ),
			'comment_parent'       => $parent_id,
			'comment_author'       => $current_user->display_name,
			'comment_author_email' => $current_user->user_email,
			'comment_author_url'   => $current_user->user_url,
			'comment_approved'     => 1, // Auto-approve admin replies.
			'user_id'              => $current_user->ID,
		];

		$new_comment_id = wp_insert_comment( $comment_data );

		if ( ! $new_comment_id ) {
			return [ 'error' => 'Failed to post reply.' ];
		}

		return [
			'comment_id'     => $new_comment_id,
			'parent_id'      => $parent_id,
			'post_id'        => (int) $parent->comment_post_ID,
			'author'         => $current_user->display_name,
			'content'        => $input['content'],
			'message'        => 'Reply posted successfully.',
		];
	}
}
