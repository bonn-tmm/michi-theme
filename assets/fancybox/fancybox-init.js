/**
 * Global Fancybox initializer for all pages.
 *
 * @package BB_Theme_Child
 */
import { Fancybox } from './fancybox.esm.js';

Fancybox.bind('[data-fancybox]', {
	on: {
		'Carousel.ready Carousel.change': (fancybox) => {
			const slide = fancybox.getSlide();
			const triggerEl =
				slide && slide.triggerEl instanceof Element ? slide.triggerEl : null;
			const index = slide && typeof slide.index === 'number' ? slide.index : -1;

			window.dispatchEvent(
				new CustomEvent('michi:fancybox-change', {
					detail: {
						triggerEl,
						index,
					},
				}),
			);
		},
	},
});
