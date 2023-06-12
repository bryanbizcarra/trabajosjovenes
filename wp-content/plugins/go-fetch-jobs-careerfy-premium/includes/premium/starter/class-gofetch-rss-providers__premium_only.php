<?php
/**
 * Importer classes for providers that use RSS feeds to provide jobs.
 *
 * @package GoFetch/Admin/Premium/Starter/RSS Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class GoFetch_Premium_Starter_Providers {

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

	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Pro+ hooks.
	 */
	public function init_hooks() {
		add_filter( 'goft_wpjm_providers', array( __CLASS__, 'providers' ) );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'jobicy_feed_builder_fields' ) );
	}

	/**
	 * Retrieves a list of providers and their details.
	 */
	public static function providers( $providers ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'crunchboard.com' => array(
				'website'     => 'https://www.crunchboard.com',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-crunchboard.svg',
				'description' => 'Tech, Startup and Engineering Jobs',
				'feed' => array(
					'base_url'       => 'https://www.crunchboard.com/jobs.rss',
					'search_url'     => 'https://www.crunchboard.com',
					// Fixed RSS feeds examples.
					'fixed' => array(
						__( 'Latest Jobs ', 'gofetch-wpjm' ) => 'https://www.crunchboard.com/jobs.rss',
					),
					// Regex mappings for known/custom tags used in the feed title.
					'regexp_mappings' => array(
						'title' => array(
							'location'  => '/ at (.*)$/is', // e.g: Developer at Google (new york)
						),
					),
					'regexp_title' => '/^(.*)\sat\s/is',
				),
				'category' => __( 'IT/Development', 'gofetch-wpjm' ),
				'weight'   => 7,
			),
			'remoteok.io' => array(
				'website'     => 'https://remoteok.io',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-remotetok.jpg',
				'description' => 'Remote Jobs in Programming, Marketing, Design & More',
				'feed'        => array(
					'base_url'        => 'https://remoteok.io/remote-marketing-jobs.rss',
					'search_url'      => 'https://remoteok.io',
					// Feed URL query args.
					'query_args'  => array(
						'keyword' => array(
							'domain_k' => array(
								'placeholder' => 'e.g.: design, marketing, etc, or leave empty for any',
								'regex' => 'remote-(.*?)-jobs',
								'allowEmpty' => false, // @todo: allow true
							),
						),
					),
					'full_description' => true,
					'default' => true,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[@itemprop="description"]',
							'exclude'    => '//div[contains(@class,"company_profile ")]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//*[@itemprop="hiringOrganization"]',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//td[contains(@class,"company_and_position")]//div[contains(@class,"location")][1]',
						),
						'salary' => array(
							'nicename' => __( 'Salary', 'gofetch-wpjm' ),
							'query'    => '//td[contains(@class,"company_and_position")]//div[contains(@class,"location")][1]/following-sibling::div',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"company_profile ")]//img/@data-src',
						),
					),
				),
				'category' => __( 'Remote Work', 'gofetch-wpjm' ),
				'weight'   => 8,
			),
			'remotewoman.com' => array(
				'website'     => 'https://remotewoman.com',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-remotewomen.png',
				'description' => 'Remote Jobs - Remote jobs at trusted companies',
				'feed'        => array(
					'base_url'        => 'https://remotewoman.com/?feed=job_feed',
					'search_url'      => 'https://remotewoman.com/jobs',
					// Feed URL query args.
					'query_args'  => array(
						'keyword'  => array( 'search_keywords' => '' ),
					),
					'full_description' => true,
					'default' => true,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"job_listing-description")]',
							'exclude' => '//div[contains(@class, "job_application")]'
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//ul[contains(@class,"job-listing-meta")]//li[contains(@class,"job-company")]',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//ul[contains(@class,"job-listing-meta")]//li[contains(@class,"location")]',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"job-meta")]//img[contains(@class,"company_logo")]/@data-lazy-src',
						),
					),
				),
				'category' => __( 'Remote Work', 'gofetch-wpjm' ),
				'weight'   => 8,
			),
			'indeed.com' => array(
				'website'     => 'https://www.indeed.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-indeed.png',
				'description' => 'One search. All jobs - BEING DISCONTINUED - NOT RECOMMENDED',
				'feed'        => array(
					'base_url'   => 'https://www.indeed.com/rss',
					'search_url' => 'https://www.indeed.com',
					// Mappings for known/custom tags used in feed.
					'tag_mappings' => array(
						'geolocation' => 'point',
						'company'     => 'source',
					),
					// Regex mappings for known/custom tags used in the feed title.
					'regexp_mappings' => array(
						'title' => array(
							'company'  => '/.*\\s-\\s(.*?)\\s-\\s.*/is', // e.g: Developer - Google - San Francisco
							'location' => '/.*-.*\\s-\\s(.*?)$/is',      // e.g: Developer - Google - San Francisco
						),
					),
					'regexp_title' => '/^(.*?)\\s[-–]/is',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'  => array( 'q'     => '' ),
						'location' => array( 'l'     => '' ),
						'limit'    => array( 'limit' => '' ),
					),
					'pagination' => array(
						'params'  => array(
							'page'  => 'start',
							'limit' => 'limit',
						),
						'type'    => 'offset',
						'results' => 20,
					),
					'default' => true,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"jobsearch-jobDescriptionText")]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"jobsearch-InlineCompanyRating")]/div[2]',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"jobsearch-InlineCompanyRating")]/following-sibling::div[1]',
						),
						'salary' => array(
							'nicename' => __( 'Salary', 'gofetch-wpjm' ),
							'query'    => '//div[@id="salaryInfoAndJobType"]/span[contains(@class,"attribute_snippet")]',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//div[@class="icl-Card-body"]/a/img/@src',
						),
					),
				),
				'multi_region_match' => 'indeed',
				'region_domains' => array(
					'https://ar.indeed.com' => 'Argentina',
					'https://au.indeed.com' => 'Australia',
					'https://at.indeed.com' => 'Austria',
					'https://bh.indeed.com' => 'Bahrain',
					'https://be.indeed.com' => 'Belgium',
					'https://www.indeed.com.br' => 'Brazil',
					'https://ca.indeed.com' => 'Canada',
					'https://cl.indeed.com' => 'Chile',
					'https://cn.indeed.com' => 'China',
					'https://co.indeed.com' => 'Colombia',
					'https://cr.indeed.com' => 'Costa Rica',
					'https://cz.indeed.com' => 'Czech Republic',
					'https://dk.indeed.com' => 'Denmark',
					'https://ec.indeed.com' => 'Ecuador',
					'https://eg.indeed.com' => 'Egypt',
					'https://fi.indeed.com' => 'Finland',
					'https://fr.indeed.com' => 'France',
					'https://de.indeed.com' => 'Germany',
					'https://gr.indeed.com' => 'Greece',
					'https://hk.indeed.com' => 'Hong Kong',
					'https://hu.indeed.com' => 'Hungary',
					'https://www.indeed.co.in' => 'India',
					'https://id.indeed.com' => 'Indonesia',
					'https://ie.indeed.com' => 'Ireland',
					'https://il.indeed.com' => 'Israel',
					'https://it.indeed.com' => 'Italy',
					'https://jp.indeed.com' => 'Japan',
					'https://kw.indeed.com' => 'Kuwait',
					'https://lu.indeed.com' => 'Luxembourg',
					'https://malaysia.indeed.com' => 'Malaysia',
					'https://www.indeed.com.mx' => 'Mexico',
					'https://ma.indeed.com' => 'Morocco',
					'https://www.indeed.nl' => 'Netherlands',
					'https://nz.indeed.com' => 'New Zealand',
					'https://ng.indeed.com' => 'Nigeria',
					'https://no.indeed.com' => 'Norway',
					'https://om.indeed.com' => 'Oman',
					'https://pk.indeed.com' => 'Pakistan',
					'https://pa.indeed.com' => 'Panama',
					'https://pe.indeed.com' => 'Peru',
					'https://ph.indeed.com' => 'Philippines',
					'https://pl.indeed.com' => 'Poland',
					'https://pt.indeed.com' => 'Portugal',
					'https://qa.indeed.com' => 'Qatar',
					'https://ro.indeed.com' => 'Romania',
					'https://ru.indeed.com' => 'Russia',
					'https://sa.indeed.com' => 'Saudi Arabia',
					'https://sg.indeed.com' => 'Singapore',
					'https://za.indeed.com' => 'South Africa',
					'https://kr.indeed.com' => 'South Korea',
					'https://es.indeed.com' => 'Spain',
					'https://se.indeed.com' => 'Sweden',
					'https://www.indeed.ch' => 'Switzerland',
					'https://tw.indeed.com' => 'Taiwan',
					'https://th.indeed.com' => 'Thailand',
					'https://tr.indeed.com' => 'Turkey',
					'https://ua.indeed.com' => 'Ukraine',
					'https://www.indeed.ae' => 'United Arab Emirates',
					'https://www.indeed.co.uk' => 'United Kingdom',
					'https://www.indeed.com' => 'United States',
					'https://uy.indeed.com' => 'Uruguay',
					'https://ve.indeed.com' => 'Venezuela',
					'https://vn.indeed.com' => 'Vietnam',
				),
				'region_default' => 'https://www.indeed.com',
				'multi-region'   => true,
				'category'       => __( 'Generic', 'gofetch-wpjm' ),
				'crsf'           => true,
				'deprecated'     => true,
				'weight'         => 10,
			),
			'jobicy.com' => array(
				'website'     => 'https://jobicy.com/',

				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-jobicy.svg',
				'description' => 'The best Remote Jobs in programming, design, marketing, sales.',
				'feed'        => array(
					'base_url'   => 'https://jobicy.com/?feed=job_feed',
					'search_url' => 'https://jobicy.com/jobs',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'  => array( 'search_keywords' => '' ),
						'location' => array( 'search_location' => array(
							'placeholder' => __( 'e.g: Canada, UK', 'gofetch-wpjm' ),
							'default_value' => '',
						) ),
						// Custom.
						'job_categories' => array( 'job_categories' => '' ),
						'job_types'      => array( 'job_types' => '' ),
					),
					'default_mappings' => array(
						//'encoded' => 'post_content',
					),
					'full_description' => true,
					'default' => true,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"job__desc")]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//a[contains(@class,"flex items-center inline-block")]',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//dd[contains(@class,"style-4 tmz-rj")]',
						),
						'salary' => array(
							'nicename' => __( 'Salary', 'gofetch-wpjm' ),
							'query'    => '//dt[contains(@class,"style-4 opacity-60 sm-mb2 lg-mb3")][contains(text(),"Salary")]/following-sibling::dd',
							'currency' => 'USD',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"relative sm-ml-auto sm-mr-auto md-mr5")]//img/@src',
						),
					),
				),
				'category' => __( 'Remote Work', 'gofetch-wpjm' ),
				'weight' => 9,
			),
			'remotive.io' => array(
				'website'     => 'https://remotive.io/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-remotive.svg',
				'description' => 'The best Remote Jobs in programming, design, marketing, sales.',
				'feed'        => array(
					'url_match'  => 'remotive.io/remote-jobs',
					'base_url'   => 'https://remotive.io/remote-jobs/feed?limit=50',
					'search_url' => 'https://remotive.io/remote-jobs/',
					'regexp_mappings' => array(
						'description' => array(
							'location' => '/Hiring from:(.*?)$/mis', // e.g: Hiring from: San Francisco
						),
					),
					'default' => true,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"tw-mt-8")]/div[contains(@class,"left")]',
							'exclude' => '//div[contains(@class, "left")]//div[contains(@class, "tw-mt-8")]'
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//div[@id="company-panel"]//span',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"tw-sticky tw-flex")]//div[@id="job-meta-panel"]//tr[1]//td[2]//span[2]',
						),
						'salary' => array(
							'nicename' => __( 'Salary', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"tw-sticky tw-flex")]//div[@id="job-meta-panel"]//tr[1]//td[1]//span[2]',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//div[@id="company-panel"]//img/@data-lazyload',
						),
					),
				),
				'category'     => __( 'Remote Work', 'gofetch-wpjm' ),
				'weight' => 9,
			),
			'rss.jobsearch.monster.com' => array(
				'website'     => 'https://www.monster.com/jobs',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-monster.svg',
				'description' => 'Jobs in US, Canada',
				'feed' => array(
					'base_url'   => 'http://rss.jobsearch.monster.com/rssquery.ashx',
					'search_url' => 'https://www.monster.com/jobs',
					// Regex mappings for known/custom tags used in the feed description.
					'regexp_mappings' => array(
						'location' => '/(.*?),/is',  // e.g: NY-New York, blah blah
					),
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword'  => array( 'q' => '' ),
						'location' => array(
							'cy' => array(
								'placeholder'   => 'e.g: us, ca, uk, fr, de or nl',
								'default_value' => 'us',
							),
						),
					),
					// Fixed RSS feeds examples.
					'examples' => array(
						__( 'Latest Jobs ', 'gofetch-jobs' )               => 'http://rss.jobsearch.monster.com/rssquery.ashx',
						__( 'Latest Design Jobs', 'gofetch-jobs' )         => 'http://rss.jobsearch.monster.com/rssquery.ashx?brd=1&q=design',
						__( 'Latest Teachers Jobs in US', 'gofetch-jobs' ) => 'http://rss.jobsearch.monster.com/rssquery.ashx?brd=1&q=teacher&cy=us',
					),
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"descriptionstyles__DescriptionContainer-sc-13ve12b-0")]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//h2[contains(@class,"headerstyle__JobViewHeaderCompany-sc-1ijq9nh-6")]',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//h3[contains(@class,"headerstyle__JobViewHeaderLocation-sc-1ijq9nh-4")]',
						),
						'salary' => array(
							'nicename' => __( 'Salary', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"detailsstyles__DetailsTableRow-sc-1deoovj-2")]//span/span',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"company-logostyles__LogoContainer-sc-11vzkdn-1")]//img/@src',
						),
					),
				),
				'weight'   => 9,
				'category' => __( 'Generic', 'gofetch-jobs' ),
			),
			'monster.com.hk' => array(
				'website'     => 'https://www.monster.com.hk/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-monster-asia.svg',
				'description' => 'Asia - IT Jobs, Sales Jobs',
				'feed' => array(
					'base_url'   => 'https://jobsearch.monster.com.hk/rss_jobs.html',
					'search_url' => 'https://www.monster.com.hk/job-search.html',
					'feeds_url'  => 'https://www.monster.com.hk/jobsearch/rss-feed.html',
					// Regex mappings for known/custom tags used in the feed description.
					'regexp_mappings' => array(
						'company'  => '/Company:.*?<.*?>(.*?)<.*?>/is',  // e.g: <b>Company: </b><br/> Google <br/>
						'location' => '/Location:.*?<.*?>(.*?)<.*?>/is', // e.g: <b>Location: </b><br/> San Francisco <br/>
					),
					// Fixed RSS feeds examples.
					'fixed' => array(
						__( 'Latest Jobs ', 'gofetch-wpjm' )            => 'https://jobsearch.monster.com.hk/rss_jobs.html',
						__( 'Latest IT Jobs', 'gofetch-wpjm' )          => 'https://jobsearch.monster.com.hk/rss_jobs.html?cat=22',
						__( 'Latest Health Care Jobs', 'gofetch-wpjm' ) => 'https://jobsearch.monster.com.hk/rss_jobs.html?cat=9',
					),
				),
				'multi_region_match' => 'monster.com.hk',
				'multi-region' => sprintf( __( 'Other countries: %s', 'gofetch-wpjm' ), '<a href="https://www.monster.com.hk/destination_china.html" target="_blank">China</a>, <a href="https://www.monster.com.sg/" target="_blank">Singapore</a>, <a href="https://www.monster.com.ph/" target="_blank">Philipines</a>, <a href="https://www.monster.co.th/" target="_blank">Thailand</a>, <a href="https://www.monster.com.vn/" target="_blank">Vietnam</a>, ' .
				'<a href="https://www.monster.co.id/" target="_blank">Indonesia</a>, <a href="https://www.monster.com.my/" target="_blank">Malaysia</a>, <a href="https://www.monsterindia.com/" target="_blank">India</a>, <a href="https://www.monstergulf.com/" target="_blank">Gulf</a>.') .
								'<br/><br/>' . sprintf( __( 'To apply these instructions to other country you can usually replace replace the domain part <code>%1$s</code> with the country domain name you\'re interested with.', 'gofetch-wpjm' ), 'https://jobsearch.monster.com.hk' ) .
								'<br/><br/>' . sprintf( __( '<strong>e.g:</strong> For jobs in <em>Singapure</em> you would use <code>%1$s</code>[...]', 'gofetch-wpjm' ), 'https://jobsearch.monster.com.sg/' ) .
									'<br><br/>' . __( '<em>Note:</em> If replacing the domain part does not work for a specific country please refer to the provider site to check the exact domain used for their RSS feeds.', 'gofetch-wpjm' ),
				'quality'  => 'low',
				'weight'   => 8,
				'category' => __( 'Generic', 'gofetch-wpjm' ),
			),
			'reed.co.uk' => array(
				'website'     => 'https://www.reed.co.uk/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-reed.gif',
				'description' => 'Jobs and Recruitment on reed.co.uk, the UK\'s #1 job site',
				'feed' => array(
					'base_url'   => 'https://www.reed.co.uk/jobs/rss',
					'search_url' => 'https://www.reed.co.uk/jobs',
					// Regex mappings for known/custom tags used in the feed description.
					'regexp_mappings' => array(
						'location' => '/Location.*?:(.*?)<.*?>/is', // e.g: Location: San Francisco <br/>
					),
					// Feed URL query args.
					'query_args'  => array(
						'keyword'  => array( 'keywords' => '' ),
						'location' => array( 'location' => '' ),
					),
					'full_description' => true,
					'default' => true,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//span[@itemprop="description"]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//span[@itemprop="hiringOrganization"]//span',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//a[@itemprop="jobLocation"]',
						),
						'salary' => array(
							'nicename' => __( 'Salary', 'gofetch-wpjm' ),
							'query'    => '//span[@itemprop="baseSalary"]',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//aside[contains(@class,"logo-and-options")]//a[contains(@class,"logo-wrap--border-bottom")]//img/@data-src',
						),
					),
				),
				'category' => __( 'Generic', 'gofetch-wpjm' ),
				'weight'   => 8,
			),
			'healthcareercenter.com' => array(
				'website'     => 'https://jobs.healthcareercenter.com',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-healthcareercenter.png',
				'description' => 'Health Career Center',
				'feed'        => array(
					'base_url'   => 'https://jobs.healthcareercenter.com/jobs/?display=rss',
					'search_url' => 'https://jobs.healthcareercenter.com/jobs',
					'regexp_mappings' => array(
						'title' => array(
							'company' => '/\|(.*?)$/is',      // e.g: Developer | Google | San Francisco
						),
						'description' => array(
							'location' => '/(.*?,.*?),.*?/is', // e.g: San Francisco, California,
						),
					),
					'regexp_title' => '/^(.*?)\\s[|]/is',
					// Feed URL query args.
					'query_args'  => array(
						'keyword'  => array( 'keywords' => '' ),
						'limit'    => array( 'resultsPerPage' => '25', 'default' => '25' ),
					),
					'full_description' => true,
					'default' => true,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"bti-jd-main-container")]',
							'exclude' => '//div[contains(@class,"bti-grid-searchDetails-side")]'
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//h2[@class="bti-jd-employer-title"]//a',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"bti-grid-searchDetails-side")]//*[contains(text(),"Location:")]/following-sibling::text()[1]',
						),
						'salary' => array(
							'nicename' => __( 'Salary', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"bti-grid-searchDetails-side")]//*[contains(text(),"Salary:")]/following-sibling::text()[1]',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"bti-jd-employer-container")]//img/@src',
						),
					),
				),
				'category' => __( 'Healthcare', 'gofetch-wpjm' ),
				'weight'   => 7,
			),
			'hospitalcareers.com' => array(
				'website'     => 'https://www.hospitalcareers.com',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-hospitalcareers.jpg',
				'description' => 'Healthcare Jobs & Hospital Jobs',
				'feed'        => array(
					'base_url'        => 'https://www.hospitalcareers.com/rss',
					'search_url'      => 'https://www.hospitalcareers.com/jobs',
					'regexp_mappings' => array(
						'company'  => '/\n(.*?)\n/is',  // Google (first line)
						'location' => '/^(.*?)\n/is',    // Mountain View (second line)
					),
					// Feed URL query args.
					'query_args'  => array(
						'keyword'  => array( 'keywords' => '' ),
						'limit'    => array( 'limit' => '25' ),
					),
					'full_description' => true,
					'default' => true,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"details-body")]',
							'exclude'    => '//div[contains(@class,"alert-form__jobpage")]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//li[contains(@class,"listing-item__info--item-company")]',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//li[contains(@class,"listing-item__info--item-location")]',
						),
						'salary' => array(
							'nicename' => __( 'Salary', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"listing-item__info--item-salary-range")]',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//img[contains(@class,"profile__img-company")]/@src',
						),
					),
				),
				'category' => __( 'Healthcare', 'gofetch-wpjm' ),
				'weight'   => 7,
			),
			'healthcarejobsite.com' => array(
				'website'     => 'https://www.healthcarejobsite.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-healthcarejobs.png',
				'description' => 'Healthcare Jobs',
				'feed'        => array(
					'base_url'   => 'https://www.healthcarejobsite.com/jobs/search/rss',
					'search_url' => 'https://www.healthcarejobsite.com/jobs/search?k=&l=',
					'regexp_mappings' => array(
						'title' => array(
							'location' => '/.*-(.*?)$/is', // e.g: Developer - San Francisco
						),
					),
					'regexp_title' => '/(.*?)\s-.*?/is',
					// Feed URL query args.
					'query_args'  => array(
						'keyword'  => array( 'k' => '' ),
						'location' => array( 'l' => '' ),
						'limit'    => array( 'ps' => '50' ),
					),
					'default' => true,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[@itemprop="description"]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//span[@itemprop="hiringOrganization"]',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//span[@itemprop="jobLocation"]',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//li[contains(@class,"list-group-item")]//img[@itemprop="image"]/@src',
						),
					),
				),
				'category' => __( 'Healthcare', 'gofetch-wpjm' ),
				'weight'   => 7,
			),
			'salesheads.com' => array(
				'website'     => 'https://www.salesheads.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-salesheads.png',
				'description' => 'Sales Jobs',
				'feed'        => array(
					'base_url'   => 'https://www.salesheads.com/jobs/search/rss',
					'search_url' => 'https://www.salesheads.com/jobs/search',
					'regexp_mappings' => array(
						'title' => array(
							'location' => '/.*-.*\\s-\\s(.*?)$/is',      // e.g: Developer - Google - San Francisco
						),
					),
					'regexp_title' => '/(.*?)\s-.*?/is',
					// Feed URL query args.
					'query_args'  => array(
						'keyword'  => array( 'k' => '' ),
						'location' => array( 'l' => '' ),
						'limit'    => array( 'ps' => '50' ),
					),
					'default' => true,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[@itemprop="description"]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//span[@itemprop="hiringOrganization"]',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//span[@itemprop="jobLocation"]',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//li[contains(@class,"list-group-item")]//img[@itemprop="image"]/@src',
						),
					),
				),
				'category' => __( 'Sales', 'gofetch-wpjm' ),
				'weight'   => 7,
			),
			'salesjobs.com' => array(
				'website'     => 'https://www.salesjobs.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-salesjobs.png',
				'description' => 'Sales Jobs',
				'feed'        => array(
					'base_url'   => 'https://www.salesjobs.com/rss/feeder',
					'search_url' => 'https://www.salesjobs.com',
					// Regex mappings for known/custom tags used in the feed title.
					'regexp_mappings' => array(
						'title' => array(
							'company'  => '/.*\\s-\\s(.*?)\\s-\\s.*/is', // e.g: Developer - Google - San Francisco
							'location' => '/.*-.*\\s-\\s(.*?)$/is',      // e.g: Developer - Google - San Francisco
						),
					),
					'regexp_title' => '/(.*?)\s-.*?/is',
					// Feed URL query args.
					'query_args'  => array(
						'keyword'  => array( 'k' => 'sale' ),
						'location' => array( 'l' => '' ),
					),
					'default' => true,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[@id="jobDescription"]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//div[@id="jobDetails"]//div[2]/h2',
						),
						'salary' => array(
							'nicename' => __( 'Salary', 'gofetch-wpjm' ),
							'query'    => '//div[@id="jobDetails"]//div[@class="third"][1]//strong[1]',
						),
					),
				),
				'category' => __( 'Sales', 'gofetch-wpjm' ),
				'weight'   => 7,
			),
			'myjobmag.com' => array(
				'website'     => 'https://www.myjobmag.com',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-myjobmag.png',
				'description' => 'MyJobMag Jobs In Africa',
				'feed' => array(
					'base_url'   => 'https://www.myjobmag.com/feeds',
					'search_url' => 'https://www.myjobmag.com',
					'feeds_url' => 'https://www.myjobmag.com/feeds/',
					'feeds_url_desc' => __( 'See full list of RSS feeds for different countries here', 'gofetch-wpjm' ),
					// Fixed RSS feeds examples.
					'fixed' => array(
						'Nigeria'                                  => 'h1',
						__( 'a. Summarized Feed', 'gofetch-wpjm' ) => 'https://www.myjobmag.com/jobsxml.xml',
						__( 'b. Detailed Feed', 'gofetch-wpjm' )   => 'https://www.myjobmag.com/jobsxml_by_categories.xml',
						__( 'c. Aggregate Feed', 'gofetch-wpjm' )  => 'https://www.myjobmag.com/aggregate_feed.xml',
						'Ghana'                                    => 'h1',
						__( 'd. Summarized Feed', 'gofetch-wpjm' ) => 'https://www.myjobmagghana.com/jobsxml.xml',
						__( 'e. Detailed Feed', 'gofetch-wpjm' )   => 'https://www.myjobmagghana.com/jobsxml_by_categories.xml',
						__( 'f. Aggregate Feed', 'gofetch-wpjm' )  => 'https://www.myjobmagghana.com/aggregate_feed.xml',
						'Kenya'                                    => 'h1',
						__( 'g. Summarized Feed', 'gofetch-wpjm' ) => 'https://www.myjobmag.co.ke/jobsxml.xml',
						__( 'h. Detailed Feed', 'gofetch-wpjm' )   => 'https://www.myjobmag.co.ke/jobsxml_by_categories.xml',
						__( 'i. Aggregate Feed', 'gofetch-wpjm' )  => 'https://www.myjobmag.co.ke/aggregate_feed.xml',
						'South Africa'                             => 'h1',
						__( 'j. Summarized Feed', 'gofetch-wpjm' ) => 'https://www.myjobmag.co.za/jobsxml.xml',
						__( 'k. Detailed Feed', 'gofetch-wpjm' )   => 'https://www.myjobmag.co.za/jobsxml_by_categories.xml',
						__( 'l. Aggregate Feed', 'gofetch-wpjm' )  => 'https://www.myjobmag.co.za/aggregate_feed.xml',
					),
					// Regex mappings for known/custom tags used in the feed title.
					'regexp_mappings' => array(
						'title' => array(
							'company'  => '/ at (.*)$/is', // e.g: Developer - Google - San Francisco
						),
					),
					'regexp_title' => '/(.*)\sat\s.*$/is',
					'full_description' => true,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"job-details")][1]',
							'exclude'    => '//a[contains(text(),"go to method of application")]',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//ul[contains(@class,"job-key-info")][1]//span[contains(@class,"jkey-title")][contains(text(),"Location")]/following-sibling::span',
						),
					),
				),
				'multi_region_match' => 'myjobmag',
				'weight'       => 8,
				'category'     => __( 'Generic', 'gofetch-wpjm' ),
			),
			'higheredjobs.com' => array(
				'website'     => 'https://www.higheredjobs.com',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-higheredjobs.png',
				'description' => 'Jobs in Higher Education',
				'feed' => array(
					'base_url'   => 'https://www.higheredjobs.com/feeds',
					'search_url' => 'https://www.higheredjobs.com',
					'feeds_url' =>  'https://www.higheredjobs.com/rss/',
					'feeds_url_desc' => sprintf( __( 'See full list of RSS feeds <a href="%s" target="_blank" rel="noopener noreferer">here</a>', 'gofetch-wpjm' ), 'https://www.higheredjobs.com/rss/' ),
					// Fixed RSS feeds examples.
					'fixed' => array(
							   'Administrative Categories'                                  => 'h1',
							__( 'Academic Advising', 'gofetch-wpjm' )                       => 'https://www.higheredjobs.com/rss/categoryFeed.cfm?catID=141',
							__( 'Diversity and Multicultural Affairs', 'gofetch-wpjm' )     => 'https://www.higheredjobs.com/rss/categoryFeed.cfm?catID=35',
							__( 'Arts and Museum Administration', 'gofetch-wpjm' )          => 'https://www.higheredjobs.com/rss/categoryFeed.cfm?catID=36',
							__( 'Tutors and Learning Resources', 'gofetch-wpjm' )           => 'https://www.higheredjobs.com/rss/categoryFeed.cfm?catID=216',
							   'Executive Categories'                                       => 'h1',
							__( 'Administrative Vice Presidents', 'gofetch-wpjm' )          => 'https://www.higheredjobs.com/rss/categoryFeed.cfm?catID=164',
							__( 'Communications Deans', 'gofetch-wpjm' )                    => 'https://www.higheredjobs.com/rss/categoryFeed.cfm?catID=245',
							__( 'Engineering Deans', 'gofetch-wpjm' )                       => 'https://www.higheredjobs.com/rss/categoryFeed.cfm?catID=247',
							__( 'Presidents and Chancellors', 'gofetch-wpjm' )              => 'https://www.higheredjobs.com/rss/categoryFeed.cfm?catID=4',
							   'Faculty Categories'                                         => 'h1',
							__( 'Animal Science', 'gofetch-wpjm' )                          => 'https://www.higheredjobs.com/rss/categoryFeed.cfm?catID=51',
							__( 'Nutrition and Dietetics', 'gofetch-wpjm' )                 => 'https://www.higheredjobs.com/rss/categoryFeed.cfm?catID=185',
							__( 'Food Science', 'gofetch-wpjm' )                            => 'https://www.higheredjobs.com/rss/categoryFeed.cfm?catID=53',
							__( 'Veterinary Medicine', 'gofetch-wpjm' )                     => 'https://www.higheredjobs.com/rss/categoryFeed.cfm?catID=57',
							__( 'Anthropology', 'gofetch-wpjm' )                            => 'https://www.higheredjobs.com/rss/categoryFeed.cfm?catID=78',
					),
					'regexp_mappings' => array(
						'description' => array(
							'location' => '/\((.*?)\)$/is',      // e.g: University Name (Chicago)
						),
					),
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[@id="jobDesc"]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//div[@id="jobLocation"]//div[contains(@class,"job-inst")]',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//div[@id="jobLocation"]//div[contains(@class,"job-loc")]//span/following-sibling::text()[1]',
						),
						'logo' => array(
							'nicename' => __( 'Logo', 'gofetch-wpjm' ),
							'query'    => '//div[@id="ImageDiv_1"]//img/@src',
						),
					),
				),
				'weight'   => 8,
				'category' => __( 'Education', 'gofetch-wpjm' ),
			),
		);
		return array_merge( $providers, $new_providers );
	}

	/**
	 * Outputs specific Jobicy feed parameter fields.
	 */
	public function jobicy_feed_builder_fields( $provider ) {

		if ( 'jobicy.com' !== $provider ) {
			return;
		}

		$field_name = 'job_categories';
		?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Job Categories', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The job categories to filter.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: supporting, dev, marketing, etc.', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>
		<?php
		$field_name = 'job_types';
		?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Job Types', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'The job types to filter.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label><span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<input type="text" class="regular-text" name="feed-<?php echo esc_attr( $field_name ); ?>" data-qarg="feed-param-<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo __( 'e.g.: full-time, freelance, contract, etc.', 'gofetch-wpjm' ); ?>">
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>
		<?php
	}

}

function GoFetch_Premium_Starter_Providers() {
	return GoFetch_Premium_Starter_Providers::instance();
}

GoFetch_Premium_Starter_Providers();
