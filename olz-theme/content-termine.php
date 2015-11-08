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
        <div class="entry-excerpt entry-article"><?php the_content(); ?></div>
        <?php
        $lat = get_post_meta($post->ID, "location_lat", true);
        $lng = get_post_meta($post->ID, "location_lng", true);
        if ($lat && $lng): ?>
        <div class="entry-map entry-termine" id="<?php echo "map".($post->ID); ?>"><script><?php echo "window.addEventListener(\"load\", function () {
            var divelem = document.getElementById(\"map".($post->ID)."\");
            map(".$lat.", ".$lng.", divelem);
        });"; ?></script></div>
        <?php endif; ?>
        <div style="clear:both; height:1px;"></div>
    </div>
</article>
