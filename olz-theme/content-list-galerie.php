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
    <div class="entry-edit entry-galerie"><?php edit_post_link("<span class=\"dashicons dashicons-edit\"></span>"); ?></div>
    <a href="<?php the_permalink()?>">
        <header class="entry-header entry-galerie">
            <div class="entry-date entry-galerie"><?php the_time("d.m.y"); ?></div>
            <h2 class="entry-title entry-galerie"><?php the_title(); ?></h2>
        </header>
    </a>
    <div class="entry-content entry-galerie">
        <div class="entry-randomimages entry-galerie">
        <?php
        $imgs = get_post_meta($post->ID, "galerie", true);
        $chosen = array();
        if (count($imgs)<=4) {
            $chosen = $imgs;
        } else {
            $done = array();
            for ($i=0; $i<4; $i++) {
                $ind = rand(0, count($imgs)-1);
                while (array_search($ind, $done)!==false) {
                    $ind = rand(0, count($imgs)-1);
                }
                $done[] = $ind;
                $chosen[] = $imgs[$ind];
            }
        }
        echo "<table style=\"width:100%;\" cellspacing=\"0\"><tr>";
        for ($i=0; $i<count($chosen); $i++) {
            echo "<td>".wp_get_attachment_image($chosen[$i])."</td>";
        }
        echo "</tr></table>";
        ?>
        </div>
        <div style="clear:both; height:1px;"></div>
    </div>
</article>
