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
    <div class="entry-edit entry-termine"><?php edit_post_link("<span class=\"dashicons dashicons-edit\"></span>"); ?></div>
    <a href="<?php the_permalink()?>">
        <header class="entry-header entry-termine">
            <div class="entry-date entry-termine"><?php echo date("d.m.y", strtotime(get_post_meta($post->ID, "timerange_start", true))); ?></div>
            <h2 class="entry-title entry-termine"><?php the_title(); ?></h2>
        </header>
    </a>
    <div class="entry-content entry-termine">
        <?php if ( has_post_thumbnail() ): ?> <div class="entry-thumbnail entry-termine"><?php the_post_thumbnail(); ?></div> <?php endif; ?>
        <div class="entry-excerpt entry-termine"><?php the_excerpt(); ?></div>
        <?php
        $lat = get_post_meta($post->ID, "location_lat", true);
        $lng = get_post_meta($post->ID, "location_lng", true);
        if ($lat && $lng): ?>
        <div class="entry-map entry-termine" id="<?php echo "map".($post->ID); ?>"><a href="<?php echo "javascript:toggleMap(&quot;map".($post->ID)."&quot;, ".$lat.", ".$lng.")"; ?>">Karte</a></div>
        <?php endif; ?>
        <div style="clear:both; height:1px;"></div>
    </div>
</article>
