<?php

/**
 * Plugin Name: Plugins Showcase
 * Plugin URI: https://github.com/Open-WP-Club/plugins-showcase-wp
 * Description: Display GitHub organization repositories as plugin showcase pages with search and Gutenberg blocks.
 * Version: 1.1.0
 * Author: OpenWPClub.com
 * Author URI: https://OpenWPClub.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: plugins-showcase
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PLUGINS_SHOWCASE_VERSION', '1.1.0' );
define( 'PLUGINS_SHOWCASE_PATH', plugin_dir_path( __FILE__ ) );
define( 'PLUGINS_SHOWCASE_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once PLUGINS_SHOWCASE_PATH . 'includes/github-api.php';
require_once PLUGINS_SHOWCASE_PATH . 'includes/post-type.php';
require_once PLUGINS_SHOWCASE_PATH . 'includes/sync.php';
require_once PLUGINS_SHOWCASE_PATH . 'includes/blocks.php';
require_once PLUGINS_SHOWCASE_PATH . 'admin/admin.php';

/**
 * Main Plugin Class
 */
class Plugins_Showcase {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

        // Initialize components
        Plugins_Showcase_Post_Type::get_instance();
        Plugins_Showcase_Blocks::get_instance();

        if ( is_admin() ) {
            Plugins_Showcase_Admin::get_instance();
        }
    }

    public function init() {
        load_plugin_textdomain( 'plugins-showcase', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'plugins-showcase-frontend',
            PLUGINS_SHOWCASE_URL . 'assets/css/frontend.css',
            array(),
            PLUGINS_SHOWCASE_VERSION
        );

        wp_enqueue_script(
            'plugins-showcase-frontend',
            PLUGINS_SHOWCASE_URL . 'assets/js/frontend.js',
            array(),
            PLUGINS_SHOWCASE_VERSION,
            true
        );

        wp_localize_script( 'plugins-showcase-frontend', 'pluginsShowcase', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'plugins_showcase_nonce' ),
            'restUrl' => rest_url( 'plugins-showcase/v1/' ),
        ) );
    }
}

// Initialize plugin
add_action( 'plugins_loaded', array( 'Plugins_Showcase', 'get_instance' ) );

// Activation hook
register_activation_hook( __FILE__, function() {
    Plugins_Showcase_Post_Type::get_instance()->register_post_type();
    flush_rewrite_rules();
} );

// Deactivation hook
register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
} );
