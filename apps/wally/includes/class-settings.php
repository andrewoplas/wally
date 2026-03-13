<?php
namespace Wally;

class Settings {
    public static function register_menu() {
        add_menu_page(
            esc_html__( 'Wally — AI Assistant', 'wally' ),
            esc_html__( 'Wally — AI Assistant', 'wally' ),
            'manage_options',
            'wally',
            [ self::class, 'render_page' ],
            'dashicons-format-chat',
            2
        );
    }

    public static function render_page() {
        $is_admin = current_user_can( 'manage_options' );

        $activation_notice = null;

        if ( $is_admin && isset( $_POST['wally_save'] ) && check_admin_referer( 'wally_settings' ) ) {
            $license_key = sanitize_text_field( $_POST['wally_license_key'] ?? '' );
            $license_key_changed = false;
            if ( $license_key && $license_key !== '••••••••' ) {
                update_option( 'wally_license_key', self::encrypt( $license_key ) );
                $license_key_changed = true;
            }

            if ( $license_key_changed ) {
                $activation_notice = self::activate_site( $license_key, WALLY_DEFAULT_BACKEND_URL );
            }

            $all_wp_roles  = array_keys( wp_roles()->roles );
            $allowed_roles = [];
            foreach ( $all_wp_roles as $role ) {
                if ( ! empty( $_POST['wally_allowed_roles'][ $role ] ) ) {
                    $allowed_roles[] = $role;
                }
            }
            if ( ! in_array( 'administrator', $allowed_roles, true ) ) {
                $allowed_roles[] = 'administrator';
            }
            update_option( 'wally_allowed_roles', $allowed_roles );

            $rate_limit = absint( $_POST['wally_rate_limit'] ?? 50 );
            update_option( 'wally_rate_limit', $rate_limit );

            $token_budget = absint( $_POST['wally_monthly_token_budget'] ?? 0 );
            update_option( 'wally_monthly_token_budget', $token_budget );

            $data_retention = absint( $_POST['wally_data_retention'] ?? 90 );
            update_option( 'wally_data_retention', $data_retention );

            $custom_prompt = sanitize_textarea_field( $_POST['wally_custom_prompt'] ?? '' );
            update_option( 'wally_custom_prompt', $custom_prompt );

            $confirm_destructive = ! empty( $_POST['wally_confirm_destructive'] );
            update_option( 'wally_confirm_destructive', $confirm_destructive );

            $stream_responses = ! empty( $_POST['wally_stream_responses'] );
            update_option( 'wally_stream_responses', $stream_responses );

            $notification_sounds = ! empty( $_POST['wally_notification_sounds'] );
            update_option( 'wally_notification_sounds', $notification_sounds );

            $all_actions = Permissions::get_all_actions();
            $all_roles   = array_keys( Permissions::get_default_permissions() );
            $tool_perms  = [];

            foreach ( $all_roles as $role ) {
                if ( $role === 'administrator' ) {
                    $tool_perms[ $role ] = $all_actions;
                    continue;
                }
                $tool_perms[ $role ] = [];
                foreach ( $all_actions as $action ) {
                    if ( ! empty( $_POST['wally_perm'][ $role ][ $action ] ) ) {
                        $tool_perms[ $role ][] = $action;
                    }
                }
            }
            update_option( 'wally_tool_permissions', $tool_perms );
        }

        $license_key    = get_option( 'wally_license_key' ) ? '••••••••' : '';
        $allowed_roles  = get_option( 'wally_allowed_roles', [ 'administrator', 'editor' ] );
        $rate_limit     = get_option( 'wally_rate_limit', 50 );
        $token_budget   = get_option( 'wally_monthly_token_budget', 0 );
        $data_retention = get_option( 'wally_data_retention', 90 );
        $custom_prompt = get_option( 'wally_custom_prompt', '' );
        $site_profile  = SiteScanner::get_profile();

        $profile_display = [
            __( 'Site URL', 'wally' )           => $site_profile['site_url'] ?? get_site_url(),
            __( 'WordPress Version', 'wally' )  => $site_profile['wp_version'] ?? get_bloginfo( 'version' ),
            __( 'Active Theme', 'wally' )       => $site_profile['theme'] ?? wp_get_theme()->get( 'Name' ),
            __( 'Active Plugins', 'wally' )     => isset( $site_profile['active_plugins'] ) ? count( $site_profile['active_plugins'] ) . ' plugins' : '—',
            __( 'PHP Version', 'wally' )        => $site_profile['php_version'] ?? PHP_VERSION,
        ];

        $last_scan = get_option( 'wally_last_site_scan' );
        $last_scan_display = $last_scan
            ? sprintf( __( 'Last scanned: %s', 'wally' ), date( 'M j, Y \a\t g:i A', strtotime( $last_scan ) ) )
            : __( 'Not yet scanned.', 'wally' );

        // Audit log recent items
        $audit_result = AuditLog::get_actions( [ 'per_page' => 5, 'page' => 1 ] );
        $audit_items  = $audit_result['items'] ?? [];

        $all_actions   = Permissions::get_all_actions();
        $defaults      = Permissions::get_default_permissions();
        $overrides     = get_option( 'wally_tool_permissions', [] );
        $action_labels = [
            'read'    => __( 'Read', 'wally' ),
            'create'  => __( 'Create', 'wally' ),
            'update'  => __( 'Update', 'wally' ),
            'delete'  => __( 'Delete', 'wally' ),
            'plugins' => __( 'Plugins', 'wally' ),
            'site'    => __( 'Site Settings', 'wally' ),
        ];
        $action_descs = [
            'read'    => __( 'View & search content, settings, and plugins', 'wally' ),
            'create'  => __( 'Create new posts, pages, and taxonomy terms', 'wally' ),
            'update'  => __( 'Edit existing content and Elementor pages', 'wally' ),
            'delete'  => __( 'Trash or permanently delete content', 'wally' ),
            'plugins' => __( 'Install, activate, deactivate, and update plugins', 'wally' ),
            'site'    => __( 'Modify WordPress settings and options', 'wally' ),
        ];

        $conv_url      = admin_url( 'admin.php?page=wpaia-conversations' );
        $audit_url     = admin_url( 'admin.php?page=wpaia-audit-log' );
        $settings_url  = admin_url( 'admin.php?page=wally' );

        self::render_styles();
        ?>

        <?php if ( $activation_notice ) : ?>
        <div class="notice <?php echo $activation_notice['type'] === 'success' ? 'notice-success' : 'notice-error'; ?> is-dismissible">
            <p><?php echo esc_html( $activation_notice['message'] ); ?></p>
        </div>
        <?php endif; ?>

        <form method="post" id="wpaia-settings-form">
        <?php wp_nonce_field( 'wally_settings' ); ?>

        <div class="wpaia-admin">

            <!-- Header -->
            <div class="wpaia-header">
                <div class="wpaia-header-left">
                    <div class="wpaia-logo">
					<img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'admin/images/wp-ai-logo.png' ); ?>" alt="Wally Logo" />
				</div>
                    <span class="wpaia-brand">Wally</span>
                </div>
                <div class="wpaia-header-right">
                    <?php if ( $is_admin ) : ?>
                    <button type="submit" name="wally_save" class="wpaia-btn-primary"><?php esc_html_e( 'Save Settings', 'wally' ); ?></button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Body -->
            <div class="wpaia-body">

