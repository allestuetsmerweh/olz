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
    <div class="entry-content entry-galerie">
        <div class="entry-randomimages entry-galerie">
        <?php
        $imgs = get_post_meta($post->ID, "galerie", true);
        echo "<table style=\"width:100%;\" cellspacing=\"0\">";
        $cimgs = count($imgs);
        for ($y=0; $y<ceil($cimgs/4); $y++) {
            echo "<tr>";
            for ($x=0; $x<4; $x++) {
                $ind = $y*4+$x;
                if ($ind<$cimgs) {
                    echo "<td><a href='".wp_get_attachment_image_src($imgs[$ind], 'large')[0]."' class='swipebox' rel='galerie_".get_the_id()."'>".wp_get_attachment_image($imgs[$ind], 'thumbnail', false)."</a></td>";
                } else {
                    echo "<td></td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
        ?>
        </div>
        <div style="clear:both; height:1px;"></div>
    </div>
</article>
