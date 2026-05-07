<?php

add_shortcode( 'michi_categories', function () {
	$all_url = get_post_type_archive_link( 'michi-product' );
	$button_text = 'All Products';
	$currentFilter = "all";
	if ( is_tax( 'product-category' ) ) {
		$current_term = get_queried_object();
		if ( $current_term && isset( $current_term->name ) ) {
			$button_text = $current_term->name;
			$currentFilter = $current_term->slug;
		}
	}

	$terms = get_terms( [
		'taxonomy' => 'product-category',
		'hide_empty' => true,
	] );

	wp_enqueue_script_module(
		'michi-categories-interactivity',
		get_stylesheet_directory_uri() . '/shortcodes/michi-categories-interactivity.js',
		array( '@wordpress/interactivity' ),
		MICHI_THEME_VERSION
	);

	$context = array(
		'currentLabel' => $button_text,
		'currentFilter' => $currentFilter,
	);

	ob_start();
	?>

	<div id="michi-nav-container" class="michi-categories-wrapper" data-wp-interactive="michi-categories"
		data-wp-class--is-showing="state.isOpen" data-wp-init="callbacks.setupOutsideClick" <?php echo wp_interactivity_data_wp_context( $context ); ?>>
		<button type="button" class="michi-menu-toggle" aria-label="Toggle Categories" data-wp-on--click="actions.toggleMenu">
			<span class="michi-button-label" data-wp-text="state.currentLabel"><?php echo $button_text; ?></span>
		</button>

		<ul id="michi-categories" class="michi-product-categories" data-wp-class--is-loading="state.isFetching">
			<li>
				<a href="<?php echo esc_url( $all_url ); ?>" class="category-pill" data-name="All Products"
					data-wp-on--click="actions.navigate" data-wp-class--is-active="state.isActive"
					data-wp-context='{ "filter": "all", "label": "All Products" }'>
					All Products
				</a>
			</li>

			<?php
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) :
				foreach ( $terms as $term ) :
					$term_link = get_term_link( $term );
					if ( is_wp_error( $term_link ) )
						continue;
					$active_class = ( is_tax( 'product-category', $term->slug ) ) ? 'is-active' : '';
					?>
					<li>
						<a href="<?php echo esc_url( $term_link ); ?>" class="category-pill <?php echo $active_class; ?>"
							data-name="<?php echo esc_attr( $term->name ); ?>" data-wp-on--click="actions.navigate"
							data-wp-class--is-active="state.isActive" data-filter="<?php echo esc_attr( $term->slug ); ?>"
							data-wp-context='{ "filter": "<?php echo esc_attr( $term->slug ); ?>", "label": "<?php echo esc_attr( $term->name ); ?>" }'>
							<?php echo esc_html( $term->name ); ?>
						</a>
					</li>
					<?php
				endforeach;
			endif;
			?>
		</ul>
	</div>

	<?php
	$html = ob_get_clean();
	return function_exists( 'wp_interactivity_process_directives' )
		? wp_interactivity_process_directives( $html )
		: $html;
} );