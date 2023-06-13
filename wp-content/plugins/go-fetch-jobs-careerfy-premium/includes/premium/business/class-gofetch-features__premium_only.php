<?php
/**
 * Loads advanced premium features not available on the free version.
 *
 * @package GoFetch/Admin/Premium/Business/Features
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class GoFetch_Premium_Business_Features {

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
		require_once 'ATS/class-gofetch-jazzhr__premium_only.php';
		require_once 'ATS/class-gofetch-greenhouse__premium_only.php';
		require_once 'ATS/class-gofetch-recruitee__premium_only.php';

		$this->init_hooks();
	}

	/**
	 * hooks.
	 */
	public function init_hooks() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-ats', array( $this, 'inline_css' ), 99 );
	}

	public function inline_css() {
		?><style type="text/css">
			.go-fetch-jobs_page_go-fetch-jobs-wpjm-ats .wrap img.ats-providers-logo { max-height: 64px; max-width: 150px; margin-top: 10px; }
			.go-fetch-jobs_page_go-fetch-jobs-wpjm-ats .wrap img.ats-providers-logo + .form-table { margin-top: -45px; }
		</style><?php
	}

}

function GoFetch_Premium_Business_Features() {
	return GoFetch_Premium_Business_Features::instance();
}

GoFetch_Premium_Business_Features();
