<?php
/**
 * Loads advanced premium features not available on the free version.
 *
 * @package GoFetch/Admin/Premium/Professional/Features
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class GoFetch_Premium_Professional_Features {

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
		require_once 'class-gofetch-admin-settings__premium_only.php';
		//require_once 'class-gofetch-admin-providers__premium_only.php';
		require_once 'API/class-gofetch-api-providers__premium_only.php';
		require_once 'API/class-gofetch-careerjet__premium_only.php';
		require_once 'API/class-gofetch-cvlibrary_traffic__premium_only.php';
		require_once 'API/class-gofetch-indeed__premium_only.php';
		require_once 'API/class-gofetch-jooble__premium_only.php';
		require_once 'API/class-gofetch-jobtome__premium_only.php';
		require_once 'API/class-gofetch-jobs2careers__premium_only.php';
		require_once 'API/class-gofetch-juju__premium_only.php';
		require_once 'API/class-gofetch-neuvoo__premium_only.php';
		require_once 'API/class-gofetch-ziprecruiter__premium_only.php';
		require_once 'API/class-gofetch-adzuna__premium_only.php';
		require_once 'API/class-gofetch-remotive__premium_only.php';
		require_once 'API/class-gofetch-workingnomads__premium_only.php';
		require_once 'API/class-gofetch-themuse__premium_only.php';

		$this->init_hooks();
	}

	/**
	 * Pro+ hooks.
	 */
	public function init_hooks() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-providers', array( $this, 'inline_css' ), 99 );
	}

	public function inline_css() {
		?><style type="text/css">
			.go-fetch-jobs_page_go-fetch-jobs-wpjm-providers .wrap img.api-providers-logo { max-height: 64px; max-width: 150px; margin-top: 10px; }
			.go-fetch-jobs_page_go-fetch-jobs-wpjm-providers .wrap img.api-providers-logo + .form-table { margin-top: -45px; }
		</style><?php
	}

}

function GoFetch_Premium_Professional_Features() {
	return GoFetch_Premium_Professional_Features::instance();
}

GoFetch_Premium_Professional_Features();
