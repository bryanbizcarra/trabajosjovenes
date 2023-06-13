<?php
/**
 * Importer classes for providers that use RSS feeds to provide jobs.
 *
 * @package GoFetch/Admin/Premium/Starter/Euro Remote Jobs RSS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class GoFetch_Premium_Starter_Provider_EuroRemoteJobs {

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

		$new_providers = array(
			'euroremotejobs.com' => array(
				'website'     => 'https://euremotejobs.com/',
				'logo'        => GoFetch_Jobs()->plugin_url() . '/includes/images/logos/logo-euroremotejobs.png',
				'description' => 'Remote jobs Europe.',
				'feed'        => array(
					'base_url'   => 'https://euremotejobs.com/?feed=job_feed',
					'search_url' => 'https://euremotejobs.com/jobs/',
					// Feed URL query args. Key value pairs of valid keys => provider_key/default_key_value.
					'query_args'  => array(
						'keyword' => array( 'search_keywords' => '' ),
						// Custom.
						'country'  => array( 'job_region' => '' ),
						'industry' => array( 'job_categories' => '' ),
					),
					'default_mappings' => array(
						//'encoded' => 'post_content',
					),
					'full_description' => true,
					'default' => true,
				),
				'special' => array(
					'scrape' => array(
						'logo' => array(
							'nicename' => __( 'Company Logo', 'gofetch-wpjm' ),
							'query'    => '//div[contains(@class,"job-meta")]//img[contains(@class,"company_logo")]/@src',
						),
					),
				),
				'multi_region_match'  => 'euroremotejobs',
				'region_param_domain' => 'country',
				'region_domains'      => $this->countries(),
				'region_default'      => '111',
				'category' => __( 'Remote Work', 'gofetch-wpjm' ),
				'weight' => 9,
			),
		);

		return array_merge( $providers, $new_providers );
	}


	/**
	 * Outputs specific Indeed feed parameter fields.
	 */
	public function feed_builder_fields( $provider ) {

		if ( 'euroremotejobs.com' !== $provider ) {
			return;
		}

?>
		<?php
		$field = array(
			'title'   => __( 'Job Category', 'gofetch-wpjm' ),
			'name'    => 'feed-industry',
			'type'    => 'select',
			'choices' => $this->industries(),
			'class'   => 'regular-text',
			'extra'   => array(
				'data-qarg' => 'feed-param-industry',
				'style'     => "width: 550px;",
			),
			'default' => '',
		);
?>
		<?php $field_name = 'industry'; ?>
		<p class="params opt-param-<?php echo esc_attr( $field_name ); ?>">
			<label for="feed-<?php echo esc_attr( $field_name ); ?>"><strong><?php _e( 'Job Category', 'gofetch-wpjm' ); ?></strong>
				<span class="tip"><span class="dashicons-before dashicons-editor-help tip-icon bc-tip" data-tooltip="<?php echo esc_attr( __( 'Choose the job category that you want to pull jobs from.', 'gofetch-wpjm' ) ); ?>"></span></span>
			</label>
			<span class="feed-param-<?php echo esc_attr( $field_name ); ?>"></span>
			<?php echo scbForms::input( $field, array() ) ?>
			<input type="hidden" name="feed-param-<?php echo esc_attr( $field_name ); ?>">
			<span><?php _e( '<strong>Note:</strong> The companies above, are directly related with the industries that you\'ve selected on the settings page.', 'gofetch-wpjm' ); ?></span>
		</p>

		<div class="clear"></div>
<?php
	}

	/**
	 * Industries available for this provider.
	 */
	public static function industries() {

		$categories = array(
			''                 => 'Any',
			'admin-operations' => 'Admin &amp; Operations',
			'customer-support' => 'Customer Support',
			'data'             => 'Data',
			'design'           => 'Design',
			'engineering'      => 'Engineering',
			'finance'          => 'Finance',
			'human-resources'  => 'Human Resources',
			'it'               => 'IT',
			'legal'            => 'Legal',
			'marketing'        => 'Marketing',
			'product'          => 'Product',
			'sales'            => 'Sales',
			'all-others'       => 'All Others',
		);
		return $categories;
	}

	/**
	 * Categories available for this provider.
	 */
	public static function countries() {

		$countries = array(
			'73'  => 'Worldwide',
			'112' => 'APAC',
			'103' => 'Asia',
			'114' => 'Australia',
			'186' => 'Brazil',
			'105' => 'Canada',
			'164' => 'Central America',
			'198' => 'Colombia',
			'246' => 'Cyprus',
			'72'  => 'EMEA',
			'167' => 'Africa',
			'266' => 'Ethiopia',
			'227' => 'Kenya',
			'256' => 'South Africa',
			'90'  => 'Europe',
			'110' => 'Austria',
			'247' => 'Belgium',
			'128' => 'Belguim',
			'188' => 'Bulgaria',
			'152' => 'Croatia',
			'254' => 'Cyprus',
			'129' => 'Czech Republic',
			'109' => 'Denmark',
			'197' => 'Eastern Europe',
			'189' => 'Estonia',
			'104' => 'Finland',
			'99'  => 'France',
			'260' => 'Georgia',
			'78'  => 'Germany',
			'190' => 'Greece',
			'162' => 'Hungary',
			'77'  => 'Ireland',
			'126' => 'Italy',
			'265' => 'Latvia',
			'257' => 'Lithuania',
			'249' => 'Luxembourg',
			'258' => 'Macedonia',
			'221' => 'Malta',
			'98'  => 'Netherlands',
			'251' => 'Norway',
			'101' => 'Poland',
			'106' => 'Portugal',
			'134' => 'Romania',
			'208' => 'Serbia',
			'255' => 'Slovakia',
			'259' => 'Slovenia',
			'80'  => 'Spain',
			'155' => 'Sweden',
			'100' => 'Switzerland',
			'226' => 'Turkey',
			'76'  => 'UK',
			'116' => 'Ukraine',
			'165' => 'Ghana',
			'380' => 'Senegal',
			'261' => 'Uzbekistan',
			'115' => 'Georgia',
			'392' => 'Iceland',
			'160' => 'India',
			'213' => 'Kenya',
			'252' => 'LATAM',
			'382' => 'Argentina',
			'381' => 'Costa Rica',
			'384' => 'Honduras',
			'383' => 'Mexico',
			'163' => 'Latvia',
			'193' => 'Lithuania',
			'214' => 'Nigeria',
			'95'  => 'North America',
			'170' => 'Norway',
			'212' => 'Pakistan',
			'194' => 'Russia',
			'185' => 'Singapore',
			'210' => 'Slovakia',
			'187' => 'South Africa',
			'113' => 'South America',
			'390' => 'Thailand',
			'181' => 'Turkey',
			'111' => 'US',
			'127' => 'US East Coast',
		);
		return $countries;
	}

}

function GoFetch_Premium_Starter_Provider_EuroRemoteJobs() {
	return GoFetch_Premium_Starter_Provider_EuroRemoteJobs::instance();
}

GoFetch_Premium_Starter_Provider_EuroRemoteJobs();
