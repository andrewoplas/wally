<?php
namespace Wally\Tools;

/**
 * Analytics plugin management tools.
 *
 * Tools: get_site_kit_stats, get_monsterinsights_stats.
 * Each tool has per-tool conditional registration.
 * Note: Live analytics data is stored in Google's servers. These tools return
 * configuration settings and any locally cached report data.
 */

/**
 * Get Google Site Kit configuration and connected module settings.
 */
class GetSiteKitStats extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'Google\\Site_Kit\\Plugin' );
	}

	public function get_name(): string {
		return 'get_site_kit_stats';
	}

	public function get_description(): string {
		return 'Get Google Site Kit plugin configuration including active modules, GA4 measurement ID, Search Console property, and connection status.';
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
		$active_modules = get_option( 'googlesitekit_active_modules', [] );
		if ( ! is_array( $active_modules ) ) {
			$active_modules = [];
		}

		$ga4_settings = get_option( 'googlesitekit_analytics-4_settings', [] );
		$sc_settings  = get_option( 'googlesitekit_search-console_settings', [] );
		$ads_settings = get_option( 'googlesitekit_adsense_settings', [] );
		$gtm_settings = get_option( 'googlesitekit_tagmanager_settings', [] );

		// Determine connection status.
		$proxy_url = get_option( 'googlesitekit_connected_proxy_url', '' );
		$connected = ! empty( $proxy_url );

		$modules = [];

		if ( in_array( 'analytics-4', $active_modules, true ) ) {
			$modules['analytics_4'] = [
				'active'          => true,
				'property_id'     => $ga4_settings['propertyID'] ?? '',
				'measurement_id'  => $ga4_settings['measurementID'] ?? '',
				'stream_id'       => $ga4_settings['webDataStreamID'] ?? '',
			];
		}

		if ( in_array( 'search-console', $active_modules, true ) ) {
			$modules['search_console'] = [
				'active'   => true,
				'property' => $sc_settings['propertyID'] ?? '',
			];
		}

		if ( in_array( 'adsense', $active_modules, true ) ) {
			$modules['adsense'] = [
				'active'     => true,
				'account_id' => $ads_settings['accountID'] ?? '',
				'client_id'  => $ads_settings['clientID'] ?? '',
			];
		}

		if ( in_array( 'tagmanager', $active_modules, true ) ) {
			$modules['tag_manager'] = [
				'active'       => true,
				'container_id' => $gtm_settings['containerID'] ?? '',
			];
		}

		return [
			'connected'      => $connected,
			'active_modules' => $active_modules,
			'modules'        => $modules,
			'note'           => 'Live analytics data is stored in Google\'s servers. Configure and view reports at your Google Site Kit dashboard in wp-admin.',
		];
	}
}

/**
 * Get MonsterInsights configuration and tracking settings.
 */
class GetMonsterInsightsStats extends ToolInterface {

	public static function can_register(): bool {
		return function_exists( 'MonsterInsights' );
	}

	public function get_name(): string {
		return 'get_monsterinsights_stats';
	}

	public function get_description(): string {
		return 'Get MonsterInsights plugin configuration including tracking ID, GA4 measurement ID, tracking mode, and whether e-commerce tracking is enabled.';
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
		$profile       = get_option( 'monsterinsights_site_profile', [] );
		$tracking_mode = get_option( 'monsterinsights_tracking_mode', '' );

		// Get tracking ID from the profile option.
		$ua_id          = '';
		$ga4_id         = '';
		$view_name      = '';
		if ( is_array( $profile ) ) {
			$ua_id     = $profile['ua'] ?? '';
			$ga4_id    = $profile['v4'] ?? '';
			$view_name = $profile['viewname'] ?? '';
		} elseif ( function_exists( 'monsterinsights_get_ua' ) ) {
			$ua_id = monsterinsights_get_ua();
		}

		// Check for cached popular posts.
		$popular_posts_cached = (bool) get_transient( 'monsterinsights_popular_posts_cache_inline' );

		return [
			'ua_tracking_id'      => $ua_id,
			'ga4_measurement_id'  => $ga4_id,
			'view_name'           => $view_name,
			'tracking_mode'       => $tracking_mode,
			'popular_posts_cache' => $popular_posts_cached,
			'note'                => 'Live analytics reports are fetched from Google Analytics API. View reports at MonsterInsights > Reports in wp-admin.',
		];
	}
}
