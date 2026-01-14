<?php
/**
 * Custom Post Type for Plugin Showcase
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugins_Showcase_Post_Type {

    private static $instance = null;
    const POST_TYPE = 'showcase_plugin';
    const TAXONOMY = 'plugin_category';

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_taxonomy' ) );
        add_filter( 'single_template', array( $this, 'load_single_template' ) );
        add_filter( 'archive_template', array( $this, 'load_archive_template' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_filter( 'the_content', array( $this, 'filter_plugin_content' ), 20 );
    }

    /**
     * Show README content for plugin posts
     */
    public function filter_plugin_content( $content ) {
        if ( ! is_singular( self::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        // If post content is empty, show README from meta
        if ( empty( trim( strip_tags( $content ) ) ) ) {
            $readme = get_post_meta( get_the_ID(), '_github_readme', true );
            if ( ! empty( $readme ) ) {
                return '<div class="plugins-showcase-readme">' . $readme . '</div>';
            }
        }

        return $content;
    }

    /**
     * Register Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Plugins', 'plugins-showcase' ),
            'singular_name'      => __( 'Plugin', 'plugins-showcase' ),
            'menu_name'          => __( 'Plugin Showcase', 'plugins-showcase' ),
            'add_new'            => __( 'Add New', 'plugins-showcase' ),
            'add_new_item'       => __( 'Add New Plugin', 'plugins-showcase' ),
            'edit_item'          => __( 'Edit Plugin', 'plugins-showcase' ),
            'new_item'           => __( 'New Plugin', 'plugins-showcase' ),
            'view_item'          => __( 'View Plugin', 'plugins-showcase' ),
            'search_items'       => __( 'Search Plugins', 'plugins-showcase' ),
            'not_found'          => __( 'No plugins found', 'plugins-showcase' ),
            'not_found_in_trash' => __( 'No plugins found in Trash', 'plugins-showcase' ),
            'all_items'          => __( 'All Plugins', 'plugins-showcase' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'plugins' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-plugins-checked',
            'show_in_rest'       => true,
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Register Taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name'              => __( 'Plugin Categories', 'plugins-showcase' ),
            'singular_name'     => __( 'Plugin Category', 'plugins-showcase' ),
            'search_items'      => __( 'Search Categories', 'plugins-showcase' ),
            'all_items'         => __( 'All Categories', 'plugins-showcase' ),
            'parent_item'       => __( 'Parent Category', 'plugins-showcase' ),
            'parent_item_colon' => __( 'Parent Category:', 'plugins-showcase' ),
            'edit_item'         => __( 'Edit Category', 'plugins-showcase' ),
            'update_item'       => __( 'Update Category', 'plugins-showcase' ),
            'add_new_item'      => __( 'Add New Category', 'plugins-showcase' ),
            'new_item_name'     => __( 'New Category Name', 'plugins-showcase' ),
            'menu_name'         => __( 'Categories', 'plugins-showcase' ),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'plugin-category' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( self::TAXONOMY, self::POST_TYPE, $args );
    }

    /**
     * Load custom single template
     */
    public function load_single_template( $template ) {
        global $post;

        if ( $post->post_type === self::POST_TYPE ) {
            $custom_template = PLUGINS_SHOWCASE_PATH . 'templates/single-plugin.php';
            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Load custom archive template
     */
    public function load_archive_template( $template ) {
        if ( is_post_type_archive( self::POST_TYPE ) ) {
            $custom_template = PLUGINS_SHOWCASE_PATH . 'templates/archive-plugins.php';
            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route( 'plugins-showcase/v1', '/plugins', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_plugins' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'search'   => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'category' => array(
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'per_page' => array(
                    'type'              => 'integer',
                    'default'           => 12,
                    'sanitize_callback' => 'absint',
                ),
                'page'     => array(
                    'type'              => 'integer',
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ) );

        register_rest_route( 'plugins-showcase/v1', '/plugin/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_plugin' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST API: Get plugins
     */
    public function rest_get_plugins( $request ) {
        $args = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => $request->get_param( 'per_page' ),
            'paged'          => $request->get_param( 'page' ),
            'post_status'    => 'publish',
        );

        $search = $request->get_param( 'search' );
        if ( ! empty( $search ) ) {
            $args['s'] = $search;
        }

        $category = $request->get_param( 'category' );
        if ( ! empty( $category ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => self::TAXONOMY,
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }

        $query = new WP_Query( $args );
        $plugins = array();

        foreach ( $query->posts as $post ) {
            $plugins[] = $this->format_plugin_data( $post );
        }

        return new WP_REST_Response( array(
            'plugins'     => $plugins,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
        ), 200 );
    }

    /**
     * REST API: Get single plugin
     */
    public function rest_get_plugin( $request ) {
        $post = get_post( $request->get_param( 'id' ) );

        if ( ! $post || $post->post_type !== self::POST_TYPE ) {
            return new WP_Error( 'not_found', __( 'Plugin not found', 'plugins-showcase' ), array( 'status' => 404 ) );
        }

        return new WP_REST_Response( $this->format_plugin_data( $post, true ), 200 );
    }

    /**
     * Format plugin data for REST response
     */
    private function format_plugin_data( $post, $full = false ) {
        $data = array(
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'slug'        => $post->post_name,
            'excerpt'     => get_the_excerpt( $post ),
            'permalink'   => get_permalink( $post ),
            'thumbnail'   => get_the_post_thumbnail_url( $post, 'medium' ),
            'github_url'  => get_post_meta( $post->ID, '_github_url', true ),
            'stars'       => get_post_meta( $post->ID, '_github_stars', true ),
            'forks'       => get_post_meta( $post->ID, '_github_forks', true ),
            'language'    => get_post_meta( $post->ID, '_github_language', true ),
            'updated_at'  => get_post_meta( $post->ID, '_github_updated', true ),
            'categories'  => wp_get_post_terms( $post->ID, self::TAXONOMY, array( 'fields' => 'names' ) ),
        );

        if ( $full ) {
            $data['content'] = apply_filters( 'the_content', $post->post_content );
            $data['readme']  = get_post_meta( $post->ID, '_github_readme', true );
        }

        return $data;
    }

    /**
     * Get plugin by GitHub repo name
     */
    public static function get_by_repo( $repo_name ) {
        $posts = get_posts( array(
            'post_type'   => self::POST_TYPE,
            'meta_key'    => '_github_repo',
            'meta_value'  => $repo_name,
            'numberposts' => 1,
        ) );

        return ! empty( $posts ) ? $posts[0] : null;
    }

    /**
     * Create or update plugin from GitHub data
     */
    public static function create_or_update( $repo_data, $readme_html = '' ) {
        $existing = self::get_by_repo( $repo_data['full_name'] );

        $post_data = array(
            'post_title'   => $repo_data['name'],
            'post_content' => $readme_html,
            'post_excerpt' => ! empty( $repo_data['description'] ) ? $repo_data['description'] : '',
            'post_status'  => 'publish',
            'post_type'    => self::POST_TYPE,
        );

        if ( $existing ) {
            $post_data['ID'] = $existing->ID;
            $post_id = wp_update_post( $post_data );
        } else {
            $post_id = wp_insert_post( $post_data );
        }

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Update meta
        update_post_meta( $post_id, '_github_repo', $repo_data['full_name'] );
        update_post_meta( $post_id, '_github_url', $repo_data['html_url'] );
        update_post_meta( $post_id, '_github_stars', $repo_data['stargazers_count'] );
        update_post_meta( $post_id, '_github_forks', $repo_data['forks_count'] );
        update_post_meta( $post_id, '_github_language', $repo_data['language'] ?? '' );
        update_post_meta( $post_id, '_github_updated', $repo_data['updated_at'] );
        update_post_meta( $post_id, '_github_readme', $readme_html );

        // Handle topics as categories
        if ( ! empty( $repo_data['topics'] ) ) {
            $term_ids = array();
            foreach ( $repo_data['topics'] as $topic ) {
                $term = term_exists( $topic, self::TAXONOMY );
                if ( ! $term ) {
                    $term = wp_insert_term( ucfirst( $topic ), self::TAXONOMY );
                }
                if ( ! is_wp_error( $term ) ) {
                    $term_ids[] = (int) $term['term_id'];
                }
            }
            wp_set_object_terms( $post_id, $term_ids, self::TAXONOMY );
        }

        return $post_id;
    }
}
