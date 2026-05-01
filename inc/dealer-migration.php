<?php
/**
 * Dealer Region Migration
 *
 * WP-CLI command to migrate dealer country/state ACF meta into the dealer_region taxonomy.
 *
 * Usage:
 *   wp michi migrate-dealer-regions --dry-run
 *   wp michi migrate-dealer-regions
 *
 * @package BB_Theme_Child
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Michi dealer management commands.
 */
class Michi_Dealer_CLI {

	/**
	 * Migrate dealer country/state_region ACF fields into the dealer_region taxonomy.
	 *
	 * Countries become parent terms, states/regions become child terms.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview changes without writing to the database.
	 *
	 * ## EXAMPLES
	 *
	 *     wp michi migrate-dealer-regions --dry-run
	 *     wp michi migrate-dealer-regions
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function migrate_dealer_regions( $args, $assoc_args ) {
		$dry_run = WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		if ( $dry_run ) {
			WP_CLI::log( '--- DRY RUN (no changes will be made) ---' );
		}

		$posts = get_posts( array(
			'post_type'      => 'michi_dealer',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		if ( empty( $posts ) ) {
			WP_CLI::warning( 'No michi_dealer posts found.' );
			return;
		}

		WP_CLI::log( sprintf( 'Found %d dealer(s) to process.', count( $posts ) ) );

		$migrated  = 0;
		$skipped   = 0;
		$errors    = 0;
		$term_cache = array();

		foreach ( $posts as $post_id ) {
			$title   = get_the_title( $post_id );
			$country = trim( (string) get_post_meta( $post_id, 'country', true ) );
			$state   = trim( (string) get_post_meta( $post_id, 'state_region', true ) );

			if ( '' === $country && '' === $state ) {
				WP_CLI::log( sprintf( '  [SKIP] #%d "%s" — no country or state_region meta.', $post_id, $title ) );
				++$skipped;
				continue;
			}

			$term_ids = array();

			// Create or get country term (parent).
			if ( '' !== $country ) {
				$country_term_id = $this->get_or_create_term( $country, 0, $dry_run, $term_cache );
				if ( is_wp_error( $country_term_id ) ) {
					WP_CLI::warning( sprintf( '  [ERROR] #%d "%s" — country term "%s": %s', $post_id, $title, $country, $country_term_id->get_error_message() ) );
					++$errors;
					continue;
				}
				$term_ids[] = $country_term_id;

				// Create or get state term (child of country).
				if ( '' !== $state ) {
					$state_term_id = $this->get_or_create_term( $state, $country_term_id, $dry_run, $term_cache );
					if ( is_wp_error( $state_term_id ) ) {
						WP_CLI::warning( sprintf( '  [ERROR] #%d "%s" — state term "%s": %s', $post_id, $title, $state, $state_term_id->get_error_message() ) );
						++$errors;
						continue;
					}
					$term_ids[] = $state_term_id;
				}
			}

			if ( $dry_run ) {
				WP_CLI::log( sprintf( '  [OK] #%d "%s" — would assign: country="%s", state="%s"', $post_id, $title, $country, $state ) );
			} else {
				$result = wp_set_object_terms( $post_id, $term_ids, 'dealer_region' );
				if ( is_wp_error( $result ) ) {
					WP_CLI::warning( sprintf( '  [ERROR] #%d "%s" — wp_set_object_terms failed: %s', $post_id, $title, $result->get_error_message() ) );
					++$errors;
					continue;
				}
				WP_CLI::log( sprintf( '  [OK] #%d "%s" — assigned: country="%s", state="%s"', $post_id, $title, $country, $state ) );
			}

			++$migrated;
		}

		WP_CLI::success( sprintf(
			'Done. Migrated: %d, Skipped: %d, Errors: %d',
			$migrated,
			$skipped,
			$errors
		) );
	}

	/**
	 * Add missing US states under a given parent term.
	 *
	 * ## OPTIONS
	 *
	 * [--parent-id=<id>]
	 * : The term_id of the US parent region. Default: 27.
	 *
	 * [--dry-run]
	 * : Preview without creating terms.
	 *
	 * ## EXAMPLES
	 *
	 *     wp michi add-us-states --dry-run
	 *     wp michi add-us-states --parent-id=27
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function add_us_states( $args, $assoc_args ) {
		$parent_id = (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'parent-id', 27 );
		$dry_run   = WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		$all_states = array(
			'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California',
			'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia',
			'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa',
			'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland',
			'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi', 'Missouri',
			'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey',
			'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio',
			'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina',
			'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont',
			'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming',
		);

		$parent_term = get_term( $parent_id, 'dealer_region' );
		if ( ! $parent_term || is_wp_error( $parent_term ) ) {
			WP_CLI::error( sprintf( 'Parent term ID %d not found in dealer_region taxonomy.', $parent_id ) );
			return;
		}

		WP_CLI::log( sprintf( 'Parent: "%s" (ID: %d)', $parent_term->name, $parent_id ) );

		if ( $dry_run ) {
			WP_CLI::log( '--- DRY RUN ---' );
		}

		$added   = 0;
		$skipped = 0;

		foreach ( $all_states as $state ) {
			$exists = term_exists( $state, 'dealer_region', $parent_id );
			if ( $exists ) {
				WP_CLI::log( sprintf( '  [EXISTS] %s', $state ) );
				++$skipped;
				continue;
			}

			if ( $dry_run ) {
				WP_CLI::log( sprintf( '  [WOULD ADD] %s', $state ) );
				++$added;
				continue;
			}

			$result = wp_insert_term( $state, 'dealer_region', array( 'parent' => $parent_id ) );
			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( sprintf( '  [ERROR] %s — %s', $state, $result->get_error_message() ) );
				continue;
			}

			WP_CLI::log( sprintf( '  [ADDED] %s (ID: %d)', $state, $result['term_id'] ) );
			++$added;
		}

		WP_CLI::success( sprintf( 'Done. Added: %d, Already existed: %d', $added, $skipped ) );
	}

	/**
	 * Get or create a taxonomy term, using a local cache to avoid duplicate lookups.
	 *
	 * @param string $name      Term name.
	 * @param int    $parent_id Parent term ID (0 for top-level).
	 * @param bool   $dry_run   Whether this is a dry run.
	 * @param array  &$cache    Local term cache, keyed by "parent_id:name".
	 * @return int|WP_Error Term ID on success.
	 */
	private function get_or_create_term( $name, $parent_id, $dry_run, &$cache ) {
		$cache_key = $parent_id . ':' . strtolower( $name );

		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$existing = term_exists( $name, 'dealer_region', $parent_id );

		if ( $existing ) {
			$term_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;
			$cache[ $cache_key ] = $term_id;
			return $term_id;
		}

		if ( $dry_run ) {
			$fake_id = -1 * ( count( $cache ) + 1 );
			$cache[ $cache_key ] = $fake_id;
			return $fake_id;
		}

		$result = wp_insert_term( $name, 'dealer_region', array( 'parent' => $parent_id ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$term_id = (int) $result['term_id'];
		$cache[ $cache_key ] = $term_id;
		return $term_id;
	}
}

$michi_cli = new Michi_Dealer_CLI();
WP_CLI::add_command( 'michi migrate-dealer-regions', array( $michi_cli, 'migrate_dealer_regions' ) );
WP_CLI::add_command( 'michi add-us-states', array( $michi_cli, 'add_us_states' ) );
