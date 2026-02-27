# WP AI Assistant - Scaffold Guide

> Step-by-step guide to scaffold the plugin from zero to a working chat sidebar in wp-admin.

## Prerequisites

- LocalWP with a WordPress site running
- Node.js 18+ installed on your machine
- Composer installed (for PHP autoloading)
- Your LocalWP plugins path (e.g. `~/Local Sites/my-site/app/public/wp-content/plugins/`)

## Step 1: Create Plugin Directory

```bash
cd ~/Local\ Sites/<your-site>/app/public/wp-content/plugins/
mkdir wp-ai-assistant
cd wp-ai-assistant
```

## Step 2: Initialize Package Files

### package.json

```bash
npm init -y
npm install --save-dev @wordpress/scripts @wordpress/components @wordpress/element @wordpress/api-fetch @wordpress/i18n
```

Then update `package.json` scripts:

```json
{
  "name": "wp-ai-assistant",
  "version": "1.0.0",
  "scripts": {
    "build": "wp-scripts build src/index.js --output-path=admin/js/build",
    "start": "wp-scripts start src/index.js --output-path=admin/js/build",
    "lint": "wp-scripts lint-js src/"
  }
}
```

### composer.json

```bash
composer init --name="your-vendor/wp-ai-assistant" --type="wordpress-plugin" --no-interaction
```

Update `composer.json`:

```json
{
  "name": "your-vendor/wp-ai-assistant",
  "autoload": {
    "psr-4": {
      "WPAIAssistant\\": "includes/"
    }
  }
}
```

Then run:

```bash
composer dump-autoload
```

## Step 3: Create Directory Structure

```bash
mkdir -p includes/tools
mkdir -p admin/js/build
mkdir -p admin/css
mkdir -p src/components
mkdir -p languages
```

Your structure should look like:

```
wp-ai-assistant/
  wp-ai-assistant.php
  includes/
    class-plugin.php
    class-rest-controller.php
    class-tool-executor.php
    class-permissions.php
    class-database.php
    class-settings.php
    class-audit-log.php
    class-site-scanner.php
    class-llm-client.php
    tools/
      interface-tool.php
      class-content-tools.php
      class-site-tools.php
  src/
    index.js
    components/
      ChatSidebar.jsx
      MessageList.jsx
      MessageInput.jsx
  admin/
    js/build/           # Compiled by @wordpress/scripts
    css/
      sidebar.css
  languages/
  composer.json
  package.json
```

## Step 4: Main Plugin File

Create `wp-ai-assistant.php`:

```php
<?php
/**
 * Plugin Name: WP AI Assistant
 * Plugin URI:  https://your-domain.com
 * Description: AI-powered chat assistant inside wp-admin. Manage your site with natural language.
 * Version:     0.1.0
 * Author:      Your Name
 * Author URI:  https://your-domain.com
 * License:     GPL-2.0-or-later
 * Text Domain: wp-ai-assistant
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPAIA_VERSION', '0.1.0' );
define( 'WPAIA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPAIA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPAIA_PLUGIN_FILE', __FILE__ );

// Composer autoload
if ( file_exists( WPAIA_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once WPAIA_PLUGIN_DIR . 'vendor/autoload.php';
}

// Boot the plugin
add_action( 'plugins_loaded', function() {
    \WPAIAssistant\Plugin::instance();
});

// Activation hook
register_activation_hook( __FILE__, function() {
    \WPAIAssistant\Database::create_tables();
    \WPAIAssistant\SiteScanner::scan();
});

// Deactivation hook
register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'wpaia_daily_site_scan' );
});
```

## Step 5: Plugin Singleton

Create `includes/class-plugin.php`:

