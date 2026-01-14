<?php
/**
 * Gutenberg Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugins_Showcase_Blocks {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_blocks' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
    }

    /**
     * Register blocks
     */
    public function register_blocks() {
        // Plugins Grid Block
        register_block_type( 'plugins-showcase/plugins-grid', array(
            'render_callback' => array( $this, 'render_plugins_grid' ),
            'attributes'      => array(
                'columns'      => array(
                    'type'    => 'number',
                    'default' => 3,
                ),
                'perPage'      => array(
                    'type'    => 'number',
                    'default' => 12,
                ),
                'showSearch'   => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'showFilters'  => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'category'     => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'showStars'    => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'showLanguage' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );

        // Single Plugin Block
        register_block_type( 'plugins-showcase/single-plugin', array(
            'render_callback' => array( $this, 'render_single_plugin' ),
            'attributes'      => array(
                'pluginId'    => array(
                    'type'    => 'number',
                    'default' => 0,
                ),
                'showReadme'  => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'showMeta'    => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
        ) );
    }

    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'plugins-showcase-blocks',
            PLUGINS_SHOWCASE_URL . 'assets/js/blocks.js',
            array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch' ),
            PLUGINS_SHOWCASE_VERSION,
            true
        );

        wp_enqueue_style(
            'plugins-showcase-blocks-editor',
            PLUGINS_SHOWCASE_URL . 'assets/css/blocks-editor.css',
            array( 'wp-edit-blocks' ),
            PLUGINS_SHOWCASE_VERSION
        );

        // Pass categories to block
        $categories = get_terms( array(
            'taxonomy'   => Plugins_Showcase_Post_Type::TAXONOMY,
            'hide_empty' => false,
        ) );

        $plugins = get_posts( array(
            'post_type'   => Plugins_Showcase_Post_Type::POST_TYPE,
            'numberposts' => -1,
            'post_status' => 'publish',
        ) );

        wp_localize_script( 'plugins-showcase-blocks', 'pluginsShowcaseBlocks', array(
            'categories' => $categories,
            'plugins'    => array_map( function( $plugin ) {
                return array(
                    'id'    => $plugin->ID,
                    'title' => $plugin->post_title,
                );
            }, $plugins ),
            'restUrl'    => rest_url( 'plugins-showcase/v1/' ),
        ) );
    }

    /**
     * Render Plugins Grid Block
     */
    public function render_plugins_grid( $attributes ) {
        $columns = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3;
        $per_page = isset( $attributes['perPage'] ) ? absint( $attributes['perPage'] ) : 12;
        $show_search = isset( $attributes['showSearch'] ) ? $attributes['showSearch'] : true;
        $show_filters = isset( $attributes['showFilters'] ) ? $attributes['showFilters'] : true;
        $category = isset( $attributes['category'] ) ? sanitize_text_field( $attributes['category'] ) : '';
        $show_stars = isset( $attributes['showStars'] ) ? $attributes['showStars'] : true;
        $show_language = isset( $attributes['showLanguage'] ) ? $attributes['showLanguage'] : true;

        $args = array(
            'post_type'      => Plugins_Showcase_Post_Type::POST_TYPE,
            'posts_per_page' => $per_page,
            'post_status'    => 'publish',
        );

        if ( ! empty( $category ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => Plugins_Showcase_Post_Type::TAXONOMY,
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }

        $query = new WP_Query( $args );
        $categories = get_terms( array(
            'taxonomy'   => Plugins_Showcase_Post_Type::TAXONOMY,
            'hide_empty' => true,
        ) );

        ob_start();
        ?>
        <div class="plugins-showcase-grid-wrapper" data-columns="<?php echo esc_attr( $columns ); ?>" data-per-page="<?php echo esc_attr( $per_page ); ?>">
            <?php if ( $show_search || $show_filters ) : ?>
                <div class="plugins-showcase-filters">
                    <?php if ( $show_search ) : ?>
                        <div class="plugins-showcase-search">
                            <input type="text"
                                   class="plugins-showcase-search-input"
                                   placeholder="<?php esc_attr_e( 'Search plugins...', 'plugins-showcase' ); ?>">
                        </div>
                    <?php endif; ?>

                    <?php if ( $show_filters && ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                        <div class="plugins-showcase-category-filter">
                            <select class="plugins-showcase-category-select">
                                <option value=""><?php esc_html_e( 'All Categories', 'plugins-showcase' ); ?></option>
                                <?php foreach ( $categories as $cat ) : ?>
                                    <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $category, $cat->slug ); ?>>
                                        <?php echo esc_html( $cat->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="plugins-showcase-grid plugins-showcase-cols-<?php echo esc_attr( $columns ); ?>">
                <?php if ( $query->have_posts() ) : ?>
                    <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                        <?php echo $this->render_plugin_card( get_the_ID(), $show_stars, $show_language ); ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <p class="plugins-showcase-no-results">
                        <?php esc_html_e( 'No plugins found.', 'plugins-showcase' ); ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ( $query->max_num_pages > 1 ) : ?>
                <div class="plugins-showcase-pagination">
                    <button class="plugins-showcase-load-more" data-page="1" data-max-pages="<?php echo esc_attr( $query->max_num_pages ); ?>">
                        <?php esc_html_e( 'Load More', 'plugins-showcase' ); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single plugin card
     */
    private function render_plugin_card( $post_id, $show_stars = true, $show_language = true ) {
        $github_url = get_post_meta( $post_id, '_github_url', true );
        $stars = get_post_meta( $post_id, '_github_stars', true );
        $language = get_post_meta( $post_id, '_github_language', true );

        ob_start();
        ?>
        <article class="plugins-showcase-card">
            <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="plugins-showcase-card-link">
                <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                    <div class="plugins-showcase-card-thumbnail">
                        <?php echo get_the_post_thumbnail( $post_id, 'medium' ); ?>
                    </div>
                <?php endif; ?>

                <div class="plugins-showcase-card-content">
                    <h3 class="plugins-showcase-card-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>

                    <?php if ( get_the_excerpt( $post_id ) ) : ?>
                        <p class="plugins-showcase-card-excerpt"><?php echo esc_html( get_the_excerpt( $post_id ) ); ?></p>
                    <?php endif; ?>

                    <div class="plugins-showcase-card-meta">
                        <?php if ( $show_stars && $stars ) : ?>
                            <span class="plugins-showcase-stars">
                                <svg viewBox="0 0 16 16" width="16" height="16" fill="currentColor">
                                    <path d="M8 .25a.75.75 0 01.673.418l1.882 3.815 4.21.612a.75.75 0 01.416 1.279l-3.046 2.97.719 4.192a.75.75 0 01-1.088.791L8 12.347l-3.766 1.98a.75.75 0 01-1.088-.79l.72-4.194L.818 6.374a.75.75 0 01.416-1.28l4.21-.611L7.327.668A.75.75 0 018 .25z"></path>
                                </svg>
                                <?php echo esc_html( number_format_i18n( $stars ) ); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ( $show_language && $language ) : ?>
                            <span class="plugins-showcase-language">
                                <span class="plugins-showcase-language-color" data-language="<?php echo esc_attr( strtolower( $language ) ); ?>"></span>
                                <?php echo esc_html( $language ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>

            <?php if ( $github_url ) : ?>
                <a href="<?php echo esc_url( $github_url ); ?>" class="plugins-showcase-github-link" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 16 16" width="16" height="16" fill="currentColor">
                        <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path>
                    </svg>
                </a>
            <?php endif; ?>
        </article>
        <?php
        return ob_get_clean();
    }

    /**
     * Render Single Plugin Block
     */
    public function render_single_plugin( $attributes ) {
        $plugin_id = isset( $attributes['pluginId'] ) ? absint( $attributes['pluginId'] ) : 0;
        $show_readme = isset( $attributes['showReadme'] ) ? $attributes['showReadme'] : true;
        $show_meta = isset( $attributes['showMeta'] ) ? $attributes['showMeta'] : true;

        if ( ! $plugin_id ) {
            return '<p class="plugins-showcase-error">' . esc_html__( 'Please select a plugin.', 'plugins-showcase' ) . '</p>';
        }

        $post = get_post( $plugin_id );

        if ( ! $post || $post->post_type !== Plugins_Showcase_Post_Type::POST_TYPE ) {
            return '<p class="plugins-showcase-error">' . esc_html__( 'Plugin not found.', 'plugins-showcase' ) . '</p>';
        }

        $github_url = get_post_meta( $plugin_id, '_github_url', true );
        $stars = get_post_meta( $plugin_id, '_github_stars', true );
        $forks = get_post_meta( $plugin_id, '_github_forks', true );
        $language = get_post_meta( $plugin_id, '_github_language', true );
        $updated = get_post_meta( $plugin_id, '_github_updated', true );

        ob_start();
        ?>
        <div class="plugins-showcase-single">
            <header class="plugins-showcase-single-header">
                <h2 class="plugins-showcase-single-title">
                    <a href="<?php echo esc_url( get_permalink( $plugin_id ) ); ?>">
                        <?php echo esc_html( $post->post_title ); ?>
                    </a>
                </h2>

                <?php if ( $post->post_excerpt ) : ?>
                    <p class="plugins-showcase-single-excerpt"><?php echo esc_html( $post->post_excerpt ); ?></p>
                <?php endif; ?>

                <?php if ( $show_meta ) : ?>
                    <div class="plugins-showcase-single-meta">
                        <?php if ( $stars ) : ?>
                            <span class="plugins-showcase-meta-item">
                                <svg viewBox="0 0 16 16" width="16" height="16" fill="currentColor">
                                    <path d="M8 .25a.75.75 0 01.673.418l1.882 3.815 4.21.612a.75.75 0 01.416 1.279l-3.046 2.97.719 4.192a.75.75 0 01-1.088.791L8 12.347l-3.766 1.98a.75.75 0 01-1.088-.79l.72-4.194L.818 6.374a.75.75 0 01.416-1.28l4.21-.611L7.327.668A.75.75 0 018 .25z"></path>
                                </svg>
                                <?php echo esc_html( number_format_i18n( $stars ) ); ?> <?php esc_html_e( 'stars', 'plugins-showcase' ); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ( $forks ) : ?>
                            <span class="plugins-showcase-meta-item">
                                <svg viewBox="0 0 16 16" width="16" height="16" fill="currentColor">
                                    <path d="M5 3.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm0 2.122a2.25 2.25 0 10-1.5 0v.878A2.25 2.25 0 005.75 8.5h1.5v2.128a2.251 2.251 0 101.5 0V8.5h1.5a2.25 2.25 0 002.25-2.25v-.878a2.25 2.25 0 10-1.5 0v.878a.75.75 0 01-.75.75h-4.5A.75.75 0 015 6.25v-.878zm3.75 7.378a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm3-8.75a.75.75 0 100-1.5.75.75 0 000 1.5z"></path>
                                </svg>
                                <?php echo esc_html( number_format_i18n( $forks ) ); ?> <?php esc_html_e( 'forks', 'plugins-showcase' ); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ( $language ) : ?>
                            <span class="plugins-showcase-meta-item">
                                <span class="plugins-showcase-language-color" data-language="<?php echo esc_attr( strtolower( $language ) ); ?>"></span>
                                <?php echo esc_html( $language ); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ( $github_url ) : ?>
                            <a href="<?php echo esc_url( $github_url ); ?>" class="plugins-showcase-meta-item plugins-showcase-github-btn" target="_blank" rel="noopener noreferrer">
                                <svg viewBox="0 0 16 16" width="16" height="16" fill="currentColor">
                                    <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path>
                                </svg>
                                <?php esc_html_e( 'View on GitHub', 'plugins-showcase' ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </header>

            <?php if ( $show_readme && $post->post_content ) : ?>
                <div class="plugins-showcase-single-content">
                    <?php echo apply_filters( 'the_content', $post->post_content ); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
