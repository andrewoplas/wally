<?php
namespace Wally\Tools;

/**
 * UpdraftPlus backup plugin management tools.
 *
 * Tools: list_updraftplus_backups, trigger_updraftplus_backup, get_updraftplus_settings.
 * All tools require UpdraftPlus to be active (class_exists('UpdraftPlus')).
 */

/**
 * List UpdraftPlus backup history.
 */
class ListUpdraftPlusBackups extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'UpdraftPlus' );
	}

	public function get_name(): string {
		return 'list_updraftplus_backups';
	}

	public function get_description(): string {
		return 'List backup history from UpdraftPlus. Returns backup date, type (files/database), file names, and size information.';
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
				'limit' => [
					'type'        => 'integer',
					'description' => 'Maximum number of backups to return (newest first). Default: 10, max: 50.',
					'default'     => 10,
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$limit = min( (int) ( $input['limit'] ?? 10 ), 50 );

		if ( ! class_exists( 'UpdraftPlus_Backup_History' ) ) {
			// Try to load the class.
			$history_file = WP_PLUGIN_DIR . '/updraftplus/includes/class-backup-history.php';
			if ( file_exists( $history_file ) ) {
				require_once $history_file;
			}
		}

		if ( ! class_exists( 'UpdraftPlus_Backup_History' ) ) {
			// Fallback: read directly from options.
			$history_raw = get_option( 'updraft_backup_history', [] );
		} else {
			$history_raw = \UpdraftPlus_Backup_History::get_history();
		}

		if ( ! is_array( $history_raw ) ) {
			$history_raw = [];
		}

		// Sort by timestamp descending.
		krsort( $history_raw );

		$backups = [];
		$count   = 0;
		foreach ( $history_raw as $timestamp => $backup ) {
			if ( $count >= $limit ) {
				break;
			}

			$backups[] = [
				'timestamp'  => $timestamp,
				'date'       => gmdate( 'Y-m-d H:i:s', $timestamp ),
				'type'       => ( ! empty( $backup['db'] ) && ! empty( $backup['plugins'] ) ) ? 'full' : ( ! empty( $backup['db'] ) ? 'database' : 'files' ),
				'has_db'     => ! empty( $backup['db'] ),
				'has_files'  => ! empty( $backup['plugins'] ) || ! empty( $backup['themes'] ) || ! empty( $backup['uploads'] ) || ! empty( $backup['others'] ),
				'nonce'      => $backup['nonce'] ?? '',
				'job_type'   => $backup['job_type'] ?? 'single',
			];

			$count++;
		}

		return [
			'backups' => $backups,
			'total'   => count( $history_raw ),
			'shown'   => count( $backups ),
		];
	}
}

/**
 * Trigger a manual UpdraftPlus backup.
 */
class TriggerUpdraftPlusBackup extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'UpdraftPlus' );
	}

	public function get_name(): string {
		return 'trigger_updraftplus_backup';
	}

	public function get_description(): string {
		return 'Trigger a manual backup using UpdraftPlus. Schedules an immediate backup job. Use type "all" for a full backup (files + database), "database" for database only, or "files" for files only.';
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
				'type' => [
					'type'        => 'string',
					'description' => 'What to back up: "all" (files + database), "database" (database only), "files" (files only). Default: "all".',
					'enum'        => [ 'all', 'database', 'files' ],
					'default'     => 'all',
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function requires_confirmation(): bool {
		return true;
	}

	public function execute( array $input ): array {
		$type = sanitize_key( $input['type'] ?? 'all' );

		// Schedule a one-time backup via WP-Cron (fires immediately on next request).
		switch ( $type ) {
			case 'database':
				wp_schedule_single_event( time(), 'updraft_backup_database' );
				$description = 'database backup';
				break;
			case 'files':
				wp_schedule_single_event( time(), 'updraft_backup' );
				$description = 'files backup';
				break;
			default:
				wp_schedule_single_event( time(), 'updraft_backupnow_backup_all' );
				$description = 'full backup (files + database)';
				break;
		}

		// Spawn cron to process immediately.
		spawn_cron();

		return [
			'type'    => $type,
			'message' => "UpdraftPlus {$description} has been scheduled and will begin shortly. Check backup history in a few minutes via list_updraftplus_backups.",
		];
	}
}

/**
 * Get UpdraftPlus settings and configuration.
 */
class GetUpdraftPlusSettings extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'UpdraftPlus' );
	}

	public function get_name(): string {
		return 'get_updraftplus_settings';
	}

	public function get_description(): string {
		return 'Get UpdraftPlus backup plugin settings including backup schedules, retention policy, remote storage destinations, and what is included in backups.';
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
			'properties' => [],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'manage_options';
	}

	public function execute( array $input ): array {
		$get_option = function ( $key, $default = '' ) {
			if ( class_exists( 'UpdraftPlus_Options' ) && method_exists( 'UpdraftPlus_Options', 'get_updraft_option' ) ) {
				return \UpdraftPlus_Options::get_updraft_option( $key, $default );
			}
			return get_option( $key, $default );
		};

		return [
			'schedules'   => [
				'files'    => $get_option( 'updraft_interval', 'manual' ),
				'database' => $get_option( 'updraft_interval_database', 'manual' ),
			],
			'retention'   => [
				'files'    => (int) $get_option( 'updraft_retain', 1 ),
				'database' => (int) $get_option( 'updraft_retain_db', 1 ),
			],
			'remote_storage' => $get_option( 'updraft_service', '' ),
			'include'     => [
				'plugins' => (bool) $get_option( 'updraft_include_plugins', 1 ),
				'themes'  => (bool) $get_option( 'updraft_include_themes', 1 ),
				'uploads' => (bool) $get_option( 'updraft_include_uploads', 1 ),
				'others'  => (bool) $get_option( 'updraft_include_others', 1 ),
			],
			'backup_dir'     => $get_option( 'updraft_dir', WP_CONTENT_DIR . '/updraft' ),
			'email'          => $get_option( 'updraft_email', '' ),
		];
	}
}
