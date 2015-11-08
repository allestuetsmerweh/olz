<?php
/**
* @package OLZ-Plugin
* @version 1.0
*/
/*
Plugin Name: OLZ-Plugin
Description: This is the WordPress plugin that mimics and improves the old olzimmerberg.ch's functionality.
Author: Simon Hatt
Version: 1.0
Author URI: http://hatt.style/
*/


$month_names = array("Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");
function olz_ajaxify($func) {
    return function () use ($func) {
        wp_die(json_encode(call_user_func($func)));
    };
}


// OLZ Admin CSS

function olz_admin_css() {
    wp_enqueue_style('admin-styles', plugins_url('/css/olz_admin.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'olz_admin_css');

function olz_admin_disable_color_schemes() {
    global $_wp_admin_css_colors;
    remove_all_actions('admin_color_scheme_picker');
    $_wp_admin_css_colors = array();
}
add_action('admin_init', 'olz_admin_disable_color_schemes');


// OLZ Post Types

function olz_deregister_post_type() {
    remove_menu_page('edit.php');
}
add_action('admin_menu', 'olz_deregister_post_type');

require_once(dirname(__FILE__).'/functions_register_post_types.php');


// Meta Boxes

$save_post_actions = array();
function add_save_post_action($post_type, $fnname) {
    global $save_post_actions;
    if (!isset($save_post_actions[$post_type])) {
        $save_post_actions[$post_type] = array();
    }
    $save_post_actions[$post_type][] = $fnname;
}
function olz_save_meta_box_data($post_id) {
    global $save_post_actions;
    if (!isset($_POST['olz_meta_box_nonce'])) return;
    if (!wp_verify_nonce($_POST['olz_meta_box_nonce'], 'olz_save_meta_box_data')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $post_type = get_post_type($post_id) ?: $_POST['post_type'];
    if ($post_type === 'page') {
        if (!current_user_can('edit_page', $post_id)) return;
    } else {
        if (!current_user_can('edit_post', $post_id)) return;
    }
    $actions = $save_post_actions[$post_type];
    if ($actions) {
        for ($i=0; $i<count($actions); $i++) {
            call_user_func($actions[$i], $post_id);
        }
    }
}
add_action('save_post', 'olz_save_meta_box_data');


// JS Scripts

function olz_plugin_scripts() {
    wp_enqueue_script('olz_image_upload', plugins_url('/js/olz_image_upload.js', __FILE__));
}
add_action('wp_enqueue_scripts', 'olz_plugin_scripts');
function olz_plugin_admin_scripts() {
    wp_enqueue_script('olz_image_upload', plugins_url('/js/olz_image_upload.js', __FILE__));
    wp_enqueue_script('olz_admin_js', plugins_url('/js/olz_admin.js', __FILE__));
    wp_enqueue_script('qrcodejs', plugins_url('/js/lib/qrcodejs/qrcode.min.js', __FILE__));
}
add_action('admin_enqueue_scripts', 'olz_plugin_admin_scripts');


// JS Globals

function olz_javascript_globals() {
    global $month_names;
    echo "<script>
    window.olzImageUploadMaxWid = ".json_encode(get_option('large_size_w')).";
    window.olzImageUploadMaxHei = ".json_encode(get_option('large_size_h')).";
    window.olzThemeURL = ".json_encode(get_template_directory_uri()).";
    window.monthNames = ".json_encode($month_names).";
    </script>";
}
add_action('wp_head', 'olz_javascript_globals');
add_action('admin_head', 'olz_javascript_globals');


// Includes

require_once(dirname(__FILE__).'/functions_meta_boxes_aktuell.php');
require_once(dirname(__FILE__).'/functions_meta_boxes_bild_der_woche.php');
require_once(dirname(__FILE__).'/functions_meta_boxes_galerie.php');
require_once(dirname(__FILE__).'/functions_meta_boxes_karten.php');
require_once(dirname(__FILE__).'/functions_meta_boxes_mitglieder.php');
require_once(dirname(__FILE__).'/functions_meta_boxes_termine.php');
require_once(dirname(__FILE__).'/functions_meta_boxes_texte.php');

require_once(dirname(__FILE__).'/functions_organigramm.php');

require_once(dirname(__FILE__).'/functions_file_manager.php');

require_once(dirname(__FILE__).'/functions_user_profile.php');

require_once(dirname(__FILE__).'/functions_tinymce.php');

require_once(dirname(__FILE__).'/functions_email.php');

?>
