<?php
/**
 * Importer classes for providers that use an API to provide jobs.
 *
 * Docs: https://www.themuse.com/developers/api/v2
 *
 * @package GoFetch/Admin/Premium/Professional/API Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The class for The Muse Feed API.
 */
class GoFetch_TheMuse_API_Feed_Provider extends GoFetch_API_Feed_Provider {

	/**
	 * The max number of company pages to iterate over.
	 */
	protected static $max_company_pages = 2;

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

		$this->id      = 'themuse.com/api';
		$this->api_url = sprintf( 'https://www.themuse.com/api/public/jobs?api_key=%1$s&page=1', esc_attr( $goft_wpjm_options->themuse_api_key ) );

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-providers', array( $this, 'tabs' ), 85 );
		add_filter( 'goft_wpjm_providers', array( $this, 'providers' ), 15 );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );
		add_filter( 'goft_wpjm_sample_item', array( $this, 'sample_item' ), 10, 2 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );

		// Frontend.
		add_action( 'goft_wpjm_single_goft_job', array( $this, 'single_job_page_hooks' ) );
	}

	/**
	 * Init the Indeed tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'themuse', __( 'The Muse', 'gofetch-wpjm' ) );
		$this->tab_themuse();
	}

	/**
	 * Indeed settings tab.
	 */
	protected function tab_themuse() {

		$info_url = 'https://www.themuse.com/developers/api/v2/apps';

		$this->all_tabs->tab_sections['themuse']['logo'] = array(
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

		$this->all_tabs->tab_sections['themuse']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'API Key', 'gofetch-wpjm' ),
					'name'  => 'themuse_publisher_id',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Sign up for a free <a href="%1$s" target="_new">API Key</a>', 'gofetch-wpjm' ), $info_url )
					. '<br/><br/>' . __( '<strong>Note:</strong> If you don\'t provide an API key, you\'re limited to 500 requests per hour.', 'gofetch-wpjm' ),
					'tip'   => __( 'Providing an API key will allow you to make up to 3600 requests per hour, from this provider.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Industries', 'gofetch-wpjm' ),
					'name'  => 'themuse_feed_default_industry',
					'type'  => 'checkbox',
					'extra'  => array(
						'style'            => 'width: 100%;',
						'multiple'         => 'multiple',
						'class'            => 'select2-gofj-multiple',
						'data-allow-clear' => 'true',
					),
					'choices' => self::industries(),
					'tip' => __( 'Choose the industries that best suit your job board.<br/><br/>Only companies relevant to the industries you select will be available during import.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['themuse']['defaults'] = array(
			'title' => __( 'Feed Defaults', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Limit', 'gofetch-wpjm' ),
					'name'  => 'themuse_feed_default_limit',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Maximum number of results returned per query. Make sure this number is below 100 to avoid performance issues.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['themuse']['jobs'] = array(
			'title' => __( 'Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Block Search Indexing', 'gofetch-wpjm' ),
					'name'  => 'themuse_block_search_indexing',
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
	 * Enqueues Neuvoo in the list of providers.
	 */
	public function providers( $providers ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'themuse.com/api' => array(
				'API' => array(
					'info'      => 'https://www.themuse.com/developers/api/v2',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
				),
				'website'     => 'https://www.themuse.com',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-themuse.png',
				'description' => 'Job Search, Companies Hiring Near Me, and Advice.',
				'feed'        => array(
					'base_url'          => $this->get_api_url(),
					'search_url'        => 'https://www.themuse.com/search',
					'split_params' => true,
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'limit'    => array( 'limit'  => esc_attr( $goft_wpjm_options->themuse_feed_default_limit ) ),
						'cat'      => array( 'category'  => esc_attr( implode( ',', (array) $goft_wpjm_options->themuse_feed_default_industry ) ) ),
						// Custom.
						'loc' => array( 'location' => '', 'placeholder' => 'remote, london, new york, etc' ),
						'company'  => array( 'company' => '' ),
						'level'    => array( 'level'  => '' ),
						'remote'    => array( 'remote'  => '1' ),
					),
					'pagination' => array(
						'params'  => array(
							'page'  => 'page',
							'limit' => 'limit',
						),
						'results' => 20,
					),
					'full_description' => true,
					'default' => false,
				),
				'special' => array(
					'scrape' => array(
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"CompanyLogoOutlined_logoPill__weBC5")]/img/@src',
						),
					),
				),
				'category' => 'API',
				'weight'   => 10,
			),
		);

		$this->provider = $new_providers[ $this->id ];

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
		<?php $field_name = 'loc'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Location', 'gofetch-wpjm' ); ?></strong>
				<span class="tip">
					<span class="dashicons-before dashicons-editor-help tip-icon bc-tip"
					data-tooltip="<?php echo esc_attr( __( 'Comma separated list of locations.<br/><br/>Accepts country names and cities. If you search by city, make sure you include the respective country, wrapped in double quotes (e.g: <strong>United States, "London, United Kingdom", "Milan, Italy"</strong>).', 'gofetch-wpjm' ) ); ?>"></span>
				</span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 550px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder='<?php echo __( 'e.g.: remote, United States, "London, United Kingdom", "Milan, Italy", etc', 'gofetch-wpjm' ); ?>'>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
			<br/><span><?php echo html( 'small', __( '<strong>Note:</strong> If search does not match your locations, it will return jobs from everywhere. This is the provider default behavior.', 'gofetch-wpjm' ) ); ?></span>
			<br/><span><?php echo html( 'small', sprintf( 'You can see a list of valid locations on this <a href="%s" target="_new">page</a>, by scrolling down and clicking on "Locations".', esc_url( $this->api_url ) ) ); ?></span>
		</p>

		<div class="clear"></div>
