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
    <div class="entry-edit entry-article"><?php edit_post_link("<span class=\"dashicons dashicons-edit\"></span>"); ?></div>
    <a href="<?php the_permalink()?>">
        <header class="entry-header entry-article">
            <div class="entry-date entry-article"><?php the_time("d.m.y"); ?></div>
            <h2 class="entry-title entry-article"><?php the_title(); ?></h2>
        </header>
    </a>
    <div class="entry-content entry-article">
        <?php if ( has_post_thumbnail() ): ?> <div class="entry-thumbnail entry-article"><?php the_post_thumbnail(); ?></div> <?php endif; ?>
        <div class="entry-excerpt entry-article"><?php the_excerpt(); ?></div>
        <div style="clear:both; height:1px;"></div>
    </div>
</article>
