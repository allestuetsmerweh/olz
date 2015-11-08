<?php
/**
* The template for displaying search results pages.
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/

get_header(); ?>

    <section id="primary" class="content-area">
        <main id="main" class="site-main" role="main">

        <?php if ( have_posts() ) : ?>

            <header class="page-header">
                <h1 class="page-title"><?php printf( "Suchergebnisse für: %s", get_search_query() ); ?></h1>
            </header><!-- .page-header -->

            <?php
            // Start the loop.
            while ( have_posts() ) : the_post(); ?>

                <?php
                /*
                 * Run the loop for the search to output the results.
                 * If you want to overload this in a child theme then include a file
                 * called content-search.php and that will be used instead.
                 */
                get_template_part( 'content', 'search' );

            // End the loop.
            endwhile;

            // Previous/next page navigation.
            the_posts_pagination( array(
                'prev_text'          => "<",
                'next_text'          => ">",
                'before_page_number' => '<span class="meta-nav screen-reader-text">Seite </span>',
            ) );

        // If no content, include the "No posts found" template.
        else :
            get_template_part( 'content', 'none' );

        endif;
        ?>

        </main><!-- .site-main -->
    </section><!-- .content-area -->

<?php get_footer(); ?>
