/**
 * Frontend JavaScript for Michi Dealer Finder Block
 */

document.addEventListener('DOMContentLoaded', function () {
	const dealerBlock = document.querySelector('.michi-dealer-finder');
	if (!dealerBlock) return;

	const countrySelect = dealerBlock.querySelector('#country-select');
	const statesList = dealerBlock.querySelector('#states-list');
	const dealerListings = dealerBlock.querySelector('#dealer-listings');

	const config = window.michiDealerFinder || {};
	const baseUrl = config.baseUrl || '/dealer-locator';

	let allDealers = [];
	let regions = {};
	let dealersByCountryState = {};
	let currentCountry = '';
	let currentState = '';

	Promise.all([
		fetch('/wp-json/michi/v1/dealers?per_page=500').then((r) => r.json()),
		fetch('/wp-json/michi/v1/regions').then((r) => r.json()),
	])
		.then(([dealersData, regionsData]) => {
			allDealers = dealersData;
			regions = regionsData;
			indexDealers();
			populateCountries();
			restoreFromUrl();
		})
		.catch((error) => {
			console.error('Error loading dealers:', error);
			dealerListings.innerHTML =
				'<p>Error loading dealers. Please try again later.</p>';
		});

	function indexDealers() {
		allDealers.forEach((dealer) => {
			const country = dealer.country || 'Unknown';
			const state = dealer.state || 'Unknown';

			if (!dealersByCountryState[country]) {
				dealersByCountryState[country] = {};
			}
			if (!dealersByCountryState[country][state]) {
				dealersByCountryState[country][state] = [];
			}
			dealersByCountryState[country][state].push(dealer);
		});
	}

	function findCountryBySlug(slug) {
		for (const name of Object.keys(regions)) {
			if (regions[name].slug === slug) return name;
		}
		return '';
	}

	function findChildBySlug(countryName, slug) {
		if (!regions[countryName]) return null;
		const children = regions[countryName].children || [];
		return children.find((c) => c.slug === slug) || null;
	}

	function getCountrySlug(countryName) {
		return regions[countryName] ? regions[countryName].slug : '';
	}

	function updateUrl(countrySlug, stateSlug) {
		let path = baseUrl;
		if (countrySlug) {
			path += '/' + countrySlug;
			if (stateSlug) {
				path += '/' + stateSlug;
			}
		}
		history.pushState({ country: countrySlug, state: stateSlug }, '', path);
	}

	function restoreFromUrl() {
		const initialCountry = config.initialCountry || '';
		const initialState = config.initialState || '';

		if (!initialCountry) return;

		const countryName = findCountryBySlug(initialCountry);
		if (!countryName) return;

		currentCountry = countryName;
		countrySelect.value = countryName;
		populateStates(countryName, true);

		if (initialState) {
			const child = findChildBySlug(countryName, initialState);
			if (child) {
				currentState = child.name;
				// Activate the sidebar link
				if (statesList) {
					const link = statesList.querySelector(
						'[data-state="' + child.name + '"]',
					);
					if (link) link.classList.add('active');
				}
				displayDealersByState(child);
			}
		}
	}

	function populateCountries() {
		const sortedCountries = Object.keys(regions).sort();
		countrySelect.innerHTML = '<option value="">Select a country</option>';

		sortedCountries.forEach((country) => {
			const option = document.createElement('option');
			option.value = country;
			option.textContent = country;
			countrySelect.appendChild(option);
		});
	}

	function populateStates(country, skipUrlUpdate) {
		if (!country || !regions[country]) {
			if (statesList) {
				statesList.innerHTML = '';
			}
			return;
		}

		const children = (regions[country].children || [])
			.slice()
			.sort((a, b) => a.name.localeCompare(b.name));

		if (statesList) {
			statesList.innerHTML = '';
			children.forEach((child) => {
				const li = document.createElement('li');
				const a = document.createElement('a');
				a.href = baseUrl + '/' + getCountrySlug(country) + '/' + child.slug;
				a.textContent = child.name;
				a.dataset.state = child.name;
				a.addEventListener('click', function (e) {
					e.preventDefault();
					statesList
						.querySelectorAll('a')
						.forEach((link) => link.classList.remove('active'));
					this.classList.add('active');
					currentState = child.name;
					updateUrl(getCountrySlug(currentCountry), child.slug);
					displayDealersByState(child);
				});
				li.appendChild(a);
				statesList.appendChild(li);
			});
		}

		if (!skipUrlUpdate) {
			dealerListings.innerHTML =
				'<p>Please select a state/region from the list.</p>';
		}
	}

	function displayDealersByState(regionChild) {
		const stateName = regionChild.name;
		const dealers =
			(dealersByCountryState[currentCountry] &&
				dealersByCountryState[currentCountry][stateName]) ||
			[];

		dealerListings.innerHTML = '';

		const stateHeading = document.createElement('h2');
		stateHeading.className = 'state-heading';
		stateHeading.id =
			'state-' + stateName.toLowerCase().replace(/[^a-z0-9]/g, '-');
		stateHeading.textContent = stateName;
		dealerListings.appendChild(stateHeading);

		if (dealers.length === 0) {
			const noCount = document.createElement('span');
			noCount.className = 'dealer-count';
			noCount.textContent = 'NO DEALERS CURRENTLY LISTED';
			dealerListings.appendChild(noCount);

			const emptyCard = document.createElement('div');
			emptyCard.className = 'empty-region-card';

			let emptyHtml =
				'<div class="empty-region-icon">' +
				'<img src="/wp-content/uploads/2026/04/shake.png" alt="Become a dealer" />' +
				'</div>' +
				'<h3 class="empty-region-heading">Become a Michi Dealer</h3>' +
				'<p class="empty-region-text">There are no authorized Michi dealers in ' +
				stateName +
				' yet — but we’re growing. If you’re a specialist audio retailer passionate about high-performance audio, we’d love to hear from you.</p>';

			if (regionChild.description) {
				emptyHtml +=
					'<div class="empty-region-description">' +
					regionChild.description +
					'</div>';
			}

			emptyHtml +=
				'<a href="/become-a-dealer" class="empty-region-cta">Apply to become a dealer →</a>';

			emptyCard.innerHTML = emptyHtml;
			dealerListings.appendChild(emptyCard);
			return;
		}

		const dealerCount = document.createElement('span');
		dealerCount.className = 'dealer-count';
		const dealerWord = dealers.length === 1 ? 'DEALER' : 'DEALERS';
		dealerCount.textContent = dealers.length + ' AUTHORIZED ' + dealerWord;
		dealerListings.appendChild(dealerCount);

		dealers.forEach((dealer) => {
			const dealerCard = document.createElement('div');
			dealerCard.className = 'dealer-card';

			let html = '<h3 class="dealer-name">' + dealer.name + '</h3>';

			if (dealer.address) {
				html += '<p class="dealer-address">' + dealer.address;
				if (dealer.city) html += ', ' + dealer.city;
				if (dealer.state) html += ', ' + dealer.state;
				if (dealer.zip) html += ' ' + dealer.zip;
				html += '</p>';
			}

			const contactItems = [];

			if (dealer.phone) {
				contactItems.push(
					'<span class="dealer-phone"><strong>PHONE</strong> ' +
						dealer.phone +
						'</span>',
				);
			}

			if (dealer.email) {
				contactItems.push(
					'<span class="dealer-email"><strong>EMAIL</strong> <a href="mailto:' +
						dealer.email +
						'">' +
						dealer.email +
						'</a></span>',
				);
			}

			if (dealer.website) {
				let fullUrl = dealer.website;
				if (!/^https?:\/\//i.test(fullUrl)) {
					fullUrl = 'https://' + fullUrl;
				}
				const displayUrl = dealer.website.replace(/^https?:\/\//, '');
				contactItems.push(
					'<span class="dealer-website"><strong>WEB</strong> <a href="' +
						fullUrl +
						'" target="_blank" rel="noopener">' +
						displayUrl +
						'</a></span>',
				);
			}

			if (contactItems.length > 0) {
				html +=
					'<div class="dealer-contact-info">' +
					contactItems.join('') +
					'</div>';
			}

			const types = [];
			if (dealer.retail_location) types.push('Retail Location');
			if (dealer.custom_installation) types.push('Custom Installation');
			if (dealer.custom_integrator) types.push('Custom Integrator');
			if (dealer.service_center) types.push('Service Center');
			if (dealer.premium_dealer) types.push('Premium Dealer');
			if (dealer.michi_dealer) types.push('Michi Dealer');
			if (dealer.regional_distributor) types.push('Regional Distributor');

			if (types.length > 0) {
				html +=
					'<p class="dealer-types"><strong>SERVICES:</strong> ' +
					types.join(', ') +
					'</p>';
			}

			dealerCard.innerHTML = html;
			dealerListings.appendChild(dealerCard);
		});
	}

	countrySelect.addEventListener('change', function () {
		currentCountry = this.value;
		currentState = '';

		if (currentCountry) {
			updateUrl(getCountrySlug(currentCountry), '');
			populateStates(currentCountry);
		} else {
			if (statesList) {
				statesList.innerHTML = '';
			}
			dealerListings.innerHTML = '<p>Please select a country to begin.</p>';
			history.pushState({}, '', baseUrl);
		}
	});

	// Handle browser back/forward
	window.addEventListener('popstate', function (e) {
		const state = e.state;
		if (!state) {
			currentCountry = '';
			currentState = '';
			countrySelect.value = '';
			if (statesList) statesList.innerHTML = '';
			dealerListings.innerHTML = '<p>Please select a country to begin.</p>';
			return;
		}

		if (state.country) {
			const countryName = findCountryBySlug(state.country);
			if (countryName) {
				currentCountry = countryName;
				countrySelect.value = countryName;
				populateStates(countryName, true);

				if (state.state) {
					const child = findChildBySlug(countryName, state.state);
					if (child) {
						currentState = child.name;
						if (statesList) {
							const link = statesList.querySelector(
								'[data-state="' + child.name + '"]',
							);
							if (link) {
								statesList
									.querySelectorAll('a')
									.forEach((l) => l.classList.remove('active'));
								link.classList.add('active');
							}
						}
						displayDealersByState(child);
					}
				} else {
					dealerListings.innerHTML =
						'<p>Please select a state/region from the list.</p>';
				}
			}
		} else {
			currentCountry = '';
			currentState = '';
			countrySelect.value = '';
			if (statesList) statesList.innerHTML = '';
			dealerListings.innerHTML = '<p>Please select a country to begin.</p>';
		}
	});

	dealerListings.innerHTML = '<p>Please select a country to begin.</p>';
});
