<?php
/**
 * Sets up Starter specific settings.
 *
 * @package GoFetch/Admin/Premium/Starter/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Schedules settings class.
 */
class GoFetch_Starter_Settings {

	protected $all_tabs;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'tabs_go-fetch-jobs_page_go-fetch-jobs-wpjm-settings', array( $this, 'tabs' ) );
		add_action( 'pre_update_option_goft_wpjm_options', array( $this, 'maybe_update_schedule_time' ), 10, 3 );
	}

	/**
	 * Init the custom tabs.
	 */
	public function tabs( $all_tabs ) {
		$this->all_tabs = $all_tabs;

		$this->all_tabs->tabs->add( 'scheduler', __( 'Scheduler', 'gofetch-wpjm' ) );

		$this->tab_scheduler();
	}

	/**
	 * Scheduler settings tab.
	 */
	protected function tab_scheduler() {

		for ( $hours = 0; $hours < 24; $hours++ ) {

			for ( $mins = 0; $mins < 60; $mins += 30 ) {
				$time = str_pad( $hours, 2, '0', STR_PAD_LEFT ) . ':' .  str_pad( $mins, 2, '0', STR_PAD_LEFT );
				$intervals[ $time ] = $time;
			}
		}

		$this->all_tabs->tab_sections['scheduler']['settings'] = array(
			'title' => __( 'Scheduler', 'gofetch-wpjm' ),
			'fields' => array(
				array(
					'title' => __( 'Start Time', 'gofetch-wpjm' ),
					'name'  => 'scheduler_start_time',
					'type'  => 'select',
					'choices' => $intervals,
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'The approximate start time for the schedules to run.', 'gofetch-wpjm' ) .
							 '<br/><br/>' .  __( 'Please note that schedules use WP Cron and as such will only run at the specified time when someone visits your site if the scheduled time has passed.', 'gofetch-wpjm' ) .
							 '<br/><br/>' .  __( '<code>NOTE:</code> Updating the start time will trigger the schedules run.', 'gofetch-wpjm' ),
				),
				array(
					'title' => __( 'Interval', 'gofetch-wpjm' ),
					'name'  => 'scheduler_interval_sleep',
					'type'  => 'number',
					'desc' => __( 'seconds (between 0 - 30 sec)', 'gofetch-wpjm' ),
					'extra' => array(
						'class' => 'small-text',
					),
					'tip' => __( 'The interval between schedule runs, in seconds.', 'gofetch-wpjm' ) .
							 '<br/><br/>' . __( 'If you have several schedules for the same provider and you experience any problems running all schedules try increasing this interval.', 'gofetch-wpjm' ),
				),
			),
		);

	}

	/**
	 * Clear the schedule time if updated by the user.
	 * The schedule will be re-initialized to the new time.
	 */
	public function maybe_update_schedule_time( $value, $old_value, $option ) {

		if ( empty( $old_value['scheduler_start_time'] ) || ( ! empty( $value['scheduler_start_time'] ) && $value['scheduler_start_time'] !== $old_value['scheduler_start_time'] ) ) {
			if ( wp_next_scheduled( 'gofetch_wpjm_importer' ) ) {
				wp_clear_scheduled_hook( 'gofetch_wpjm_importer' );
			}
		}
		return $value;
	}

}

new GoFetch_Starter_Settings();
