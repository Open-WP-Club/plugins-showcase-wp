<?php
/**
 * Admin Settings Page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugins_Showcase_Admin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Add settings page under Plugin Showcase menu
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=' . Plugins_Showcase_Post_Type::POST_TYPE,
            __( 'Settings', 'plugins-showcase' ),
            __( 'Settings', 'plugins-showcase' ),
            'manage_options',
            'plugins-showcase-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // GitHub Settings Section
        add_settings_section(
            'plugins_showcase_github',
            __( 'GitHub Settings', 'plugins-showcase' ),
            array( $this, 'github_section_callback' ),
            'plugins-showcase-settings'
        );

        // Organization
        register_setting( 'plugins_showcase_settings', 'plugins_showcase_github_org', array(
            'type'              => 'string',
            'sanitize_callback' => array( 'Plugins_Showcase_GitHub_API', 'parse_organization' ),
        ) );

        add_settings_field(
            'plugins_showcase_github_org',
            __( 'GitHub Organization', 'plugins-showcase' ),
            array( $this, 'render_org_field' ),
            'plugins-showcase-settings',
            'plugins_showcase_github'
        );

        // Token
        register_setting( 'plugins_showcase_settings', 'plugins_showcase_github_token', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        add_settings_field(
            'plugins_showcase_github_token',
            __( 'GitHub Token (Optional)', 'plugins-showcase' ),
            array( $this, 'render_token_field' ),
            'plugins-showcase-settings',
            'plugins_showcase_github'
        );

        // Sync Settings Section
        add_settings_section(
            'plugins_showcase_sync',
            __( 'Sync Settings', 'plugins-showcase' ),
            array( $this, 'sync_section_callback' ),
            'plugins-showcase-settings'
        );

        // Sync frequency
        register_setting( 'plugins_showcase_settings', 'plugins_showcase_sync_frequency', array(
            'type'              => 'string',
            'default'           => 'disabled',
            'sanitize_callback' => array( $this, 'sanitize_sync_frequency' ),
        ) );

        add_settings_field(
            'plugins_showcase_sync_frequency',
            __( 'Auto Sync', 'plugins-showcase' ),
            array( $this, 'render_sync_frequency_field' ),
            'plugins-showcase-settings',
            'plugins_showcase_sync'
        );

        // Skip forks
        register_setting( 'plugins_showcase_settings', 'plugins_showcase_skip_forks', array(
            'type'    => 'boolean',
            'default' => true,
        ) );

        add_settings_field(
            'plugins_showcase_skip_forks',
            __( 'Skip Forks', 'plugins-showcase' ),
            array( $this, 'render_skip_forks_field' ),
            'plugins-showcase-settings',
            'plugins_showcase_sync'
        );

        // Skip archived
        register_setting( 'plugins_showcase_settings', 'plugins_showcase_skip_archived', array(
            'type'    => 'boolean',
            'default' => true,
        ) );

        add_settings_field(
            'plugins_showcase_skip_archived',
            __( 'Skip Archived', 'plugins-showcase' ),
            array( $this, 'render_skip_archived_field' ),
            'plugins-showcase-settings',
            'plugins_showcase_sync'
        );

        // Display Settings Section
        add_settings_section(
            'plugins_showcase_display',
            __( 'Display Settings', 'plugins-showcase' ),
            array( $this, 'display_section_callback' ),
            'plugins-showcase-settings'
        );

        // Show categories
        register_setting( 'plugins_showcase_settings', 'plugins_showcase_show_categories', array(
            'type'    => 'boolean',
            'default' => true,
        ) );

        add_settings_field(
            'plugins_showcase_show_categories',
            __( 'Show Categories', 'plugins-showcase' ),
            array( $this, 'render_show_categories_field' ),
            'plugins-showcase-settings',
            'plugins_showcase_display'
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'plugins-showcase-settings' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'plugins-showcase-admin',
            PLUGINS_SHOWCASE_URL . 'assets/css/admin.css',
            array(),
            PLUGINS_SHOWCASE_VERSION
        );

        wp_enqueue_script(
            'plugins-showcase-admin',
            PLUGINS_SHOWCASE_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            PLUGINS_SHOWCASE_VERSION,
            true
        );

        wp_localize_script( 'plugins-showcase-admin', 'pluginsShowcaseAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'plugins_showcase_admin' ),
            'strings' => array(
                'syncing'      => __( 'Syncing...', 'plugins-showcase' ),
                'syncComplete' => __( 'Sync complete!', 'plugins-showcase' ),
                'syncError'    => __( 'Sync failed:', 'plugins-showcase' ),
                'confirm'      => __( 'Are you sure? This will delete all synced plugins.', 'plugins-showcase' ),
            ),
        ) );
    }

    /**
     * Section callbacks
     */
    public function github_section_callback() {
        echo '<p>' . esc_html__( 'Configure your GitHub organization settings.', 'plugins-showcase' ) . '</p>';
    }

    public function sync_section_callback() {
        echo '<p>' . esc_html__( 'Configure how repositories are synced.', 'plugins-showcase' ) . '</p>';
    }

    public function display_section_callback() {
        echo '<p>' . esc_html__( 'Configure how plugins are displayed on the frontend.', 'plugins-showcase' ) . '</p>';
    }

    /**
     * Field renderers
     */
    public function render_org_field() {
        $value = get_option( 'plugins_showcase_github_org', '' );
        ?>
        <input type="text"
               name="plugins_showcase_github_org"
               value="<?php echo esc_attr( $value ); ?>"
               class="regular-text"
               placeholder="https://github.com/your-org or just your-org">
        <p class="description">
            <?php esc_html_e( 'Enter the GitHub organization URL or name.', 'plugins-showcase' ); ?>
        </p>
        <?php
    }

    public function render_token_field() {
        $value = get_option( 'plugins_showcase_github_token', '' );
        ?>
        <input type="password"
               name="plugins_showcase_github_token"
               id="plugins_showcase_github_token"
               value="<?php echo esc_attr( $value ); ?>"
               class="regular-text">
        <button type="button" id="test-token" class="button button-secondary">
            <?php esc_html_e( 'Test Token', 'plugins-showcase' ); ?>
        </button>
        <span id="token-status"></span>
        <p class="description">
            <?php esc_html_e( 'Optional: Add a GitHub personal access token for higher rate limits and private repos.', 'plugins-showcase' ); ?>
        </p>
        <?php
    }

    public function render_sync_frequency_field() {
        $value = get_option( 'plugins_showcase_sync_frequency', 'disabled' );
        ?>
        <select name="plugins_showcase_sync_frequency">
            <option value="disabled" <?php selected( $value, 'disabled' ); ?>>
                <?php esc_html_e( 'Disabled', 'plugins-showcase' ); ?>
            </option>
            <option value="daily" <?php selected( $value, 'daily' ); ?>>
                <?php esc_html_e( 'Once per day', 'plugins-showcase' ); ?>
            </option>
            <option value="weekly" <?php selected( $value, 'weekly' ); ?>>
                <?php esc_html_e( 'Once per week', 'plugins-showcase' ); ?>
            </option>
            <option value="monthly" <?php selected( $value, 'monthly' ); ?>>
                <?php esc_html_e( 'Once per month', 'plugins-showcase' ); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e( 'How often to automatically sync repositories from GitHub.', 'plugins-showcase' ); ?>
        </p>
        <?php
    }

    public function sanitize_sync_frequency( $value ) {
        $valid = array( 'disabled', 'daily', 'weekly', 'monthly' );
        return in_array( $value, $valid, true ) ? $value : 'disabled';
    }

    public function render_skip_forks_field() {
        $value = get_option( 'plugins_showcase_skip_forks', true );
        ?>
        <label>
            <input type="checkbox"
                   name="plugins_showcase_skip_forks"
                   value="1"
                   <?php checked( $value, true ); ?>>
            <?php esc_html_e( 'Skip forked repositories during sync', 'plugins-showcase' ); ?>
        </label>
        <?php
    }

    public function render_skip_archived_field() {
        $value = get_option( 'plugins_showcase_skip_archived', true );
        ?>
        <label>
            <input type="checkbox"
                   name="plugins_showcase_skip_archived"
                   value="1"
                   <?php checked( $value, true ); ?>>
            <?php esc_html_e( 'Skip archived repositories during sync', 'plugins-showcase' ); ?>
        </label>
        <?php
    }

    public function render_show_categories_field() {
        $value = get_option( 'plugins_showcase_show_categories', true );
        ?>
        <label>
            <input type="checkbox"
                   name="plugins_showcase_show_categories"
                   value="1"
                   <?php checked( $value, true ); ?>>
            <?php esc_html_e( 'Show category tags on plugin pages (based on GitHub topics)', 'plugins-showcase' ); ?>
        </label>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $last_sync   = get_option( 'plugins_showcase_last_sync', '' );
        $github_org  = get_option( 'plugins_showcase_github_org', '' );
        $has_token   = ! empty( get_option( 'plugins_showcase_github_token', '' ) );
        $next_sync   = wp_next_scheduled( 'plugins_showcase_scheduled_sync' );
        $plugin_count = wp_count_posts( Plugins_Showcase_Post_Type::POST_TYPE );
        $published   = isset( $plugin_count->publish ) ? $plugin_count->publish : 0;
        $rate_limit  = Plugins_Showcase_GitHub_API::get_rate_limit();
        $remaining   = $rate_limit['remaining'] ?? 60;
        $limit       = $rate_limit['limit'] ?? 60;
        ?>
        <div class="wrap ps-admin">
            <h1><?php esc_html_e( 'Plugins Showcase', 'plugins-showcase' ); ?></h1>

            <!-- Quick Stats -->
            <div class="ps-quick-stats">
                <span><strong><?php echo esc_html( $published ); ?></strong> <?php esc_html_e( 'plugins', 'plugins-showcase' ); ?></span>
                <span class="ps-sep">•</span>
                <span><strong><?php echo esc_html( $remaining ); ?>/<?php echo esc_html( $limit ); ?></strong> <?php esc_html_e( 'API requests', 'plugins-showcase' ); ?></span>
                <?php if ( $last_sync ) : ?>
                <span class="ps-sep">•</span>
                <span><?php esc_html_e( 'Last sync:', 'plugins-showcase' ); ?> <strong><?php echo esc_html( human_time_diff( strtotime( $last_sync ) ) ); ?></strong> <?php esc_html_e( 'ago', 'plugins-showcase' ); ?></span>
                <?php endif; ?>
                <button type="button" id="refresh-rate-limit" class="ps-link-btn"><?php esc_html_e( 'Refresh', 'plugins-showcase' ); ?></button>
            </div>

            <!-- Sync Section -->
            <div class="ps-section">
                <h2><?php esc_html_e( 'Sync', 'plugins-showcase' ); ?></h2>
                <div class="ps-sync-row">
                    <button type="button" id="sync-now" class="button button-primary"><?php esc_html_e( 'Sync Now', 'plugins-showcase' ); ?></button>
                    <button type="button" id="delete-all" class="button"><?php esc_html_e( 'Delete All', 'plugins-showcase' ); ?></button>
                    <?php if ( $next_sync ) : ?>
                    <span class="ps-next-sync"><?php printf( esc_html__( 'Next: %s', 'plugins-showcase' ), esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_sync ), 'M j @ H:i' ) ) ); ?></span>
                    <?php endif; ?>
                </div>
                <div id="sync-progress" style="display: none; margin-top: 12px;">
                    <div class="ps-progress-bar"><div class="ps-progress-fill"></div></div>
                    <span class="ps-progress-text"><?php esc_html_e( 'Syncing...', 'plugins-showcase' ); ?></span>
                </div>
                <div id="sync-results" style="display: none; margin-top: 12px;" class="ps-results">
                    <span class="ps-result-success">✓ <strong id="synced-count">0</strong> <?php esc_html_e( 'synced', 'plugins-showcase' ); ?></span>
                    <span class="ps-result-skipped">○ <strong id="skipped-count">0</strong> <?php esc_html_e( 'skipped', 'plugins-showcase' ); ?></span>
                    <span class="ps-result-failed">✗ <strong id="failed-count">0</strong> <?php esc_html_e( 'failed', 'plugins-showcase' ); ?></span>
                    <span class="ps-result-total">= <strong id="total-count">0</strong> <?php esc_html_e( 'total', 'plugins-showcase' ); ?></span>
                </div>
            </div>

            <!-- Settings Form -->
            <form method="post" action="options.php">
                <?php settings_fields( 'plugins_showcase_settings' ); ?>

                <div class="ps-section">
                    <h2><?php esc_html_e( 'GitHub', 'plugins-showcase' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="plugins_showcase_github_org"><?php esc_html_e( 'Organization', 'plugins-showcase' ); ?></label></th>
                            <td>
                                <input type="text" name="plugins_showcase_github_org" id="plugins_showcase_github_org"
                                       value="<?php echo esc_attr( $github_org ); ?>" class="regular-text" placeholder="your-org-name">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="plugins_showcase_github_token"><?php esc_html_e( 'Token', 'plugins-showcase' ); ?></label></th>
                            <td>
                                <input type="password" name="plugins_showcase_github_token" id="plugins_showcase_github_token"
                                       value="<?php echo esc_attr( get_option( 'plugins_showcase_github_token', '' ) ); ?>" class="regular-text" placeholder="ghp_xxxx">
                                <button type="button" id="test-token" class="button"><?php esc_html_e( 'Test', 'plugins-showcase' ); ?></button>
                                <span id="token-status"></span>
                                <p class="description"><?php esc_html_e( 'Optional. Increases rate limit to 5000/hour.', 'plugins-showcase' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="ps-section">
                    <h2><?php esc_html_e( 'Options', 'plugins-showcase' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Auto Sync', 'plugins-showcase' ); ?></th>
                            <td>
                                <select name="plugins_showcase_sync_frequency">
                                    <?php $freq = get_option( 'plugins_showcase_sync_frequency', 'disabled' ); ?>
                                    <option value="disabled" <?php selected( $freq, 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'plugins-showcase' ); ?></option>
                                    <option value="daily" <?php selected( $freq, 'daily' ); ?>><?php esc_html_e( 'Daily', 'plugins-showcase' ); ?></option>
                                    <option value="weekly" <?php selected( $freq, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'plugins-showcase' ); ?></option>
                                    <option value="monthly" <?php selected( $freq, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'plugins-showcase' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Skip', 'plugins-showcase' ); ?></th>
                            <td>
                                <label><input type="checkbox" name="plugins_showcase_skip_forks" value="1" <?php checked( get_option( 'plugins_showcase_skip_forks', true ) ); ?>> <?php esc_html_e( 'Forks', 'plugins-showcase' ); ?></label>
                                &nbsp;&nbsp;
                                <label><input type="checkbox" name="plugins_showcase_skip_archived" value="1" <?php checked( get_option( 'plugins_showcase_skip_archived', true ) ); ?>> <?php esc_html_e( 'Archived', 'plugins-showcase' ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Display', 'plugins-showcase' ); ?></th>
                            <td>
                                <label><input type="checkbox" name="plugins_showcase_show_categories" value="1" <?php checked( get_option( 'plugins_showcase_show_categories', true ) ); ?>> <?php esc_html_e( 'Show categories', 'plugins-showcase' ); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
