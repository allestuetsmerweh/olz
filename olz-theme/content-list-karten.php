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
    <div class="entry-edit entry-karten"><?php edit_post_link("<span class=\"dashicons dashicons-edit\"></span>"); ?></div>
    <a href="<?php the_permalink()?>">
        <header class="entry-header entry-karten">
            <h2 class="entry-title entry-karten"><?php the_title(); $city = get_post_meta($post->ID, "city", true); echo ($city?" (".$city.")":""); ?></h2>
        </header>
    </a>
    <div class="entry-content entry-karten">
        <?php if ( has_post_thumbnail() ): ?> <div class="entry-thumbnail entry-karten"><?php the_post_thumbnail(); ?></div> <?php endif; ?>
        <div class="entry-year entry-karten">Stand: <?php echo get_post_meta($post->ID, "year", true); ?></div>
        <div class="entry-scale entry-karten">Massstab: <?php $scale = get_post_meta($post->ID, "scale", true); echo ($scale?$scale:"?"); ?></div>
        <div style="clear:both; height:1px;"></div>
    </div>
</article>