<?php
		$field = array(
			'title'   => __( 'Categories', 'gofetch-wpjm' ),
			'name'    => 'feed-cat',
			'type'    => 'select',
			'choices' => self::categories(),
			'class'   => 'regular-text',
			'extra'   => array(
				'data-qarg' => 'feed-param-cat',
				'multiple'  => 'multiple',
				'style'     => "width: 550px;",
			),
			'default' => '',
		);
?>
		<?php $field_name = 'cat'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Categories', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Choose the companies that you want to pull jobs from.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label>
			<span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<?php echo scbForms::input( $field, array() ) ?>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

<?php
		$companies = $this->companies();

		$field = array(
			'title'   => __( 'Companies', 'gofetch-wpjm' ),
			'name'    => 'feed-company',
			'type'    => 'select',
			'choices' => $companies,
			'class'   => 'regular-text',
			'extra'   => array(
				'data-qarg' => 'feed-param-company',
				'multiple'  => 'multiple',
				'style'     => "width: 550px;",
			),
			'default' => '',
		);
?>
		<?php $field_name = 'company'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Companies', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Choose the companies that you want to pull jobs from.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label>
			<span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<?php echo scbForms::input( $field, array() ) ?>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
			<span><?php _e( '<strong>Note:</strong> The companies above, are directly related with the industries that you\'ve selected on the settings page.', 'gofetch-wpjm' ); ?></span>
		</p>

		<div class="clear"></div>