```php
<?php
namespace WPAIAssistant;

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

        // REST API
        add_action( 'rest_api_init', function() {
            $controller = new RestController();
            $controller->register_routes();
        });

        // Settings page
        add_action( 'admin_menu', function() {
            Settings::register_menu();
        });

        // Chat sidebar HTML
        add_action( 'admin_footer', [ $this, 'render_chat_container' ] );

        // Daily site scan cron
        if ( ! wp_next_scheduled( 'wpaia_daily_site_scan' ) ) {
            wp_schedule_event( time(), 'daily', 'wpaia_daily_site_scan' );
        }
        add_action( 'wpaia_daily_site_scan', [ SiteScanner::class, 'scan' ] );
    }

    public function enqueue_admin_assets( $hook ) {
        $asset_file = WPAIA_PLUGIN_DIR . 'admin/js/build/index.asset.php';

        if ( ! file_exists( $asset_file ) ) {
            return;
        }

        $asset = include $asset_file;

        wp_enqueue_script(
            'wpaia-chat',
            WPAIA_PLUGIN_URL . 'admin/js/build/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_enqueue_style(
            'wpaia-chat',
            WPAIA_PLUGIN_URL . 'admin/css/sidebar.css',
            [],
            WPAIA_VERSION
        );

        // Pass data to JS
        wp_localize_script( 'wpaia-chat', 'wpaiaData', [
            'restUrl'  => rest_url( 'wp-ai-assistant/v1/' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'siteProfile' => SiteScanner::get_profile(),
        ]);
    }

    public function add_admin_bar_button( $wp_admin_bar ) {
        if ( ! is_admin() ) {
            return;
        }

        $wp_admin_bar->add_node([
            'id'    => 'wpaia-toggle',
            'title' => '💬 AI Assistant',
            'href'  => '#',
            'meta'  => [
                'onclick' => 'document.dispatchEvent(new Event("wpaia-toggle")); return false;',
            ],
        ]);
    }

    public function render_chat_container() {
        echo '<div id="wpaia-chat-root"></div>';
    }
}
```

## Step 6: Database Class

Create `includes/class-database.php`:

```php
<?php
namespace WPAIAssistant;

class Database {
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $conversations = $wpdb->prefix . 'wpaia_conversations';
        $messages      = $wpdb->prefix . 'wpaia_messages';
        $actions       = $wpdb->prefix . 'wpaia_actions';

        $sql = "
            CREATE TABLE {$conversations} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                title varchar(255) DEFAULT '',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id)
            ) {$charset};

            CREATE TABLE {$messages} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                conversation_id bigint(20) unsigned NOT NULL,
                role varchar(20) NOT NULL,
                content longtext NOT NULL,
                token_count int unsigned DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY conversation_id (conversation_id)
            ) {$charset};

            CREATE TABLE {$actions} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                conversation_id bigint(20) unsigned NOT NULL,
                message_id bigint(20) unsigned DEFAULT NULL,
                user_id bigint(20) unsigned NOT NULL,
                tool_name varchar(100) NOT NULL,
                tool_input longtext,
                tool_output longtext,
                status varchar(20) DEFAULT 'success',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY conversation_id (conversation_id),
                KEY user_id (user_id)
            ) {$charset};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( 'wpaia_db_version', WPAIA_VERSION );
    }
}
```

## Step 7: Site Scanner

Create `includes/class-site-scanner.php`:

