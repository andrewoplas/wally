<?php
namespace Wally;

class Plugin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Admin assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // Admin bar button
        add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_button' ], 100 );

        // Post-activation admin notice.
        add_action( 'admin_notices', [ $this, 'activation_notice' ] );

        // Initialize the tool executor and register all tools.
        $this->register_tools();

        // REST API
        add_action( 'rest_api_init', function() {
            $controller = new RestController();
            $controller->register_routes();
        });

        // Settings page, audit log, and conversations browser
        add_action( 'admin_menu', function() {
            Settings::register_menu();
            AdminLogPage::register_menu();
            AdminConversationsPage::register_menu();
        });

        // Chat sidebar HTML
        add_action( 'admin_footer', [ $this, 'render_chat_container' ] );

        // Daily site scan cron
        if ( ! wp_next_scheduled( 'wally_daily_site_scan' ) ) {
            wp_schedule_event( time(), 'daily', 'wally_daily_site_scan' );
        }
        add_action( 'wally_daily_site_scan', [ SiteScanner::class, 'scan' ] );

        // Daily conversation auto-prune cron (default 90 days, 0 = disabled)
        if ( ! wp_next_scheduled( 'wally_auto_prune' ) ) {
            wp_schedule_event( time(), 'daily', 'wally_auto_prune' );
        }
        add_action( 'wally_auto_prune', [ Database::class, 'prune_old_conversations' ] );
    }

    /**
     * Register all tools with the ToolExecutor via auto-discovery.
     *
     * Scans the tools directory for PHP class files, loads them, and
     * registers every non-abstract ToolInterface subclass whose
     * can_register() method returns true. This allows new tools to be
     * added by simply creating a class file — no manual registration needed.
     */
    private function register_tools() {
        $executor  = ToolExecutor::instance();
        $tools_dir = WALLY_PLUGIN_DIR . 'includes/tools/';

        // Load all tool class files so their classes become available.
        foreach ( glob( $tools_dir . 'class-*-tools.php' ) as $file ) {
            require_once $file;
        }

        // Find and register every concrete ToolInterface implementation.
        foreach ( get_declared_classes() as $class_name ) {
            if (
                str_starts_with( $class_name, 'Wally\\Tools\\' ) &&
                is_subclass_of( $class_name, Tools\ToolInterface::class ) &&
                ! ( new \ReflectionClass( $class_name ) )->isAbstract() &&
                $class_name::can_register()
            ) {
                $executor->register_tool( new $class_name() );
            }
        }
    }

    public function enqueue_admin_assets( $hook ) {
        $asset_file = WALLY_PLUGIN_DIR . 'admin/js/build/index.asset.php';

        if ( ! file_exists( $asset_file ) ) {
            return;
        }

        $asset = include $asset_file;

        wp_enqueue_script(
            'wpaia-chat',
            WALLY_PLUGIN_URL . 'admin/js/build/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_enqueue_style(
            'wpaia-inter-font',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@700&family=Fira+Code:wght@400;500&display=swap',
            [],
            null
        );

        wp_enqueue_style(
            'wpaia-chat',
            WALLY_PLUGIN_URL . 'admin/css/sidebar.css',
            [ 'wpaia-inter-font' ],
            WALLY_VERSION
        );

        // Pass data to JS
        $current_user = wp_get_current_user();
        $role_names   = $current_user->roles;
        $user_role    = ! empty( $role_names ) ? ucfirst( reset( $role_names ) ) : 'Subscriber';

        wp_localize_script( 'wpaia-chat', 'wpaiaData', [
            'restUrl'         => rest_url( 'wally/v1/' ),
            'nonce'           => wp_create_nonce( 'wp_rest' ),
            'siteProfile'     => SiteScanner::get_profile(),
            'userRole'        => $user_role,
            'userPermissions' => Permissions::get_allowed_actions(),
            'isAdmin'         => current_user_can( 'manage_options' ),
            'hasLicense'      => ! empty( get_option( 'wally_license_key', '' ) ),
            'settingsUrl'     => admin_url( 'admin.php?page=wally' ),
        ]);
    }

    public function add_admin_bar_button( $wp_admin_bar ) {
        if ( ! is_admin() ) {
            return;
        }

        $wp_admin_bar->add_node([
            'id'    => 'wpaia-toggle',
            'title' => '&#x1F4AC; Wally — AI Assistant',
            'href'  => '#',
            'meta'  => [
                'onclick' => 'document.dispatchEvent(new Event("wpaia-toggle")); return false;',
            ],
        ]);
    }

    public function render_chat_container() {
        echo '<div id="wpaia-chat-root"></div>';
    }

    /**
     * Show a one-time admin notice after plugin activation.
     */
    public function activation_notice() {
        if ( ! get_transient( 'wally_activation_notice' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        delete_transient( 'wally_activation_notice' );

        $settings_url = admin_url( 'admin.php?page=wally' );

        printf(
            '<div class="notice notice-success is-dismissible"><p><strong>Wally</strong> is active! '
            . 'Your site has been scanned automatically. '
            . '<a href="%s">Configure your API key and settings</a> to get started, '
            . 'then click <strong>"Wally — AI Assistant"</strong> in the admin bar to open the chat.</p></div>',
            esc_url( $settings_url )
        );
    }
}
