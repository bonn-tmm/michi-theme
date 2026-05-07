/**
 * Michi product gallery: Swiper main + thumbs.
 *
 */
const gallerySwiperMap = new Map();

/**
 * @param {HTMLElement} scope Gallery root (.michi-gallery-container).
 */
const initGallery = (scope) => {
	if (
		!scope ||
		scope.dataset.michiGalleryInit === '1' ||
		typeof window.Swiper === 'undefined'
	) {
		return;
	}

	const thumbsEl = scope.querySelector('.thumbs-slider');
	const mainEl = scope.querySelector('.main-slider');
	const galleryId = scope.dataset.galleryId;

	if (!thumbsEl || !mainEl || !galleryId) {
		return;
	}

	scope.dataset.michiGalleryInit = '1';

	const swiperThumbs = new window.Swiper(thumbsEl, {
		spaceBetween: 8,
		slidesPerView: 12,
		watchSlidesProgress: true,
	});

	const swiperMain = new window.Swiper(mainEl, {
		spaceBetween: 0,
		autoHeight: true,
		thumbs: { swiper: swiperThumbs },
	});
	gallerySwiperMap.set(scope, swiperMain);
};

const initAll = () => {
	const galleries = document.querySelectorAll('.michi-gallery-container');
	for (const scope of galleries) {
		try {
			initGallery(scope);
		} catch (error) {
			// eslint-disable-next-line no-console
			console.error('Failed to initialize Michi gallery.', error);
		}
	}
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initAll, { once: true });
} else {
	initAll();
}

window.addEventListener('michi:fancybox-change', (event) => {
	const detail = event && event.detail ? event.detail : {};
	const triggerEl =
		detail.triggerEl instanceof Element ? detail.triggerEl : null;
	const galleryScope = triggerEl
		? triggerEl.closest('.michi-gallery-container')
		: null;
	const swiperMain = galleryScope ? gallerySwiperMap.get(galleryScope) : null;
	const index = typeof detail.index === 'number' ? detail.index : -1;
	if (!swiperMain || index < 0) {
		return;
	}
	requestAnimationFrame(() => {
		swiperMain.slideTo(index, 0);
	});
});
