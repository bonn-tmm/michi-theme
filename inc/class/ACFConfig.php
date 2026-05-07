<?php

namespace MICHI_TMM;

class ACFConfig {
	/**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;
	/**
	 * Instance Control
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	public function __construct() {
		add_filter( 'acf/settings/save_json', array( $this, 'acf_json_save_point' ) );
		add_filter( 'acf/settings/load_json', array( $this, 'acf_json_load_point' ) );
	}

	public function acf_json_save_point( $path ) {
		return get_stylesheet_directory() . '/acf-json';
	}

	public function acf_json_load_point( $paths ) {
		// Remove original path.
		unset( $paths[0] );

		// Add theme's acf-json directory.
		$paths[] = MICHI_THEME_DIR . '/acf-json';

		return $paths;
	}


}