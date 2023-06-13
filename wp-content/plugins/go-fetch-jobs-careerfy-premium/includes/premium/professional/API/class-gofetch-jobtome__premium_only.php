<?php
/**
 * Importer classes for providers that use an API to provide jobs.
 *
 * Docs: https://ads.jobtome.com/affiliates/pa/api
 *
 * @package GoFetch/Admin/Premium/Professional/API Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The class for the jobtome Feed API.
 */
class GoFetch_jobtome_API_Feed_Provider extends GoFetch_API_Feed_Provider {

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

		$this->id      = 'api.jobtome.com';
		$this->api_url = sprintf( 'http://api.jobtome.com/v2.php?pid=%1$s', esc_attr( $goft_wpjm_options->jobtome_feed_pid ) );

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

		add_filter( 'goft_no_robots', array( $this, 'maybe_no_robots' ), 10, 2 );

		add_filter( 'goft_wpjm_use_custom_feed_pagination', array( $this, 'use_custom_pagination' ), 10, 2 );
		add_filter( 'goft_wpjm_custom_feed_pagination', array( $this, 'custom_pagination' ), 10, 4 );
	}

	/**
	 * Init the jobtome tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'jobtome', __( 'Jobtome', 'gofetch-wpjm' ) );
		$this->tab_jobtome();
	}

	/**
	 * jobtome settings tab.
	 */
	protected function tab_jobtome() {
		global $goft_wpjm_options;

		$docs = 'https://ads.jobtome.com/affiliates/pa/api';

		$this->all_tabs->tab_sections['jobtome']['logo'] = array(
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

		$this->all_tabs->tab_sections['jobtome']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title'   => __( 'Country', 'gofetch-wpjm' ),
					'name'    => 'jobtome_feed_default_country',
					'type'    => 'select',
					'choices' => $this->locales(),
					'tip'     => __( 'Search within country specified. You can still change the country on each import template.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Publisher ID *', 'gofetch-wpjm' ),
					'name'  => 'jobtome_feed_pid',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Register for a free <a href="%1$s" target="_new">Jobtome Publisher Account</a> to get your Publisher ID.', 'gofetch-wpjm' ), 'https://ads.jobtome.com/affiliates/' ),
					'tip'   => __( 'You need a publisher ID in order to pull jobs from this provider.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['jobtome']['defaults'] = array(
			'title' => __( 'Feed Defaults', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Location', 'gofetch-wpjm' ),
					'name'  => 'jobtome_feed_default_location',
					'type'  => 'text',
					'tip' => __( 'The geographic centre of the search. Multiple locations are not supported (e.g: new york, boston).', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Channel Name', 'gofetch-wpjm' ),
					'name'  => 'jobtome_feed_default_channel',
					'type'  => 'text',
					'tip'   => __( 'Group API requests to a specific channel name.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Results Per Page', 'gofetch-wpjm' ),
					'name'  => 'jobtome_feed_default_results_per_page',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'The number of jobs to include on the results. Max recommended is \'100\'.', 'gofetch-wpjm' ),
				),

			),
		);

		$this->all_tabs->tab_sections['jobtome']['jobs'] = array(
			'title' => __( 'Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Block Search Indexing', 'gofetch-wpjm' ),
					'name'  => 'jobtome_block_search_indexing',
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
	 * Enqueues jobtome in the list of providers.
	 */
	public function providers( $providers ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'api.jobtome.com' => array(
				'API' => array(
					'info'      => 'https://ads.jobtome.com/affiliates/pa/api',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'Publisher ID'  => 'jobtome_feed_pid',
					),
				),
				'website'     => 'https://www.jobtome.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-jobtome.png',
				'description' => 'InternationaL Job Search: Job Opportunities',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://ads.jobtome.com/affiliates/pa/api',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'  => array( 'k' => '' ),
						'location' => array( 'l' => esc_attr( $goft_wpjm_options->jobtome_feed_default_location ) ),
						'limit'    => array( 'results'  => esc_attr( $goft_wpjm_options->jobtome_feed_default_results_per_page ) ),
						// Custom
						'country' => array( 'country'  => esc_attr( $goft_wpjm_options->jobtome_feed_default_country ) ),
						'channel' => array( 'channel'  => esc_attr( $goft_wpjm_options->jobtome_feed_default_channel ) ),
					),
					'pagination' => array(
						'params'  => array(
							'page'  => 'p',
							'limit' => 'results',
						),
						'type'    => 'page',
						'results' => 50,
					),
					'scraping' => false,
					'notes' => 'Uses JS scrape blocker',
					'default' => false,
				),
				'multi_region_match'  => 'jobtome',
				'region_param_domain' => 'country',
				'region_domains'      => $this->locales(),
				'region_default'      => 'us',
				'category'            => 'API',
				'weight'              => 10,
			),
		);
		return array_merge( $providers, $new_providers );
	}

	/**
	 * Outputs specific jobtome feed parameter fields.
	 */
	public function feed_builder_fields( $provider ) {
		global $goft_wpjm_options;

		if ( ! $this->condition( $provider ) ) {
			return;
		}
?>

		<?php $field_name = 'channel'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Channel Name', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Group API requests to a specific channel name..', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: my-secondary-site-channel', 'gofetch-wpjm' ); ?>">
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
				$url = str_replace( sprintf( 'page=%s', $params['page'] ), '', $url );
			}
		}

		$api_args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		$url = add_query_arg( array(
			'p'         => 1,
			'output'    => 'json',
			'ip'        => urlencode( BC_Framework_Utils::get_user_ip() ),
			'browser' => urlencode( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) ),
		), $url );

		$api_data = $this->get_api_data( $url, $_xml_data = false, $api_args );

		$paginated_results = ( ! empty( $this->provider['feed']['pagination'] ) && in_array( $this->provider['feed']['pagination']['params']['page'], array_keys( $params ) ) );

		if ( ! $paginated_results && ( is_wp_error( $api_data ) || empty( $api_data['results'] ) ) ) {

			if ( ! is_wp_error( $api_data ) ) {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Make sure you\'ve specified \'Keyword\' and that your API key is valid for the selected country.', 'gofetch-wpjm' ) );
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
			'title'       => '',
			'description' => '',
			'company'     => '',
			'location'    => '',
			'source'      => '',
			'date'        => '',
			'url'         => '',
			'affiliate'   => '',
			'onmousedown' => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['title'] );
			$new_item['date']        = GoFetch_Importer::get_valid_date( $job['date'], 'api' );
			$new_item['location']    = sanitize_text_field( $job['location'] );

			// Some locations might retrieve 'null' so, remove them.
			$new_item['location'] = str_replace( array( 'null,', 'null' ), '', $new_item['location'] );

			$new_item['company'] = sanitize_text_field( $job['company'] );
			$new_item['source']  = sanitize_text_field( $job['source'] );

			$new_item['description'] = GoFetch_Importer::format_description( $job['description'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( $job['url'] ) );

			$new_item['link_atts'] = array(
				'javascript' => array(
					'onmousedown' => sanitize_text_field( $job['onmousedown'] ),
				),
				'class' => (bool) $job['affiliate'] ? 'goft-wpjm-jobtome-aff': '',
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

		$provider['name'] = 'Job Search | jobtome';

		return array(
			'provider'    => $provider,
			'items'       => $new_items,
			'sample_item' => $sample_item,
		);
	}

	/**
	 * Set specific meta from jobtome.
	 */
	public function params_meta( $params, $item ) {
		global $goft_wpjm_options;

		if ( empty( $item['provider_id'] ) || ! $this->condition( $item['provider_id'] ) ) {
			return $params;
		}

		if ( isset( $item['affiliate'] ) && (bool) $item['affiliate'] ) {
			$params['meta']['_goft_wpjm_jobtome_affiliate'] = (bool) $item['affiliate'];
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

		$item['id']    = null; unset( $item['id'] );
		$item['adref'] = null; unset( $item['adref'] );


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
			'be' => 'Belgium',
			'br' => 'Brazil',
			'ca' => 'Canada',
			'cl' => 'Chile',
			'co' => 'Colombia',
			'cz' => 'Czech Republic',
			'dk' => 'Denmark',
			'fi' => 'Finland',
			'fr' => 'France',
			'de' => 'Germany',
			'hk' => 'Hong Kong',
			'hu' => 'Hungary',
			'in' => 'India',
			'id' => 'Indonesia',
			'ie' => 'Ireland',
			'it' => 'Italy',
			'mx' => 'Mexico',
			'nl' => 'Netherlands',
			'nz' => 'New Zealand',
			'ph' => 'Philippines',
			'pl' => 'Poland',
			'pt' => 'Portugal',
			'ro' => 'Romania',
			'ru' => 'Russia',
			'sg' => 'Singapore',
			'za' => 'South Africa',
			'es' => 'Spain',
			'se' => 'Sweden',
			'ch' => 'Switzerland',
			'gb' => 'United Kingdom',
			'us' => 'United States',
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
			wp_enqueue_script( 'jobtome-click-tracking', '//api.jobtome.com/trust.js', array(), GoFetch_Jobs()->version, true );
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

		if ( $goft_wpjm_options->jobtome_block_search_indexing ) {
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

		$args['publisher'] = $goft_wpjm_options->jobtome_publisher_id;
		$args['ip']        = urlencode( BC_Framework_Utils::get_user_ip() );
		$args['useragent'] = urlencode( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) );
		$args['v']         = 2;

		return $args;
	}

}
new GoFetch_jobtome_API_Feed_Provider();
