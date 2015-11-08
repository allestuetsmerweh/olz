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
    print_r($post);
    ?>
</div>
<div class="maincolumn">
    <div class="singlearticle">
        <article>
            <h2><?php the_title(); ?></h2>
<?php
if (substr($post->post_mime_type, 0, 6)=="image/") {
    echo "<center>".wp_get_attachment_image($post->ID, 'full', 0, array("style"=>"max-width:100%; height:auto;"))."</center>";
} else {
    echo "<iframe src=\"".wp_get_attachment_url($post->ID)."\" style=\"width:100%; height:600px; border:0px;\" />";
}
if (comments_open()||get_comments_number()) {
    comments_template();
}
?>
        </article>
    </div>
</div>

<?php get_footer(); ?>
