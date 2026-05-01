<?php
/**
 * Beaver Builder Child Theme
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * @link https://docs.wpbeaverbuilder.com/
 *
 * @package BB_Theme_Child
 * @version 1.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$theme_version = wp_get_theme()->get( 'Version' );

define( 'FL_CHILD_THEME_DIR', get_stylesheet_directory() );
define( 'FL_CHILD_THEME_URL', get_stylesheet_directory_uri() );
define( 'MICHI_THEME_VERSION', $theme_version );

// Updater
require_once get_stylesheet_directory() . '/updater/updater.php';

use TMM\PluginUpdateChecker\v5\PucFactory;

$update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/bonn-tmm/michi-theme',
	__FILE__,
	'michi-theme'
);

$update_checker->setBranch( 'master' );


/**
 * Load custom modules.
 *
 * All customizations are organized into separate files in the /inc directory
 * for better organization, security, and maintainability.
 */
require_once get_stylesheet_directory() . '/inc/enqueue.php';
require_once get_stylesheet_directory() . '/inc/acf-config.php';
require_once get_stylesheet_directory() . '/inc/post-types.php';
require_once get_stylesheet_directory() . '/inc/rest-api.php';
require_once get_stylesheet_directory() . '/inc/dealer-finder.php';
require_once get_stylesheet_directory() . '/inc/dealer-migration.php';
require_once get_stylesheet_directory() . '/slider/slider.php';