```php
<?php
namespace WPAIAssistant;

class SiteScanner {
    public static function scan() {
        global $wpdb;

        $profile = [
            'wp_version'    => get_bloginfo( 'version' ),
            'php_version'   => phpversion(),
            'site_url'      => get_site_url(),
            'site_name'     => get_bloginfo( 'name' ),
            'theme'         => self::get_theme_info(),
            'plugins'       => self::get_plugins_info(),
            'post_types'    => self::get_post_types(),
            'content_counts'=> self::get_content_counts(),
            'elementor'     => self::get_elementor_info(),
            'permalink'     => get_option( 'permalink_structure' ),
            'multisite'     => is_multisite(),
            'user_roles'    => self::get_user_role_counts(),
            'scanned_at'    => current_time( 'mysql' ),
        ];

        update_option( 'wpaia_site_profile', $profile, false );
        return $profile;
    }

    public static function get_profile() {
        $profile = get_option( 'wpaia_site_profile', null );
        if ( ! $profile ) {
            $profile = self::scan();
        }
        return $profile;
    }

    private static function get_theme_info() {
        $theme = wp_get_theme();
        return [
            'name'        => $theme->get( 'Name' ),
            'version'     => $theme->get( 'Version' ),
            'is_child'    => is_child_theme(),
            'parent'      => is_child_theme() ? $theme->parent()->get( 'Name' ) : null,
        ];
    }

    private static function get_plugins_info() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins    = get_plugins();
        $active_plugins = get_option( 'active_plugins', [] );
        $result = [];

        foreach ( $all_plugins as $path => $data ) {
            $result[] = [
                'name'    => $data['Name'],
                'version' => $data['Version'],
                'active'  => in_array( $path, $active_plugins ),
                'slug'    => dirname( $path ),
            ];
        }

        return $result;
    }

    private static function get_post_types() {
        $types = get_post_types( [ 'public' => true ], 'objects' );
        $result = [];
        foreach ( $types as $type ) {
            $result[] = [
                'name'  => $type->name,
                'label' => $type->label,
                'count' => (int) wp_count_posts( $type->name )->publish,
            ];
        }
        return $result;
    }

    private static function get_content_counts() {
        return [
            'posts'  => (int) wp_count_posts( 'post' )->publish,
            'pages'  => (int) wp_count_posts( 'page' )->publish,
            'media'  => (int) wp_count_attachments()->{'image/jpeg'} +
                        (int) wp_count_attachments()->{'image/png'},
        ];
    }

    private static function get_elementor_info() {
        $active = is_plugin_active( 'elementor/elementor.php' ) ||
                  is_plugin_active( 'elementor-pro/elementor-pro.php' );

        if ( ! $active ) {
            return [ 'installed' => false ];
        }

        global $wpdb;
        $elementor_pages = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
             WHERE meta_key = '_elementor_data'
             AND meta_value != ''"
        );

        return [
            'installed' => true,
            'pro'       => is_plugin_active( 'elementor-pro/elementor-pro.php' ),
            'version'   => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : 'unknown',
            'pages'     => $elementor_pages,
        ];
    }

    private static function get_user_role_counts() {
        $counts = count_users();
        return $counts['avail_roles'];
    }
}
```

## Step 8: REST Controller (Minimal)

Create `includes/class-rest-controller.php`:

```php
<?php
namespace WPAIAssistant;

class RestController {
    private $namespace = 'wp-ai-assistant/v1';

    public function register_routes() {
        register_rest_route( $this->namespace, '/chat', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_chat' ],
            'permission_callback' => [ $this, 'check_permission' ],
            'args' => [
                'message' => [
                    'required' => true,
                    'type'     => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'conversation_id' => [
                    'required' => false,
                    'type'     => 'integer',
                ],
            ],
        ]);

        register_rest_route( $this->namespace, '/conversations', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'list_conversations' ],
            'permission_callback' => [ $this, 'check_permission' ],
        ]);

        register_rest_route( $this->namespace, '/conversations/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_conversation' ],
            'permission_callback' => [ $this, 'check_permission' ],
        ]);

        register_rest_route( $this->namespace, '/conversations/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_conversation' ],
            'permission_callback' => [ $this, 'check_permission' ],
        ]);

        register_rest_route( $this->namespace, '/site-profile', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_site_profile' ],
            'permission_callback' => [ $this, 'check_permission' ],
        ]);

        register_rest_route( $this->namespace, '/site-profile/rescan', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'rescan_site' ],
            'permission_callback' => [ $this, 'check_admin' ],
        ]);
    }

    public function check_permission() {
        return is_user_logged_in();
    }

    public function check_admin() {
        return current_user_can( 'manage_options' );
    }

    public function handle_chat( $request ) {
        $message         = $request->get_param( 'message' );
        $conversation_id = $request->get_param( 'conversation_id' );
        $user_id         = get_current_user_id();

        // TODO: Forward to backend orchestration API
        // For now, echo back to prove the loop works
        return rest_ensure_response([
            'reply'           => "I received: \"{$message}\". Backend not connected yet.",
            'conversation_id' => $conversation_id,
        ]);
    }

    public function list_conversations( $request ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'wpaia_conversations';
        $user_id = get_current_user_id();

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC LIMIT 50",
                $user_id
            )
        );

        return rest_ensure_response( $rows );
    }

    public function get_conversation( $request ) {
        global $wpdb;
        $conv_table = $wpdb->prefix . 'wpaia_conversations';
        $msg_table  = $wpdb->prefix . 'wpaia_messages';
        $id         = (int) $request['id'];
        $user_id    = get_current_user_id();

        $conv = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$conv_table} WHERE id = %d AND user_id = %d",
                $id, $user_id
            )
        );

        if ( ! $conv ) {
            return new \WP_Error( 'not_found', 'Conversation not found', [ 'status' => 404 ] );
        }

        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$msg_table} WHERE conversation_id = %d ORDER BY created_at ASC",
                $id
            )
        );

        $conv->messages = $messages;
        return rest_ensure_response( $conv );
    }

    public function delete_conversation( $request ) {
        global $wpdb;
        $conv_table = $wpdb->prefix . 'wpaia_conversations';
        $msg_table  = $wpdb->prefix . 'wpaia_messages';
        $id         = (int) $request['id'];
        $user_id    = get_current_user_id();

        $deleted = $wpdb->delete( $conv_table, [ 'id' => $id, 'user_id' => $user_id ] );

        if ( $deleted ) {
            $wpdb->delete( $msg_table, [ 'conversation_id' => $id ] );
        }

        return rest_ensure_response( [ 'deleted' => (bool) $deleted ] );
    }

    public function get_site_profile() {
        return rest_ensure_response( SiteScanner::get_profile() );
    }

    public function rescan_site() {
        return rest_ensure_response( SiteScanner::scan() );
    }
}
```

