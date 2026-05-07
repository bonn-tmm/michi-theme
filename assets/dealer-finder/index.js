/**
 * Frontend JavaScript for Michi Dealer Finder Block
 */

import { store, getContext, getElement } from '@wordpress/interactivity';

const updateUrl = (country, state, url) => {
	if (country) {
		url += country;
		if (state) {
			url += '/' + state;
		}
	}
	history.pushState({ country: country, state: state }, '', url);
};

store('michi-dealer-finder', {
	actions: {
		selectCountry: () => {
			const { state } = store('michi-dealer-finder');
			const { ref } = getElement();
			const context = getContext();
			const slug = ref.value;
			const selectedCountry =
				state.countries.find((country) => country.slug === slug) ?? null;

			state.selectedState = '';
			state.selectedCountry = '';
			context.statesList = [];
			context.selectedStateName = '';
			context.dealersList = [];
			context.dealerCount = 0;
			context.dealerCountText = '';
			context.selectedCountryName = '';
			context.noDealersText = '';

			if (slug) {
				state.selectedCountry = slug;
				context.statesList = selectedCountry?.children ?? [];
				context.selectedCountryName = selectedCountry?.name ?? '';
			}
			updateUrl(state.selectedCountry, state.selectedState, state.baseUrl);
		},
		selectState: () => {
			const { state } = store('michi-dealer-finder');
			const context = getContext();
			state.selectedState = '';
			if (!context.item.slug) return;
			state.selectedState = context.item.slug;
			context.selectedStateName = context.item.name;
			context.dealersList =
				state.dealers?.[context.selectedCountryName]?.[context.item.name] ?? [];
			context.dealerCount = context.dealersList.length;
			if (context.dealerCount > 0) {
				context.dealerCountText = `${context.dealerCount} Authorized Dealer${context.dealerCount === 1 ? '' : 's'}`;
			} else {
				context.dealerCountText = 'NO DEALERS CURRENTLY LISTED';
			}
			context.noDealersText =
				'There are no authorized Michi dealers in ' +
				context.selectedStateName +
				' yet — but we’re growing. If you’re a specialist audio retailer passionate about high-performance audio, we’d love to hear from you.';

			console.log(context.dealersList);

			updateUrl(state.selectedCountry, state.selectedState, state.baseUrl);
		},
	},
	callbacks: {
		shouldShowStatePrompt: () => {
			const { state } = store('michi-dealer-finder');
			return Boolean(state.selectedCountry) && !Boolean(state.selectedState);
		},
		shouldShowCountryPrompt: () => {
			const { state } = store('michi-dealer-finder');
			return !Boolean(state.selectedCountry);
		},
		isStateActive: () => {
			const { state } = store('michi-dealer-finder');
			const context = getContext();
			return state.selectedState === context.item.slug;
		},
		isDealersListEmpty: () => {
			const context = getContext();
			return context.dealerCount === 0;
		},
	},
});
