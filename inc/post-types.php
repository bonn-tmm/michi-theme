<?php
/**
 * Custom Post Types
 *
 * Registers custom post types for the theme.
 *
 * @package BB_Theme_Child
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Michi Dealer Custom Post Type.
 *
 * @return void
 */
function bb_child_register_michi_dealer_cpt() {
	$labels = array(
		'name'                  => _x( 'Michi Dealers', 'Post type general name', 'bb-theme-child' ),
		'singular_name'         => _x( 'Michi Dealer', 'Post type singular name', 'bb-theme-child' ),
		'menu_name'             => _x( 'Michi Dealers', 'Admin Menu text', 'bb-theme-child' ),
		'name_admin_bar'        => _x( 'Michi Dealer', 'Add New on Toolbar', 'bb-theme-child' ),
		'add_new'               => __( 'Add New', 'bb-theme-child' ),
		'add_new_item'          => __( 'Add New Dealer', 'bb-theme-child' ),
		'new_item'              => __( 'New Dealer', 'bb-theme-child' ),
		'edit_item'             => __( 'Edit Dealer', 'bb-theme-child' ),
		'view_item'             => __( 'View Dealer', 'bb-theme-child' ),
		'all_items'             => __( 'All Dealers', 'bb-theme-child' ),
		'search_items'          => __( 'Search Dealers', 'bb-theme-child' ),
		'parent_item_colon'     => __( 'Parent Dealers:', 'bb-theme-child' ),
		'not_found'             => __( 'No dealers found.', 'bb-theme-child' ),
		'not_found_in_trash'    => __( 'No dealers found in Trash.', 'bb-theme-child' ),
		'archives'              => _x( 'Dealer archives', 'The post type archive label used in nav menus.', 'bb-theme-child' ),
		'insert_into_item'      => _x( 'Insert into dealer', 'Overrides the "Insert into post" phrase', 'bb-theme-child' ),
		'uploaded_to_this_item' => _x( 'Uploaded to this dealer', 'Overrides the "Uploaded to this post" phrase', 'bb-theme-child' ),
		'filter_items_list'     => _x( 'Filter dealers list', 'Screen reader text for the filter links', 'bb-theme-child' ),
		'items_list_navigation' => _x( 'Dealers list navigation', 'Screen reader text for the pagination', 'bb-theme-child' ),
		'items_list'            => _x( 'Dealers list', 'Screen reader text for the items list', 'bb-theme-child' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'dealer' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 20,
		'menu_icon'          => 'dashicons-location',
		'show_in_rest'       => true,
		'supports'           => array( 'title', 'editor', 'thumbnail' ),
		'description'        => __( 'Michi authorized dealers and distributors.', 'bb-theme-child' ),
	);

	register_post_type( 'michi_dealer', $args );
}
add_action( 'init', 'bb_child_register_michi_dealer_cpt' );

/**
 * Register Dealer Region Taxonomy.
 *
 * @return void
 */
function bb_child_register_dealer_region_taxonomy() {
	$labels = array(
		'name'                       => _x( 'Regions', 'Taxonomy general name', 'bb-theme-child' ),
		'singular_name'              => _x( 'Region', 'Taxonomy singular name', 'bb-theme-child' ),
		'search_items'               => __( 'Search Regions', 'bb-theme-child' ),
		'popular_items'              => __( 'Popular Regions', 'bb-theme-child' ),
		'all_items'                  => __( 'All Regions', 'bb-theme-child' ),
		'parent_item'                => __( 'Parent Region', 'bb-theme-child' ),
		'parent_item_colon'          => __( 'Parent Region:', 'bb-theme-child' ),
		'edit_item'                  => __( 'Edit Region', 'bb-theme-child' ),
		'update_item'                => __( 'Update Region', 'bb-theme-child' ),
		'add_new_item'               => __( 'Add New Region', 'bb-theme-child' ),
		'new_item_name'              => __( 'New Region Name', 'bb-theme-child' ),
		'separate_items_with_commas' => __( 'Separate regions with commas', 'bb-theme-child' ),
		'add_or_remove_items'        => __( 'Add or remove regions', 'bb-theme-child' ),
		'choose_from_most_used'      => __( 'Choose from the most used regions', 'bb-theme-child' ),
		'not_found'                  => __( 'No regions found.', 'bb-theme-child' ),
		'menu_name'                  => __( 'Regions', 'bb-theme-child' ),
		'back_to_items'              => __( '&larr; Back to Regions', 'bb-theme-child' ),
	);

	$args = array(
		'labels'            => $labels,
		'hierarchical'      => true,
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'dealer-region' ),
	);

	register_taxonomy( 'dealer_region', 'michi_dealer', $args );
}
add_action( 'init', 'bb_child_register_dealer_region_taxonomy' );

