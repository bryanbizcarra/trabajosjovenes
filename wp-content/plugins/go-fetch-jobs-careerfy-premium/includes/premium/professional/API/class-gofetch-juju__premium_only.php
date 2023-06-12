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
 * The class for the Juju Feed API.
 */
class GoFetch_Juju_API_Feed_Provider extends GoFetch_API_Feed_Provider {

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

		$this->id      = 'api.juju.com';
		$this->api_url = sprintf( 'http://api.juju.com/jobs?partnerid=%1$s', esc_attr( $goft_wpjm_options->juju_publisher_id ) );

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-providers', array( $this, 'tabs' ), 85 );
		add_filter( 'goft_wpjm_providers', array( $this, 'config' ), 15 );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );

		// Frontend.
		add_action( 'goft_wpjm_single_goft_job', array( $this, 'single_job_page_hooks' ) );

		add_action( 'goft_no_robots', array( $this, 'maybe_no_robots' ), 10, 2 );
	}

	/**
	 * Init the Juju tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'juju', __( 'Juju', 'gofetch-wpjm' ) );
		$this->tab_juju();
	}

	/**
	 * Juju settings tab.
	 */
	protected function tab_juju() {

		$this->all_tabs->tab_sections['juju']['logo'] = array(
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

		$this->all_tabs->tab_sections['juju']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Publisher ID *', 'gofetch-wpjm' ),
					'name'  => 'juju_publisher_id',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Sign up for a free <a href="%1$s" target="_new">Juju Publisher Account</a>', 'gofetch-wpjm' ), 'https://www.juju.com/publisher/aup/' ),
					'tip'   => __( 'You need a publisher ID in order to pull jobs from Juju.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['juju']['defaults'] = array(
			'title' => __( 'Feed Defaults', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Radius', 'gofetch-wpjm' ),
					'name'  => 'juju_feed_default_radius',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Distance from search location ("as the crow flies")', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Industry', 'gofetch-wpjm' ),
					'name'  => 'juju_feed_default_industry',
					'type'  => 'checkbox',
					'choices' => $this->categories(),
					'extra'  => array(
						'style'            => 'width: 100%;',
						'multiple'         => 'multiple',
						'class'            => 'select2-gofj-multiple',
						'data-allow-clear' => 'true',
					),
					'tip' => __( 'Choose the job industries that suit your site.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Days Back', 'gofetch-wpjm' ),
					'name'  => 'juju_feed_default_fromage',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Number of days back to search.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Sorting', 'gofetch-wpjm' ),
					'name'  => 'juju_feed_default_sort',
					'type'  => 'select',
					'choices' => array(
						'relevance' => 'Relevance',
						'date'      => 'Date',
						'distane'   => 'Distance',
					),
					'tip' => __( 'Sort by <em>relevance</em>, <em>date</em> or <em>distance</em>.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Limit', 'gofetch-wpjm' ),
					'name'  => 'juju_feed_default_limit',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Maximum number of results returned per query. Limit is 20.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Channel Name', 'gofetch-wpjm' ),
					'name'  => 'juju_feed_default_chnl',
					'type'  => 'text',
					'tip' => __( 'Group API requests to a specific channel name.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['juju']['jobs'] = array(
			'title' => __( 'Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Block Search Indexing', 'gofetch-wpjm' ),
					'name'  => 'juju_block_search_indexing',
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
	 * Enqueues Juju in the list of providers.
	 */
	public function config( $providers = array() ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'api.juju.com' => array(
				'API' => array(
					'info' => 'http://www.juju.com/publisher/spec/',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'Publisher ID' => 'juju_publisher_id',
					),
				),
				'website'     => 'https://www.juju.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-juju.png',
				'description' => 'Smarter Job Search',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://www.juju.com/jobs',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'  => array( 'k'      => '' ),
						'location' => array( 'l'      => '' ),
						'limit'    => array( 'jpp'  => esc_attr( $goft_wpjm_options->juju_feed_default_limit ) ),
						'radius'   => array( 'r' => esc_attr( $goft_wpjm_options->juju_feed_default_radius ) ),
						// Custom.
						'c'      => array( 'c' => esc_attr( implode( ',', (array) $goft_wpjm_options->juju_feed_default_industry ) ) ),
						'days'   => array( 'days' => esc_attr( $goft_wpjm_options->juju_feed_default_fromage ) ),
						'order'  => array( 'order' => esc_attr( $goft_wpjm_options->juju_feed_default_sort ) ),
						'channel' => array( 'channel' => esc_attr( $goft_wpjm_options->juju_feed_default_chnl ) ),
					),
					'pagination' => array(
						'params'  => array(
							'page'  => 'page',
							'limit' => 'jpp',
						),
						'type'    => 'offset',
						'results' => 20,
					),
					'scraping' => false,
					'notes' => 'Redirects to third parties',
					'default' => false,
				),
				'category' => 'API',
				'weight'   => 10,
			),
		);
		return array_merge( $providers, $new_providers );
	}

	/**
	 * Outputs specific Juju feed parameter fields.
	 */
	public function feed_builder_fields( $provider ) {

		if ( ! $this->condition( $provider ) ) {
			return;
		}

		$field = array(
			'title'   => __( 'Industries', 'gofetch-wpjm' ),
			'name'    => 'feed-c',
			'type'    => 'select',
			'choices' => $this->categories(),
			'class'   => 'regular-text',
			'extra'   => array(
				'data-qarg' => 'feed-param-c',
				'multiple'  => 'multiple',
				'style'     => "width: 550px;",
			),
			'default' => 'accounting',
		);
?>
		<?php $field_name = 'c'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Industries', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Choose the jobs industries that suit your site.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<?php echo scbForms::input( $field, array() ) ?>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'days'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Days Back', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Number of days back to search.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 65px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 5', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'order'; ?>
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

		<?php $field_name = 'channel'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Channel Name', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Group API requests to a specific channel name.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 150px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: my-jobs-site', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>
<?php
	}

	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {

		$params = array(
			'ipaddress' => urlencode( BC_Framework_Utils::get_user_ip() ),
			'useragent' => urlencode( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) ),
			'highlight' => 0,
		);
		$url = add_query_arg( $params, $url );

		$api_data = $this->get_api_data( $url, $_xml_data = true );

		if ( is_wp_error( $api_data ) || empty( $api_data['channel']['item'] ) ) {

			if ( ! is_wp_error( $api_data ) ) {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Make sure you\'ve specified \'Keyword\', \'Location\' or \'Industry\'', 'gofetch-wpjm' ) );
			}
			return $api_data;
		}
		return $api_data['channel']['item'];
	}

	/**
	 * Fetch items from the API feed.
	 */
	public function fetch_feed_items( $items, $url, $provider ) {
		global $goft_wpjm_options;

		$new_items = $sample_item = array();

		$defaults = array(
			'zip'         => '',
			'city'        => '',
			'county'      => '',
			'state'       => '',
			'country'     => '',
			'source'      => '',
			'company'     => '',
			'link'        => '',
			'site_type'   => '',
			'post_date'   => '',
			'description' => '',
			'onclick'     => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$location = '';

			if ( ! empty( $job['city'] )) {
				$location = $job['city'];
			}
			if ( ! empty( $job['county'] )) {
				$location .= $job['county'];
			}

			if ( ! empty( $job['country'] )) {
				$location .= $job['country'];
			}

			$new_item['provider_id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['title'] );
			$new_item['date']        = GoFetch_Importer::get_valid_date( $job['post_date'], 'api' );
			$new_item['location']    = sanitize_text_field( $location );
			$new_item['state']       = sanitize_text_field( $job['state'] );
			$new_item['city']        = sanitize_text_field( $job['city'] );
			$new_item['zip']         = sanitize_text_field( $job['zip'] );
			$new_item['country']     = sanitize_text_field( $job['country'] );
			$new_item['company']     = sanitize_text_field( $job['company'] );
			$new_item['source']      = sanitize_text_field( $job['source'] );
			$new_item['description'] = GoFetch_Importer::format_description( $job['description'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( $job['link'] ) );

			$new_item['link_atts'] = array(
				'javascript' => array(
					'onclick' => sanitize_text_field( $job['onclick'] ),
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

		$provider['name'] = 'Job Search | Juju';

		return array(
			'provider'    => $provider,
			'items'       => $new_items,
			'sample_item' => $sample_item,
		);
	}

	/**
	 * Set specific meta from Juju.
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

		return $params;
	}

	/**
	 * Job categories for this provider.
	 */
	private function categories() {
		return array(
			'accounting'               => 'Accounting',
			'administrative-clerical'  => 'Administrative / Clerical',
			'banking-mortgage'         => 'Banking / Mortgage',
			'biotech-pharmaceutical'   => 'Biotech / Pharmaceutical',
			'construction'             => 'Construction',
			'customer-service'         => 'Customer Service',
			'design'                   => 'Design',
			'education'                => 'Education',
			'engineering'              => 'Engineering',
			'entry-level'              => 'Entry Level',
			'facilities'               => 'Facilities',
			'finance'                  => 'Finance',
			'government'               => 'Government',
			'health-care'              => 'Health Care',
			'hospitality'              => 'Hospitality',
			'human-resources'          => 'Human Resources',
			'installer-technician'     => 'Installer / Technician',
			'insurance'                => 'Insurance',
			'legal'                    => 'Legal',
			'logistics-transportation' => 'Logistics / Transportation',
			'management'               => 'Management',
			'manufacturing-industrial' => 'Manufacturing / Industrial',
			'marketing'                => 'Marketing',
			'media'                    => 'Media',
			'non-profit'               => 'Non Profit',
			'nursing'                  => 'Nursing',
			'real-estate'              => 'Real Estate',
			'restaurant'               => 'Restaurant',
			'retail'                   => 'Retail',
			'sales'                    => 'Sales',
			'sciences'                 => 'Sciences',
			'software-it'              => 'Software / It',
			'warehouse'                => 'Warehouse',
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

		add_action( 'wp_enqueue_scripts', function() {
			wp_enqueue_script( 'juju-click-tracking', '//d5k1a84rm5hwo.cloudfront.net/partnerapi.js', array(), GoFetch_Jobs()->version, true );
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
				$attributes['onclick'] = esc_attr( $link_atts['javascript'] );
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

		if ( $goft_wpjm_options->juju_block_search_indexing ) {
			$robots['noindex'] = true;
		}
		return $robots;
	}


}
new GoFetch_Juju_API_Feed_Provider();
