<?php

namespace MICHI_TMM;

class Enqueue {
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	public function enqueue_styles() {
		$theme_path = MICHI_THEME_DIR . '/style.css';
		wp_enqueue_style(
			'child-style',
			MICHI_THEME_URL . '/style.css',
			array( 'fl-automator-skin' ),
			file_exists( $theme_path ) ? filemtime( $theme_path ) : '1.0.0'
		);
	}
}