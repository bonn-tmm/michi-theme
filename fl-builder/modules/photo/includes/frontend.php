<?php

$photo = $module->get_data();
$classes = $module->get_classes();
$src = $module->get_src();
$link = $module->get_link();
$alt = $module->get_alt();
$attrs = $module->get_attributes();
$filetype = pathinfo( $src, PATHINFO_EXTENSION );
$rel = $module->get_rel();
$caption = $module->get_caption();

$wrapper_attrs = [
	'class' => $module->get_wrapper_classes(),
];

if ( false === strpos( $attrs, 'loading=' ) && false === strpos( $attrs, 'no-lazy' ) ) {
	$attrs = rtrim( $attrs ) . ' ' . FLBuilderUtils::img_lazyload( 'lazy' );
}

if ( 2 < $module->version ) {
	$wrapper_attributes = array( 'figure' );
	$caption_attributes = array( 'figcaption' );
} else {
	$wrapper_attributes = array( 'div', 'role="figure"' );
	$caption_attributes = array( 'div' );
	$attrs .= ' itemprop="image"';
	if ( '0' !== $settings->show_caption ) {
		$wrapper_attributes[] = 'aria-labelledby="caption-' . $id . '"';
		$caption_attributes[] = 'id="caption-' . $id . '"';
		$caption_attributes[] = 'itemprop="caption"';
	}
}

?>
<<?php echo join( ' ', $wrapper_attributes ); ?> <?php
			 $module->render_attributes( $wrapper_attrs );
			 FLBuilder::print_schema( ' itemscope itemtype="https://schema.org/ImageObject"' );
			 ?>>
	<div class="fl-photo-content fl-photo-img-<?php echo sanitize_html_class( $filetype ); ?>">
		<?php if ( ! empty( $link ) ) : ?>
			<a href="<?php echo $link; ?>" <?php echo ( isset( $settings->link_url_download ) && 'yes' === $settings->link_url_download ) ? ' download' : ''; ?> 	<?php echo ( 'lightbox' === $settings->link_type ) ? ' data-fancybox ' : ''; ?> target="<?php echo esc_attr( $settings->link_url_target ); ?>" <?php echo $rel; ?>
				itemprop="url">
			<?php endif; ?>
			<img class="<?php echo $classes; ?>" src="<?php echo $src; ?>" alt="<?php echo $alt; ?>" <?php echo $attrs; ?> />
			<?php if ( ! empty( $link ) ) : ?>
			</a>
		<?php endif; ?>
		<?php if ( 'hover' === $settings->show_caption ) : ?>
			<<?php echo join( ' ', $caption_attributes ); ?> class="fl-photo-caption fl-photo-caption-hover">
				<?php echo $caption; ?></<?php echo $caption_attributes[0]; ?>>
		<?php endif; ?>
	</div>
	<?php if ( 'below' === $settings->show_caption ) : ?>
		<<?php echo join( ' ', $caption_attributes ); ?> class="fl-photo-caption
			fl-photo-caption-below"><?php echo $caption; ?></<?php echo $caption_attributes[0]; ?>>
	<?php endif; ?>
</<?php echo $wrapper_attributes[0]; ?>>