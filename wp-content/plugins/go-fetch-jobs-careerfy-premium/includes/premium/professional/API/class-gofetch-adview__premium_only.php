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
class GoFetch_AdView_API_Feed_Provider extends GoFetch_API_Feed_Provider {

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

		$this->id      = 'adview.online';
		$this->api_url = sprintf(
			'https://adview.online/api/v1/jobs.json?publisher=%1$s',
			esc_attr( $goft_wpjm_options->adview_publisher_id )
		);

		$this->tracking_update = $goft_wpjm_options->adview_feed_default_track_update_interval;

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {

		if ( $this->tracking_update ) {
			add_filter( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		}

		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-providers', array( $this, 'tabs' ), 25 );
		add_filter( 'goft_wpjm_providers', array( $this, 'providers' ), 15 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );
		add_filter( 'goft_wpjm_sample_item', array( $this, 'sample_item' ), 10, 2 );
		add_filter( 'goft_wpjm_api_updated_job_link', array( $this, 'job_link' ), 10, 3 );

		// Frontend.
		add_action( 'goft_wpjm_single_goft_job', array( $this, 'single_job_page_hooks' ) );

		add_action( 'goft_no_robots', array( $this, 'maybe_no_robots' ),10, 2 );
	}

	/**
	 * Init the Indeed tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'adview', __( 'AdView', 'gofetch-wpjm' ) );
		$this->tab_adview();
	}

	/**
	 * Indeed settings tab.
	 */
	protected function tab_adview() {

		$info_url = 'https://adview.online/publisher';

		$this->all_tabs->tab_sections['adview']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Publisher ID', 'gofetch-wpjm' ),
					'name'  => 'adview_publisher_id',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Sign up for a free <a href="%1$s" target="_new">AdView Publisher Account</a>', 'gofetch-wpjm' ), esc_url( $info_url ) ),
					'tip'   => __( 'You need a Publisher ID in order to pull jobs from AdView.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['adview']['defaults'] = array(
			'title' => __( 'Feed Defaults', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Snippet', 'gofetch-wpjm' ),
					'name'  => 'adview_feed_default_snippet',
					'type'  => 'select',
					'choices' => array(
						'full'      => 'Full',
						'highlight' => 'Highlight',
						'basic'     => 'Basic',
					),
					'tip' => __( 'Choose the job snippet size.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Radius', 'gofetch-wpjm' ),
					'name'  => 'adview_feed_default_radius',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Distance from search location ("as the crow flies")', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Min. Salary', 'gofetch-wpjm' ),
					'name'  => 'adview_feed_default_salary_from',
					'type'  => 'text',
					'extra' => array(
						'style' => 'width: 80px',
					),
					'desc' => __( '(Annual Salary)', 'gofetch-wpjm' ),
					'tip' => __( 'Only pull jobs that pay more than the salary you specify here (only numeric values without currency). Leave empty for any.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Max. Salary', 'gofetch-wpjm' ),
					'name'  => 'adview_feed_default_salary_to',
					'type'  => 'text',
					'extra' => array(
						'style' => 'width: 80px',
					),
					'desc' => __( '(Annual Salary)', 'gofetch-wpjm' ),
					'tip' => __( 'Only pull jobs that pay up to the salary you specify here (only numeric values without currency). Leave empty for any.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Job Type', 'gofetch-wpjm' ),
					'name'  => 'adview_feed_default_job_type',
					'type'  => 'select',
					'choices' => array(
						''           => __( 'Any', 'gofetch-wpjm' ),
						'permanent'  => 'Permanent',
						'temporary'  => 'Temporary',
						'contract'   => 'Contract',
						'internship' => 'Placement Student',
						'seasonal'   => 'Seasonal',
					),
					'tip' => __( 'Choose a specific job type if you want to target your jobs (only one job type per request is allowed).', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Channel Name', 'gofetch-wpjm' ),
					'name'  => 'adview_feed_default_channel',
					'type'  => 'text',
					'extra' => array(
						'style' => 'width: 150px',
					),
					'tip' => __( 'Channel name is used to categorise your AdView tracking reports.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Sorting', 'gofetch-wpjm' ),
					'name'  => 'adview_feed_default_sort',
					'type'  => 'select',
					'choices' => array(
						'relevance' => 'Relevance',
						'date'      => 'Date',
						'distance'  => 'Distance',
					),
					'tip' => __( 'Sort by <em>relevance</em>, <em>date</em> or <em>distance</em>.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Limit', 'gofetch-wpjm' ),
					'name'  => 'adview_feed_default_limit',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Maximum number of results returned per query. Limit is 50.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['adview']['tracking'] = array(
			'title' => __( 'Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Links Update Interval', 'gofetch-wpjm' ),
					'name'  => 'adview_feed_default_track_update_interval',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'desc' => __( ' hour(s)', 'gofetch-wpjm' ),
					'tip' => __( 'AdView expires their link tracking to make sure jobs are not stale. Set how often the links should be updated (in hours) to keep click tracking working. Leave empty to disable update.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Expire Jobs', 'gofetch-wpjm' ),
					'name'  => 'adview_expire_jobs',
					'type'  => 'checkbox',
					'desc'  => __( 'Yes', 'gofetch-wpjm' ),
					'tip'   => __( 'Check this option to expire jobs that are no longer valid for click tracking.', 'gofetch-wpjm' ) .
							'<br/><br/>' . __( 'Click tracking is consider invalid when a job is no longer found when contacting the provider using the original feed URL.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Block Search Indexing', 'gofetch-wpjm' ),
					'name'  => 'adview_block_search_indexing',
					'type'  => 'checkbox',
					'desc'  => __( 'Yes', 'gofetch-wpjm' ),
					'tip'   => __( 'Check this option to block search robots from indexing imported jobs pages from this provider API.', 'gofetch-wpjm' ) .
							'<br/><br/>' . __( 'This option should be checked for providers that do not allow indexing their jobs.', 'gofetch-wpjm' ),
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
			'adview.online/api' => array(
				'API' => array(
					'info'      => 'https://adview.online/api/v1/jobs.json',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'Publisher ID' => 'adview_publisher_id',
					),
				),
				'website'     => 'https://adview.online/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-adview.png',
				'description' => 'AdView is an UK employment search engine.',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://adview.online/advanced-search',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'  => array( 'keyword'  => '' ),
						'location' => array( 'location' => '' ),
						'limit'    => array( 'limit'    => esc_attr( $goft_wpjm_options->adview_feed_default_limit ) ),
						'radius'   => array( 'radius'   => esc_attr( $goft_wpjm_options->adview_feed_default_radius ) ),
						'type'     => array( 'jt'     => array(
							'placeholder'   => "e.g: permanent",
							'default_value' => esc_attr( $goft_wpjm_options->adview_feed_default_job_type ),
						) ),
						// Custom.
						'snippet'     => array( 'snippet'     => esc_attr( $goft_wpjm_options->adview_feed_default_snippet ) ),
						'salary_from' => array( 'salary_from' => esc_attr( $goft_wpjm_options->adview_feed_default_salary_from ) ),
						'salary_to'   => array( 'salary_to'   => esc_attr( $goft_wpjm_options->adview_feed_default_salary_to ) ),
						'sort'        => array( 'sort'        => esc_attr( $goft_wpjm_options->adview_feed_default_sort ) ),
						'channel'     => array( 'channel'     => esc_attr( $goft_wpjm_options->adview_feed_default_channel ) ),
					),
					'pagination' => array(
						'params'  => array(
							'page'  => 'page',
							'limit' => 'limit',
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
		<?php $field_name = 'snippet'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Snippet', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The job snippet size. Please note that \'Full\' will retrieve a longer snippet but does not guarantee it will return the full job description (thats why <em>AdView</em> calls it a \'Snippet\').', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: 100px;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>">
				<option value='full' selected>Full</option>
				<option value='highlight'>Highlight</option>
				<option value='basic'>Basic</option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'salary_from'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Min. Salary', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Limit results with annual salary greater than this number.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 100px" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 50000', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'salary_to'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Max. Salary', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Limit results with annual salary up to this number.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 100px" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 100000', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

		<p class="params opt-param-channel">
			<label for="feed-channel"><strong><?php _e( 'Channel Name', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Used to categorize your AdView tracking reports.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-channel"></span>
			<input type="text" class="regular-text" style="width: 150px" name="feed-channel" data-qarg="feed-param-channel" placeholder="<?php echo __( 'e.g.: my-jobs-site', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-channel">
		</p>
<?php
	}

	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {

		$params = array(
			'format'     => 'json',
			'link'       => 1,
			'user_ip'    => urlencode( BC_Framework_Utils::get_user_ip() ),
			'user_agent' => urlencode( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) ),
		);
		$url = add_query_arg( $params, $url );

		$api_data = $this->get_api_data( $url );

		if ( is_wp_error( $api_data ) || empty( $api_data['data'] ) ) {

			if ( ! is_wp_error( $api_data ) ) {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Consider tweaking your filters to increase job matches.', 'gofetch-wpjm' ) );
			}
			return $api_data;
		}
		return $api_data['data'];
	}

	/**
	 * Fetch items from the API feed.
	 */
	public function fetch_feed_items( $items, $url, $provider ) {
		global $goft_wpjm_options;

		$new_items = $sample_item = array();

		$defaults = array(
			'title'       => '',
			'location'    => '',
			'company'     => '',
			'snippet'     => '',
			'job_type'    => '',
			'salary'      => '',
			'logo'        => '',
			'url'         => '',
			'onmousedown' => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['title'] );
			$new_item['location']    = sanitize_text_field( $job['location'] );
			$new_item['company']     = sanitize_text_field( $job['company'] );
			$new_item['description'] = GoFetch_Importer::format_description( $job['snippet'] );
			$new_item['job_type']    = sanitize_text_field( $job['job_type'] );
			$new_item['salary']      = sanitize_text_field( $job['salary'] );
			$new_item['logo']        = esc_url( $job['logo'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( $job['url'] ) );

			$new_item['link_atts'] = array(
				'javascript' => array(
					'onmousedown' => sanitize_text_field( $job['onmousedown'] ),
				),
			);

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

		$provider['name'] = 'AdView - Jobs & Careers';

		return array(
			'provider'    => $provider,
			'items'       => $new_items,
			'sample_item' => $sample_item,
		);
	}

	/**
	 * Set specific meta from AdView.
	 */
	public function params_meta( $params, $item ) {
		global $goft_wpjm_options;

		if ( empty( $item['provider_id'] ) || ! $this->condition( $item['provider_id'] ) ) {
			return $params;
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

		$item['link_atts'] = null; unset( $item['link_atts'] );

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

		// Enqueue other hooks for this provider.
		add_filter( 'goft_wpjm_read_more_link_attributes', array( $this, 'link_attributes' ), 10, 2 );
		add_filter( 'goft_wpjm_source_link_attributes', array( $this, 'link_attributes' ), 10, 2 );
		add_filter( 'goft_wpjm_external_link_qargs', array( $this, 'external_link_args' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', function() {
			wp_enqueue_script( 'adview-click-tracking', '//adview.online/js/pub/tracking.js', array(), GoFetch_Jobs()->version, true );
		} );
		// Trigger jobs tracking update on this provider.
		add_action( 'wp_footer', array( $this, 'update_job_tracking' ) );
	}

	/**
	 * Apply additional attributes to each external job link.
	 */
	public function job_link( $link, $post_id = 0, $provider_id = 0 ) {

		if ( $post_id && ( empty( $provider_id ) || ! $this->condition( $provider_id ) ) ) {
			return $link;
		}

		$args = array(
			'user_ip'    => urlencode( BC_Framework_Utils::get_user_ip() ),
			'user_agent' => urlencode( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) ),
		);
		return add_query_arg( $args, $link );
	}

	/**
	 * Apply additional attributes to each external job link.
	 */
	public function link_attributes( $attributes, $post ) {

		$link_atts = get_post_meta( $post->ID, '_goft_link_atts', true );

		if ( ! empty( $link_atts['javascript'] ) ) {
			foreach ( $link_atts['javascript'] as $event => $action ) {
				$attributes[ $event ] = esc_attr( $action );
			}
		}
		$attributes['href'] = $this->job_link( $attributes['href'] );

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

		if ( $goft_wpjm_options->adview_block_search_indexing ) {
			$robots['noindex'] = true;
		}
		return $robots;
	}

	/**
	 * Append additional required args to each job link.
	 */
	public function external_link_args( $args, $params ) {
		global $goft_wpjm_options, $post;

		if ( empty( $params['website'] ) || false === strpos( $params['website'], $this->id ) ) {
			return $args;
		}

		$metadata = get_post_meta( $post->ID, '_goft_source_data', true );

		if ( ! empty( $metadata['channel'] ) ) {
			$args['channel'] = urlencode( $metadata['channel'] );
		}

		$args['publisher'] = $goft_wpjm_options->adview_publisher_id;
		$args['source']    = 'feed';

		return $args;
	}
}
new GoFetch_AdView_API_Feed_Provider();
