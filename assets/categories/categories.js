import {
	store,
	getContext,
	getElement,
	withSyncEvent,
} from '@wordpress/interactivity';

const navContainer = document.getElementById('michi-nav-container');
const mainContainer = document.getElementById('main-query-box');
const HEADER_OFFSET = 100;

const setLoadingState = (isLoading) => {
	if (!mainContainer) return;
	mainContainer.classList.toggle('is-loading', isLoading);
	mainContainer.setAttribute('aria-busy', isLoading ? 'true' : 'false');
};

const scrollToNavigation = () => {
	if (!navContainer) return;

	const elementPosition =
		navContainer.getBoundingClientRect().top + window.pageYOffset;

	window.scrollTo({
		top: elementPosition - HEADER_OFFSET,
		behavior: 'smooth',
	});
};

store('michi-categories', {
	state: {
		isFetching: false,
		isOpen: false,

		get isActive() {
			const context = getContext();
			return context.currentFilter === context.filter;
		},
		get currentLabel() {
			const context = getContext();
			return context.currentLabel;
		},
	},

	actions: {
		goToPage: withSyncEvent(function* (event) {
			event.preventDefault();
			const { state } = store('michi-categories');
			const context = getContext();
			const link = event.currentTarget;
			const url = link?.href;
			if (state.isFetching || !url) return;
			state.isFetching = true;
			state.isOpen = false;
			context.currentFilter = context.filter;
			context.currentLabel = 'Loading...';
			setLoadingState(true);
			const { actions } = yield import('@wordpress/interactivity-router');
			yield actions.navigate(url);
			actions.prefetch(url);
			state.isFetching = false;
			context.currentLabel = context.label;
			setLoadingState(false);
			scrollToNavigation();
		}),
		toggleMenu(event) {
			event.preventDefault();
			const { state } = store('michi-categories');
			state.isOpen = !state.isOpen;
		},
	},
	callbacks: {
		setupOutsideClick: () => {
			const { state } = store('michi-categories');
			const { ref } = getElement();

			const handleOutsideClick = (event) => {
				if (!ref.contains(event.target) && state.isOpen) {
					state.isOpen = false;
				}
			};

			window.addEventListener('click', handleOutsideClick);

			return () => {
				window.removeEventListener('click', handleOutsideClick);
			};
		},
	},
});
