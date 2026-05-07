<?php

namespace MICHI_TMM;
use WP_Term;

class PostTypes {
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

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_michi_dealer_cpt' ) );
		add_action( 'init', array( $this, 'register_dealer_region_taxonomy' ) );
		add_action( 'init', array( $this, 'allow_html_dealer_region_description' ) );
		add_action( 'dealer_region_edit_form_fields', array( $this, 'dealer_region_edit_description_field' ), 1 );
		add_action( 'dealer_region_add_form_fields', array( $this, 'dealer_region_add_description_field' ), 1 );
		add_action( 'init', array( $this, 'dealer_locator_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'dealer_locator_query_vars' ) );
		add_action( 'init', array( $this, 'flush_rewrite_rules' ), 999 );
		add_action( 'admin_footer', array( $this, 'dealer_region_hide_default_description' ) );
	}

	/**
	 * Register the Michi Dealer CPT
	 */
	public function register_michi_dealer_cpt() {
		$labels = array(
			'name' => _x( 'Michi Dealers', 'Post type general name', 'michi-theme' ),
			'singular_name' => _x( 'Michi Dealer', 'Post type singular name', 'michi-theme' ),
			'menu_name' => _x( 'Michi Dealers', 'Admin Menu text', 'michi-theme' ),
			'name_admin_bar' => _x( 'Michi Dealer', 'Add New on Toolbar', 'michi-theme' ),
			'add_new' => __( 'Add New', 'michi-theme' ),
			'add_new_item' => __( 'Add New Dealer', 'michi-theme' ),
			'new_item' => __( 'New Dealer', 'michi-theme' ),
			'edit_item' => __( 'Edit Dealer', 'michi-theme' ),
			'view_item' => __( 'View Dealer', 'michi-theme' ),
			'all_items' => __( 'All Dealers', 'michi-theme' ),
			'search_items' => __( 'Search Dealers', 'michi-theme' ),
			'parent_item_colon' => __( 'Parent Dealers:', 'michi-theme' ),
			'not_found' => __( 'No dealers found.', 'michi-theme' ),
			'not_found_in_trash' => __( 'No dealers found in Trash.', 'michi-theme' ),
			'archives' => _x( 'Dealer archives', 'The post type archive label used in nav menus.', 'michi-theme' ),
			'insert_into_item' => _x( 'Insert into dealer', 'Overrides the "Insert into post" phrase', 'michi-theme' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this dealer', 'Overrides the "Uploaded to this post" phrase', 'michi-theme' ),
			'filter_items_list' => _x( 'Filter dealers list', 'Screen reader text for the filter links', 'michi-theme' ),
			'items_list_navigation' => _x( 'Dealers list navigation', 'Screen reader text for the pagination', 'michi-theme' ),
			'items_list' => _x( 'Dealers list', 'Screen reader text for the items list', 'michi-theme' ),
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'dealer' ),
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'menu_position' => 20,
			'menu_icon' => 'dashicons-location',
			'show_in_rest' => true,
			'supports' => array( 'title', 'editor', 'thumbnail' ),
			'description' => __( 'Michi authorized dealers and distributors.', 'michi-theme' ),
		);

		register_post_type( 'michi_dealer', $args );
	}

	/**
	 * Register the Dealer Region Taxonomy
	 */
	public function register_dealer_region_taxonomy() {
		$labels = array(
			'name' => _x( 'Regions', 'Taxonomy general name', 'michi-theme' ),
			'singular_name' => _x( 'Region', 'Taxonomy singular name', 'michi-theme' ),
			'search_items' => __( 'Search Regions', 'michi-theme' ),
			'popular_items' => __( 'Popular Regions', 'michi-theme' ),
			'all_items' => __( 'All Regions', 'michi-theme' ),
			'parent_item' => __( 'Parent Region', 'michi-theme' ),
			'parent_item_colon' => __( 'Parent Region:', 'michi-theme' ),
			'edit_item' => __( 'Edit Region', 'michi-theme' ),
			'update_item' => __( 'Update Region', 'michi-theme' ),
			'add_new_item' => __( 'Add New Region', 'michi-theme' ),
			'new_item_name' => __( 'New Region Name', 'michi-theme' ),
			'separate_items_with_commas' => __( 'Separate regions with commas', 'michi-theme' ),
			'add_or_remove_items' => __( 'Add or remove regions', 'michi-theme' ),
			'choose_from_most_used' => __( 'Choose from the most used regions', 'michi-theme' ),
			'not_found' => __( 'No regions found.', 'michi-theme' ),
			'menu_name' => __( 'Regions', 'michi-theme' ),
			'back_to_items' => __( '&larr; Back to Regions', 'michi-theme' ),
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => true,
			'public' => true,
			'show_ui' => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_in_rest' => true,
			'rewrite' => array( 'slug' => 'dealer-region' ),
		);

		register_taxonomy( 'dealer_region', 'michi_dealer', $args );
	}

	/**
	 * Allow HTML in dealer_region taxonomy descriptions
	 */
	public function allow_html_dealer_region_description() {
		remove_filter( 'pre_term_description', 'wp_filter_kses' );
		remove_filter( 'term_description', 'wp_kses_data' );
	}

	/**
	 * Edit the dealer_region taxonomy description field
	 */
	public function dealer_region_edit_description_field( WP_Term $term ) {
		?>
<tr class="form-field term-description-wrap">
  <th scope="row"><label for="description">
      <?php esc_html_e( 'Description', 'michi-theme' ); ?>
    </label></th>
  <td>
    <?php
				wp_editor(
					html_entity_decode( $term->description, ENT_QUOTES, 'UTF-8' ),
					'dealer_region_description',
					array(
						'textarea_name' => 'description',
						'textarea_rows' => 10,
						'media_buttons' => true,
						'tinymce' => true,
						'quicktags' => true,
					)
				);
				?>
    <p class="description">
      <?php esc_html_e( 'The description is displayed on the frontend when this region has no dealers.', 'michi-theme' ); ?>
    </p>
  </td>
</tr>
<?php
	}

	/**
	 * Add the dealer_region taxonomy description field
	 */
	public function dealer_region_add_description_field() {
		?>
<div class="form-field term-description-wrap">
  <label for="dealer_region_description">
    <?php esc_html_e( 'Description', 'michi-theme' ); ?>
  </label>
  <?php
			wp_editor(
				'',
				'dealer_region_description',
				array(
					'textarea_name' => 'description',
					'textarea_rows' => 7,
					'media_buttons' => true,
					'tinymce' => true,
					'quicktags' => true,
				)
			);
			?>
  <p class="description">
    <?php esc_html_e( 'The description is displayed on the frontend when this region has no dealers.', 'michi-theme' ); ?>
  </p>
</div>
<?php
	}

	/**
	 * Flush rewrite rules
	 */
	public function flush_rewrite_rules() {
		if ( 'v4' !== get_option( 'bb_child_michi_dealer_flush_rewrite_rules' ) ) {
			flush_rewrite_rules();
			update_option( 'bb_child_michi_dealer_flush_rewrite_rules', 'v4' );
		}
	}

	/**
	 * Add dealer_country and dealer_state query vars
	 */
	public function dealer_locator_query_vars( array $vars ): array {
		$vars[] = 'dealer_country';
		$vars[] = 'dealer_state';
		return $vars;
	}

	/**
	 * Get the Dealer Finder page
	 */
	public function get_dealer_finder_page() {
		$slugs = array( 'dealer-locator', 'dealers', 'dealer-finder' );
		foreach ( $slugs as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page && 'publish' === $page->post_status ) {
				return $page;
			}
		}
		return null;
	}

	/**
	 * Add rewrite rules for the Dealer Finder page
	 */
	public function dealer_locator_rewrite_rules() {
		$page = $this->get_dealer_finder_page();
		if ( ! $page ) {
			return;
		}

		$slug = $page->post_name;
		$page_id = $page->ID;

		add_rewrite_rule(
			'^' . preg_quote( $slug, '/' ) . '/([^/]+)/([^/]+)/?$',
			'index.php?page_id=' . $page_id . '&dealer_country=$matches[1]&dealer_state=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'^' . preg_quote( $slug, '/' ) . '/([^/]+)/?$',
			'index.php?page_id=' . $page_id . '&dealer_country=$matches[1]',
			'top'
		);

	}

	/**
	 * Hide the default description field on dealer_region screens via CSS/JS.
	 */
	public function dealer_region_hide_default_description() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-dealer_region' !== $screen->id ) {
			return;
		}
		?>
<script>
jQuery(document).ready(function($) {
  // Edit screen: hide the default description row
  $('textarea#description').closest('.form-field').remove();
  // Add screen: hide the default description wrapper
  $('.term-description-wrap textarea#tag-description').closest('.form-field').remove();
});
</script>
<?php
	}

}