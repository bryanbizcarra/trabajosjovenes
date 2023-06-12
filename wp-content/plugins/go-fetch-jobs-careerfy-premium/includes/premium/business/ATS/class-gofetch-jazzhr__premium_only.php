<?php
/**
 * Importer classes for providers that use an API to provide jobs.
 *
 * Docs: http://www.resumatorapi.com/v1/#!
 *
 * @package GoFetch/Admin/Premium/Business/ATS Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The class for the JazzHR Feed API.
 */
class GoFetch_JazzHR_API_Feed_Provider extends GoFetch_API_Feed_Provider {

	/**
	 * @var The single instance of the class.
	 */
	protected static $_instance = null;

	/**
	 * The application URL.
	 */
	protected static $application_link = 'https://info.jazzhr.com/submit-demo.html?utm_source=partner-WPUno';

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

		$this->id      = 'jazzhr.io';
		$this->api_url = sprintf( 'https://api.resumatorapi.com/v1/jobs/status/open?apikey=%s', esc_attr( $goft_wpjm_options->jazzhr_api_key ) );

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
	}

	/**
	 * Init the jazzhr tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'jazzhr', sprintf( '<span class="gofj-top-partner-tab"><span class="dashicons dashicons-star-empty"></span> %s</span>', __( 'JazzHR', 'gofetch-wpjm' ) ) );
		$this->tab_jazzhr();
	}

	/**
	 * Greenhouse settings tab.
	 */
	protected function tab_jazzhr() {
		global $goft_wpjm_options;

		$docs = 'http://www.resumatorapi.com/v1/#!/jobs/jobs_get';

		$this->all_tabs->tab_sections['jazzhr']['logo'] = array(
			'title' => '',
			'fields' => array(
				array(
					'title'  => '',
					'name'   => '_blank',
					'type'   => 'custom',
					'render' => function() {
						$ats = 'JazzHR';
						$output = '<h2 class="gofj-top-partner"> <span class="dashicons dashicons-star-empty"></span> Top Partner <span class="dashicons-before dashicons-editor-help tip-icon bc-tip" title="Click to read additional info..." data-tooltip="A \'Top Partner\' means better integration and support."></span></h2>';
						$output .=  '<a href="https://www.jazzhr.com/" rel="noreferer noopener"><img class="ats-providers-logo" src="' . esc_url( $this->get_config('logo') ) . '"></a>'
									. sprintf( __( '<p>If you use <a href="https://www.jazzhr.com/" rel="noreferer noopener">%s</a>, you can list your company jobs by filling the info below.</p>', 'gofetch-wpjm' ), $ats )
									. sprintf( __( '<p>if you are not familiar with <em>%s</em>, you can get more information or request a demo <a href="%s" target="_new">here</a>.</p>', 'gofetch-wpjm' ), $ats, self::$application_link );
						//echo html( 'span class="gofj-custom-render"', $output );
						echo '<span class="gofj-custom-render">' . wp_kses_post( $output ) . '</span>';
					},
				),
			),
		);

		$this->all_tabs->tab_sections['jazzhr']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'API Key *', 'gofetch-wpjm' ),
					'name'  => 'jazzhr_api_key',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Your API key can be found in the <a href="%1$s" target="_new">Integrations</a> section of your JazzHR account.', 'gofetch-wpjm' ), 'https://app.jazz.co/app/settings/integrations' ),
					'tip'   => __( 'You need an API key to pull jobs from this provider.', 'gofetch-wpjm' ),
				),
				array(
					'title'   => __( 'Board Subdomain *', 'gofetch-wpjm' ),
					'name'    => 'jazzhr_board_subdomain',
					'type'    => 'text',
					'default' => ucfirst( $goft_wpjm_options->jazzhr_board_subdomain ),
					'desc'    => __( 'Your board subdomain. You can find it on your JazzHR Account page.', 'gofetch-wpjm' ),
					'tip'     => __( 'Your board subdomain is required to send users to the application page.', 'gofetch-wpjm' ),
				),
				array(
					'title'   => __( 'Board Code *', 'gofetch-wpjm' ),
					'name'    => 'jazzhr_board_code',
					'type'    => 'text',
					'extra' => array(
						'class' => 'text-small',
					),
					'default' => ucfirst( $goft_wpjm_options->jazzhr_board_code ),
					'desc'    => sprintf( __( 'Your board code. You can find instructions <a href="%s" rel="noreferrrer noopener">on this page</a>.', 'gofetch-wpjm' ), 'https://help.jazzhr.com/s/article/Integrate-JazzHR-with-LinkedIn-Recruiter-System-Connect-RSC' ),
					'tip'     => __( 'Your board code is required to send users to the application page.', 'gofetch-wpjm' ),
				),

			),
		);

		$this->all_tabs->tab_sections['jazzhr']['company'] = array(
			'title' => __( 'Company', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title'   => __( 'Company Name', 'gofetch-wpjm' ),
					'name'    => 'jazzhr_company_name',
					'type'    => 'text',
					'default' => ucfirst( $goft_wpjm_options->jazzhr_company_name ),
					'desc'    => sprintf( __( 'Your company name. If empty, jobs will not have a company name assigned.', 'gofetch-wpjm' ) ),
					'tip'     => __( 'Your Company name.', 'gofetch-wpjm' ),
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
	 * Enqueues jazzhr in the list of providers.
	 */
	public function providers( $providers ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'jazzhr.io' => array(
				'API' => array(
					'info' => 'http://www.resumatorapi.com/v1/#!/jobs/jobs_get',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'API Key'   => 'jazzhr_api_key',
						'Subdomain' => 'jazzhr_board_subdomain',
					),
				),
				'website'     => 'https://www.jazzhr.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-jazzhr.png',
				'description' => 'Award-Winning Recruiting Software for SMBs.',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://app.jazz.co/',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'         => array( 'title' => '' ),
						'company_name'    => array( 'company_name' => esc_attr( $goft_wpjm_options->jazzhr_company_name ) ),
						'board_subdomain' => array( 'board_subdomain' => esc_attr( $goft_wpjm_options->jazzhr_board_subdomain ) ),
						'board_code'      => array( 'board_code' => esc_attr( $goft_wpjm_options->jazzhr_board_code ) ),
						//'team_id'         => array( 'team_id' => '' ),
						'department'      => array( 'department' => '' ),
						'state' => array( 'state' => array(
							'placeholder' => __( 'e.g: CA, PA', 'gofetch-wpjm' ),
							'default_value' => '',
						) ),
						'city' => array( 'city' => '' ),
					),
					'query_args_sep' => '/',
					'query_args_sep_pos' => 'before',
					'default' => false,
				),
				'multi_region_match' => 'jazzhr',
				'partner' => true,
				'partner_msg' => sprintf( __( '<em>JazzHR</em> is a Partner provider. If you are not familiar with <em>JazzHR</em> you can get more info <a href="%s" target="_blank">here</a>.' ), self::$application_link ),
				'category' => array( ' ' . __( 'Partners', 'gofetch-wpjm' ), 'ATS' ),
				'weight'   => 1,
			),
		);
		return array_merge( $providers, $new_providers );
	}

	/**
	 * Outputs specific jazzhr feed parameter fields.
	 */
	public function feed_builder_fields( $provider ) {

		if ( ! $this->condition( $provider ) ) {
			return;
		}

?>
		<?php $field_name = 'city'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'City', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The job location city.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: los angeles', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'department'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Department', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The job department.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: johndoejobs', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

		<?php $field_name = 'board_subdomain'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Board Subdomain', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'If you need to pull jobs from an alternative board, please add the respective subdomain here.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: ABC123', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'board_code'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Board Code', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'If you need to pull jobs from an alternative board, please add the respective board code here.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: ABC123', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'company_name'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Company Name', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'If you need to pull jobs from an alternative board, please add the company name here.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: My Other Company', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

<?php
	}


	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {
		global $goft_wpjm_options;

		$parts = parse_url( $url );

		$params = array();
/*
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $params );
			$board_token = $goft_wpjm_options->jazzhr_board_token;

			if ( isset( $params['board_token'] ) && strtolower( $params['board_token'] ) !== strtolower( $board_token ) ) {
				$alt_board_token = $params['board_token'];
				$url = str_ireplace( $board_token, $alt_board_token, $url );
			}
		}
*/
		$api_args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		$api_data = $this->get_api_data( $url, $_xml_data = false, $api_args );

		if ( is_wp_error( $api_data ) || empty( $api_data ) ) {

			if ( ! is_wp_error( $api_data ) ) {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Please review your criteria.', 'gofetch-wpjm' ) );
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

		$company_name    = $goft_wpjm_options->jazzhr_company_name;
		$board_subdomain = $goft_wpjm_options->jazzhr_board_subdomain;

		$parts = parse_url( $url );

		if ( ! empty( $parts['path'] ) ) {
			$path_parts = explode( '/', $parts['path'] );

			$pos = array_search( 'company_name', $path_parts );
			$alt_company_name = $path_parts[ $pos + 1 ];

			if ( $alt_company_name && strtolower( $alt_company_name ) !== strtolower( $company_name ) ) {
				$company_name = $alt_company_name;
			}

			$pos = array_search( 'board_subdomain', $path_parts );
			$alt_board_subdomain = $path_parts[ $pos + 1 ];

			if ( strtolower( $alt_board_subdomain ) !== strtolower( $board_subdomain ) ) {
				$board_subdomain = $alt_board_subdomain;
			}

		}

		$new_items = $sample_item = array();

		$defaults = array(
			'id'                 => '',
			//'team_id'            => '',
			'title'              => '',
			'country_id'         => '',
			'city'               => '',
			'state'              => '',
			'zip'                => '',
			'department'         => '',
			'description'        => '',
			'minimum_salary'     => '',
			'maximum_salary'     => '',
			'notes'              => '',
			'original_open_date' => '',
			'type'               => '',
			'board_code'         => '',
			'hiring_lead'        => '',
			'internal_code'      => '',
			'questionnaire'      => '',
			'send_to_job_boards' => '',
		);

		$application_url_placeholder = sprintf( 'https://%s.applytojob.com/apply/', $board_subdomain );
		$application_url_placeholder .= '%s/%s';

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];

			$new_item['title'] = sanitize_text_field( $job['title'] );

			$new_item['company'] = sanitize_text_field( $company_name );

			if ( $goft_wpjm_options->jazzhr_company_logo ) {
				$image_id = $goft_wpjm_options->jazzhr_company_logo;
				$image_src = wp_get_attachment_image_src( $image_id, apply_filters( 'goft_wpjm_fetch_feed_custom_logo_size', 'large', 'jazzhr' ) );
				$new_item['logo'] = sanitize_text_field( $image_src[0] );
			}

			$new_item['date'] = GoFetch_Importer::get_valid_date( $job['original_open_date'], 'api' );

			if ( ! empty( $job['city'] ) ) {
				$new_item['location'] = sanitize_text_field( $job['city'] );
				// Some locations might retrieve 'null' so, remove them.
				$new_item['location'] = str_replace( array( 'null,', 'null' ), '', $new_item['location'] );
			}

			if ( ! empty( $job['department'] ) ) {
				$new_item['category'] = sanitize_text_field( $job['department'] );
			}

			$new_item['description'] = GoFetch_Importer::format_description( $job['description'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( sprintf( $application_url_placeholder, $job['board_code'], sanitize_title( $job['title'] ) ) ) );

			$new_item['team_id']        = sanitize_text_field( $job['team_id'] );
			$new_item['country_id']     = sanitize_text_field( $job['country_id'] );
			$new_item['state']          = sanitize_text_field( $job['state'] );
			$new_item['zip']            = sanitize_text_field( $job['zip'] );
			$new_item['minimum_salary'] = sanitize_text_field( $job['minimum_salary'] );
			$new_item['maximum_salary'] = sanitize_text_field( $job['maximum_salary'] );
			$new_item['hiring_lead']    = sanitize_text_field( $job['hiring_lead'] );
			$new_item['internal_code']  = sanitize_text_field( $job['internal_code'] );

			$new_item['board_code'] = sanitize_text_field( $job['board_code'] );

			$new_item['id'] = sanitize_text_field( $job['id'] );

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

		$provider['name'] = 'Job Search | jazzhr';

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

		if ( isset( $_POST['jazzhr_company_logo'] ) ) {
			$image_id = 0;
			if ( ! empty( $_POST['jazzhr_company_logo'] ) ) {
				$image_id = intval( sanitize_text_field( $_POST['jazzhr_company_logo'] ) );
			}
		}
		$goft_wpjm_options->set( 'jazzhr_company_logo', $image_id );

	}


	/**
	 * HELPERS
	 */


	/**
	 * Custom admin field for the company logo.
	 */
	public function company_logo_field() {
		global $goft_wpjm_options;

		$image_id = $goft_wpjm_options->jazzhr_company_logo;

		$field_name = 'jazzhr_company_logo';

		$unique_id = sprintf( 'gofj-upl-id-%s', $this->id );

		if ( $image = wp_get_attachment_image_src( $image_id ) ) {

			$field = '<a href="#" class="gofj-upl"><img class="ats-company-logo" src="' . esc_url( $image[0] ) . '" /></a>
				<p class="gofj-rmv"><a href="#">Remove<a/></p>
				<input type="hidden" class="' . esc_attr( $unique_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . intval( $image_id ) . '">';

		} else {

			$field = '<a href="#" class="gofj-upl">Upload ...</a>
				<p style="display:none" class="gofj-rmv"><a href="#">Remove<a/></p>
				<input type="hidden" class="' . esc_attr( $unique_id ) . '" name="' . esc_attr( $field_name ) . '">';

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

		$board_token = $goft_wpjm_options->jazzhr_board_token;

		$parts = parse_url( $url );

		$params = array();

		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $params );
			$board_token = $goft_wpjm_options->jazzhr_board_token;

			if ( isset( $params['board_token'] ) ) {
				$board_token = $params['board_token'];
			}
		}
		return ucfirst( $board_token );
	}


}
new GoFetch_JazzHR_API_Feed_Provider();
