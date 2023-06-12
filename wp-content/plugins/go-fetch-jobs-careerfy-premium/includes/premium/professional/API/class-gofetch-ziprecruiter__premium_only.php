<?php
/**
 * Importer classes for providers that use an API to provide jobs.
 *
 * @package GoFetch/Admin/Premium/Pro+/API Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The class for the Indeed Feed API.
 */
class GoFetch_ZipRecruiter_API_Feed_Provider extends GoFetch_API_Feed_Provider {

	/**
	 * @var The single instance of the class.
	 */
	protected static $_instance = null;

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

		$this->id      = 'api.ziprecruiter.com';
		$this->api_url = sprintf( 'https://api.ziprecruiter.com/jobs/v1?api_key=%1$s', esc_attr( $goft_wpjm_options->ziprecruiter_api_key ) );

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-providers', array( $this, 'tabs' ), 105 );
		add_filter( 'goft_wpjm_providers', array( $this, 'providers' ), 15 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );
		add_filter( 'goft_wpjm_sample_item', array( $this, 'sample_item' ), 10, 2 );

		// Frontend.
		add_action( 'goft_no_robots', array( $this, 'maybe_no_robots' ), 10, 2 );
	}

	/**
	 * Init the Indeed tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		/** @temp
		$this->all_tabs->tabs->add( 'ziprecruiter', __( 'ZipRecruiter', 'gofetch-wpjm' ) );
		$this->tab_ziprecruiter();
		*/
	}

	/**
	 * Indeed settings tab.
	 */
	protected function tab_ziprecruiter() {

		$info_url = 'https://www.ziprecruiter.com/publishers';

		$this->all_tabs->tab_sections['ziprecruiter']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'API Key', 'gofetch-wpjm' ),
					'name'  => 'ziprecruiter_api_key',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Sign up for a free <a href="%1$s" target="_new">ZipRecruiter Publisher Account</a>', 'gofetch-wpjm' ), esc_url( $info_url ) ),
					'tip'   => __( 'You need to request an API Key in order to pull jobs from ZipRecruiter.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['ziprecruiter']['defaults'] = array(
			'title' => __( 'Feed Defaults', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Radius', 'gofetch-wpjm' ),
					'name'  => 'ziprecruiter_feed_default_radius',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Distance from search location ("as the crow flies")', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Jobs Age', 'gofetch-wpjm' ),
					'name'  => 'ziprecruiter_feed_default_days_ago',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'desc' => __( 'Days', 'gofetch-wpjm' ),
					'tip' => __( 'Only pull jobs posted within this number of days. Leave empty for any.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Min. Salary', 'gofetch-wpjm' ),
					'name'  => 'ziprecruiter_feed_default_refine_by_salary',
					'type'  => 'text',
					'extra' => array(
						'style' => 'width: 80px',
					),
					'desc' => __( '(Annual Salary)', 'gofetch-wpjm' ),
					'tip' => __( 'Only pull jobs that pay more than the salary you specify here (only numeric values without currency). Leave empty for any.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Limit', 'gofetch-wpjm' ),
					'name'  => 'ziprecruiter_feed_default_jobs_per_page',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Maximum number of results returned per query. Maximum recommended is 100.', 'gofetch-wpjm' ),
				),
			),
		);

	}

	/**
	 * Enqueues Indeed in the list of providers.
	 */
	public function providers( $providers ) {
		global $goft_wpjm_options;

		// @temp
		return $providers;

		$new_providers = array(
			'api.ziprecruiter.com' => array(
				'API' => array(
					'info'      => 'https://api.ziprecruiter.com/jobs/v1',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'API Key' => 'ziprecruiter_api_key',
					),
				),
				'website'     => 'https://www.ziprecruiter.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-ziprecruiter.png',
				'description' => 'ZipRecruiter is an employment search engine.',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://www.ziprecruiter.com/jobs/search',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'  => array( 'search'   => '' ),
						'location' => array( 'location' => '' ),
						'radius'   => array( 'radius'   => esc_attr( $goft_wpjm_options->ziprecruiter_feed_default_radius ) ),
						// Custom.
						'jobs_per_page'    => array( 'jobs_per_page'    => esc_attr( $goft_wpjm_options->ziprecruiter_feed_default_jobs_per_page ) ),
						'days_ago'         => array( 'days_ago'         => esc_attr( $goft_wpjm_options->ziprecruiter_feed_default_days_ago ) ),
						'refine_by_salary' => array( 'refine_by_salary' => esc_attr( $goft_wpjm_options->ziprecruiter_feed_default_refine_by_salary ) ),
					),
					'pagination' => array(
						'params'  => array(
							'page'  => 'page',
							'limit' => 'jobs_per_page',
						),
						'results' => 50,
					),
					'default' => false,
				),
				'category' => 'API',
				'weight' => 9,
			),
		);
		return array_merge( $providers, $new_providers );
	}

	/**
	 * Outputs specific Indeed feed parameter fields.
	 */
	public function feed_builder_fields( $provider ) {

		if ( ! $this->condition( $provider ) ) {
			return;
		}
?>
		<p class="params opt-param-days_ago">
			<label for="feed-fromage"><strong><?php _e( 'Days Back', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Number of days back to search.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-days_ago"></span>
			<input type="text" class="regular-text" style="width: 65px" style name="feed-days_ago" data-qarg="feed-param-days_ago" placeholder="<?php echo __( 'e.g.: 5', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-days_ago">
		</p>
		<p class="params opt-param-refine_by_salary">
			<label for="feed-fromage"><strong><?php _e( 'Min. Salary', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Limit results with annual salary greater than this number.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-refine_by_salary"></span>
			<input type="text" class="regular-text" style="width: 65px" style name="feed-refine_by_salary" data-qarg="feed-param-refine_by_salary" placeholder="<?php echo __( 'e.g.: 50000', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-refine_by_salary">
		</p>
		<p class="params opt-param-jobs_per_page">
			<label for="feed-jobs_per_page"><strong><?php _e( 'Limit', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Number of offers returned each time.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-jobs_per_page"></span>
			<input type="text" class="regular-text" style="width: 65px" style name="feed-jobs_per_page" data-qarg="feed-param-jobs_per_page" placeholder="<?php echo __( 'e.g.: 50', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-jobs_per_page">
		</p>
<?php
	}

	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {

		//$api_data = $this->get_api_data( $url );
		$api_data = new WP_Error( '-999', 'ZipRecruiter is temporarily unavailable. Please choose antother provider.' );

		if ( is_wp_error( $api_data ) || empty( $api_data['jobs'] ) ) {

			if ( ! is_wp_error( $api_data ) ) {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Consider tweaking your filters to increase job matches.', 'gofetch-wpjm' ) );
			}
			return $api_data;
		}
		return $api_data['jobs'];
	}

	/**
	 * Fetch items from the API feed.
	 */
	public function fetch_feed_items( $items, $url, $provider ) {
		global $goft_wpjm_options;

		$new_items = $sample_item = array();

		$defaults = array(
			'name'           => '',
			'posted_time'    => '',
			'location'       => '',
			'hiring_company' => array(
				'name' => '',
				'url'  => '',
			),
			'snippet'           => '',
			'category'          => '',
			'country'           => '',
			'city'              => '',
			'state'             => '',
			'url'               => '',
			'industry_name'     => '',
			'salary_min'        => '',
			'salary_max'        => '',
			'salary_min_annual' => '',
			'salary_max_annual' => '',
			'salary_interval'   => '',
			'hiring_company'    => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id']       = $provider['id'];
			$new_item['title']             = sanitize_text_field( $job['name'] );
			$new_item['date']              = GoFetch_Importer::get_valid_date( $job['posted_time'], 'api' );
			$new_item['location']          = sanitize_text_field( $job['location'] );
			$new_item['company']           = sanitize_text_field( $job['hiring_company']['name'] );
			$new_item['company_url']       = esc_url_raw( $job['hiring_company']['url'] );
			$new_item['description']       = GoFetch_Importer::format_description( $job['snippet'] );
			$new_item['link']              = esc_url_raw( html_entity_decode( $job['url'] ) );
			$new_item['category']          = sanitize_text_field( $job['category'] );
			$new_item['country']           = sanitize_text_field( $job['country'] );
			$new_item['city']              = sanitize_text_field( $job['city'] );
			$new_item['state']             = sanitize_text_field( $job['state'] );
			$new_item['salary_min']        = sanitize_text_field( $job['salary_min'] );
			$new_item['salary_min_annual'] = sanitize_text_field( $job['salary_min_annual'] );
			$new_item['salary_max_annual'] = sanitize_text_field( $job['salary_max_annual'] );
			$new_item['salary_interval']   = sanitize_text_field( $job['salary_interval'] );

			// Find the item with the most attributes to use as sample.
			if ( count( array_keys( $new_item ) ) > count( array_keys( $sample_item ) ) ) {
				$sample_item                = $new_item;
				$sample_item['description'] = GoFetch_Importer::shortened_description( $job['snippet'] );
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

		$provider['name'] = 'ZipRecruiter - Jobs & Careers';

		return array(
			'provider'    => $provider,
			'items'       => $new_items,
			'sample_item' => $sample_item,
		);
	}

	/**
	 * Set specific meta from ZipRecruiter.
	 */
	public function params_meta( $params, $item ) {
		global $goft_wpjm_options;

		if ( empty( $item['provider_id'] ) || ! $this->condition( $item['provider_id'] ) ) {
			return $params;
		}
/*
		// WPJM fields.
		if ( ! empty( $item['company'] ) ) {
			$params['meta'][ $goft_wpjm_options->setup_field_company_name ] = $item['company'];
			$params['meta'][ $goft_wpjm_options->setup_field_company_url ]  = $item['company_url'];
		}
*/
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

		$item['salary_min'] = null; unset( $item['salary_min'] );

		return $item;
	}


	/**
	 * Block robots if option is enabled.
	 */
	public function maybe_no_robots( $robots, $feed ) {
		global $goft_wpjm_options;

		if ( ! $this->condition() ) {
			return $robots;
		}

		$robots['noindex'] = true;

		return $robots;
	}

}
new GoFetch_ZipRecruiter_API_Feed_Provider();
