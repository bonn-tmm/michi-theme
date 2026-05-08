<?php
/**
 * Autoloader for theme
 */

function autoloader( $class ) {
	// Replace namespace separator with directory separator

	if ( strpos( $class, MICHI_THEME ) !== 0 ) {
		return false;
	}

	$class = str_replace( MICHI_THEME . '\\', '', $class );

	// Construct the full path to the class file
	$class_path = MICHI_THEME_CLASS_PATH . '/' . $class . '.php';

	// Check if the file exists before including
	if ( file_exists( $class_path ) ) {
		require $class_path;
	}
}

spl_autoload_register( 'autoloader' );

$instances = array(
	'ACFConfig',
	'RestApi',
	'DealerFinder',
	'PostTypes',
	'DealerMigration',
	'Enqueue',
	'Categories',
	'Slider',
	'ProductGrid',
);

foreach ( $instances as $instance ) {
	$class = 'MICHI_TMM\\' . $instance;
	$class::get_instance();
}