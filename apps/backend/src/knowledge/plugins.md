## Plugin Management

### Plugin File Paths
WordPress identifies plugins by their file path relative to wp-content/plugins/. Format: "slug/slug.php" (e.g., "akismet/akismet.php") or "single-file.php" for single-file plugins.

### Operations
- activate_plugin($plugin) — activates, fires activation hook
- deactivate_plugins($plugin) — deactivates, fires deactivation hook (note: plural function name)
- delete_plugins([$plugin]) — deletes files; must deactivate first; fires uninstall hook if defined
- install_plugin_install_status() — check install status before installing

### Installation from WordPress.org
Uses Plugin_Upgrader class and plugins_api() to search and install:
1. plugins_api('plugin_information', ['slug' => $slug]) — get plugin details
2. Plugin_Upgrader->install($download_url) — download and install
3. activate_plugin($plugin_path) — activate after install

### Common Plugin Slugs
Popular plugins use predictable slugs: wordpress-seo (Yoast), woocommerce, contact-form-7, elementor, advanced-custom-fields, wpforms-lite, all-in-one-seo-pack, wordfence, wp-super-cache, really-simple-ssl.

### Must-Use Plugins
Located in wp-content/mu-plugins/. Always active, cannot be deactivated via admin. Not managed through standard plugin APIs.

### Plugin Updates
Check: get_plugin_updates(). Update via Plugin_Upgrader->upgrade($plugin_path). Always deactivate before major updates if there's risk of fatal errors.

### Plugin Data
Plugins often store data in wp_options or custom tables. Deactivation typically preserves data; uninstall/deletion should clean up via uninstall.php or register_uninstall_hook().
