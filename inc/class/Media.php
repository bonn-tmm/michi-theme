<?php

namespace MICHI_TMM;
use WP_Post;
class Media {
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
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_video_url_field' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save_video_url_field' ), 10, 2 );

	}

	/**
	 * Add "Video URL" field to Media library
	 */
	public function add_video_url_field( array $form_fields, WP_Post $post ) {
		$form_fields['video_url'] = array(
			'label' => 'Video URL',
			'input' => 'text',
			'value' => get_post_meta( $post->ID, 'video_url', true ),
			'helps' => 'Enter the full URL for the video (YouTube, Vimeo, or MP4)',
		);
		return $form_fields;
	}

	/**
	 * Save "Video URL" field value
	 */
	public function save_video_url_field( array $post, array $attachment ) {
		if ( isset( $attachment['video_url'] ) ) {
			update_post_meta( $post['ID'], 'video_url', esc_url_raw( $attachment['video_url'] ) );
		}
		return $post;
	}
}