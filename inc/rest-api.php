<?php
/**
 * REST API Endpoints
 *
 * Custom REST API endpoints for Michi dealers.
 *
 * @package BB_Theme_Child
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register custom REST API routes.
 *
 * @return void
 */
function bb_child_register_rest_routes() {
	register_rest_route(
		'michi/v1',
		'/dealers',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'bb_child_get_dealers_rest_callback',
			'permission_callback' => 'bb_child_dealers_rest_permission_callback',
			'args'                => array(
				'country'  => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => __( 'Filter dealers by country.', 'bb-theme-child' ),
				),
				'state'    => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => __( 'Filter dealers by state/region.', 'bb-theme-child' ),
				),
				'page'     => array(
					'type'              => 'integer',
					'default'           => 1,
					'sanitize_callback' => 'absint',
					'validate_callback' => function( $param ) {
						return is_numeric( $param ) && (int) $param >= 1;
					},
					'description'       => __( 'Page number for paginated dealer results.', 'bb-theme-child' ),
				),
				'per_page' => array(
					'type'              => 'integer',
					'default'           => 100,
					'sanitize_callback' => 'absint',
					'validate_callback' => function( $param ) {
						return is_numeric( $param ) && (int) $param >= 1 && (int) $param <= 500;
					},
					'description'       => __( 'Number of dealers per page (max 500).', 'bb-theme-child' ),
				),
			),
		)
	);
	register_rest_route(
		'michi/v1',
		'/regions',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'bb_child_get_regions_rest_callback',
			'permission_callback' => 'bb_child_dealers_rest_permission_callback',
		)
	);
}
add_action( 'rest_api_init', 'bb_child_register_rest_routes' );

/**
 * Permission check for dealers REST endpoint.
 *
 * This endpoint intentionally exposes published dealer listing data publicly
 * for frontend dealer locator functionality (read-only access).
 *
 * @param WP_REST_Request $request Request object.
 * @return bool True to allow public read access.
 */
function bb_child_dealers_rest_permission_callback( $request ) {
	// Public read-only access is intentional for dealer locator functionality.
	return true;
}

/**
 * Get all dealers data for REST API.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error Response object or error.
 */
