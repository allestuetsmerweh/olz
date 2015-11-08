<?php
/**
* The template for displaying all single posts and attachments
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/

get_header(); ?>

<?php include('menu.php'); ?>

<div class="rightcolumn">
    <?php
    //olz_posts_timeline($wp_query);
    ?>
</div>
<div class="maincolumn">
    <div class="singlearticle">
        <?php
        while (have_posts()) {
            the_post();
            get_template_part('content', 'article');
            if ( comments_open() || get_comments_number() ) {
                comments_template();
            }
        }
        ?>
    </div>
</div>

<?php get_footer(); ?>
