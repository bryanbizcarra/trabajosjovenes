<?php
/**
 * Importer classes for providers that use an API to provide jobs.
 *
 * Docs: https://jooble.org/api
 *
 * @package GoFetch/Admin/Premium/Professional/API Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The class for the Jooble Feed API.
 */
class GoFetch_Jooble_API_Feed_Provider extends GoFetch_API_Feed_Provider {

	/**
	 * Custom API args.
	 */
	protected $api_args = array();

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

		$this->id      = 'jooble.org/api';
		$this->api_url = sprintf( '%1$s/api/%2$s', esc_url( $goft_wpjm_options->jooble_feed_default_domain ), esc_attr( $goft_wpjm_options->jooble_api_key ) );

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-providers', array( $this, 'tabs' ), 75 );
		add_filter( 'goft_wpjm_providers', array( $this, 'config' ), 15 );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );

		// Frontend.
		add_action( 'goft_wpjm_single_goft_job', array( $this, 'single_job_page_hooks' ) );

		add_action( 'goft_no_robots', array( $this, 'maybe_no_robots' ), 10, 2 );
	}

	/**
	 * Init the Jooble tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'jooble', __( 'Jooble', 'gofetch-wpjm' ) );
		$this->tab_jooble();
	}

	/**
	 * Retrieves the markup for the grouped regions.
	 */
	public function regions_group_dropdown() {
		global $goft_wpjm_options;

		$grouped_region_domains = $this->get_config( 'region_domains' );

		$default_domain = $goft_wpjm_options->jooble_feed_default_domain;

		$optgroup_html = '';

		foreach ( $grouped_region_domains as $group => $items ) {
			$items_html = '';
			foreach ( $items as $key => $value ) {
				$selected = selected( $key === $default_domain, true, false );
				$items_html .= html( 'option ' . $selected . ' value="' . esc_attr( $key ) . '"', $value );
			}
			$optgroup_html .= html( 'optgroup label="' . esc_attr( $group ) . '"', $items_html );
		}

		$select = html( 'select name="jooble_feed_default_domain" class="gofj-multiselect"', $optgroup_html );

		return $select;
	}

	/**
	 * Jooble settings tab.
	 */
	protected function tab_jooble() {

		$info_url = 'https://jooble.org/api/about';

		$this->all_tabs->tab_sections['jooble']['logo'] = array(
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

		$this->all_tabs->tab_sections['jooble']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Country', 'gofetch-wpjm' ),
					'name'  => 'jooble_feed_default_domain',
					'type'  => 'custom',
					'render'  => array( $this, 'regions_group_dropdown' ),
					'tip' => __( 'The default domain to use.', 'gofetch-wpjm' ) .
							'<br/><br/>' . __( '<code>IMPORTANT</code> Each domain requires its own API key, Please select the country valid for your API key.', 'gofetch-wpjm' ) .
							'<br/><br/>' . __( 'If you have different API keys for several countries, please manually specify them and the respective API key, directly on the import page. The one you specify here will be used as default.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'API Key *', 'gofetch-wpjm' ),
					'name'  => 'jooble_api_key',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Sign up for a free <a href="%1$s" target="_new">Jooble API Key</a>', 'gofetch-wpjm' ), esc_url( $info_url ) ),
					'tip'   => __( 'You need an API key in order to pull jobs from Jooble.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['jooble']['defaults'] = array(
			'title' => __( 'Feed Defaults', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Radius', 'gofetch-wpjm' ),
					'name'  => 'jooble_feed_default_radius',
					'type'  => 'select',
					'choices' => array(
						0  => 0,
						5  => 5,
						10 => 10,
						15 => 15,
						25 => 25,
						50 => 50,
					),
					'tip' => __( 'Distance from search location (in miles)', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Min. Salary', 'gofetch-wpjm' ),
					'name'  => 'jooble_feed_default_salary_from',
					'type'  => 'text',
					'extra' => array(
						'style' => 'width: 80px',
					),
					'desc' => __( '(Annual Salary)', 'gofetch-wpjm' ),
					'tip' => __( 'Only pull jobs that pay more than the salary you specify here (only numeric values without currency). Leave empty for any.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Max. Salary', 'gofetch-wpjm' ),
					'name'  => 'jooble_feed_default_salary_to',
					'type'  => 'text',
					'extra' => array(
						'style' => 'width: 80px',
					),
					'desc' => __( '(Annual Salary)', 'gofetch-wpjm' ),
					'tip' => __( 'Only pull jobs that pay up to the salary you specify here (only numeric values without currency). Leave empty for any.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Search Mode', 'gofetch-wpjm' ),
					'name'  => 'jooble_feed_default_search_mode',
					'type'  => 'select',
					'choices' => array(
						'1' => __( 'Recommended job listings', 'gofetch-wpjm' ),
						'3' => __( 'All Job Listings (slow - not recommended)', 'gofetch-wpjm' ),
					),
					'tip' => __( 'the job listings search mode.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Limit', 'gofetch-wpjm' ),
					'name'  => 'jooble_feed_default_limit',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Maximum number of results returned per query.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['jooble']['jobs'] = array(
			'title' => __( 'Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Block Search Indexing', 'gofetch-wpjm' ),
					'name'  => 'jooble_block_search_indexing',
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
	 * Enqueues Jooble in the list of providers.
	 */
	public function config( $providers = array() ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'jooble.org/api' => array(
				'API' => array(
					'info'      => 'https://jooble.com/services/api-new/documentation.php',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'Publisher ID' => 'jooble_api_key',
					),
				),
				'website'     => 'https://jooble.org/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-jooble.svg',
				'description' => 'Jooble is the place where you can search jobs across the whole Internet.',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://jooble.org/jobs',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'  => array( 'keywords' => '' ),
						'location' => array( 'location' => '' ),
						'limit'    => array( 'limit'  => esc_attr( $goft_wpjm_options->jooble_feed_default_limit ) ),
						'radius'   => array( 'radius' => esc_attr( $goft_wpjm_options->jooble_feed_default_radius ) ),
						// Custom.
						'search_mode'  => array( 'search_mode'  => esc_attr( $goft_wpjm_options->jooble_feed_default_search_mode ) ),
						'salary_from' => array( 'salary_from' => esc_attr( $goft_wpjm_options->jooble_feed_default_salary_from ) ),
						'salary_to'   => array( 'salary_to' => esc_attr( $goft_wpjm_options->jooble_feed_default_salary_to ) ),
					),
					'pagination' => array(
						'params'  => array(
							'page'  => 'page',
							'limit' => 'limit',
						),
						'type'    => 'page',
						'results' => 20,
					),
					'scraping' => false,
					'notes' => 'Uses JS scrape blocker',
					'default' => false,
				),
				'multi_region_match' => 'jooble',
				'region_domains' => array(
					'Europe' => array(
						'https://ua.jooble.org' =>'Работа в Украине',
						'https://by.jooble.org' =>'Работа в Беларуси',
						'https://ru.jooble.org' =>'Работа в России',
						'https://pl.jooble.org' =>'Praca w Polsce',
						'https://cz.jooble.org' =>'Práce Česká republika',
						'https://ro.jooble.org' =>'Locuri de muncă în România',
						'https://sk.jooble.org' =>'Práca Slovensko',
						'https://rs.jooble.org' =>'Posao Srbija',
						'https://hu.jooble.org' =>'Állás Magyarországon',
						'https://gr.jooble.org' =>'Εργασία Ελλάδα',
						'https://fr.jooble.org' =>'Emploi France',
						'https://fi.jooble.org' =>'Avoimet työpaikat Suomi',
						'https://no.jooble.org' =>'Jobb Norge',
						'https://es.jooble.org' =>'Trabajo en España',
						'https://pt.jooble.org' =>'Empregos Portugal',
						'https://ch.jooble.org' =>'Jobs Schweiz',
						'https://se.jooble.org' =>'Jobb i Sverige',
						'https://dk.jooble.org' =>'Job i Danmark',
						'https://be.jooble.org' =>'Emploi Belgique',
						'https://de.jooble.org' =>'Stellenangebote Deutschland',
						'https://at.jooble.org' =>'Stellenangebote Österreich',
						'https://nl.jooble.org' =>'Vacatures Nederland',
						'https://it.jooble.org' =>'Lavoro Italia',
						'https://uk.jooble.org' =>'Jobs United Kingdom',
						'https://ie.jooble.org' =>'Jobs Ireland',
						'https://tr.jooble.org' =>'Türkiye is ilanlari',
						'https://ba.jooble.org' =>'Posao u Bosni i Hercegovini',
						'https://hr.jooble.org' =>'Posao u Hrvatskoj',
						'https://bg.jooble.org' =>'Работа в България',
					),
					'Asia' => array(
						'https://kz.jooble.org' =>'Работа в Казахстане',
						'https://in.jooble.org' =>'Jobs in India',
						'https://sg.jooble.org' =>'Jobs in Singapore',
						'https://ph.jooble.org' =>'Jobs in Philippines',
						'https://pk.jooble.org' =>'Jobs in Pakistan',
						'https://th.jooble.org' =>'งานประเทศไทย',
						'https://jp.jooble.org' =>'日本求人',
						'https://tw.jooble.org' =>'工作台湾',
						'https://hk.jooble.org' =>'香港職位',
						'https://id.jooble.org' =>'Lowongan kerja Indonesia',
						'https://kr.jooble.org' =>'채용 대한민국',
						'https://cn.jooble.org' =>'中国职位',
						'https://az.jooble.org' =>'Работа в Азербайджане',
						'https://my.jooble.org' =>'Jobs in Malaysia',
						'https://ae.jooble.org' =>'Jobs in UAE',
						'https://sa.jooble.org' =>'Jobs in Saudi Arabia',
						'https://qa.jooble.org' =>'Jobs in Qatar',
						'https://kw.jooble.org' =>'Jobs in Kuwait',
						'https://bh.jooble.org' =>'Jobs in Bahrain',
						'https://uz.jooble.org' =>'Работа в Узбекистане',
					),
					'North And South America' => array(
						'https://jooble.org' =>'Jobs in US',
						'https://mx.jooble.org' =>'Trabajo en México',
						'https://ar.jooble.org' =>'Trabajo en Argentina',
						'https://cl.jooble.org' =>'Trabajo Chile',
						'https://co.jooble.org' =>'Trabajo en Colombia',
						'https://pe.jooble.org' =>'Trabajo Perú',
						'https://ve.jooble.org' =>'Trabajo en Venezuela',
						'https://do.jooble.org' =>'Trabajo en Dominicana',
						'https://cr.jooble.org' =>'Trabajo en Costa Rica',
						'https://uy.jooble.org' =>'Trabajo en Uruguay',
						'https://sv.jooble.org' =>'Trabajo en El Salvador',
						'https://cu.jooble.org' =>'Trabajo en Cuba',
						'https://pr.jooble.org' =>'Trabajo en Puerto Rico',
						'https://br.jooble.org' =>'Vagas de empregos Brasil',
						'https://ca.jooble.org' =>'Jobs in Canada',
						'https://ec.jooble.org' =>'Trabajo Ecuador',
					),
					'Oceania' => array(
						'https://nz.jooble.org' =>'Jobs in New Zealand',
						'https://au.jooble.org' =>'Jobs Australia',
					),
					'Africa' => array(
						'https://ng.jooble.org' =>'Jobs in Nigeria',
						'https://za.jooble.org' =>'Jobs in South Africa',
						'https://eg.jooble.org' =>'Jobs in Egypt',
						'https://ma.jooble.org' =>'Jobs in Morocco',
					),
				),
				'region_groups' => true,
				'region_option' => 'jooble_feed_default_domain',
				'region_default' => 'https://jooble.org',
				'multi-region' => sprintf( __( 'To see all available countries visit <a href="%s">Jooble\'s site</a>.', 'gofetch-wpjm' ), 'https://jooble.org' ) .
								'<br/><br/>' . sprintf( __( 'To apply these instructions to other country you can usually replace replace this URL part <code>%1$s</code> with the respective country URL you\'re interested with.', 'gofetch-wpjm' ), 'https://jooble.org/api/' ) .
								'<br/><br/>' . sprintf( __( '<strong>e.g:</strong> For <em>Portugal</em> you would use <code>%1$s</code>[...]', 'gofetch-wpjm' ), 'https://pt.jooble.org/api/' ) .
									'<br><br/>' . __( '<em>Note:</em> If replacing the domain part does not work for a specific country please refer to the provider site to check the exact domain used for their feeds.', 'gofetch-wpjm' ),
				'category' => 'API',
				'weight'   => 10,
			),
		);
		return array_merge( $providers, $new_providers );
	}

	/**
	 * Outputs specific Jooble feed parameter fields.
	 */
	public function feed_builder_fields( $provider ) {

		if ( ! $this->condition( $provider ) ) {
			return;
		}
?>
		<?php $field_name = 'search_mode'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Search Mode', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Job listings search mode.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>">
				<option value="1" selected><?php esc_attr_e( 'Recommended Job Listings', 'gofetch-wpjm' ); ?></option>
				<option value="3"><?php esc_attr_e( 'All Job Listings (slow - not recomended)', 'gofetch-wpjm' ); ?></option>
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

<?php
	}

	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {

		$parts = parse_url( $url );

		$params = array();

		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $params );
			$params = json_encode( $params );
		}

		$api_args = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body' => $params,
		);


		$api_data = $this->get_api_data( $url, $_xml_data = false, $api_args );

		$paginated_results = ( ! empty( $this->provider['feed']['pagination'] ) && in_array( $this->provider['feed']['pagination']['params']['page'], array_keys( $params ) ) );

		if ( ! $paginated_results && ( is_wp_error( $api_data ) || empty( $api_data['jobs'] ) ) ) {

			if ( ! is_wp_error( $api_data ) ) {
				return new WP_Error( 'no_jobs_found', __( 'No jobs found. Make sure you\'ve specified \'Keyword\' and that your API key is valid for the selected country.', 'gofetch-wpjm' ) );
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
			'title'    => '',
			'location' => '',
			'snippet'  => '',
			'salary'   => '',
			'source'   => '',
			'type'     => '',
			'link'     => '',
			'company'  => '',
			'updated'  => '',
			'id'       => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['title'] );
			$new_item['date']        = current_time( 'mysql' );
			$new_item['location']    = sanitize_text_field( $job['location'] );

			// Some locations might retrieve 'null' so, remove them.
			$new_item['location'] = str_replace( array( 'null,', 'null' ), '', $new_item['location'] );

			$new_item['salary']  = sanitize_text_field( $job['salary'] );
			$new_item['source']  = sanitize_text_field( $job['source'] );
			$new_item['type']    = sanitize_text_field( $job['type'] );
			$new_item['company'] = sanitize_text_field( $job['company'] );

			$new_item['logo'] = ! empty( $job['logo'] ) ? sanitize_text_field( $job['logo'] ) : '';

			$new_item['description'] = GoFetch_Importer::format_description( $job['snippet'] );
			$new_item['link']        = esc_url_raw( html_entity_decode( $job['link'] ) );
			$new_item['id']     = sanitize_text_field( $job['id'] );

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

		$provider['name'] = 'Job Search | Jooble';

		return array(
			'provider'    => $provider,
			'items'       => $new_items,
			'sample_item' => $sample_item,
		);
	}

	/**
	 * Set specific meta from Jooble.
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

		if ( $goft_wpjm_options->jooble_block_search_indexing ) {
			$robots['noindex'] = true;
		}
		return $robots;
	}

}
new GoFetch_Jooble_API_Feed_Provider();
