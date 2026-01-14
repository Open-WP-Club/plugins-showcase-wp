<?php
/**
 * GitHub Sync Handler
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugins_Showcase_Sync {

    private static $instance = null;
    private $github_api;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->github_api = new Plugins_Showcase_GitHub_API();

        // AJAX handlers
        add_action( 'wp_ajax_plugins_showcase_sync', array( $this, 'ajax_sync' ) );
        add_action( 'wp_ajax_plugins_showcase_sync_single', array( $this, 'ajax_sync_single' ) );
        add_action( 'wp_ajax_plugins_showcase_delete_all', array( $this, 'ajax_delete_all' ) );

        // Cron
        add_action( 'plugins_showcase_scheduled_sync', array( $this, 'cron_sync' ) );
        add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );

        // Schedule cron on settings save
        add_action( 'update_option_plugins_showcase_sync_frequency', array( $this, 'schedule_cron' ), 10, 2 );
    }

    /**
     * Add custom cron intervals
     */
    public function add_cron_intervals( $schedules ) {
        $schedules['weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display'  => __( 'Once Weekly', 'plugins-showcase' ),
        );
        $schedules['monthly'] = array(
            'interval' => MONTH_IN_SECONDS,
            'display'  => __( 'Once Monthly', 'plugins-showcase' ),
        );
        return $schedules;
    }

    /**
     * Schedule or unschedule cron based on frequency setting
     */
    public function schedule_cron( $old_value, $new_value ) {
        // Clear any existing scheduled event
        $timestamp = wp_next_scheduled( 'plugins_showcase_scheduled_sync' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'plugins_showcase_scheduled_sync' );
        }

        // Schedule new event if not disabled
        if ( $new_value && $new_value !== 'disabled' ) {
            wp_schedule_event( time(), $new_value, 'plugins_showcase_scheduled_sync' );
        }
    }

    /**
     * Cron sync handler
     */
    public function cron_sync() {
        $this->sync_all();
    }

    /**
     * AJAX: Sync all repositories
     */
    public function ajax_sync() {
        check_ajax_referer( 'plugins_showcase_admin', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'plugins-showcase' ) ) );
        }

        $result = $this->sync_all();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX: Sync single repository
     */
    public function ajax_sync_single() {
        check_ajax_referer( 'plugins_showcase_admin', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'plugins-showcase' ) ) );
        }

        $repo = isset( $_POST['repo'] ) ? sanitize_text_field( $_POST['repo'] ) : '';

        if ( empty( $repo ) ) {
            wp_send_json_error( array( 'message' => __( 'No repository specified', 'plugins-showcase' ) ) );
        }

        $org = get_option( 'plugins_showcase_github_org', '' );
        $result = $this->sync_repository( $org, $repo );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array(
            'message' => __( 'Repository synced successfully', 'plugins-showcase' ),
            'post_id' => $result,
        ) );
    }

    /**
     * Sync all repositories from organization
     */
    public function sync_all() {
        $org = get_option( 'plugins_showcase_github_org', '' );

        if ( empty( $org ) ) {
            return new WP_Error( 'no_org', __( 'No GitHub organization configured', 'plugins-showcase' ) );
        }

        $repos = $this->github_api->get_repositories( $org );

        if ( is_wp_error( $repos ) ) {
            return $repos;
        }

        $synced = 0;
        $failed = 0;
        $skipped = 0;

        foreach ( $repos as $repo ) {
            // Skip forks if configured
            if ( get_option( 'plugins_showcase_skip_forks', true ) && $repo['fork'] ) {
                $skipped++;
                continue;
            }

            // Skip archived repos if configured
            if ( get_option( 'plugins_showcase_skip_archived', true ) && $repo['archived'] ) {
                $skipped++;
                continue;
            }

            $result = $this->sync_repository( $org, $repo['name'], $repo );

            if ( is_wp_error( $result ) ) {
                $failed++;
            } else {
                $synced++;
            }
        }

        // Update last sync time
        update_option( 'plugins_showcase_last_sync', current_time( 'mysql' ) );

        return array(
            'synced'  => $synced,
            'failed'  => $failed,
            'skipped' => $skipped,
            'total'   => count( $repos ),
        );
    }

    /**
     * Sync single repository
     */
    public function sync_repository( $org, $repo_name, $repo_data = null ) {
        if ( null === $repo_data ) {
            $repo_data = $this->github_api->get_repository( $org, $repo_name );

            if ( is_wp_error( $repo_data ) ) {
                return $repo_data;
            }
        }

        // Get topics
        $topics = $this->github_api->get_topics( $org, $repo_name );
        $repo_data['topics'] = $topics;

        // Get README
        $readme = $this->github_api->get_readme( $org, $repo_name );

        // Create or update post
        return Plugins_Showcase_Post_Type::create_or_update( $repo_data, $readme );
    }

    /**
     * AJAX: Delete all plugins
     */
    public function ajax_delete_all() {
        check_ajax_referer( 'plugins_showcase_admin', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'plugins-showcase' ) ) );
        }

        $count = self::delete_all();

        wp_send_json_success( array(
            'count'   => $count,
            'message' => sprintf( __( 'Deleted %d plugins', 'plugins-showcase' ), $count ),
        ) );
    }

    /**
     * Delete all synced plugins
     */
    public static function delete_all() {
        $posts = get_posts( array(
            'post_type'   => Plugins_Showcase_Post_Type::POST_TYPE,
            'numberposts' => -1,
            'post_status' => 'any',
        ) );

        foreach ( $posts as $post ) {
            wp_delete_post( $post->ID, true );
        }

        return count( $posts );
    }
}

// Initialize
Plugins_Showcase_Sync::get_instance();
