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

define( 'MICHI_THEME_DIR', get_stylesheet_directory() );
define( 'MICHI_THEME_URL', get_stylesheet_directory_uri() );
define( 'MICHI_THEME_VERSION', $theme_version );
define( 'MICHI_THEME_CLASS_PATH', untrailingslashit( get_stylesheet_directory() . '/inc/class' ) );
define( 'MICHI_THEME', 'MICHI_TMM' );

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
require MICHI_THEME_DIR . '/inc/autoloader.php';