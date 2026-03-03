<?php
namespace Wally;

/**
 * Dedicated file-based logger for Wally.
 *
 * Writes to wp-content/wally-logs/wally-YYYY-MM-DD.log with daily rotation.
 * Each entry is a single line: [timestamp] [LEVEL] [context] message {json_data}
 *
 * Usage:
 *   WallyLogger::info( 'Tool executed', [ 'tool' => 'create_post', 'input' => [...] ] );
 *   WallyLogger::error( 'Tool failed', [ 'tool' => 'create_post', 'error' => '...' ] );
 */
class WallyLogger {

	/** @var string Resolved log directory path. */
	private static string $log_dir = '';

	/** @var bool Whether the log directory has been verified this request. */
	private static bool $dir_checked = false;

	/**
	 * Log an info-level message.
	 */
	public static function info( string $message, array $context = [] ): void {
		self::log( 'INFO', $message, $context );
	}

	/**
	 * Log a warning-level message.
	 */
	public static function warning( string $message, array $context = [] ): void {
		self::log( 'WARNING', $message, $context );
	}

	/**
	 * Log an error-level message.
	 */
	public static function error( string $message, array $context = [] ): void {
		self::log( 'ERROR', $message, $context );
	}

	/**
	 * Log a debug-level message (only when WP_DEBUG is enabled).
	 */
	public static function debug( string $message, array $context = [] ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			self::log( 'DEBUG', $message, $context );
		}
	}

	/**
	 * Write a log entry to the daily log file.
	 *
	 * @param string $level   Log level (INFO, WARNING, ERROR, DEBUG).
	 * @param string $message Human-readable message.
	 * @param array  $context Additional structured data (encoded as JSON).
	 */
	private static function log( string $level, string $message, array $context = [] ): void {
		$dir = self::get_log_dir();
		if ( ! $dir ) {
			return;
		}

		$date     = gmdate( 'Y-m-d' );
		$file     = $dir . '/wally-' . $date . '.log';
		$time     = gmdate( 'Y-m-d H:i:s' );
		$ctx_json = ! empty( $context ) ? ' ' . wp_json_encode( $context, JSON_UNESCAPED_SLASHES ) : '';

		$entry = sprintf( "[%s] [%s] %s%s\n", $time, $level, $message, $ctx_json );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $file, $entry, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Get (and lazily create) the log directory.
	 *
	 * Logs are stored in wp-content/wally-logs/ with an .htaccess and
	 * index.html to prevent direct web access.
	 *
	 * @return string|false Directory path or false on failure.
	 */
	private static function get_log_dir() {
		if ( self::$dir_checked ) {
			return self::$log_dir ?: false;
		}

		self::$dir_checked = true;

		$dir = WP_CONTENT_DIR . '/wally-logs';

		if ( ! is_dir( $dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			if ( ! mkdir( $dir, 0755, true ) ) {
				// Fall back to PHP error_log if we can't create the directory.
				error_log( '[Wally] Could not create log directory: ' . $dir );
				return false;
			}

			// Prevent web access to log files.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $dir . '/.htaccess', "Deny from all\n" );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $dir . '/index.html', '' );
		}

		self::$log_dir = $dir;
		return $dir;
	}

	/**
	 * Get the path to today's log file (for admin display).
	 *
	 * @return string|false File path or false if not available.
	 */
	public static function get_current_log_path() {
		$dir = self::get_log_dir();
		if ( ! $dir ) {
			return false;
		}

		$file = $dir . '/wally-' . gmdate( 'Y-m-d' ) . '.log';
		return file_exists( $file ) ? $file : false;
	}

	/**
	 * Get the log directory path (for admin display).
	 *
	 * @return string
	 */
	public static function get_log_dir_path(): string {
		return WP_CONTENT_DIR . '/wally-logs';
	}
}
