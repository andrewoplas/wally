<?php
namespace Wally;

/**
 * Maps WordPress roles to allowed action types.
 *
 * Enforces the permission matrix from the spec. Action-based access
 * is checked before execution in addition to WordPress capability checks.
 * Admins can override per-role toggles via the settings page (stored in
 * the wally_tool_permissions option).
 *
 * Action types: read, create, update, delete, plugins, site
 */
class Permissions {

	/**
	 * Default action permissions per WordPress role.
	 */
	private const DEFAULT_ROLE_PERMISSIONS = [
		'administrator' => [ 'read', 'create', 'update', 'delete', 'plugins', 'site' ],
		'editor'        => [ 'read', 'create', 'update', 'delete' ],
		'author'        => [ 'read', 'create', 'update' ],
		'contributor'   => [ 'read', 'create' ],
		'subscriber'    => [ 'read' ],
	];

	/**
	 * Check if a user can perform a specific action type.
	 *
	 * @param string $action  Action type (read, create, update, delete, plugins, site).
	 * @param int    $user_id WordPress user ID (0 for current user).
	 * @return bool
	 */
	public static function can_use_action( string $action, int $user_id = 0 ): bool {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		return in_array( $action, self::get_allowed_actions( $user_id ), true );
	}

	/**
	 * Get all action types allowed for a user based on their role.
	 *
	 * Merges default role permissions with admin-configured overrides.
	 *
	 * @param int $user_id WordPress user ID (0 for current user).
	 * @return array List of allowed action strings.
	 */
	public static function get_allowed_actions( int $user_id = 0 ): array {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return [];
		}

		$roles = $user->roles;
		$role  = ! empty( $roles ) ? reset( $roles ) : 'subscriber';

		// Check admin-configured overrides first.
		$overrides = get_option( 'wally_tool_permissions', [] );
		if ( is_array( $overrides ) && isset( $overrides[ $role ] ) && is_array( $overrides[ $role ] ) ) {
			return $overrides[ $role ];
		}

		// Fall back to defaults.
		return self::DEFAULT_ROLE_PERMISSIONS[ $role ] ?? [];
	}

	/**
	 * Get the full default permissions map (for settings UI rendering).
	 *
	 * @return array Role => actions mapping.
	 */
	public static function get_default_permissions(): array {
		return self::DEFAULT_ROLE_PERMISSIONS;
	}

	/**
	 * Get the list of all action types.
	 *
	 * @return array
	 */
	public static function get_all_actions(): array {
		return [ 'read', 'create', 'update', 'delete', 'plugins', 'site' ];
	}
}
