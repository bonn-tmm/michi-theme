<?php
/**
 * Enqueue Scripts & Styles
 *
 * Handles enqueueing of theme styles and scripts.
 *
 * @package BB_Theme_Child
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue child theme stylesheet with cache busting.
 *
 * @return void
 */
function bb_child_enqueue_styles() {
	// Get the absolute path to the child theme style.css for cache busting.
	$theme_path = get_stylesheet_directory() . '/style.css';

	wp_enqueue_style(
		'child-style',
		get_stylesheet_uri(),
		array( 'fl-automator-skin' ),
		file_exists( $theme_path ) ? filemtime( $theme_path ) : '1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'bb_child_enqueue_styles' );