                <!-- Sidebar -->
                <nav class="wpaia-sidebar">
                    <a href="#section-general" class="wpaia-nav-item wpaia-nav-active">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <?php esc_html_e( 'General', 'wally' ); ?>
                    </a>
                    <a href="#section-permissions" class="wpaia-nav-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <?php esc_html_e( 'Permissions', 'wally' ); ?>
                    </a>
                    <a href="#section-site-profile" class="wpaia-nav-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        <?php esc_html_e( 'Site Profile', 'wally' ); ?>
                    </a>
                    <a href="#section-audit-log" class="wpaia-nav-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        <?php esc_html_e( 'Audit Log', 'wally' ); ?>
                    </a>
                    <a href="<?php echo esc_url( $conv_url ); ?>" class="wpaia-nav-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <?php esc_html_e( 'Conversations', 'wally' ); ?>
                    </a>
                </nav>

                <!-- Main Content -->
                <main class="wpaia-main">

                    <?php if ( ! $is_admin ) : ?>
                    <div class="wpaia-notice-readonly">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <?php esc_html_e( 'Settings can only be changed by administrators.', 'wally' ); ?>
                    </div>
                    <fieldset disabled style="border:none;padding:0;margin:0;">
                    <?php endif; ?>

                    <!-- ─── General ─────────────────────────────────── -->
                    <section id="section-general" class="wpaia-section">
                        <div class="wpaia-section-header">
                            <h1 class="wpaia-section-title"><?php esc_html_e( 'General', 'wally' ); ?></h1>
                            <p class="wpaia-section-desc"><?php esc_html_e( 'Configure your AI assistant\'s activation, usage limits, and behavior.', 'wally' ); ?></p>
                        </div>

                        <!-- Activation Card -->
                        <div class="wpaia-card">
                            <div class="wpaia-card-title"><?php esc_html_e( 'Activation', 'wally' ); ?></div>
                            <p class="wpaia-card-desc"><?php esc_html_e( 'Your license key connects this plugin to the Wally service.', 'wally' ); ?></p>

