(() => {
	'use strict';

	if (document.body.classList.contains('fl-builder-edit')) {
		return;
	}

	const sliders = document.querySelectorAll('[data-tmm-swiper-slider]');
	if (!sliders.length || typeof window.Swiper === 'undefined') {
		return;
	}

	for (const slider of sliders) {
		if (slider.swiper) {
			continue;
		}

		new window.Swiper(slider, {
			loop: true,
			slidesPerView: 1,
			speed: 900,
			//disable touch gestures
			allowTouchMove: false,
			autoplay: {
				delay: 4500,
				disableOnInteraction: false,
				pauseOnMouseEnter: false,
			},
			pagination: {
				el: '.custom-pagination',
				clickable: true,
			},
		});
	}
})();
