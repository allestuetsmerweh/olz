<?php

$post_type = 'bild_der_woche';

function olz_bild_der_woche_save_meta_box_data($post_id) {
}
add_save_post_action($post_type, 'olz_bild_der_woche_save_meta_box_data');

function olz_register_bild_der_woche_meta_boxes() {
}
add_action('add_meta_boxes_bild_der_woche', 'olz_register_bild_der_woche_meta_boxes');


function olz_bild_der_woche_posts_columns($defaults) {
    $arr = array();
    $arr['cb'] = $defaults['cb'];
    $arr['bild_der_woche-img'] = "Bild";
    foreach ($defaults as $key=>$value) {
        if ($key!='cb') $arr[$key] = $value;
    }
    return $arr;
}
function olz_bild_der_woche_posts_custom_columns($column_name, $id) {
    if ($column_name=='bild_der_woche-img') {
        print_r(get_the_post_thumbnail($id, array(75,75)));
    }
}
function olz_bild_der_woche_posts_sorting($columns) {
    $columns['termine-date'] = 'termine-date';
    return $columns;
}
function olz_bild_der_woche_posts_orderby($vars) {
    if (isset($vars['orderby'])) {
        if ($vars['orderby']=='termine-date') {
            $vars = array_merge($vars, array(
                'orderby'=>'meta_value',
                'meta_key'=>'timerange_start',
            ));
        }
    }
    return $vars;
}
function olz_bild_der_woche_posts_css() {
  echo '<style>
    .column-bild_der_woche-img {
        width:75px;
    }
  </style>';
}
add_filter('manage_bild_der_woche_posts_columns', 'olz_bild_der_woche_posts_columns', 5);
add_action('manage_bild_der_woche_posts_custom_column', 'olz_bild_der_woche_posts_custom_columns', 5, 2);
add_filter('manage_edit-bild_der_woche_sortable_columns', 'olz_bild_der_woche_posts_sorting');
add_filter('request', 'olz_bild_der_woche_posts_orderby');
add_action('admin_head', 'olz_bild_der_woche_posts_css');

?>
