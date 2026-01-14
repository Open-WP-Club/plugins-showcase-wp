<?php
/**
 * Template for plugins archive page
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$categories = get_terms( array(
    'taxonomy'   => 'plugin_category',
    'hide_empty' => true,
) );
?>

<div class="plugins-showcase-archive">
    <header class="plugins-showcase-archive-header">
        <h1 class="plugins-showcase-archive-title">
            <?php
            if ( is_tax( 'plugin_category' ) ) {
                single_term_title();
            } else {
                esc_html_e( 'Plugins', 'plugins-showcase' );
            }
            ?>
        </h1>

        <?php if ( is_tax( 'plugin_category' ) ) : ?>
            <p class="plugins-showcase-archive-description">
                <?php echo esc_html( term_description() ); ?>
            </p>
        <?php endif; ?>
    </header>

    <div class="plugins-showcase-grid-wrapper" data-columns="3" data-per-page="12">
        <div class="plugins-showcase-filters">
            <div class="plugins-showcase-search">
                <input type="text"
                       class="plugins-showcase-search-input"
                       placeholder="<?php esc_attr_e( 'Search plugins...', 'plugins-showcase' ); ?>">
            </div>

            <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                <div class="plugins-showcase-category-filter">
                    <select class="plugins-showcase-category-select">
                        <option value=""><?php esc_html_e( 'All Categories', 'plugins-showcase' ); ?></option>
                        <?php foreach ( $categories as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( is_tax( 'plugin_category', $cat->slug ) ); ?>>
                                <?php echo esc_html( $cat->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <div class="plugins-showcase-grid plugins-showcase-cols-3">
            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php
                    $github_url = get_post_meta( get_the_ID(), '_github_url', true );
                    $stars = get_post_meta( get_the_ID(), '_github_stars', true );
                    $language = get_post_meta( get_the_ID(), '_github_language', true );
                    ?>

                    <article class="plugins-showcase-card">
                        <a href="<?php the_permalink(); ?>" class="plugins-showcase-card-link">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="plugins-showcase-card-thumbnail">
                                    <?php the_post_thumbnail( 'medium' ); ?>
                                </div>
                            <?php endif; ?>

                            <div class="plugins-showcase-card-content">
                                <h3 class="plugins-showcase-card-title"><?php the_title(); ?></h3>

                                <?php if ( has_excerpt() ) : ?>
                                    <p class="plugins-showcase-card-excerpt"><?php the_excerpt(); ?></p>
                                <?php endif; ?>

                                <div class="plugins-showcase-card-meta">
                                    <?php if ( $stars ) : ?>
                                        <span class="plugins-showcase-stars">
                                            <svg viewBox="0 0 16 16" width="16" height="16" fill="currentColor">
                                                <path d="M8 .25a.75.75 0 01.673.418l1.882 3.815 4.21.612a.75.75 0 01.416 1.279l-3.046 2.97.719 4.192a.75.75 0 01-1.088.791L8 12.347l-3.766 1.98a.75.75 0 01-1.088-.79l.72-4.194L.818 6.374a.75.75 0 01.416-1.28l4.21-.611L7.327.668A.75.75 0 018 .25z"></path>
                                            </svg>
                                            <?php echo esc_html( number_format_i18n( $stars ) ); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ( $language ) : ?>
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
                <?php endwhile; ?>
            <?php else : ?>
                <p class="plugins-showcase-no-results">
                    <?php esc_html_e( 'No plugins found.', 'plugins-showcase' ); ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if ( $wp_query->max_num_pages > 1 ) : ?>
            <div class="plugins-showcase-pagination">
                <button class="plugins-showcase-load-more" data-page="1" data-max-pages="<?php echo esc_attr( $wp_query->max_num_pages ); ?>">
                    <?php esc_html_e( 'Load More', 'plugins-showcase' ); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
