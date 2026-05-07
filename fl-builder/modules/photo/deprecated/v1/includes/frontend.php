<?php

$photo    = $module->get_data();
$classes  = $module->get_classes();
$src      = $module->get_src();
$link     = $module->get_link();
$alt      = $module->get_alt();
$attrs    = $module->get_attributes();
$filetype = pathinfo( $src, PATHINFO_EXTENSION );
$rel      = $module->get_rel();
$caption  = $module->get_caption();

$wrapper_classes = $module->get_wrapper_classes();

$wrapper_attributes = array( 'role="figure"' );
$caption_attributes = array();
$attrs             .= ' itemprop="image"';
if ( '0' !== $settings->show_caption ) {
	$wrapper_attributes[] = 'aria-labelledby="caption-' . $id . '"';
	$caption_attributes[] = 'id="caption-' . $id . '"';
	$caption_attributes[] = 'itemprop="caption"';
}

?>
<div <?php echo join( ' ', $wrapper_attributes ); ?> class="<?php echo $wrapper_classes; ?>"<?php FLBuilder::print_schema( ' itemscope itemtype="https://schema.org/ImageObject"' ); ?>>
	<div class="fl-photo-content fl-photo-img-<?php echo sanitize_html_class( $filetype ); ?>">
		<?php if ( ! empty( $link ) ) : ?>
		<a href="<?php echo $link; ?>" target="<?php echo $settings->link_url_target; ?>"<?php echo $rel; ?> itemprop="url">
		<?php endif; ?>
		<img class="<?php echo $classes; ?>" src="<?php echo $src; ?>" alt="<?php echo $alt; ?>" <?php echo $attrs; ?> />
		<?php if ( ! empty( $link ) ) : ?>
		</a>
		<?php endif; ?>
		<?php if ( 'hover' === $settings->show_caption ) : ?>
		<div <?php echo join( ' ', $caption_attributes ); ?> class="fl-photo-caption fl-photo-caption-hover"><?php echo $caption; ?></div>
		<?php endif; ?>
	</div>
	<?php if ( 'below' === $settings->show_caption ) : ?>
	<div <?php echo join( ' ', $caption_attributes ); ?> class="fl-photo-caption fl-photo-caption-below"><?php echo $caption; ?></div>
	<?php endif; ?>
</div>