                            <div class="wpaia-field">
                                <label class="wpaia-label"><?php esc_html_e( 'License Key', 'wally' ); ?></label>
                                <input type="password" name="wally_license_key" class="wpaia-input"
                                       value="<?php echo esc_attr( $license_key ); ?>"
                                       placeholder="<?php echo esc_attr__( 'Enter your license key', 'wally' ); ?>" autocomplete="off" />
                                <span class="wpaia-hint"><?php echo wp_kses( sprintf( __( 'Stored encrypted. Get your key from your <a href="%s" target="_blank" rel="noopener noreferrer">account dashboard</a>.', 'wally' ), 'https://www.wallychat.com/app/license' ), [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ); ?></span>
                            </div>
                        </div>

                        <!-- Usage Limits Card -->
                        <div class="wpaia-card">
                            <div class="wpaia-card-title"><?php esc_html_e( 'Usage Limits', 'wally' ); ?></div>
                            <p class="wpaia-card-desc"><?php esc_html_e( 'Control how much the AI assistant can be used on your site.', 'wally' ); ?></p>

                            <div class="wpaia-row2">
                                <div class="wpaia-field">
                                    <label class="wpaia-label"><?php esc_html_e( 'Rate Limit', 'wally' ); ?></label>
                                    <input type="number" name="wally_rate_limit" class="wpaia-input"
                                           value="<?php echo esc_attr( $rate_limit ); ?>"
                                           min="1" max="1000" />
                                    <span class="wpaia-hint"><?php esc_html_e( 'Maximum messages per user per day.', 'wally' ); ?></span>
                                </div>
                                <div class="wpaia-field">
                                    <label class="wpaia-label"><?php esc_html_e( 'Monthly Token Budget', 'wally' ); ?></label>
                                    <input type="number" name="wally_monthly_token_budget" class="wpaia-input"
                                           value="<?php echo esc_attr( $token_budget ); ?>"
                                           min="0" max="100000000" step="1000" />
                                    <span class="wpaia-hint"><?php esc_html_e( 'Total tokens per month. 0 = unlimited.', 'wally' ); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Behavior Card -->
                        <div class="wpaia-card">
                            <div class="wpaia-card-title"><?php esc_html_e( 'Behavior', 'wally' ); ?></div>

                            <div class="wpaia-field">
                                <label class="wpaia-label"><?php esc_html_e( 'Custom System Prompt', 'wally' ); ?></label>
                                <textarea name="wally_custom_prompt" class="wpaia-textarea"
                                          rows="5"
                                          placeholder="<?php echo esc_attr__( 'e.g. You are a helpful assistant for Acme Corp. Always respond in a professional tone.', 'wally' ); ?>"
                                ><?php echo esc_textarea( $custom_prompt ); ?></textarea>
                                <span class="wpaia-hint"><?php esc_html_e( 'Appended to the AI system prompt. Use for branding, tone, or site-specific context.', 'wally' ); ?></span>
                            </div>

                            <div class="wpaia-divider"></div>

                            <div class="wpaia-field">
                                <label class="wpaia-label"><?php esc_html_e( 'Data Retention', 'wally' ); ?></label>
                                <select name="wally_data_retention" class="wpaia-select">
                                    <option value="30"  <?php selected( $data_retention, 30 ); ?>><?php echo esc_html__( '30 days', 'wally' ); ?></option>
                                    <option value="90"  <?php selected( $data_retention, 90 ); ?>><?php echo esc_html__( '90 days', 'wally' ); ?></option>
                                    <option value="180" <?php selected( $data_retention, 180 ); ?>><?php echo esc_html__( '180 days', 'wally' ); ?></option>
                                    <option value="365" <?php selected( $data_retention, 365 ); ?>><?php echo esc_html__( '1 year', 'wally' ); ?></option>
                                    <option value="0"   <?php selected( $data_retention, 0 ); ?>><?php echo esc_html__( 'Forever', 'wally' ); ?></option>
                                </select>
                                <span class="wpaia-hint"><?php esc_html_e( 'How long conversation history is stored before automatic deletion.', 'wally' ); ?></span>
                            </div>

                            <div class="wpaia-divider"></div>

                            <div class="wpaia-toggle-row">
                                <div class="wpaia-toggle-info">
                                    <div class="wpaia-toggle-label"><?php esc_html_e( 'Confirm destructive actions', 'wally' ); ?></div>
                                    <div class="wpaia-toggle-desc"><?php esc_html_e( 'Require confirmation before executing delete, update, or deactivate operations', 'wally' ); ?></div>
                                </div>
                                <label class="wpaia-toggle">
                                    <input type="checkbox" name="wally_confirm_destructive" value="1"
                                           <?php checked( (bool) get_option( 'wally_confirm_destructive', true ) ); ?> />
                                    <span class="wpaia-toggle-track"><span class="wpaia-toggle-thumb"></span></span>
                                </label>
                            </div>

                            <div class="wpaia-divider"></div>

                            <div class="wpaia-toggle-row">
                                <div class="wpaia-toggle-info">
                                    <div class="wpaia-toggle-label"><?php esc_html_e( 'Stream responses', 'wally' ); ?></div>
                                    <div class="wpaia-toggle-desc"><?php esc_html_e( 'Display AI responses in real-time as they are generated', 'wally' ); ?></div>
                                </div>
                                <label class="wpaia-toggle">
                                    <input type="checkbox" name="wally_stream_responses" value="1"
                                           <?php checked( (bool) get_option( 'wally_stream_responses', true ) ); ?> />
                                    <span class="wpaia-toggle-track"><span class="wpaia-toggle-thumb"></span></span>
                                </label>
                            </div>

                            <div class="wpaia-divider"></div>

                            <div class="wpaia-toggle-row">
                                <div class="wpaia-toggle-info">
                                    <div class="wpaia-toggle-label"><?php esc_html_e( 'Notification sounds', 'wally' ); ?></div>
                                    <div class="wpaia-toggle-desc"><?php esc_html_e( 'Play a sound when the assistant completes a response', 'wally' ); ?></div>
                                </div>
                                <label class="wpaia-toggle">
                                    <input type="checkbox" name="wally_notification_sounds" value="1"
                                           <?php checked( (bool) get_option( 'wally_notification_sounds', false ) ); ?> />
                                    <span class="wpaia-toggle-track"><span class="wpaia-toggle-thumb"></span></span>
                                </label>
                            </div>
                        </div>
                    </section>

                    <div class="wpaia-section-divider"></div>

                    <!-- ─── Permissions ──────────────────────────────── -->
                    <section id="section-permissions" class="wpaia-section">
                        <div class="wpaia-section-header">
                            <h2 class="wpaia-section-title"><?php esc_html_e( 'Permissions', 'wally' ); ?></h2>
                            <p class="wpaia-section-desc"><?php esc_html_e( 'Control which roles can access the chat and what actions they are allowed to perform.', 'wally' ); ?></p>
                        </div>

                        <!-- Chat Access Card -->
                        <div class="wpaia-card">
                            <div class="wpaia-card-title"><?php esc_html_e( 'Chat Access', 'wally' ); ?></div>
                            <p class="wpaia-card-desc"><?php esc_html_e( 'Choose which WordPress user roles can open and use the AI chat interface.', 'wally' ); ?></p>

                            <div class="wpaia-roles-grid">
                                <?php foreach ( wp_roles()->roles as $role_key => $role_data ) :
                                    $is_checked = is_array( $allowed_roles ) && in_array( $role_key, $allowed_roles, true );
                                    $is_admin   = $role_key === 'administrator';
                                ?>
                                <label class="wpaia-role-row <?php echo $is_admin ? 'wpaia-role-locked' : ''; ?>">
                                    <span class="wpaia-perm-toggle">
                                        <input type="checkbox"
                                               name="wally_allowed_roles[<?php echo esc_attr( $role_key ); ?>]"
                                               value="1"
                                               <?php checked( $is_checked ); ?>
                                               <?php disabled( $is_admin ); ?> />
                                        <span class="wpaia-perm-box"><svg width="10" height="10" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><polyline points="2,6 5,9 10,3" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                    </span>
                                    <span class="wpaia-role-info">
                                        <span class="wpaia-role-name"><?php echo esc_html( translate_user_role( $role_data['name'] ) ); ?></span>
                                        <?php if ( $is_admin ) : ?>
                                        <span class="wpaia-role-locked-badge"><?php esc_html_e( 'Always enabled', 'wally' ); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Action Permissions Card -->
                        <div class="wpaia-card">
                            <div class="wpaia-card-title"><?php esc_html_e( 'Action Permissions', 'wally' ); ?></div>
                            <p class="wpaia-card-desc"><?php esc_html_e( 'Define which action types each role can perform through the AI assistant.', 'wally' ); ?></p>

                            <div class="wpaia-table-wrap">
                                <table class="wpaia-table">
                                    <thead>
                                        <tr>
                                            <th class="wpaia-th-role"><?php esc_html_e( 'Role', 'wally' ); ?></th>
                                            <?php foreach ( $all_actions as $action ) : ?>
                                                <th class="wpaia-th-center" title="<?php echo esc_attr( $action_descs[ $action ] ?? '' ); ?>">
                                                    <?php echo esc_html( $action_labels[ $action ] ?? ucfirst( $action ) ); ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $defaults as $role => $default_actions ) :
                                            $is_admin_role = $role === 'administrator';
                                            $active_actions = $is_admin_role
                                                ? $all_actions
                                                : ( ( is_array( $overrides ) && isset( $overrides[ $role ] ) )
                                                    ? $overrides[ $role ]
                                                    : $default_actions );
                                        ?>
                                        <tr>
                                            <td class="wpaia-td-role"><?php echo esc_html( ucfirst( $role ) ); ?></td>
                                            <?php foreach ( $all_actions as $action ) :
                                                $checked = in_array( $action, $active_actions, true );
                                            ?>
                                            <td class="wpaia-td-center">
                                                <label class="wpaia-perm-toggle <?php echo $is_admin_role ? 'wpaia-perm-locked' : ''; ?>">
                                                    <input type="checkbox"
                                                           name="wally_perm[<?php echo esc_attr( $role ); ?>][<?php echo esc_attr( $action ); ?>]"
                                                           value="1"
                                                           <?php checked( $checked ); ?>
                                                           <?php disabled( $is_admin_role ); ?> />
                                                    <span class="wpaia-perm-box"><svg width="10" height="10" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><polyline points="2,6 5,9 10,3" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                                </label>
                                            </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <div class="wpaia-section-divider"></div>

