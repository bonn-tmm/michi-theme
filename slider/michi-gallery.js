(function () {
	'use strict';

	const config = window.MICHI_GALLERY_CONFIG || {};
	const PHOTOSWIPE_LIGHTBOX_URL = config.photoswipeLightboxUrl || '';
	const PHOTOSWIPE_URL = config.photoswipeUrl || '';

	let photoswipeModulesPromise = null;

	const getPhotoSwipeModules = async () => {
		if (!PHOTOSWIPE_LIGHTBOX_URL || !PHOTOSWIPE_URL) {
			throw new Error('Michi gallery config missing local PhotoSwipe URLs.');
		}
		if (!photoswipeModulesPromise) {
			photoswipeModulesPromise = Promise.all([
				import(PHOTOSWIPE_LIGHTBOX_URL),
				import(PHOTOSWIPE_URL),
			]);
		}
		return photoswipeModulesPromise;
	};

	const initGallery = async (scope) => {
		if (!scope || scope.dataset.michiGalleryInit === '1' || typeof window.Swiper === 'undefined') {
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

		const [{ default: PhotoSwipeLightbox }, { default: PhotoSwipe }] = await getPhotoSwipeModules();

		const lightbox = new PhotoSwipeLightbox({
			gallery: '#' + galleryId + ' .pswp-gallery',
			children: 'a',
			pswpModule: () => PhotoSwipe,
		});

		lightbox.on('change', () => {
			if (lightbox.pswp) {
				swiperMain.slideTo(lightbox.pswp.currIndex, 0, false);
			}
		});

		lightbox.init();
	};

	const initAll = () => {
		const galleries = document.querySelectorAll('.michi-gallery-container');
		if (!galleries.length) {
			return;
		}

		galleries.forEach((scope) => {
			void initGallery(scope).catch((error) => {
				// eslint-disable-next-line no-console
				console.error('Failed to initialize Michi gallery.', error);
			});
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll, { once: true });
	} else {
		initAll();
	}
})();
