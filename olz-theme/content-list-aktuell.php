<?php
/**
* The template used for displaying page content
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/
?>

<div>
<article id="post-<?php the_ID(); ?>" class="article-aktuell <?php
    $now = date("Y-m-d");
    if ($now<get_post_meta($post->ID, 'notification_first', true)) echo "grey";
    else if ($now<get_post_meta($post->ID, 'notification_last', true)) echo "green";
    else if ($now<=get_post_meta($post->ID, 'expiration', true)) echo "yellow";
    else if ($now<=get_post_meta($post->ID, 'disappearance', true)) echo "red";
    else echo "grey";
?>">
    <?php if (has_post_thumbnail()) { ?> <div class="entry-thumbnail entry-aktuell"><?php the_post_thumbnail(); ?></div> <?php } ?>
    <span class="entry-edit entry-aktuell"><?php edit_post_link("<span class=\"dashicons dashicons-edit\"></span>"); ?></span>
    <h2 class="entry-title entry-aktuell"<?php if (has_post_thumbnail()) echo " style=\"display:block;\""; ?>><?php the_title(); ?></h2>
    <span class="entry-content entry-aktuell"><?php the_content(); ?></span>
    <div style="clear:both; height:0px;"></div>
</article>
</div>