                    <!-- ─── Site Profile ─────────────────────────────── -->
                    <section id="section-site-profile" class="wpaia-section">
                        <div class="wpaia-section-header">
                            <h2 class="wpaia-section-title"><?php esc_html_e( 'Site Profile', 'wally' ); ?></h2>
                            <p class="wpaia-section-desc"><?php esc_html_e( 'Your site profile is shared with the AI to provide context about your WordPress installation.', 'wally' ); ?></p>
                        </div>

                        <div class="wpaia-card">
                            <div class="wpaia-card-row-header">
                                <div class="wpaia-card-title"><?php esc_html_e( 'Site Information', 'wally' ); ?></div>
                                <button type="button" class="wpaia-btn-outline" onclick="wpaiaRescan(this)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                                    <?php esc_html_e( 'Rescan Site', 'wally' ); ?>
                                </button>
                            </div>

                            <div class="wpaia-info-grid">
                                <?php $first = true; foreach ( $profile_display as $key => $val ) : ?>
                                <div class="wpaia-info-row <?php echo $first ? '' : 'wpaia-info-row-border'; ?>">
                                    <span class="wpaia-info-key"><?php echo esc_html( $key ); ?></span>
                                    <span class="wpaia-info-val"><?php echo esc_html( $val ); ?></span>
                                </div>
                                <?php $first = false; endforeach; ?>
                            </div>

