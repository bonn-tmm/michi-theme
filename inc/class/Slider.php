<?php

namespace MICHI_TMM;

class Slider {
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'home_slider', array( $this, 'home_slider_shortcode' ) );
		add_shortcode( 'michi_gallery', array( $this, 'product_gallery_shortcode' ) );
	}

	/**
	 * Enqueue assets
	 */

	public function enqueue_assets() {
		$swiper_css_rel = '/assets/swiper/swiper-bundle.min.css';
		$swiper_js_rel = '/assets/swiper/swiper-bundle.min.js';
		$fancybox_css_rel = '/assets/fancybox/fancybox.css';

		$swiper_ver = '11.2.10';
		$fancybox_ver = '5.0.36';

		wp_register_style( 'swiper-css', MICHI_THEME_URL . $swiper_css_rel, array(), $swiper_ver );
		wp_register_style( 'fancybox-css', MICHI_THEME_URL . $fancybox_css_rel, array(), $fancybox_ver );
		wp_register_style( 'michi-gallery', MICHI_THEME_URL . '/assets/slider/michi-gallery.css', array( 'swiper-css', 'fancybox-css' ), MICHI_THEME_VERSION );
		wp_register_script( 'swiper-bundle', MICHI_THEME_URL . $swiper_js_rel, array(), $swiper_ver, true );

		wp_register_script( 'michi-gallery-config', false, array(), MICHI_THEME_VERSION, false );
		wp_localize_script(
			'michi-gallery-config',
			'michiThemeAssets',
			array(
				'assetsBaseUrl' => trailingslashit( untrailingslashit( MICHI_THEME_URL ) . '/assets' ),
				'assetsBasePath' => trailingslashit( untrailingslashit( MICHI_THEME_DIR ) . '/assets' ),
			)
		);


		wp_enqueue_script_module(
			'michi-gallery',
			MICHI_THEME_URL . '/assets/slider/michi-gallery.js',
			array(),
			MICHI_THEME_VERSION
		);
		wp_enqueue_script_module(
			'fancybox-init',
			MICHI_THEME_URL . '/assets/fancybox/fancybox-init.js',
			array(),
			MICHI_THEME_VERSION
		);
		wp_register_script( 'home-slider', MICHI_THEME_URL . '/assets/slider/home-slider.js', array( 'swiper-bundle' ), MICHI_THEME_VERSION, true );
		wp_register_style( 'home-slider', MICHI_THEME_URL . '/assets/slider/home-slider.css', array( 'swiper-css' ), MICHI_THEME_VERSION );

		wp_enqueue_style( 'fancybox-css' );
		wp_enqueue_script_module( 'fancybox-init' );


		if ( is_singular( 'michi-product' ) ) {
			wp_enqueue_style( 'swiper-css' );
			wp_enqueue_script( 'swiper-bundle' );
		}

		if ( is_front_page() ) {
			wp_enqueue_style( 'swiper-css' );
			wp_enqueue_script( 'swiper-bundle' );
			wp_enqueue_script( 'home-slider' );
			wp_enqueue_style( 'home-slider' );
		}


	}

	/**
	 * Shortcode: [home_slider]
	 */
	public function home_slider_shortcode( array|string|null $atts ) {
		$atts = shortcode_atts(
			array(
				'field' => 'slider',
				'post_id' => get_the_ID(),
				'class' => '',
			),
			$atts,
			'home_slider'
		);

		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}

		$rows = get_field( $atts['field'], $atts['post_id'] );
		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return '';
		}

		$slides = array();

		foreach ( $rows as $row ) {
			if ( empty( $row['image'] ) ) {
				continue;
			}

			$image = $row['image'];

			if ( is_numeric( $image ) ) {
				$image_id = (int) $image;
				$image_url = wp_get_attachment_image_url( $image_id, 'full' );
				$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			} elseif ( is_array( $image ) ) {
				$image_id = ! empty( $image['ID'] ) ? (int) $image['ID'] : 0;
				$image_url = ! empty( $image['url'] ) ? $image['url'] : '';
				$image_alt = isset( $image['alt'] ) ? $image['alt'] : '';
			} else {
				$image_id = 0;
				$image_url = (string) $image;
				$image_alt = '';
			}

			if ( empty( $image_url ) ) {
				continue;
			}

			$slides[] = array(
				'id' => $image_id,
				'url' => $image_url,
				'alt' => $image_alt,
			);
		}

		if ( empty( $slides ) ) {
			return '';
		}

		$unique_id = wp_unique_id( 'tmm-swiper-' );

		$classes = 'slider-wrap';
		if ( ! empty( $atts['class'] ) ) {
			$extra_classes = preg_split( '/\s+/', trim( (string) $atts['class'] ) );
			$extra_classes = array_filter( array_map( 'sanitize_html_class', $extra_classes ) );
			if ( ! empty( $extra_classes ) ) {
				$classes .= ' ' . implode( ' ', $extra_classes );
			}
		}

		ob_start();
		?>
