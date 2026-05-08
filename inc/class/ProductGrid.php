<?php

namespace MICHI_TMM;

use WP_Query;

class ProductGrid {
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
		add_shortcode( 'michi_product_grid', array( $this, 'product_grid_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets() {
		wp_register_style(
			'michi-product-grid-style',
			MICHI_THEME_URL . '/assets/product-grid/style.css',
			array(),
			MICHI_THEME_VERSION
		);
	}
	public function product_grid_shortcode( array|string|null $atts ) {
		$params = shortcode_atts( array(
			'items' => -1,
		), $atts, 'michi_product_grid' );

		global $wp_query;

		// 1. Determine the Query Source
		if ( is_archive() || is_home() || is_search() ) {
			// Use the existing global query from the archive page
			$query = $wp_query;
		} else {
			// Use ACF Relationship field for specific pages
			$product_collection = get_field( 'product_collection' );

			if ( ! $product_collection ) {
				return '';
			}

			$product_ids = wp_list_pluck( $product_collection, 'ID' );

			$args = array(
				'post_type' => 'michi-product',
				'post__in' => $product_ids,
				'orderby' => 'post__in',
				'posts_per_page' => (int) $params['items'],
			);
			$query = new WP_Query( $args );
		}

		ob_start();

		if ( $query->have_posts() ) :
			wp_enqueue_style( 'michi-product-grid-style' );
			?>

<div id="michi-grid" class="michi-flex-grid">
  <?php while ( $query->have_posts() ) :
					$query->the_post(); ?>
  <div class="michi-child">
    <div class="product-loop-box">
      <div class="product-loop-categories">
        <?php echo get_the_term_list( get_the_ID(), 'product-category', '', ' · ', '' ); ?>
      </div>
      <div class="product-loop-image">
        <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'full' ); ?></a>
      </div>
      <h3 class="product-loop-title">
        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
      </h3>
      <div class="product-loop-excerpt"><?php echo get_the_excerpt(); ?></div>
      <div class="product-loop-tags">
        <?php
								$tags = wp_get_post_terms( get_the_ID(), 'product-tag', array( 'fields' => 'names' ) );
								if ( ! empty( $tags ) ) {
									foreach ( $tags as $tag ) {
										echo '<span class="michi-tag-item">' . esc_html( $tag ) . '</span> ';
									}
								}
								?>
      </div>
      <div class="product-loop-more">
        <a href="<?php the_permalink(); ?>" class="link-more">
          <span class="fl-button-text">Explore</span>
          <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    </div>
  </div>
  <?php endwhile; ?>

  <?php if ( ! is_archive() ) : ?>
  <div class="michi-child last-loop">
    <?php echo do_shortcode( '[fl_builder_insert_layout id=1538]' ); ?>
  </div>
  <?php endif; ?>
</div>
<?php
			wp_reset_postdata();
		endif;

		return ob_get_clean();
	}
}