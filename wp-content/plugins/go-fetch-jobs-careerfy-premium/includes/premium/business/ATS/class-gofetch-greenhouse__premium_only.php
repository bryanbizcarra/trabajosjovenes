<?php
/**
 * Importer classes for providers that use an API to provide jobs.
 *
 * Docs: https://developers.greenhouse.io/job-board.html#list-jobs
 *
 * @package GoFetch/Admin/Premium/Business/ATS Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The class for the greenhouse Feed API.
 */
class GoFetch_greenhouse_API_Feed_Provider extends GoFetch_API_Feed_Provider {

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

		$this->id      = 'greenhouse.io';
		$this->api_url = sprintf( 'https://boards-api.greenhouse.io/v1/boards/%1$s/jobs?content=true', esc_attr( $goft_wpjm_options->greenhouse_board_token ) );

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-ats', array( $this, 'tabs' ), 15 );

		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-ats_form_handler', array( $this, 'form_handler' ), 15, 2 );

		add_filter( 'goft_wpjm_providers', array( $this, 'providers' ), 15 );
		add_filter( 'goft_wpjm_sample_item', array( $this, 'sample_item' ), 10, 2 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );

		add_action( 'admin_notices', array( $this, 'display_notice' ) );
	}

	/**
	 * Init the greenhouse tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'greenhouse', __( 'Greenhouse', 'gofetch-wpjm' ) );
		$this->tab_greenhouse();
	}

	/**
	 * Display a custom notice.
	 */
	public function display_notice() {
		$class = 'notice notice-warning';

		if ( ! isset( $_GET['tab'] ) || 'greenhouse' !== $_GET['tab'] ) {
			return;
		}

		$message = sprintf( __( '<strong>Note:</strong> This is the <a href="%s" rel="noreferrer noopener">public Greenhouse API</a> (no API key is required). If you have a Greenhouse API key and need to use their private API, please contact us, through the plugin <a href="%s">contact form</a>.', 'gofetch-wpjm' ), 'https://developers.greenhouse.io/job-board.html#list-jobs', gfjwjm_fs()->contact_url() );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
	}

	/**
	 * Greenhouse settings tab.
	 */
	protected function tab_greenhouse() {
		global $goft_wpjm_options;

		$docs = 'https://developers.greenhouse.io/job-board.html#list-jobs';

		$this->all_tabs->tab_sections['greenhouse']['logo'] = array(
			'title' => '',
			'fields' => array(
				array(
					'title'  => '',
					'name'   => '_blank',
					'type'   => 'custom',
					'render' => function() {
						$ats = 'Greenhouse';
						$output =  '<a href="https://www.greenhouse.io/" rel="noreferer noopener"><img class="ats-providers-logo" src="' . esc_url( $this->get_config('logo') ) . '"></a>'
									. sprintf( __( '<p>If you use <a href="https://www.greenhouse.io/" rel="noreferer noopener">%s</a>, you can list your company jobs by filling the info below.</p>', 'gofetch-wpjm' ), $ats )
									. sprintf( __( '<p>if you are not familiar with <em>%s</em>, you can get more information or request a demo <a href="%s" target="_new">here</a>.</p>', 'gofetch-wpjm' ), $ats, 'https://www.greenhouse.io/uk/demo' );
						echo html( 'span class="gofj-custom-render"', $output );
					},
				),
			),
		);

		$this->all_tabs->tab_sections['greenhouse']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Board Token *', 'gofetch-wpjm' ),
					'name'  => 'greenhouse_board_token',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Register for a <a href="%1$s" target="_new">Greenhouse Account</a> to get your <a href="%2$s">board token</a>.', 'gofetch-wpjm' ), 'https://app2.greenhouse.io/jobboard', 'https://planted.zendesk.com/hc/en-us/articles/360024779172-How-to-Find-Your-Greenhouse-Job-Board-Token' ),
					'tip'   => __( 'You need a board token to pull jobs from this provider.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['greenhouse']['company'] = array(
			'title' => __( 'Company', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title'   => __( 'Company Name', 'gofetch-wpjm' ),
					'name'    => 'greenhouse_company_name',
					'type'    => 'text',
					'default' => ucfirst( $goft_wpjm_options->greenhouse_company_name ),
					'desc'    => sprintf( __( 'Your board company name. If empty, jobs will not have a company name assigned.', 'gofetch-wpjm' ) ),
					'tip'     => __( 'The company name assigned to your Greenhouse board token.', 'gofetch-wpjm' ),
				),
				array(
					'title'  => __( 'Company Logo', 'gofetch-wpjm' ),
					'name'   => '_blank',
					'type'   => 'custom',
					'render' => array( $this, 'company_logo_field' ),
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
	 * Enqueues greenhouse in the list of providers.
	 */
	public function providers( $providers ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'greenhouse.io' => array(
				'API' => array(
					'info'      => 'https://developers.greenhouse.io/',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'Board Token'  => 'greenhouse_board_token',
					),
				),
				'website'     => 'https://www.greenhouse.io/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-greenhouse.svg',
				'description' => 'Applicant Tracking System & Recruiting Software.',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://developers.greenhouse.io/',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'board_token' => array( 'board_token' => esc_attr( $goft_wpjm_options->greenhouse_board_token ) ),
					),
					'default' => false,
				),
				'multi_region_match' => 'greenhouse',
				'category' => 'ATS',
				'weight'   => 10,
			),
		);
		return array_merge( $providers, $new_providers );
	}

	/**
	 * Outputs specific greenhouse feed parameter fields.
	 */
	public function feed_builder_fields( $provider ) {

		if ( ! $this->condition( $provider ) ) {
			return;
		}

?>
		<?php $field_name = 'board_token'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Board Token', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'If you need to pull jobs from an alternative board token, add the token here.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: my-other-board-token', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

<?php
	}


	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {
		global $goft_wpjm_options;

		$parts = parse_url( $url );

		$params = array();

		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $params );
			$board_token = $goft_wpjm_options->greenhouse_board_token;

			if ( isset( $params['board_token'] ) && strtolower( $params['board_token'] ) !== strtolower( $board_token ) ) {
				$alt_board_token = $params['board_token'];
				$url = str_ireplace( $board_token, $alt_board_token, $url );
			}
		}

		$api_args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		$api_data = $this->get_api_data( $url, $_xml_data = false, $api_args );

		if ( is_wp_error( $api_data ) || empty( $api_data['jobs'] ) ) {

			if ( ! is_wp_error( $api_data ) ) {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Make sure you\'ve specified a valid board token.', 'gofetch-wpjm' ) );
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
			'id'              => '',
			'absolute_url'    => '',
			'data_compliance' => '',
			'internal_job_id' => '',
			'location'        => '',
			'metadata'        => '',
			'location'        => '',
			'updated_at'      => '',
			'requisition_id'  => '',
			'title'           => '',
			'content'         => '',
			'departments'     => '',
			'offices'         => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];

			$new_item['title'] = sanitize_text_field( $job['title'] );

			if ( $goft_wpjm_options->greenhouse_company_name ) {
				$new_item['company'] = sanitize_text_field( $goft_wpjm_options->greenhouse_company_name );
			}

			if ( $goft_wpjm_options->greenhouse_company_logo ) {
				$image_id = $goft_wpjm_options->greenhouse_company_logo;
				$image_src = wp_get_attachment_image_src( $image_id, apply_filters( 'goft_wpjm_fetch_feed_custom_logo_size', 'large', 'greenhouse' ) );
				$new_item['logo'] = sanitize_text_field( $image_src[0] );
			}

			$new_item['date'] = GoFetch_Importer::get_valid_date( $job['updated_at'], 'api' );

			if ( ! empty( $job['location']['name'] ) ) {
				$new_item['location'] = sanitize_text_field( $job['location']['name'] );
				// Some locations might retrieve 'null' so, remove them.
				$new_item['location'] = str_replace( array( 'null,', 'null' ), '', $new_item['location'] );
			}

			if ( ! empty( $job['departments'] ) ) {
				$new_item['category'] = sanitize_text_field( $job['departments'][0]['name'] );
			}

			$new_item['description'] = GoFetch_Importer::format_description( $job['content'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( $job['absolute_url'] ) );

			$new_item['requisition_id'] = sanitize_text_field( $job['requisition_id'] );
			$new_item['id']             = sanitize_text_field( $job['id'] );

			// Find the item with the most attributes to use as sample.
			if ( count( array_keys( $new_item ) ) > count( array_keys( $sample_item ) ) ) {
				$sample_item                = $new_item;
				$sample_item['description'] = GoFetch_Importer::shortened_description( $job['content'] );
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

		$provider['name'] = 'Job Search | greenhouse';

		return array(
			'provider'    => $provider,
			'items'       => $new_items,
			'sample_item' => $sample_item,
		);
	}

	/**
	 * Unset specific attributes from the sample job.
	 */
	public function sample_item( $item, $provider ) {

		if ( empty( $item['provider_id'] ) || ! $this->condition( $item['provider_id'] ) ) {
			return $item;
		}

		$item['id'] = null; unset( $item['id'] );

		return $item;
	}

	public function form_handler( $tab, $options ) {
		global $goft_wpjm_options;

		if ( isset( $_POST['greenhouse_company_logo'] ) ) {
			$image_id = 0;
			if ( ! empty( $_POST['greenhouse_company_logo'] ) ) {
				$image_id = intval( sanitize_text_field( $_POST['greenhouse_company_logo'] ) );
			}
		}
		$goft_wpjm_options->set( 'greenhouse_company_logo', $image_id );

	}


	/**
	 * HELPERS
	 */


	/**
	 * Custom admin field for the company logo.
	 */
	public function company_logo_field() {
		global $goft_wpjm_options;

		$image_id = $goft_wpjm_options->greenhouse_company_logo;

		$unique_id = sprintf( 'gofj-upl-id-%s', $this->id );

		if ( $image = wp_get_attachment_image_src( $image_id ) ) {

			$field = '<a href="#" class="gofj-upl"><img class="ats-company-logo" src="' . esc_url( $image[0] ) . '" /></a>
				<p class="gofj-rmv"><a href="#">Remove<a/></p>
				<input type="hidden" class="' . esc_attr( $unique_id ) . '" name="greenhouse_company_logo" value="' . intval( $image_id ) . '">';

		} else {

			$field = '<a href="#" class="gofj-upl">Upload ...</a>
				<p style="display:none" class="gofj-rmv"><a href="#">Remove<a/></p>
				<input type="hidden" class="' . esc_attr( $unique_id ) . '" name="greenhouse_company_logo">';

		}

		$field = html( 'span class="gofj-upl-container"', $field );

		$field .= GoFetch_Helper::image_uploader_js();

		return $field;
	}


	/**
	 * Retrieves the board token from an URL.
	 */
	public function get_board_token_from_url( $url ) {
		global $goft_wpjm_options;

		$board_token = $goft_wpjm_options->greenhouse_board_token;

		$parts = parse_url( $url );

		$params = array();

		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $params );
			$board_token = $goft_wpjm_options->greenhouse_board_token;

			if ( isset( $params['board_token'] ) ) {
				$board_token = $params['board_token'];
			}
		}
		return ucfirst( $board_token );
	}


}
new GoFetch_greenhouse_API_Feed_Provider();
