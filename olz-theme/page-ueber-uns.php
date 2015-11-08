<?php
/**
* The template for displaying pages
*
* This is the template that displays all pages by default.
* Please note that this is the WordPress construct of pages and that
* other "pages" on your WordPress site will use a different template.
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/

get_header(); ?>

<?php include('menu.php'); ?>

<div class="mainrightcolumn">
    <?php
/*
$kq = get_users();
for ($i=0; $i<count($kq); $i++) {
    echo "<pre>";
    print_r($kq[$i]);
    echo "</pre><br>";
}
*/
$oq = new WP_Query(array('post_type'=>'organigramm', 'post_parent'=>0, 'orderby'=>'menu_order', 'order'=>'ASC'));
$org = '<div class="organigramm">';
foreach ($oq->posts as $post) {
    //get_template_part('content', 'list-article');
    $org .= '<div class="ressort">';
    $org .= '<div class="box" id="box-'.$post->ID.'"><div title="'.($post->post_title).'">'.($post->post_title).'</div>';
    $bq = new WP_Query(array('post_type'=>'besetzung', 'meta_key'=>'organigramm', 'meta_value'=>$post->ID, 'meta_compare'=>'='));
    for ($l=0; $l<count($bq->posts); $l++) {
        $u = new WP_User(get_post_meta($bq->posts[$l]->ID, 'user', true));
        $org .= '<div title="'.($u->display_name).'">'.($u->display_name).'</div>';
    }
    $org .= '</div>';
    $sq = new WP_Query(array('post_type'=>'organigramm', 'post_parent'=>$post->ID, 'orderby'=>'menu_order', 'order'=>'ASC'));
    for ($j=0; $j<count($sq->posts); $j++) {
        $sqid = $sq->posts[$j]->ID;
        $sqtitle = $sq->posts[$j]->post_title;
        $org .= '<div class="boxconn"></div>';
        $org .= '<div class="box" id="box-'.$sqid.'"><div title="'.$sqtitle.'">'.$sqtitle.'</div>';
        $ssstyle = 'font-style:italic;';
        if (strlen($sqtitle)==0) $ssstyle = 'font-weight:bold;';
        $ssq = new WP_Query(array('post_type'=>'organigramm', 'post_parent'=>$sqid, 'orderby'=>'menu_order', 'order'=>'ASC'));
        for ($k=0; $k<count($ssq->posts); $k++) {
            $ssqtitle = $ssq->posts[$k]->post_title;
            $org .= '<div style="'.$ssstyle.'" title="'.$ssqtitle.'">'.$ssqtitle.'</div>';
        }
        $org .= '</div>';
    }
    $org .= '</div>';
}
$org .= '</div>';
echo $org;
wp_reset_postdata();
?>
</div>

<?php get_footer(); ?>
