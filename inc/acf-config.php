<?php
/**
 * ACF Configuration
 *
 * Handles ACF JSON save and load points for field synchronization.
 *
 * @package BB_Theme_Child
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set ACF JSON save point to theme's acf-json directory.
 *
 * @param string $path Original save path.
 * @return string Modified save path.
 */
function bb_child_acf_json_save_point( $path ) {
	return get_stylesheet_directory() . '/acf-json';
}
add_filter( 'acf/settings/save_json', 'bb_child_acf_json_save_point' );

/**
 * Add ACF JSON load point from theme's acf-json directory.
 *
 * @param array $paths Array of load paths.
 * @return array Modified array of load paths.
 */
function bb_child_acf_json_load_point( $paths ) {
	// Remove original path.
	unset( $paths[0] );

	// Add theme's acf-json directory.
	$paths[] = get_stylesheet_directory() . '/acf-json';

	return $paths;
}
add_filter( 'acf/settings/load_json', 'bb_child_acf_json_load_point' );
