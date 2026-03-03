<?php
namespace Wally\Tools;

/**
 * User management tools for WordPress.
 *
 * Tools: list_users, get_user, create_user, update_user, delete_user, update_user_role.
 * All tools use WordPress core user functions with proper capability checks.
 */

/**
 * List WordPress users with optional filters.
 */
class ListUsers extends ToolInterface {

	public function get_name(): string {
		return 'list_users';
	}

	public function get_description(): string {
		return 'List WordPress users with optional filters by role, search query, or pagination. Returns user ID, login, email, display name, roles, and registration date.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'role'     => [
					'type'        => 'string',
					'description' => 'Filter by role slug (e.g., administrator, editor, author, contributor, subscriber).',
				],
				'search'   => [
					'type'        => 'string',
					'description' => 'Search keyword to filter users by login, email, display name, or URL.',
				],
				'per_page' => [
					'type'        => 'integer',
					'description' => 'Number of users to return (max 100).',
					'default'     => 20,
				],
				'page'     => [
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				],
				'orderby'  => [
					'type'        => 'string',
					'description' => 'Field to order results by.',
					'enum'        => [ 'ID', 'display_name', 'login', 'email', 'registered' ],
					'default'     => 'registered',
				],
				'order'    => [
					'type'        => 'string',
					'description' => 'Sort direction.',
					'enum'        => [ 'ASC', 'DESC' ],
					'default'     => 'DESC',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'list_users';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		$args = [
			'number'      => $per_page,
			'offset'      => ( $page - 1 ) * $per_page,
			'orderby'     => sanitize_key( $input['orderby'] ?? 'registered' ),
			'order'       => strtoupper( $input['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC',
			'count_total' => true,
		];

		if ( ! empty( $input['role'] ) ) {
			$args['role'] = sanitize_key( $input['role'] );
		}

		if ( ! empty( $input['search'] ) ) {
			$args['search']         = '*' . sanitize_text_field( $input['search'] ) . '*';
			$args['search_columns'] = [ 'user_login', 'user_email', 'display_name', 'user_nicename' ];
		}

		$user_query = new \WP_User_Query( $args );
		$users      = [];

		foreach ( $user_query->get_results() as $user ) {
			$users[] = [
				'id'           => $user->ID,
				'login'        => $user->user_login,
				'email'        => $user->user_email,
				'display_name' => $user->display_name,
				'roles'        => $user->roles,
				'registered'   => $user->user_registered,
			];
		}

		return [
			'users'       => $users,
			'total'       => (int) $user_query->get_total(),
			'total_pages' => (int) ceil( $user_query->get_total() / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Get full details of a single WordPress user.
 */
class GetUser extends ToolInterface {

	public function get_name(): string {
		return 'get_user';
	}

	public function get_description(): string {
		return 'Get full details of a single WordPress user by ID, login username, or email address. Returns profile fields, roles, and capabilities.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'user_id' => [
					'type'        => 'integer',
					'description' => 'The user ID to look up.',
				],
				'login'   => [
					'type'        => 'string',
					'description' => 'The user login (username) to look up. Used if user_id is not provided.',
				],
				'email'   => [
					'type'        => 'string',
					'description' => 'The user email address to look up. Used if user_id and login are not provided.',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'list_users';
	}

	public function execute( array $input ): array {
		$user = null;

		if ( ! empty( $input['user_id'] ) ) {
			$user = get_user_by( 'id', absint( $input['user_id'] ) );
		} elseif ( ! empty( $input['login'] ) ) {
			$user = get_user_by( 'login', sanitize_user( $input['login'] ) );
		} elseif ( ! empty( $input['email'] ) ) {
			$user = get_user_by( 'email', sanitize_email( $input['email'] ) );
		} else {
			return [ 'error' => 'Provide user_id, login, or email to look up a user.' ];
		}

		if ( ! $user ) {
			return [ 'error' => 'User not found.' ];
		}

		return [
			'id'           => $user->ID,
			'login'        => $user->user_login,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'first_name'   => $user->first_name,
			'last_name'    => $user->last_name,
			'nickname'     => $user->nickname,
			'description'  => $user->description,
			'url'          => $user->user_url,
			'roles'        => $user->roles,
			'registered'   => $user->user_registered,
		];
	}
}

/**
 * Create a new WordPress user.
 */
class CreateUser extends ToolInterface {

	public function get_name(): string {
		return 'create_user';
	}

	public function get_description(): string {
		return 'Create a new WordPress user with a username, email, password, and optional role and profile fields (first name, last name, display name).';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'create';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'username'     => [
					'type'        => 'string',
					'description' => 'The login username for the new user (must be unique).',
				],
				'email'        => [
					'type'        => 'string',
					'description' => 'The email address for the new user (must be unique).',
				],
				'password'     => [
					'type'        => 'string',
					'description' => 'The plain-text password for the new user.',
				],
				'role'         => [
					'type'        => 'string',
					'description' => 'User role slug (e.g., subscriber, contributor, author, editor, administrator). Defaults to the site default role.',
					'default'     => 'subscriber',
				],
				'first_name'   => [
					'type'        => 'string',
					'description' => 'First name of the new user.',
				],
				'last_name'    => [
					'type'        => 'string',
					'description' => 'Last name of the new user.',
				],
				'display_name' => [
					'type'        => 'string',
					'description' => 'Public display name. Defaults to username if not provided.',
				],
			],
			'required'   => [ 'username', 'email', 'password' ],
		];
	}

	public function get_required_capability(): string {
		return 'create_users';
	}

	public function execute( array $input ): array {
		$userdata = [
			'user_login' => sanitize_user( $input['username'] ),
			'user_email' => sanitize_email( $input['email'] ),
			'user_pass'  => $input['password'],
			'role'       => sanitize_key( $input['role'] ?? 'subscriber' ),
		];

		if ( ! empty( $input['first_name'] ) ) {
			$userdata['first_name'] = sanitize_text_field( $input['first_name'] );
		}
		if ( ! empty( $input['last_name'] ) ) {
			$userdata['last_name'] = sanitize_text_field( $input['last_name'] );
		}
		if ( ! empty( $input['display_name'] ) ) {
			$userdata['display_name'] = sanitize_text_field( $input['display_name'] );
		}

		$user_id = wp_insert_user( $userdata );

		if ( is_wp_error( $user_id ) ) {
			return [ 'error' => $user_id->get_error_message() ];
		}

		$user = get_userdata( $user_id );

		return [
			'user_id'      => $user_id,
			'login'        => $user->user_login,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'roles'        => $user->roles,
			'message'      => "User \"{$user->user_login}\" created successfully.",
		];
	}
}

/**
 * Update an existing WordPress user's profile fields.
 */
class UpdateUser extends ToolInterface {

	public function get_name(): string {
		return 'update_user';
	}

	public function get_description(): string {
		return 'Update an existing WordPress user\'s profile fields such as email, display name, first name, last name, or password. Provide the user_id and any fields to change.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'user_id'      => [
					'type'        => 'integer',
					'description' => 'The ID of the user to update.',
				],
				'email'        => [
					'type'        => 'string',
					'description' => 'New email address (must be unique).',
				],
				'password'     => [
					'type'        => 'string',
					'description' => 'New plain-text password.',
				],
				'first_name'   => [
					'type'        => 'string',
					'description' => 'New first name.',
				],
				'last_name'    => [
					'type'        => 'string',
					'description' => 'New last name.',
				],
				'display_name' => [
					'type'        => 'string',
					'description' => 'New public display name.',
				],
				'description'  => [
					'type'        => 'string',
					'description' => 'New biographical description/bio.',
				],
				'url'          => [
					'type'        => 'string',
					'description' => 'New website URL for the user.',
				],
			],
			'required'   => [ 'user_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_users';
	}

	public function execute( array $input ): array {
		$user_id = absint( $input['user_id'] );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return [ 'error' => "User not found: {$user_id}" ];
		}

		$userdata = [ 'ID' => $user_id ];
		$changes  = [];

		if ( isset( $input['email'] ) ) {
			$userdata['user_email'] = sanitize_email( $input['email'] );
			$changes[]              = 'email';
		}
		if ( isset( $input['password'] ) ) {
			$userdata['user_pass'] = $input['password'];
			$changes[]             = 'password';
		}
		if ( isset( $input['first_name'] ) ) {
			$userdata['first_name'] = sanitize_text_field( $input['first_name'] );
			$changes[]              = 'first_name';
		}
		if ( isset( $input['last_name'] ) ) {
			$userdata['last_name'] = sanitize_text_field( $input['last_name'] );
			$changes[]             = 'last_name';
		}
		if ( isset( $input['display_name'] ) ) {
			$userdata['display_name'] = sanitize_text_field( $input['display_name'] );
			$changes[]                = 'display_name';
		}
		if ( isset( $input['description'] ) ) {
			$userdata['description'] = sanitize_textarea_field( $input['description'] );
			$changes[]               = 'description';
		}
		if ( isset( $input['url'] ) ) {
			$userdata['user_url'] = esc_url_raw( $input['url'] );
			$changes[]            = 'url';
		}

		if ( empty( $changes ) ) {
			return [ 'error' => 'No fields provided to update.' ];
		}

		$result = wp_update_user( $userdata );

		if ( is_wp_error( $result ) ) {
			return [ 'error' => $result->get_error_message() ];
		}

		$updated_user = get_userdata( $user_id );

		return [
			'user_id'      => $user_id,
			'login'        => $updated_user->user_login,
			'email'        => $updated_user->user_email,
			'display_name' => $updated_user->display_name,
			'updated'      => $changes,
			'message'      => "User \"{$updated_user->user_login}\" updated successfully.",
		];
	}
}

/**
 * Delete a WordPress user. Requires confirmation.
 */
class DeleteUser extends ToolInterface {

	public function get_name(): string {
		return 'delete_user';
	}

	public function get_description(): string {
		return 'Permanently delete a WordPress user. Optionally reassign their posts to another user. This is a destructive action that requires confirmation.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'delete';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'user_id'    => [
					'type'        => 'integer',
					'description' => 'The ID of the user to delete.',
				],
				'reassign_to' => [
					'type'        => 'integer',
					'description' => 'User ID to reassign the deleted user\'s posts to. If not provided, the posts will also be deleted.',
				],
			],
			'required'   => [ 'user_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'delete_users';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$user_id = absint( $input['user_id'] );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return [ 'error' => "User not found: {$user_id}" ];
		}

		$login    = $user->user_login;
		$reassign = ! empty( $input['reassign_to'] ) ? absint( $input['reassign_to'] ) : null;

		// wp_delete_user() requires this file when called outside wp-admin.
		require_once ABSPATH . 'wp-admin/includes/user.php';

		$result = wp_delete_user( $user_id, $reassign );

		if ( ! $result ) {
			return [ 'error' => "Failed to delete user: {$user_id}" ];
		}

		$message = "User \"{$login}\" deleted successfully.";
		if ( $reassign ) {
			$message .= " Posts reassigned to user ID {$reassign}.";
		}

		return [
			'user_id' => $user_id,
			'login'   => $login,
			'message' => $message,
		];
	}
}

