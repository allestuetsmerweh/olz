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
    <header class="entry-header entry-article">
        <div class="entry-article">
            <span class="entry-date entry-article"><?php the_time("d.m.y"); ?></span>
            <span class="entry-author entry-article"><?php the_author(); ?></span>
            <span class="entry-edit entry-article"><?php edit_post_link("Bearbeiten"); ?></span>
        </div>
        <h2 class="entry-title entry-article"><?php the_title(); ?></h2>
    </header>
    <div class="entry-content entry-article">
        <?php if ( has_post_thumbnail() ): ?> <div class="entry-thumbnail entry-article"><?php the_post_thumbnail(); ?></div> <?php endif; ?>
        <div class="entry-content entry-article"><?php the_content(); ?></div>
        <div style="clear:both; height:1px;"></div>
    </div>
</article>
