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
class GoFetch_Careerjet_API_Feed_Provider extends GoFetch_API_Feed_Provider {

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

		$this->id      = 'api.careerjet.com';
		$this->api_url = sprintf( 'http://public.api.careerjet.net/search?affid=%1$s', esc_attr( $goft_wpjm_options->careerjet_publisher_id ) );

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-providers', array( $this, 'tabs' ), 35 );
		add_filter( 'goft_wpjm_providers', array( $this, 'providers' ), 15 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );

		// Frontend.
		add_action( 'goft_no_robots', array( $this, 'maybe_no_robots' ), 10, 2 );
	}

	/**
	 * Init the Indeed tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'careerjet', __( 'Careerjet', 'gofetch-wpjm' ) );
		$this->tab_careerjet();
	}

	/**
	 * Indeed settings tab.
	 */
	protected function tab_careerjet() {

		$info_url = 'http://www.careerjet.com/partners/';

		$this->all_tabs->tab_sections['careerjet']['logo'] = array(
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

		$this->all_tabs->tab_sections['careerjet']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title'   => __( 'Country', 'gofetch-wpjm' ),
					'name'    => 'careerjet_feed_default_locale_code',
					'type'    => 'select',
					'choices' => $this->locales(),
					'tip'     => __( 'Select your preferred country.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Publisher ID *', 'gofetch-wpjm' ),
					'name'  => 'careerjet_publisher_id',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Sign up for a free <a href="%1$s" target="_new">Careerjet Publisher Account</a>', 'gofetch-wpjm' ), esc_url( $info_url ) ),
					'tip'   => __( 'You need a publisher ID in order to pull jobs from Careerjet.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['careerjet']['defaults'] = array(
			'title' => __( 'Feed Defaults', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Job Type', 'gofetch-wpjm' ),
					'name'  => 'careerjet_feed_default_contracttype',
					'type'  => 'select',
					'choices' => array(
						''  => __( 'Any', 'gofetch-wpjm' ),
						'p' => 'Permanent',
						'c' => 'Contract',
						't' => 'Temporary',
						'i' => 'Training',
						'v' => 'Voluntary',
					),
					'tip' => __( 'Choose a specific job type if you want to target your jobs (only one job type per request is allowed).', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Contract Period', 'gofetch-wpjm' ),
					'name'  => 'careerjet_feed_default_contractperiod',
					'type'  => 'select',
					'choices' => array(
						''  => __( 'Any', 'gofetch-wpjm' ),
						'f' => 'Full-time',
						'p' => 'Part-time',
					),
					'tip' => __( 'Choose a specific contract period if you want to target your jobs (only one contract period per request is allowed).', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Sorting', 'gofetch-wpjm' ),
					'name'  => 'careerjet_feed_default_sort',
					'type'  => 'select',
					'choices' => array(
						'relevance' => 'Relevance',
						'date'      => 'Date',
						'salary'    => 'Salary',
					),
					'tip' => __( 'Sort by <em>relevance</em>, <em>date</em> or <em>salary</em>.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Limit', 'gofetch-wpjm' ),
					'name'  => 'careerjet_feed_default_pagesize',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Maximum number of results returned per query. Limit is 50.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['careerjet']['jobs'] = array(
			'title' => __( 'Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Block Search Indexing', 'gofetch-wpjm' ),
					'name'  => 'careerjet_block_search_indexing',
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
			'api.careerjet.com' => array(
				'API' => array(
					'info'      => 'http://www.careerjet.pt/partners/api/',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'Publisher ID' => 'careerjet_publisher_id',
					),
				),
				'website'     => 'http://www.careerjet.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-careerjet.svg',
				'description' => 'Careerjet is an employment search engine.',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'http://www.careerjet.com/search/advanced.html',
					// Regex mappings for known/custom tags used in the feed description.
					'regexp_mappings' => array(
						'company'  => '/(.*?)-.*?-\s/is',                                   // e.g: Google - San Francisco -
						'location' => '/(?(?!.*?\s-\s.*?\s-\s.*?)(.*?)-|.*?-(.*?)-\s)/is',  // e.g: Google - San Francisco - OR San Francisco -
					),
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'        => array( 'keywords'       => '' ),
						'location'       => array( 'location'       => '' ),
						'sort'           => array( 'sort'           => esc_attr( $goft_wpjm_options->careerjet_feed_default_sort ) ),
						'pagesize'       => array( 'pagesize'       => esc_attr( $goft_wpjm_options->careerjet_feed_default_pagesize ) ),
						'contracttype'   => array( 'contracttype'   => esc_attr( $goft_wpjm_options->careerjet_feed_default_contracttype ) ),
						'contractperiod' => array( 'contractperiod' => esc_attr( $goft_wpjm_options->careerjet_feed_default_contractperiod ) ),
						// Custom.
						'locale_code' => array( 'locale_code' => esc_attr( $goft_wpjm_options->careerjet_feed_default_locale_code ) ),
					),
					'pagination' => array(
						'params'  => array(
							'page'  => 'page',
							'limit' => 'pagesize',
						),
						'results' => 50,
					),
					'default' => false,
				),
				'special' => array(
					'redirect_url' => array(
						'query'     => '/html/head/noscript/meta/@content',
						'delimiter' => '=',
					),
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '(//article[@id="job"]//section[contains(@class,"content")][1])[1]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '(//article[@id="job"]//p[contains(@class,"company")])[1]',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '(//article[@id="job"]//ul[contains(@class,"details")]/li[1])[1]',
						),
						'salary' => array(
							'nicename' => __( 'Salary', 'gofetch-wpjm' ),
							'query'    => '//article[@id="job"]//ul[contains(@class,"details")]//li[2][text()[contains(.," per ")]]',
						),
						'logo' => array(
							'nicename'       => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'          => '//article[@id="job"]//img[contains(@class,"logo")][1]/@data-src',
							'query_lazyload' => '//article[@id="job"]//img[contains(@class,"logo")][1]/@src',
						),
					),
				),
				'multi_region_match' => 'careerjet',
				'region_param_domain' => 'locale_code',
				'region_domains'      => $this->countries(),
				'region_default'      => $goft_wpjm_options->careerjet_feed_default_locale_code,
				'weight' => 9,
				'category' => 'API',
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

		if ( false === strpos( $provider, 'api.' ) ) :
?>
			<p class="params opt-param-snl">
				<label for="feed-snl"><strong><?php _e( 'Description Length', 'gofetch-wpjm' ); ?></strong>
					<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The job description length. Limit is 500.', 'gofetch-wpjm' ) ); ?>"></span></span>
				</label><span class="feed-param-snl"></span>
				<input type="text" class="regular-text" style="width: 50px" name="feed-snl" data-qarg="feed-param-snl" placeholder="<?php echo __( 'e.g.: 100', 'gofetch-wpjm' ); ?>">
				<input type="hidden" name="feed-param-snl">
			</p>
			<p class="params opt-param-psz">
				<label for="feed-psz"><strong><?php _e( 'Limit', 'gofetch-wpjm' ); ?></strong>
					<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Number of offers to retrieve. Limit is 50.', 'gofetch-wpjm' ) ); ?>"></span></span>
				</label><span class="feed-param-psz"></span>
				<input type="text" class="regular-text" style="width: 50px" name="feed-psz" data-qarg="feed-param-psz" placeholder="<?php echo __( 'e.g.: 30', 'gofetch-wpjm' ); ?>">
				<input type="hidden" name="feed-param-psz">
			</p>
<?php
		else :
?>
			<p class="params opt-param-contracttype">
				<label for="feed-contracttype"><strong><?php _e( 'Job Type', 'gofetch-wpjm' ); ?></strong>
					<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Character code for contract type. <br/><br/>e.g: p (permanent), c (contract), t (temporary), i (training), v (voluntary)', 'gofetch-wpjm' ) ); ?>"></span></span>
				</label><span class="feed-param-contracttype"></span>
				<input type="text" class="regular-text" style="width: 100px" name="feed-contracttype" data-qarg="feed-param-contracttype" placeholder="<?php echo __( 'e.g.: p', 'gofetch-wpjm' ); ?>">
				<input type="hidden" name="feed-param-contracttype">
			</p>
			<p class="params opt-param-contractperiod">
				<label for="feed-contractperiod"><strong><?php _e( 'Contract Period', 'gofetch-wpjm' ); ?></strong>
					<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Character code for contract period. <br/><br/>e.g: f (full-time), p (part-time)', 'gofetch-wpjm' ) ); ?>"></span></span>
				</label><span class="feed-param-contractperiod"></span>
				<input type="text" class="regular-text" style="width: 100px" name="feed-contractperiod" data-qarg="feed-param-contractperiod" placeholder="<?php echo __( 'e.g.: f', 'gofetch-wpjm' ); ?>">
				<input type="hidden" name="feed-param-contractperiod">
			</p>
			<p class="params opt-param-sort">
				<label for="feed-sort"><strong><?php _e( 'Sort', 'gofetch-wpjm' ); ?></strong>
					<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Results sort type. <br/><br/>e.g.: relevance, date or salary', 'gofetch-wpjm' ) ); ?>"></span></span>
				</label><span class="feed-param-sort"></span>
				<input type="text" class="regular-text" style="width: 110px" name="feed-sort" data-qarg="feed-param-sort" placeholder="<?php echo __( 'e.g.: relevance', 'gofetch-wpjm' ); ?>">
				<input type="hidden" name="feed-param-sort">
			</p>
			<p class="params opt-param-pagesize">
				<label for="feed-pagesize"><strong><?php _e( 'Limit', 'gofetch-wpjm' ); ?></strong>
					<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Number of offers returned each time.', 'gofetch-wpjm' ) ); ?>"></span></span>
				</label><span class="feed-param-pagesize"></span>
				<input type="text" class="regular-text" style="width: 65px" name="feed-pagesize" data-qarg="feed-param-pagesize" placeholder="<?php echo __( 'e.g.: 20', 'gofetch-wpjm' ); ?>">
				<input type="hidden" name="feed-param-pagesize">
			</p>
<?php
		endif;
	}

	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {

		$params = array(
			'user_ip'    => urlencode( BC_Framework_Utils::get_user_ip() ),
			'user_agent' => urlencode( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) ),
		);
		$url = add_query_arg( $params, $url );

		$api_data = $this->get_api_data( $url );

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
			'title'       => '',
			'date'        => '',
			'locations'   => '',
			'company'     => '',
			'description' => '',
			'url'         => '',
			'site'        => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['title'] );
			$new_item['date']        = GoFetch_Importer::get_valid_date( $job['date'], 'api' );
			$new_item['location']    = sanitize_text_field( $job['locations'] );
			$new_item['company']     = sanitize_text_field( $job['company'] );
			$new_item['description'] = GoFetch_Importer::format_description( $job['description'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( ( $job['url'] ) ) );
			$new_item['site']        = sanitize_text_field( $job['site'] );

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

		$provider['name'] = 'Careerjet - Jobs & Careers';

		return array(
			'provider'    => $provider,
			'items'       => $new_items,
			'sample_item' => $sample_item,
		);
	}

	/**
	 * Set specific meta from Careerjet.
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
	 * Block robots if option is enabled.
	 */
	public function maybe_no_robots( $robots, $feed ) {
		global $goft_wpjm_options;

		if ( ! $this->condition() ) {
			return $robots;
		}

		if ( $goft_wpjm_options->careerjet_block_search_indexing ) {
			$robots['noindex'] = true;
		}
		return $robots;

	}


	/**
	 * Retrieves the list of country codes for this provider.
	 */
	protected function countries() {
		return array(
			'es_AR' => 'Argentina',
			'en_AU' => 'Australia',
			'de_AT' => 'Austria',
			'fr_BE' => 'Belgium',
			'nl_BE' => 'Belgium',
			'es_BO' => 'Bolivia',
			'pt_BR' => 'Brazil',
			'en_CA' => 'Canada',
			'fr_CA' => 'Canada',
			'es_CL' => 'Chile',
			'en_CN' => 'China',
			'zh_CN' => 'China',
			'es_CR' => 'Costa Rica',
			'es_CO' => 'Colombia',
			'cs_CZ' => 'Czech Republic',
			'da_DK' => 'Denmark',
			'es_DO' => 'Dominican Republic',
			'es_EC' => 'Ecuador',
			'fi_FI' => 'Finland',
			'fr_FR' => 'France',
			'de_DE' => 'Germany',
			'es_GT' => 'Guatemala',
			'en_HK' => 'Hong Kong',
			'hu_HU' => 'Hungary',
			'en_IN' => 'India',
			'en_IE' => 'Ireland',
			'it_IT' => 'Italy',
			'ja_JP' => 'Japan',
			'ko_KR' => 'Korea',
			'fr_LU' => 'Luxembourg',
			'en_MY' => 'Malaysia',
			'es_MX' => 'Mexico',
			'fr_MA' => 'Morocco',
			'nl_NL' => 'Netherlands',
			'en_NZ' => 'New Zealand',
			'no_NO' => 'Norway',
			'en_OM' => 'Oman',
			'en_PK' => 'Pakistan',
			'es_PA' => 'Panama',
			'es_PY' => 'Paraguay',
			'es_PE' => 'Peru',
			'en_PH' => 'Philippines',
			'pl_PL' => 'Poland',
			'pt_PT' => 'Portugal',
			'es_PR' => 'Puerto Rico',
			'en_QA' => 'Qatar',
			'ru_RU' => 'Russia',
			'en_SG' => 'Singapore',
			'sk_SK' => 'Slovakia',
			'en_ZA' => 'South Africa',
			'es_ES' => 'Spain',
			'sv_SE' => 'Sweden',
			'de_CH' => 'Switzerland',
			'fr_CH' => 'Switzerland',
			'en_TW' => 'Taiwan',
			'tr_TR' => 'Turkey',
			'ru_UA' => 'Ukraine',
			'uk_UA' => 'Ukraine',
			'en_AE' => 'United Arab Emirates',
			'en_GB' => 'United Kingdom',
			'en_US' => 'United States',
			'es_UY' => 'Uruguay',
			'es_VE' => 'Venezuela',
			'en_VN' => 'Vietnam',
			'vi_VN' => 'Vietnam',
		);
	}

	/**
	 * Retrieve a list of all the available country codes.
	 */
	public function locales() {
		$locales = array (
			'cs_CZ' => 'Czech Republic (https://www.careerjet.cz)',
			'da_DK' => 'Denmark (https://www.careerjet.dk)',
			'de_AT' => 'Austria (https://www.careerjet.at)',
			'de_CH' => 'Switzerland (https://www.careerjet.ch)',
			'de_DE' => 'Germany (https://www.careerjet.de)',
			'en_AE' => 'United Arab Emirates (https://www.careerjet.ae)',
			'en_AU' => 'Australia (https://www.careerjet.com.au)',
			'en_CA' => 'Canada (https://www.careerjet.ca)',
			'en_CN' => 'China (https://en.careerjet.cn)',
			'en_HK' => 'Hong Kong (https://www.careerjet.hk)',
			'en_IE' => 'Ireland (https://www.careerjet.ie)',
			'en_IN' => 'India (https://www.careerjet.co.in)',
			'en_MY' => 'Malaysia (https://www.careerjet.com.my)',
			'en_NZ' => 'New Zealand (https://www.careerjet.co.nz)',
			'en_OM' => 'Oman (https://www.careerjet.com.om)',
			'en_PH' => 'Philippines (https://www.careerjet.ph)',
			'en_PK' => 'Pakistan (https://www.careerjet.com.pk)',
			'en_QA' => 'Qatar (https://www.careerjet.com.qa)',
			'en_SG' => 'Singapore (https://www.careerjet.sg)',
			'en_GB' => 'United Kingdom (https://www.careerjet.co.uk)',
			'en_US' => 'United States (https://www.careerjet.com)',
			'en_ZA' => 'South Africa (https://www.careerjet.co.za)',
			'en_TW' => 'Taiwan (https://www.careerjet.com.tw)',
			'en_VN' => 'Vietnam (https://www.careerjet.vn)',
			'en_NG' => 'Nigeria (https://www.careerjet.com.ng)',
			'en_MT' => 'Malta (https://www.careerjet.com.mt)',
			'es_AR' => 'Argentina (https://www.opcionempleo.com.ar)',
			'es_BO' => 'Bolivia (https://www.opcionempleo.com.bo)',
			'es_CL' => 'Chile (https://www.opcionempleo.cl)',
			'es_CR' => 'Costa Rica (https://www.opcionempleo.co.cr)',
			'es_DO' => 'Dominican Republic (https://www.opcionempleo.com.do)',
			'es_EC' => 'Ecuador (https://www.opcionempleo.ec)',
			'es_ES' => 'Spain (https://www.opcionempleo.com)',
			'es_GT' => 'Guatemala (https://www.opcionempleo.com.gt)',
			'es_MX' => 'Mexico (https://www.opcionempleo.com.mx)',
			'es_PA' => 'Panama (https://www.opcionempleo.com.pa)',
			'es_PE' => 'Peru (https://www.opcionempleo.com.pe)',
			'es_PR' => 'Puerto Rico (https://www.opcionempleo.com.pr)',
			'es_PY' => 'Paraguay (https://www.opcionempleo.com.py)',
			'es_UY' => 'Uruguay (https://www.opcionempleo.com.uy)',
			'es_VE' => 'Venezuela (https://www.opcionempleo.com.ve)',
			'fi_FI' => 'Finland (https://www.careerjet.fi)',
			'fr_CA' => 'Canada (https://fr.careerjet.ca)',
			'fr_BE' => 'Belgium (https://www.optioncarriere.be)',
			'fr_CH' => 'Switzerland (https://www.optioncarriere.ch)',
			'fr_FR' => 'France (https://www.optioncarriere.com)',
			'fr_LU' => 'Luxembourg (https://www.optioncarriere.lu)',
			'fr_MA' => 'Morocco (https://www.optioncarriere.ma)',
			'hu_HU' => 'Hungary (https://www.careerjet.hu)',
			'it_IT' => 'Italy (https://www.careerjet.it)',
			'ja_JP' => 'Japan (https://www.careerjet.jp)',
			'ko_KR' => 'Korea (https://www.careerjet.co.kr)',
			'nl_BE' => 'Belgium (https://www.careerjet.be)',
			'nl_NL' => 'Netherlands (https://www.careerjet.nl)',
			'no_NO' => 'Norway (https://www.careerjet.no)',
			'pl_PL' => 'Poland (https://www.careerjet.pl)',
			'pt_PT' => 'Portugal (https://www.careerjet.pt)',
			'pt_BR' => 'Brazil (https://www.careerjet.com.br)',
			'ru_RU' => 'Russia (https://www.careerjet.ru)',
			'ru_UA' => 'Ukraine (https://www.careerjet.com.ua)',
			'sv_SE' => 'Sweden (https://www.careerjet.se)',
			'sk_SK' => 'Slovakia (https://www.careerjet.sk)',
			'tr_TR' => 'Turkey (https://www.careerjet.com.tr)',
			'uk_UA' => 'Ukraine (https://www.careerjet.ua)',
			'vi_VN' => 'Vietnam (https://www.careerjet.com.vn)',
			'zh_CN' => 'China (https://www.careerjet.cn)',
		);
		asort( $locales );
		return $locales;
	}

}
new GoFetch_Careerjet_API_Feed_Provider();
