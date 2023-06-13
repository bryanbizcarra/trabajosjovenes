<?php
/**
 * Loads advanced premium features not available on the free version.
 *
 * @package GoFetch/Admin/Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class GoFetch_Premium_Features {

	function __construct() {
		$this->init_premium();
	}

	/**
	 * Initialized the scheduler.
	 */
	private function init_premium() {

		if ( gfjwjm_fs()->can_use_premium_code() ) {
			require_once 'starter/class-gofetch-features__premium_only.php';

			if ( gfjwjm_fs()->is_plan( 'professional' ) || gfjwjm_fs()->is_plan( 'proplus', true ) ) {
				require_once 'professional/class-gofetch-features__premium_only.php';
			}

			if ( gfjwjm_fs()->is_plan( 'business' ) || gfjwjm_fs()->is_plan( 'proplus', true ) ) {
				require_once 'business/class-gofetch-features__premium_only.php';
			}
		}
		//add_action( 'admin_menu', array( $this, 'plugin_browser' ), 15 );
	}

	/**
	 * Setup the plugin browser.
	 */
	public function plugin_browser() {

		$args = array(
			'page_title' => __( 'Browse Plugins', 'gofetch-wpjm' ),
			'remote_info' => 'https://bruno-carreco.com/help/catalog/fetch-catalog-goft-wpjm.php?username=sebet&type=products-info',
			'tabs'        => array(
				'all'  =>  array(
					'name' => __( 'All', 'gofetch-wpjm' ),
					'url' => "https://bruno-carreco.com/help/catalog/fetch-catalog-goft-wpjm.php?username=sebet&type=products-new",
				),
			),
			'wp_hosted_args' => array(
				'author' => 'SebeT',
			),
			'menu' => array(
				'menu_title' => __( 'Other Plugins', 'gofetch-wpjm' ),
				'parent'     => GoFetch_Jobs()->slug,
			)
		);

		wp_product_showcase_register( 'go-fetch-jobs-wpjm-plugins-browser', $args );
	}

}

new GoFetch_Premium_Features();