## Step 9: Settings Page

Create `includes/class-settings.php`:

```php
<?php
namespace WPAIAssistant;

class Settings {
    public static function register_menu() {
        add_options_page(
            'AI Assistant',
            'AI Assistant',
            'manage_options',
            'wp-ai-assistant',
            [ self::class, 'render_page' ]
        );
    }

    public static function render_page() {
        if ( isset( $_POST['wpaia_save'] ) && check_admin_referer( 'wpaia_settings' ) ) {
            $api_key = sanitize_text_field( $_POST['wpaia_api_key'] ?? '' );
            if ( $api_key ) {
                update_option( 'wpaia_api_key', self::encrypt( $api_key ) );
            }

            $model = sanitize_text_field( $_POST['wpaia_model'] ?? 'claude-sonnet' );
            update_option( 'wpaia_model', $model );

            $rate_limit = absint( $_POST['wpaia_rate_limit'] ?? 50 );
            update_option( 'wpaia_rate_limit', $rate_limit );

            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }

        $api_key    = get_option( 'wpaia_api_key' ) ? '••••••••' : '';
        $model      = get_option( 'wpaia_model', 'claude-sonnet' );
        $rate_limit = get_option( 'wpaia_rate_limit', 50 );

        ?>
        <div class="wrap">
            <h1>AI Assistant Settings</h1>
            <form method="post">
                <?php wp_nonce_field( 'wpaia_settings' ); ?>

                <table class="form-table">
                    <tr>
                        <th>API Key</th>
                        <td>
                            <input type="password" name="wpaia_api_key"
                                   value="<?php echo esc_attr( $api_key ); ?>"
                                   class="regular-text"
                                   placeholder="Enter your API key" />
                            <p class="description">Your Anthropic or OpenAI API key. Stored encrypted.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Model</th>
                        <td>
                            <select name="wpaia_model">
                                <option value="claude-sonnet" <?php selected( $model, 'claude-sonnet' ); ?>>Claude Sonnet</option>
                                <option value="claude-haiku" <?php selected( $model, 'claude-haiku' ); ?>>Claude Haiku</option>
                                <option value="gpt-4o" <?php selected( $model, 'gpt-4o' ); ?>>GPT-4o</option>
                                <option value="gpt-4o-mini" <?php selected( $model, 'gpt-4o-mini' ); ?>>GPT-4o Mini</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Rate Limit</th>
                        <td>
                            <input type="number" name="wpaia_rate_limit"
                                   value="<?php echo esc_attr( $rate_limit ); ?>"
                                   min="1" max="1000" />
                            <p class="description">Maximum messages per user per day.</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="wpaia_save" class="button-primary" value="Save Settings" />
                </p>
            </form>

            <hr />
            <h2>Site Profile</h2>
            <p>
                <button class="button" onclick="fetch(wpaiaData.restUrl + 'site-profile/rescan', {method:'POST', headers:{'X-WP-Nonce': wpaiaData.nonce}}).then(()=>location.reload())">
                    Rescan Site
                </button>
            </p>
            <pre style="background:#f0f0f0;padding:15px;max-height:400px;overflow:auto;">
                <?php echo esc_html( json_encode( SiteScanner::get_profile(), JSON_PRETTY_PRINT ) ); ?>
            </pre>
        </div>
        <?php
    }

    private static function encrypt( $value ) {
        $key    = wp_salt( 'auth' );
        $cipher = 'aes-256-cbc';
        $iv     = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $cipher ) );
        $encrypted = openssl_encrypt( $value, $cipher, $key, 0, $iv );
        return base64_encode( $iv . '::' . $encrypted );
    }

    public static function decrypt( $value ) {
        $key    = wp_salt( 'auth' );
        $cipher = 'aes-256-cbc';
        $data   = base64_decode( $value );
        list( $iv, $encrypted ) = explode( '::', $data, 2 );
        return openssl_decrypt( $encrypted, $cipher, $key, 0, $iv );
    }
}
```

