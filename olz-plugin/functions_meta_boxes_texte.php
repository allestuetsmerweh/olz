<?php

$post_type = 'texte';

function olz_texte_save_meta_box_data($post_id) {
    update_meta_from_dict($post_id, 'ident', $_POST, null, true);
}
add_save_post_action($post_type, 'olz_texte_save_meta_box_data');

function olz_texte_ident_meta_box($post) {
    wp_nonce_field('olz_save_meta_box_data', 'olz_meta_box_nonce');
    $ident = strtotime(get_post_meta($post->ID, 'ident', true));
    echo "<script>
    var ident = ".json_encode($ident).";
    </script>";
    ?>
    <input type='text' id='texte_ident' name='ident' value='' />
    <script>
    jQuery("#texte_ident").val(ident);
    </script>
    <?php
}

function olz_register_texte_meta_boxes() {
    add_meta_box('texte-ident', 'Identifikation', 'olz_texte_ident_meta_box', 'texte', 'normal', 'high');
}
add_action('add_meta_boxes_texte', 'olz_register_texte_meta_boxes');

?>
