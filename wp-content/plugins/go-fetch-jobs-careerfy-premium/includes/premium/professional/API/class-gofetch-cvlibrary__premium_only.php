<?php
/**
 * Importer classes for providers that use an API to provide jobs.
 *
 * @package GoFetch/Admin/Premium/Pro+/API Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// ************
// DISCONTINUED
// ************
/**
 * The class for the Indeed Feed API.
 */
class GoFetch_CVLibrary_API_Feed_Provider extends GoFetch_API_Feed_Provider {

	/**
	 * @var The single instance of the class.
	 */
	protected static $_instance = null;

	/**
	 * The provider main URL.
	 */
	protected static $base_url = 'https://www.cv-library.co.uk';

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		global $goft_wpjm_options;

		$this->id      = 'cv-library.co.uk/api';
		$this->api_url = sprintf( 'https://www.cv-library.co.uk/cgi-bin/feed.xml?affid=%1$s&agencyref=%2$s', esc_attr( $goft_wpjm_options->cvlibrary_affid ), esc_attr( $goft_wpjm_options->cvlibrary_agencyref ) );
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-providers', array( $this, 'tabs' ), 45 );
		add_filter( 'goft_wpjm_providers', array( $this, 'providers' ), 15 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );
		add_filter( 'goft_wpjm_sample_item', array( $this, 'sample_item' ), 10, 2 );

