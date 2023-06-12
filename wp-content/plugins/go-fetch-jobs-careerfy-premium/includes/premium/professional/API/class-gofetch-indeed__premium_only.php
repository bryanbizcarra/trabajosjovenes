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
class GoFetch_Indeed_API_Feed_Provider extends GoFetch_API_Feed_Provider {

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

		$this->id      = 'api.indeed.com';
		$this->api_url = sprintf( 'https://api.indeed.com/ads/apisearch?publisher=%1$s&v=2', esc_attr( $goft_wpjm_options->indeed_publisher_id ) );

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-providers', array( $this, 'tabs' ), 55 );
		add_filter( 'goft_wpjm_providers', array( $this, 'config' ), 15 );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );
		add_filter( 'goft_wpjm_sample_item', array( $this, 'sample_item' ), 10, 2 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );

		// Frontend.
		add_action( 'goft_wpjm_single_goft_job', array( $this, 'single_job_page_hooks' ) );

		add_action( 'goft_no_robots', array( $this, 'maybe_no_robots' ), 10, 2 );
	}

	/**
	 * Init the Indeed tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'indeed', __( 'Indeed', 'gofetch-wpjm' ) );
		$this->tab_indeed();
	}

	/**
	 * Indeed settings tab.
	 */
	protected function tab_indeed() {

		$info_url = 'https://indeed.force.com/employerSupport1/s/';

		$this->all_tabs->tab_sections['indeed']['logo'] = array(
			'title' => '',
			'fields' => array(
				array(
					'title'  => '',
					'name'   => '_blank',
					'type'   => 'custom',
					'render' => function() {
						echo '<img class="api-providers-logo" src="' . esc_url( $this->get_config('logo') ) . '">';
					},
				),
			),
		);

		$this->all_tabs->tab_sections['indeed']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Country', 'gofetch-wpjm' ),
					'name'  => 'indeed_feed_default_co',
					'type'  => 'select',
					'choices' => $this->locales(),
					'tip' => __( 'Select your preferred country.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Publisher ID *', 'gofetch-wpjm' ),
					'name'  => 'indeed_publisher_id',
					'type'  => 'text',
					'desc'  => __( '<em>Indeed</em> seems to have discontinued their API. If you have an old API key, please paste it on this field.', 'gofetch-wpjm' ),
					'tip'   => __( 'You need a publisher ID in order to pull jobs from Indeed.', 'gofetch-wpjm' )
							. '<br/><br/>' . __( '<strong>Note:</strong> Indeed may not be accepting new applications.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['indeed']['defaults'] = array(
			'title' => __( 'Feed Defaults', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Radius', 'gofetch-wpjm' ),
					'name'  => 'indeed_feed_default_radius',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Distance from search location ("as the crow flies")', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Latitude/Longitude', 'gofetch-wpjm' ),
					'name'  => 'indeed_default_latlong',
					'type'  => 'checkbox',
					'tip' => __( 'If checked, returns latitude and longitude information for each job result.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Days Back', 'gofetch-wpjm' ),
					'name'  => 'indeed_feed_default_fromage',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Number of days back to search.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Site Type', 'gofetch-wpjm' ),
					'name'  => 'indeed_feed_default_st',
					'type'  => 'select',
					'choices' => array(
						'employer' => 'Employer',
						'jobsite'  => 'Job Site',
					),
					'tip' => __( 'To show only jobs from job boards use "jobsite". For jobs from direct employer websites use "employer".', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Job Type', 'gofetch-wpjm' ),
					'name'  => 'indeed_feed_default_jt',
					'type'  => 'select',
					'choices' => array(
						''           => __( 'Any', 'gofetch-wpjm' ),
						'fulltime'   => 'Fulltime',
						'parttime'   => 'Parttime',
						'contract'   => 'Contract',
						'internship' => 'Internship',
						'temporary'  => 'Temporary',
					),
					'tip' => __( 'Choose a specific job type if you want to target your jobs (only one job type per request is allowed).', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Channel Name', 'gofetch-wpjm' ),
					'name'  => 'indeed_feed_default_chnl',
					'type'  => 'text',
					'tip' => __( 'Group API requests to a specific channel name.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Sorting', 'gofetch-wpjm' ),
					'name'  => 'indeed_feed_default_sort',
					'type'  => 'select',
					'choices' => array(
						'relevance' => 'Relevance',
						'date'      => 'Date',
					),
					'tip' => __( 'Sort by <em>relevance</em> or <em>date</em>.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Limit', 'gofetch-wpjm' ),
					'name'  => 'indeed_feed_default_limit',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Maximum number of results returned per query. Limit is 25.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['indeed']['sponsored'] = array(
			'title' => __( 'Sponsored Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Feature Sponsored Jobs', 'gofetch-wpjm' ),
					'name'  => 'indeed_feature_sponsored',
					'type'  => 'checkbox',
					'desc'  => __( 'Yes', 'gofetch-wpjm' ),
					'tip' => sprintf( __( 'Check this option to automatically feature Sponsored jobs. These jobs can be filtered using a special meta key named <code>%s</code>.', 'gofetch-wpjm' ), '_goft_wpjm_indeed_sponsored' ),
				),
			),
		);

		$this->all_tabs->tab_sections['indeed']['jobs'] = array(
			'title' => __( 'Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Block Search Indexing', 'gofetch-wpjm' ),
					'name'  => 'indeed_block_search_indexing',
					'type'  => 'checkbox',
					'desc' => __( 'Yes', 'gofetch-wpjm' ),
					'tip' => __( 'Check this option to block search robots from indexing imported jobs pages from this provider API.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( 'This option should be checked for providers that do not allow indexing their jobs.', 'gofetch-wpjm' ),
				),
				array(
					'title'  => __( '<small>(*) Required field</small>', 'gofetch-wpjm' ),
					'name'   => '_blank',
					'type'   => 'custom',
					'render' => '__return_false',
				),
			),
		);
	}

	/**
	 * Enqueues Indeed in the list of providers.
	 */
	public function config( $providers = array() ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'api.indeed.com' => array(
				'API' => array(
					'info' => 'https://ads.indeed.com/jobroll/xmlfeed',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'Publisher ID' => 'indeed_publisher_id',
					),
				),
				'website'     => 'https://www.indeed.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-indeed.png',
				'description' => 'One search. All jobs',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://www.indeed.com/advanced_search',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'  => array( 'q'      => '' ),
						'location' => array( 'l'      => '' ),
						'country' => array( 'co'  => esc_attr( $goft_wpjm_options->indeed_feed_default_co ) ),
						'limit'    => array( 'limit'  => esc_attr( $goft_wpjm_options->indeed_feed_default_limit ) ),
						'radius'   => array( 'radius' => esc_attr( $goft_wpjm_options->indeed_feed_default_radius ) ),
						'type'     => array( 'jt'     => array(
							'placeholder'   => "e.g: fulltime",
							'default_value' => esc_attr( $goft_wpjm_options->indeed_feed_default_jt ),
						) ),
						// Custom.
						'co'      => array( 'co'      => esc_attr( $goft_wpjm_options->indeed_feed_default_co ) ),
						'latlong' => array( 'latlong' => (int) $goft_wpjm_options->indeed_feed_default_latlong ),
						'fromage' => array( 'fromage' => esc_attr( $goft_wpjm_options->indeed_feed_default_fromage ) ),
						'st'      => array( 'st'      => esc_attr( $goft_wpjm_options->indeed_feed_default_st ) ),
						'sort'    => array( 'sort'    => esc_attr( $goft_wpjm_options->indeed_feed_default_sort ) ),
						'chnl'    => array( 'chnl'    => esc_attr( $goft_wpjm_options->indeed_feed_default_chnl ) ),
					),
					'pagination' => array(
						'params'  => array(
							'page'  => 'start',
							'limit' => 'limit',
						),
						'type'    => 'offset',
						'results' => 25,
					),
					'default' => false,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"jobsearch-jobDescriptionText")]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"jobsearch-InlineCompanyRating")]/div/following-sibling::div',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"jobsearch-InlineCompanyRating")]/following-sibling::div',
						),
						'salary' => array(
							'nicename' => __( 'Salary', 'gofetch-wpjm' ),
							'query'    => '//div[@id="salaryInfoAndJobType"]/span[contains(@class,"attribute_snippet")]',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//div[@class="icl-Card-body"]/a/img/@src',
						),
					),
				),
				'multi_region_match' => 'indeed',
				'region_param_domain' => 'co',
				'region_domains'      => $this->locales(),
				'region_default'      => $goft_wpjm_options->indeed_feed_default_co,
				'category' => 'API',
				'weight'   => 10,
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
		<?php $field_name = 'latlong'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Lat/Lng?', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Return Latitude and Longitude information for each job result?', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>">
				<option value="1">Yes</option>
				<option value="0">No</option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<p class="params opt-param-fromage">
			<label for="feed-fromage"><strong><?php _e( 'Days Back', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Number of days back to search.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-fromage"></span>
			<input type="text" class="regular-text" style="width: 65px" style name="feed-fromage" data-qarg="feed-param-fromage" placeholder="<?php echo __( 'e.g.: 5', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-fromage">
		</p>

		<div class="clear"></div>

		<p class="params opt-param-st">
			<label for="feed-st"><strong><?php _e( 'Site Type', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'To show only jobs from job boards use <em>jobsite</em>. For jobs from direct employer websites use <em>employer</em>.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-st"></span>
			<input type="text" class="regular-text" style="width: 100px" style name="feed-st" data-qarg="feed-param-st" placeholder="<?php echo __( 'e.g.: jobsite', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-st">
		</p>

		<?php $field_name = 'sort'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Sort', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The order in which to return results.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>">
				<option value="relevance">Relevance</option>
				<option value="date">Date</option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<p class="params opt-param-chnl">
			<label for="feed-chnl"><strong><?php _e( 'Channel Name', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Group API requests to a specific channel name.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-chnl"></span>
			<input type="text" class="regular-text" style="width: 150px" style name="feed-chnl" data-qarg="feed-param-chnl" placeholder="<?php echo __( 'e.g.: my-jobs-site', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-chnl">
		</p>

<?php
	}

	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {

		$api_data = $this->get_api_data( $url, $_xml_data = true );

		if ( is_wp_error( $api_data ) || empty( $api_data['results']['result'] ) ) {

			if ( ! is_wp_error( $api_data ) ) {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Make sure you\'ve specified a \'Keyword\' and/or \'Location\' ', 'gofetch-wpjm' ) );
			}
			return $api_data;
		}
		return $api_data['results']['result'];
	}

	/**
	 * Fetch items from the API feed.
	 */
	public function fetch_feed_items( $items, $url, $provider ) {
		global $goft_wpjm_options;

		$new_items = $sample_item = array();

		$defaults = array(
			'jobtitle'              => '',
			'company'               => '',
			'snippet'               => '',
			'date'                  => '',
			'url'                   => '',
			'city'                  => '',
			'country'               => '',
			'state'                 => '',
			'latitude'              => '',
			'longitude'             => '',
			'formattedLocationFull' => '',
			'sponsored'             => '',
			'expired'               => '',
			'onmousedown'           => '',
			'jobkey'                => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['jobtitle'] );
			$new_item['date']        = GoFetch_Importer::get_valid_date( $job['date'], 'api' );
			$new_item['location']    = sanitize_text_field( $job['formattedLocationFull'] );
			$new_item['state']       = sanitize_text_field( $job['state'] );
			$new_item['city']        = sanitize_text_field( $job['city'] );
			$new_item['country']     = sanitize_text_field( $job['country'] );
			$new_item['company']     = sanitize_text_field( $job['company'] );
			$new_item['latitude']    = sanitize_text_field( $job['latitude'] );
			$new_item['longitude']   = sanitize_text_field( $job['longitude'] );
			$new_item['description'] = GoFetch_Importer::format_description( $job['snippet'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( $job['url'] ) );
			$new_item['_jobkey']     = sanitize_text_field( $job['jobkey'] );

			$new_item['link_atts'] = array(
				'javascript' => array(
					'onmousedown' => sanitize_text_field( $job['onmousedown'] ),
				),
				'class' => (bool) $job['sponsored'] ? 'goft-wpjm-indeed-sponsored': '',
			);

			$new_item['sponsored'] = sanitize_text_field( $job['sponsored'] );
			$new_item['expired']   = sanitize_text_field( $job['expired'] );

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

		$provider['name'] = 'Job Search | Indeed';

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

		$params['meta']['_featured'] = isset( $item['sponsored'] ) && (bool) $item['sponsored'] && $goft_wpjm_options->indeed_feature_sponsored ? 1: 0;

		if ( isset( $item['sponsored'] ) && (bool) $item['sponsored'] ) {
			$params['meta']['_goft_wpjm_indeed_sponsored'] = (bool) $item['sponsored'];
		}

		// Other link attributes.
		if ( ! empty( $item['link_atts'] ) ) {
			$params['meta']['_goft_link_atts'] = $item['link_atts'];
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

		$item['sponsored'] = null; unset( $item['sponsored'] );
		$item['expired']   = null; unset( $item['expired'] );
		$item['_jobkey']   = null; unset( $item['_jobkey'] );

		return $item;
	}

	/**
	 * Retrieves the list of country codes for this provider.
	 */
	protected function locales() {
		return array(
			'ar' => 'Argentina',
			'au' => 'Australia',
			'at' => 'Austria',
			'bh' => 'Bahrain',
			'be' => 'Belgium',
			'br' => 'Brazil',
			'ca' => 'Canada',
			'cl' => 'Chile',
			'cn' => 'China',
			'co' => 'Colombia',
			'cz' => 'Czech Republic',
			'dk' => 'Denmark',
			'fi' => 'Finland',
			'fr' => 'France',
			'de' => 'Germany',
			'gr' => 'Greece',
			'hk' => 'Hong Kong',
			'hu' => 'Hungary',
			'in' => 'India',
			'id' => 'Indonesia',
			'ie' => 'Ireland',
			'il' => 'Israel',
			'it' => 'Italy',
			'jp' => 'Japan',
			'kr' => 'Korea',
			'kw' => 'Kuwait',
			'lu' => 'Luxembourg',
			'my' => 'Malaysia',
			'mx' => 'Mexico',
			'nl' => 'Netherlands',
			'nz' => 'New Zealand',
			'no' => 'Norway',
			'om' => 'Oman',
			'pk' => 'Pakistan',
			'pe' => 'Peru',
			'ph' => 'Philippines',
			'pl' => 'Poland',
			'pt' => 'Portugal',
			'qt' => 'Qatar',
			'ro' => 'Romania',
			'ru' => 'Russia',
			'sa' => 'Saudi Arabia',
			'sg' => 'Singapore',
			'za' => 'South Africa',
			'es' => 'Spain',
			'se' => 'Sweden',
			'ch' => 'Switzerland',
			'tw' => 'Taiwan',
			'th' => 'Thailand',
			'tr' => 'Turkey',
			'ae' => 'United Arab Emirates',
			'gb' => 'United Kingdom',
			'us' => 'United States',
			've' => 'Venezuela',
			'vn' => 'Vietnam',
		);
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

		// Enqueue other hooks for this provider.
		add_filter( 'goft_wpjm_read_more_link_attributes', array( $this, 'link_attributes' ), 10, 2 );
		add_filter( 'goft_wpjm_source_link_attributes', array( $this, 'link_attributes' ), 10, 2 );
		add_filter( 'goft_wpjm_external_link_qargs', array( $this, 'external_link_args' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', function() {
			wp_enqueue_script( 'indeed-click-tracking', '//gdc.indeed.com/ads/apiresults.js', array(), GoFetch_Jobs()->version, true );
		} );
	}

	/**
	 * Apply additional attributes to each external job link.
	 */
	public function link_attributes( $attributes, $post ) {

		$link_atts = get_post_meta( $post->ID, '_goft_link_atts', true );

		if ( ! empty( $link_atts['javascript'] ) ) {

			if ( is_array( $link_atts['javascript'] ) ) {

				foreach ( $link_atts['javascript'] as $event => $action ) {
					$attributes[ $event ] = esc_attr( $action );
				}
			} else {
				$attributes['onmousedown'] = esc_attr( $link_atts['javascript'] );
			}
		}

		if ( ! empty( $link_atts['class'] ) ) {
			$attributes['class'] .= ' ' . esc_attr( $link_atts['class'] );
		}
		return $attributes;
	}

	/**
	 * Block robots if option is enabled.
	 */
	public function maybe_no_robots( $robots, $feed ) {
		global $goft_wpjm_options;

		if ( ! $this->condition() ) {
			return $robots;
		}

		if ( $goft_wpjm_options->indeed_block_search_indexing ) {
			$robots['noindex'] = true;
		}
		return $robots;
	}

	/**
	 * Append additional required args to each job link.
	 */
	public function external_link_args( $args, $params ) {
		global $goft_wpjm_options;

		if ( empty( $params['website'] ) || false === strpos( $params['website'], $this->id ) ) {
			return $args;
		}

		$args['publisher'] = $goft_wpjm_options->indeed_publisher_id;
		$args['userip']    = urlencode( BC_Framework_Utils::get_user_ip() );
		$args['useragent'] = urlencode( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) );
		$args['v']         = 2;

		return $args;
	}

}
new GoFetch_Indeed_API_Feed_Provider();
