<?php
/**
* The template for displaying 404 pages (not found)
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/

get_header(); ?>

    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">

            <section class="error-404 not-found">
                <header class="page-header">
                    <h1 class="page-title">Diese Seite konnte nicht gefunden werden.</h1>
                </header><!-- .page-header -->

                <div class="page-content">
                    <p>Du stehst im Schilf.</p>

                    <?php get_search_form(); ?>
                </div><!-- .page-content -->
            </section><!-- .error-404 -->

        </main><!-- .site-main -->
    </div><!-- .content-area -->

<?php get_footer(); ?>
