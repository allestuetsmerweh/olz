<?php
/**
* The default template for displaying content
*
* Used for both single and index/archive/search.
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php
            if ( is_single() ) :
                the_title( '<h1 class="entry-title">', '</h1>' );
            else :
                the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' );
            endif;
        ?>
    </header><!-- .entry-header -->

    <div class="entry-content">
        <?php
            /* translators: %s: Name of current post */
            the_content( sprintf(
                "%s weiterlesen",
                the_title( '<span class="screen-reader-text">', '</span>', false )
            ) );

            wp_link_pages( array(
                'before'      => '<div class="page-links"><span class="page-links-title">Seiten:</span>',
                'after'       => '</div>',
                'link_before' => '<span>',
                'link_after'  => '</span>',
                'pagelink'    => '<span class="screen-reader-text">Seite </span>%',
                'separator'   => '<span class="screen-reader-text">, </span>',
            ) );
        ?>
    </div><!-- .entry-content -->

    <?php
        // Author bio.
        if ( is_single() && get_the_author_meta( 'description' ) ) :
            get_template_part( 'author-bio' );
        endif;
    ?>

    <footer class="entry-footer">
        <?php edit_post_link( "Bearbeiten", '<span class="edit-link">', '</span>' ); ?>
    </footer><!-- .entry-footer -->

</article><!-- #post-## -->
