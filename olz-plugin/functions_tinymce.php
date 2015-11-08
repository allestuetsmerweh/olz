<?php

/*
* TinyMCE modifications for OLZ Theme
*/

function olz_tinymce_add_plugins($plugins) {
    $plugins['olz_placeholder'] = plugins_url('/js/olz_tinymce.js', __FILE__);
    return $plugins;
}
function olz_tinymce_register_buttons($buttons) {
    array_push($buttons, '|', 'olz_placeholder');
    return $buttons;
}
function olz_tinymce_buttons() {
    add_filter('mce_external_plugins', 'olz_tinymce_add_plugins');
    add_filter('mce_buttons', 'olz_tinymce_register_buttons');
}
add_action('init', 'olz_tinymce_buttons');

function olz_tinymce_js() {
    function get_organigramm_children($postid, $level=0) {
        $cld = array();
        $oq = new WP_Query(array('post_type'=>'organigramm', 'post_parent'=>$postid, 'orderby'=>'menu_order', 'order'=>'ASC'));
        $cnt = count($oq->posts);
        $prefix = "";
        for ($i=0; $i<$level; $i++) $prefix .= "-";
        for ($i=0; $i<$cnt; $i++) {
            $post = $oq->posts[$i];
            $cld[] = array('id'=>intval($post->ID), 'title'=>$prefix.$post->post_title);
            $cld = array_merge($cld, get_organigramm_children($post->ID, $level+1));
        }
        return $cld;
    }
    $texte = array();
    $tq = new WP_Query(array('post_type'=>'texte', 'orderby'=>'menu_order', 'order'=>'ASC'));
    $cnt = count($tq->posts);
    for ($i=0; $i<$cnt; $i++) {
        $post = $tq->posts[$i];
        $texte[] = array('id'=>$post->ID, 'title'=>$post->post_title);
    }
    echo "<script>var olz_organigramm = ".json_encode(get_organigramm_children(0))."; var olz_texte = ".json_encode($texte).";</script>";
}
add_action('wp_head', 'olz_tinymce_js');
add_action('admin_head', 'olz_tinymce_js');


function olz_tinymce_css($mce_css) {
    if (!empty($mce_css)) $mce_css .= ',';
    $mce_css .= plugins_url('/css/olz_tinymce.css', __FILE__);
    return $mce_css;
}
add_filter('mce_css', 'olz_tinymce_css');

?>