function bb_child_get_dealers_rest_callback( $request ) {
	// Get and sanitize pagination parameters.
	$page     = max( 1, absint( $request->get_param( 'page' ) ) );
	$per_page = absint( $request->get_param( 'per_page' ) );
	$per_page = max( 1, min( 500, $per_page ? $per_page : 100 ) );

	// Build query args.
	$args = array(
		'post_type'      => 'michi_dealer',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	// Sanitize request parameters for taxonomy filtering.
	$country = sanitize_text_field( wp_unslash( (string) $request->get_param( 'country' ) ) );
	$state   = sanitize_text_field( wp_unslash( (string) $request->get_param( 'state' ) ) );

	// Build tax_query for filtering by dealer_region taxonomy.
	$tax_query = array();

	if ( '' !== $state ) {
		$state_term = get_term_by( 'name', $state, 'dealer_region' );
		if ( $state_term ) {
			$tax_query[] = array(
				'taxonomy' => 'dealer_region',
				'field'    => 'term_id',
				'terms'    => $state_term->term_id,
			);
		}
	} elseif ( '' !== $country ) {
		$country_term = get_term_by( 'name', $country, 'dealer_region' );
		if ( $country_term ) {
			$tax_query[] = array(
				'taxonomy'         => 'dealer_region',
				'field'            => 'term_id',
				'terms'            => $country_term->term_id,
				'include_children' => true,
			);
		}
	}

	if ( ! empty( $tax_query ) ) {
		$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	$dealers_query = new WP_Query( $args );
	$dealers       = array();

	if ( $dealers_query->have_posts() ) {
		while ( $dealers_query->have_posts() ) {
			$dealers_query->the_post();
			$post_id = get_the_ID();

			// Derive country and state from dealer_region taxonomy.
			$dealer_country = '';
			$dealer_state   = '';
			$region_terms   = wp_get_object_terms( $post_id, 'dealer_region' );

			if ( ! is_wp_error( $region_terms ) && ! empty( $region_terms ) ) {
				foreach ( $region_terms as $term ) {
					if ( 0 === $term->parent ) {
						$dealer_country = $term->name;
					} else {
						$dealer_state = $term->name;
						$parent_term  = get_term( $term->parent, 'dealer_region' );
						if ( $parent_term && ! is_wp_error( $parent_term ) ) {
							$dealer_country = $parent_term->name;
						}
					}
				}
			}

			// Get ACF fields if available.
			if ( function_exists( 'get_field' ) ) {
				$address_1 = get_field( 'address_1', $post_id );
				$street    = get_field( 'street', $post_id );
				$city      = get_field( 'city', $post_id );
				$zip       = get_field( 'zip', $post_id );
				$phone     = get_field( 'phone', $post_id );
				$website   = get_field( 'website', $post_id );
				$email     = get_field( 'contact_email', $post_id );
				$owner     = get_field( 'owner', $post_id );

				$full_address = trim( $address_1 );
				if ( $street ) {
					$full_address .= ( $full_address ? ', ' : '' ) . trim( $street );
				}

				$dealers[] = array(
					'id'                     => $post_id,
					'name'                   => get_the_title(),
					'slug'                   => get_post_field( 'post_name', $post_id ),
					'url'                    => get_permalink( $post_id ),
					'address'                => $full_address,
					'city'                   => $city,
					'state'                  => $dealer_state,
					'zip'                    => $zip,
					'country'                => $dealer_country,
					'phone'                  => $phone,
					'email'                  => $email,
					'website'                => $website,
					'retail_location'        => (bool) get_field( 'retail_location', $post_id ),
					'custom_installation'    => (bool) get_field( 'custom_installation', $post_id ),
					'custom_integrator'      => (bool) get_field( 'custom_integrator', $post_id ),
					'service_center'         => (bool) get_field( 'service_center', $post_id ),
					'premium_dealer'         => (bool) get_field( 'premium_dealer', $post_id ),
					'michi_dealer'           => (bool) get_field( 'michi_dealer', $post_id ),
					'regional_distributor'   => (bool) get_field( 'regional_distributor', $post_id ),
					'owner'                  => $owner,
				);
			}
		}
		wp_reset_postdata();
	}

	// Create response with pagination headers.
	$response = rest_ensure_response( $dealers );

	// Add pagination headers.
	$total_dealers = $dealers_query->found_posts;
	$total_pages   = $dealers_query->max_num_pages;

	$response->header( 'X-WP-Total', $total_dealers );
	$response->header( 'X-WP-TotalPages', $total_pages );

	return $response;
}

/**
 * Get all dealer regions as a hierarchical tree for REST API.
 *
 * Returns countries (parent terms) with their child regions, including
 * empty regions and HTML descriptions.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response Response object.
 */
function bb_child_get_regions_rest_callback( $request ) {
	$terms = get_terms( array(
		'taxonomy'   => 'dealer_region',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return rest_ensure_response( new stdClass() );
	}

	$countries = array();
	$children  = array();

	foreach ( $terms as $term ) {
		if ( 0 === $term->parent ) {
			$countries[ $term->term_id ] = array(
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'count'       => $term->count,
				'children'    => array(),
			);
		} else {
			$children[] = $term;
		}
	}

	foreach ( $children as $term ) {
		if ( isset( $countries[ $term->parent ] ) ) {
			$countries[ $term->parent ]['children'][] = array(
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'count'       => $term->count,
			);
		}
	}

	$result = new stdClass();
	foreach ( $countries as $country ) {
		$result->{$country['name']} = $country;
	}

	return rest_ensure_response( $result );
}
