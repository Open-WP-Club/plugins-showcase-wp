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
        add_action( 'wp_ajax_plugins_showcase_test_token', array( $this, 'ajax_test_token' ) );
        add_action( 'wp_ajax_plugins_showcase_get_rate_limit', array( $this, 'ajax_get_rate_limit' ) );

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
        $repo_data['topics'] = $this->github_api->get_topics( $org, $repo_name );

        // Get README
        $readme = $this->github_api->get_readme( $org, $repo_name );

        // Get latest release
        $repo_data['latest_release'] = $this->github_api->get_latest_release( $org, $repo_name );

        // Get contributors
        $repo_data['contributors'] = $this->github_api->get_contributors( $org, $repo_name, 10 );

        // Get screenshots
        $repo_data['screenshots'] = $this->github_api->get_screenshots( $org, $repo_name );

        // Get requirements from composer.json or plugin header
        $composer = $this->github_api->get_composer_json( $org, $repo_name );
        $plugin_header = $this->github_api->get_plugin_header( $org, $repo_name );

        $repo_data['requirements'] = array_merge(
            $composer ?? array(),
            $plugin_header ?? array()
        );

        // Create or update post
        return Plugins_Showcase_Post_Type::create_or_update( $repo_data, $readme );
    }

    /**
     * AJAX: Test GitHub token
     */
    public function ajax_test_token() {
        check_ajax_referer( 'plugins_showcase_admin', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'plugins-showcase' ) ) );
        }

        $token = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';

        if ( empty( $token ) ) {
            wp_send_json_error( array( 'message' => __( 'No token provided', 'plugins-showcase' ) ) );
        }

        // Test the token by making a request to GitHub API
        $response = wp_remote_get( 'https://api.github.com/user', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/vnd.github.v3+json',
                'User-Agent'    => 'WordPress/Plugins-Showcase',
            ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 ) {
            $rate_response = wp_remote_get( 'https://api.github.com/rate_limit', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/vnd.github.v3+json',
                    'User-Agent'    => 'WordPress/Plugins-Showcase',
                ),
            ) );

            $rate_limit = 5000;
            $rate_remaining = 5000;

            if ( ! is_wp_error( $rate_response ) ) {
                $rate_body = json_decode( wp_remote_retrieve_body( $rate_response ), true );
                if ( isset( $rate_body['rate'] ) ) {
                    $rate_limit = $rate_body['rate']['limit'];
                    $rate_remaining = $rate_body['rate']['remaining'];
                }
            }

            wp_send_json_success( array(
                'message'   => sprintf(
                    __( 'Token valid! Authenticated as %s. Rate limit: %d/%d requests remaining.', 'plugins-showcase' ),
                    $body['login'],
                    $rate_remaining,
                    $rate_limit
                ),
                'user'      => $body['login'],
                'rate'      => $rate_remaining . '/' . $rate_limit,
            ) );
        } elseif ( $code === 401 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid token. Please check and try again.', 'plugins-showcase' ) ) );
        } else {
            wp_send_json_error( array(
                'message' => sprintf( __( 'GitHub API error: %s', 'plugins-showcase' ), $body['message'] ?? 'Unknown error' ),
            ) );
        }
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

    /**
     * AJAX: Get GitHub API rate limit
     */
    public function ajax_get_rate_limit() {
        check_ajax_referer( 'plugins_showcase_admin', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'plugins-showcase' ) ) );
        }

        // Fetch fresh rate limit from GitHub
        $rate_limit = $this->github_api->fetch_rate_limit();

        wp_send_json_success( $rate_limit );
    }
}

// Initialize
Plugins_Showcase_Sync::get_instance();
