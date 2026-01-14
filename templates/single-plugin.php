<?php
/**
 * Template for single plugin page
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

while ( have_posts() ) :
    the_post();
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'plugins-showcase-single-page' ); ?>>
        <div class="plugins-showcase-single">
            <header class="plugins-showcase-single-header">
                <h1 class="plugins-showcase-single-title"><?php the_title(); ?></h1>

                <?php if ( has_excerpt() ) : ?>
                    <p class="plugins-showcase-single-excerpt"><?php the_excerpt(); ?></p>
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