                            <div class="wpaia-scan-time"><?php echo esc_html( $last_scan_display ); ?></div>
                        </div>
                    </section>

                    <div class="wpaia-section-divider"></div>

                    <!-- ─── Audit Log ────────────────────────────────── -->
                    <section id="section-audit-log" class="wpaia-section">
                        <div class="wpaia-section-header">
                            <h2 class="wpaia-section-title"><?php esc_html_e( 'Audit Log', 'wally' ); ?></h2>
                            <p class="wpaia-section-desc"><?php esc_html_e( 'Track all actions performed by the AI assistant, including tool executions and content changes.', 'wally' ); ?></p>
                        </div>

                        <div class="wpaia-card">
                            <div class="wpaia-card-row-header">
                                <div class="wpaia-card-title"><?php esc_html_e( 'Recent Activity', 'wally' ); ?></div>
                                <a href="<?php echo esc_url( $audit_url ); ?>" class="wpaia-btn-outline">
                                    <?php esc_html_e( 'View All', 'wally' ); ?>
                                </a>
                            </div>

                            <div class="wpaia-table-wrap">
                                <table class="wpaia-table">
                                    <thead>
                                        <tr>
                                            <th style="width:140px;"><?php esc_html_e( 'Time', 'wally' ); ?></th>
                                            <th><?php esc_html_e( 'Action', 'wally' ); ?></th>
                                            <th style="width:120px;"><?php esc_html_e( 'Tool', 'wally' ); ?></th>
                                            <th style="width:80px;text-align:center;"><?php esc_html_e( 'Status', 'wally' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ( empty( $audit_items ) ) : ?>
                                        <tr>
                                            <td colspan="4" style="padding:20px 16px;color:#A1A1AA;font-size:13px;text-align:center;"><?php esc_html_e( 'No activity yet.', 'wally' ); ?></td>
                                        </tr>
                                        <?php else : foreach ( $audit_items as $item ) :
                                            $status_class = [
                                                'success'   => 'wpaia-badge-success',
                                                'failed'    => 'wpaia-badge-error',
                                                'pending'   => 'wpaia-badge-warning',
                                                'cancelled' => 'wpaia-badge-neutral',
                                            ][ $item->status ] ?? 'wpaia-badge-neutral';
                                            $action_label = $item->tool_name ?? 'Unknown';
                                            $time_diff    = human_time_diff( strtotime( $item->created_at ), time() ) . ' ago';
                                        ?>
                                        <tr class="wpaia-tr-border">
                                            <td class="wpaia-td-time"><?php echo esc_html( $time_diff ); ?></td>
                                            <td class="wpaia-td-action">
                                                <?php
                                                $output = json_decode( $item->tool_output ?? '{}', true );
                                                $desc   = $output['message'] ?? $action_label;
                                                echo esc_html( $desc );
                                                ?>
                                            </td>
                                            <td class="wpaia-td-tool"><?php echo esc_html( ucwords( str_replace( '_', ' ', $item->tool_name ) ) ); ?></td>
                                            <td style="text-align:center;">
                                                <span class="wpaia-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( ucfirst( $item->status ) ); ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <?php if ( ! $is_admin ) : ?></fieldset><?php endif; ?>

                    <!-- Footer -->
                    <div class="wpaia-footer">
                        <span class="wpaia-footer-text"><?php echo sprintf( esc_html__( 'Wally v%s', 'wally' ), esc_html( WALLY_VERSION ) ); ?></span>
                        <?php if ( $is_admin ) : ?>
                        <button type="submit" name="wally_save" class="wpaia-btn-primary"><?php esc_html_e( 'Save Settings', 'wally' ); ?></button>
                        <?php endif; ?>
                    </div>

                </main>
            </div>
        </div>
        </form>

        <script>
        function wpaiaRescan(btn) {
            btn.disabled = true;
            btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:wpaia-spin 1s linear infinite"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg> <?php echo esc_js( __( 'Scanning…', 'wally' ) ); ?>';
            fetch(wpaiaData.restUrl + 'site-profile/rescan', {
                method: 'POST',
                headers: { 'X-WP-Nonce': wpaiaData.nonce }
            }).then(function() {
                location.reload();
            }).catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<?php echo esc_js( __( 'Rescan Site', 'wally' ) ); ?>';
            });
        }

        // Highlight active nav on scroll
        (function() {
            var sections = ['section-general','section-permissions','section-site-profile','section-audit-log'];
            var navItems = document.querySelectorAll('.wpaia-nav-item');
            function updateNav() {
                var scrollY = window.scrollY + 120;
                var active = 0;
                sections.forEach(function(id, i) {
                    var el = document.getElementById(id);
                    if (el && el.offsetTop <= scrollY) active = i;
                });
                navItems.forEach(function(item, i) {
                    item.classList.toggle('wpaia-nav-active', i === active);
                });
            }
            window.addEventListener('scroll', updateNav, { passive: true });

            // Smooth scroll for nav
            navItems.forEach(function(item) {
                item.addEventListener('click', function(e) {
                    var href = item.getAttribute('href');
                    if (href && href.startsWith('#')) {
                        e.preventDefault();
                        var target = document.getElementById(href.slice(1));
                        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });

        })();
        </script>
        <?php
    }

    private static function render_styles() {
        ?>
        <style>
        /* ── Reset / Base ─────────────────────────── */
        #wpcontent { padding-left: 0 !important; }
        #wpbody-content { padding-bottom: 0; }
        .wpaia-admin *, .wpaia-admin *::before, .wpaia-admin *::after { box-sizing: border-box; }
        .wpaia-admin { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; color: #18181B; min-height: 100vh; background: #FFFFFF; }

        /* ── Header ──────────────────────────────── */
        .wpaia-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 40px;
            border-bottom: 1px solid #E4E4E7;
            background: #FFFFFF;
            position: sticky; top: 32px; z-index: 100;
        }
        .wpaia-header-left { display: flex; align-items: center; gap: 10px; }
        .wpaia-logo {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;
        }
        .wpaia-logo img { width: 100%; height: 100%; object-fit: cover; }
        .wpaia-brand { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-size: 20px; font-weight: 700; color: #18181B; }
        .wpaia-header-right { display: flex; align-items: center; gap: 12px; }

        /* ── Body layout ─────────────────────────── */
        .wpaia-body { display: flex; min-height: calc(100vh - 73px); }

        /* ── Sidebar ─────────────────────────────── */
        .wpaia-sidebar {
            width: 240px; flex-shrink: 0;
            padding: 32px 16px 32px 24px;
            display: flex; flex-direction: column; gap: 4px;
            position: sticky; top: 105px; align-self: flex-start;
        }
        .wpaia-nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 16px; border-radius: 12px;
            font-size: 14px; font-weight: 400; color: #71717A;
            text-decoration: none; transition: background .15s, color .15s;
            cursor: pointer; border: none; background: transparent;
        }
        .wpaia-nav-item svg { color: #A1A1AA; flex-shrink: 0; }
        .wpaia-nav-item:hover { background: #F4F4F5; color: #18181B; }
        .wpaia-nav-active { background: #F4F4F5 !important; color: #18181B !important; font-weight: 600 !important; }
        .wpaia-nav-active svg { color: #8B5CF6 !important; }

        /* ── Main content ────────────────────────── */
        .wpaia-main {
            flex: 1; min-width: 0;
            padding: 32px 60px 40px 32px;
            display: flex; flex-direction: column; gap: 32px;
        }

        /* ── Section ─────────────────────────────── */
        .wpaia-section { display: flex; flex-direction: column; gap: 24px; }
        .wpaia-section-header { display: flex; flex-direction: column; gap: 6px; }
        .wpaia-section-title { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-size: 28px; font-weight: 700; color: #18181B; margin: 0; line-height: 1.1; }
        .wpaia-section-desc { font-size: 15px; color: #71717A; margin: 0; line-height: 1.5; }
        .wpaia-section-divider { height: 1px; background: #E4E4E7; }

        /* ── Card ────────────────────────────────── */
        .wpaia-card {
            background: #F4F4F5; border-radius: 24px;
            padding: 28px; display: flex; flex-direction: column; gap: 20px;
        }
        .wpaia-card-title { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-size: 17px; font-weight: 700; color: #18181B; margin: 0; }
        .wpaia-card-desc { font-size: 13px; color: #A1A1AA; margin: 0; line-height: 1.4; }
        .wpaia-card-row-header { display: flex; align-items: center; justify-content: space-between; }

        /* ── Fields ──────────────────────────────── */
        .wpaia-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .wpaia-field { display: flex; flex-direction: column; gap: 6px; }
        .wpaia-label { font-size: 14px; font-weight: 600; color: #18181B; }
        .wpaia-admin .wpaia-input,
        .wpaia-admin .wpaia-select,
        .wpaia-admin .wpaia-textarea {
            height: 48px !important; padding: 0 18px !important;
            border: 1px solid #E4E4E7 !important; border-radius: 14px !important;
            font-family: 'Inter', sans-serif !important; font-size: 14px !important; color: #18181B !important;
            background: #FFFFFF !important; width: 100% !important; max-width: 100% !important; outline: none !important;
            box-shadow: none !important; margin: 0 !important;
            transition: border-color .15s, box-shadow .15s;
        }
        .wpaia-admin .wpaia-input:focus,
        .wpaia-admin .wpaia-select:focus,
        .wpaia-admin .wpaia-textarea:focus {
            border-color: #8B5CF6 !important;
            box-shadow: 0 0 0 3px rgba(139,92,246,.12) !important;
        }
        .wpaia-admin .wpaia-input::placeholder,
        .wpaia-admin .wpaia-textarea::placeholder { color: #A1A1AA; }
        .wpaia-admin .wpaia-textarea { height: 120px !important; padding: 16px 20px !important; resize: vertical; }
        .wpaia-admin .wpaia-select { appearance: none !important; -webkit-appearance: none !important; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2371717A' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") !important; background-repeat: no-repeat !important; background-position: right 18px center !important; padding-right: 44px !important; cursor: pointer; }
        .wpaia-input-md { max-width: 420px; }
        .wpaia-hint { font-size: 12px; color: #A1A1AA; }

        /* ── Toggle ──────────────────────────────── */
        .wpaia-toggle-row { display: flex; align-items: center; gap: 12px; }
        .wpaia-toggle-info { flex: 1; min-width: 0; }
        .wpaia-toggle-label { font-size: 14px; font-weight: 500; color: #18181B; }
        .wpaia-toggle-desc { font-size: 13px; color: #A1A1AA; line-height: 1.4; margin-top: 2px; }
        .wpaia-toggle { position: relative; display: inline-flex; align-items: center; cursor: pointer; flex-shrink: 0; }
        .wpaia-toggle input { position: absolute; opacity: 0; width: 0; height: 0; }
        .wpaia-toggle-track { width: 44px; height: 24px; background: #E4E4E7; border-radius: 100px; position: relative; transition: background .2s; display: block; }
        .wpaia-toggle input:checked + .wpaia-toggle-track { background: #8B5CF6; }
        .wpaia-toggle-thumb { position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: white; border-radius: 50%; box-shadow: 0 1px 4px rgba(0,0,0,.15); transition: transform .2s; display: block; }
        .wpaia-toggle input:checked ~ .wpaia-toggle-track .wpaia-toggle-thumb,
        .wpaia-toggle input:checked + .wpaia-toggle-track .wpaia-toggle-thumb { transform: translateX(20px); }
        .wpaia-divider { height: 1px; background: #E4E4E7; }

        /* ── Permissions table ───────────────────── */
        .wpaia-table-wrap { border-radius: 14px; border: 1px solid #E4E4E7; overflow: hidden; background: #FFFFFF; }
        .wpaia-table { width: 100%; border-collapse: collapse; }
        .wpaia-table thead tr { background: #FAFAFA; }
        .wpaia-table th { padding: 12px 16px; font-size: 12px; font-weight: 600; color: #71717A; text-align: left; border: none; }
        .wpaia-th-role { width: 140px; }
        .wpaia-th-center { text-align: center !important; }
        .wpaia-table td { padding: 14px 16px; font-size: 13px; border: none; }
        .wpaia-td-role { font-weight: 500; color: #18181B; }
        .wpaia-td-center { text-align: center; }
        .wpaia-table tr + tr td { border-top: 1px solid #F4F4F5; }
        .wpaia-tr-border td { border-top: 1px solid #F4F4F5 !important; }

        /* ── Permission checkbox ─────────────────── */
        .wpaia-perm-toggle { display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .wpaia-perm-toggle input { position: absolute; opacity: 0 !important; width: 0; height: 0; -webkit-appearance: none; appearance: none; }
        .wpaia-perm-toggle input::before, .wpaia-perm-toggle input::after { display: none !important; }
        .wpaia-perm-box {
            width: 16px; height: 16px; border-radius: 6px;
            border: 1px solid #C5C5CB; background: #FFFFFF;
            display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
            transition: border-color .15s, background .15s;
            overflow: hidden;
        }
        .wpaia-perm-box svg { display: none; }
        .wpaia-perm-toggle:hover .wpaia-perm-box { border-color: #5749F4; }
        .wpaia-perm-toggle input:checked + .wpaia-perm-box {
            background: #5749F4; border-color: #5749F4;
        }
        .wpaia-perm-toggle input:checked + .wpaia-perm-box svg { display: block; }

        /* ── Site Profile ────────────────────────── */
        .wpaia-info-grid { border-radius: 14px; border: 1px solid #E4E4E7; overflow: hidden; background: #FFFFFF; }
        .wpaia-info-row { display: flex; align-items: center; padding: 12px 16px; }
        .wpaia-info-row-border { border-top: 1px solid #F4F4F5; }
        .wpaia-info-key { width: 160px; flex-shrink: 0; font-size: 13px; font-weight: 500; color: #71717A; }
        .wpaia-info-val { flex: 1; font-size: 13px; color: #18181B; }
        .wpaia-scan-time { font-size: 12px; color: #A1A1AA; line-height: 1.5; }

        /* ── Audit Log ───────────────────────────── */
        .wpaia-td-time { width: 140px; font-size: 13px; color: #A1A1AA; }
        .wpaia-td-action { font-size: 13px; color: #18181B; }
        .wpaia-td-tool { width: 120px; font-size: 12px; color: #71717A; }
        .wpaia-badge {
            display: inline-block; padding: 3px 10px; border-radius: 100px;
            font-size: 11px; font-weight: 600;
        }
        .wpaia-badge-success { background: #DCFCE7; color: #16A34A; }
        .wpaia-badge-error   { background: #FEE2E2; color: #DC2626; }
        .wpaia-badge-warning { background: #FEF9C3; color: #CA8A04; }
        .wpaia-badge-neutral { background: #F4F4F5; color: #71717A; }

        /* ── Buttons ─────────────────────────────── */
        .wpaia-btn-primary {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 10px 24px; border-radius: 100px;
            background: #8B5CF6; color: #FFFFFF;
            font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600;
            border: none; cursor: pointer; transition: background .15s;
            text-decoration: none;
        }
        .wpaia-btn-primary:hover { background: #7C3AED; color: #FFFFFF; }
        .wpaia-btn-outline {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 16px; border-radius: 100px;
            border: 1px solid #E4E4E7; background: transparent;
            font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; color: #71717A;
            cursor: pointer; transition: background .15s, color .15s;
            text-decoration: none;
        }
        .wpaia-btn-outline:hover { background: #F4F4F5; color: #18181B; border-color: #D4D4D8; }
        .wpaia-btn-outline svg { color: #71717A; }

        /* ── Footer ──────────────────────────────── */
        .wpaia-footer {
            display: flex; align-items: center; justify-content: space-between;
            padding-top: 16px; border-top: 1px solid #E4E4E7; margin-top: 8px;
        }
        .wpaia-footer-text { font-size: 12px; color: #A1A1AA; }

        /* ── Access / Roles ──────────────────────── */
        .wpaia-roles-grid { border-radius: 14px; border: 1px solid #E4E4E7; overflow: hidden; background: #FFFFFF; }
        .wpaia-role-row { display: flex; align-items: center; gap: 12px; padding: 14px 16px; cursor: pointer; transition: background .15s; }
        .wpaia-role-row + .wpaia-role-row { border-top: 1px solid #F4F4F5; }
        .wpaia-role-row:hover { background: #FAFAFA; }
        .wpaia-role-locked { cursor: default; }
        .wpaia-role-locked:hover { background: transparent; }
        .wpaia-role-locked .wpaia-perm-toggle { opacity: 0.5; pointer-events: none; }
        .wpaia-perm-locked { opacity: 0.5; pointer-events: none; cursor: default; }
        .wpaia-role-info { display: flex; align-items: center; gap: 8px; }
        .wpaia-role-name { font-size: 14px; font-weight: 500; color: #18181B; }
        .wpaia-role-locked-badge { font-size: 11px; font-weight: 500; color: #A1A1AA; background: #F4F4F5; padding: 2px 8px; border-radius: 100px; }

        /* ── Spinner ─────────────────────────────── */
        @keyframes wpaia-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        /* ── Read-only notice ────────────────────── */
        .wpaia-notice-readonly {
            display: flex; align-items: center; gap: 8px;
            padding: 12px 16px; border-radius: 12px;
            background: #FEF9C3; color: #92400E;
            font-size: 13px; font-weight: 500;
        }
        fieldset[disabled] .wpaia-input,
        fieldset[disabled] .wpaia-select,
        fieldset[disabled] .wpaia-textarea {
            background: #F4F4F5 !important; color: #A1A1AA !important;
            cursor: not-allowed !important;
        }
        fieldset[disabled] .wpaia-toggle,
        fieldset[disabled] .wpaia-perm-toggle { cursor: not-allowed !important; opacity: .55; }
        fieldset[disabled] .wpaia-btn-outline { pointer-events: none; opacity: .55; }
        </style>
        <?php
    }

    private static function encrypt( $value ) {
        $key       = wp_salt( 'auth' );
        $cipher    = 'aes-256-cbc';
        $iv        = random_bytes( openssl_cipher_iv_length( $cipher ) );
        $encrypted = openssl_encrypt( $value, $cipher, $key, 0, $iv );
        return base64_encode( $iv . '::' . $encrypted );
    }

    public static function decrypt( $value ) {
        $key    = wp_salt( 'auth' );
        $cipher = 'aes-256-cbc';
        $data   = base64_decode( $value, true );

        if ( false === $data || false === strpos( $data, '::' ) ) {
            return '';
        }

        list( $iv, $encrypted ) = explode( '::', $data, 2 );
        $decrypted = openssl_decrypt( $encrypted, $cipher, $key, 0, $iv );

        return $decrypted ?: '';
    }

    /**
     * Call the backend /license/activate endpoint after saving a new license key.
     *
     * @param string $license_key Plaintext license key.
     * @param string $backend_url Base backend URL (e.g. http://localhost:3100/api/v1).
     * @return array{ type: string, message: string }
     */
    private static function activate_site( string $license_key, string $backend_url ): array {
        $site_id = md5( get_site_url() );
        $domain  = parse_url( get_site_url(), PHP_URL_HOST );

        $response = wp_remote_post(
            rtrim( $backend_url, '/' ) . '/license/activate',
            [
                'body'    => wp_json_encode( [
                    'license_key' => $license_key,
                    'site_id'     => $site_id,
                    'domain'      => $domain,
                ] ),
                'headers' => [ 'Content-Type' => 'application/json' ],
                'timeout' => 10,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [
                'type'    => 'error',
                'message' => sprintf( __( 'Could not reach the Wally server: %s', 'wally' ), $response->get_error_message() ),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! ( $body['valid'] ?? false ) ) {
            $error_map = [
                'max_sites_reached'  => sprintf(
                    __( 'Max sites reached (%d/%d). Deactivate another site from your dashboard first.', 'wally' ),
                    $body['site_count'] ?? '?',
                    $body['max_sites'] ?? '?'
                ),
                'license_expired'    => __( 'Your license has expired. Please renew it from your dashboard.', 'wally' ),
                'license_cancelled'  => __( 'Your license has been cancelled.', 'wally' ),
                'invalid_key'        => __( 'Invalid license key. Please check and try again.', 'wally' ),
            ];
            $err_code = $body['error'] ?? '';
            return [
                'type'    => 'error',
                'message' => $error_map[ $err_code ] ?? __( 'Activation failed. Please try again.', 'wally' ),
            ];
        }

        $tier      = ucfirst( $body['tier'] ?? 'free' );
        $count     = $body['site_count'] ?? 1;
        $max       = $body['max_sites'] ?? '?';

        return [
            'type'    => 'success',
            'message' => sprintf( __( 'Wally activated! Plan: %1$s. Active sites: %2$s/%3$s.', 'wally' ), $tier, $count, $max ),
        ];
    }
}
