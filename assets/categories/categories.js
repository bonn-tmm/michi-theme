import {
	store,
	getContext,
	getElement,
	withSyncEvent,
} from '@wordpress/interactivity';

const STORE_NAME = 'michi-categories';
const SCROLL_OFFSET_PX = 100;

// Archive layout: these nodes sit outside the interactive region markup.
const navContainer = document.getElementById('michi-nav-container');
const mainQueryBox = document.getElementById('main-query-box');

function setMainQueryLoading(isBusy) {
	if (!mainQueryBox) return;

	mainQueryBox.classList.toggle('is-loading', isBusy);
	mainQueryBox.setAttribute('aria-busy', isBusy ? 'true' : 'false');
}

function scrollToCategoryNav() {
	if (!navContainer) return;

	const y = navContainer.getBoundingClientRect().top + window.scrollY;

	window.scrollTo({
		top: y - SCROLL_OFFSET_PX,
		behavior: 'smooth',
	});
}

function beginCategoryNavigation(state, context) {
	state.isFetching = true;
	state.isOpen = false;

	context.currentFilter = context.filter;
	context.currentLabel = 'Loading...';

	setMainQueryLoading(true);
}

function endCategoryNavigation(state, context) {
	state.isFetching = false;
	context.currentLabel = context.label;

	setMainQueryLoading(false);
	scrollToCategoryNav();
}

let routerActions = null;

store(STORE_NAME, {
	state: {
		isFetching: false,
		isOpen: false,

		get isActive() {
			const context = getContext();

			return context.currentFilter === context.filter;
		},

		get currentLabel() {
			return getContext().currentLabel;
		},
	},

	actions: {
		goToPage: withSyncEvent(function* (event) {
			event.preventDefault();

			const { state } = store(STORE_NAME);
			const context = getContext();
			const url = event.currentTarget?.href;

			if (state.isFetching || !url) {
				return;
			}

			beginCategoryNavigation(state, context);

			try {
				if (!routerActions) {
					const router = yield import('@wordpress/interactivity-router');
					routerActions = router.actions;
				}
				yield routerActions.navigate(url);
				routerActions.prefetch(url);
			} finally {
				endCategoryNavigation(state, context);
			}
		}),

		toggleMenu(event) {
			event.preventDefault();

			const { state } = store(STORE_NAME);

			state.isOpen = !state.isOpen;
		},
	},

	callbacks: {
		setupOutsideClick() {
			const { state } = store(STORE_NAME);
			const { ref } = getElement();

			const onWindowClick = (event) => {
				const clickedInside = ref.contains(event.target);

				if (clickedInside || !state.isOpen) {
					return;
				}

				state.isOpen = false;
			};

			window.addEventListener('click', onWindowClick);

			return () => {
				window.removeEventListener('click', onWindowClick);
			};
		},
	},
});
