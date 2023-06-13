<?php
/**
 * Importer classes for providers that use an API to provide jobs.
 *
 * Docs: https://neuvoo.ca/services/api-new/documentation.php
 *
 * @package GoFetch/Admin/Premium/Professional/API Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The class for the Neuvoo Feed API.
 */
class GoFetch_Neuvoo_API_Feed_Provider extends GoFetch_API_Feed_Provider {

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

		$this->id = 'api.neuvoo.com';

		$this->api_url = sprintf( 'https://talent.com/services/api-new/search?publisher=%1$s&v=2', esc_attr( $goft_wpjm_options->neuvoo_publisher_id ) );

		// @todo: add support for placeholders on URL
		//$this->id      = 'talent.com/api';
		//$this->api_url = sprintf( 'https://%%%1$s%%talent.com/services/api-new/search?publisher=%2$s&v=2', esc_url( $goft_wpjm_options->neuvoo_feed_default_cc ), esc_attr( $goft_wpjm_options->neuvoo_publisher_id ) );

		$this->init_hooks();
	}

	/**
	 * The method for retrieving the API URL.
	 *
	protected function get_api_url() {
		return 'talent.com';
	}*/

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-providers', array( $this, 'tabs' ), 95 );
		add_filter( 'goft_wpjm_providers', array( $this, 'providers' ), 15 );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );
		add_filter( 'goft_wpjm_sample_item', array( $this, 'sample_item' ), 10, 2 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );

		add_filter( 'goft_wpjm_provider_in_url', array( $this, 'alt_provider_id' ), 10, 2 );

		// Frontend.
		add_action( 'goft_wpjm_single_goft_job', array( $this, 'single_job_page_hooks' ) );

		add_action( 'goft_no_robots', array( $this, 'maybe_no_robots' ), 10, 2 );
	}

	/**
	 * Init the Neuvoo tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'neuvoo', __( 'Talent.com / Neuvoo', 'gofetch-wpjm' ) );
		$this->tab_neuvoo();
	}

	/**
	 * Neuvoo settings tab.
	 */
	protected function tab_neuvoo() {

		$info_url = 'https://neuvoo.com/services/api-new/documentation.php';

		$this->all_tabs->tab_sections['neuvoo']['logo'] = array(
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

		$this->all_tabs->tab_sections['neuvoo']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title'   => __( 'Country', 'gofetch-wpjm' ),
					'name'    => 'neuvoo_feed_default_cc',
					'type'    => 'select',
					'choices' => $this->locales(),
					'tip'     => sprintf( __( 'Search within country specified. Leave empty to let the location algorithm try to match the best possible place in the world.', 'gofetch-wpjm' ), esc_url( $info_url ) ),
				),
				array(
					'title' => __( 'Publisher ID *', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_publisher_id',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Sign up for a free <a href="%1$s" target="_new">Talent.com/Neuvoo Publisher Account</a>', 'gofetch-wpjm' ), 'https://www.talent.com/publishers' ),
					'tip'   => __( 'You need a publisher ID in order to pull jobs from Neuvoo.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['neuvoo']['defaults'] = array(
			'title' => __( 'Feed Defaults', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Language', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feed_default_lang',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Biases the default snippets and location string.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Source Type', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feed_default_st',
					'type'  => 'select',
					'choices' => array(
						'all'      => 'All',
						'company'  => 'Company',
						'staffing' => 'Staffing',
						'jobboard' => 'Job Board',
					),
					'tip' => __( 'Type of the source who owns the jobs.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Content Type', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feed_default_ct',
					'type'  => 'select',
					'choices' => array(
						'all'       => 'All',
						'organic'   => 'Organic',
						'sponsored' => 'Sponsored',
					),
					'tip' => __( 'Type of content you want to be displayed in the results (sponsored, organic or all).', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Search Fields', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feed_searchon',
					'type'  => 'select',
					'choices' => array(
						'title'   => 'Job Title',
						'empname' => 'Employer Name',
					),
					'tip' => __( 'The field used to match the search keywords.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Minimum Bid', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feed_default_min_cpcfloor',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Minimum bid per job allowed in the results. Works only with sponsored jobs.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Radius', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feed_default_radius',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Distance from search location ("as the crow flies")', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Sorting', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feed_default_sort',
					'type'  => 'select',
					'choices' => array(
						'relevance' => 'Relevance',
						'date'      => 'Date',
					),
					'tip' => __( 'Sort by <em>relevance</em> or <em>date</em>.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Limit', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feed_default_limit',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Maximum number of results returned per query. Limit is 15.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Channel Name 1', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feed_default_chnl',
					'type'  => 'text',
					'tip' => __( 'Group API requests to a specific channel name.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Channel Name 2', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feed_default_chn2',
					'type'  => 'text',
					'tip' => __( 'Group API requests to a specific channel name.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Channel Name 3', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feed_default_chn3',
					'type'  => 'text',
					'tip' => __( 'Group API requests to a specific channel name.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Channel Subid', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feed_default_subid',
					'type'  => 'text',
					'tip' => __( 'Group API requests to a specific ID. Can only be integers. Max length: 15 characters.', 'gofetch-wpjm' ),
				),
				/*
				array(
					'title' => __( 'Redirect', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feed_default_rdr',
					'type'  => 'text',
					'tip' => __( 'URL to redirect the user if there is a problem charging the click. Please enter a full website URL e.g. https://neuvoo.ca.', 'gofetch-wpjm' ),
				),*/
			),
		);


		$this->all_tabs->tab_sections['neuvoo']['sponsored'] = array(
			'title' => __( 'Sponsored Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Feature Sponsored Jobs', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_feature_sponsored',
					'type'  => 'checkbox',
					'desc'  => __( 'Yes', 'gofetch-wpjm' ),
					'tip' => sprintf( __( 'Check this option to automatically feature Sponsored jobs. These jobs can be filtered using a special meta key named <code>%s</code>.', 'gofetch-wpjm' ), '_goft_wpjm_neuvoo_sponsored' ),
				),
			),
		);

		$this->all_tabs->tab_sections['neuvoo']['jobs'] = array(
			'title' => __( 'Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Block Search Indexing', 'gofetch-wpjm' ),
					'name'  => 'neuvoo_block_search_indexing',
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
			'api.neuvoo.com' => array(
				'API' => array(
					'info'      => '	',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'Publisher ID' => 'neuvoo_publisher_id',
					),
				),
				'website'     => 'https://neuvoo.com',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-talent.svg',
				'description' => 'Now Talent.com. Your job search starts here.',
				'feed'        => array(
					'url_match' => array( 'talent.com/services' ),
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://neuvoo.com/services/api-new/search',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'  => array( 'k'      => '' ),
						'location' => array( 'l'      => '' ),
						'limit'    => array( 'limit'  => esc_attr( $goft_wpjm_options->neuvoo_feed_default_limit ) ),
						'radius'   => array( 'radius' => esc_attr( $goft_wpjm_options->neuvoo_feed_default_radius ) ),
						// Custom.
						'sourcetype'  => array( 'sourcetype'  => esc_attr( $goft_wpjm_options->neuvoo_feed_default_st ) ),
						'contenttype' => array( 'contenttype' => esc_attr( $goft_wpjm_options->neuvoo_feed_default_ct ) ),
						'searchon' => array( 'searchon' => esc_attr( $goft_wpjm_options->neuvoo_feed_searchon ) ),
						'cpcfloor' => array( 'cpcfloor' => esc_attr( $goft_wpjm_options->neuvoo_feed_default_min_cpcfloor ) ),
						'country'  => array( 'country'  => esc_attr( $goft_wpjm_options->neuvoo_feed_default_cc ) ),
						'language'    => array( 'language' => esc_attr( $goft_wpjm_options->neuvoo_feed_default_lang ) ),
						'sort'    => array( 'sort'  => esc_attr( $goft_wpjm_options->neuvoo_feed_default_sort ) ),
						'chnl1'    => array( 'chnl1' => esc_attr( $goft_wpjm_options->neuvoo_feed_default_chnl ) ),
						'chnl2'    => array( 'chnl2' => esc_attr( $goft_wpjm_options->neuvoo_feed_default_chn2 ) ),
						'chnl3'    => array( 'chnl3' => esc_attr( $goft_wpjm_options->neuvoo_feed_default_chn3 ) ),
						'subid'   => array( 'subid' => esc_attr( $goft_wpjm_options->neuvoo_feed_default_subid ) ),
						'rdr'    => array( 'rdr'  => esc_attr( $goft_wpjm_options->neuvoo_feed_default_rdr ) ),
					),
					'pagination' => array(
						'params'  => array(
							'page'  => 'start',
							'limit' => 'limit',
						),
						'type'    => 'offset',
						'results' => 25,
					),
					'scraping' => false,
					'notes' => 'Uses JS scrape blocker',
					'default' => false,
				),
				'category' => 'API',
				'weight'   => 10,
			),
		);
		return array_merge( $providers, $new_providers );
	}

	/**
	 * Outputs specific Neuvoo feed parameter fields.
	 */
	public function feed_builder_fields( $provider ) {

		if ( ! $this->condition( $provider ) ) {
			return;
		}

		$locales = $this->locales();

		$field_name = 'country';

		$field = array(
			'title'   => __( 'Locale', 'gofetch-wpjm' ),
			'name'    => 'feed-' . $field_name,
			'type'    => 'select',
			'choices' => $locales,
			'class'   => 'regular-text',
			'extra'   => array(
				'class'     => 'country-sel',
				'data-qarg' => 'feed-param-' . $field_name,
			),
			'default' => 'us',
		);
?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-co"><strong><?php _e( 'Country', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Search within country specified. <br/><br/>e.g: pt, us, es, de, etc', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<?php echo scbForms::input( $field, array() ) ?>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'language'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Language?', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Biases the default snippets and location string.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 60px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: en', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'sourcetype'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Source Type', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Type of the source who owns the jobs.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: Job Board', 'gofetch-wpjm' ); ?>">
				<option value="all" selected><?php esc_attr_e( 'All', 'gofetch-wpjm' ); ?></option>
				<option value="company"><?php esc_attr_e( 'Company', 'gofetch-wpjm' ); ?></option>
				<option value="staffing"><?php esc_attr_e( 'Staffing', 'gofetch-wpjm' ); ?></option>
				<option value="jobboard"><?php esc_attr_e( 'Job Board', 'gofetch-wpjm' ); ?></option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'contenttype'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Content Type', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Type of content you want to be displayed in the results.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: Month', 'gofetch-wpjm' ); ?>">
				<option value="all"><?php esc_attr_e( 'All', 'gofetch-wpjm' ); ?></option>
				<option value="organic"><?php esc_attr_e( 'Organic', 'gofetch-wpjm' ); ?></option>
				<option value="sponsored" selected><?php esc_attr_e( 'Sponsored', 'gofetch-wpjm' ); ?></option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'searchon'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Search Field', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The field used to match the search keywords.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: Month', 'gofetch-wpjm' ); ?>">
				<option value="title"><?php esc_attr_e( 'Job Title', 'gofetch-wpjm' ); ?></option>
				<option value="empname" selected><?php esc_attr_e( 'Employer Name', 'gofetch-wpjm' ); ?></option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'cpcfloor'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-co"><strong><?php _e( 'Min. Bid', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Minimum bid per job allowed in the results. Works only with sponsored jobs.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 50px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 1', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'sort'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Sort', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The order in which to return results.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>">
				<option value="relevance">Relevance</option>
				<option value="date">Date</option>
				<option value="distance">Distance</option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

		<?php $field_name = 'chnl1'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-chnl"><strong><?php _e( 'Channel Name', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Group API requests to a specific channel name.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 150px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: my-jobs-site', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'chnl2'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-chnl"><strong><?php _e( 'Channel Name 2', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Group API requests to a specific channel name.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 150px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: my-jobs-site 2', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'chnl3'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-chnl"><strong><?php _e( 'Channel Name 3', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Group API requests to a specific channel name.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 150px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: my-jobs-site 3', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'subid'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-chnl"><strong><?php _e( 'Channel Subid', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Group API requests to a specific ID. Can only be integers.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 150px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 12345', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>
<?php
	}

	/**
	 * Keep support for legacy endpoint.
	 */
	public function alt_provider_id( $match, $url ) {
		if ( ! $match && strpos( $url, 'neuvoo.com/services/api-new' ) !== false ) {
			return $this->id;
		}
		return $match;
	}

	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {

		$params = array(
			'format'    => 'xml',
			'ip'        => urlencode( BC_Framework_Utils::get_user_ip() ),
			'useragent' => urlencode( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) ),
		);
		$url = add_query_arg( $params, $url );

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
			'jobkey'            => '',
			'jobtitle'          => '',
			'company'           => '',
			'city'              => '',
			'state'             => '',
			'country'           => '',
			'formattedLocation' => '',
			'source'            => '',
			'date'              => '',
			'category'          => '',
			'url'               => '',
			'logo'              => '',
			'bid'               => '',
			'description'       => '',
			'currency'          => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['jobtitle'] );
			$new_item['date']        = GoFetch_Importer::get_valid_date( $job['date'], 'api' );
			$new_item['location']    = sanitize_text_field( $job['formattedLocation'] );

			// Some locations might retrieve 'null' so, remove them.
			$new_item['location'] = str_replace( array( 'null,', 'null' ), '', $new_item['location'] );

			$new_item['state']       = sanitize_text_field( $job['state'] );
			$new_item['city']        = sanitize_text_field( $job['city'] );
			$new_item['country']     = sanitize_text_field( $job['country'] );
			$new_item['company']     = sanitize_text_field( $job['company'] );

			$new_item['source']      = sanitize_text_field( $job['source'] );
			$new_item['category']    = sanitize_text_field( $job['category'] );
			$new_item['bid']         = sanitize_text_field( $job['bid'] );
			$new_item['currency']    = sanitize_text_field( $job['currency'] );

			$new_item['logo']        = sanitize_text_field( $job['logo'] );

			$new_item['description'] = GoFetch_Importer::format_description( $job['description'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( $job['url'] ) );
			$new_item['_jobkey']     = sanitize_text_field( $job['jobkey'] );

			$new_item['link_atts'] = array(
				'javascript' => array(
					'onmousedown' => sanitize_text_field( $job['onmousedown'] ),
				),
				'class' => ( 'sponsored' === $provider['feed']['query_args']['contenttype']['contenttype'] ? 'goft-wpjm-neuvoo-sponsored': '' ),
			);

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

		$provider['name'] = 'Job Search | Neuvoo';

		return array(
			'provider'    => $provider,
			'items'       => $new_items,
			'sample_item' => $sample_item,
		);
	}

	/**
	 * Set specific meta from Neuvoo.
	 */
	public function params_meta( $params, $item ) {
		global $goft_wpjm_options;

		if ( empty( $item['provider_id'] ) || ! $this->condition( $item['provider_id'] ) ) {
			return $params;
		}

		$params['meta']['_featured'] = isset( $item['sponsored'] ) && (bool) $item['sponsored'] && $goft_wpjm_options->neuvoo_feature_sponsored ? 1: 0;

		if ( isset( $item['sponsored'] ) && (bool) $item['sponsored'] ) {
			$params['meta']['_goft_wpjm_neuvoo_sponsored'] = (bool) $item['sponsored'];
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
		$item['_jobkey']   = null; unset( $item['_jobkey'] );

		return $item;
	}

	/**
	 * Retrieves the list of country codes for this provider.
	 */
	protected function locales() {
		return array(
			''   => 'Auto',
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
			'qa' => 'Qatar',
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

		if ( $goft_wpjm_options->neuvoo_block_search_indexing ) {
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

		$args['publisher'] = $goft_wpjm_options->neuvoo_publisher_id;
		$args['ip']        = urlencode( BC_Framework_Utils::get_user_ip() );
		$args['useragent'] = urlencode( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) );
		$args['v']         = 2;

		return $args;
	}

}
new GoFetch_Neuvoo_API_Feed_Provider();