		// Frontend.
		add_action( 'goft_the_job_description_content', array( $this, 'dynamic_job_description' ) );
		add_action( 'goft_no_robots', array( $this, 'maybe_no_robots' ), 10, 2 );
	}

	/**
	 * Init the Indeed tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'cvlibrary', 'CV-Library' );
		$this->tab_cvlibrary();
	}

	/**
	 * Indeed settings tab.
	 */
	protected function tab_cvlibrary() {

		$this->all_tabs->tab_sections['cvlibrary']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Affiliate ID *', 'gofetch-wpjm' ),
					'name'  => 'cvlibrary_affid',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Please reach out to your <a href="%s" target="_blank" rel="noreferrer noopener">CV-Library</a> contact to get your Affiliate ID', 'gofetch-wpjm' ), 'https://www.cv-library.co.uk/' ),
					'tip'   => __( 'You need your Affiliate ID to pull jobs using CVLibrary\'s API.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Agency Ref. *', 'gofetch-wpjm' ),
					'name'  => 'cvlibrary_agencyref',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Please reach out to your <a href="%s" target="_blank" rel="noreferrer noopener">CV-Library</a> contact to get your Agency Reference', 'gofetch-wpjm' ), 'https://www.cv-library.co.uk/' ),
					'tip'   => __( 'You need your Agency Reference to pull jobs using CVLibrary\'s API.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['cvlibrary']['jobs'] = array(
			'title' => __( 'Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Block Search Indexing', 'gofetch-wpjm' ),
					'name'  => 'cvlibrary_block_search_indexing',
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
	public function providers( $providers ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'cv-library.co.uk/api' => array(
				'API' => array(
					'info'      => 'https://www.cv-library.co.uk/',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'Affiliate ID' => 'cvlibrary_affid',
						'Agency Ref.' => 'cvlibrary_agencyref',
					),
				),
				'website'     => 'https://www.cv-library.co.uk',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-cvlibrary.png',
				'description' => 'CVLibrary is an UK employment search engine.',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://www.cv-library.co.uk/search-jobs',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'  => array( 'q'      => '' ),
						'location' => array( 'geo'    => '' ),
						'limit'    => array( 'perpage'  => esc_attr( $goft_wpjm_options->cvlibrary_feed_default_jobs_per_page ) ),
						// Custom.
						'tempperm'   => array( 'tempperm'   => esc_attr( implode( ',', (array) $goft_wpjm_options->cvlibrary_feed_default_jobtype ) ) ),
						'industry'   => array( 'industry'   => esc_attr( implode( ',', (array) $goft_wpjm_options->cvlibrary_feed_default_industry ) ) ),
						'distance'   => array( 'distance'   => esc_attr( $goft_wpjm_options->cvlibrary_feed_default_distance ) ),
						'salarytype' => array( 'salarytype' => esc_attr( $goft_wpjm_options->cvlibrary_feed_default_salary_type ) ),
						'salarymin'  => array( 'salarymin'  => esc_attr( $goft_wpjm_options->cvlibrary_feed_default_salary_min ) ),
						'salarymax'  => array( 'salarymax'  => esc_attr( $goft_wpjm_options->cvlibrary_feed_default_salary_max ) ),
						'posted'     => array( 'posted'     => esc_attr( $goft_wpjm_options->cvlibrary_feed_default_days_posted ) ),
						'applyurl'   => array( 'applyurl'   => esc_attr( $goft_wpjm_options->cvlibrary_feed_default_apply_url ) ),
						'order'      => array( 'order'      => esc_attr( $goft_wpjm_options->cvlibrary_feed_default_sort ) ),
					),
					'pagination' => array(
						'params'  => array(
							'page'  => 'offset',
							'limit' => 'perpage',
						),
						'results' => 50,
					),
					'full_description' => true,
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
		<div class="clear"></div>

		<?php $field_name = 'tempperm'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Job Type(s)', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Multiple job types are allowed.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: 400px;" multiple="multiple" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: Permanent', 'gofetch-wpjm' ); ?>">
				<option value='any' selected>Any</option>
				<option value='permanent'>Permanent</option>
				<option value='part time'>Part Time</option>
				<option value='temporary'>Tempoary</option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

		<?php $field_name = 'industry'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Industries', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Choose the jobs industries that suit your site.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: 800px;" multiple="multiple" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: IT', 'gofetch-wpjm' ); ?>">
				<option value='1'>Accounting/Financial/Insurance</option>
				<option value='2'>Agriculture</option>
				<option value='3'>Arts/Graphic</option>
				<option value='4'>Automotive/Aerospace</option>
				<option value='5'>Catering</option>
				<option value='6'>Charity</option>
				<option value='7'>Engineering</option>
				<option value='8'>Consulting</option>
				<option value='9'>Customer</option>
				<option value='10'>Distribution</option>
				<option value='11'>Education</option>
				<option value='12'>Electronics</option>
				<option value='13'>IT</option>
				<option value='14'>Legal</option>
				<option value='15'>Leisure/Tourism</option>
				<option value='16'>Management</option>
				<option value='17'>Manufacturing/Surveying</option>
				<option value='18'>Sales</option>
				<option value='19'>Media</option>
				<option value='20'>Medical/Pharmaceutical/Scientific</option>
				<option value='21'>Military/Emergency/Government</option>
				<option value='22'>Other</option>
				<option value='23'>Personnel/Recruitment</option>
				<option value='24'>Property</option>
				<option value='25'>Telecoms</option>
				<option value='26'>Administration</option>
				<option value='27'>Construction</option>
				<option value='28'>Retail/Purchasing</option>
				<option value='29'>Marketing</option>
				<option value='30'>Hospitality/Hotel</option>
				<option value='31'>Public</option>
				<option value='32'>Social</option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

		<?php $field_name = 'distance'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Distance (in miles)', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Jobs Distance (in miles).', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 3', 'gofetch-wpjm' ); ?>">
				<option value="1">1</option>
				<option value="2">2</option>
				<option value="5">5</option>
				<option value="7" selected>7</option>
				<option value="10">10</option>
				<option value="15">15</option>
				<option value="20">20</option>
				<option value="25">25</option>
				<option value="35">35</option>
				<option value="50">50</option>
				<option value="75">75</option>
				<option value="100">100</option>
				<option value="250">250</option>
				<option value="500">500</option>
				<option value="750">750</option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'salarymin'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Min. Salary', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Limit results by this mininum salary.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 65px;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 50000', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'salarymax'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Max. Salary', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Limit results by this maximum salary.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 65px;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 100000', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'salarytype'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Salary Type', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Choose the salary type if you wish to filter jobs by salary.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: Month', 'gofetch-wpjm' ); ?>">
				<option value="annum"><?php esc_attr_e( 'Any', 'gofetch-wpjm' ); ?></option>
				<option value="month"><?php esc_attr_e( 'Month', 'gofetch-wpjm' ); ?></option>
				<option value="week"><?php esc_attr_e( 'Week', 'gofetch-wpjm' ); ?></option>
				<option value="day"><?php esc_attr_e( 'Day', 'gofetch-wpjm' ); ?></option>
				<option value="hour"><?php esc_attr_e( 'Hour', 'gofetch-wpjm' ); ?></option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'posted'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Jobs Age', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The age of the job postings can be refined down to jobs posted in the last number of days.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 3', 'gofetch-wpjm' ); ?>">
				<option value="28">28</option>
				<option value="14">14</option>
				<option value="7">7</option>
				<option value="3" selected>3</option>
				<option value="1">1</option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'applyurl'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Apply URL', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Choose between direct apply URL\'s, or simple job view URL\'s', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: Job View', 'gofetch-wpjm' ); ?>">
				<option value="0"><?php esc_attr_e( 'Job View URL', 'gofetch-wpjm' ); ?></option>
				<option value="1" selected><?php esc_attr_e( 'Apply URL', 'gofetch-wpjm' ); ?></option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

		<?php $field_name = 'order'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Sort Order', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The order for the search results', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: Relevance', 'gofetch-wpjm' ); ?>">
				<option value="sm" selected><?php esc_attr_e( 'Relevance', 'gofetch-wpjm' ); ?></option>
				<option value="date"><?php esc_attr_e( 'Date', 'gofetch-wpjm' ); ?></option>
				<option value="distance"><?php esc_attr_e( 'Distance', 'gofetch-wpjm' ); ?></option>
				<option value="salarymaxDESC"><?php esc_attr_e( 'Salary (Highest First) ', 'gofetch-wpjm' ); ?></option>
				<option value="salarymaxASC"><?php esc_attr_e( 'Salary (Lowest First)', 'gofetch-wpjm' ); ?></option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>
