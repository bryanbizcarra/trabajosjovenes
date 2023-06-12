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
 * The class for the Remotive Feed API.
 */
class GoFetch_Remotive_API_Feed_Provider extends GoFetch_API_Feed_Provider {

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

		$this->id      = 'remotive.io/api';
		$this->api_url = 'https://remotive.io/api/remote-jobs';

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_filter( 'goft_wpjm_providers', array( $this, 'config' ), 15 );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );

		// Frontend.
		add_action( 'goft_wpjm_single_goft_job', array( $this, 'single_job_page_hooks' ) );

		add_action( 'goft_no_robots', array( $this, 'maybe_no_robots' ), 10, 2 );
	}

	/**
	 * Enqueues Remotive in the list of providers.
	 */
	public function config( $providers = array() ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'remotive.io/api' => array(
				'API' => array(
					'info' => 'https://remotive.io/api-documentation',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
				),
				'website'     => 'https://www.remotive.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-remotive.svg',
				'description' => 'Remote Jobs in Programming, Support, Design and mores',
				'feed'        => array(
					'url_match'  => 'remotive.io/api',
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://remotive.io/remote-jobs',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword' => array( 'search' => '' ),
						'limit'   => array( 'limit'  => '' ),
						// Custom.
						'category'     => array( 'category' => '' ),
						'company_name' => array( 'company_name' => '' ),
					),
					'default' => false,
				),
				'category' => 'API',
				'weight'   => 10,
			),
		);
		return array_merge( $providers, $new_providers );
	}

	/**
	 * Outputs specific Remotive feed parameter fields.
	 */
	public function feed_builder_fields( $provider ) {

		if ( ! $this->condition( $provider ) ) {
			return;
		}

		$field = array(
			'title'   => __( 'Categories', 'gofetch-wpjm' ),
			'name'    => 'feed-category',
			'type'    => 'select',
			'choices' => $this->categories(),
			'class'   => 'regular-text',
			'extra'   => array(
				'data-qarg' => 'feed-param-category',
				'style'     => "width: 550px;",
			),
			'default' => 'accounting',
		);
?>
		<?php $field_name = 'category'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Category', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Retrieve jobs only for this category.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<?php echo scbForms::input( $field, array() ) ?>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'company_name'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Company', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Filter by company name.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: auto" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: google', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

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

		$api_data = $this->get_api_data( $url );

		if ( is_wp_error( $api_data ) || empty( $api_data['jobs'] ) ) {
			if ( ! is_wp_error( $api_data ) ) {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Make sure you\'ve specified \'Keyword\', or \'Category\'', 'gofetch-wpjm' ) );
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
			'id'                          => '',
			'url'                         => '',
			'title'                       => '',
			'company_name'                => '',
			'category'                    => '',
			'tags'                        => '',
			'job_type'                    => '',
			'publication_date'            => '',
			'candidate_required_location' => '',
			'salary'                      => '',
			'description'                 => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['title'] );
			$new_item['description'] = GoFetch_Importer::format_description( $job['description'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( $job['url'] ) );

			$new_item['company']                     = sanitize_text_field( $job['company_name'] );
			$new_item['category']                    = sanitize_text_field( $job['category'] );
			$new_item['tags']                        = is_array( $job['tags'] ) ? implode( ',', array_map( 'sanitize_text_field', $job['tags'] ) ): sanitize_text_field( $job['tags'] );
			$new_item['job_type']                    = sanitize_text_field( $job['job_type'] );
			$new_item['date']                        = GoFetch_Importer::get_valid_date( $job['publication_date'], 'api' );
			$new_item['candidate_required_location'] = sanitize_text_field( $job['candidate_required_location'] );
			$new_item['salary']                      = sanitize_text_field( $job['salary'] );

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

		$provider['name'] = 'Job Search | Remotive';

		return array(
			'provider'    => $provider,
			'items'       => $new_items,
			'sample_item' => $sample_item,
		);
	}

	/**
	 * Set specific meta from Remotive.
	 */
	public function params_meta( $params, $item ) {
		global $goft_wpjm_options;

		if ( empty( $item['provider_id'] ) || ! $this->condition( $item['provider_id'] ) ) {
			return $params;
		}

		return $params;
	}

	/**
	 * Job categories for this provider.
	 */
	private function categories() {
		return array(
			'software-dev'     => 'Software Development',
			'customer-support' => 'Customer Service',
			'design'           => 'Design',
			'marketing'        => 'Marketing',
			'sales'            => 'Sales',
			'product'          => 'Product',
			'business'         => 'Business',
			'data'             => 'Data',
			'devops'           => 'DevOps / Sysadmin',
			'finance-legal'    => 'Finance / Legal',
			'hr'               => 'Human Resources',
			'qa'               => 'QA',
			'writing'          => 'Writing',
			'all-others'       => 'All Others',
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
new GoFetch_Remotive_API_Feed_Provider();
