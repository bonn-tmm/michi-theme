<?php

namespace MICHI_TMM;

class DealerFinder {
	/**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;
	/**
	 * Instance Control
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_shortcode( 'michi_dealer_finder', array( $this, 'dealer_finder_shortcode' ) );
	}

	public function enqueue_dealer_finder_assets() {
		wp_enqueue_style(
			'michi-dealer-finder-style',
			MICHI_THEME_URL . '/assets/dealer-finder/style.css',
			array(),
			MICHI_THEME_VERSION
		);

		wp_enqueue_script_module(
			'michi-dealer-finder-view',
			MICHI_THEME_URL . '/assets/dealer-finder/index.js',
			array( '@wordpress/interactivity' ),
			MICHI_THEME_VERSION
		);
	}

	public function dealer_finder_shortcode( $atts ) {
		$rest_api = RestApi::get_instance();
		if ( ! $rest_api instanceof RestApi ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'heading' => __( 'Find a Dealer by Country & State/Region', 'michi-theme' ),
				'subheading' => __( 'Select your state from the list below to jump directly to available authorized dealers.', 'michi-theme' ),
				'show_sidebar' => 'yes',
			),
			$atts,
			'michi_dealer_finder'
		);

		// Sanitize attributes.
		$heading = sanitize_text_field( $atts['heading'] );
		$subheading = sanitize_text_field( $atts['subheading'] );
		$show_sidebar = in_array( strtolower( $atts['show_sidebar'] ), array( 'yes', '1', 'true' ), true );

		// Enqueue scripts and styles.
		$this->enqueue_dealer_finder_assets();

		$countries_list = $rest_api->get_countries();
		$selected_country = sanitize_text_field( get_query_var( 'dealer_country', '' ) );
		$selected_state = sanitize_text_field( get_query_var( 'dealer_state', '' ) );
		$states_list = $rest_api->get_states_by_country_slug( $selected_country );
		$dealers_by_country_and_state = $rest_api->get_dealers_by_country_and_state();
		$selected_country_name = $rest_api->get_country_name_by_slug( $selected_country );
		$selected_state_name = $rest_api->get_state_name_by_slug( $selected_state, $selected_country );

		$selected_dealers = array();
		if ( $selected_country_name && $selected_state_name && isset( $dealers_by_country_and_state[ $selected_country_name ][ $selected_state_name ] ) ) {
			$selected_dealers = $dealers_by_country_and_state[ $selected_country_name ][ $selected_state_name ];
		}

		wp_interactivity_state(
			'michi-dealer-finder',
			array(
				'baseUrl' => get_home_url() . '/dealers/',
				'countries' => $countries_list,
				'dealers' => $dealers_by_country_and_state,
				'selectedCountry' => $selected_country,
				'selectedState' => $selected_state,
			)
		);

		$context_args = array(
			'statesList' => $states_list,
			'selectedCountryName' => $selected_country_name,
			'selectedStateName' => $selected_state_name,
			'noDealersText' => 'There are no authorized Michi dealers in ' . $selected_state_name . ' yet — but we’re growing. If you’re a specialist audio retailer passionate about high-performance audio, we’d love to hear from you.',
		);
		if ( $selected_country_name && $selected_state_name ) {
			$context_args['dealersList'] = $selected_dealers;
			$dealer_count = count( $selected_dealers );
			$context_args['dealerCount'] = $dealer_count;
			if ( $dealer_count > 0 ) {
				$context_args['dealerCountText'] = sprintf(
					_n( '%s Authorized Dealer', '%s Authorized Dealers', $dealer_count, 'michi-theme' ),
					$dealer_count
				);
			} else {
				$context_args['dealerCountText'] = 'NO DEALERS CURRENTLY LISTED';
			}
		}
		// Build the HTML output.
		ob_start();

		?>
<div class="michi-dealer-finder" data-wp-interactive="michi-dealer-finder"
  <?php echo wp_interactivity_data_wp_context( $context_args ); ?>>
  <div class="dealer-finder-content">
    <?php if ( $show_sidebar ) : ?>
    <aside class="dealer-states-sidebar">
      <div class="dealer-finder-filters">
        <div class="filter-group">
          <label for="country-select"><?php esc_html_e( 'CHOOSE A COUNTRY', 'michi-theme' ); ?></label>

          <select id="country-select" data-wp-bind--value="state.selectedCountry"
            data-wp-on--change="actions.selectCountry">
            <option value="">
              <?php esc_html_e( 'Select a country', 'michi-theme' ); ?>
            </option>
            <template data-wp-each="state.countries" data-wp-each-key="context.item.slug">
              <option data-wp-bind--value="context.item.slug" data-wp-text="context.item.name"></option>
            </template>
          </select>

        </div>
      </div>
      <h3 data-wp-bind--hidden="!state.selectedCountry"><?php esc_html_e( 'STATE/REGION', 'michi-theme' ); ?></h3>
      <ul id="states-list">
        <template data-wp-each="context.statesList" data-wp-each-key="context.item.slug">
          <li>
            <a data-wp-bind--href="state.baseUrl + '/' + state.selectedCountry + '/' + context.item.slug"
              data-wp-on--click="actions.selectState" data-wp-class--active="callbacks.isStateActive"
              data-wp-text="context.item.name"></a>
          </li>
        </template>
      </ul>
    </aside>
    <?php endif; ?>
    <div class="dealer-results">

      <p data-wp-bind--hidden="!callbacks.shouldShowStatePrompt">
        <?php esc_html_e( 'Please select a state or region from the list.', 'michi-theme' ); ?>
      </p>

      <p data-wp-bind--hidden="!callbacks.shouldShowCountryPrompt">
        <?php esc_html_e( 'Please select a country to begin.', 'michi-theme' ); ?>
      </p>

      <div id="dealer-listings" data-wp-bind--hidden="!state.selectedState">
        <h2 class="state-heading" data-wp-bind--id="state.selectedState" data-wp-text="context.selectedStateName">
        </h2><span class="dealer-count" data-wp-text="context.dealerCountText"></span>

        <div class="empty-region-card" data-wp-bind--hidden="!callbacks.isDealersListEmpty">
          <div class="empty-region-icon"><img src="<?php echo home_url(); ?>/wp-content/uploads/2026/04/shake.png"
              alt="Become a dealer"></div>
          <h3 class="empty-region-heading">Become a Michi Dealer</h3>
          <p class="empty-region-text" data-wp-text="context.noDealersText"></p>
          <a href="/become-a-dealer" class="empty-region-cta">Apply to become a dealer →</a>
        </div>


        <template data-wp-each="context.dealersList" data-wp-each-key="context.item.id">
          <div class="dealer-card">
            <h3 class="dealer-name" data-wp-text="context.item.name"></h3>
            <p class="dealer-address" data-wp-text="context.item.fullAddress"></p>
            <div class="dealer-contact-info">
              <span class="dealer-phone"><strong>PHONE</strong>
                <div data-wp-text="context.item.phone"></div>
              </span>

              <span class="dealer-website">
                <strong>WEB</strong>
                <a data-wp-bind--href="context.item.websiteUrl" target="_blank" rel="noopener"
                  data-wp-text="context.item.website"></a>
              </span>
            </div>
            <p class="dealer-types"><strong>SERVICES:</strong>
              <span data-wp-text="context.item.services"></span>
            </p>
          </div>
        </template>
      </div>
    </div>
  </div>
</div>
<?php
		$html = ob_get_clean();
		return function_exists( 'wp_interactivity_process_directives' )
			? wp_interactivity_process_directives( $html )
			: $html;
	}

}