/**
 * Update a WordPress user's role.
 */
class UpdateUserRole extends ToolInterface {

	public function get_name(): string {
		return 'update_user_role';
	}

	public function get_description(): string {
		return 'Change a WordPress user\'s role (e.g., promote a subscriber to editor, or demote an administrator). Replaces all existing roles with the new role.';
	}

	public function get_category(): string {
		return 'site';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'user_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the user to update.',
				],
				'role'    => [
					'type'        => 'string',
					'description' => 'New role slug to assign (e.g., subscriber, contributor, author, editor, administrator, or any custom role).',
				],
			],
			'required'   => [ 'user_id', 'role' ],
		];
	}

	public function get_required_capability(): string {
		return 'edit_users';
	}

	public function execute( array $input ): array {
		$user_id = absint( $input['user_id'] );
		$role    = sanitize_key( $input['role'] );

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return [ 'error' => "User not found: {$user_id}" ];
		}

		// Verify the role exists.
		if ( ! get_role( $role ) ) {
			return [ 'error' => "Role does not exist: {$role}" ];
		}

		$old_roles = $user->roles;
		$user->set_role( $role );

		return [
			'user_id'   => $user_id,
			'login'     => $user->user_login,
			'old_roles' => $old_roles,
			'new_role'  => $role,
			'message'   => "User \"{$user->user_login}\" role updated to \"{$role}\".",
		];
	}
}
