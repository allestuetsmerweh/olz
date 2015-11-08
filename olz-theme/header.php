<?php
/**
* The template for displaying the header
*
* Displays all of the head element and everything up until the "site-content" div.
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width">
    <link rel="shortcut icon" href="<?php echo get_stylesheet_directory_uri(); ?>/favicon.ico" />
    <?php wp_head(); ?>
    <script>
        var ajaxurl = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;
    </script>
</head>

<body <?php body_class(); ?>>
    <div class="mainbar">
        <div class="maincontainer">
            <div style="height:1px; margin-bottom:-1px;"></div><!-- Prohibit margin-top collapse -->