<?php
	}

	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {
		$api_data = $this->get_api_data( $url );

		$parsed_url = parse_url( $url );
		parse_str( $parsed_url['query'], $query_parts );

		if ( is_wp_error( $api_data ) || empty( $api_data ) ) {
			if ( is_wp_error( $api_data ) ) {
				$error_message = $api_data->get_error_message();
				if ( strpos( $error_message, 'too high' ) >= 0  ) {
					return new WP_Error( 'api_pagination', __( 'Limit is too high (max limit is 80). Please adjust and try again.', 'gofetch-wpjm' ) );
				} else {
					return new WP_Error( 'api_error', $error_message );
				}
			} else {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Make sure you\'ve specified a \'Keyword\' and/or \'Location\' ', 'gofetch-wpjm' ) );
			}
			return $api_data;
		}
		return $api_data['results'];
	}

	/**
	 * Fetch items from the API feed.
	 */
	public function fetch_feed_items( $items, $url, $provider ) {
		global $goft_wpjm_options;

		$new_items = $sample_item = array();

		$defaults = array(
			'contents'         => '',
			'name'             => '',
			'type'             => '',
			'publication_date' => '',
			'short_name'       => '',
			'model_type'       => '',
			'id'               => '',
			'locations'        => '',
			'categories'       => '',
			'levels'           => '',
			'tags'             => '',
			'refs'             => '',
			'company'          => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['name'] );
			$new_item['date']        = GoFetch_Importer::get_valid_date( $job['publication_date'], 'api' );

			$location = array_shift( $job['locations'] );
			$new_item['location']    = sanitize_text_field( $location['name'] );

			if ( ! empty( $job['company']['name'] ) ) {
				$new_item['company'] = sanitize_text_field( $job['company']['name'] );
			}

			if ( ! empty( $job['categories'] ) ) {
				$new_item['category'] = is_array( $job['categories'] ) ? implode( ',', array_map( 'sanitize_text_field', wp_list_pluck( $job['categories'], 'name' ) ) ): sanitize_text_field( $job['categories'] );
			}

			if ( ! empty( $job['levels'] ) ) {
				$new_item['levels'] = is_array( $job['levels'] ) ? implode( ',', array_map( 'sanitize_text_field', wp_list_pluck( $job['levels'], 'name' ) ) ): sanitize_text_field( $job['levels'] );
			}

			$new_item['description'] = GoFetch_Importer::format_description( $job['contents'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( $job['refs']['landing_page'] ) );

			// Find the item with the most attributes to use as sample.
			if ( count( array_keys( $new_item ) ) > count( array_keys( $sample_item ) ) ) {
				$sample_item = $new_item;
				$sample_item['description'] = GoFetch_Importer::shortened_description( $job['contents'] );
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

		$provider['name'] = 'Job Search | The Muse';

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
	 * Get the list of companies.
	 */
	public static function get_companies( $page = 1, $companies = array() ) {
		global $goft_wpjm_options;

		$industries = $goft_wpjm_options->themuse_feed_default_industry;

		$transient_key = sprintf( 'goft_themuse_companies_%s', md5( serialize( $industries ) ) );

		if ( ! ( $cached_companies = get_transient( $transient_key ) ) ) {

			$api_url = sprintf( 'https://www.themuse.com/api/public/companies?page=%s', $page );

			if ( $industries ) {

				foreach ( (array) $industries as $industry ) {
					$query_args .= sprintf( '&industry=%s', $industry );

				}
				$api_url .= $query_args;
			}

			$api_args = array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			);

			$response = wp_remote_get( $api_url, $api_args );

			if ( ! is_wp_error( $response ) ) {
				$results = wp_remote_retrieve_body(  $response );

				$json = json_decode( $results, true );

				$companies = array_merge( $companies, wp_list_pluck( $json['results'], 'name' ) );

				$page_count = (int) $json['page_count'];
				$curr_page = (int) $json['page'];

				if ( $curr_page < $page_count && $curr_page <= self::$max_company_pages ) {
					return self::get_companies( ++$curr_page, $companies );
				}

				set_transient( $transient_key, $companies, DAY_IN_SECONDS );
			}
		} else {
			$companies = $cached_companies;
		}
		return $companies;
	}

	/**
	 * Industries available for this provider.
	 */
	public static function industries() {

		$categories = array(
			'Accounting'                            => 'Accounting',
			'Advertising and Agencies'              => 'Advertising and Agencies',
			'Architecture'                          => 'Architecture',
			'Arts &amp; Music'                      => 'Arts &amp; Music',
			'Biotechnology'                         => 'Biotechnology',
			'Blockchain'                            => 'Blockchain',
			'Client Services'                       => 'Client Services',
			'Consulting'                            => 'Consulting',
			'Consumer Goods &amp; Services'         => 'Consumer Goods &amp; Services',
			'Data Science'                          => 'Data Science',
			'Education'                             => 'Education',
			'Engineering'                           => 'Engineering',
			'Entertainment &amp; Gaming'            => 'Entertainment &amp; Gaming',
			'Fashion &amp; Beauty'                  => 'Fashion &amp; Beauty',
			'Financial Services'                    => 'Financial Services',
			'FinTech'                               => 'FinTech',
			'Food &amp; Beverage'                   => 'Food &amp; Beverage',
			'Government'                            => 'Government',
			'Healthcare'                            => 'Healthcare',
			'Healthtech'                            => 'Healthtech',
			'Information Technology'                => 'Information Technology',
			'Insurance'                             => 'Insurance',
			'Law'                                   => 'Law',
			'Manufacturing'                         => 'Manufacturing',
			'Marketing'                             => 'Marketing',
			'Media'                                 => 'Media',
			'Mortgage'                              => 'Mortgage',
			'Non-Profit'                            => 'Non-Profit',
			'Pharmaceutical'                        => 'Pharmaceutical',
			'Public Relations &amp; Communications' => 'Public Relations &amp; Communications',
			'Real Estate &amp; Construction'        => 'Real Estate &amp; Construction',
			'Retail'                                => 'Retail',
			'Social Good'                           => 'Social Good',
			'Social Media'                          => 'Social Media',
			'Software'                              => 'Software',
			'Technology'                            => 'Technology',
			'Telecom'                               => 'Telecom',
			'Travel and Hospitality'                => 'Travel and Hospitality',
			'Veterinary'                            => 'Veterinary',
		);
		return $categories;
	}


	/**
	 * Categories available for this provider.
	 */
	public static function categories() {

		$categories = array(
			'Accounting'                             => 'Accounting',
			'Accounting and Finance'                 => 'Accounting and Finance',
			'Account Management'                     => 'Account Management',
			'Account Management/Customer Success'    => 'Account Management/Customer Success',
			'Administration and Office'              => 'Administration and Office',
			'Advertising and Marketing'              => 'Advertising and Marketing',
			'Animal Care'                            => 'Animal Care',
			'Arts'                                   => 'Arts',
			'Business Operations'                    => 'Business Operations',
			'Cleaning and Facilities'                => 'Cleaning and Facilities',
			'Computer and IT'                        => 'Computer and IT',
			'Construction'                           => 'Construction',
			'Corporate'                              => 'Corporate',
			'Customer Service'                       => 'Customer Service',
			'Data and Analytics'                     => 'Data and Analytics',
			'Data Science'                           => 'Data Science',
			'Design'                                 => 'Design',
			'Design and UX'                          => 'Design and UX',
			'Editor'                                 => 'Editor',
			'Education'                              => 'Education',
			'Energy Generation and Mining'           => 'Energy Generation and Mining',
			'Entertainment and Travel Services'      => 'Entertainment and Travel Services',
			'Farming and Outdoors'                   => 'Farming and Outdoors',
			'Food and Hospitality Services'          => 'Food and Hospitality Services',
			'Healthcare'                             => 'Healthcare',
			'HR'                                     => 'HR',
			'Human Resources and Recruitment'        => 'Human Resources and Recruitment',
			'Installation, Maintenance, and Repairs' => 'Installation, Maintenance, and Repairs',
			'IT'                                     => 'IT',
			'Law'                                    => 'Law',
			'Legal Services'                         => 'Legal Services',
			'Management'                             => 'Management',
			'Manufacturing and Warehouse'            => 'Manufacturing and Warehouse',
			'Marketing'                              => 'Marketing',
			'Mechanic'                               => 'Mechanic',
			'Media, PR, and Communications'          => 'Media, PR, and Communications',
			'Mental Health'                          => 'Mental Health',
			'Nurses'                                 => 'Nurses',
			'Office Administration'                  => 'Office Administration',
			'Personal Care and Services'             => 'Personal Care and Services',
			'Physical Assistant'                     => 'Physical Assistant',
			'Product'                                => 'Product',
			'Product Management'                     => 'Product Management',
			'Project Management'                     => 'Project Management',
			'Protective Services'                    => 'Protective Services',
			'Public Relations'                       => 'Public Relations',
			'Real Estate'                            => 'Real Estate',
			'Recruiting'                             => 'Recruiting',
			'Retail'                                 => 'Retail',
			'Sales'                                  => 'Sales',
			'Science and Engineering'                => 'Science and Engineering',
			'Social Services'                        => 'Social Services',
			'Software Engineer'                      => 'Software Engineer',
			'Software Engineering'                   => 'Software Engineering',
			'Sports, Fitness, and Recreation'        => 'Sports, Fitness, and Recreation',
			'Transportation and Logistics'           => 'Transportation and Logistics',
			'Unknown'                                => 'Unknown',
			'UX'                                     => 'UX',
			'Videography'                            => 'Videography',
			'Writer'                                 => 'Writer',
			'Writing and Editing'                    => 'Writing and Editing',
		);
		return $categories;
	}

	/**
	 * Companies available from this provider.
	 */
	public static function companies() {
		$raw_companies = self::get_companies();

		$companies = array();

		foreach ( $raw_companies as $raw_company ) {
			$companies[ $raw_company ] = $raw_company;
		}
		return $companies;
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
new GoFetch_TheMuse_API_Feed_Provider();
