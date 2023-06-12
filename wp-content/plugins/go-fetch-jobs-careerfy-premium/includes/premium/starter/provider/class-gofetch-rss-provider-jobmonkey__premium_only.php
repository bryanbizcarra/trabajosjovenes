<?php
/**
 * Importer classes for providers that use RSS feeds to provide jobs.
 *
 * @package GoFetch/Admin/Premium/Starter/Job Monkey RSS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class GoFetch_Premium_Starter_Provider_JobMonkey {

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
		add_filter( 'goft_wpjm_providers', array( $this, 'providers' ), 20 );
		add_action( 'goft_wpjm_feed_builder_fields', array( $this, 'feed_builder_fields' ) );
	}

	/**
	 * Retrieves a list of providers and their details.
	 */
	public function providers( $providers ) {
		global $goft_wpjm_options;

		$new_providers = array(
			'jobmonkeyjobs.com' => array(
				'website'     => 'https://www.jobmonkeyjobs.com',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-jobmonkey.png',
				'description' => 'The Coolest Jobs on Earth',
				'feed'        => array(
					'base_url'        => 'https://www.jobmonkeyjobs.com/main/rss/feed?status=1',
					'search_url'      => 'https://www.jobmonkeyjobs.com',
					'regexp_mappings' => array(
						'company'  => '/(.*?)\.\s.*/is', // e.g: Google. San Francisco -
					),
					// Feed URL query args.
					'query_args'  => array(
						'keyword'  => array( 'keyword' => '' ),
						'location'  => array( 'location' => '' ),
						// Custom.
						'country'  => array( 'country' => '' ),
						//'region'    => array( 'region'  => '' ),
						'industry'    => array( 'industry'  => '' ),
					),
					'default' => true,
				),
				'special' => array(
					'scrape' => array(
						'description' => array(
							'nicename' => __( 'Full Job Description', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"search-job-result")]//td[@itemprop="description"]',
						),
						'company' => array(
							'nicename' => __( 'Company', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"search-job-result")]//td[contains(@class,"vac_item_employer")]/following-sibling::td[1]/a',
						),
						'location' => array(
							'nicename' => __( 'Location', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"search-job-result")]//td[contains(@class,"vac_item_city")]/following-sibling::td[1]',
						),
						'salary' => array(
							'nicename' => __( 'Salary', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"search-job-result")]//td[contains(@class,"vac_item_salary-text")]/following-sibling::td[1]',
						),
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//img[contains(@class,"logo_vacancy")]/img/@src',
						),
					),
				),
				'multi_region_match'  => 'jobmonkey',
				'region_param_domain' => 'country',
				'region_domains'      => $this->countries(),
				'region_default'      => '5343',
				'category' => __( 'Generic', 'gofetch-wpjm' ),
				'weight'   => 7,
			),
		);

		return array_merge( $providers, $new_providers );
	}


	/**
	 * Outputs specific Indeed feed parameter fields.
	 */
	public function feed_builder_fields( $provider ) {

		if ( 'jobmonkeyjobs.com' !== $provider ) {
			return;
		}

		$field = array(
			'title'   => __( 'Region', 'gofetch-wpjm' ),
			'name'    => 'feed-region',
			'type'    => 'select',
			'choices' => array(),
			'class'   => 'regular-text',
			'extra'   => array(
				'data-qarg' => 'feed-param-region',
				'multiple'  => 'multiple',
				'style'     => "width: 550px;",
			),
			'default' => '',
		);
?>
		<?php $field_name = 'region'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Companies', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Choose the companies that you want to pull jobs from.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label>
			<span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<?php echo scbForms::input( $field, array() ) ?>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
			<span><?php _e( '<strong>Note:</strong> The companies above, are directly related with the industries that you\'ve selected on the settings page.', 'gofetch-wpjm' ); ?></span>
		</p>

		<div class="clear"></div>

		<?php
		$field = array(
			'title'   => __( 'Industry', 'gofetch-wpjm' ),
			'name'    => 'feed-industry',
			'type'    => 'select',
			'choices' => $this->industries(),
			'class'   => 'regular-text',
			'extra'   => array(
				'data-qarg' => 'feed-param-industry',
				'multiple'  => 'multiple',
				'style'     => "width: 550px;",
			),
			'default' => '',
		);
?>
		<?php $field_name = 'industry'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Industries', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Choose the industry that you want to pull jobs from.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label>
			<span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<?php echo scbForms::input( $field, array() ) ?>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
		</p>

		<div class="clear"></div>
<?php
	}

	/**
	 * Industries available for this provider.
	 */
	public static function industries() {

		$categories = array(
			'5294' => 'Cool Jobs',
			'5960' => 'Summer Jobs',
			'6017' => 'Work from Anywhere / Work from Home',
			'5984' => 'Gigs',
			'5983' => 'Digital Nomad',
			'5961' => 'Internships',
			'5342' => 'Work Abroad',
			'5283' => 'Accounting / Finance',
			'5284' => 'Advertising / Marketing / PR',
			'5285' => 'Agriculture / Forestry',
			'5286' => 'Airline / Aviation / Aerospace',
			'5287' => 'Animal Care',
			'5966' => 'Automotive',
			'5288' => 'Banking / Financial Services',
			'5289' => 'Beauty / Cosmetology / Spa',
			'5290' => 'Biotechnology / Pharma',
			'5291' => 'Broadcasting / Radio',
			'5292' => 'Casino / Gaming',
			'5293' => 'Childcare / Nanny / Au Pair',
			'5967' => 'Consulting',
			'5295' => 'Credit / Loan / Collections',
			'5296' => 'Cruise Line',
			'5297' => 'Diving',
			'5298' => 'Driver / Courier',
			'5299' => 'Education / Teaching / Admin',
			'5300' => 'Employment / Staffing / HR',
			'5301' => 'Energy - Oil / Gas',
			'5302' => 'Energy - Renewable',
			'5303' => 'Entertainment / Music',
			'5962' => 'Entrepreneurial',
			'5304' => 'Environmental / Ecological',
			'5305' => 'ESL / TESL',
			'5306' => 'Events',
			'5307' => 'Exercise / Fitness',
			'5308' => 'Faith Based',
			'5309' => 'Film / TV / Modeling',
			'5310' => 'Fisheries / Seafood',
			'5311' => 'Food / Beverage / Wine',
			'5312' => 'Government / Civil Service',
			'5313' => 'Green Jobs',
			'5314' => 'Healthcare / Medical',
			'5315' => 'Hospitality',
			'5316' => 'Hotel / Resort / Lodging',
			'6021' => 'Housekeeping / Janitorial',
			'5317' => 'Internet / E-Commerce',
			'5318' => 'IT / Software Development',
			'5319' => 'Law Enforcement / Fire',
			'5320' => 'Legal',
			'5972' => 'Logistics / Distribution Center',
			'5321' => 'Maritime',
			'5322' => 'Media / Journalism / Publishing',
			'5323' => 'Museum',
			'6018' => 'Mystery Shopping / Secret Shopper',
			'5324' => 'Not for Profit / Charitable',
			'5325' => 'Nursing',
			'5326' => 'Nutrition',
			'5328' => 'Photography / Videography',
			'5329' => 'Real Estate / Property Mgt',
			'5330' => 'Recreation / Parks',
			'5982' => 'Remote Work',
			'5331' => 'Restaurant / Food Service',
			'5332' => 'Retail / Merchandising',
			'5333' => 'Sales',
			'5334' => 'Security / Surveillance',
			'6020' => 'Senior Care',
			'5973' => 'Shared Economy',
			'5335' => 'Ski / Winter Sports',
			'5965' => 'Social Media',
			'5336' => 'Sports',
			'5337' => 'Summer Camp',
			'5338' => 'Theme Park',
			'5339' => 'Transportation / Freight',
			'5340' => 'Travel / Tourism',
			'6019' => 'Tutoring',
			'5341' => 'Video Game',
			'5327' => 'Other Industries',

		);
		return $categories;
	}

	/**
	 * Categories available for this provider.
	 */
	public static function countries() {

		$countries = array(
			'5343' => 'United States',
			'5344' => 'Australia',
			'5345' => 'Brazil',
			'5346' => 'Canada',
			'5347' => 'China',
			'5348' => 'Ireland',
			'5349' => 'Japan',
			'5350' => 'Mexico',
			'5351' => 'United Kingdom',
			'5352' => 'Abkhazia',
			'5353' => 'Afghanistan',
			'5354' => 'Aland',
			'5355' => 'Albania',
			'5356' => 'Algeria',
			'5357' => 'American Samoa',
			'5358' => 'Andorra',
			'5359' => 'Angola',
			'5360' => 'Anguilla',
			'5361' => 'Antigua and Barbuda',
			'5362' => 'Argentina',
			'5363' => 'Armenia',
			'5364' => 'Aruba',
			'5365' => 'Ascension',
			'5366' => 'Ashmore and Cartier Islands',
			'5367' => 'Australian Antarctic Territory',
			'5368' => 'Austria',
			'5369' => 'Azerbaijan',
			'5370' => 'Bahamas, The',
			'5371' => 'Bahrain',
			'5372' => 'Baker Island',
			'5373' => 'Bangladesh',
			'5374' => 'Barbados',
			'5375' => 'Belarus',
			'5376' => 'Belgium',
			'5377' => 'Belize',
			'5378' => 'Benin',
			'5379' => 'Bermuda',
			'5380' => 'Bhutan',
			'5381' => 'Bolivia',
			'5382' => 'Bosnia and Herzegovina',
			'5383' => 'Botswana',
			'5384' => 'Bouvet Island',
			'5385' => 'British Antarctic Territory',
			'5386' => 'British Indian Ocean Territory',
			'5387' => 'British Sovereign Base Areas',
			'5388' => 'British Virgin Islands',
			'5389' => 'Brunei',
			'5390' => 'Bulgaria',
			'5391' => 'Burkina Faso',
			'5392' => 'Burundi',
			'5393' => 'Cambodia',
			'5394' => 'Cameroon',
			'5395' => 'Cape Verde',
			'5396' => 'Cayman Islands',
			'5397' => 'Central African Republic',
			'5398' => 'Chad',
			'5399' => 'Chile',
			'5400' => 'Christmas Island',
			'5401' => 'Clipperton Island',
			'5402' => 'Cocos (Keeling) Islands',
			'5403' => 'Colombia',
			'5404' => 'Comoros',
			'5405' => 'Congo, (Congo – Brazzaville)',
			'5406' => 'Congo, (Congo – Kinshasa)',
			'5407' => 'Cook Islands',
			'5408' => 'Coral Sea Islands',
			'5409' => 'Costa Rica',
			'5410' => 'Cote d\'Ivoire (Ivory Coast)',
			'5411' => 'Croatia',
			'5412' => 'Cuba',
			'5413' => 'Cyprus',
			'5414' => 'Czech Republic',
			'5415' => 'Denmark',
			'5416' => 'Djibouti',
			'5417' => 'Dominica',
			'5418' => 'Dominican Republic',
			'5419' => 'Ecuador',
			'5420' => 'Egypt',
			'5421' => 'El Salvador',
			'5422' => 'Equatorial Guinea',
			'5423' => 'Eritrea',
			'5424' => 'Estonia',
			'5425' => 'Ethiopia',
			'5426' => 'Falkland Islands (Islas Malvinas)',
			'5427' => 'Faroe Islands',
			'5428' => 'Fiji',
			'5429' => 'Finland',
			'5430' => 'France',
			'5431' => 'French Guiana',
			'5432' => 'French Polynesia',
			'5433' => 'French Southern and Antarctic Lands',
			'5434' => 'Gabon',
			'5435' => 'Gambia, The',
			'5436' => 'Georgia',
			'5437' => 'Germany',
			'5438' => 'Ghana',
			'5439' => 'Gibraltar',
			'5440' => 'Greece',
			'5441' => 'Greenland',
			'5442' => 'Grenada',
			'5443' => 'Guadeloupe',
			'5444' => 'Guam',
			'5445' => 'Guatemala',
			'5446' => 'Guernsey',
			'5447' => 'Guinea',
			'5448' => 'Guinea-Bissau',
			'5449' => 'Guyana',
			'5450' => 'Haiti',
			'5451' => 'Heard Island and McDonald Islands',
			'5452' => 'Honduras',
			'5453' => 'Hong Kong',
			'5454' => 'Howland Island',
			'5455' => 'Hungary',
			'5456' => 'Iceland',
			'5457' => 'India',
			'5458' => 'Indonesia',
			'5459' => 'Iran',
			'5460' => 'Iraq',
			'5461' => 'Isle of Man',
			'5462' => 'Israel',
			'5463' => 'Italy',
			'5464' => 'Jamaica',
			'5465' => 'Jarvis Island',
			'5466' => 'Jersey',
			'5467' => 'Johnston Atoll',
			'5468' => 'Jordan',
			'5469' => 'Kazakhstan',
			'5470' => 'Kenya',
			'5471' => 'Kingman Reef',
			'5472' => 'Kiribati',
			'5473' => 'Korea, North',
			'5474' => 'Korea, South',
			'5475' => 'Kuwait',
			'5476' => 'Kyrgyzstan',
			'5477' => 'Laos',
			'5478' => 'Latvia',
			'5479' => 'Lebanon',
			'5480' => 'Lesotho',
			'5481' => 'Liberia',
			'5482' => 'Libya',
			'5483' => 'Liechtenstein',
			'5484' => 'Lithuania',
			'5485' => 'Luxembourg',
			'5486' => 'Macau',
			'5487' => 'Macedonia',
			'5488' => 'Madagascar',
			'5489' => 'Malawi',
			'5490' => 'Malaysia',
			'5491' => 'Maldives',
			'5492' => 'Mali',
			'5493' => 'Malta',
			'5494' => 'Marshall Islands',
			'5495' => 'Martinique',
			'5496' => 'Mauritania',
			'5497' => 'Mauritius',
			'5498' => 'Mayotte',
			'5499' => 'Micronesia',
			'5500' => 'Midway Islands',
			'5501' => 'Moldova',
			'5502' => 'Monaco',
			'5503' => 'Mongolia',
			'5504' => 'Montenegro',
			'5505' => 'Montserrat',
			'5506' => 'Morocco',
			'5507' => 'Mozambique',
			'5508' => 'Myanmar (Burma)',
			'5509' => 'Nagorno-Karabakh',
			'5510' => 'Namibia',
			'5511' => 'Nauru',
			'5512' => 'Navassa Island',
			'5513' => 'Nepal',
			'5514' => 'Netherlands',
			'5515' => 'Netherlands Antilles',
			'5516' => 'New Caledonia',
			'5517' => 'New Zealand',
			'5518' => 'Nicaragua',
			'5519' => 'Niger',
			'5520' => 'Nigeria',
			'5521' => 'Niue',
			'5522' => 'Norfolk Island',
			'5523' => 'Northern Cyprus',
			'5524' => 'Northern Mariana Islands',
			'5525' => 'Norway',
			'5526' => 'Oman',
			'5527' => 'Pakistan',
			'5528' => 'Palau',
			'5529' => 'Palmyra Atoll',
			'5530' => 'Panama',
			'5531' => 'Papua New Guinea',
			'5532' => 'Paraguay',
			'5533' => 'Peru',
			'5534' => 'Peter I Island',
			'5535' => 'Philippines',
			'5536' => 'Pitcairn Islands',
			'5537' => 'Poland',
			'5538' => 'Portugal',
			'5539' => 'Pridnestrovie (Transnistria)',
			'5540' => 'Puerto Rico',
			'5541' => 'Qatar',
			'5542' => 'Queen Maud Land',
			'5543' => 'Reunion',
			'5544' => 'Romania',
			'5545' => 'Ross Dependency',
			'5546' => 'Russia',
			'5547' => 'Rwanda',
			'5548' => 'Saint Barthelemy',
			'5549' => 'Saint Helena',
			'5550' => 'Saint Kitts and Nevis',
			'5551' => 'Saint Lucia',
			'5552' => 'Saint Martin',
			'5553' => 'Saint Pierre and Miquelon',
			'5554' => 'Saint Vincent and the Grenadines',
			'5555' => 'Samoa',
			'5556' => 'San Marino',
			'5557' => 'Sao Tome and Principe',
			'5558' => 'Saudi Arabia',
			'5559' => 'Senegal',
			'5560' => 'Serbia',
			'5561' => 'Seychelles',
			'5562' => 'Sierra Leone',
			'5563' => 'Singapore',
			'5564' => 'Slovakia',
			'5565' => 'Slovenia',
			'5566' => 'Solomon Islands',
			'5567' => 'Somalia',
			'5568' => 'Somaliland',
			'5569' => 'South Africa',
			'5570' => 'South Georgia &amp; South Sandwich Islands',
			'5571' => 'South Ossetia',
			'5572' => 'Spain',
			'5573' => 'Sri Lanka',
			'5574' => 'Sudan',
			'5575' => 'Suriname',
			'5576' => 'Svalbard',
			'5577' => 'Swaziland',
			'5578' => 'Sweden',
			'5579' => 'Switzerland',
			'5580' => 'Syria',
			'5581' => 'Taiwan (Republic of China)',
			'5582' => 'Tajikistan',
			'5583' => 'Tanzania',
			'5584' => 'Thailand',
			'5585' => 'Timor-Leste (East Timor)',
			'5586' => 'Togo',
			'5587' => 'Tokelau',
			'5588' => 'Tonga',
			'5589' => 'Trinidad and Tobago',
			'5590' => 'Tristan da Cunha',
			'5591' => 'Tunisia',
			'5592' => 'Turkey',
			'5593' => 'Turkmenistan',
			'5594' => 'Turks and Caicos Islands',
			'5595' => 'Tuvalu',
			'5596' => 'U.S. Virgin Islands',
			'5597' => 'Uganda',
			'5598' => 'Ukraine',
			'5599' => 'United Arab Emirates',
			'5600' => 'Uruguay',
			'5601' => 'Uzbekistan',
			'5602' => 'Vanuatu',
			'5603' => 'Vatican City',
			'5604' => 'Venezuela',
			'5605' => 'Vietnam',
			'5606' => 'Wake Island',
			'5607' => 'Wallis and Futuna',
			'5608' => 'Yemen',
			'5609' => 'Zambia',
			'5610' => 'Zimbabwe',
			'5981' => 'EU - European Union',
		);
		return $countries;
	}

}

function GoFetch_Premium_Starter_Provider_JobMonkey() {
	return GoFetch_Premium_Starter_Provider_JobMonkey::instance();
}

GoFetch_Premium_Starter_Provider_JobMonkey();
