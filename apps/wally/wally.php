<?php
/**
 * Plugin Name: Wally
 * Plugin URI:  https://www.wallychat.com
 * Description: AI-powered chat assistant inside wp-admin. Manage your site with natural language.
 * Version:     0.1.4
 * Author:      Andrew Oplas
 * Author URI:  https://andrewoplas.com
 * License:     GPL-2.0-or-later
 * Text Domain: wally
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WALLY_VERSION', '0.1.4' );
define( 'WALLY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WALLY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WALLY_PLUGIN_FILE', __FILE__ );

// Load backend URL from .env if present (local dev), otherwise use production default.
$wally_backend_url = 'https://wally.up.railway.app/api/v1';
$wally_env_file    = WALLY_PLUGIN_DIR . '.env';
if ( file_exists( $wally_env_file ) ) {
    foreach ( file( $wally_env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
        if ( str_starts_with( trim( $line ), '#' ) ) {
            continue;
        }
        if ( str_starts_with( $line, 'BACKEND_URL=' ) ) {
            $wally_backend_url = substr( $line, strlen( 'BACKEND_URL=' ) );
            break;
        }
    }
}

define( 'WALLY_DEFAULT_BACKEND_URL', $wally_backend_url );
unset( $wally_backend_url, $wally_env_file );

// Composer autoload
if ( file_exists( WALLY_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once WALLY_PLUGIN_DIR . 'vendor/autoload.php';
}

// Boot the plugin
add_action( 'plugins_loaded', function() {
    \Wally\Plugin::instance();
});

// Activation hook
register_activation_hook( __FILE__, function() {
    \Wally\Database::create_tables();
    \Wally\SiteScanner::scan();
    set_transient( 'wally_activation_notice', true, 300 ); // Show notice for 5 minutes.
});

// Deactivation hook
register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'wally_daily_site_scan' );
    wp_clear_scheduled_hook( 'wally_auto_prune' );
});