<?php
	}

	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {
		$url ="https://www.cv-library.co.uk/cgi-bin/feed.xml?affid=104738&agencyref=282410";
		$api_data = $this->get_api_data( $url, $_xml = true );

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
			'title'        => '',
			'location'     => '',
			'agency' => array(
				'title' => '',
				'url'   => '',
			),
			'description'  => '',
			'type'         => '',
			'salary'       => '',
			'logo'         => '',
			'url'          => '',
			'id'           => '',
			'distance'     => '',
			'applications' => '',
			'posted'       => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['title'] );
			$new_item['location']    = sanitize_text_field( $job['location'] );
			$new_item['company']     = sanitize_text_field( $job['agency']['title'] );
			$new_item['company_url'] = esc_url_raw( $job['agency']['url'] );
			$new_item['description'] = GoFetch_Importer::format_description( $job['description'] );
			$new_item['job_type']    = is_array( $job['type'] ) ? implode( ',', array_map( 'sanitize_text_field', $job['type'] ) ): sanitize_text_field( $job['type'] );
			$new_item['salary']      = sanitize_text_field( $job['salary'] );
			$new_item['logo']        = esc_url( $job['logo'] );

			$parsed_url = parse_url( $job['url'] );

			// If there's not 'http' scheme then the URL is relative - default to CV base URL.
			if ( empty( $parsed_url['scheme'] ) ) {
				$job['url'] = self::$base_url . $job['url'];
			}

			$new_item['link']        = esc_url_raw( html_entity_decode( $job['url'] ) );

			$new_item['id']           = sanitize_text_field( $job['id'] );
			$new_item['distance']     = sanitize_text_field( $job['distance'] );
			$new_item['applications'] = sanitize_text_field( $job['applications'] );
			$new_item['posted']       = sanitize_text_field( $job['posted'] );

			// Find the item with the most attributes to use as sample.
			if ( count( array_keys( $new_item ) ) > count( array_keys( $sample_item ) ) ) {
				$sample_item                = $new_item;
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

		$provider['name'] = 'CVLibrary - Jobs & Careers';

		return array(
			'provider'    => $provider,
			'items'       => $new_items,
			'sample_item' => $sample_item,
		);
	}

	/**
	 * Set specific meta from CVLibrary.
	 */
	public function params_meta( $params, $item ) {
		global $goft_wpjm_options;

		if ( empty( $item['provider_id'] ) || ! $this->condition( $item['provider_id'] ) ) {
			return $params;
		}

		// Geolocation.
		if ( ! empty( $item['location'] ) ) {
			$params['meta'][ $goft_wpjm_options->setup_field_formatted_address ] = $item['location'];
			$params['meta'][ $goft_wpjm_options->setup_field_location ]          = $item['location'];
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
	  * Get full job descriptions from CV-Library API.
	  */
	public function get_job_description( $content, $post ) {
		global $goft_wpjm_options;

		$updated_content = get_post_meta( $post->ID, 'goft_updated_post_content', true );

		if ( $updated_content ) {
			return $content;
		}

		$job_data = get_post_meta( $post->ID, '_goft_wpjm_original_item', true );

		$query_args = array(
			'description_formatted' => 1,
			'key' => esc_attr( $goft_wpjm_options->cvlibrary_api_key ),
		);

		$endpoint = sprintf( 'https://partners.cv-library.co.uk/api/v1/jobs/%1$s', $job_data['id'] );
		$endpoint = add_query_arg( $query_args, $endpoint );

		$result = wp_remote_get( esc_url_raw( $endpoint ) );

		if ( ! is_wp_error( $result ) && ! empty( $result['body'] ) ) {

			$json = wp_remote_retrieve_body( $result );
			$result = json_decode( $json, true );

			if ( ! empty( $result['description_formatted'] ) ) {
				$post_content = $result['description_formatted'];
			} else if ( ! empty( $result['description'] ) ) {
				$post_content = GoFetch_Importer::format_description( $result['description'] );
			}
			if ( $post_content ) {
				$post_arr = array(
					'ID' => $post->ID,
					'post_content' => $post_content,
					'meta_input' => array(
						'goft_updated_post_content' => current_time( 'mysql' ),
					),
				);
				wp_update_post( $post_arr );

				$content = $post_content;
			}
		}
		return $content;
	}

	/**
	 * Actions that should run on the single job page.
	 */
	public function dynamic_job_description( $content ) {
		global $post;

		if ( ! $this->condition() ) {
			return $content;
		}
		return $this->get_job_description( $content, $post );
	}

	/**
	 * Block robots if option is enabled.
	 */
	public function maybe_no_robots( $robots, $feed ) {
		global $goft_wpjm_options;

		if ( ! $this->condition() ) {
			return $robots;
		}

		if ( $goft_wpjm_options->cvlibrary_block_search_indexing ) {
			$robots['noindex'] = true;
		}
		return $robots;

	}

}
new GoFetch_CVLibrary_API_Feed_Provider();