## Step 10: React Chat UI (Entry Point)

Create `src/index.js`:

```jsx
import { createRoot } from '@wordpress/element';
import ChatSidebar from './components/ChatSidebar';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('wpaia-chat-root');
    if (container) {
        const root = createRoot(container);
        root.render(<ChatSidebar />);
    }
});
```

## Step 11: Chat Sidebar Component

Create `src/components/ChatSidebar.jsx`:

```jsx
import { useState, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import MessageList from './MessageList';
import MessageInput from './MessageInput';

const ChatSidebar = () => {
    const [isOpen, setIsOpen] = useState(false);
    const [messages, setMessages] = useState([]);
    const [loading, setLoading] = useState(false);
    const [conversationId, setConversationId] = useState(null);

    useEffect(() => {
        const toggle = () => setIsOpen(prev => !prev);
        document.addEventListener('wpaia-toggle', toggle);
        return () => document.removeEventListener('wpaia-toggle', toggle);
    }, []);

    const sendMessage = async (text) => {
        const userMessage = { role: 'user', content: text };
        setMessages(prev => [...prev, userMessage]);
        setLoading(true);

        try {
            const response = await apiFetch({
                path: 'wp-ai-assistant/v1/chat',
                method: 'POST',
                data: {
                    message: text,
                    conversation_id: conversationId,
                },
            });

            const assistantMessage = { role: 'assistant', content: response.reply };
            setMessages(prev => [...prev, assistantMessage]);

            if (response.conversation_id) {
                setConversationId(response.conversation_id);
            }
        } catch (err) {
            const errorMessage = { role: 'assistant', content: `Error: ${err.message}` };
            setMessages(prev => [...prev, errorMessage]);
        } finally {
            setLoading(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="wpaia-sidebar">
            <div className="wpaia-sidebar-header">
                <h3>AI Assistant</h3>
                <button onClick={() => setIsOpen(false)} className="wpaia-close">&times;</button>
            </div>
            <MessageList messages={messages} loading={loading} />
            <MessageInput onSend={sendMessage} disabled={loading} />
        </div>
    );
};

export default ChatSidebar;
```

## Step 12: Message Components

Create `src/components/MessageList.jsx`:

```jsx
import { useEffect, useRef } from '@wordpress/element';

const MessageList = ({ messages, loading }) => {
    const endRef = useRef(null);

    useEffect(() => {
        endRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, loading]);

    return (
        <div className="wpaia-messages">
            {messages.length === 0 && (
                <div className="wpaia-welcome">
                    <p>Hi! I can help you manage your site. Try:</p>
                    <ul>
                        <li>"List all published posts"</li>
                        <li>"What plugins are installed?"</li>
                        <li>"Replace 'old text' with 'new text' across all pages"</li>
                    </ul>
                </div>
            )}
            {messages.map((msg, i) => (
                <div key={i} className={`wpaia-message wpaia-message-${msg.role}`}>
                    <div className="wpaia-message-content">{msg.content}</div>
                </div>
            ))}
            {loading && (
                <div className="wpaia-message wpaia-message-assistant">
                    <div className="wpaia-typing">Thinking...</div>
                </div>
            )}
            <div ref={endRef} />
        </div>
    );
};

export default MessageList;
```

Create `src/components/MessageInput.jsx`:

