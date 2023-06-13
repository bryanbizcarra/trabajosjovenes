<?php
/**
 * Importer classes for providers that use an API to provide jobs.
 *
 * Docs: https://developer.adzuna.com/activedocs
 *
 * @package GoFetch/Admin/Premium/Professional/API Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The class for the adzuna Feed API.
 */
class GoFetch_adzuna_API_Feed_Provider extends GoFetch_API_Feed_Provider {

	/**
	 * @var The single instance of the class.
	 */
	protected static $_instance = null;

	/**
	 * The application URL.
	 */
	protected static $application_link = 'https://forms.gle/aKXnifeHZMknnoCv9';

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

		$country = esc_attr( $goft_wpjm_options->adzuna_feed_default_country );
		$page = esc_attr( $goft_wpjm_options->adzuna_feed_page );

		$page = max( $page, 1 );

		$this->id      = 'api.adzuna.com';
		$this->api_url = sprintf( 'https://api.adzuna.com/v1/api/jobs/%1$s/search/%2$d?app_id=%3$s&app_key=%4$s', esc_attr( $country ), esc_attr( $page ), esc_attr( $goft_wpjm_options->adzuna_feed_app_id ), esc_attr( $goft_wpjm_options->adzuna_feed_app_key ) );

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-providers', array( $this, 'tabs' ), 15 );
		add_filter( 'goft_wpjm_providers', array( $this, 'providers' ), 15 );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );
		add_filter( 'goft_wpjm_sample_item', array( $this, 'sample_item' ), 10, 2 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );

		// Frontend.
		add_action( 'goft_wpjm_single_goft_job', array( $this, 'single_job_page_hooks' ) );

		add_filter( 'goft_no_robots', array( $this, 'maybe_no_robots' ), 10, 2 );

		add_filter( 'goft_wpjm_use_custom_feed_pagination', array( $this, 'use_custom_pagination' ), 10, 2 );
		add_filter( 'goft_wpjm_custom_feed_pagination', array( $this, 'custom_pagination' ), 10, 4 );
	}

	/**
	 * Init the adzuna tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'adzuna', sprintf( '<span class="gofj-top-partner-tab"><span class="dashicons dashicons-star-empty"></span> %s</span>', __( 'Adzuna', 'gofetch-wpjm' ) ) );
		$this->tab_adzuna();
	}

	/**
	 * adzuna settings tab.
	 */
	protected function tab_adzuna() {
		global $goft_wpjm_options;

		$docs = 'https://developer.adzuna.com/activedocs#!/adzuna/histogram_0';

		$this->all_tabs->tab_sections['adzuna']['logo'] = array(
			'title' => '',
			'fields' => array(
				array(
					'title'  => '',
					'name'   => '_blank',
					'type'   => 'custom',
					'render' => function() {
						echo '<h2 class="gofj-top-partner"> <span class="dashicons dashicons-star-empty"></span> Top Partner <span class="dashicons-before dashicons-editor-help tip-icon bc-tip" title="Click to read additional info..." data-tooltip="A \'Top Partner\' means better integration and support."></span> <sep>|</sep> <span class="dashicons dashicons-money-alt"></span> Revenue Sharing <span class="dashicons-before dashicons-editor-help tip-icon bc-tip" title="Click to read additional info..." data-tooltip="Earn money by listing jobs from this partner. Please read the terms and conditions when you apply for a publisher account."></span></h2>';
						echo '<img class="api-providers-logo" src="' . esc_url( $this->get_config('logo') ) . '">';
					},
				),
			),
		);

		$this->all_tabs->tab_sections['adzuna']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title'   => __( 'Country', 'gofetch-wpjm' ),
					'name'    => 'adzuna_feed_default_country',
					'type'    => 'select',
					'choices' => $this->locales(),
					'tip'     => __( 'Search within country specified.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Application ID *', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_app_id',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Register for a free <a href="%1$s" target="_new">Adzuna Publisher Account</a> to get your Application ID.', 'gofetch-wpjm' ), self::$application_link ),
					'tip'   => __( 'You need an application ID in order to pull jobs from this provider.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Application Key *', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_app_key',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Register for a free <a href="%1$s" target="_new">Adzuna Publisher Account</a> to get your Application Key.', 'gofetch-wpjm' ), self::$application_link ),
					'tip'   => __( 'You need an application key in order to pull jobs from this provider.', 'gofetch-wpjm' ),
				),
			),
		);

		if ( ! $goft_wpjm_options->adzuna_feed_app_id || ! $goft_wpjm_options->adzuna_feed_app_key ) {
			return;
		}

		$categories = get_transient( 'goft_adzuna_categories' );

		$categories_desc = '';

		if ( ! $categories ) {
			$categories_desc = __( 'Try <a href="">refreshing this page</a> if you don\'y see the list of categories.', 'gofetch-wpjm' );
		}

		$this->all_tabs->tab_sections['adzuna']['defaults'] = array(
			'title' => __( 'Feed Defaults', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Where', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_default_where',
					'type'  => 'text',
					'tip' => __( 'The geographic centre of the search. Place names, postal codes, etc. may be used.', 'gofetch-wpjm' ),
				),
				/*
				array(
					'title' => __( 'Location 1', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_default_location0',
					'type'  => 'text',
					'tip' => __( 'For example, location 1 = UK and location 2 = South East England and location 3 = Surrey, will perform a search over the county of Surrey.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Location 2', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_default_location1',
					'type'  => 'text',
					'tip' => __( 'For example, location 1 = UK and location 2 = South East England and location 3 = Surrey, will perform a search over the county of Surrey.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Location 3', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_default_location2',
					'type'  => 'text',
					'tip' => __( 'For example, location 1 = UK and location 2 = South East England and location 3 = Surrey, will perform a search over the county of Surrey.', 'gofetch-wpjm' ),
				),
				*/
				array(
					'title' => __( 'Company', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_default_company',
					'type'  => 'text',
					'tip' => __( 'Limit results to a specific company name. You might get empty results if you mispelled the company name. Try clearing this setting or adjusting the wording, if you get empty results.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Distance', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_default_distance',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'The distance in kilometres from the centre of the place described in \'Where\'. Defaults to 5 km.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Max Days Old', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_default_max_days_old',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'The age of the oldest job in days, that will be returned.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Min. Salary', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_default_salary_min',
					'type'  => 'text',
					'extra' => array(
						'style' => 'width: 80px',
					),
					'desc' => __( '(Annual Salary)', 'gofetch-wpjm' ),
					'tip' => __( 'Only pull jobs that pay more than the salary you specify here (only numeric values without currency). Leave empty for any.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Max. Salary', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_default_salary_max',
					'type'  => 'text',
					'extra' => array(
						'style' => 'width: 80px',
					),
					'desc' => __( '(Annual Salary)', 'gofetch-wpjm' ),
					'tip' => __( 'Only pull jobs that pay up to the salary you specify here (only numeric values without currency). Leave empty for any.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Include Unknown Salaries', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_default_salary_include_unknown',
					'type'  => 'checkbox',
					'desc' => __( 'Yes', 'gofetch-wpjm' ),
					'tip' => __( 'Include jobs with unknown salaries in results.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Category', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_default_category',
					'type'  => 'select',
					'choices' => self::categories(),
					'desc' => $categories_desc,
					'tip' => __( 'Choose the job category that best suits your job board.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Sorting', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_default_sort_by',
					'type'  => 'select',
					'choices' => array(
						'date'      => 'Date',
						'salary'    => 'Salary',
						'relevance' => 'Relevance',
					),
					'tip' => __( 'The ordering of the search results.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Results Per Page', 'gofetch-wpjm' ),
					'name'  => 'adzuna_feed_default_results_per_page',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'The number of jobs to include on the results. Max recommended is \'100\'.', 'gofetch-wpjm' ),
				),

			),
		);

		$this->all_tabs->tab_sections['adzuna']['jobs'] = array(
			'title' => __( 'Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Block Search Indexing', 'gofetch-wpjm' ),
					'name'  => 'adzuna_block_search_indexing',
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
	 * Enqueues adzuna in the list of providers.
	 */
	public function providers( $providers ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'api.adzuna.com' => array(
				'API' => array(
					'info'      => 'https://developer.adzuna.com/overview',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'Application ID'  => 'adzuna_feed_app_id',
						'Application Key' => 'adzuna_feed_app_key',
					),
				),
				'website'     => 'https://www.adzuna.co.uk/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-adzuna.png',
				'description' => 'Jobs in London, the UK & Beyond.',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://api.adzuna.com/v1/api',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'  => array( 'what' => '' ),
						//'location' => array( 'location0' => esc_attr( $goft_wpjm_options->adzuna_feed_default_location0 ) ),
						'limit'    => array( 'results_per_page'  => esc_attr( $goft_wpjm_options->adzuna_feed_default_results_per_page ) ),
						'radius'   => array( 'distance' => esc_attr( $goft_wpjm_options->adzuna_feed_default_distance ) ),
						// Custom.
						'country'      => array( 'country' => esc_attr( $goft_wpjm_options->adzuna_feed_default_country ) ),
						'sector'       => array( 'category' => esc_attr( $goft_wpjm_options->adzuna_feed_default_category ) ),
						'where'        => array( 'where' => esc_attr( $goft_wpjm_options->adzuna_feed_default_where ) ),
						//'location1'    => array( 'location1' => esc_attr( $goft_wpjm_options->adzuna_feed_default_location1 ) ),
						//'location2'    => array( 'location2' => esc_attr( $goft_wpjm_options->adzuna_feed_default_location2 ) ),
						'company'      => array( 'company' => esc_attr( $goft_wpjm_options->adzuna_feed_default_company ) ),
						'max_days_old' => array( 'max_days_old' => esc_attr( $goft_wpjm_options->adzuna_feed_default_max_days_old ) ),
						'salary_min'   => array( 'salary_min' => esc_attr( $goft_wpjm_options->adzuna_feed_default_salary_min ) ),
						'salary_max'   => array( 'salary_max' => esc_attr( $goft_wpjm_options->adzuna_feed_default_salary_max ) ),
						'sort_by'      => array( 'sort_by' => esc_attr( $goft_wpjm_options->adzuna_feed_default_sort_by ) ),
					),
					'pagination' => array(
						'params'  => array(
							'page'      => 'page',
							'limit'     => 'limit',
						),
						'type'    => 'page',
						'results' => 20,
					),
					'full_description' => true,
					'default' => false,
				),
				'multi_region_match' => 'adzuna',
				'region_domains' => $this->locales(),
				'region_default' => $goft_wpjm_options->adzuna_feed_default_country,
				'partner' => true,
				'partner_msg' => sprintf( __( '<em>Adzuna</em> is a Partner provider, and will share traffic revenue. If are not a publisher yet, you can <a href="%s" target="_blank">apply here</a>.' ), self::$application_link ),
				'category' => array( ' ' . __( 'Partners', 'gofetch-wpjm' ), ' ' . __( 'Partners with Revenue Share', 'gofetch-wpjm' ), 'API' ),
				'weight'   => 10,
			),
		);
		return array_merge( $providers, $new_providers );
	}

	/**
	 * Outputs specific adzuna feed parameter fields.
	 */
	public function feed_builder_fields( $provider ) {
		global $goft_wpjm_options;

		if ( ! $this->condition( $provider ) ) {
			return;
		}

		$categories = self::categories();
?>
		<?php $field_name = 'sector'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Job Category', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Choose the job category that best suits your job board.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: 400px;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>">
				<?php foreach ( $categories as $key => $cat ) : ?>
					<option value='<?php echo esc_attr( $key ); ?>' <?php selected( $goft_wpjm_options->adzuna_feed_default_category ); ?>><?php echo wp_kses_post( $cat ); ?></option>
				<?php endforeach; ?>

			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'company'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Company Name', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Limit results to a specific company name. You might get empty results if you mispelled the company name. Try clearing this setting or adjusting the wording, if you get empty results.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: google', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'where'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Where', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The geographic centre of the search. Place names, postal codes, etc. may be used.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: london', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

		<?php $field_name = 'location'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Location', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'For example, location = UK and location 2 = South East England and location 3 = Surrey, will perform a search over the county of Surrey.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: london', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'location1'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Location 2', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'For example, location 1 = UK and location 2 = South East England and location 3 = Surrey, will perform a search over the county of Surrey.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: london', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'location2'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Location 3', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'For example, location = UK and alt location = South East England and alt location 2 = Surrey, will perform a search over the county of Surrey.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: london', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

		<?php $field_name = 'max_days_old'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Max Days Old', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The age of the oldest job in days, that will be returned.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 60px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: london', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'salary_min'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Min. Salary', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Limit results with annual salary greater than this number.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 100px" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 50000', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'salary_max'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Max. Salary', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Limit results with annual salary up to this number.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 100px" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 100000', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

		<?php $field_name = 'sort_by'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Sort', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The order in which to return results.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>">
				<option value="salary">Salary</option>
				<option value="relevance">Relevance</option>
				<option value="date">Date</option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

<?php
	}

	/**
	 * Force custom pagination.
	 */
	public function use_custom_pagination( $use_custom, $provider_id ) {
		return ( $this->condition( $provider_id ) );
	}

	/**
	 * Use pagination based on path instead of parameter.
	 */
	public function custom_pagination( $url, $provider_id, $page ) {

		if ( ! $this->condition( $provider_id ) ) {
			return $url;
		}

		$parts = parse_url( $url );
		$path_parts = explode( '/', $parts['path'] );
		$page = array_pop( $path_parts );

		$url = str_replace( sprintf( '/%s', $page ), sprintf( '/%s', $page ), $url );

		return $url;
	}

	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {
		$parts = parse_url( $url );

		$params = array();

		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $params );
			if ( ! empty( $params['results_per_page'] ) ) {
				// @todo: remove this limitation if users can pull more than 20 jobs at a time (add option on settings page)
				if ( (int) $params['results_per_page'] > 20 ) {
					$url = str_replace( sprintf( 'results_per_page=%s', $params['results_per_page'] ), '', $url );
				}
			}
			if ( ! empty( $params['page'] ) ) {
				$page = intval( $params['page'] );
			} else {
				$page = 1;
			}
			$url = str_replace( sprintf( 'page=%s', $page ), '', $url );
		}

		$api_args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		$api_data = $this->get_api_data( $url, $_xml_data = false, $api_args );

		$paginated_results = ( ! empty( $this->provider['feed']['pagination'] ) && in_array( $this->provider['feed']['pagination']['params']['page'], array_keys( $params ) ) );

		if ( ! $paginated_results && ( is_wp_error( $api_data ) || empty( $api_data['results'] ) ) ) {

			if ( ! is_wp_error( $api_data ) ) {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Make sure you\'ve specified \'Keyword\' and that your API key is valid.', 'gofetch-wpjm' ) );
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
			'id'           => '',
			'salary_min'   => '',
			'salary_max'   => '',
			'category'     => '',
			'adref'        => '',
			'location'     => '',
			'latitude'     => '',
			'longitude'    => '',
			'created'      => '',
			'company'      => '',
			'title'        => '',
			'description'  => '',
			'redirect_url' => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['title'] );
			$new_item['date']        = GoFetch_Importer::get_valid_date( $job['created'], 'api' );
			$new_item['location']    = sanitize_text_field( $job['location']['display_name'] );

			// Some locations might retrieve 'null' so, remove them.
			$new_item['location'] = str_replace( array( 'null,', 'null' ), '', $new_item['location'] );

			$new_item['latitude']    = sanitize_text_field( $job['latitude'] );
			$new_item['longitude']   = sanitize_text_field( $job['longitude'] );
			$new_item['company']     = sanitize_text_field( $job['company']['display_name'] );

			$new_item['category']    = sanitize_text_field( $job['category']['label'] );

			$new_item['salary_min']  = sanitize_text_field( $job['salary_min'] );
			$new_item['salary_max']  = sanitize_text_field( $job['salary_max'] );

			$new_item['description'] = GoFetch_Importer::format_description( $job['description'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( $job['redirect_url'] ) );

			$new_item['adref']       = sanitize_text_field( $job['adref'] );
			$new_item['id']          = sanitize_text_field( $job['id'] );

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

		$provider['name'] = 'Job Search | adzuna';

		return array(
			'provider'    => $provider,
			'items'       => $new_items,
			'sample_item' => $sample_item,
		);
	}

	/**
	 * Set specific meta from adzuna.
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

		$item['id']    = null; unset( $item['id'] );
		$item['adref'] = null; unset( $item['adref'] );


		return $item;
	}


	/**
	 * Retrieves the list of country codes for this provider.
	 */
	protected function locales() {
		return array(
			'au' => 'Australia',
			'at' => 'Austria',
			'br' => 'Brazil',
			'ca' => 'Canada',
			'de' => 'Germany',
			'fr' => 'France',
			'gb' => 'United Kingdom',
			'in' => 'India',
			'it' => 'Italy',
			'nl' => 'Netherlands',
			'nz' => 'New Zealand',
			'pl' => 'Poland',
			'ru' => 'Russia',
			'sg' => 'Singapore',
			'us' => 'United States',
			'za' => 'South Africa',
		);
	}

	/**
	 * Get a list of categories.
	 */
	public static function get_categories() {
		global $goft_wpjm_options;

		if ( ! $goft_wpjm_options->adzuna_feed_app_id || ! $goft_wpjm_options->adzuna_feed_app_key ) {
			return array();
		}

		if ( ! ( $categories = get_transient( 'goft_adzuna_categories' ) ) ) {
			$country = esc_attr( $goft_wpjm_options->adzuna_feed_default_country );

			$api_url = sprintf( 'https://api.adzuna.com/v1/api/jobs/%1$s/categories?app_id=%2$s&app_key=%3$s', esc_attr( $country ), esc_attr( $goft_wpjm_options->adzuna_feed_app_id ), esc_attr( $goft_wpjm_options->adzuna_feed_app_key ) );

			$api_args = array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			);

			$response = wp_remote_get( $api_url, $api_args );

			$categories = array();

			if ( ! is_wp_error( $response ) ) {
				$results = wp_remote_retrieve_body(  $response );

				$json = json_decode( $results );

				$categories = $json->results;

				set_transient( 'goft_adzuna_categories', $categories, YEAR_IN_SECONDS );
			}
		}
		return $categories;
	}

	/**
	 * Job categories for this provider.
	 */
	public static function categories() {
		$raw_categories = self::get_categories();

		$categories = array();

		foreach ( $raw_categories as $raw_category ) {
			$categories[ $raw_category->tag ] = $raw_category->label;
		}
		return $categories;
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

		if ( $goft_wpjm_options->adzuna_block_search_indexing ) {
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

		$args['publisher'] = $goft_wpjm_options->adzuna_publisher_id;
		$args['ip']        = urlencode( BC_Framework_Utils::get_user_ip() );
		$args['useragent'] = urlencode( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) );
		$args['v']         = 2;

		return $args;
	}

}
new GoFetch_adzuna_API_Feed_Provider();
