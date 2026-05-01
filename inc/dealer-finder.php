<?php
/**
 * Dealer Finder Block & Shortcode
 *
 * Handles the Michi dealer finder block registration and shortcode.
 *
 * @package BB_Theme_Child
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Michi Dealer Finder Block.
 *
 * @return void
 */
function bb_child_register_dealer_finder_block() {
	$block_path = get_stylesheet_directory() . '/blocks/dealer-finder';

	// Only register if block.json exists.
	if ( file_exists( $block_path . '/block.json' ) ) {
		$result = register_block_type( $block_path );

		// Debug logging if WP_DEBUG is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			if ( $result ) {
				error_log( 'Michi Dealer Finder block registered successfully: ' . $result->name );
			} else {
				error_log( 'ERROR: Failed to register Michi Dealer Finder block at: ' . $block_path );
			}
		}
	}
}
add_action( 'init', 'bb_child_register_dealer_finder_block' );

/**
 * Register Michi Dealer Finder Shortcode.
 *
 * Usage: [michi_dealer_finder heading="Find a Dealer" show_sidebar="yes"]
 *
 * @param array $atts Shortcode attributes.
 * @return string Shortcode output HTML.
 */
function bb_child_dealer_finder_shortcode( $atts ) {
	// Parse shortcode attributes with defaults.
	$atts = shortcode_atts(
		array(
			'heading'      => __( 'Find a Dealer by Country & State/Region', 'bb-theme-child' ),
			'subheading'   => __( 'Select your state from the list below to jump directly to available authorized dealers.', 'bb-theme-child' ),
			'show_sidebar' => 'yes',
		),
		$atts,
		'michi_dealer_finder'
	);

	// Sanitize attributes.
	$heading      = sanitize_text_field( $atts['heading'] );
	$subheading   = sanitize_text_field( $atts['subheading'] );
	$show_sidebar = in_array( strtolower( $atts['show_sidebar'] ), array( 'yes', '1', 'true' ), true );

	// Enqueue scripts and styles.
	bb_child_enqueue_dealer_finder_assets();

	// Build the HTML output.
	ob_start();
	?>
	<div class="michi-dealer-finder">
		<div class="dealer-finder-content">
			<?php if ( $show_sidebar ) : ?>
			<aside class="dealer-states-sidebar">
				<div class="dealer-finder-filters">
					<div class="filter-group">
						<label for="country-select"><?php esc_html_e( 'CHOOSE A COUNTRY', 'bb-theme-child' ); ?></label>
						<select id="country-select">
							<option value=""><?php esc_html_e( 'Select a country', 'bb-theme-child' ); ?></option>
						</select>
					</div>
				</div>
				<h3><?php esc_html_e( 'STATE/REGION', 'bb-theme-child' ); ?></h3>
				<ul id="states-list"></ul>
			</aside>
			<?php endif; ?>
			<div class="dealer-results">
				<div id="dealer-listings"></div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'michi_dealer_finder', 'bb_child_dealer_finder_shortcode' );

/**
 * Enqueue Dealer Finder assets (styles and scripts).
 *
 * @return void
 */
function bb_child_enqueue_dealer_finder_assets() {
	static $enqueued = false;

	// Prevent double-enqueueing.
	if ( $enqueued ) {
		return;
	}

	$block_path = get_stylesheet_directory() . '/blocks/dealer-finder';
	$block_url  = get_stylesheet_directory_uri() . '/blocks/dealer-finder';

	// Enqueue styles.
	if ( file_exists( $block_path . '/src/style.css' ) ) {
		wp_enqueue_style(
			'michi-dealer-finder-style',
			$block_url . '/src/style.css',
			array(),
			filemtime( $block_path . '/src/style.css' )
		);
	}

	// Enqueue view script.
	if ( file_exists( $block_path . '/src/view.js' ) ) {
		wp_enqueue_script(
			'michi-dealer-finder-view',
			$block_url . '/src/view.js',
			array(),
			filemtime( $block_path . '/src/view.js' ),
			true
		);
	}

	$enqueued = true;
}

/**
 * Output dealer finder config as inline script.
 */
function bb_child_dealer_finder_inline_config() {
	$page = bb_child_get_dealer_finder_page();
	if ( ! $page || ! is_page( $page->ID ) ) {
		return;
	}

	$data = wp_json_encode( array(
		'baseUrl'        => '/' . $page->post_name,
		'initialCountry' => sanitize_text_field( get_query_var( 'dealer_country', '' ) ),
		'initialState'   => sanitize_text_field( get_query_var( 'dealer_state', '' ) ),
	) );

	echo '<script>var michiDealerFinder = ' . $data . ';</script>' . "\n";
}
add_action( 'wp_head', 'bb_child_dealer_finder_inline_config', 1 );

/**
 * Auto-enqueue dealer finder assets when shortcode is detected in content.
 *
 * @param string $content Post content.
 * @return string Unchanged content.
 */
function bb_child_auto_enqueue_dealer_finder( $content ) {
	if ( has_shortcode( $content, 'michi_dealer_finder' ) ) {
		bb_child_enqueue_dealer_finder_assets();
	}
	return $content;
}
add_filter( 'the_content', 'bb_child_auto_enqueue_dealer_finder' );
