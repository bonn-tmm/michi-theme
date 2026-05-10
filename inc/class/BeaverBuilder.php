<?php
namespace MICHI_TMM;

class BeaverBuilder {
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
		add_filter( 'fl_builder_register_module_settings_form', array( $this, 'box_register_module_settings_form' ), 10, 2 );
		add_filter( 'fl_builder_module_attributes', array( $this, 'box_module_attributes' ), 10, 2 );
	}

	public function box_register_module_settings_form( $form, $slug ) {
		if ( 'box' !== $slug || ! isset( $form['general']['sections'] ) || ! is_array( $form['general']['sections'] ) ) {
			return $form;
		}

		$form['general']['sections']['wp_interactivity'] = [
			'title' => __( 'Interactivity', 'your-textdomain' ),
			'fields' => [
				'wp_interactive_id' => [
					'label' => __( 'Interactive ID', 'your-textdomain' ),
					'type' => 'text',
					'default' => '',
				],
				'wp_router_region' => [
					'label' => __( 'Router Region', 'your-textdomain' ),
					'type' => 'text',
					'default' => '',
				],
			],
		];

		return $form;
	}

	public function box_module_attributes( $attrs, $module ) {
		if ( 'box' !== $module->slug || empty( $module->settings ) ) {
			return $attrs;
		}

		$s = $module->settings;

		if ( ! empty( $s->wp_interactive_id ) ) {
			$attrs['data-wp-interactive'] = esc_attr( $s->wp_interactive_id );
		}
		if ( ! empty( $s->wp_router_region ) ) {
			$attrs['data-wp-router-region'] = esc_attr( $s->wp_router_region );
		}

		return $attrs;
	}
}