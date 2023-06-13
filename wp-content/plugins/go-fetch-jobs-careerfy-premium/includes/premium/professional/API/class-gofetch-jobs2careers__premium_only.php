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
class GoFetch_Jobs2Careers_API_Feed_Provider extends GoFetch_API_Feed_Provider {

	/**
	 * @var The single instance of the class.
	 */
	protected static $_instance = null;

	/**
	 * The provider main URL.
	 */
	protected static $base_url = 'https://www.jobs2careers.com';

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		global $goft_wpjm_options;

		$this->id      = 'api.jobs2careers.com';
		$this->api_url = sprintf(
			'https://api.jobs2careers.com/api/search.php?id=%1$s&pass=%2$s',
			esc_attr( $goft_wpjm_options->jobs2careers_publisher_id ),
			esc_attr( $goft_wpjm_options->jobs2careers_publisher_pass )
		);
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-providers', array( $this, 'tabs' ), 65 );
		add_filter( 'goft_wpjm_providers', array( $this, 'providers' ), 15 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );
		add_filter( 'goft_wpjm_import_item_params', array( $this, 'params_meta' ), 10, 2 );
		add_filter( 'goft_wpjm_sample_item', array( $this, 'sample_item' ), 10, 2 );

		// Frontend.
		add_action( 'goft_wpjm_single_goft_job', array( $this, 'single_job_page_hooks' ) );

