<?php
namespace Wally;

class Settings {
    public static function register_menu() {
        add_menu_page(
            'Wally — AI Assistant',
            'Wally — AI Assistant',
            'manage_options',
            'wally',
            [ self::class, 'render_page' ],
            'dashicons-format-chat',
            2
        );
    }

    public static function render_page() {
        $is_admin = current_user_can( 'manage_options' );

        if ( $is_admin && isset( $_POST['wally_save'] ) && check_admin_referer( 'wally_settings' ) ) {
            $license_key = sanitize_text_field( $_POST['wally_license_key'] ?? '' );
            if ( $license_key && $license_key !== '••••••••' ) {
                update_option( 'wally_license_key', self::encrypt( $license_key ) );
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

            $backend_url = esc_url_raw( trim( $_POST['wally_backend_url'] ?? 'http://localhost:3100/api/v1' ), [ 'http', 'https' ] );
            if ( $backend_url ) {
                update_option( 'wally_backend_url', $backend_url );
            }

            $api_key = sanitize_text_field( $_POST['wally_api_key'] ?? '' );
            if ( $api_key && $api_key !== '••••••••' ) {
                update_option( 'wally_api_key', self::encrypt( $api_key ) );
            }

            $model = sanitize_text_field( $_POST['wally_model'] ?? 'claude-haiku-4-5' );
            $valid_models = [ 'claude-haiku-4-5', 'claude-sonnet-4-5', 'claude-opus-4', 'gpt-4o', 'gpt-4o-mini' ];
            if ( in_array( $model, $valid_models, true ) ) {
                update_option( 'wally_model', $model );
            }

            $all_actions = Permissions::get_all_actions();
            $all_roles   = array_keys( Permissions::get_default_permissions() );
            $tool_perms  = [];

            foreach ( $all_roles as $role ) {
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
        $backend_url   = get_option( 'wally_backend_url', 'http://localhost:3100/api/v1' );
        $api_key_set   = (bool) get_option( 'wally_api_key', '' );
        $model         = get_option( 'wally_model', 'claude-haiku-4-5' );
        $site_profile  = SiteScanner::get_profile();

        $profile_display = [
            'Site URL'           => $site_profile['site_url'] ?? get_site_url(),
            'WordPress Version'  => $site_profile['wp_version'] ?? get_bloginfo( 'version' ),
            'Active Theme'       => $site_profile['theme'] ?? wp_get_theme()->get( 'Name' ),
            'Active Plugins'     => isset( $site_profile['active_plugins'] ) ? count( $site_profile['active_plugins'] ) . ' plugins' : '—',
            'PHP Version'        => $site_profile['php_version'] ?? PHP_VERSION,
        ];

        $last_scan = get_option( 'wally_last_site_scan' );
        $last_scan_display = $last_scan
            ? 'Last scanned: ' . date( 'M j, Y \a\t g:i A', strtotime( $last_scan ) )
            : 'Not yet scanned.';

        // Audit log recent items
        $audit_result = AuditLog::get_actions( [ 'per_page' => 5, 'page' => 1 ] );
        $audit_items  = $audit_result['items'] ?? [];

        $all_actions   = Permissions::get_all_actions();
        $defaults      = Permissions::get_default_permissions();
        $overrides     = get_option( 'wally_tool_permissions', [] );
        $action_labels = [
            'read'    => 'Read',
            'create'  => 'Create',
            'update'  => 'Update',
            'delete'  => 'Delete',
            'plugins' => 'Plugins',
            'site'    => 'Site Settings',
        ];
        $action_descs = [
            'read'    => 'View & search content, settings, and plugins',
            'create'  => 'Create new posts, pages, and taxonomy terms',
            'update'  => 'Edit existing content and Elementor pages',
            'delete'  => 'Trash or permanently delete content',
            'plugins' => 'Install, activate, deactivate, and update plugins',
            'site'    => 'Modify WordPress settings and options',
        ];

        $conv_url      = admin_url( 'admin.php?page=wpaia-conversations' );
        $audit_url     = admin_url( 'admin.php?page=wpaia-audit-log' );
        $settings_url  = admin_url( 'admin.php?page=wally' );

        self::render_styles();
        ?>

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
                    <button type="submit" name="wally_save" class="wpaia-btn-primary">Save Settings</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Body -->
            <div class="wpaia-body">

                <!-- Sidebar -->
                <nav class="wpaia-sidebar">
                    <a href="#section-general" class="wpaia-nav-item wpaia-nav-active">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        General
                    </a>
                    <a href="#section-permissions" class="wpaia-nav-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Permissions
                    </a>
                    <a href="#section-site-profile" class="wpaia-nav-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        Site Profile
                    </a>
                    <a href="#section-audit-log" class="wpaia-nav-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Audit Log
                    </a>
                    <a href="<?php echo esc_url( $conv_url ); ?>" class="wpaia-nav-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Conversations
                    </a>
                </nav>

                <!-- Main Content -->
                <main class="wpaia-main">

                    <?php if ( ! $is_admin ) : ?>
                    <div class="wpaia-notice-readonly">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Settings can only be changed by administrators.
                    </div>
                    <fieldset disabled style="border:none;padding:0;margin:0;">
                    <?php endif; ?>

                    <!-- ─── General ─────────────────────────────────── -->
                    <section id="section-general" class="wpaia-section">
                        <div class="wpaia-section-header">
                            <h1 class="wpaia-section-title">General</h1>
                            <p class="wpaia-section-desc">Configure your AI assistant's activation, usage limits, and behavior.</p>
                        </div>

                        <!-- Activation Card -->
                        <div class="wpaia-card">
                            <div class="wpaia-card-title">Activation</div>
                            <p class="wpaia-card-desc">Your license key connects this plugin to the Wally service.</p>

                            <div class="wpaia-field">
                                <label class="wpaia-label">License Key</label>
                                <input type="password" name="wally_license_key" class="wpaia-input"
                                       value="<?php echo esc_attr( $license_key ); ?>"
                                       placeholder="Enter your license key" autocomplete="off" />
                                <span class="wpaia-hint">Stored encrypted. Find your key in your account dashboard.</span>
                            </div>
                        </div>

                        <!-- API Connection Card -->
                        <div class="wpaia-card">
                            <div class="wpaia-card-title">API Connection</div>
                            <p class="wpaia-card-desc">Configure the backend orchestration server that handles AI requests.</p>

                            <div class="wpaia-field">
                                <label class="wpaia-label">Backend URL</label>
                                <input type="url" name="wally_backend_url" class="wpaia-input"
                                       value="<?php echo esc_attr( $backend_url ); ?>"
                                       placeholder="http://localhost:3100/api/v1" />
                                <span class="wpaia-hint">The URL of the Wally backend server.</span>
                            </div>

                            <div class="wpaia-field">
                                <label class="wpaia-label">API Key</label>
                                <input type="password" name="wally_api_key" class="wpaia-input"
                                       value="<?php echo esc_attr( $api_key_set ? '••••••••' : '' ); ?>"
                                       placeholder="Enter your API key" autocomplete="off" />
                                <span class="wpaia-hint">Authentication key for the backend server. Stored encrypted.</span>
                            </div>

                            <div class="wpaia-field">
                                <label class="wpaia-label">AI Model</label>
                                <select name="wally_model" class="wpaia-select">
                                    <option value="claude-haiku-4-5" <?php selected( $model, 'claude-haiku-4-5' ); ?>>Claude Haiku 4.5 (Fast)</option>
                                    <option value="claude-sonnet-4-5" <?php selected( $model, 'claude-sonnet-4-5' ); ?>>Claude Sonnet 4.5 (Balanced)</option>
                                    <option value="claude-opus-4" <?php selected( $model, 'claude-opus-4' ); ?>>Claude Opus 4 (Advanced)</option>
                                    <option value="gpt-4o" <?php selected( $model, 'gpt-4o' ); ?>>GPT-4o</option>
                                    <option value="gpt-4o-mini" <?php selected( $model, 'gpt-4o-mini' ); ?>>GPT-4o Mini (Fast)</option>
                                </select>
                                <span class="wpaia-hint">The AI model used for generating responses.</span>
                            </div>
                        </div>

                        <!-- Usage Limits Card -->
                        <div class="wpaia-card">
                            <div class="wpaia-card-title">Usage Limits</div>
                            <p class="wpaia-card-desc">Control how much the AI assistant can be used on your site.</p>

                            <div class="wpaia-row2">
                                <div class="wpaia-field">
                                    <label class="wpaia-label">Rate Limit</label>
                                    <input type="number" name="wally_rate_limit" class="wpaia-input"
                                           value="<?php echo esc_attr( $rate_limit ); ?>"
                                           min="1" max="1000" />
                                    <span class="wpaia-hint">Maximum messages per user per day.</span>
                                </div>
                                <div class="wpaia-field">
                                    <label class="wpaia-label">Monthly Token Budget</label>
                                    <input type="number" name="wally_monthly_token_budget" class="wpaia-input"
                                           value="<?php echo esc_attr( $token_budget ); ?>"
                                           min="0" max="100000000" step="1000" />
                                    <span class="wpaia-hint">Total tokens per month. 0 = unlimited.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Behavior Card -->
                        <div class="wpaia-card">
                            <div class="wpaia-card-title">Behavior</div>

                            <div class="wpaia-field">
                                <label class="wpaia-label">Custom System Prompt</label>
                                <textarea name="wally_custom_prompt" class="wpaia-textarea"
                                          rows="5"
                                          placeholder="e.g. You are a helpful assistant for Acme Corp. Always respond in a professional tone."
                                ><?php echo esc_textarea( $custom_prompt ); ?></textarea>
                                <span class="wpaia-hint">Appended to the AI system prompt. Use for branding, tone, or site-specific context.</span>
                            </div>

                            <div class="wpaia-divider"></div>

                            <div class="wpaia-field">
                                <label class="wpaia-label">Data Retention</label>
                                <select name="wally_data_retention" class="wpaia-select">
                                    <option value="30"  <?php selected( $data_retention, 30 ); ?>>30 days</option>
                                    <option value="90"  <?php selected( $data_retention, 90 ); ?>>90 days</option>
                                    <option value="180" <?php selected( $data_retention, 180 ); ?>>180 days</option>
                                    <option value="365" <?php selected( $data_retention, 365 ); ?>>1 year</option>
                                    <option value="0"   <?php selected( $data_retention, 0 ); ?>>Forever</option>
                                </select>
                                <span class="wpaia-hint">How long conversation history is stored before automatic deletion.</span>
                            </div>

                            <div class="wpaia-divider"></div>

                            <div class="wpaia-toggle-row">
                                <div class="wpaia-toggle-info">
                                    <div class="wpaia-toggle-label">Confirm destructive actions</div>
                                    <div class="wpaia-toggle-desc">Require confirmation before executing delete, update, or deactivate operations</div>
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
                                    <div class="wpaia-toggle-label">Stream responses</div>
                                    <div class="wpaia-toggle-desc">Display AI responses in real-time as they are generated</div>
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
                                    <div class="wpaia-toggle-label">Notification sounds</div>
                                    <div class="wpaia-toggle-desc">Play a sound when the assistant completes a response</div>
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
                            <h2 class="wpaia-section-title">Permissions</h2>
                            <p class="wpaia-section-desc">Control which roles can access the chat and what actions they are allowed to perform.</p>
                        </div>

                        <!-- Chat Access Card -->
                        <div class="wpaia-card">
                            <div class="wpaia-card-title">Chat Access</div>
                            <p class="wpaia-card-desc">Choose which WordPress user roles can open and use the AI chat interface.</p>

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
                                        <span class="wpaia-role-locked-badge">Always enabled</span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Action Permissions Card -->
                        <div class="wpaia-card">
                            <div class="wpaia-card-title">Action Permissions</div>
                            <p class="wpaia-card-desc">Define which action types each role can perform through the AI assistant.</p>

                            <div class="wpaia-table-wrap">
                                <table class="wpaia-table">
                                    <thead>
                                        <tr>
                                            <th class="wpaia-th-role">Role</th>
                                            <?php foreach ( $all_actions as $action ) : ?>
                                                <th class="wpaia-th-center" title="<?php echo esc_attr( $action_descs[ $action ] ?? '' ); ?>">
                                                    <?php echo esc_html( $action_labels[ $action ] ?? ucfirst( $action ) ); ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $defaults as $role => $default_actions ) :
                                            $active_actions = ( is_array( $overrides ) && isset( $overrides[ $role ] ) )
                                                ? $overrides[ $role ]
                                                : $default_actions;
                                            $is_admin_role = $role === 'administrator';
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
                            <h2 class="wpaia-section-title">Site Profile</h2>
                            <p class="wpaia-section-desc">Your site profile is shared with the AI to provide context about your WordPress installation.</p>
                        </div>

                        <div class="wpaia-card">
                            <div class="wpaia-card-row-header">
                                <div class="wpaia-card-title">Site Information</div>
                                <button type="button" class="wpaia-btn-outline" onclick="wpaiaRescan(this)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                                    Rescan Site
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
                            <h2 class="wpaia-section-title">Audit Log</h2>
                            <p class="wpaia-section-desc">Track all actions performed by the AI assistant, including tool executions and content changes.</p>
                        </div>

                        <div class="wpaia-card">
                            <div class="wpaia-card-row-header">
                                <div class="wpaia-card-title">Recent Activity</div>
                                <a href="<?php echo esc_url( $audit_url ); ?>" class="wpaia-btn-outline">
                                    View All
                                </a>
                            </div>

                            <div class="wpaia-table-wrap">
                                <table class="wpaia-table">
                                    <thead>
                                        <tr>
                                            <th style="width:140px;">Time</th>
                                            <th>Action</th>
                                            <th style="width:120px;">Tool</th>
                                            <th style="width:80px;text-align:center;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ( empty( $audit_items ) ) : ?>
                                        <tr>
                                            <td colspan="4" style="padding:20px 16px;color:#A1A1AA;font-size:13px;text-align:center;">No activity yet.</td>
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
                        <span class="wpaia-footer-text">Wally v<?php echo esc_html( WALLY_VERSION ); ?></span>
                        <?php if ( $is_admin ) : ?>
                        <button type="submit" name="wally_save" class="wpaia-btn-primary">Save Settings</button>
                        <?php endif; ?>
                    </div>

                </main>
            </div>
        </div>
        </form>

        <script>
        function wpaiaRescan(btn) {
            btn.disabled = true;
            btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:wpaia-spin 1s linear infinite"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg> Scanning…';
            fetch(wpaiaData.restUrl + 'site-profile/rescan', {
                method: 'POST',
                headers: { 'X-WP-Nonce': wpaiaData.nonce }
            }).then(function() {
                location.reload();
            }).catch(function() {
                btn.disabled = false;
                btn.innerHTML = 'Rescan Site';
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
        .wpaia-perm-toggle input { position: absolute; opacity: 0; width: 0; height: 0; }
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
}
