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
     * Add plugin meta and README to content
     */
    public function filter_plugin_content( $content ) {
        if ( ! is_singular( self::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $post_id = get_the_ID();
        $github_url = get_post_meta( $post_id, '_github_url', true );
        $stars = get_post_meta( $post_id, '_github_stars', true );
        $forks = get_post_meta( $post_id, '_github_forks', true );
        $language = get_post_meta( $post_id, '_github_language', true );

        $meta_html = '<div class="plugins-showcase-meta-bar" style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:2rem;padding:1rem;background:#f6f8fa;border-radius:6px;">';

        if ( $stars ) {
            $meta_html .= '<span style="display:inline-flex;align-items:center;gap:0.25rem;">';
            $meta_html .= '<svg viewBox="0 0 16 16" width="16" height="16" fill="#f1c40f"><path d="M8 .25a.75.75 0 01.673.418l1.882 3.815 4.21.612a.75.75 0 01.416 1.279l-3.046 2.97.719 4.192a.75.75 0 01-1.088.791L8 12.347l-3.766 1.98a.75.75 0 01-1.088-.79l.72-4.194L.818 6.374a.75.75 0 01.416-1.28l4.21-.611L7.327.668A.75.75 0 018 .25z"></path></svg>';
            $meta_html .= esc_html( number_format_i18n( $stars ) ) . ' stars</span>';
        }

        if ( $forks ) {
            $meta_html .= '<span style="display:inline-flex;align-items:center;gap:0.25rem;">';
            $meta_html .= '<svg viewBox="0 0 16 16" width="16" height="16" fill="currentColor"><path d="M5 3.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm0 2.122a2.25 2.25 0 10-1.5 0v.878A2.25 2.25 0 005.75 8.5h1.5v2.128a2.251 2.251 0 101.5 0V8.5h1.5a2.25 2.25 0 002.25-2.25v-.878a2.25 2.25 0 10-1.5 0v.878a.75.75 0 01-.75.75h-4.5A.75.75 0 015 6.25v-.878zm3.75 7.378a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm3-8.75a.75.75 0 100-1.5.75.75 0 000 1.5z"></path></svg>';
            $meta_html .= esc_html( number_format_i18n( $forks ) ) . ' forks</span>';
        }

        if ( $language ) {
            $meta_html .= '<span>' . esc_html( $language ) . '</span>';
        }

        if ( $github_url ) {
            $meta_html .= '<a href="' . esc_url( $github_url ) . '" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:0.25rem;margin-left:auto;padding:0.5rem 1rem;background:#24292e;color:#fff;border-radius:4px;text-decoration:none;">';
            $meta_html .= '<svg viewBox="0 0 16 16" width="16" height="16" fill="currentColor"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path></svg>';
            $meta_html .= 'View on GitHub</a>';
        }

        $meta_html .= '</div>';

        // If content is empty but we have README in meta, use that
        if ( empty( trim( $content ) ) ) {
            $readme = get_post_meta( $post_id, '_github_readme', true );
            if ( ! empty( $readme ) ) {
                $content = '<div class="plugins-showcase-readme">' . $readme . '</div>';
            }
        }

        return $meta_html . $content;
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
