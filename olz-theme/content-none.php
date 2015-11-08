<?php
/**
* The template part for displaying a message that posts cannot be found
*
* Learn more: {@link https://codex.wordpress.org/Template_Hierarchy}
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/
?>

<section class="no-results not-found">
    <header class="page-header">
        <h1 class="page-title">Nichts gefunden</h1>
    </header><!-- .page-header -->

    <div class="page-content">

        <?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>

            <p><?php printf( "Willst du einen Beitrag erstellen? <a href="%1$s">Hier geht's los</a>.", esc_url( admin_url( 'post-new.php' ) ) ); ?></p>

        <?php elseif ( is_search() ) : ?>

            <p>Leider wurden keine Übereinstimmungen gefunden. Bitte versuchen Sie es mit anderen Suchbegriffen.</p>
            <?php get_search_form(); ?>

        <?php else : ?>

            <p>Wir konnten nicht finden, was Sie suchten. Vielleicht hilft eine Suche?</p>
            <?php get_search_form(); ?>

        <?php endif; ?>

    </div><!-- .page-content -->
</section><!-- .no-results -->
