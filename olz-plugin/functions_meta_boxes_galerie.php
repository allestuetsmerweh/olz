<?php

$post_type = 'galerie';

function olz_galerie_save_meta_box_data($post_id) {
    update_meta_from_dict($post_id, 'galerie', $_POST, json_decode);
}
add_save_post_action($post_type, 'olz_galerie_save_meta_box_data');

function olz_galerie_media_meta_box($post) {
    wp_nonce_field('olz_save_meta_box_data', 'olz_meta_box_nonce');
    $galerie = get_post_meta($post->ID, "galerie", true);
    ?>
    <style type="text/css">
    #galerie_edit table {
        display:inline-table;
        table-layout:fixed;
        width:128px;
        margin:4px;
        padding:0px;
    }
    #galerie_edit table td {
        margin:0px;
        border:0px;
        padding:0px;
        white-space: nowrap;
        overflow: hidden;
    }
    #galerie_edit table td.upper {
        height:128px;
    }
    #galerie_edit table td.lower {
        height:24px;
    }
    </style>
    <div id="galerie_edit"></div>
    <input type="hidden" name="galerie" value="<?php echo esc_attr(json_encode($galerie)); ?>" id="galerie_input" />
    <?php
    echo "<script>
    olzImageUpload(\"galerie_edit\", 1, ".json_encode($galerie).", ajaxurl, ".json_encode(array('post_type'=>'galerie', 'post_ID'=>$post->ID)).", function (val) {
        jQuery('#galerie_input').val(JSON.stringify(val));
    });
    </script>";
}

function olz_register_galerie_meta_boxes() {
    add_meta_box('galerie-media', 'Medien', 'olz_galerie_media_meta_box', 'galerie', 'normal', 'high');
}
add_action('add_meta_boxes_galerie', 'olz_register_galerie_meta_boxes');


function olz_ajax_thumb_url_from_attachment_id() {
    function get_img_src($att_id) {
        $arr = wp_get_attachment_image_src($att_id, array(16, 16), true);
        return $arr[0];
    }
    $att_ids = json_decode($_POST['imgIDs']);
    $img_srcs = array_map(get_img_src, $att_ids);
    return array_combine($att_ids, $img_srcs);
}
add_action('wp_ajax_thumb_url_from_attachment_id', olz_ajaxify('olz_ajax_thumb_url_from_attachment_id'));

?>
