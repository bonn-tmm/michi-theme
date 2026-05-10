<?php
namespace MICHI_TMM;

use WP_REST_Request;
use WP_REST_Server;
use WP_Query;

class RestApi {
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

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}


	public function register_rest_routes() {
		register_rest_route(
			'michi/v1',
			'/dealers',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_dealers_rest_callback' ),
				'permission_callback' => array( $this, 'dealers_rest_permission_callback' ),
				'args' => array(
					'country' => array(
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'description' => __( 'Filter dealers by country.', 'michi-theme' ),
					),
					'state' => array(
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'description' => __( 'Filter dealers by state/region.', 'michi-theme' ),
					),
					'page' => array(
						'type' => 'integer',
						'default' => 1,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && (int) $param >= 1;
						},
						'description' => __( 'Page number for paginated dealer results.', 'michi-theme' ),
					),
					'per_page' => array(
						'type' => 'integer',
						'default' => 100,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && (int) $param >= 1 && (int) $param <= 500;
						},
						'description' => __( 'Number of dealers per page (max 500).', 'michi-theme' ),
					),
				),
			)
		);
		register_rest_route(
			'michi/v1',
			'/regions',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_regions_rest_callback' ),
				'permission_callback' => array( $this, 'dealers_rest_permission_callback' ),
			)
		);
	}

	public function get_dealers_rest_callback( WP_REST_Request $request ) {
		// Get and sanitize pagination parameters.
		$page = max( 1, absint( $request->get_param( 'page' ) ) );
		$per_page = absint( $request->get_param( 'per_page' ) );
		$per_page = max( 1, min( 500, $per_page ? $per_page : 100 ) );

		// Build query args.
		$args = array(
			'post_type' => 'michi_dealer',
			'post_status' => 'publish',
			'posts_per_page' => $per_page,
			'paged' => $page,
			'orderby' => 'title',
			'order' => 'ASC',
		);

		// Sanitize request parameters for taxonomy filtering.
		$country = sanitize_text_field( wp_unslash( (string) $request->get_param( 'country' ) ) );
		$state = sanitize_text_field( wp_unslash( (string) $request->get_param( 'state' ) ) );

		// Build tax_query for filtering by dealer_region taxonomy.
		$tax_query = array();

		if ( '' !== $state ) {
			$state_term = get_term_by( 'name', $state, 'dealer_region' );
			if ( $state_term ) {
				$tax_query[] = array(
					'taxonomy' => 'dealer_region',
					'field' => 'term_id',
					'terms' => $state_term->term_id,
				);
			}
		} elseif ( '' !== $country ) {
			$country_term = get_term_by( 'name', $country, 'dealer_region' );
			if ( $country_term ) {
				$tax_query[] = array(
					'taxonomy' => 'dealer_region',
					'field' => 'term_id',
					'terms' => $country_term->term_id,
					'include_children' => true,
				);
			}
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$dealers_query = new WP_Query( $args );
		$dealers = array();

		if ( $dealers_query->have_posts() ) {
			while ( $dealers_query->have_posts() ) {
				$dealers_query->the_post();
				$post_id = get_the_ID();

				// Derive country and state from dealer_region taxonomy.
				$dealer_country = '';
				$dealer_state = '';
				$region_terms = wp_get_object_terms( $post_id, 'dealer_region' );

				if ( ! is_wp_error( $region_terms ) && ! empty( $region_terms ) ) {
					foreach ( $region_terms as $term ) {
						if ( 0 === $term->parent ) {
							$dealer_country = $term->name;
						} else {
							$dealer_state = $term->name;
							$parent_term = get_term( $term->parent, 'dealer_region' );
							if ( $parent_term && ! is_wp_error( $parent_term ) ) {
								$dealer_country = $parent_term->name;
							}
						}
					}
				}

				// Get ACF fields if available.
				if ( function_exists( 'get_field' ) ) {
					$address_1 = get_field( 'address_1', $post_id );
					$street = get_field( 'street', $post_id );
					$city = get_field( 'city', $post_id );
					$zip = get_field( 'zip', $post_id );
					$phone = get_field( 'phone', $post_id );
					$website = get_field( 'website', $post_id );
					$email = get_field( 'contact_email', $post_id );
					$owner = get_field( 'owner', $post_id );

					$full_address = trim( $address_1 );
					if ( $street ) {
						$full_address .= ( $full_address ? ', ' : '' ) . trim( $street );
					}

					$services = [];
					if ( (bool) get_field( 'retail_location', $post_id ) ) {
						$services[] = 'Retail Location';
					}
					if ( (bool) get_field( 'custom_installation', $post_id ) ) {
						$services[] = 'Custom Installation';
					}
					if ( (bool) get_field( 'custom_integrator', $post_id ) ) {
						$services[] = 'Custom Integrator';
					}
					if ( (bool) get_field( 'service_center', $post_id ) ) {
						$services[] = 'Service Center';
					}
					if ( (bool) get_field( 'premium_dealer', $post_id ) ) {
						$services[] = 'Premium Dealer';
					}
					if ( (bool) get_field( 'michi_dealer', $post_id ) ) {
						$services[] = 'Michi Dealer';
					}
					if ( (bool) get_field( 'regional_distributor', $post_id ) ) {
						$services[] = 'Regional Distributor';
					}
					$services = implode( ', ', $services );

					$dealers[] = array(
						'id' => $post_id,
						'name' => wp_kses_decode_entities( get_the_title() ),
						'slug' => get_post_field( 'post_name', $post_id ),
						'url' => get_permalink( $post_id ),
						'address' => $full_address,
						'city' => $city,
						'state' => $dealer_state,
						'zip' => $zip,
						'country' => $dealer_country,
						'phone' => $phone,
						'email' => $email,
						'website' => $website,
						'retail_location' => (bool) get_field( 'retail_location', $post_id ),
						'custom_installation' => (bool) get_field( 'custom_installation', $post_id ),
						'custom_integrator' => (bool) get_field( 'custom_integrator', $post_id ),
						'service_center' => (bool) get_field( 'service_center', $post_id ),
						'premium_dealer' => (bool) get_field( 'premium_dealer', $post_id ),
						'michi_dealer' => (bool) get_field( 'michi_dealer', $post_id ),
						'regional_distributor' => (bool) get_field( 'regional_distributor', $post_id ),
						'services' => $services,
						'owner' => $owner,
					);
				}
			}
			wp_reset_postdata();
		}

		// Create response with pagination headers.
		$response = rest_ensure_response( $dealers );

		// Add pagination headers.
		$total_dealers = $dealers_query->found_posts;
		$total_pages = $dealers_query->max_num_pages;

		$response->header( 'X-WP-Total', $total_dealers );
		$response->header( 'X-WP-TotalPages', $total_pages );

		return $response;
	}

	public function dealers_rest_permission_callback( WP_REST_Request $request ) {
		// Public read-only access is intentional for dealer locator functionality.
		return true;
	}

	public function get_dealers_by_country_and_state(): array {
		$dealers_by_country_state = array();
		$dealers = $this->get_dealers();
		foreach ( $dealers as $dealer ) {
			$country = isset( $dealer['country'] ) && '' !== (string) $dealer['country']
				? (string) $dealer['country']
				: 'Unknown';
			$state = isset( $dealer['state'] ) && '' !== (string) $dealer['state']
				? (string) $dealer['state']
				: 'Unknown';
			if ( ! isset( $dealers_by_country_state[ $country ] ) ) {
				$dealers_by_country_state[ $country ] = array();
			}
			if ( ! isset( $dealers_by_country_state[ $country ][ $state ] ) ) {
				$dealers_by_country_state[ $country ][ $state ] = array();
			}
			$dealers_by_country_state[ $country ][ $state ][] = $dealer;
		}
		return apply_filters( 'michi_dealers_by_country_and_state', $dealers_by_country_state );
	}

	public function get_countries(): array {
		$terms = get_terms( array(
			'taxonomy' => 'dealer_region',
			'hide_empty' => false,
			'orderby' => 'name',
			'order' => 'ASC',
		) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$countries = array();
		$children = array();

		foreach ( $terms as $term ) {
			if ( 0 === $term->parent ) {
				$countries[ $term->term_id ] = array(
					'name' => $term->name,
					'slug' => $term->slug,
					'description' => $term->description,
					'count' => $term->count,
					'children' => array(),
				);
			} else {
				$children[] = $term;
			}
		}

		foreach ( $children as $term ) {
			if ( isset( $countries[ $term->parent ] ) ) {
				$countries[ $term->parent ]['children'][] = array(
					'name' => $term->name,
					'slug' => $term->slug,
					'description' => $term->description,
					'count' => $term->count,
				);
			}
		}

		$name_cmp = static function ( $a, $b ) {
			return strcasecmp( $a['name'] ?? '', $b['name'] ?? '' );
		};

		foreach ( $countries as &$country ) {
			usort( $country['children'], $name_cmp );
		}
		unset( $country );

		$countries_list = array_values( $countries );
		usort( $countries_list, $name_cmp );

		$countries_list = apply_filters( 'michi_countries_list', $countries_list );

		return $countries_list;
	}

	public function get_states_by_country_slug( string $country_slug = '' ): array {
		foreach ( $this->get_countries() as $country ) {
			if ( $country['slug'] === $country_slug ) {
				return $country['children'];
			}
		}
		return array();
	}

	public function get_country_name_by_slug( string $country_slug ): string {

		$country_slug = sanitize_text_field( (string) $country_slug );
		if ( '' === $country_slug ) {
			return '';
		}
		$countries = $this->get_countries();
		foreach ( $countries as $country ) {
			if ( $country['slug'] === $country_slug ) {
				return $country['name'];
			}
		}
		return '';
	}

	public function get_state_name_by_slug( string $state_slug, string $country_slug = '' ): string {
		$state_slug = sanitize_text_field( (string) $state_slug );
		if ( '' === $state_slug || '' === $country_slug ) {
			return '';
		}
		$states = $this->get_states_by_country_slug( $country_slug );
		foreach ( $states as $state ) {
			if ( isset( $state['slug'], $state['name'] ) && $state['slug'] === $state_slug ) {
				return $state['name'];
			}
		}
		return '';
	}

	public function get_dealers( string $country = '', string $state = '' ): array {
		$args = array(
			'post_type' => 'michi_dealer',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
		);

		// Build tax_query for filtering by dealer_region taxonomy.
		$tax_query = array();

		if ( '' !== $state ) {
			$state_term = get_term_by( 'name', $state, 'dealer_region' );
			if ( $state_term ) {
				$tax_query[] = array(
					'taxonomy' => 'dealer_region',
					'field' => 'term_id',
					'terms' => $state_term->term_id,
				);
			}
		} elseif ( '' !== $country ) {
			$country_term = get_term_by( 'name', $country, 'dealer_region' );
			if ( $country_term ) {
				$tax_query[] = array(
					'taxonomy' => 'dealer_region',
					'field' => 'term_id',
					'terms' => $country_term->term_id,
					'include_children' => true,
				);
			}
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$dealers_query = new WP_Query( $args );
		$dealers = array();

		if ( $dealers_query->have_posts() ) {
			while ( $dealers_query->have_posts() ) {
				$dealers_query->the_post();
				$post_id = get_the_ID();

				// Derive country and state from dealer_region taxonomy.
				$dealer_country = '';
				$dealer_state = '';
				$region_terms = wp_get_object_terms( $post_id, 'dealer_region' );

				if ( ! is_wp_error( $region_terms ) && ! empty( $region_terms ) ) {
					foreach ( $region_terms as $term ) {
						if ( 0 === $term->parent ) {
							$dealer_country = $term->name;
						} else {
							$dealer_state = $term->name;
							$parent_term = get_term( $term->parent, 'dealer_region' );
							if ( $parent_term && ! is_wp_error( $parent_term ) ) {
								$dealer_country = $parent_term->name;
							}
						}
					}
				}

				// Get ACF fields if available.
				if ( function_exists( 'get_field' ) ) {
					$address_1 = get_field( 'address_1', $post_id );
					$street = get_field( 'street', $post_id );
					$city = get_field( 'city', $post_id );
					$zip = get_field( 'zip', $post_id );
					$phone = get_field( 'phone', $post_id );
					$website = get_field( 'website', $post_id );
					$email = get_field( 'contact_email', $post_id );
					$owner = get_field( 'owner', $post_id );

					$full_address = trim( $address_1 );
					if ( $street ) {
						$full_address .= ( $full_address ? ', ' : '' ) . trim( $street );
					}

					$services = [];
					if ( (bool) get_field( 'retail_location', $post_id ) ) {
						$services[] = 'Retail Location';
					}
					if ( (bool) get_field( 'custom_installation', $post_id ) ) {
						$services[] = 'Custom Installation';
					}
					if ( (bool) get_field( 'custom_integrator', $post_id ) ) {
						$services[] = 'Custom Integrator';
					}
					if ( (bool) get_field( 'service_center', $post_id ) ) {
						$services[] = 'Service Center';
					}
					if ( (bool) get_field( 'premium_dealer', $post_id ) ) {
						$services[] = 'Premium Dealer';
					}
					if ( (bool) get_field( 'michi_dealer', $post_id ) ) {
						$services[] = 'Michi Dealer';
					}
					if ( (bool) get_field( 'regional_distributor', $post_id ) ) {
						$services[] = 'Regional Distributor';
					}
					$services = implode( ', ', $services );

					$dealers[] = array(
						'id' => $post_id,
						'name' => html_entity_decode( get_the_title() ),
						'slug' => get_post_field( 'post_name', $post_id ),
						'url' => get_permalink( $post_id ),
						'address' => $full_address,
						'city' => $city,
						'state' => $dealer_state,
						'zip' => $zip,
						'country' => $dealer_country,
						'fullAddress' => $full_address . ( $city ? ', ' . trim( $city ) : '' ) . ( $dealer_state ? ', ' . trim( $dealer_state ) : '' ) . ( $zip ? ' ' . trim( $zip ) : '' ),
						'phone' => $phone,
						'phoneUrl' => $phone ? 'tel:' . $phone : '',
						'email' => $email,
						'emailUrl' => $email ? 'mailto:' . $email : '',
						'website' => $website,
						'websiteUrl' => $website ? ( preg_match( '#^https?://#i', $website ) ? $website : 'https://' . $website ) : '',
						'retail_location' => (bool) get_field( 'retail_location', $post_id ),
						'custom_installation' => (bool) get_field( 'custom_installation', $post_id ),
						'custom_integrator' => (bool) get_field( 'custom_integrator', $post_id ),
						'service_center' => (bool) get_field( 'service_center', $post_id ),
						'premium_dealer' => (bool) get_field( 'premium_dealer', $post_id ),
						'michi_dealer' => (bool) get_field( 'michi_dealer', $post_id ),
						'regional_distributor' => (bool) get_field( 'regional_distributor', $post_id ),
						'services' => $services,
						'owner' => $owner,
					);
				}
			}
			wp_reset_postdata();
		}
		$dealers = array_values( $dealers );
		$dealers = apply_filters( 'michi_dealers_list', $dealers );
		return $dealers;
	}

}