		add_action( 'goft_no_robots', array( $this, 'maybe_no_robots' ), 10, 2 );
	}

	/**
	 * Init the Indeed tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;
		$this->all_tabs->tabs->add( 'jobs2careers', 'Talroo' );
		$this->tab_jobs2careers();
	}

	/**
	 * Indeed settings tab.
	 */
	protected function tab_jobs2careers() {

		$info_url     = 'https://www.talroo.com/publisher/';
		$api_info_url = 'https://docs.talroo.com/search/';


		$this->all_tabs->tab_sections['jobs2careers']['logo'] = array(
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

		$this->all_tabs->tab_sections['jobs2careers']['settings'] = array(
			'title' => __( 'Account Details', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Publisher ID *', 'gofetch-wpjm' ),
					'name'  => 'jobs2careers_publisher_id',
					'type'  => 'text',
					'desc'  => sprintf( __( 'Sign up for a free <a href="%1$s" target="_new">Talroo Publisher ID</a>', 'gofetch-wpjm' ), esc_url( $info_url ) ),
					'tip'   => __( 'Your unique Publisher ID (as shown in the Feed Manager, on your Talroo Publisher dashboard).', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Publisher Password *', 'gofetch-wpjm' ),
					'name'  => 'jobs2careers_publisher_pass',
					'type'  => 'text',
					'desc'  => sprintf( __( '<a href="%1$s" target="_new">How to get a Publisher Password</a>', 'gofetch-wpjm' ), esc_url( $info_url ) ),
					'tip'   => __( 'Your unique Publisher password (as shown in the Feed Manager, on your Talroo Publisher dashboard).', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['jobs2careers']['defaults'] = array(
			'title' => __( 'Feed Defaults', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Job Type', 'gofetch-wpjm' ),
					'name'  => 'jobs2careers_feed_default_jobtype',
					'type'  => 'checkbox',
					'choices' => array(
						'1' => 'Full Time/Professional',
						'2' => 'Part Time',
						'4' => 'Gigs',
					),
					'extra' => array(
						'style'    => 'width: 300px;',
						'multiple' => 'multiple',
						'class'    => 'select2-gofj-multiple',
					),
					'tip' => __( 'Choose the job type(s) that suit your site.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Category', 'gofetch-wpjm' ),
					'name'  => 'jobs2careers_feed_default_industry',
					'type'  => 'checkbox',
					'choices' => $this->categories(),
					'extra' => array(
						'style'            => 'width: 100%;',
						'multiple'         => 'multiple',
						'class'            => 'select2-gofj-multiple',
						'data-allow-clear' => 'true',
					),
					'tip' => __( 'Choose the job categories that suit your site.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Sub-Category', 'gofetch-wpjm' ),
					'name'  => 'jobs2careers_feed_default_minor_industry',
					'type'  => 'checkbox',
					'choices' => $this->sub_categories(),
					'extra' => array(
						'style'            => 'width: 100%;',
						'multiple'         => 'multiple',
						'class'            => 'select2-gofj-multiple',
						'data-allow-clear' => 'true',
					),
					'tip' => __( 'Choose the job sub-categories that suit your site.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Radius Search', 'gofetch-wpjm' ),
					'name'  => 'jobs2careers_feed_default_distance',
					'type'  => 'select',
					'choices' => array(
						20,
						40,
						80,
					),
					'tip' => __( 'This is the distance in miles from where we believe the applicant is.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Sorting', 'gofetch-wpjm' ),
					'name'  => 'jobs2careers_feed_default_sort',
					'type'  => 'select',
					'choices' => array(
						'd' => __( 'Date', 'gofetch-wpjm' ),
						'r' => __( 'Relevance', 'gofetch-wpjm' ),
					),
					'tip' => __( 'This is the order in which you want your search results provided in.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Limit', 'gofetch-wpjm' ),
					'name'  => 'jobs2careers_feed_default_jobs_per_page',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'Maximum number of results returned per query. Limit is 200.', 'gofetch-wpjm' ),
				),
			),
		);

		$this->all_tabs->tab_sections['jobs2careers']['jobs'] = array(
			'title' => __( 'Jobs', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Block Search Indexing', 'gofetch-wpjm' ),
					'name'  => 'jobs2careers_block_search_indexing',
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
	 * Enqueues Indeed in the list of providers.
	 */
	public function providers( $providers ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'api.jobs2careers.com' => array(
				'API' => array(
					'info'      => 'https://api.jobs2careers.com/api/spec.pdf',
					'callback' => array(
						'fetch_feed'       => array( $this, 'fetch_feed' ),
						'fetch_feed_items' => array( $this, 'fetch_feed_items' ),
					),
					'required_fields' => array(
						'Publisher ID' => 'jobs2careers_publisher_id',
						'Publisher Password' => 'jobs2careers_publisher_pass',
					),
				),
				'website'     => 'https://www.talroo.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-talroo.svg',
				'description' => 'Talroo - Job Search Engine, Search Jobs & Employment.',
				'feed'        => array(
					'base_url'   => $this->get_api_url(),
					'search_url' => 'https://www.talroo.com/',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'  => array( 'q' => '' ),
						'location' => array( 'l' => array( 'placeholder' => 'e.g: Combination of city state (Austin,TX) or zipcode (78750).', 'default_value' => ''  ) ),
						'limit'    => array( 'limit'  => esc_attr( $goft_wpjm_options->jobs2careers_feed_default_jobs_per_page ) ),
						// Custom.
						'major_category' => array( 'major_category' => esc_attr( implode( '|', (array) $goft_wpjm_options->jobs2careers_feed_default_industry ) ) ),
						'minor_category' => array( 'minor_category' => esc_attr( implode( '|', (array) $goft_wpjm_options->jobs2careers_feed_default_minor_industry ) ) ),
						'jobtype'  => array( 'jobtype' => esc_attr( implode( ',', (array) $goft_wpjm_options->jobs2careers_feed_default_jobtype ) ) ),
						'd'        => array( 'd' => esc_attr( $goft_wpjm_options->jobs2careers_feed_default_distance ) ),
						'link'     => array( 'link' => esc_attr( $goft_wpjm_options->jobs2careers_feed_default_link ) ),
						'm'        => array( 'm' => esc_attr( $goft_wpjm_options->jobs2careers_feed_default_mobile_only ) ),
						'sort'      => array( 'sort' => esc_attr( $goft_wpjm_options->jobs2careers_feed_default_sort ) ),
						'full_desc' => array( 'full_desc' => esc_attr( $goft_wpjm_options->jobs2careers_feed_default_full_job_desc ) ),
					),
					'pagination' => array(
						'params'  => array(
							'page'  => 'start',
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
	 * Get a list of the available job categories.
	 */
	protected function categories() {
		return array(
			10000 => 'Accounting',
			20000 => 'Administrative/Clerical',
			30000 => 'Advertising/Marketing/Public Relations',
			40000 => 'Agriculture',
			50000 => 'Automotive',
			60000 => 'Aviation',
			70000 => 'Bilingual/Interpretation/Translation',
			80000 => 'Caregiving/Babysitting',
			100000 => 'Construction',
			110000 => 'Consumer Product/Consumer Packaged Goods/Packaging',
			130000 => 'Customer Service/Call Center',
			140000 => 'Defense/Security Clearance',
			170000 => 'Education - Excluding Post Secondary',
			180000 => 'Education - Post Secondary',
			190000 => 'Electronics/Semiconductors',
			200000 => 'Energy/Power',
			210000 => 'Engineering',
			220000 => 'Entertainment',
			230000 => 'Executive Management',
			240000 => 'Facility Maintenance',
			250000 => 'Fashion',
			260000 => 'Financial Services - Banking/Investment/Finance',
			270000 => 'Financial Services - Insurance',
			280000 => 'Firefighting',
			300000 => 'Government',
			310000 => 'Graphic Design/CAD',
			320000 => 'Healthcare - Allied Health',
			330000 => 'Healthcare - Dental Care',
			340000 => 'Healthcare - Nursing',
			350000 => 'Healthcare - Physician',
			360000 => 'Hospitality - Food Services',
			370000 => 'Hospitality - Lodging/Resort/Travel',
			390000 => 'Human Resources',
			400000 => 'Information Technology',
			410000 => 'Interior Design/Decorating',
			430000 => 'Law Enforcement',
			440000 => 'Legal',
			450000 => 'Manufacturing',
			460000 => 'Maritime Transportation',
			470000 => 'Media',
			480000 => 'Mining/Extraction',
			485000 => 'Non-Executive Management',
			490000 => 'Nonprofit',
			510000 => 'Pharmaceutical/Biotechnology',
			520000 => 'Printing/Publishing',
			530000 => 'Real Estate',
			540000 => 'Retail',
			550000 => 'Sales',
			560000 => 'Scientific Research',
			580000 => 'Social Services/Counseling',
			590000 => 'Sports',
			610000 => 'Telecommunication/Wireless/Cable',
			620000 => 'Telecommuting/Work-At-Home',
			630000 => 'Trucking',
			650000 => 'Veterinary Services',
			660000 => 'Warehousing/Logistics',
			999000 => 'Miscellaneous',
		);
	}

	/**
	 * Get a list of all the job sub-categories.
	 */
	protected function sub_categories() {
		return array(
			10010 => '[Accounting] Accounts Payable/Accounts Receivable/Bookkeeping',
			10020 => '[Accounting] Accounting Management',
			10030 => '[Accounting] Corporate Accounting - Entry Level',
			10040 => '[Accounting] Corporate Accounting - Senior',
			10050 => '[Accounting] Public Accounting - Entry Level',
			10060 => '[Accounting] Public Accounting - Senior',
			10999 => '[Accounting] Others',
			20010 => '[Administrative/Clerical] Administrative Support - General',
			20020 => '[Administrative/Clerical] Data Entry/Typist/Word Processor',
			20030 => '[Administrative/Clerical] File/Mail/Record Clerk',
			20040 => '[Administrative/Clerical] Executive Secretary',
			20050 => '[Administrative/Clerical] Legal Secretary/Paralegal',
			20060 => '[Administrative/Clerical] Receptionist/Telephone Operator',
			20999 => '[Administrative/Clerical] Others',
			30010 => '[Advertising/Marketing/Public Relations] Account Executive/Manager',
			30020 => '[Advertising/Marketing/Public Relations] Art Director',
			30030 => '[Advertising/Marketing/Public Relations] Digital Marketer/Community Relations Manager',
			30040 => '[Advertising/Marketing/Public Relations] Copywriter And Proposal Writer',
			30050 => '[Advertising/Marketing/Public Relations] Brand Ambassador',
			30060 => '[Advertising/Marketing/Public Relations] Graphic Designer',
			30070 => '[Advertising/Marketing/Public Relations] Marketer/Market Researcher/Data Analyst',
			30080 => '[Advertising/Marketing/Public Relations] Event Coordinator/Planner',
			30090 => '[Advertising/Marketing/Public Relations] Marketing Director',
			30100 => '[Advertising/Marketing/Public Relations] Media Planner/Buyer',
			30999 => '[Advertising/Marketing/Public Relations] Others',
			40010 => '[Agriculture] Agronomist',
			40020 => '[Agriculture] Apprentice/Intern',
			40030 => '[Agriculture] Farm/Ranch Assistant',
			40040 => '[Agriculture] Farm/Ranch Operations',
			40050 => '[Agriculture] Farm/Ranch Management',
			40060 => '[Agriculture] Plant/Mill Management/Administration',
			40070 => '[Agriculture] Plant/Mill Operations',
			40080 => '[Agriculture] Sales - Equipment',
			40090 => '[Agriculture] Sales - Agricultural Product',
			40999 => '[Agriculture] Others',
			50010 => '[Automotive] Automotive Assembler',
			50020 => '[Automotive] Automotive Designer/Engineer',
			50030 => '[Automotive] Auto Body Painter',
			50040 => '[Automotive] Auto Collision Estimator/Service Adviser',
			50050 => '[Automotive] Auto Parts Manager/Counter Person',
			50060 => '[Automotive] Auto Sales/Dealer',
			50070 => '[Automotive] Automotive Mechanic/Technician',
			50080 => '[Automotive] Diesel Mechanic/Technician',
			50090 => '[Automotive] Finance And Insurance Manager',
			50100 => '[Automotive] Title Clerk',
			50999 => '[Automotive] Others',
			60010 => '[Aviation] Aircraft Mechanic/Technician',
			60020 => '[Aviation] Aircraft Sales',
			60030 => '[Aviation] Airport/Ground Operations Manager',
			60040 => '[Aviation] Airport/Ground Staff',
			60050 => '[Aviation] Air Traffic Control',
			60060 => '[Aviation] Engineering/Design',
			60070 => '[Aviation] Pilot/Flight Operations',
			60080 => '[Aviation] Training/Instruction',
			60999 => '[Aviation] Others',
			70999 => '[Bilingual/Interpretation/Translation] All',
			80010 => '[Caregiving/Babysitting] Babysitter/Child Caregiver',
			80020 => '[Caregiving/Babysitting] Housekeeper/Maid',
			80030 => '[Caregiving/Babysitting] Nanny/Au Pair',
			80040 => '[Caregiving/Babysitting] Personal Assistant',
			80050 => '[Caregiving/Babysitting] Pet Care',
			80060 => '[Caregiving/Babysitting] Senior Care',
			80999 => '[Caregiving/Babysitting] Others',
			100010 => '[Construction] Architect',
			100020 => '[Construction] Carpenter',
			100030 => '[Construction] Civil Engineer',
			100040 => '[Construction] Cost Estimator',
			100050 => '[Construction] Electrical Engineer',
			100060 => '[Construction] Electrician',
			100070 => '[Construction] Heavy Equipment Operator',
			100080 => '[Construction] HVAC Mechanic',
			100090 => '[Construction] Inspector And Surveyor',
			100100 => '[Construction] Ironworker/Welder',
			100110 => '[Construction] Laborer',
			100120 => '[Construction] Mason',
			100130 => '[Construction] Plumber/Pipefitter',
			100140 => '[Construction] Superintendent/Manager',
			100150 => '[Construction] Surveying And Mapping Technician',
			100999 => '[Construction] Others',
			110999 => '[Consumer Product/Consumer Packaged Goods/Packaging] All',
			130010 => '[Customer Service/Call Center] Call Center/Customer Service Manager',
			130020 => '[Customer Service/Call Center] Call Center/Customer Service Representative',
			130030 => '[Customer Service/Call Center] Collections',
			130040 => '[Customer Service/Call Center] Customer Service Analyst',
			130050 => '[Customer Service/Call Center] Help Desk',
			130060 => '[Customer Service/Call Center] Telemarketing/Inside Sales',
			130999 => '[Customer Service/Call Center] Others',
			140010 => '[Defense/Security Clearance] Confidential',
			140020 => '[Defense/Security Clearance] DoE/DoJ And Other Clearances',
			140030 => '[Defense/Security Clearance] Secret',
			140040 => '[Defense/Security Clearance] Top Secret',
			140050 => '[Defense/Security Clearance] Top Secret - SCI',
			140999 => '[Defense/Security Clearance] Others',
			170010 => '[Education - Excluding Post Secondary] Administrative Support And Student Affairs',
			170020 => '[Education - Excluding Post Secondary] Executive Management',
			170030 => '[Education - Excluding Post Secondary] Teacher, Early Childhood Education',
			170040 => '[Education - Excluding Post Secondary] Teacher, Elementary School',
			170050 => '[Education - Excluding Post Secondary] Teacher, Middle School And High School',
			170060 => '[Education - Excluding Post Secondary] Teacher, Special Education',
			170070 => '[Education - Excluding Post Secondary] Teacher/Tutor, Extracurricular',
			170999 => '[Education - Excluding Post Secondary] Others',
			180010 => '[Education - Post Secondary] Business And Operational Support',
			180020 => '[Education - Post Secondary] Executive Management',
			180030 => '[Education - Post Secondary] Faculty, Business And Law',
			180040 => '[Education - Post Secondary] Faculty, Engineering',
			180050 => '[Education - Post Secondary] Faculty, Health And Medicine',
			180060 => '[Education - Post Secondary] Faculty, Humanities',
			180070 => '[Education - Post Secondary] Faculty, Mathematics And Natural Sciences',
			180080 => '[Education - Post Secondary] Faculty, Professions',
			180090 => '[Education - Post Secondary] Faculty, Social Sciences',
			180100 => '[Education - Post Secondary] Faculty, Vocational And Technical Education',
			180110 => '[Education - Post Secondary] Student Affairs',
			180999 => '[Education - Post Secondary] Others',
			190010 => '[Electronics/Semiconductors] Semiconductor Processing',
			190020 => '[Electronics/Semiconductors] Hardware Engineer/Designer',
			190030 => '[Electronics/Semiconductors] Hardware Test Engineer',
			190999 => '[Electronics/Semiconductors] Others',
			200010 => '[Energy/Power] Engineering, Petroleum And Chemical',
			200020 => '[Energy/Power] Engineering, Civil And Mechanical',
			200030 => '[Energy/Power] Engineering, Electrical And Nuclear',
			200040 => '[Energy/Power] Field And Refinery Operations',
			200050 => '[Energy/Power] Field And Refinery Operations Management',
			200060 => '[Energy/Power] Geology/Geophysics',
			200070 => '[Energy/Power] Health, Environment And Safety',
			200080 => '[Energy/Power] Power Plant Operations',
			200090 => '[Energy/Power] Procurement/Purchasing',
			200100 => '[Energy/Power] Sales/Marketing',
			200110 => '[Energy/Power] Scheduling And Dispatching',
			200999 => '[Energy/Power] Others',
			210010 => '[Engineering] Aerospace Engineer',
			210020 => '[Engineering] Architect',
			210030 => '[Engineering] Biological And Biomedical Engineer',
			210040 => '[Engineering] Chemical Engineer',
			210050 => '[Engineering] Civil Engineer',
			210060 => '[Engineering] Electrical Engineer',
			210070 => '[Engineering] Electronic/Computer Engineer',
			210080 => '[Engineering] Environmental Engineer',
			210090 => '[Engineering] Industrial Engineer',
			210100 => '[Engineering] Material Engineer',
			210110 => '[Engineering] Mechanical Engineer',
			210120 => '[Engineering] Mineral And Petroleum Engineer',
			210130 => '[Engineering] Nuclear Engineer',
			210999 => '[Engineering] Others',
			220010 => '[Entertainment] Administrative And Operational Support',
			220020 => '[Entertainment] Agent/Manager',
			220030 => '[Entertainment] Choreographer/Dancer',
			220040 => '[Entertainment] Comedian',
			220050 => '[Entertainment] Film/TV/Music Studio Crew',
			220060 => '[Entertainment] Film/TV/Video Cast',
			220070 => '[Entertainment] Film/TV/Video Producer And Director',
			220080 => '[Entertainment] Music Producer',
			220090 => '[Entertainment] Musician/Singer/Composer',
			220100 => '[Entertainment] Stage Staff/Stage Technician',
			220110 => '[Entertainment] Theater Performer',
			220120 => '[Entertainment] Voice Over',
			220130 => '[Entertainment] Writer/Editor',
			220999 => '[Entertainment] Others',
			230010 => '[Executive Management] Accounting/Finance',
			230020 => '[Executive Management] Administrative/Operational Support',
			230030 => '[Executive Management] Chief Executive/General Management',
			230040 => '[Executive Management] Engineering/Production',
			230050 => '[Executive Management] Human Resources',
			230060 => '[Executive Management] Information Technology',
			230070 => '[Executive Management] Marketing/Public Relations',
			230080 => '[Executive Management] Procurement/Supply Chain',
			230090 => '[Executive Management] Sales/Business Development',
			230999 => '[Executive Management] Others',
			240010 => '[Facility Maintenance] Administrative Support',
			240020 => '[Facility Maintenance] Director/Property Manager',
			240030 => '[Facility Maintenance] Electrician',
			240040 => '[Facility Maintenance] Grounds Maintenance',
			240050 => '[Facility Maintenance] HVAC Technician',
			240060 => '[Facility Maintenance] Janitor',
			240070 => '[Facility Maintenance] Pest Control',
			240080 => '[Facility Maintenance] Plumber',
			240999 => '[Facility Maintenance] Others',
			250010 => '[Fashion] Advertising/Marketing/Public Relations',
			250020 => '[Fashion] Fashion Design',
			250030 => '[Fashion] Internship',
			250040 => '[Fashion] IT/E-Commerce',
			250050 => '[Fashion] Model/Styling',
			250060 => '[Fashion] Patternmaking/Production',
			250070 => '[Fashion] Purchasing/Merchandising',
			250080 => '[Fashion] Retail/Sales/Account Management',
			250090 => '[Fashion] Visual Merchandising',
			250999 => '[Fashion] Others',
			260010 => '[Financial Services - Banking/Investment/Finance] Banker/Loan Officer',
			260020 => '[Financial Services - Banking/Investment/Finance] Financial Analyst/Examiner',
			260030 => '[Financial Services - Banking/Investment/Finance] General Operational Manager',
			260040 => '[Financial Services - Banking/Investment/Finance] Loan Processor',
			260050 => '[Financial Services - Banking/Investment/Finance] Securities Broker',
			260060 => '[Financial Services - Banking/Investment/Finance] Teller',
			260999 => '[Financial Services - Banking/Investment/Finance] Others',
			270010 => '[Financial Services - Insurance] Actuary',
			270020 => '[Financial Services - Insurance] Claim Adjuster',
			270030 => '[Financial Services - Insurance] Insurance Sales Agent/Broker',
			270040 => '[Financial Services - Insurance] Underwriter',
			270999 => '[Financial Services - Insurance] Others',
			280010 => '[Firefighting] Fire Chief/Battalion Chief',
			280020 => '[Firefighting] Fire Marshal',
			280030 => '[Firefighting] Firefighter',
			280040 => '[Firefighting] Dispatcher',
			280999 => '[Firefighting] Others',
			300010 => '[Government] Accounting/Finance',
			300020 => '[Government] Administrative And Operational Support',
			300030 => '[Government] Animal/Forestry/Environment',
			300040 => '[Government] Architecture/Engineering',
			300050 => '[Government] Community And Social Services',
			300060 => '[Government] Education',
			300070 => '[Government] Facility Maintenance',
			300080 => '[Government] Healthcare',
			300090 => '[Government] IT And Computer Systems',
			300100 => '[Government] Law Enforcement/Fire',
			300110 => '[Government] Legal/Compliance',
			300120 => '[Government] Library/Museum',
			300130 => '[Government] Logistics/Transportation',
			300140 => '[Government] Public Affairs',
			300150 => '[Government] Science/Research',
			300999 => '[Government] Others',
			310999 => '[Graphic Design/CAD] All',
			320010 => '[Healthcare - Allied Health] Dietitian/Nutritionist',
			320020 => '[Healthcare - Allied Health] Emergency Medical Technician/Paramedic',
			320030 => '[Healthcare - Allied Health] Medical Assistant',
			320040 => '[Healthcare - Allied Health] Medical Biller/Coder',
			320050 => '[Healthcare - Allied Health] Medical Technologist (MLS)',
			320060 => '[Healthcare - Allied Health] Nursing Assistant/Patient Care Technician',
			320070 => '[Healthcare - Allied Health] Occupational Therapist',
			320080 => '[Healthcare - Allied Health] Pharmacist',
			320090 => '[Healthcare - Allied Health] Phlebotomist',
			320100 => '[Healthcare - Allied Health] Physical Therapist',
			320110 => '[Healthcare - Allied Health] Physician Assistant',
			320120 => '[Healthcare - Allied Health] Radiographer',
			320130 => '[Healthcare - Allied Health] Recreational/Expressive Therapist',
			320140 => '[Healthcare - Allied Health] Respiratory Therapist',
			320150 => '[Healthcare - Allied Health] Social Worker/Counselor',
			320160 => '[Healthcare - Allied Health] Sonographer',
			320170 => '[Healthcare - Allied Health] Speech And Language Pathologist (Therapist)',
			320180 => '[Healthcare - Allied Health] Surgical Assistant/Technologist',
			320999 => '[Healthcare - Allied Health] Others',
			330010 => '[Healthcare - Dental Care] Dental Assistant',
			330020 => '[Healthcare - Dental Care] Dental Hygienist',
			330030 => '[Healthcare - Dental Care] Dental Technician',
			330040 => '[Healthcare - Dental Care] General Dentist',
			330050 => '[Healthcare - Dental Care] Oral/Maxillofacial Surgeon',
			330060 => '[Healthcare - Dental Care] Orthodontist/Prosthodontist',
			330999 => '[Healthcare - Dental Care] Others',
			340010 => '[Healthcare - Nursing] Nursing Manager',
			340020 => '[Healthcare - Nursing] Advanced Practice Registered Nurse (APRN)',
			340030 => '[Healthcare - Nursing] Registered Nurse (RN/BSN/ASN)',
			340040 => '[Healthcare - Nursing] Licensed Practical Nurse (LPN/LVN)',
			340999 => '[Healthcare - Nursing] Others',
			350010 => '[Healthcare - Physician] Anesthesiology',
			350020 => '[Healthcare - Physician] Cardiology',
			350030 => '[Healthcare - Physician] Critical Care',
			350040 => '[Healthcare - Physician] Dermatology',
			350050 => '[Healthcare - Physician] Family Medicine/General Practice',
			350060 => '[Healthcare - Physician] General Surgery',
			350070 => '[Healthcare - Physician] HIV/Infectious Diseases',
			350080 => '[Healthcare - Physician] Internal Medicine',
			350090 => '[Healthcare - Physician] Neurology',
			350100 => '[Healthcare - Physician] OB/GYN And Women\'s Health',
			350110 => '[Healthcare - Physician] Oncology',
			350120 => '[Healthcare - Physician] Ophthalmology',
			350130 => '[Healthcare - Physician] Optometry',
			350140 => '[Healthcare - Physician] Orthopedic Surgery',
			350150 => '[Healthcare - Physician] Pathology',
			350160 => '[Healthcare - Physician] Pediatrics',
			350170 => '[Healthcare - Physician] Plastic Surgery',
			350180 => '[Healthcare - Physician] Psychiatry',
			350190 => '[Healthcare - Physician] Radiology',
			350200 => '[Healthcare - Physician] Urology',
			350999 => '[Healthcare - Physician] Others',
			360010 => '[Hospitality - Food Services] Bartender',
			360020 => '[Hospitality - Food Services] Busser',
			360030 => '[Hospitality - Food Services] Chef - Commercial Kitchen',
			360040 => '[Hospitality - Food Services] Cook - Commercial Kitchen',
			360050 => '[Hospitality - Food Services] Cook/Chef - Personal/Private',
			360060 => '[Hospitality - Food Services] Dishwasher',
			360070 => '[Hospitality - Food Services] General Operations Manager - Food Services',
			360080 => '[Hospitality - Food Services] Waiter/Server',
			360999 => '[Hospitality - Food Services] Others',
			370010 => '[Hospitality - Lodging/Resort/Travel] Bellperson/Concierge/Host',
			370020 => '[Hospitality - Lodging/Resort/Travel] Camp Staff',
			370030 => '[Hospitality - Lodging/Resort/Travel] Esthetician/Nail Tech/Hairdresser',
			370040 => '[Hospitality - Lodging/Resort/Travel] Flight Attendent',
			370050 => '[Hospitality - Lodging/Resort/Travel] Gaming Service Worker',
			370060 => '[Hospitality - Lodging/Resort/Travel] Gaming Service Supervisor',
			370070 => '[Hospitality - Lodging/Resort/Travel] Housekeeper',
			370080 => '[Hospitality - Lodging/Resort/Travel] Massage Therapist',
			370090 => '[Hospitality - Lodging/Resort/Travel] Tour Guide',
			370100 => '[Hospitality - Lodging/Resort/Travel] Valet/Parking Attendant',
			370999 => '[Hospitality - Lodging/Resort/Travel] Others',
			390010 => '[Human Resources] Assistant/Intern',
			390020 => '[Human Resources] Director/VP/CHRO',
			390030 => '[Human Resources] Generalist',
			390040 => '[Human Resources] Specialist - Compensation/Benefit/Payroll',
			390050 => '[Human Resources] Specialist - Employee/Labor Relations',
			390060 => '[Human Resources] Specialist - Information Systems (HRIS)',
			390070 => '[Human Resources] Specialist - Learning And Development',
			390080 => '[Human Resources] Specialist - Recruiting',
			390999 => '[Human Resources] Others',
			400010 => '[Information Technology] Chief Executive (Director/CIO/CTO)',
			400020 => '[Information Technology] Business Systems Analyst And IT Consultant',
			400030 => '[Information Technology] Data Architect/Analyst/Administrator',
			400040 => '[Information Technology] Information Security Specialist/Forensics',
			400050 => '[Information Technology] IT Help Desk/Technical Support',
			400060 => '[Information Technology] Mobile Developer',
			400070 => '[Information Technology] Project And Product Manager',
			400080 => '[Information Technology] Software Architect And Senior Engineer',
			400090 => '[Information Technology] Software Engineer, Developer, And Programmer',
			400100 => '[Information Technology] Software Quality Assurance Analyst',
			400110 => '[Information Technology] Systems And Network Administrator',
			400120 => '[Information Technology] System Architect And Senior Engineer',
			400130 => '[Information Technology] System Engineer And Technician',
			400140 => '[Information Technology] Technical Writer/Documentation Specialist',
			400150 => '[Information Technology] UX/UI Designer',
			400160 => '[Information Technology] Web Developer',
			400999 => '[Information Technology] Others',
			410999 => '[Interior Design/Decorating] All',
			430010 => '[Law Enforcement] Correctional Officer',
			430020 => '[Law Enforcement] Criminologist And Forensic Scientist',
			430030 => '[Law Enforcement] Detective/Investigator',
			430040 => '[Law Enforcement] Dispatcher',
			430050 => '[Law Enforcement] Immigration/Border Control Officer',
			430060 => '[Law Enforcement] Police Chief',
			430070 => '[Law Enforcement] Police Commander/Captain',
			430080 => '[Law Enforcement] Police Officer/Deputy Sheriff',
			430090 => '[Law Enforcement] Security Officer/Guard',
			430999 => '[Law Enforcement] Others',
			440010 => '[Legal] Attorney - Corporate',
			440020 => '[Legal] Attorney - Firm',
			440030 => '[Legal] Legal Secretary/Paralegal',
			440999 => '[Legal] Others',
			450010 => '[Manufacturing] Assembling',
			450020 => '[Manufacturing] Carpentry',
			450030 => '[Manufacturing] CNC Fabricating/Machining',
			450040 => '[Manufacturing] Food Processing',
			450050 => '[Manufacturing] General Fabricating/Welding',
			450060 => '[Manufacturing] Healthcare Appliances',
			450070 => '[Manufacturing] Machine Repair And Maintenance',
			450080 => '[Manufacturing] Packaging',
			450090 => '[Manufacturing] Painting And Coating',
			450100 => '[Manufacturing] Patternmaking (Apparel And Texture)',
			450110 => '[Manufacturing] Printing',
			450120 => '[Manufacturing] Production/Plant Management',
			450130 => '[Manufacturing] Quality Control And Assurance',
			450140 => '[Manufacturing] Textile And Sewing',
			450150 => '[Manufacturing] Tool And Die',
			450160 => '[Manufacturing] Upholstery',
			450999 => '[Manufacturing] Others',
			460010 => '[Maritime Transportation] Captain',
			460020 => '[Maritime Transportation] Deck Officer',
			460030 => '[Maritime Transportation] Deck Operations',
			460040 => '[Maritime Transportation] Engine/Electronics Engineer',
			460050 => '[Maritime Transportation] Engine/Electronics Operations',
			460060 => '[Maritime Transportation] Hospitality Operations',
			460070 => '[Maritime Transportation] Safety/Security/Medical',
			460080 => '[Maritime Transportation] Cadet/Trainee',
			460999 => '[Maritime Transportation] Others',
			470010 => '[Media] Anchor/Announcer/Host',
			470020 => '[Media] Audio/Visual/Broadcast',
			470030 => '[Media] Crew - Non-Technical',
			470040 => '[Media] Director/Producer/Editor-In-Chief',
			470050 => '[Media] Editor - Text',
			470060 => '[Media] Intern',
			470070 => '[Media] Journalist/Reporter/Correspondent',
			470080 => '[Media] Video/Graphic',
			470999 => '[Media] Others',
			480010 => '[Mining/Extraction] Administrative And Operational Support',
			480020 => '[Mining/Extraction] Engineering',
			480030 => '[Mining/Extraction] Environment, Health And Safety',
			480040 => '[Mining/Extraction] Exploration And Geoscience',
			480050 => '[Mining/Extraction] Extraction/Production Management',
			480060 => '[Mining/Extraction] Construction And Extraction Operations',
			480070 => '[Mining/Extraction] Refinement Operations',
			480999 => '[Mining/Extraction] Others',
			485999 => '[Non-Executive Management] All',
			490010 => '[Nonprofit] Administrative And Operational Support',
			490020 => '[Nonprofit] Accounting And Finance',
			490030 => '[Nonprofit] Development/Fundraising/Donor Relations',
			490040 => '[Nonprofit] Executive Management',
			490050 => '[Nonprofit] Grant Administration',
			490060 => '[Nonprofit] Program Staff',
			490070 => '[Nonprofit] Program Development/Management',
			490080 => '[Nonprofit] Public Relations/Marketing',
			490090 => '[Nonprofit] Volunteer Management',
			490999 => '[Nonprofit] Others',
			510010 => '[Pharmaceutical/Biotechnology] Biostatistician',
			510020 => '[Pharmaceutical/Biotechnology] Brand/Product Manager',
			510030 => '[Pharmaceutical/Biotechnology] Engineer',
			510040 => '[Pharmaceutical/Biotechnology] Medical Affairs',
			510050 => '[Pharmaceutical/Biotechnology] Medical Writer',
			510060 => '[Pharmaceutical/Biotechnology] Pharmacist',
			510070 => '[Pharmaceutical/Biotechnology] Principal Investigator',
			510080 => '[Pharmaceutical/Biotechnology] Research Associate/Quality Control',
			510090 => '[Pharmaceutical/Biotechnology] Research Coordinator',
			510100 => '[Pharmaceutical/Biotechnology] Research Fellow/Scientist',
			510110 => '[Pharmaceutical/Biotechnology] Sales',
			510120 => '[Pharmaceutical/Biotechnology] Technician/Operational Staff',
			510999 => '[Pharmaceutical/Biotechnology] Others',
			520010 => '[Printing/Publishing] Bindery/Finisher',
			520020 => '[Printing/Publishing] Estimator/Account Manager',
			520030 => '[Printing/Publishing] Ink Technician',
			520040 => '[Printing/Publishing] Operations Manager',
			520050 => '[Printing/Publishing] Packaging Operator',
			520060 => '[Printing/Publishing] Prepress',
			520070 => '[Printing/Publishing] Press Operator - Digital/Reprographic',
			520080 => '[Printing/Publishing] Press Operator - Flexographic/Screen',
			520090 => '[Printing/Publishing] Press Operator - Sheetfed',
			520100 => '[Printing/Publishing] Press Operator - Web',
			520999 => '[Printing/Publishing] Others',
			530010 => '[Real Estate] Broker/Agent - Commercial Real Estate',
			530020 => '[Real Estate] Broker/Agent - Residential Real Estate',
			530030 => '[Real Estate] Community/Property Manager',
			530040 => '[Real Estate] Leasing Manager/Consultant',
			530050 => '[Real Estate] Real Estate Analyst',
			530060 => '[Real Estate] Real Estate Attorney',
			530070 => '[Real Estate] Real Estate Director/Manager',
			530080 => '[Real Estate] Title Agent',
			530999 => '[Real Estate] Others',
			540010 => '[Retail] Administrative And Operational Support',
			540020 => '[Retail] Customer Service/Sales',
			540030 => '[Retail] Loss Prevention',
			540040 => '[Retail] Management',
			540050 => '[Retail] Merchandising/Stocking',
			540999 => '[Retail] Others',
			550010 => '[Sales] Cashier/Counter Person',
			550020 => '[Sales] Real Estate Agent/Broker',
			550030 => '[Sales] Retailer',
			550040 => '[Sales] Sales Engineer',
			550050 => '[Sales] Sales - Business Services',
			550060 => '[Sales] Sales - Insurance',
			550070 => '[Sales] Sales - Securities/Financial Services',
			550080 => '[Sales] Sales - Wholesaler/Manufacturer',
			550090 => '[Sales] Telemarketer',
			550100 => '[Sales] Travel Agent',
			550110 => '[Sales] Sales Manager',
			550999 => '[Sales] Others',
			560010 => '[Scientific Research] Biology/Bioengineering',
			560020 => '[Scientific Research] Chemistry/Material Science',
			560030 => '[Scientific Research] Economics',
			560040 => '[Scientific Research] Education',
			560050 => '[Scientific Research] Electronic Engineering/Computer Science',
			560060 => '[Scientific Research] Environmental Science',
			560070 => '[Scientific Research] Geoscience/Earth Science',
			560080 => '[Scientific Research] Health/Medicine/Pharmacology',
			560090 => '[Scientific Research] Humanities',
			560100 => '[Scientific Research] Mathematics',
			560110 => '[Scientific Research] Neuroscience/Psychology',
			560120 => '[Scientific Research] Physics',
			560130 => '[Scientific Research] Political Science',
			560140 => '[Scientific Research] Statistics',
			560999 => '[Scientific Research] Others',
			580999 => '[Social Services/Counseling] All',
			590010 => '[Sports] Athletic Director',
			590020 => '[Sports] Coach - Collegiate',
			590030 => '[Sports] Coach - Pre-Collegiate',
			590040 => '[Sports] Facility Operations - Manager',
			590050 => '[Sports] Facility Operations - Staff',
			590060 => '[Sports] Intern/Entry-Level Sports Marketing',
			590070 => '[Sports] Referee/Umpire/Sports Official',
			590080 => '[Sports] Sports Agent',
			590090 => '[Sports] Sports Broadcaster/Reporter/Journalist/Analyst',
			590100 => '[Sports] Sports Information Director',
			590110 => '[Sports] Vendor/Ticket Sales',
			590999 => '[Sports] Others',
			610010 => '[Telecommunication/Wireless/Cable] Accounting And Finance',
			610020 => '[Telecommunication/Wireless/Cable] Administrative/Clerical',
			610030 => '[Telecommunication/Wireless/Cable] Customer Service/Call Center',
			610040 => '[Telecommunication/Wireless/Cable] Field Installation',
			610050 => '[Telecommunication/Wireless/Cable] Marketing',
			610060 => '[Telecommunication/Wireless/Cable] Network/Systems/Wireless',
			610070 => '[Telecommunication/Wireless/Cable] Software',
			610999 => '[Telecommunication/Wireless/Cable] Others',
			620999 => '[Telecommuting/Work-At-Home] All',
			630010 => '[Trucking] CDL-A Driver - Company',
			630020 => '[Trucking] CDL-A Driver - Owner Operator',
			630030 => '[Trucking] CDL-B Driver/CDL-C Driver',
			630040 => '[Trucking] Fleet Management',
			630060 => '[Trucking] Team Driver',
			630070 => '[Trucking] Trainee/Inexperienced Driver',
			630080 => '[Trucking] Delivery Driver',
			630999 => '[Trucking] Others',
			650010 => '[Veterinary Services] Boarding/Grooming',
			650020 => '[Veterinary Services] Office Staff',
			650030 => '[Veterinary Services] Practice Manager',
			650040 => '[Veterinary Services] Research/Lab Technician',
			650050 => '[Veterinary Services] Sales',
			650060 => '[Veterinary Services] Veterinarian',
			650070 => '[Veterinary Services] Veterinary Assistant/Technician',
			650999 => '[Veterinary Services] Others',
			660010 => '[Warehousing/Logistics] Customs Brokerage',
			660020 => '[Warehousing/Logistics] Freight Forwarding',
			660030 => '[Warehousing/Logistics] Freight Broker/Sales',
			660040 => '[Warehousing/Logistics] Warehouse/Logistics Management',
			660050 => '[Warehousing/Logistics] Warehouse/Logistics Operations',
			660999 => '[Warehousing/Logistics] Others',
			999999 => '[Miscellaneous] All',
		);
	}

	/**
	 * Outputs specific Indeed feed parameter fields.
	 */
	public function feed_builder_fields( $provider ) {

		if ( ! $this->condition( $provider ) ) {
			return;
		}
?>
		<div class="clear"></div>

		<?php
		$field_name = 'major_category';

		$field = array(
			'title'   => __( 'Categories', 'gofetch-wpjm' ),
			'name'    => 'feed-'. $field_name,
			'type'    => 'select',
			'choices' => $this->categories(),
			'class'   => 'regular-text',
			'extra'   => array(
				'data-qarg' => 'feed-param-' . $field_name,
				'multiple'  => 'multiple',
				'style'     => "width: 550px;",
			),
			'default' => '30000',
		);
		?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Industries', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Choose the job industries that suit your site.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<?php echo scbForms::input( $field, array() ) ?>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

		<?php
		$field_name = 'minor_category';

		$field = array(
			'title'   => __( 'Sub-Categories', 'gofetch-wpjm' ),
			'name'    => 'feed-'. $field_name,
			'type'    => 'select',
			'choices' => $this->sub_categories(),
			'class'   => 'regular-text',
			'extra'   => array(
				'data-qarg' => 'feed-param-' . $field_name,
				'multiple'  => 'multiple',
				'style'     => "width: 550px;",
			),
			'default' => '30000',
		);
		?>

		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Sub-Industries', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Choose the sub job industries that suit your site.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<?php echo scbForms::input( $field, array() ) ?>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

		<?php $field_name = 'jobtype'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Job Type(s)', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Choose the job type(s) you wish to filter.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: 450px;" multiple="multiple" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>">
				<option value="1"><?php esc_attr_e( ' Full Time/Professional', 'gofetch-wpjm' ); ?></option>
				<option value="2"><?php esc_attr_e( 'Part-Time', 'gofetch-wpjm' ); ?></option>
				<option value="4"><?php esc_attr_e( 'Gigs', 'gofetch-wpjm' ); ?></option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<?php $field_name = 'd'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Distance (in miles)', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Jobs Distance (in miles).', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: 80px;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: 20', 'gofetch-wpjm' ); ?>">
				<option value="20">20</option>
				<option value="40">40</option>
				<option value="80">80</option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>

		<?php $field_name = 'sort'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Sort Order', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The order for the search results', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<select class="regular-text" style="width: auto;" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: Relevance', 'gofetch-wpjm' ); ?>">
				<option value="r" selected><?php esc_attr_e( 'Relevance', 'gofetch-wpjm' ); ?></option>
				<option value="d"><?php esc_attr_e( 'Date', 'gofetch-wpjm' ); ?></option>
			</select>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>
<?php
	}

	/**
	 * Fetch the API feed.
	 */
	public function fetch_feed( $url ) {

		$params = array(
			'format'    => 'json',
			'link'      => 1,
			'ip'        => urlencode( BC_Framework_Utils::get_user_ip() ),
			'useragent' => urlencode( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) ),
			'logo'      => 1,
			'full_desc' => 1,
		);
		$url = add_query_arg( $params, $url );

		$api_data = $this->get_api_data( $url, $_xml = false, $params );

		if ( is_wp_error( $api_data ) || empty( $api_data['jobs'] ) ) {

			if ( ! is_wp_error( $api_data ) && ! empty( $api_data['message'] ) ) {
				return new WP_Error( 'api_message', $api_data['message'] );
			} else {
				return new WP_Error( 'api_message', 'No jobs found. Try tweaking your feed settings.' );
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
			'title'           => '',
			'date'            => '',
			'url'             => '',
			'company'         => '',
			'city'            => array(),
			'zipcode'         => array(),
			'description'     => '',
			'price'           => '',
			'id'              => '',
			'onclick'         => '',
			'job_type'        => '',
			'major_category0' => '',
			'minor_category0' => '',
			'logo_url'        => '',
		);

		foreach ( (array) $items as $job ) {
			$job = wp_parse_args( $job, $defaults );

			$new_item = array();

			$new_item['provider_id'] = $provider['id'];
			$new_item['title']       = sanitize_text_field( $job['title'] );
			$new_item['date']        = sanitize_text_field( $job['date'] );
			$new_item['location']    = str_replace( ',', ', ', implode( ',', $job['city'] ) );
			$new_item['company']     = sanitize_text_field( $job['company'] );
			$new_item['description'] = GoFetch_Importer::format_description( $job['description'] );

			$new_item['logo']        = sanitize_text_field( $job['logo_url'] );
			$new_item['category']    = sanitize_text_field( $job['major_category0'] );

			$parsed_url = parse_url( $job['url'] );

			// If there's not 'http' scheme then the URL is relative - default to CV base URL.
			if ( empty( $parsed_url['scheme'] ) ) {
				$job['url'] = self::$base_url . $job['url'];
			}

			$new_item['link_atts'] = array(
				'javascript' => array(
					'onclick' => sanitize_text_field( $job['onclick'] ),
				),
			);

			$new_item['link'] = esc_url_raw( html_entity_decode( $job['url'] ) );
			$new_item['id']   = sanitize_text_field( $job['id'] );

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

		$provider['name'] = 'Talroo - Jobs & Careers';

		return array(
			'provider'    => $provider,
			'items'       => $new_items,
			'sample_item' => $sample_item,
		);
	}

	/**
	 * Set specific meta from Talroo.
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

		// Other link attributes.
		if ( ! empty( $item['link_atts'] ) ) {
			$params['meta']['_goft_link_atts'] = $item['link_atts'];
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

		// Enqueue other hooks for this provider.
		add_filter( 'goft_wpjm_read_more_link_attributes', array( $this, 'link_attributes' ), 10, 2 );
		add_filter( 'goft_wpjm_source_link_attributes', array( $this, 'link_attributes' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', function() {
			wp_enqueue_script( 'jobs2careers-click-tracking', '//api.Jobs2Careers.com/api/j2c.js', array(), GoFetch_Jobs()->version, true );
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

		if ( $goft_wpjm_options->jobs2careers_block_search_indexing ) {
			$robots['noindex'] = true;
		}
		return $robots;
	}

}
new GoFetch_Jobs2Careers_API_Feed_Provider();
