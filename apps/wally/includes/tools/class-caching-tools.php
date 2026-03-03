<?php
namespace Wally\Tools;

/**
 * WordPress caching plugin management tools.
 *
 * Tools: clear_cache, get_cache_settings.
 * These tools are universal and detect the active caching plugin automatically.
 * Supports: WP Rocket, W3 Total Cache, WP Super Cache, LiteSpeed Cache, WP Fastest Cache.
 * Falls back to wp_cache_flush() if no specific plugin is detected.
 */

/**
 * Detect which caching plugin is active and return its identifier.
 */
function wally_detect_cache_plugin(): string {
	if ( function_exists( 'rocket_clean_domain' ) ) {
		return 'wp-rocket';
	}
	if ( function_exists( 'w3tc_flush_all' ) ) {
		return 'w3-total-cache';
	}
	if ( function_exists( 'wp_cache_clear_cache' ) && defined( 'WPCACHEHOME' ) ) {
		return 'wp-super-cache';
	}
	if ( defined( 'LSCWP_V' ) || class_exists( 'LiteSpeed\\Core' ) ) {
		return 'litespeed-cache';
	}
	if ( class_exists( 'WpFastestCache' ) ) {
		return 'wp-fastest-cache';
	}
	if ( class_exists( 'autoptimize_cache' ) || class_exists( 'Autoptimize_Cache' ) ) {
		return 'autoptimize';
	}
	return 'core';
}

/**
 * Clear the site cache (universal — works with all major caching plugins).
 */
class ClearCache extends ToolInterface {

	public function get_name(): string {
		return 'clear_cache';
	}

	public function get_description(): string {
		return 'Clear the WordPress site cache. Automatically detects and purges the active caching plugin (WP Rocket, W3 Total Cache, WP Super Cache, LiteSpeed Cache, WP Fastest Cache). Falls back to WordPress object cache flush if no plugin is detected.';
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
				'scope' => [
					'type'        => 'string',
					'description' => 'What to clear: "all" (entire site cache) or "object" (WordPress object cache only). Default: "all".',
					'enum'        => [ 'all', 'object' ],
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
		$scope  = sanitize_key( $input['scope'] ?? 'all' );
		$plugin = wally_detect_cache_plugin();

		if ( $scope === 'object' ) {
			wp_cache_flush();
			return [
				'cleared' => 'object_cache',
				'message' => 'WordPress object cache cleared successfully.',
			];
		}

		// Always flush object cache.
		wp_cache_flush();

		$cleared = [ 'object_cache' ];

		switch ( $plugin ) {
			case 'wp-rocket':
				rocket_clean_domain();
				if ( function_exists( 'rocket_clean_minify' ) ) {
					rocket_clean_minify();
				}
				$cleared[] = 'wp_rocket_page_cache';
				$cleared[] = 'wp_rocket_minify';
				break;

			case 'w3-total-cache':
				w3tc_flush_all();
				$cleared[] = 'w3tc_all';
				break;

			case 'wp-super-cache':
				wp_cache_clear_cache();
				$cleared[] = 'wp_super_cache';
				break;

			case 'litespeed-cache':
				do_action( 'litespeed_purge_all' );
				$cleared[] = 'litespeed_cache';
				break;

			case 'wp-fastest-cache':
				if ( function_exists( 'wpfc_clear_all_cache' ) ) {
					wpfc_clear_all_cache( true );
				} else {
					do_action( 'wpfc_clear_all_cache' );
				}
				$cleared[] = 'wp_fastest_cache';
				break;

			case 'autoptimize':
				if ( class_exists( 'Autoptimize_Cache' ) && method_exists( 'Autoptimize_Cache', 'clear_all' ) ) {
					\Autoptimize_Cache::clear_all();
					$cleared[] = 'autoptimize';
				}
				break;
		}

		return [
			'plugin'  => $plugin,
			'cleared' => $cleared,
			'message' => 'Cache cleared successfully' . ( $plugin !== 'core' ? " (using {$plugin})" : ' (WordPress object cache)' ) . '.',
		];
	}
}

/**
 * Get current cache plugin settings.
 */
class GetCacheSettings extends ToolInterface {

	public function get_name(): string {
		return 'get_cache_settings';
	}

	public function get_description(): string {
		return 'Get the current cache plugin settings and status. Returns which caching plugin is active, key configuration options like minification and lazy loading, and cache directory information.';
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
		$plugin   = wally_detect_cache_plugin();
		$settings = [];

		switch ( $plugin ) {
			case 'wp-rocket':
				$rocket   = get_option( 'wp_rocket_settings', [] );
				$settings = [
					'mobile_cache'   => ! empty( $rocket['cache_mobile'] ),
					'minify_css'     => ! empty( $rocket['minify_css'] ),
					'minify_js'      => ! empty( $rocket['minify_js'] ),
					'lazy_load'      => ! empty( $rocket['lazyload'] ),
					'preload'        => ! empty( $rocket['preload'] ),
					'cdn_enabled'    => ! empty( $rocket['cdn'] ),
					'cache_dir'      => WP_CONTENT_DIR . '/cache/wp-rocket/',
				];
				break;

			case 'w3-total-cache':
				$settings = [
					'config_location' => WP_CONTENT_DIR . '/w3tc-config/',
					'cache_dir'       => WP_CONTENT_DIR . '/cache/',
					'object_cache'    => wp_using_ext_object_cache(),
				];
				break;

			case 'wp-super-cache':
				$settings = [
					'cache_dir' => WP_CONTENT_DIR . '/cache/supercache/',
				];
				if ( defined( 'WPCACHEHOME' ) ) {
					$config_file = WPCACHEHOME . 'wp-cache-config.php';
					$settings['config_file'] = $config_file;
					$settings['config_exists'] = file_exists( $config_file );
				}
				break;

			case 'litespeed-cache':
				$settings = [
					'cache_enabled' => (bool) get_option( 'litespeed.conf.cache', false ),
					'optm_css_min'  => (bool) get_option( 'litespeed.conf.optm-css_min', false ),
					'optm_js_min'   => (bool) get_option( 'litespeed.conf.optm-js_min', false ),
					'cdn_enabled'   => (bool) get_option( 'litespeed.conf.cdn', false ),
				];
				break;

			case 'wp-fastest-cache':
				$wpfc     = maybe_unserialize( get_option( 'WpFastestCache', '' ) );
				$settings = [
					'status'        => ! empty( $wpfc['wpFastestCacheStatus'] ),
					'mobile_cache'  => ! empty( $wpfc['wpFastestCacheMobile'] ),
					'minify_html'   => ! empty( $wpfc['wpFastestCacheMinifyHtml'] ),
					'minify_css'    => ! empty( $wpfc['wpFastestCacheMinifyCss'] ),
					'minify_js'     => ! empty( $wpfc['wpFastestCacheMinifyJs'] ),
					'lazy_load'     => ! empty( $wpfc['wpFastestCacheLazyLoad'] ),
					'cache_dir'     => WP_CONTENT_DIR . '/cache/all/',
				];
				break;
		}

		return [
			'active_plugin'     => $plugin,
			'object_cache'      => wp_using_ext_object_cache(),
			'settings'          => $settings,
		];
	}
}
