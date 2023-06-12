<?php
/**
 * Importer classes for providers that use an API to provide jobs.
 *
 * Docs: https://docs.recruitee.com/reference
 *
 * @package GoFetch/Admin/Premium/Business/ATS Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The class for the recruitee Feed API.
 */
class GoFetch_recruitee_API_Feed_Provider extends GoFetch_API_Feed_Provider {

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

		$this->id      = 'api.recruitee.com';

		// Public API.
		$this->api_url = sprintf( 'https://%1$s.recruitee.com/api/offers/', esc_attr( strtolower( $goft_wpjm_options->recruitee_subdomain ) ) );

		// Private API.
		//this->api_url = sprintf( 'https://api.recruitee.com/c/%1$s/offers', esc_attr( strtolower( $goft_wpjm_options->recruitee_subdomain ) ) );

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
	 * Init the recruitee tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'recruitee', __( 'Recruitee', 'gofetch-wpjm' ) );
		$this->tab_recruitee();
	}

	/**
	 * Display a custom notice.
	 */
	public function display_notice() {
		$class = 'notice notice-warning';

		if ( ! isset( $_GET['tab'] ) || 'recruitee' !== $_GET['tab'] ) {
			return;
		}

		$message = sprintf( __( '<strong>Note:</strong> This is the <a href="%s" rel="noreferrer noopener">public Recruitee API</a> (no API key token is required). If you have a Recruitee API token and need to use their private API, please contact us, through the plugin <a href="%s">contact form</a>.', 'gofetch-wpjm' ), 'https://docs.recruitee.com/reference#intro-to-public-api', gfjwjm_fs()->contact_url() );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
	}

	/**
	 * Greenhouse settings tab.
	 */
	protected function tab_recruitee() {
		global $goft_wpjm_options;

		$docs = 'https://docs.recruitee.com/reference#offers-1';

		$this->all_tabs->tab_sections['recruitee']['logo'] = array(
			'title' => '',
			'fields' => array(
				array(
					'title'  => '',
					'name'   => '_blank',
					'type'   => 'custom',
					'render' => function() {
						$ats = 'Recruitee';
						$output =  '<a href="https://recruitee.com/" rel="noreferer noopener"><img class="ats-providers-logo" src="' . esc_url( $this->get_config('logo') ) . '"></a>'
									. sprintf( __( '<p>If you use <a href="https://recruitee.com/" rel="noreferer noopener">%s</a>, you can list your company jobs by filling the info below.</p>', 'gofetch-wpjm' ), $ats )
									. sprintf( __( '<p>if you are not familiar with <em>%s</em>, you can get more information or request a demo <a href="%s" target="_new">here</a>.</p>', 'gofetch-wpjm' ), $ats, 'https://recruitee.com/demo' );
						echo html( 'span class="gofj-custom-render"', $output );

					},
				),
			),
		);

		$this->all_tabs->tab_sections['recruitee']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				/*
				array(
					'title' => __( 'API Token', 'gofetch-wpjm' ),
					'name'  => 'recruitee_api_token',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Optional. Register for a <a href="%1$s" target="_new">Recruitee Account</a> to get your <a href="%2$s">API Token</a>.', 'gofetch-wpjm' ), 'https://app2.recruitee.io/jobboard', 'https://planted.zendesk.com/hc/en-us/articles/360024779172-How-to-Find-Your-Greenhouse-Job-Board-Token' ),
					'tip'   => __( 'The API token provides access to the private API, which returns richer jobs metatada.', 'gofetch-wpjm' ),
				),*/
				array(
					'title' => __( 'Company subomain *', 'gofetch-wpjm' ),
					'name'  => 'recruitee_subdomain',
					'type'  => 'text',
					'desc'  => __( 'The name that is displayed on the Recruitee Careers site.', 'gofetch-wpjm' ),
					'tip'   => __( 'You need a board token to pull jobs from this provider.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['recruitee']['company'] = array(
			'title' => __( 'Company', 'gofetch-wpjm' ),
			'fields' => array(
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
/*
		$this->all_tabs->tab_sections['recruitee']['jobs'] = array(
			'title' => __( 'Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title'   => __( 'Statuses', 'gofetch-wpjm' ),
					'name'    => 'recruitee_job_statuses',
					'type'    => 'select',
					'choices' => array(
						'publish' => __( 'Publish', 'gofetch-wpjm' ),
						'all'     => __( 'All', 'gofetch-wpjm' ),
					),
					'default' => ucfirst( $goft_wpjm_options->recruitee_job_statuses ),
					'tip'     => __( 'Choose if you want to import ALL jobs, or only \'Published\' jobs.', 'gofetch-wpjm' ),
				),
			),
		);*/
	}

	/**
	 * Enqueues recruitee in the list of providers.
	 */
	public function providers( $providers ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'api.recruitee.com' => array(
				'API' => array(
					'info'      => 'https://docs.recruitee.com/reference',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'Board Token'  => 'recruitee_subdomain',
					),
				),
				'website'     => 'https://www.recruitee.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-recruitee.png',
				'description' => ' Recruitment Software - Talent Acquisition Platform',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://docs.recruitee.com/reference',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'subdomain'   => array( 'subdomain' => esc_attr( $goft_wpjm_options->recruitee_subdomain ) ),
					),
					'default' => false,
				),
				'multi_region_match' => 'recruitee',
				'category' => 'ATS',
				'weight'   => 10,
			),
		);

		$query_args['private'] = array(
			'position'    => array( 'position' => '' ),
			'status'      => array( 'status' => 'published' ),
			'location'    => array( 'location' => '' ),
			'postal_code' => array( 'postal_code' => '' ),
			'state_code'  => array( 'state_code' => '' ),
		);

		$query_args['public'] = array(
			'department' => array( 'department' => '' ),
			'tag'        => array( 'tag' => '' ),
		);

		$more_query_args = $query_args['public'];

		$new_providers['api.recruitee.com']['feed']['query_args'] = array_merge( $new_providers['api.recruitee.com']['feed']['query_args'], $more_query_args );

		return array_merge( $providers, $new_providers );
	}

	/**
	 * Outputs specific recruitee feed parameter fields.
	 */
	public function feed_builder_fields( $provider ) {

		if ( ! $this->condition( $provider ) ) {
			return;
		}

?>
		<?php $field_name = 'subdomain'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-latlong"><strong><?php _e( 'Subdomain', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'If you need to pull jobs from an alternative sudomain, add it here.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: my-other-sudbomain', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php if ( false ) : ?>

			<?php $field_name = 'position'; ?>
			<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
				<label for="feed-latlong"><strong><?php _e( 'Position', 'gofetch-wpjm' ); ?></strong>
					<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Position of the offer in the web app, on the job list.', 'gofetch-wpjm' ) ); ?>"></span></span>
				</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
				<input type="text" class="regular-text" style="width: 60px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 1', 'gofetch-wpjm' ); ?>">
				<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
			</p>

			<?php $field_name = 'status'; ?>
			<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
				<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Status', 'gofetch-wpjm' ); ?></strong>
					<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Status of the job. Possible values: draft, internal, published, closed, archived.', 'gofetch-wpjm' ) ); ?>"></span></span>
				</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
				<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>">
					<option value="draft">Draft</option>
					<option value="internal">Internal</option>
					<option value="published">Published</option>
					<option value="closed">Closed</option>
					<option value="archived">Archived</option>
				</select>
				<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
			</p>

			<div class="clear"></div>

			<?php $field_name = 'postal_code'; ?>
			<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
				<label for="feed-latlong"><strong><?php _e( 'Postal Code', 'gofetch-wpjm' ); ?></strong>
					<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Postal code of the offer location.', 'gofetch-wpjm' ) ); ?>"></span></span>
				</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
				<input type="text" class="regular-text" style="width: 150px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 90210', 'gofetch-wpjm' ); ?>">
				<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
			</p>

			<?php $field_name = 'state_code'; ?>
			<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
				<label for="feed-latlong"><strong><?php _e( 'State Code', 'gofetch-wpjm' ); ?></strong>
					<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'State/Region field in the offer settings.', 'gofetch-wpjm' ) ); ?>"></span></span>
				</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
				<input type="text" class="regular-text" style="width: 80px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: CA', 'gofetch-wpjm' ); ?>">
				<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
			</p>

		<?php else : ?>

			<div class="clear"></div>

			<?php $field_name = 'department'; ?>
			<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
				<label for="feed-latlong"><strong><?php _e( 'Department', 'gofetch-wpjm' ); ?></strong>
					<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Filters job offers by department name.', 'gofetch-wpjm' ) ); ?>"></span></span>
				</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
				<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: marketing', 'gofetch-wpjm' ); ?>">
				<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
			</p>

			<?php $field_name = 'tag'; ?>
			<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
				<label for="feed-latlong"><strong><?php _e( 'Tag', 'gofetch-wpjm' ); ?></strong>
					<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Filters job offers by tag name.', 'gofetch-wpjm' ) ); ?>"></span></span>
				</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
				<input type="text" class="regular-text" style="width: 250px" style name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: ads', 'gofetch-wpjm' ); ?>">
				<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
			</p>

		<?php endif;
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
			$subdomain = $goft_wpjm_options->recruitee_subdomain;

			if ( isset( $params['subdomain'] ) && strtolower( $params['subdomain'] ) !== strtolower( $subdomain ) ) {
				$alt_subdomain = $params['subdomain'];
				$url = str_ireplace( $subdomain, $alt_subdomain, $url );
			}
		}

		$api_args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		$api_data = $this->get_api_data( $url, $_xml_data = false, $api_args );

		if ( is_wp_error( $api_data ) || empty( $api_data['offers'] ) ) {

			if ( ! is_wp_error( $api_data ) ) {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Make sure you\'ve specified a valid company subdomain.', 'gofetch-wpjm' ) );
			}
			return $api_data;
		}
		return $api_data['offers'];
	}

	/**
	 * Fetch items from the API feed.
	 */
	public function fetch_feed_items( $items, $url, $provider ) {
		global $goft_wpjm_options;

		$new_items = $sample_item = array();

		$defaults = array(
			'id'                   => '',
			'country_code'         => '',
			'postal_code'          => '',
			'min_hours'            => '',
			'max_hours'            => '',
			'title'                => '',
			'description'          => '',
			'requirements'         => '',
			'location'             => '',
			'city'                 => '',
			'country'              => '',
			'careers_apply_url'    => '',
			'company_name'         => '',
			'employment_type_code' => '',
			'category_code'        => '',
			'published_at'         => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];

			$new_item['title'] = sanitize_text_field( $job['title'] );

			$new_item['company'] = sanitize_text_field( $job['company_name'] );

			if ( $goft_wpjm_options->recruitee_company_logo ) {
				$image_id = $goft_wpjm_options->recruitee_company_logo;
				$image_src = wp_get_attachment_image_src( $image_id, apply_filters( 'goft_wpjm_fetch_feed_custom_logo_size', 'large', 'recruitee' ) );
				$new_item['logo'] = sanitize_text_field( $image_src[0] );
			}

			$new_item['date'] = GoFetch_Importer::get_valid_date( $job['published_at'], 'api' );

			$new_item['location'] = sanitize_text_field( $job['location'] );

			// Some locations might retrieve 'null' so, remove them.
			$new_item['location'] = str_replace( array( 'null,', 'null' ), '', $new_item['location'] );

			$new_item['category'] = sanitize_text_field( $job['category_code'] );
			$new_item['type']     = sanitize_text_field( $job['employment_type_code'] );

			$new_item['description'] = GoFetch_Importer::format_description( $job['description'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( $job['careers_apply_url'] ) );

			$new_item['requirements'] = GoFetch_Importer::format_description( $job['requirements'] );
			$new_item['city']         = sanitize_text_field( $job['city'] );
			$new_item['country_code'] = sanitize_text_field( $job['country_code'] );
			$new_item['postal_code']  = sanitize_text_field( $job['postal_code'] );
			$new_item['min_hours']    = sanitize_text_field( $job['min_hours'] );
			$new_item['max_hours']    = sanitize_text_field( $job['max_hours'] );

			$new_item['id'] = sanitize_text_field( $job['id'] );

			// Find the item with the most attributes to use as sample.
			if ( count( array_keys( $new_item ) ) > count( array_keys( $sample_item ) ) ) {
				$sample_item                = $new_item;
				$sample_item['description'] = GoFetch_Importer::shortened_description( $job['description'] );
				$sample_item['requirements'] = GoFetch_Importer::shortened_description( $job['requirements'] );
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

		$provider['name'] = 'Job Search | recruitee';

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

		if ( isset( $_POST['recruitee_company_logo'] ) ) {
			$image_id = 0;
			if ( ! empty( $_POST['recruitee_company_logo'] ) ) {
				$image_id = intval( sanitize_text_field( $_POST['recruitee_company_logo'] ) );
			}
		}
		$goft_wpjm_options->set( 'recruitee_company_logo', $image_id );

	}


	/**
	 * HELPERS
	 */


	/*
	 * Custom admin field for the company logo.
	 */
	public function company_logo_field() {
		global $goft_wpjm_options;

		$image_id = $goft_wpjm_options->recruitee_company_logo;

		$unique_id = sprintf( 'gofj-upl-id-%s', $this->id );

		if ( $image = wp_get_attachment_image_src( $image_id ) ) {

			$field = '<a href="#" class="gofj-upl"><img class="ats-company-logo" src="' . esc_url( $image[0] ) . '" /></a>
				<p class="gofj-rmv"><a href="#">Remove<a/></p>
				<input type="hidden" class="' . esc_attr( $unique_id ) . '" name="recruitee_company_logo" value="' . intval( $image_id ) . '">';

		} else {

			$field = '<a href="#" class="gofj-upl">Upload ...</a>
				<p style="display:none" class="gofj-rmv"><a href="#">Remove<a/></p>
				<input type="hidden" class="' . esc_attr( $unique_id ) . '" name="recruitee_company_logo">';

		}

		$field = html( 'span class="gofj-upl-container"', $field );

		$field .= GoFetch_Helper::image_uploader_js();

		return $field;
	}

}
new GoFetch_recruitee_API_Feed_Provider();
