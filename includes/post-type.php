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

        $post_id = get_the_ID();

        // Increment view counter
        self::increment_views( $post_id );

        // Get all meta data
        $release      = get_post_meta( $post_id, '_github_release', true );
        $contributors = get_post_meta( $post_id, '_github_contributors', true );
        $screenshots  = get_post_meta( $post_id, '_github_screenshots', true );
        $requirements = get_post_meta( $post_id, '_github_requirements', true );
        $open_issues  = get_post_meta( $post_id, '_github_open_issues', true );
        $github_url   = get_post_meta( $post_id, '_github_url', true );
        $github_repo  = get_post_meta( $post_id, '_github_repo', true );
        $views        = self::get_views( $post_id );

        // Settings
        $show_categories = get_option( 'plugins_showcase_show_categories', true );

        // Get additional meta
        $language   = get_post_meta( $post_id, '_github_language', true );
        $updated_at = get_post_meta( $post_id, '_github_updated', true );

        $output = '<div class="plugins-showcase-plugin-page">';

        // Top action bar - all meta items with same design
        $output .= '<div class="plugins-showcase-action-bar">';

        // Language
        if ( ! empty( $language ) ) {
            $output .= '<span class="plugins-showcase-action-item">';
            $output .= '<span class="plugins-showcase-language-dot" data-language="' . esc_attr( strtolower( $language ) ) . '"></span>';
            $output .= esc_html( $language ) . '</span>';
        }

        // Updated date
        if ( ! empty( $updated_at ) ) {
            $time_ago = human_time_diff( strtotime( $updated_at ), current_time( 'timestamp' ) );
            $output .= '<span class="plugins-showcase-action-item">';
            $output .= sprintf( esc_html__( 'Updated %s ago', 'plugins-showcase' ), $time_ago ) . '</span>';
        }

        // Views
        $output .= '<span class="plugins-showcase-action-item">';
        $output .= '<svg viewBox="0 0 16 16" width="14" height="14" fill="currentColor"><path d="M1.679 7.932c.412-.621 1.242-1.75 2.366-2.717C5.175 4.242 6.527 3.5 8 3.5c1.473 0 2.824.742 3.955 1.715 1.124.967 1.954 2.096 2.366 2.717a.119.119 0 010 .136c-.412.621-1.242 1.75-2.366 2.717C10.825 11.758 9.473 12.5 8 12.5c-1.473 0-2.824-.742-3.955-1.715C2.92 9.818 2.09 8.69 1.679 8.068a.119.119 0 010-.136zM8 2c-1.981 0-3.67.992-4.933 2.078C1.797 5.169.88 6.423.43 7.1a1.619 1.619 0 000 1.798c.45.678 1.367 1.932 2.637 3.024C4.329 13.008 6.019 14 8 14c1.981 0 3.67-.992 4.933-2.078 1.27-1.091 2.187-2.345 2.637-3.023a1.619 1.619 0 000-1.798c-.45-.678-1.367-1.932-2.637-3.023C11.671 2.992 9.981 2 8 2zm0 8a2 2 0 100-4 2 2 0 000 4z"></path></svg>';
        $output .= ' ' . sprintf( esc_html__( '%d views', 'plugins-showcase' ), $views ) . '</span>';

        // Issues
        if ( $github_url ) {
            $output .= '<a href="' . esc_url( $github_url . '/issues' ) . '" class="plugins-showcase-action-item" target="_blank" rel="noopener">';
            $output .= '<svg viewBox="0 0 16 16" width="14" height="14" fill="currentColor"><path d="M8 9.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path><path fill-rule="evenodd" d="M8 0a8 8 0 100 16A8 8 0 008 0zM1.5 8a6.5 6.5 0 1113 0 6.5 6.5 0 01-13 0z"></path></svg>';
            $output .= ' ' . sprintf( esc_html__( '%d Issues', 'plugins-showcase' ), (int) $open_issues ) . '</a>';
        }

        // Spacer
        $output .= '<span class="plugins-showcase-action-spacer"></span>';

        // Action buttons (right side)
        if ( $github_url ) {
            // WordPress Playground
            $playground_url = 'https://playground.wordpress.net/?plugin=' . urlencode( $github_url );
            $output .= '<a href="' . esc_url( $playground_url ) . '" class="plugins-showcase-action-btn" target="_blank" rel="noopener">';
            $output .= '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path></svg>';
            $output .= ' ' . esc_html__( 'Live Preview', 'plugins-showcase' ) . '</a>';

            // View on GitHub
            $output .= '<a href="' . esc_url( $github_url ) . '" class="plugins-showcase-action-btn" target="_blank" rel="noopener">';
            $output .= '<svg viewBox="0 0 16 16" width="14" height="14" fill="currentColor"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path></svg>';
            $output .= ' GitHub</a>';
        }

        $output .= '</div>'; // End action bar

        // Second bar: Download, Requirements, Contributors, Categories
        $has_second_bar = ! empty( $release['download_url'] ) || ! empty( $requirements ) || ! empty( $contributors ) || ( $show_categories && ! empty( get_the_terms( $post_id, self::TAXONOMY ) ) );

        if ( $has_second_bar ) {
            $output .= '<div class="plugins-showcase-meta-bar">';

            // Download button
            if ( ! empty( $release ) && ! empty( $release['download_url'] ) ) {
                $output .= '<a href="' . esc_url( $release['download_url'] ) . '" class="plugins-showcase-download-link">';
                $output .= '<svg viewBox="0 0 16 16" width="14" height="14" fill="currentColor"><path d="M2.75 14A1.75 1.75 0 011 12.25v-2.5a.75.75 0 011.5 0v2.5c0 .138.112.25.25.25h10.5a.25.25 0 00.25-.25v-2.5a.75.75 0 011.5 0v2.5A1.75 1.75 0 0113.25 14H2.75z"></path><path d="M7.25 7.689V2a.75.75 0 011.5 0v5.689l1.97-1.969a.749.749 0 111.06 1.06l-3.25 3.25a.749.749 0 01-1.06 0L4.22 6.78a.749.749 0 111.06-1.06l1.97 1.969z"></path></svg>';
                $output .= ' ' . esc_html__( 'Download', 'plugins-showcase' );
                if ( ! empty( $release['tag_name'] ) ) {
                    $output .= ' <span class="plugins-showcase-version-tag">v' . esc_html( $release['tag_name'] ) . '</span>';
                }
                $output .= '</a>';
            }

            // Requirements inline
            if ( ! empty( $requirements ) ) {
                if ( ! empty( $requirements['php'] ) || ! empty( $requirements['requires_php'] ) ) {
                    $php_version = $requirements['requires_php'] ?? $requirements['php'];
                    $output .= '<span class="plugins-showcase-action-item">PHP ' . esc_html( $php_version ) . '+</span>';
                }
                if ( ! empty( $requirements['wordpress'] ) || ! empty( $requirements['requires_wp'] ) ) {
                    $wp_version = $requirements['requires_wp'] ?? $requirements['wordpress'];
                    $output .= '<span class="plugins-showcase-action-item">WP ' . esc_html( $wp_version ) . '+</span>';
                }
            }

            // Contributors inline
            if ( ! empty( $contributors ) && is_array( $contributors ) ) {
                $output .= '<span class="plugins-showcase-contributors-inline">';
                foreach ( array_slice( $contributors, 0, 5 ) as $contributor ) {
                    $output .= '<a href="' . esc_url( $contributor['html_url'] ) . '" target="_blank" rel="noopener" title="' . esc_attr( $contributor['login'] ) . '">';
                    $output .= '<img src="' . esc_url( $contributor['avatar_url'] ) . '&s=32" alt="' . esc_attr( $contributor['login'] ) . '" width="24" height="24">';
                    $output .= '</a>';
                }
                $output .= '</span>';
            }

            // Categories (if enabled)
            if ( $show_categories ) {
                $categories = get_the_terms( $post_id, self::TAXONOMY );
                if ( $categories && ! is_wp_error( $categories ) ) {
                    $output .= '<span class="plugins-showcase-action-spacer"></span>';
                    foreach ( $categories as $category ) {
                        $output .= '<a href="' . esc_url( get_term_link( $category ) ) . '" class="plugins-showcase-category-tag">';
                        $output .= esc_html( $category->name ) . '</a>';
                    }
                }
            }

            $output .= '</div>'; // End meta bar
        }

        // Main content (full width now)
        $output .= '<div class="plugins-showcase-main-content">';

        // Screenshots
        if ( ! empty( $screenshots ) && is_array( $screenshots ) ) {
            $output .= '<div class="plugins-showcase-screenshots">';
            $output .= '<h3>' . esc_html__( 'Screenshots', 'plugins-showcase' ) . '</h3>';
            $output .= '<div class="plugins-showcase-screenshots-grid">';
            foreach ( $screenshots as $screenshot ) {
                $output .= '<a href="' . esc_url( $screenshot['download_url'] ) . '" target="_blank" class="plugins-showcase-screenshot">';
                $output .= '<img src="' . esc_url( $screenshot['download_url'] ) . '" alt="' . esc_attr( $screenshot['name'] ) . '" loading="lazy">';
                $output .= '</a>';
            }
            $output .= '</div>';
            $output .= '</div>';
        }

        // README content
        if ( empty( trim( strip_tags( $content ) ) ) ) {
            $readme = get_post_meta( $post_id, '_github_readme', true );
            if ( ! empty( $readme ) ) {
                $output .= '<div class="plugins-showcase-readme">' . $readme . '</div>';
            }
        } else {
            $output .= '<div class="plugins-showcase-readme">' . $content . '</div>';
        }

        $output .= '</div>'; // End main content
        $output .= '</div>'; // End plugin page

        return $output;
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
        update_post_meta( $post_id, '_github_open_issues', $repo_data['open_issues_count'] ?? 0 );

        // Latest release
        if ( ! empty( $repo_data['latest_release'] ) ) {
            update_post_meta( $post_id, '_github_release', $repo_data['latest_release'] );
        }

        // Contributors
        if ( ! empty( $repo_data['contributors'] ) ) {
            update_post_meta( $post_id, '_github_contributors', $repo_data['contributors'] );
        }

        // Screenshots
        if ( ! empty( $repo_data['screenshots'] ) ) {
            update_post_meta( $post_id, '_github_screenshots', $repo_data['screenshots'] );
        }

        // Requirements
        if ( ! empty( $repo_data['requirements'] ) ) {
            update_post_meta( $post_id, '_github_requirements', $repo_data['requirements'] );
        }

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

    /**
     * Increment view count
     */
    public static function increment_views( $post_id ) {
        $views = (int) get_post_meta( $post_id, '_plugin_views', true );
        update_post_meta( $post_id, '_plugin_views', $views + 1 );
        return $views + 1;
    }

    /**
     * Get view count
     */
    public static function get_views( $post_id ) {
        return (int) get_post_meta( $post_id, '_plugin_views', true );
    }
}
