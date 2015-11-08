<?php
/**
* The template used for displaying page content
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
    </header><!-- .entry-header -->

    <div class="entry-content">
        <?php echo olz_replace_placeholders(get_the_content()); ?>
        <?php
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

    <?php edit_post_link( "Bearbeiten", '<footer class="entry-footer"><span class="edit-link">', '</span></footer><!-- .entry-footer -->' ); ?>

</article><!-- #post-## -->
