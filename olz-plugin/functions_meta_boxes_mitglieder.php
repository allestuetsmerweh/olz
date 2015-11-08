<?php


// Mitglieder

$post_type = 'mitglieder';

function olz_mitglieder_save_meta_box_data($post_id) {
    if (isset($_POST['mitglieder_type'])) {
        wp_set_post_terms($post_id, array(intval($_POST['mitglieder_type'])), 'mitglieder-typ');
    }
}
add_save_post_action($post_type, 'olz_mitglieder_save_meta_box_data');

function olz_mitglieder_type_meta_box($post) {
    wp_nonce_field('olz_save_meta_box_data', 'olz_meta_box_nonce');
    $type = wp_get_post_terms($post->ID, 'mitglieder-typ')[0]->term_id;
    echo "<script>
    var type = ".json_encode($type).";
    </script>";
    ?>
    <select id="mitglieder_type" name="mitglieder_type">
        <?php
        $types = get_terms(array('taxonomy'=>'mitglieder-typ', 'hide_empty'=>false));
        foreach ($types as $t) {
            echo "<option value=\"" . $t->term_id . "\">" . $t->name . "</option>";
        }
        ?>
    </select>
    <script>
    jQuery("#mitglieder_type").val(type);
    </script>
    <?php
}

function olz_register_mitglieder_meta_boxes() {
    remove_meta_box('tagsdiv-mitglieder-typ', 'mitglieder', 'side');
    add_meta_box('mitglieder-type', 'Mitglieds-Typ', 'olz_mitglieder_type_meta_box', 'mitglieder', 'normal', 'high');
}
add_action('add_meta_boxes_mitglieder', 'olz_register_mitglieder_meta_boxes');


// Zahlungen

$post_type = 'zahlungen';

function olz_zahlungen_save_meta_box_data($post_id) {
    if (isset($_POST['mitglied']) && isset($_POST['beginn'])) {
        update_post_meta($post_id, 'mitglied', intval($_POST['mitglied']));
        update_post_meta($post_id, 'beginn', date('Y-m-d', strtotime($_POST['beginn'])));
    }
}
add_save_post_action($post_type, 'olz_zahlungen_save_meta_box_data');

function olz_zahlungen_mitglied_meta_box($post) {
    wp_nonce_field('olz_save_meta_box_data', 'olz_meta_box_nonce');
    $mitglied = get_post_meta($post->ID, 'mitglied', true);
    echo "<script>
    var mitglied = ".json_encode($mitglied).";
    </script>";
    ?>
    <select id="mitglied" name="mitglied">
        <?php
        $q = new WP_Query(array('post_type'=>'mitglieder', 'posts_per_page'=>-1));
        foreach ($q->posts as $p) {
            echo "<option value=\"" . $p->ID . "\">" . $p->post_title . "</option>";
        }
        ?>
    </select>
    <script>
    jQuery("#mitglied").val(mitglied);
    </script>
    <?php
}

function olz_zahlungen_beginn_meta_box($post) {
    wp_nonce_field('olz_save_meta_box_data', 'olz_meta_box_nonce');
    $beginn = date('Y-m-d', strtotime(get_post_meta($post->ID, 'beginn', true)));
    echo "<script>
    var beginn = ".json_encode($beginn).";
    </script>";
    ?>
    <input type="date" id="beginn" name="beginn" />
    <script>
    jQuery("#beginn").val(beginn);
    </script>
    <?php
}

function olz_register_zahlungen_meta_boxes() {
    add_meta_box('zahlungen-mitglied', 'Mitglied', 'olz_zahlungen_mitglied_meta_box', 'zahlungen', 'normal', 'high');
    add_meta_box('zahlungen-beginn', 'Beitragsbeginn', 'olz_zahlungen_beginn_meta_box', 'zahlungen', 'normal', 'high');
}
add_action('add_meta_boxes_zahlungen', 'olz_register_zahlungen_meta_boxes');


function olz_zahlungen_posts_columns($defaults) {
    $arr = array();
    $arr['cb'] = $defaults['cb'];
    $arr['zahlungen-mitglied'] = "Mitglied";
    $arr['zahlungen-beginn'] = "Beginn-Datum";
    foreach ($defaults as $key=>$value) {
        if ($key!='cb' && $key!='title') $arr[$key] = $value;
    }
    return $arr;
}
function olz_zahlungen_posts_custom_columns($column_name, $id) {
    if ($column_name=='zahlungen-mitglied') {
        echo get_post(get_post_meta($id, 'mitglied', true))->post_title;
    } else if ($column_name=='zahlungen-beginn') {
        echo get_post_meta($id, 'beginn', true);
    }
}
add_filter('manage_zahlungen_posts_columns', 'olz_zahlungen_posts_columns', 5);
add_action('manage_zahlungen_posts_custom_column', 'olz_zahlungen_posts_custom_columns', 5, 2);

?>
