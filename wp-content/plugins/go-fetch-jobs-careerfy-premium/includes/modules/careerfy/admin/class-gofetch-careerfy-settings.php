<?php
/**
 * Specific import code for Careerfy.
 *
 * @package GoFetch/Careerfy/Admin/Import
 */

 if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once dirname( GOFT_WPJM_PLUGIN_FILE ) . '/includes/class-gofetch-importer.php';

/**
 * Specific import functionality.
 */
class GoFetch_Careefy_Admin_Settings extends GoFetch_Importer {

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
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-settings', array( $this, 'remove_incompatible_options' ), 50 );
	}

	/**
	 * Remove incompatible options.
	 */
	public function remove_incompatible_options( $tab ) {
		global $goft_wpjm;

		$goft_wpjm['settings']->tab_sections['jobs']['jobs']['fields'][] = array(
			'title'   => __( 'Override Employer Meta', 'gofetch-wpjm' ),
			'name'    => 'override_employers',
			'type'    => 'checkbox',
			'tip' => __( 'Check this option to override the default employer name and logo, with the relevant imported data, when available.', 'gofetch-wpjm' ) .
					'<br/><br/>' . __( 'If you leave this option unchecked, imported jobs will use the name and logo from the selected employer name, during import.', 'gofetch-wpjm' )
		);

		// Remove Listings tab.
		$goft_wpjm['settings']->tabs->remove('listings');

		// API.
		unset( $goft_wpjm['settings']->tab_sections['indeed']['sponsored'] );
		unset( $goft_wpjm['settings']->tab_sections['neuvoo']['sponsored'] );

		unset( $goft_wpjm['settings']->tab_sections['jobs']['applications'] );
	}

}

GoFetch_Careefy_Admin_Settings::instance();