<div id="<?php echo esc_attr( $unique_id ); ?>" data-tmm-swiper-slider="<?php echo esc_attr( $unique_id ); ?>"
  data-pagination="custom-pagination" class="<?php echo esc_attr( $classes ); ?> swiper">
  <div class="swiper-wrapper">
    <?php foreach ( $slides as $slide ) : ?>
    <div class="swiper-slide">
      <?php echo wp_get_attachment_image( $slide['id'], 'full', false, array( 'loading' => 'lazy' ) ); ?>
      <?php if ( empty( $slide['id'] ) ) : ?>
      <img src="<?php echo esc_url( $slide['url'] ); ?>" alt="<?php echo esc_attr( $slide['alt'] ); ?>"
        loading="lazy" />
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php

		return ob_get_clean();
	}

	/**
	 * Shortcode: [michi_gallery]
	 */
	public function product_gallery_shortcode() {
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}

		$images = get_field( 'product_gallery' );
		if ( empty( $images ) || ! is_array( $images ) ) {
			return '';
		}

		wp_enqueue_style( 'michi-gallery' );
		wp_enqueue_script( 'swiper-bundle' );
		wp_enqueue_script( 'michi-gallery-config' );
		wp_enqueue_script_module( 'michi-gallery' );

		$unique_id = wp_unique_id( 'michi-' );

		ob_start();
		?>
<div id="<?php echo esc_attr( $unique_id ); ?>" class="michi-gallery-container"
  data-gallery-id="<?php echo esc_attr( $unique_id ); ?>">
  <div class="swiper main-slider">
    <div class="swiper-wrapper michi-fancybox-gallery">
      <?php foreach ( $images as $image ) : ?>
      <?php
						$full_url = isset( $image['url'] ) ? $image['url'] : '';
						$large_url = isset( $image['sizes']['large'] ) ? $image['sizes']['large'] : $full_url;
						$img_width = isset( $image['width'] ) ? (int) $image['width'] : 0;
						$img_height = isset( $image['height'] ) ? (int) $image['height'] : 0;
						$img_alt = isset( $image['alt'] ) ? $image['alt'] : '';
						?>
      <?php if ( ! empty( $full_url ) ) : ?>
      <div class="swiper-slide">
        <div class="product-image-wrapper">
          <a href="<?php echo esc_url( $full_url ); ?>" data-fancybox="<?php echo esc_attr( $unique_id ); ?>"
            <?php if ( $img_width > 0 ) : ?> data-width="<?php echo esc_attr( (string) $img_width ); ?>" <?php endif; ?>
            <?php if ( $img_height > 0 ) : ?> data-height="<?php echo esc_attr( (string) $img_height ); ?>"
            <?php endif; ?>>
            <img src="<?php echo esc_url( $large_url ); ?>" alt="<?php echo esc_attr( $img_alt ); ?>" />
          </a>
        </div>
      </div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="swiper thumbs-slider">
    <div class="swiper-wrapper">
      <?php foreach ( $images as $image ) : ?>
      <?php
						$full_url = isset( $image['url'] ) ? $image['url'] : '';
						$thumb_url = isset( $image['sizes']['thumbnail'] ) ? $image['sizes']['thumbnail'] : ( isset( $image['sizes']['large'] ) ? $image['sizes']['large'] : $full_url );
						?>
      <?php if ( ! empty( $full_url ) ) : ?>
      <div class="swiper-slide">
        <img src="<?php echo esc_url( $thumb_url ); ?>" alt="Thumbnail" />
      </div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php

		return ob_get_clean();
	}

}