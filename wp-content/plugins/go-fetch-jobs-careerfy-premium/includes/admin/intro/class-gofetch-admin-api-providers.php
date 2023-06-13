<?php
/**
 * Provides and outputs the providers settings page.
 *
 * @package GoFetch/Admin/API Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Settings Admin class.
 */
class GoFetch_Provider_Settings extends BC_Framework_Tabs_Page {

	/**
	 * Constructor.
	 */
	function __construct() {
		global $goft_wpjm_options;

		parent::__construct( $goft_wpjm_options, 'gofetch-wpjm' );

	}

	/**
	 * Setup the plugin sub-menu.
	 */
	public function setup() {
		$this->args = array(
			'page_title'            => __( 'API Providers', 'gofetch-wpjm' ),
			'menu_title'            => __( 'API Providers', 'gofetch-wpjm' ),
			'page_slug'             => 'go-fetch-jobs-wpjm-providers',
			'parent'                => GoFetch_Jobs()->slug,
			'admin_action_priority' => 10,
		);
	}


	// __Hook Callbacks.

	/**
	 * Initialize tabs.
	 */
	protected function init_tabs() {
?>
		<style>
			.gofj-top-partner {
				color: #6c6a6a;
				font-style: italic;
			}
			.gofj-top-partner-tab .dashicons {
				height: 15px;
			}
			.gofj-top-partner-tab .dashicons::before {
				font-size: 15px;
			}
			sep {
				color: #ccc;
				padding: 0 5px;
				font-style: normal;
			}
			.nav-tab[href*=adzuna] {
				margin-right: 20px;
			}
		</style>
<?php
	}

}

$GLOBALS['goft_wpjm']['api_providers'] = new GoFetch_Provider_Settings();
