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

        $last_sync = get_option( 'plugins_showcase_last_sync', '' );
        ?>
        <div class="wrap plugins-showcase-settings">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'plugins_showcase_settings' );
                do_settings_sections( 'plugins-showcase-settings' );
                submit_button();
                ?>
            </form>

            <hr>

            <h2><?php esc_html_e( 'Sync Actions', 'plugins-showcase' ); ?></h2>

            <?php if ( $last_sync ) : ?>
                <p>
                    <?php
                    printf(
                        esc_html__( 'Last sync: %s', 'plugins-showcase' ),
                        esc_html( $last_sync )
                    );
                    ?>
                </p>
            <?php endif; ?>

            <?php
            $next_sync = wp_next_scheduled( 'plugins_showcase_scheduled_sync' );
            if ( $next_sync ) :
                ?>
                <p>
                    <?php
                    printf(
                        esc_html__( 'Next scheduled sync: %s', 'plugins-showcase' ),
                        esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_sync ), 'Y-m-d H:i:s' ) )
                    );
                    ?>
                </p>
            <?php endif; ?>

            <div class="sync-actions">
                <button type="button" id="sync-now" class="button button-primary">
                    <?php esc_html_e( 'Sync Now', 'plugins-showcase' ); ?>
                </button>
                <button type="button" id="delete-all" class="button button-secondary">
                    <?php esc_html_e( 'Delete All Plugins', 'plugins-showcase' ); ?>
                </button>
                <span id="sync-status"></span>
            </div>

            <div id="sync-results" style="display: none; margin-top: 20px;">
                <h3><?php esc_html_e( 'Sync Results', 'plugins-showcase' ); ?></h3>
                <ul>
                    <li><?php esc_html_e( 'Synced:', 'plugins-showcase' ); ?> <span id="synced-count">0</span></li>
                    <li><?php esc_html_e( 'Failed:', 'plugins-showcase' ); ?> <span id="failed-count">0</span></li>
                    <li><?php esc_html_e( 'Skipped:', 'plugins-showcase' ); ?> <span id="skipped-count">0</span></li>
                    <li><?php esc_html_e( 'Total:', 'plugins-showcase' ); ?> <span id="total-count">0</span></li>
                </ul>
            </div>
        </div>
        <?php
    }
}
