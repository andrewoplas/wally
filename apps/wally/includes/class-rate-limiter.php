<?php
namespace Wally;

/**
 * Per-user rate limiting for chat messages.
 *
 * Counts user messages within a rolling daily window using the
 * wally_messages table and enforces the admin-configured limit.
 */
class RateLimiter {

	/** Default messages per user per day if no setting configured. */
	private const DEFAULT_LIMIT = 50;

	/**
	 * Check if a user has exceeded their daily message limit.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool True if the user is rate-limited and should be blocked.
	 */
	public static function is_rate_limited( int $user_id ): bool {
		$limit = self::get_daily_limit();
		$count = self::get_message_count_today( $user_id );

		return $count >= $limit;
	}

	/**
	 * Get rate limit status for a user (for informational responses).
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array { used: int, limit: int, remaining: int, resets_at: string }
	 */
	public static function get_status( int $user_id ): array {
		$limit = self::get_daily_limit();
		$used  = self::get_message_count_today( $user_id );

		return [
			'used'      => $used,
			'limit'     => $limit,
			'remaining' => max( 0, $limit - $used ),
			'resets_at' => self::get_reset_time(),
		];
	}

	/**
	 * Count user messages sent today (role = 'user' only).
	 *
	 * Joins wally_messages with wally_conversations to filter by user_id,
	 * counting only messages created since the start of the current day
	 * in the WordPress-configured timezone.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int Number of messages sent today.
	 */
	public static function get_message_count_today( int $user_id ): int {
		global $wpdb;

		$msg_table  = $wpdb->prefix . 'wally_messages';
		$conv_table = $wpdb->prefix . 'wally_conversations';
		$day_start  = self::get_day_start();

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$msg_table} m
				 INNER JOIN {$conv_table} c ON m.conversation_id = c.id
				 WHERE c.user_id = %d
				   AND m.role = 'user'
				   AND m.created_at >= %s",
				$user_id,
				$day_start
			)
		);

		return (int) $count;
	}

	/**
	 * Check if the site-wide monthly token budget has been exceeded.
	 *
	 * @return bool True if budget is set and has been exceeded.
	 */
	public static function is_token_budget_exceeded(): bool {
		$budget = self::get_monthly_token_budget();

		// 0 means unlimited.
		if ( 0 === $budget ) {
			return false;
		}

		return self::get_monthly_token_usage() >= $budget;
	}

	/**
	 * Get token budget status for the current month (site-wide).
	 *
	 * @return array { used: int, budget: int, remaining: int }
	 */
	public static function get_token_budget_status(): array {
		$budget = self::get_monthly_token_budget();
		$used   = self::get_monthly_token_usage();

		return [
			'used'      => $used,
			'budget'    => $budget,
			'remaining' => $budget > 0 ? max( 0, $budget - $used ) : -1, // -1 = unlimited
		];
	}

	/**
	 * Sum all token_count values from wally_messages for the current month.
	 *
	 * This is site-wide (all users), since token budget is a global cap.
	 *
	 * @return int Total tokens used this month.
	 */
	public static function get_monthly_token_usage(): int {
		global $wpdb;

		$msg_table   = $wpdb->prefix . 'wally_messages';
		$month_start = self::get_month_start();

		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(token_count), 0) FROM {$msg_table}
				 WHERE created_at >= %s",
				$month_start
			)
		);

		return (int) $total;
	}

	/**
	 * Get the configured monthly token budget.
	 *
	 * @return int Token budget (0 = unlimited).
	 */
	public static function get_monthly_token_budget(): int {
		return absint( get_option( 'wally_monthly_token_budget', 0 ) );
	}

	/**
	 * Get the configured daily message limit.
	 *
	 * @return int Messages per user per day.
	 */
	public static function get_daily_limit(): int {
		return absint( get_option( 'wally_rate_limit', self::DEFAULT_LIMIT ) );
	}

	/**
	 * Get the start of today in MySQL datetime format (WP timezone).
	 *
	 * @return string MySQL datetime string for midnight today.
	 */
	private static function get_day_start(): string {
		// current_time('timestamp') returns a Unix timestamp offset to WP timezone.
		$wp_timestamp = current_time( 'timestamp' );
		$midnight     = strtotime( 'today midnight', $wp_timestamp );

		return gmdate( 'Y-m-d H:i:s', $midnight );
	}

	/**
	 * Get the start of the current month in MySQL datetime format (WP timezone).
	 *
	 * @return string MySQL datetime string for 1st of the month at midnight.
	 */
	private static function get_month_start(): string {
		$wp_timestamp = current_time( 'timestamp' );
		$first_of_month = strtotime( 'first day of this month midnight', $wp_timestamp );

		return gmdate( 'Y-m-d H:i:s', $first_of_month );
	}

	/**
	 * Get the time when the rate limit resets (start of next day, WP timezone).
	 *
	 * @return string ISO 8601 datetime string.
	 */
	private static function get_reset_time(): string {
		$wp_timestamp     = current_time( 'timestamp' );
		$tomorrow         = strtotime( 'tomorrow midnight', $wp_timestamp );
		$timezone_string  = wp_timezone_string();

		try {
			$tz   = new \DateTimeZone( $timezone_string );
			$dt   = new \DateTime( '@' . $tomorrow );
			$dt->setTimezone( $tz );
			return $dt->format( 'c' ); // ISO 8601
		} catch ( \Exception $e ) {
			return gmdate( 'c', $tomorrow );
		}
	}
}
