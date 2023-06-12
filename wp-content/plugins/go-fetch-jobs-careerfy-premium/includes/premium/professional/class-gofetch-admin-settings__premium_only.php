<?php
/**
 * Sets up Professional plan specific settings.
 *
 * @package GoFetch/Admin/Premium/Professional/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Schedules settings class.
 */
class GoFetch_Professional_Settings {

	protected $all_tabs;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-settings', array( $this, 'tabs' ), 9 );
	}

	/**
	 * Init the custom tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;

		$this->all_tabs->tabs->add( 'listings', __( 'Listings', 'gofetch-wpjm' ) );

		$this->tab_listings();
	}

	/**
	 * Listings settings tab.
	 */
	protected function tab_listings() {
?><style>.nav-tab[href*=advanced] { margin-left: 1.3em; }</style><?php
		$this->all_tabs->tab_sections['listings']['shortcode_info'] = array(
			'title' => __( 'Shortcode', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title'  => 'Info',
					'name'   => '_blank',
					'type'   => 'custom',
					'render' => function() {
						$text = __( '<em>Go Fetch Jobs</em> provides its own custom shortcode <code>[goft_jobs]</code> to display imported jobs only. Use it to display imported jobs separately (e.g: on a dedicated page).', 'gofetch-wpjm' ) .
						'<br/><br/>' . sprintf( __( 'Accepts the <a href="%s">same parameters</a> as WPJM <code>[jobs]</code> shortcode with the addition of:', 'gofetch-wpjm' ), 'https://wpjobmanager.com/document/shortcode-reference/' ) .
						'<br/><br/>' . __( '- <code>company</code> <em>Display jobs for a specific company</em> (e.g: <code>[goft_jobs company="google"]</code>)', 'gofetch-wpjm' );
						return $text;
					}
				),
			),
		);

		$this->all_tabs->tab_sections['listings']['shortcode'] = array(
			'title' => __( '', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Separate Listings', 'gofetch-wpjm' ),
					'name'  => 'independent_listings',
					'type'  => 'checkbox',
					'desc' => __( 'Yes. Let me use the custom shortcode <code>[goft_jobs]</code> to display imported jobs separately.', 'gofetch-wpjm' ),
					'tip' => __( 'Enable this option to hide imported jobs from your regular listings.<br/><br/>Use the shortcode <code>[goft_jobs]</code> (accepts all WPJM parameters) to display the list of imported jobs. The default <code>[jobs]</code> shortcode will only display user submitted jobs. <br/><br/>Leave it unchecked to mix regular with imported jobs on your listings.', 'gofetch-wpjm' ) .
							 '<br/><br/>' . __( '<strong>NOTE: </strong> You can still use the shortcode with this option unchecked (mixed listings) for displaying a list of imported jobs only.', 'gofetch-wpjm' )
				),
				array(
					'title' => __( 'Allow Filtering', 'gofetch-wpjm' ),
					'name'  => 'filter_imported_jobs',
					'type'  => 'checkbox',
					'desc'  => __( 'Yes. Allow users to filter out external jobs (imported jobs).', 'goft-wpjm' ),
					'tip'   => __( 'Enable this option to display a dropdown filter under jobs listings to allow users to filter between all, site or external jobs.', 'gofetch-wpjm' )
							. '<br/><br/>' . __( "Use the class <code>goft-jobs-filter</code> if you need to style the dropdown.", 'gofetch-wpjm' )
							. '<br/><br/>' . __( "<strong>NOTE:</strong> The dropdown filter will only be visible if you use mixed listings (<code>Independent Listings</code> option is unchecked).", 'gofetch-wpjm' ),
				),
				array(
					'title' => __( ' > Imported Jobs Label', 'gofetch-wpjm' ),
					'name'  => 'filter_imported_jobs_label',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text2',
					),
					'tip' => __( 'The text to be displayed as the external jobs filter label on the filter dropdown.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( ' > Site Jobs Label', 'gofetch-wpjm' ),
					'name'  => 'filter_site_jobs_label',
					'type'  => 'text',
					'extra' => array(
						'class' => 'small-text2',
					),
					'tip' => __( 'The text to be displayed as the site jobs filter label on the filter dropdown.', 'gofetch-wpjm' ),
				),
			),
		);

	}

}

new GoFetch_Professional_Settings();