/**
 * Allow HTML in dealer_region taxonomy descriptions.
 */
function bb_child_allow_html_dealer_region_description() {
	remove_filter( 'pre_term_description', 'wp_filter_kses' );
	remove_filter( 'term_description', 'wp_kses_data' );
}
add_action( 'init', 'bb_child_allow_html_dealer_region_description' );

/**
 * Replace the plain textarea with a WYSIWYG editor on the dealer_region edit screen.
 *
 * @param WP_Term $term Term object.
 */
function bb_child_dealer_region_edit_description_field( $term ) {
	?>
	<tr class="form-field term-description-wrap">
		<th scope="row"><label for="description"><?php esc_html_e( 'Description', 'bb-theme-child' ); ?></label></th>
		<td>
			<?php
			wp_editor(
				html_entity_decode( $term->description, ENT_QUOTES, 'UTF-8' ),
				'dealer_region_description',
				array(
					'textarea_name' => 'description',
					'textarea_rows' => 10,
					'media_buttons' => true,
					'tinymce'       => true,
					'quicktags'     => true,
				)
			);
			?>
			<p class="description"><?php esc_html_e( 'The description is displayed on the frontend when this region has no dealers.', 'bb-theme-child' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'dealer_region_edit_form_fields', 'bb_child_dealer_region_edit_description_field', 1 );

/**
 * Replace the plain textarea with a WYSIWYG editor on the dealer_region add screen.
 */
function bb_child_dealer_region_add_description_field() {
	?>
	<div class="form-field term-description-wrap">
		<label for="dealer_region_description"><?php esc_html_e( 'Description', 'bb-theme-child' ); ?></label>
		<?php
		wp_editor(
			'',
			'dealer_region_description',
			array(
				'textarea_name' => 'description',
				'textarea_rows' => 7,
				'media_buttons' => true,
				'tinymce'       => true,
				'quicktags'     => true,
			)
		);
		?>
		<p class="description"><?php esc_html_e( 'The description is displayed on the frontend when this region has no dealers.', 'bb-theme-child' ); ?></p>
	</div>
	<?php
}
add_action( 'dealer_region_add_form_fields', 'bb_child_dealer_region_add_description_field', 1 );

/**
 * Hide the default description field on dealer_region screens via CSS/JS.
 */
function bb_child_dealer_region_hide_default_description() {
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
add_action( 'admin_footer', 'bb_child_dealer_region_hide_default_description' );

/**
 * Find the dealer finder page by known slugs or block/shortcode content.
 *
 * @return WP_Post|null
 */
function bb_child_get_dealer_finder_page() {
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
 * Add rewrite rules for SEO-friendly dealer locator URLs.
 *
 * Handles:
 *   /dealer-locator/{country}/
 *   /dealer-locator/{country}/{state}/
 */
function bb_child_dealer_locator_rewrite_rules() {
	$page = bb_child_get_dealer_finder_page();
	if ( ! $page ) {
		return;
	}

	$slug    = $page->post_name;
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
add_action( 'init', 'bb_child_dealer_locator_rewrite_rules' );

/**
 * Register custom query vars for dealer locator routing.
 *
 * @param array $vars Existing query vars.
 * @return array Modified query vars.
 */
function bb_child_dealer_locator_query_vars( $vars ) {
	$vars[] = 'dealer_country';
	$vars[] = 'dealer_state';
	return $vars;
}
add_filter( 'query_vars', 'bb_child_dealer_locator_query_vars' );

/**
 * Flush rewrite rules on theme activation.
 *
 * This ensures permalinks work correctly for the custom post type.
 * Only runs once after theme activation.
 *
 * @return void
 */
function bb_child_flush_rewrite_rules() {
	// Check if we need to flush.
	if ( 'v4' !== get_option( 'bb_child_michi_dealer_flush_rewrite_rules' ) ) {
		flush_rewrite_rules();
		update_option( 'bb_child_michi_dealer_flush_rewrite_rules', 'v4' );
	}
}
add_action( 'init', 'bb_child_flush_rewrite_rules', 999 );
