<?php
/**
 * Importer classes for providers that use an API to provide jobs.
 *
 * Docs: https://jobs.github.com/api
 *
 * @package GoFetch/Admin/Premium/Professional/API Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// ************
// DISCONTINUED
// ************
/**
 * The class for the Github Feed API.
 */
class GoFetch_Github_API_Feed_Provider extends GoFetch_API_Feed_Provider {

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

		$this->id      = 'api.jobs.github.com';
		$this->api_url = 'https://jobs.github.com/positions.json';

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_filter( 'goft_wpjm_providers', array( $this, 'providers' ), 15 );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );
		add_filter( 'goft_wpjm_sample_item', array( $this, 'sample_item' ), 10, 2 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );

		// Frontend.
		add_action( 'goft_wpjm_single_goft_job', array( $this, 'single_job_page_hooks' ) );
	}

	/**
	 * Enqueues Neuvoo in the list of providers.
	 */
	public function providers( $providers ) {

		$new_providers = array(
			'api.jobs.github.com' => array(
				'API' => array(
					'info'      => 'https://jobs.github.com/api',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
				),
				'website'     => 'https://jobs.github.com',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-githubjobs.png',
				'description' => 'Your job search starts here.',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://jobs.github.com/',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword' => array( 'description' => '' ),
						'location' => array( 'location' => '' ),
						// Custom.
						'lat'  => array( 'lat'  => '' ),
						'long' => array( 'long' => '' ),
						'full_time' => array( 'full_time' => '' ),
					),
					'default' => false,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"column main")]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"column sidebar")]//div[contains(@class,"module logo")]//h2',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"column sidebar")]//div[contains(@class,"module logo")]//div[contains(@class,"logo")]//img/@src',
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
		<?php $field_name = 'lat'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-co"><strong><?php _e( 'Latitude', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'A specific latitude. If used, you must also send \'longitude\' and must not send \'location\'.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 160px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 37.3229978', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'long'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-co"><strong><?php _e( 'Longitude', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'A specific longitude. If used, you must also send \'latitude\' and must not send \'location\'.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 160px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: -122.0321823', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'full_time'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Full-Time.', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( ' If you want to limit results to full time positions set this parameter to "Yes".', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>">
				<option value="1"><?php esc_attr_e( 'Yes', 'gofetch-wpjm' ); ?></option>
				<option value="0" selected><?php esc_attr_e( 'No', 'gofetch-wpjm' ); ?></option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>
<?php
	}

	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {

		$api_data = $this->get_api_data( $url );

		$parsed_url    = parse_url( $url );
		parse_str( $parsed_url['query'], $query_parts );

		$paginated_results = ( ! empty( $this->provider['feed']['pagination'] ) && in_array( $this->provider['feed']['pagination']['params']['page'], array_keys( $query_parts ) ) );

		if ( ! $paginated_results && ( is_wp_error( $api_data ) || empty( $api_data ) ) ) {
			if ( ! is_wp_error( $api_data ) && empty( $api_data ) ) {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Make sure you\'ve specified a \'Keyword\' and/or \'Location\' ', 'gofetch-wpjm' ) );
			}
			return $api_data;
		}
		return $api_data;
	}

	/**
	 * Fetch items from the API feed.
	 */
	public function fetch_feed_items( $items, $url, $provider ) {
		global $goft_wpjm_options;

		$new_items = $sample_item = array();

		$defaults = array(
			'company'     => '',
			'company_url' => '',
			'description' => '',
			'location'    => '',
			'created_at'  => '',
			'url'         => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['title'] );
			$new_item['date']        = GoFetch_Importer::get_valid_date( $job['created_at'], 'api' );
			$new_item['location']    = sanitize_text_field( $job['location'] );

			$new_item['company']     = sanitize_text_field( $job['company'] );
			$new_item['company_url'] = sanitize_url( $job['company_url'] );

			$new_item['description'] = GoFetch_Importer::format_description( $job['description'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( $job['url'] ) );

			// Find the item with the most attributes to use as sample.
			if ( count( array_keys( $new_item ) ) > count( array_keys( $sample_item ) ) ) {
				$sample_item = $new_item;
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

		$provider['name'] = 'Job Search | Github';

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
new GoFetch_Github_API_Feed_Provider();