```jsx
import { useState } from '@wordpress/element';

const MessageInput = ({ onSend, disabled }) => {
    const [text, setText] = useState('');

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!text.trim() || disabled) return;
        onSend(text.trim());
        setText('');
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSubmit(e);
        }
    };

    return (
        <form className="wpaia-input-form" onSubmit={handleSubmit}>
            <textarea
                value={text}
                onChange={(e) => setText(e.target.value)}
                onKeyDown={handleKeyDown}
                placeholder="Ask me anything about your site..."
                disabled={disabled}
                rows={2}
            />
            <button type="submit" disabled={disabled || !text.trim()}>
                Send
            </button>
        </form>
    );
};

export default MessageInput;
```

## Step 13: Sidebar CSS

Create `admin/css/sidebar.css`:

```css
#wpaia-chat-root {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.wpaia-sidebar {
    position: fixed;
    top: 32px; /* Below WP admin bar */
    right: 0;
    width: 400px;
    height: calc(100vh - 32px);
    background: #fff;
    box-shadow: -2px 0 12px rgba(0, 0, 0, 0.15);
    z-index: 99999;
    display: flex;
    flex-direction: column;
    border-left: 1px solid #ddd;
}

.wpaia-sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
}

.wpaia-sidebar-header h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
}

.wpaia-close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: #666;
    padding: 0 4px;
}

.wpaia-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
}

.wpaia-welcome {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
}

.wpaia-welcome ul {
    padding-left: 20px;
    margin-top: 8px;
}

.wpaia-welcome li {
    font-style: italic;
    margin-bottom: 6px;
}

.wpaia-message {
    margin-bottom: 12px;
    display: flex;
}

.wpaia-message-content {
    max-width: 85%;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 14px;
    line-height: 1.5;
    word-wrap: break-word;
}

.wpaia-message-user {
    justify-content: flex-end;
}

.wpaia-message-user .wpaia-message-content {
    background: #2271b1;
    color: #fff;
    border-bottom-right-radius: 4px;
}

.wpaia-message-assistant .wpaia-message-content {
    background: #f0f0f0;
    color: #1d2327;
    border-bottom-left-radius: 4px;
}

.wpaia-typing {
    color: #888;
    font-style: italic;
    padding: 10px 14px;
}

.wpaia-input-form {
    display: flex;
    gap: 8px;
    padding: 12px 16px;
    border-top: 1px solid #eee;
    background: #f8f9fa;
}

.wpaia-input-form textarea {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 14px;
    resize: none;
    font-family: inherit;
    outline: none;
}

.wpaia-input-form textarea:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
}

.wpaia-input-form button {
    background: #2271b1;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 14px;
    cursor: pointer;
    font-weight: 500;
    white-space: nowrap;
}

.wpaia-input-form button:hover {
    background: #135e96;
}

.wpaia-input-form button:disabled {
    background: #a7aaad;
    cursor: not-allowed;
}

/* Mobile */
@media (max-width: 782px) {
    .wpaia-sidebar {
        width: 100%;
        top: 46px; /* Mobile admin bar height */
        height: calc(100vh - 46px);
    }
}
```

## Step 14: Build and Test

```bash
# From the plugin directory
npm run build          # One-time build
# OR
npm run start          # Watch mode (auto-rebuild on file changes)
```

Then go to your LocalWP site's wp-admin:

1. Go to **Plugins** and activate **WP AI Assistant**
2. You should see "💬 AI Assistant" in the admin bar
3. Click it to open the chat sidebar
4. Type a message, it should echo back (backend not connected yet)
5. Go to **Settings > AI Assistant** to see the settings page and site profile

## Step 15: Verify Everything Works

**Checklist:**
- [ ] Plugin activates without errors
- [ ] Admin bar button appears
- [ ] Chat sidebar opens/closes
- [ ] Messages send and receive (echo response)
- [ ] Settings page loads with site profile JSON
- [ ] DB tables created (check phpMyAdmin or `wp db query "SHOW TABLES LIKE '%wpaia%'"`)
- [ ] No console errors in browser dev tools

## What's Next

Once this scaffold is working:

1. **Connect LLM client** - Build `class-llm-client.php` to call Claude API
2. **Build tool executor** - `class-tool-executor.php` with the tool registration pattern
3. **First real tool** - `get_site_info` that returns the site profile through chat
4. **SSE streaming** - Replace the simple REST response with streaming
5. **Add tools one by one** - Each tool is 30-60 min once the framework exists
