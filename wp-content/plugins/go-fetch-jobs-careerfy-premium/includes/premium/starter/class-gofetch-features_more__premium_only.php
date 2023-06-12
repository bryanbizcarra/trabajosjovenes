<?php
/**
 * Loads advanced premium features not available on the free version.
 *
 * @package GoFetchJobs/Premium/Starter/Features
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class GoFetch_Premium_Starter_More_Features {

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

	function __construct() {
		$this->init_hooks();
	}

	public function init_hooks() {
		add_filter( 'goft_wpjm_settings', array( $this, 'pro_settings' ), 10, 2 );
		add_filter( 'goft_wpjm_settings_taxonomies', array( $this, 'output_tax_settings' ) );
		add_filter( 'goft_wpjm_providers', array( $this, 'providers' ) );
		add_filter( 'goft_wpjm_prepare_item', array( __CLASS__, 'geocode_item' ), 10, 2 );
	}

	/**
	 * Output specific PRO settings by calling the related setting callback.
	 */
	public function pro_settings( $fields, $type = '' ) {

		if ( $type && method_exists( $this, 'output_settings_' . $type ) ) {
			$callback = 'output_settings_' . $type;
			return call_user_func( array( $this, $callback ), $fields );
		}
		return $fields;
	}

	/**
	 * Outputs the affiliate parameter.
	 */
	protected function output_settings_monetize( $fields ) {

		$fields[] = array(
			'title'  => __( 'URL Parameters', 'gofetch-wpjm' ),
			'type'  => 'text',
			'name'  => 'source[args]',
			'extra' => array(
				'class'       => 'regular-text goft_wpjm-monetize',
				'placeholder' => 'e.g: affID=123, publisher=123',
				'section'     => 'monetize',
			),
			'tip'   => __( 'If you have a publisher/partner/affiliate ID or any other query URL argument you wish to add to external links add them to this field using the format <code>key=value [,key=value]</code>.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( 'The publisher/partner/affiliate ID varies from site to site so make sure you check the site source help pages to find the correct parameter <code>(e.g: publisher=123, pshid=123, pid=123)</code>.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( 'These arguments will be added to each external link.', 'gofetch-wpjm' ),
			'value' => ( ! empty( $_POST['source[args]'] ) ? sanitize_text_field( $_POST['source[args]'] ) : '' ),
			'tr'    => 'temp-tr-hide tr-monetize tr-advanced',
		);

		return $fields;
	}

	/**
	 * Outputs the additional taxonomies parameters.
	 */
	public function output_tax_settings( $fields ) {

		$choices = array(
			'multiple' => __( 'Yes (Multi Terms Match)', 'gofetch-wpjm' ),
			'single'   => __( 'Yes (Single Term Match)', 'gofetch-wpjm' ),
		);

		$choices = apply_filters( 'goft_wpjm_tax_settings_choices', $choices );

		$choices[''] = __( 'No', 'gofetch-wpjm' );

		$fields[] = array(
			'title'    => __( 'Smart Assign?', 'gofetch-wpjm' ),
			'type'     => 'select',
			'name'     => 'smart_tax_input',
			'desc'     => __( 'Choose \'Yes\' to let the import process choose the best term(s) for each job.', 'gofetch-wpjm' ),
			'tip'      => __( 'This option is very useful on RSS feeds that can contain jobs for multiple job types/categories. When enabled, the import process will analyze the content and will try to assign the best term(s) for each job.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( 'This is done by matching your existing job types/job categories terms with the content/title for each job being imported.', 'gofetch-wpjm' ) .
						' ' . __( 'If no valid matches are found, jobs will default to the terms you\'ve specified previously.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( '<strong>Options:</strong>', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( '<code>No</code> Do not use term matching. Blindly assign the previously specified terms to each imported job.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( '<code>Multiple Terms Match</code> Allow assigning multiple terms if there are multiple matches.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( 'As an example, consider you have the following job categories created: <em>Writer</em> and <em>Designer</em>. If the job being imported contains those terms, both terms will be assigned to the job.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( '<code>Single Terms Match</code> Assign only the first matched term.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( 'As an example, consider you have the following job categories created: <em>Writer</em> and <em>Designer</em>. If the job being imported contains those terms, only the first term match will be assigned to the job.', 'gofetch-wpjm' ),
			'selected' => ( ! empty( $_POST['smart_tax_input'] ) ? sanitize_text_field( $_POST['smart_tax_input'] ) : '' ),
			'choices'  => $choices,
			'default' => '',
			'tr'      => 'temp-tr-hide tr-smart-assign tr-advanced tr-advanced-hide',
		);
		return $fields;
	}

	/**
	 * Outputs the date interval settings.
	 */
	protected function output_settings_filter( $fields ) {

		$new_fields = array(
			array(
				'title'  => __( 'Negative Keywords', 'gofetch-wpjm' ),
				'type'  => 'text',
				'name'  => 'keywords_exclude',
				'extra' => array(
					'class'       => 'large-text',
					'placeholder' => 'e.g: sales manager, marketing assistant',
				),
				'value' => ( ! empty( $_POST['keywords_exclude'] ) ? sanitize_text_field( $_POST['keywords_exclude'] ) : '' ),
				'tip'   => __( 'Jobs that contain any keywords you specify here will not be imported.', 'gofetch-wpjm' ),
				'desc'  => __( 'Comma separated list of keywords that jobs MUST NOT contain to be imported.', 'gofetch-wpjm' ),
				'tr'    => 'temp-tr-hide tr-keywords-negative  tr-keywords tr-advanced tr-toggle-hide',
			),
			array(
				'title' => __( 'Negative Comparison', 'gofetch-wpjm' ),
				'type'  => 'select',
				'name'  => 'keywords_exclude_comparison',
				'choices'  => array(
					'OR' => 'OR',
					'AND' => 'AND',
				),
				'tip'  => __( '<code>OR</code> ANY negative keywords found inside the fields that you\'ve selected under the settings page, will invalidate the job.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( '<code>AND</code> Discards jobs that contain ALL the negative keywords found inside the fields that you\'ve selected under the settings page.', 'gofetch-wpjm' ),
				'tr'    => 'temp-tr-hide tr-keywords tr-advanced tr-toggle-hide',
			),
			array(
				'title'  => __( 'Positive Keywords', 'gofetch-wpjm' ),
				'type'  => 'text',
				'name'  => 'keywords',
				'extra' => array(
					'class'       => 'large-text',
					'placeholder' => 'e.g: design, sales, marketing',
				),
				'value' => ( ! empty( $_POST['keywords'] ) ? sanitize_text_field( $_POST['keywords'] ) : '' ),
				'tip'   => __( 'Only jobs containing the keywords you specify here will be imported.', 'gofetch-wpjm' ),
				'desc'  => __( 'Comma separated list of keywords that jobs MUST contain to be imported.', 'gofetch-wpjm' ),
				'tr'    => 'temp-tr-hide tr-keywords tr-advanced tr-toggle-hide',
			),
			array(
				'title' => __( 'Positive Comparison', 'gofetch-wpjm' ),
				'type'  => 'select',
				'name'  => 'keywords_comparison',
				'choices'  => array(
					'OR' => 'OR',
					'AND' => 'AND',
				),
				'tip'  => __( '<code>OR</code> If at least one of the positive keywords is found inside the fields that you\'ve selected under the settings page, the job is considered valid.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( '<code>AND</code> ALL positive keywords MUST be found inside the fields that you\'ve selected under the settings page, to consider the job valid.', 'gofetch-wpjm' ),
				'tr'    => 'temp-tr-hide tr-keywords tr-advanced tr-toggle-hide',
			),
			array(
				'title'   => __( 'Keywords Filtering?', 'gofetch-wpjm' ),
				'type'    => 'checkbox',
				'name'    => 'keywords_filtering',
				'desc'    => 'Include/Exclude jobs based on their keywords',
				'extra' => array(
					'class' => 'keywords-filtering',
				),
				'tr'      => 'temp-tr-hide tr-keywords tr-advanced',
				'tip'  => __( 'Check this option to do additional keyword matching to include/exclude certain jobs.', 'gofetch-wpjm' ),
			),
		);
		return array_merge( $fields, $new_fields );
	}

	/**
	 * Retrieves a list of providers and their details.
	 */
	public static function providers( $providers ) {

		$new_providers = array(
		'technojobs.co.uk' => array(
			'website'     => 'https://www.technojobs.co.uk/',
			'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-technojobs.png',
			'description' => 'IT Jobs Board - Specialist Technology and IT Jobs',
			'feed'        => array(
				'base_url'   => 'https://www.technojobs.co.uk/rss.php',
				'search_url' => 'https://www.technojobs.co.uk/search.phtml/searchfield/location/radius/salary',
				// Regex mappings for known/custom tags used in the feed description.
				'regexp_mappings' => array(
					/*'salary'   => '/Salary\/Rate.*?:(.*?)<.*?>/is', // e.g: <p>Salary/Rate: 50.000 - 80.000</p>*/
					'location' => '/Location.*?:(.*?)<.*?>/is',     // e.g: <p>Location: London</p>
				),
				// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
				'query_args'  => array(
					'keyword'  => array(
						'' => array(
							'default_value' => 'any',
							'is_prefix'     => 1, // means the parameter is prefixed instead of delimited with '/' or '&' (in this case with ''). e.g: ../rss/developer/locationlondon/
						),
					),
					'location' => array(
						'location' => array(
							'default_value' => '',
							'is_prefix'     => 1, // means the parameter is prefixed instead of delimited with '/' or '&' (in this case with 'location'). e.g: ../rss/developer/locationlondon/
						),
					),
				),
				'query_args_sep' => '/',
				'default'        => true,
			),
			'special' => array(
				'scrape' => array(
					'description' => array(
						'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
						'query'    => '//div[@class="job-listing-body"]',
					),
					'company' => array(
						'nicename' => __( 'Company', 'gofetch-wpjm' ),
						'query'    => '//div[@class="job-listing-details"]/table[@class="job-listing-table"]//tr[1]/td',
					),
					'location' => array(
						'nicename' => __( 'Location', 'gofetch-wpjm' ),
						'query'    => '//div[@class="job-listing-details"]/table[@class="job-listing-table"]//tr[3]/td',
					),
					'logo' => array(
						'nicename' => __( 'Logo', 'gofetch-wpjm' ),
						'query'    => '//div[@class="job-listing-image"]//img/@src',
						'base_url' => 'https://www.technojobs.co.uk',
					),
				),
			),
			'weight'      => 5,
			'category'    => __( 'IT/Development', 'gofetch-wpjm' ),
		),
		'dribbble.com' => array(
			'website'     => 'https://dribbble.com/jobs',
			'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-dribbble.png',
			'description' => 'Show and tell for designers',
			'feed'        => array(
				'base_url'   => 'https://dribbble.com/jobs.rss/',
				'search_url' => 'https://dribbble.com/jobs',
				// Mappings for known/custom tags used in feed.
				'tag_mappings' => array(
					'company' => 'creator',
				),
				// Regex mappings for known/custom tags used in the feed title.
				'regexp_mappings' => array(
					'title' => array(
						'location' => '/.*\\sin\\s(.*)/is', // e.g: Google is looking for a developer in Mountain View
						'company'  => '/(.*?) is/is', // e.g: Google is
					),
				),
				// Feed URL query args.
				'query_args'  => array(
					'keyword' => array( 'keyword' => '' ),
					'location' => array( 'location' => '' ),
				),
				// Fixed RSS feeds.
				'fixed' => array(
					__( 'Latest Jobs', 'gofetch-wpjm' ) => 'http://dribbble.com/jobs.rss',
					__( 'Team Jobs', 'gofetch-wpjm' )   => 'https://dribbble.com/jobs.rss?teams_only=true',
					__( 'Remote Jobs', 'gofetch-wpjm' ) => 'https://dribbble.com/jobs.rss?location=Anywhere',
				),
			),
			'special' => array(
				'scrape' => array(
					'description' => array(
						'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"job-details-description")]',
					),
					'company' => array(
						'nicename' => __( 'Company', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"organization-name")]',
					),
					'location' => array(
						'nicename' => __( 'Location', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"sidebar-content-container")]//div[contains(@class,"margin-t-24")][2]//div[contains(@class,"font-label")]',
					),
					'logo' => array(
						'nicename' => __( 'Logo', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"team-avatar")]/img/@src',
					),
				),
			),
			'category' => __( 'Creative', 'gofetch-wpjm' ),
			'weight'   => 9,
		),
		'krop.com' => array(
			'website'     => 'https://www.krop.com/',
			'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-krop.png',
			'description' => 'Find Creative, Design & Tech Jobs',
			'feed'        => array(
				'base_url' => 'https://www.krop.com/services/feeds/rss/latest/',
				// Regex mappings for known/custom tags used in the feed description.
				'regexp_mappings' => array(
					'title' => array(
						'company'  => '/(.*)\\sis\\s.*/is', // e.g: Google is looking for a developer in Mountain View
						'location' => '/.*\\sin\\s(.*)/is', // e.g: Google is looking for a developer in Mountain View
						// 'location' => '/Location.*?:(.*?)Status/is',  // e.g: Location: San Francisco Status: etc...
					),
				),
				'default' => true,
			),
			'special' => array(
				'scrape' => array(
					'description' => array(
						'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"job-posting-detail")]//div[contains(@class,"description")]',
					),
					'company' => array(
						'nicename' => __( 'Company', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"job-posting-detail")]//h2[contains(@class,"summary")]/a[1]',
					),
					'location' => array(
						'nicename' => __( 'Location', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"job-posting-detail")]//h2[contains(@class,"summary")]/a[2]',
					),
					'logo' => array(
						'nicename' => __( 'Logo', 'gofetch-wpjm' ),
						'query'    => '//a[contains(@class,"account-logo")]/@data-url',
					),
				),
			),
			'category' => __( 'Creative', 'gofetch-wpjm' ),
			'single'   => true,
			'weight'   => 7,
		),
		'authenticjobs.com' => array(
			'website'     => 'https://authenticjobs.com/',
			'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-authentic-jobs.png',
			'description' => 'Job opportunities for web, design, and creative professionals',
			'feed'        => array(
				'base_url'   => 'https://authenticjobs.com/?feed=job_feed',
				'search_url' => 'https://authenticjobs.com/?feed=job_feed',
				'meta'       => array(
					'logo',
					'location',
				),
				// Regex mappings for known/custom tags used in the feed description.
				'regexp_mappings' => array(
					'location' => '/^<.*?>\((.*?)\)<.*?>/is', // e.g: <strong>(NYC)</strong>
				),
				// Feed URL query args.
				'query_args'  => array(
					'keyword'  => array( 'search_keywords' => '' ),
					'location' => array( 'search_location' => '' ),
				),
				'default' => true,
			),
			'special' => array(
				'scrape' => array(
					'description' => array(
						'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"job_description")]',
						'exclude' => '//div[@id="apply-to-job"]'
					),
					'company' => array(
						'nicename' => __( 'Company', 'gofetch-wpjm' ),
						'query'    => '//a[contains(@class,"company-name")]//h6',
					),
					'location' => array(
						'nicename' => __( 'Location', 'gofetch-wpjm' ),
						'query'    => '//ul[contains(@class,"job-details")]//li[contains(@class,"location")]//div[contains(@class,"meta-content")]',
					),
					'salary' => array(
						'nicename' => __( 'Salary', 'gofetch-wpjm' ),
						'query'    => '//ul[contains(@class,"job-details")]//li[contains(@class,"salary")]//div[contains(@class,"meta-content")]',
					),
					'logo' => array(
						'nicename' => __( 'Logo', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"company-details")]//img[contains(@class,"company_logo")]/@src',
					),
				),
			),
			'category' => __( 'Creative', 'gofetch-wpjm' ),
			'weight'   => 7,
		),
		/*
		'creativejobscentral.com' => array(
			'website'     => 'https://www.creativejobscentral.com',
			'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-creativejobscentral.jpg',
			'description' => 'Creative Internship Opportunities at Creative Jobs Central',
			'feed'        => array(
				'base_url'       => 'https://www.creativejobscentral.com/rss/rss2html.php',
				'feeds_url'      => 'https://www.creativejobscentral.com/rss/rss2html.php',
				'feeds_url_desc' => __( 'See full list of RSS feeds for different job sectors and countries here', 'gofetch-wpjm' ),
				'fixed' => array(
					__( 'Animation Jobs', 'gofetch-wpjm' )      => 'https://www.creativejobscentral.com/rss/rss_jobs.php?ind=58',
					__( 'Art Jobs', 'gofetch-wpjm' )            => 'https://www.creativejobscentral.com/rss/rss_jobs.php?ind=30',
					__( 'Fashion Jobs', 'gofetch-wpjm' )        => 'https://www.creativejobscentral.com/rss/rss_jobs.php?ind=1',
					__( 'Film Jobs', 'gofetch-wpjm' )           => 'https://www.creativejobscentral.com/rss/rss_jobs.php?ind=193',
					__( 'Gaming Jobs', 'gofetch-wpjm' )         => 'https://www.creativejobscentral.com/rss/rss_jobs.php?ind=10',
					__( 'Graphic Design Jobs', 'gofetch-wpjm' ) => 'https://www.creativejobscentral.com/rss/rss_jobs.php?ind=11',
				),
			),
			'special' => array(
				'scrape' => array(
					'company' => array(
						'nicename' => __( 'Company', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"post-content")]//div[@class="col-md-5"][contains(text(),"Company")]/following-sibling::div[1][not(contains(text(),"******"))]',
					),
					'location' => array(
						'nicename' => __( 'Location', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"post-content")]//div[@class="col-md-5"][text()="Location")]/following-sibling::div[1]',
					),
					'salary' => array(
						'nicename' => __( 'Salary', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"post-content")]//div[@class="col-md-5"][text()="Benefits")]/following-sibling::div[1]',
					),

				),
			),
			'category' => __( 'Creative', 'gofetch-wpjm' ),
			'weight'   => 7,
		),*/
		'jobs.gamasutra.com' => array(
			'website'     => 'https://jobs.gamasutra.com/',
			'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-gamasutra.png',
			'description' => 'The Art & Business of Making Games',
			'feed' => array(
				'base_url'   => 'https://jobs.gamasutra.com/xml_feed/action/advanced_search/site/wj',
				'search_url' => 'https://jobs.gamasutra.com/search',
				// Regex mappings for known/custom tags used in the feed title.
				'regexp_mappings' => array(
					'title' => array(
						'company'  => '/.*\:(.*)$/is', // e.g: Developer : Google
						'location'  => '/Location:(.*?)\n/is', // e.g: Location : Mountain View
					),
				),
				// Feed URL query args.
				'query_args'  => array(
					'keyword'  => array( 'keywords' => '' ),
					'location' => array( 'city' => '' ),
				),
				'query_args_sep' => '/',
				'default'        => true,
			),
			'special' => array(
				'scrape' => array(
					'description' => array(
						'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
						'query'    => '//div[@class="right_column"]//div[@class="view_long"]',
					),
					'company' => array(
						'nicename' => __( 'Company', 'gofetch-wpjm' ),
						//'query'    => '//div[@class="view_job"]/div[1]/div[@class="left2"]/a',
						'query' => '//div[@class="view_job"]//div[@class="left1"][contains(text(),"Company Name")]/following-sibling::div[1]/a',

					),
					'location' => array(
						'nicename' => __( 'Location', 'gofetch-wpjm' ),
						//'query'    => '//div[@class="view_job"]/div[3]/div[@class="left2"][1]',
						'query' => '//div[@class="view_job"]//div[@class="left1"][contains(text(),"Location")]/following-sibling::div[1]',
					),
					'logo' => array(
						'nicename' => __( 'Logo', 'gofetch-wpjm' ),
						'query'    => '//div[@class="view_job_image"]/img[@class="view_image"]/@src',
					),
				),
			),
			'category' => __( 'Gaming', 'gofetch-wpjm' ),
			'weight'   => 7,
		),
		'weworkremotely.com' => array(
			'website'     => 'https://weworkremotely.com/',
			'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-weworkremotely.svg',
			'description' => 'Remote Jobs: Design, Programming, Rails, Executive, Marketing, and more',
			'search_url'  => 'https://weworkremotely.com/jobs/search',
			'feed' => array(
				'base_url'   => 'https://weworkremotely.com/jobs.rss',
				'search_url' => 'https://weworkremotely.com',
				'meta'       => array(
					'logo',
				),
				'regexp_mappings' => array(
					'title' => array(
						'company'  => '/^(.*):.*?/is',                         // e.g: Google : Developer
						'location' => '/Headquarters.*?:<.*?>(.*?)<.*?>/is', // e.g: <strong>Headquarters:</strong> New York, NY <br />
					),
				),
				'default' => true,
			),
			'special' => array(
				'scrape' => array(
					'description' => array(
						'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
						'query'    => '//div[@class="listing-container"]',
					),
					'company' => array(
						'nicename' => __( 'Company', 'gofetch-wpjm' ),
						'query'    => '//span[@class="company"]',
					),
					'location' => array(
						'nicename' => __( 'Location', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"company-card")]//h3[1]',
					),
					'logo' => array(
						'nicename' => __( 'Logo', 'gofetch-wpjm' ),
						'query'    => '//div[@class="listing-logo"]/img/@src',
					),
				),
			),
			'category' => __( 'Remote Work', 'gofetch-wpjm' ),
			'weight'   => 7,
		),
		'jobs.ac.uk' => array(
			'website'     => 'https://www.jobs.ac.uk/',
			'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-jobsacuk.png',
			'description' => 'Great jobs for bright people',
			'feed'        => array(
				'base_url'   => 'https://www.jobs.ac.uk/feeds',
				'search_url' => 'https://www.jobs.ac.uk/',
				'feeds_url'  => 'https://www.jobs.ac.uk/feeds/',
				// Regex mappings for known/custom tags used in the feed description.
				'regexp_mappings' => array(
					'company' => '/^(.*?)-/is', // e.g: <strong>Google - Developer</strong> blah blah
				),
				// Fixed RSS feeds examples.
				'fixed' => array(
					__( 'Latest IT Jobs ', 'gofetch-wpjm' )    => 'https://www.jobs.ac.uk/jobs/it/?format=rss',
					__( 'Latest London Jobs', 'gofetch-wpjm' ) => 'https://www.jobs.ac.uk/jobs/london/?format=rss',
				),
			),
			'special' => array(
				'scrape' => array(
					'description' => array(
						'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
						'query'    => '//div[@id="job-description"]',
					),
					'company' => array(
						'nicename' => __( 'Company', 'gofetch-wpjm' ),
						'query'    => '//h3[contains(@class,"j-advert__employer")]',
					),
					'location' => array(
						'nicename' => __( 'Location', 'gofetch-wpjm' ),
						'query'    => '//th[contains(@class,"j-advert-details__table-header")][contains(text(),"Location:")]/following-sibling::td',
					),
					'logo' => array(
						'nicename' => __( 'Logo', 'gofetch-wpjm' ),
						'query'    => '//img[contains(@class,"j-default-logo")]/@src',
						'base_url' => 'https://www.jobs.ac.uk',
					),
				),
			),
			'category' => __( 'Generic', 'gofetch-wpjm' ),
			'weight'   => 5,
		),
		'problogger.com' => array(
			'website'     => 'https://problogger.com/',
			'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-problogger.png',
			'description' => 'Jobs for Bloggers',
			'feed' => array(
				'base_url'   => 'https://problogger.com/jobs/wpjobboard/xml/rss',
				'search_url' => 'https://problogger.com/jobs',
				// Feed URL query args.
				'query_args'  => array(
					'keyword'  => array( 'query' => '' ),
					'location' => array( 'location' => '' ),
				),
				'default' => true,
			),
			'special' => array(
				'scrape' => array(
					'description' => array(
						'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
						'query'    => '//div[@class="wpjb-text-box"]/div[@class="wpjb-text"]',
					),
					'company' => array(
						'nicename' => __( 'Company', 'gofetch-wpjm' ),
						'query'    => '//span[@class="wpjb-top-header-title"]',
					),
					'location' => array(
						'nicename' => __( 'Location', 'gofetch-wpjm' ),
						'query'    => '//div[contains(@class,"wpjb-icon-location")]',
					),
					'logo' => array(
						'nicename' => __( 'Logo', 'gofetch-wpjm' ),
						'query'    => '//div[@class="wpjb-top-header-image"]/img/@src',
					),
				),
			),
			'weight'   => 6,
			'category' => __( 'Blogging', 'gofetch-wpjm' ),
		),

		);
		return array_merge( $providers, $new_providers );
	}

	/**
	 * Scans content and assigns taxonomies by matching it against a list of taxonomy terms.
	 */
	public static function smart_tax_terms_input( $tax_input, $item, $content, $taxonomies, $match_type = 'multiple' ) {

		foreach ( $taxonomies as $taxonomy ) {

			$terms = get_terms( $taxonomy->name, array( 'hide_empty' => 0 ) );

			$matched_terms = array();

			$skip_other_terms = false;

			foreach ( $terms as $term ) {

				$match_terms   = array( $term->slug, html_entity_decode( $term->name ) );
				$term_mappings = get_term_meta( $term->term_id, 'keyword_map', true );

				$match_terms = array_merge( $match_terms, array_map( 'sanitize_text_field', explode( ',', $term_mappings ) ) );

				if ( GoFetch_Helper::match_keywords( $content, $match_terms ) ) {
					$matched_terms[ $term->slug ] = $term->slug;

					if ( 'single' === $match_type ) {
						$skip_other_terms = true;
						break;
					}
				}

				if ( $skip_other_terms ) {
					break;
				}
			}

			// Only assign when terms are found.
			// Defaults to user assigned taxonomies.
			if ( $matched_terms ) {
				$tax_input[ $taxonomy->name ] = $matched_terms;
			}
		}
		return $tax_input;
	}

	/**
	 * Geocode item if it contains geocoding coordinates.
	 *
	 * @since 1.3.
	 */
	public static function geocode_item( $item, $params ) {
		static $goft_wpjm_rate_count;

		if ( empty( $item['latitude'] ) || empty( $item['longitude'] ) ) {
			return $item;
		}

		// Maybe wait some seconds. before executing the geocode to honor Google rate limits.
		$goft_wpjm_rate_count = self::maybe_honor_rate_limits( $goft_wpjm_rate_count );

		if ( ! empty( $item['location'] ) ) {
			$raw_location = $item['location'];
		} else {
			$raw_location = $item['latitude'] . $item['longitude'];
		}

		if ( $location = self::simple_reverse_geocode( $item['latitude'], $item['longitude'], $raw_location ) ) {
			$item['location'] = $location;
		}
		return $item;
	}

	/**
	 * Does a simple reverse geocode using lat and long and retrieves the result address, or false, on error.
	 */
	private static function simple_reverse_geocode( $lat, $lng, $raw_location ) {
		global $goft_wpjm_options;

		// Skip if there's no API key set.
		if ( empty( $goft_wpjm_options->geocode_api_key ) ) {

			// __LOG.
			// Maybe log import info.
			$geocode_start_time = current_time( 'timestamp' );
			$vars = array(
				'context' => 'GOFT :: SKIPPED SIMPLE REVERSE GEOCODE',
				'reason'  => 'No key provided',
			);
			BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );

			return false;
		}

		// Skip if there's no API key set.
		if ( empty( $goft_wpjm_options->geocode_api_key ) ) {
			return false;
		}

		$transient_name = 'goft_wpjm_geocode_' . md5( $raw_location );

		if ( $location = get_transient( $transient_name ) ) {

			// __LOG.
			// Maybe log import info.
			$vars = array(
				'context'      => 'GOFT :: SIMPLE REVERSE GEOCODE FROM CACHE',
				'lat'          => $lat,
				'lng'          => $lng,
				'raw_location' => $raw_location,
				'location'     => $location,
			);
			BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );

			return $location;
		}

		// __LOG.
		// Maybe log import info.
		$vars = array(
			'context'      => 'GOFT :: STARTING SIMPLE REVERSE GEOCODE',
			'lat'          => $lat,
			'lng'          => $lng,
			'raw_location' => $raw_location,
		);
		BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );

		$geo_api = 'https://maps.googleapis.com/maps/api/geocode/json?sensor=false';

		$geo_api = add_query_arg(
			apply_filters( 'goft_wpjm_geocode_params',
				array(
					'latlng' => $lat . ',' . $lng,
					'key'    => $goft_wpjm_options->geocode_api_key,
				)
			), $geo_api
		);

		$response = wp_remote_get( $geo_api, apply_filters( 'goft_wpjm_geocode_rget_params', array(
			'timeout'     => 10,
			'redirection' => 1,
			'sslverify'   => false,
		) ) );

		// __LOG.
		// Maybe log import info.
		$vars = array(
			'context'  => 'GOFT :: SIMPLE REVERSE GEOCODE RESULTS',
			'geo_api'  => $geo_api,
			'response' => $response,
		);
		BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
		//

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response         = wp_remote_retrieve_body( $response );
		$geocoded_address = json_decode( $response );

		if ( empty( $geocoded_address->results[0]->formatted_address ) ) {
			return false;
		}

		$location = sanitize_text_field( $geocoded_address->results[0]->formatted_address );

		set_transient( $transient_name, $location, 24 * HOUR_IN_SECONDS * 365 );

		return $location;
	}

	/**
	 * Honors Google geo API rate limits by waiting n seconds before continuing code execution.
	 * https://developers.google.com/maps/documentation/geocoding/usage-limits.
	 *
	 * Ignored if User has a premium plan.
	 */
	private static function maybe_honor_rate_limits( $count ) {
		global $goft_wpjm_options;

		if ( ! $goft_wpjm_options->geocode_rate_limit ) {
			return 1;
		}

		if ( ! $count ) {
			$count = 1;
		}

		if ( apply_filters( 'goft_wpjm_geocode_balance_rate_limit', ( $count % $goft_wpjm_options->geocode_rate_limit === 1 ), $count ) ) {

			// __LOG.
			// Maybe log import info.
			$vars = array(
				'context' => 'GOFT :: HONORING RATE LIMIT',
				'limit'   => $goft_wpjm_options->geocode_rate_limit,
			);
			BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );

			// __END LOG.
			// Wait for 'n' seconds to honor Google rate limits.
			sleep( apply_filters( 'goft_wpjm_geocode_balance_limit_sleep', 1 ) );

			$count = 0;

		} else {

			$count++;

		}
		return $count;
	}

}

GoFetch_Premium_Starter_More_Features::instance();
