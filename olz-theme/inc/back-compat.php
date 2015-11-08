<?php
/**
* OLZ back compat functionality
*
* Prevents OLZ from running on WordPress versions prior to 4.1,
* since this theme is not meant to be backward compatible beyond that and
* relies on many newer functions and markup changes introduced in 4.1.
*
* @package WordPress
* @subpackage OLZ-Theme
* @since OLZ-Theme 1.0
*/

/**
 * Prevent switching to OLZ on old versions of WordPress.
 *
 * Switches to the default theme.
 *
 * @since OLZ-Theme 1.0
 */
function olz_switch_theme() {
	switch_theme( WP_DEFAULT_THEME, WP_DEFAULT_THEME );
	unset( $_GET['activated'] );
	add_action( 'admin_notices', 'olz_upgrade_notice' );
}
add_action( 'after_switch_theme', 'olz_switch_theme' );

/**
 * Add message for unsuccessful theme switch.
 *
 * Prints an update nag after an unsuccessful attempt to switch to
 * OLZ on WordPress versions prior to 4.1.
 *
 * @since OLZ-Theme 1.0
 */
function olz_upgrade_notice() {
	$message = sprintf( "OLZ benötigt mindestens WordPress Version 4.1. Die installierte Version ist %s. Bitte aktualisieren Sie WordPress und versuchen Sie es erneut.", $GLOBALS['wp_version'] );
	printf( '<div class="error"><p>%s</p></div>', $message );
}

/**
 * Prevent the Customizer from being loaded on WordPress versions prior to 4.1.
 *
 * @since OLZ-Theme 1.0
 */
function olz_customize() {
	wp_die( sprintf( "OLZ benötigt mindestens WordPress Version 4.1. Die installierte Version ist %s. Bitte aktualisieren Sie WordPress und versuchen Sie es erneut.", $GLOBALS['wp_version'] ), '', array(
		'back_link' => true,
	) );
}
add_action( 'load-customize.php', 'olz_customize' );

/**
 * Prevent the Theme Preview from being loaded on WordPress versions prior to 4.1.
 *
 * @since OLZ-Theme 1.0
 */
function olz_preview() {
	if ( isset( $_GET['preview'] ) ) {
		wp_die( sprintf( "OLZ benötigt mindestens WordPress Version 4.1. Die installierte Version ist %s. Bitte aktualisieren Sie WordPress und versuchen Sie es erneut.", $GLOBALS['wp_version'] ) );
	}
}
add_action( 'template_redirect', 'olz_preview' );
