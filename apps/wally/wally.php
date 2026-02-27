<?php
/**
 * Plugin Name: Wally
 * Plugin URI:  https://your-domain.com
 * Description: AI-powered chat assistant inside wp-admin. Manage your site with natural language.
 * Version:     0.1.0
 * Author:      Your Name
 * Author URI:  https://your-domain.com
 * License:     GPL-2.0-or-later
 * Text Domain: wally
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WALLY_VERSION', '0.1.0' );
define( 'WALLY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WALLY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WALLY_PLUGIN_FILE', __FILE__ );

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
