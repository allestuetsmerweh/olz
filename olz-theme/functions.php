<?php
/**
* OLZ Theme was created on top of Wordpress' Twenty Fifteen Theme.
* OLZ functions and definitions
*
* Set up the theme and provides some helper functions, which are used in the
* theme as custom template tags. Others are attached to action and filter
* hooks in WordPress to change core functionality.
*
* When using a child theme you can override certain functions (those wrapped
* in a function_exists() call) by defining them first in your child theme's
* functions.php file. The child theme's functions.php file is included before
* the parent theme's file, so the child theme functions would be used.
*
* @link https://codex.wordpress.org/Theme_Development
* @link https://codex.wordpress.org/Child_Themes
*
* Functions that are not pluggable (not wrapped in function_exists()) are
* instead attached to a filter or action hook.
*
* For more information on hooks, actions, and filters,
* {@link https://codex.wordpress.org/Plugin_API}
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/

/**
* Set the content width based on the theme's design and stylesheet.
*
* @since OLZ-Theme 1.0
*/
if (!isset($content_width)) {
    $content_width = 660;
}

/**
* OLZ only works in WordPress 4.1 or later.
* @todo Is this true/working?
*/
if (version_compare($GLOBALS['wp_version'], '4.1-alpha', '<')) {
    require_once(get_template_directory().'/inc/back-compat.php');
}

if (!function_exists('olz_ajaxify')) {
    function olz_ajaxify($func) {
        return function () use ($func) {
            wp_die(json_encode(call_user_func($func)));
        };
    }
}

if (! function_exists('olz_setup')) :
    /**
    * Sets up theme defaults and registers support for various WordPress features.
    *
    * Note that this function is hooked into the after_setup_theme hook, which
    * runs before the init hook. The init hook is too late for some features, such
    * as indicating support for post thumbnails.
    *
    * @since OLZ-Theme 1.0
    */
    function olz_setup() {

        /*
        * Make theme available for translation.
        * Translations can be filed in the /languages/ directory.
        * If you're building a theme based on OLZ, use a find and replace
        * to change 'OLZ' to the name of your theme in all the template files
        */
        load_theme_textdomain('olz', get_template_directory() . '/languages');

        // Add default posts and comments RSS feed links to head.
        add_theme_support('automatic-feed-links');

        /*
        * Let WordPress manage the document title.
        * By adding theme support, we declare that this theme does not use a
        * hard-coded <title> tag in the document head, and expect WordPress to
        * provide it for us.
        */
        add_theme_support('title-tag');

        /*
        * Enable support for Post Thumbnails on posts and pages.
        *
        * See: https://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
        */
        add_theme_support('post-thumbnails');
        set_post_thumbnail_size(825, 510, true);

        // This theme uses wp_nav_menu() in two locations.
        register_nav_menus(array(
            'primary' => "Hauptmenu",
        ));

        /*
        * Switch default core markup for search form, comment form, and comments
        * to output valid HTML5.
        */
        add_theme_support('html5', array(
            'search-form', 'comment-form', 'comment-list', 'gallery', 'caption'
        ));

        /*
        * Enable support for Post Formats.
        *
        * See: https://codex.wordpress.org/Post_Formats
        */
        add_theme_support('post-formats', array(
            'aside', 'image', 'video', 'quote', 'link', 'gallery', 'status', 'audio', 'chat'
        ));
    }
endif; // olz_setup
add_action('after_setup_theme', 'olz_setup');

/**
* JavaScript Detection.
*
* Adds a `js` class to the root `<html>` element when JavaScript is detected.
*
* @since OLZ-Theme 1.0
*/
function olz_javascript_detection() {
    echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
}
add_action('wp_head', 'olz_javascript_detection', 0);

/**
* Enqueue scripts and styles.
*
* @since OLZ-Theme 1.0
*/
function olz_theme_scripts() {
    // Load our main stylesheet.
    wp_enqueue_style('swipebox', get_template_directory_uri().'/lib/swipebox/css/swipebox.css');
    wp_enqueue_style('olz-style', get_stylesheet_uri(), array('dashicons'));
    // Load our main scripts.
    if (is_singular()) wp_enqueue_script("comment-reply");
    wp_enqueue_script('swipebox', get_template_directory_uri().'/lib/swipebox/js/jquery.swipebox.js', array('jquery'), '20150330', true);
    wp_enqueue_script('olz-script', get_template_directory_uri().'/js/functions.js', array('jquery'), '20150330', true);
}
add_action('wp_enqueue_scripts', 'olz_theme_scripts');

/**
* Enqueue admin scripts and styles.
*
* @since OLZ-Theme 1.0
*/
function olz_admin_scripts() {
    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'olz_admin_scripts');

require_once(dirname(__FILE__).'/functions_olz.php');

?>
