<?php
/**
 * Importer classes for providers that use an API to provide jobs.
 *
 * API: https://www.workingnomads.com/api/exposed_jobs/
 *
 * @package GoFetch/Admin/Premium/Professional/API Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The class for the Working Nomads Feed API.
 */
class GoFetch_WorkingNomads_API_Feed_Provider extends GoFetch_API_Feed_Provider {

	/**
	 * @var The single instance of the class.
	 */
	protected static $_instance = null;

	protected $provider;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Setup the base data for the provider.
	 */
	public function __construct() {
		global $goft_wpjm_options;

		$this->id      = 'workingnomads.com/api';
		$this->api_url = 'https://www.workingnomads.com/api/exposed_jobs';

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_filter( 'goft_wpjm_providers', array( $this, 'providers' ), 15 );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );
		add_filter( 'goft_wpjm_sample_item', array( $this, 'sample_item' ), 10, 2 );

		// Frontend.
		add_action( 'goft_wpjm_single_goft_job', array( $this, 'single_job_page_hooks' ) );
	}

	/**
	 * Enqueues Neuvoo in the list of providers.
	 */
	public function providers( $providers ) {

		$new_providers = array(
			'workingnomads.com/api' => array(
				'API' => array(
					'info'      => 'https://www.workingnomads.com/api/exposed_jobs/',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
				),
				'website'     => 'https://www.workingnomads.com',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-workingnomads.png',
				'description' => 'Remote Jobs.',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://www.workingnomads.com',

					'fixed' => array(
						__( 'Latest Jobs', 'gofetch-wpjm' ) => $this->get_api_url(),
					),

					'default' => true,
				),
				'category' => 'API',
				'weight'   => 7,
			),
		);

		$this->provider = $new_providers[ $this->id ];

		return array_merge( $providers, $new_providers );
	}

	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {

		$api_data = $this->get_api_data( $url );

		$parsed_url    = parse_url( $url );
		parse_str( $parsed_url['query'], $query_parts );

		$paginated_results = ( ! empty( $this->provider['feed']['pagination'] ) && in_array( $this->provider['feed']['pagination']['params']['page'], array_keys( $query_parts ) ) );

		if ( ! $paginated_results && ( is_wp_error( $api_data ) || empty( $api_data ) ) ) {
			if ( ! is_wp_error( $api_data ) && empty( $api_data ) ) {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Make sure you\'ve specified a \'Keyword\' and/or \'Location\' ', 'gofetch-wpjm' ) );
			}
			return $api_data;
		}
		return $api_data;
	}

	/**
	 * Fetch items from the API feed.
	 */
	public function fetch_feed_items( $items, $url, $provider ) {
		global $goft_wpjm_options;

		$new_items = $sample_item = array();

		$defaults = array(
			'url'           => '',
			'title'         => '',
			'description'   => '',
			'company_name'  => '',
			'category_name' => '',
			'tags'          => '',
			'location'      => '',
			'pub_date'      => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['title'] );
			$new_item['date']        = GoFetch_Importer::get_valid_date( $job['pub_date'], 'api' );
			$new_item['location']    = sanitize_text_field( $job['location'] );

			$new_item['company']     = sanitize_text_field( $job['company_name'] );

			$new_item['description'] = GoFetch_Importer::format_description( $job['description'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( $job['url'] ) );

			$new_item['category'] = sanitize_text_field( $job['category_name'] );
			$new_item['tags']     = sanitize_text_field( $job['tags'] );

			// Find the item with the most attributes to use as sample.
			if ( count( array_keys( $new_item ) ) > count( array_keys( $sample_item ) ) ) {
				$sample_item = $new_item;
				$sample_item['description'] = GoFetch_Importer::shortened_description( $job['description'] );
			}

			$new_item    = apply_filters( 'goft_wpjm_fetch_feed_item', $new_item, $provider, $url );
			$sample_item = apply_filters( 'goft_wpjm_fetch_feed_sample_item', $sample_item, $provider, $job );

			$new_items[] = $new_item;
		}

		// Clear memory.
		$items = null;

		// __LOG.
		// Maybe log import info.
		$vars = array(
			'context' => 'GOFT :: ITEMS COLLECTED FROM FEED',
			'items'   => count( $new_items ),
		);
		BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
		// __END LOG.

		$provider['name'] = 'Job Search | Working Nomads';

		return array(
			'provider'    => $provider,
			'items'       => $new_items,
			'sample_item' => $sample_item,
		);
	}

	/**
	 * Set specific meta from Indeed.
	 */
	public function params_meta( $params, $item ) {
		global $goft_wpjm_options;

		if ( empty( $item['provider_id'] ) || ! $this->condition( $item['provider_id'] ) ) {
			return $params;
		}

		// Geolocation.
		if ( ! empty( $item['location'] ) ) {
			$params['meta'][ $goft_wpjm_options->setup_field_formatted_address ] = $item['location'];
		}

		return $params;
	}

	/**
	 * Unset specific attributes from the sample job.
	 */
	public function sample_item( $item, $provider ) {

		if ( empty( $item['provider_id'] ) || ! $this->condition( $item['provider_id'] ) ) {
			return $item;
		}
		return $item;
	}


	/**
	 * FRONTEND
	 */


	/**
	 * Actions that should run on the single job page.
	 */
	public function single_job_page_hooks( $post ) {

		if ( ! $this->condition() ) {
			return;
		}

	}

}
new GoFetch_WorkingNomads_API_Feed_Provider();
