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
    <?php if ( has_post_thumbnail() ): ?> <div class="entry-thumbnail entry-karten"><?php the_post_thumbnail(); ?></div> <?php endif; ?>
    <header class="entry-header entry-karten">
        <div class="entry-karten">
            <span class="entry-edit entry-karten"><?php edit_post_link("Bearbeiten"); ?></span>
        </div>
        <h2 class="entry-title entry-karten"><?php the_title(); $city = get_post_meta($post->ID, "city", true); echo ($city?" (".$city.")":""); ?></h2>
    </header>
    <div class="entry-content entry-karten">
        <div class="entry-year entry-karten">Stand: <?php echo get_post_meta($post->ID, "year", true); ?></div>
        <div class="entry-scale entry-karten">Massstab: <?php $scale = get_post_meta($post->ID, "scale", true); echo ($scale?$scale:"?"); ?></div>
        <div class="entry-scale entry-karten">Karten-Nummer: <?php $kartennr = get_post_meta($post->ID, "kartennr", true); echo ($kartennr?$kartennr:"?"); ?></div>
        <div class="entry-scale entry-karten">Karten-Typ:
        <?php $kartentyp = wp_get_object_terms($post->ID, "karten-typ");
        for ($i=0; $i<count($kartentyp); $i++) {
            if (0<$i) echo ", ";
            echo $kartentyp[$i]->name;
        }
        ?>
        </div>
        <div style="clear:both; height:1px;"></div>
    </div>
</article>
