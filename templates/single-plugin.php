<?php
/**
 * Template for single plugin page
 */

get_header();

while ( have_posts() ) :
    the_post();

    $github_url = get_post_meta( get_the_ID(), '_github_url', true );
    $stars = get_post_meta( get_the_ID(), '_github_stars', true );
    $forks = get_post_meta( get_the_ID(), '_github_forks', true );
    $language = get_post_meta( get_the_ID(), '_github_language', true );
    $updated = get_post_meta( get_the_ID(), '_github_updated', true );
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'plugins-showcase-single-page' ); ?>>
        <div class="plugins-showcase-single">
            <header class="plugins-showcase-single-header">
                <h1 class="plugins-showcase-single-title"><?php the_title(); ?></h1>

                <?php if ( has_excerpt() ) : ?>
                    <p class="plugins-showcase-single-excerpt"><?php the_excerpt(); ?></p>
                <?php endif; ?>

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

                    <?php if ( $updated ) : ?>
                        <span class="plugins-showcase-meta-item">
                            <?php
                            printf(
                                esc_html__( 'Updated %s', 'plugins-showcase' ),
                                esc_html( human_time_diff( strtotime( $updated ) ) . ' ago' )
                            );
                            ?>
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

                <?php
                $categories = get_the_terms( get_the_ID(), 'plugin_category' );
                if ( $categories && ! is_wp_error( $categories ) ) :
                    ?>
                    <div class="plugins-showcase-categories" style="margin-top: 1rem;">
                        <?php foreach ( $categories as $category ) : ?>
                            <a href="<?php echo esc_url( get_term_link( $category ) ); ?>" class="plugins-showcase-badge">
                                <?php echo esc_html( $category->name ); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </header>

            <div class="plugins-showcase-single-content">
                <?php the_content(); ?>
            </div>
        </div>
    </article>

    <?php
endwhile;

get_footer();
