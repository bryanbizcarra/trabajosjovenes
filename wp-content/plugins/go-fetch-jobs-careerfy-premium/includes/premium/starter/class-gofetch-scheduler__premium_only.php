<?php
/**
 * Registers and handles the scheduler custom post type: 'goft_wpjm_schedule'.
 *
 * @package GoFetch/Admin/Premium/Starter/Scheduler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Scheduler core class.
 */
class GoFetch_Scheduler {

	/**
	 * Constructor.
	 */
	function __construct() {
		add_action( 'admin_notices', array( $this, 'warnings' ) );
		add_action( 'init', array( $this, 'register_post_types' ), 99 );
		add_action( 'init', array( $this, 'maybe_create_schedule' ), 100 );
		add_action( 'admin_head', array( $this, 'schedule_list_style' ) );

		add_action( 'wp_ajax_goft_wpjm_run_schedule', array( $this, 'manual_schedule_run' ) );

		// Custom columns.
		add_filter( 'manage_' . GoFetch_Jobs()->post_type . '_posts_columns', array( $this, 'manage_columns' ) , 11 );
		add_action( 'manage_' . GoFetch_Jobs()->post_type . '_posts_custom_column', array( $this, 'add_column_data' ), 10, 2 );
	}

	/**
	 * Register the custom post type for the import scheduler.
	 */
	public function register_post_types() {

		$labels = array(
			'name'               => __( 'Schedules', 'gofetch-wpjm' ),
			'singular_name'      => __( 'Schedule', 'gofetch-wpjm' ),
			'all_items'          => __( 'Schedules', 'gofetch-wpjm' ),
			'add_new'            => __( 'Add New Schedule', 'gofetch-wpjm' ),
			'add_new_item'       => __( 'Add New Schedule', 'gofetch-wpjm' ),
			'edit_item'          => __( 'Edit Schedule', 'gofetch-wpjm' ),
			'new_item'           => __( 'New Schedule', 'gofetch-wpjm' ),
			'view_item'          => __( 'View Schedule', 'gofetch-wpjm' ),
			'search_items'       => __( 'Search Schedule', 'gofetch-wpjm' ),
			'not_found'          => __( 'No Schedule found', 'gofetch-wpjm' ),
			'not_found_in_trash' => __( 'No Schedule found in Trash', 'gofetch-wpjm' ),
			'menu_name'          => __( 'Go Fetch Jobs', 'gofetch-wpjm' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => GoFetch_Jobs()->slug,
			'query_var'          => true,
			'rewrite'            => true,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 100,
			'taxonomies'         => array( 'post_tag' ),
			'supports'           => array( 'title', 'author' ),
		);

		register_post_type( GoFetch_Jobs()->post_type, $args );
	}

	/**
	 * Output custom columns.
	 */
	public function manage_columns( $columns ) {

		$date = $columns['date'];

		unset( $columns['date'] );

		$columns['schedule']  = __( 'Schedule', 'gofetch-wpjm' );
		$columns['time_span'] = __( 'Content Period', 'gofetch-wpjm' );
		$columns['limit']     = __( 'Limit', 'gofetch-wpjm' );
		$columns['template']  = __( 'Template', 'gofetch-wpjm' );
		$columns['last_run']  = __( 'Last Automated Run', 'gofetch-wpjm' );

		return $columns;
	}

	/**
	 * Output custom columns data.
	 */
	public function add_column_data( $column_index, $post_id ) {

		switch ( $column_index ) {

			case 'schedule':
				$schedule = get_post_meta( $post_id, '_goft_wpjm_cron', true );
				$last_run = get_post_meta( $post_id, '_scheduler_log_timestamp', true );

				if ( ! $last_run ) {
					$last_run = current_time( 'mysql' );
				}

				switch ( $schedule ) {
					case 'hourly':
						$schedule = __( 'Hourly', 'gofetch-wpjm' );
						$schedule .= '<br/>' . html( 'small', sprintf( __( '( ~ %s )', 'gofetch-wpjm' ), date( 'H:i', strtotime( '1 hour', strtotime( $last_run ) ) ) ) );
						break;

					case 'twice_daily':
						$schedule = __( '12 Hours', 'gofetch-wpjm' );
						$schedule .= '<br/>' . html( 'small', sprintf( __( '( ~ %s )', 'gofetch-wpjm' ), date( 'H:i', strtotime( '12 hours', strtotime( $last_run ) ) ) ) );
						break;

					case 'daily':
						$schedule = __( 'Daily', 'gofetch-wpjm' );
						$schedule .= '<br/>' . html( 'small', sprintf( __( '( ~ %s )', 'gofetch-wpjm' ), date( 'H:i', strtotime( '24 hours', strtotime( $last_run ) ) ) ) );
						break;

					case 'weekly':
						$schedule = __( 'Weekly', 'gofetch-wpjm' );
						$schedule .= '<br/>' . html( 'small', sprintf( __( '( %s )', 'gofetch-wpjm' ), date( 'F, d', strtotime( 'this week +1 week' ) ) ) );
						break;

					case 'monthly':
						$schedule = __( 'Monthly', 'gofetch-wpjm' );
						$schedule .= '<br/>' . html( 'small', sprintf( __( '( %s )', 'gofetch-wpjm' ), date( 'F, d', strtotime( date( 'm', strtotime( '+1 month' ) ) . '/01/' . date('Y') . ' 00:00:00' ) ) ) );
						break;
				}
				echo wp_kses_post( $schedule );
				break;

			case 'time_span':
				$time_span = get_post_meta( $post_id, '_goft_wpjm_period', true );
				if ( 'custom' == $time_span ) {
					$time_span = (int) get_post_meta( $post_id, '_goft_wpjm_period_custom', true );
				}

				switch ( $time_span ) {
					case 'today':
						$time_span = __( 'Today', 'gofetch-wpjm' );
						break;

					default:
						$time_span = sprintf( __( 'Last %1$s %2$s', 'gofetch-wpjm' ), $time_span, _n( 'day', 'days', $time_span, 'gofetch-wpjm' ) );
						break;
				}
				echo wp_kses_post( $time_span );
				break;

			case 'limit':
				$limit = (int) get_post_meta( $post_id, '_goft_wpjm_limit', true );

				echo wp_kses_post( $limit ? $limit : '-' );
				break;

			case 'template':
				echo esc_attr( get_post_meta( $post_id, '_goft_wpjm_template', true ) );
				break;

			case 'last_run':
				$last_message = get_post_meta( $post_id, '_scheduler_log_status', true );

				if ( $last_message ) {
					$timestamp = get_post_meta( $post_id, '_scheduler_log_timestamp', true );

					if ( $timestamp ) {
						echo date( get_option( 'date_format', 'Y-m-d' ) . ' H:i', strtotime( $timestamp ) ) . '<br/>';
					} else {
						echo 'Manually Run<br/>';
					}

					$visible_stats = apply_filters( 'goft_wpjm_schedules_admin_cols_stats', array( 'in_rss_feed', 'imported', 'excluded' ) );

					$col_stats = array();

					foreach ( (array) $last_message as $key => $value ) {
						if ( in_array( $key, $visible_stats ) ) {
							$col_stats[ $key ] = $value;
						}
					}

					$last_message = $col_stats;
				} else {
					$last_message = '-';
				}
				echo ( is_array( $last_message ) ? GoFetch_Log_Message_Table::get_stats_message( $last_message ) : $last_message );
				break;
		}

	}

	/**
	 * Manually runs a schedule test/normal import.
	 *
	 * @since 1.3.
	 */
	public function manual_schedule_run() {

		if ( ! wp_verify_nonce( $_POST['_ajax_nonce'], 'goft_wpjm_nonce' ) ) {
			die( 0 );
		}

		$post_id = (int) $_POST['post_id'];
		$action  = stripslashes( sanitize_text_field( $_POST['goft_action'] ) );

		$post = get_post( $post_id );

		self::_run_schedule( $post, '', 'test_import' === $action, $_manual = true );
		die( 1 );
	}

	/**
	 * Get the shcedule timestamp, considering the user selection.
	 */
	protected function get_schedule_timestamp() {
		global $goft_wpjm_options;

		if ( empty( $goft_wpjm_options->scheduler_start_time ) ) {
			return time();
		}

		$user_schedule = $goft_wpjm_options->scheduler_start_time;

		$next_day = '';

		$user_date_time = new DateTime( date( 'Y-m-d' ) . ' ' . $user_schedule . wp_timezone_string() );
		$current_date_time = new DateTime( date( 'Y-m-d', current_time('timestamp') ) );

		if ( $user_date_time < $current_date_time ) {
			$next_day = ' +1 day';
		}

		// Add 1 day (if time is in the future), and the timezone difference to the user schedule time, to calculate the exact start time,
		$timestamp = strtotime( $user_schedule . wp_timezone_string() . ' ' . $next_day );

		return $timestamp;
	}

	// Helpers.
	/**
	 * Create a single WP cron job for all schedules when the user adds a schedule for the first time.
	 */
	public function maybe_create_schedule() {

		$schedule_set = get_option( 'goft-wpjm-scheduler-active' );

		// Reset old daily schedules so they run hourly.
		if ( $event = wp_get_scheduled_event( 'gofetch_wpjm_importer' ) ) {
			if ( 'hourly' !== $event->schedule ) {
				wp_clear_scheduled_hook( 'gofetch_wpjm_importer' );
				$schedule_set = false;
			}
		}

		// Create the new cron schedule.
		if ( ! $schedule_set || ! wp_next_scheduled( 'gofetch_wpjm_importer' ) ) {

			$timestamp = $this->get_schedule_timestamp();

			wp_schedule_event( $timestamp, 'hourly', 'gofetch_wpjm_importer' );
			update_option( 'goft-wpjm-scheduler-active', GoFetch_Jobs()->version );
		}
	}

	/**
	 * The main schedule callback that manages all the user schedules.
	 */
	public static function scheduler_manager() {
		global $goft_wpjm_options;

		$args = array(
			'post_type' => GoFetch_Jobs()->post_type,
			'status'    => 'publish',
			'nopaging'  => true,
		);
		$schedules = new WP_Query( $args );

		// __LOG.
		$schedule_start_time = current_time( 'timestamp' );
		$vars = array(
			'context'         => 'GOFT :: STARTING SCHEDULE WARMUP',
			'post_type'       => GoFetch_Jobs()->post_type,
			'schedules_count' => count( $schedules->posts ),
		);
		BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );

		$i = 0;

		foreach ( $schedules->posts as $post ) {

			$schedule_name = sprintf( 'goft_wpjm_sch_%s', $post->post_name );

			// Recurrence: daily, weekly, monthly, etc.
			$recurrence = get_post_meta( $post->ID, '_goft_wpjm_cron', true );

			// Last run date.
			$last_run      = get_post_meta( $post->ID, '_scheduler_log_timestamp', true );
			$last_run_time = strtotime( $last_run );

			$month     = date( 'n' );
			$month_day = date( 'j' );

			$week     = date( 'W' );
			$week_day = date( 'w' );

			$time = current_time( 'timestamp' );

			switch ( $recurrence ) {

				case 'hourly':
					$run = true;
					break;

				case 'daily':
					$run = $time >= strtotime( '24 hours', $last_run_time );
					break;

				case 'twice_daily':
					$run = $time >= strtotime( '12 hours', $last_run_time );
					break;

				case 'monthly':
					// Run of first day of month.
					if ( 1 === (int) $month_day ) {
						$run = true;
					} else {
						// Otherwise, run if never run before during the current month.
						$last_run_month = date( 'n', $last_run_time );
						$run = ( (int) $last_run_month < (int) $month );
					}
					break;

				case 'weekly':
					// Run evey Monday.
					if ( 1 === (int) $week_day ) {
						$run = true;
					} else {
						// Otherwise, run if never run before during the current week.
						$last_run_week = date( 'W', $last_run_time );
						$run = ( (int) $last_run_week < (int) $week );
					}
					break;

				default:
					$run = false;
			}

			// __LOG.
			$schedule_start_time = current_time( 'timestamp' );
			$vars = array(
				'context'    => 'GOFT :: STARTING SCHEDULE RUN',
				'schedule'   => $schedule_name,
				'recurrence' => $recurrence,
				'run?'      => apply_filters( 'goft_wpjm_schedules_run', $run, $recurrence, $last_run ),
			);
			BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );

			if ( apply_filters( 'goft_wpjm_schedules_run', $run, $recurrence, $last_run, $post ) ) {

				// __LOG.
				$vars = array(
					'context' => 'GOFT :: SCHEDULE RUNNING',
				);
				BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );

				// Sleep for n seconds before starting a new schedule.
				if ( $i++ > 0 ) {

					// Make sure the max interval is not over 60.
					$sleep = $goft_wpjm_options->scheduler_interval_sleep > 30 ? 30 : $goft_wpjm_options->scheduler_interval_sleep;

					$sleep = apply_filters( 'goft_wpjm_schedules_interval_sleep', $goft_wpjm_options->scheduler_interval_sleep );

					sleep( $sleep );
				}

				do_action( 'goft_wpjm_before_schedule_run' );

				self::_run_schedule( $post, $schedule_name );

				do_action( 'goft_wpjm_after_schedule_run' );
			}

			// __LOG.
			$vars = array(
				'context'    => 'GOFT :: ENDED SCHEDULE RUN',
				'schedule'   => $schedule_name,
				'recurrence' => $recurrence,
				'run?'       => (string) $run,
				'duration'   => date( 'i:s', current_time( 'timestamp' ) - $schedule_start_time ),
			);
			BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );
		}

		// Clear memory.
		$schedules = null;

		wp_reset_postdata();
	}

	/**
	 * Runs a schedule, exports and sends any CSV's by email.
	 */
	protected static function _run_schedule( $post, $schedule_name = '', $test = false, $manual = false ) {
		global $goft_wpjm_options;

		$meta = get_post_custom( $post->ID );

		$defaults = array(
			'_goft_wpjm_template'                    => array( '' ),
			'_goft_wpjm_period'                      => array( '' ),
			'_goft_wpjm_limit'                       => array( '' ),
			'_goft_wpjm_keywords'                    => array( '' ),
			'_goft_wpjm_keywords_comparison'         => array( 'OR' ),
			'_goft_wpjm_keywords_exclude'            => array( '' ),
			'_goft_wpjm_keywords_exclude_comparison' => array( 'OR' ),
		);
		$meta = wp_parse_args( $meta, $defaults );

		$template_name               = $meta['_goft_wpjm_template'][0];
		$period                      = $meta['_goft_wpjm_period'][0];
		$limit                       = $meta['_goft_wpjm_limit'][0];
		$keywords                    = $meta['_goft_wpjm_keywords'][0];
		$keywords_comparison         = $meta['_goft_wpjm_keywords_comparison'][0];
		$keywords_exclude            = $meta['_goft_wpjm_keywords_exclude'][0];
		$keywords_exclude_comparison = $meta['_goft_wpjm_keywords_exclude_comparison'][0];
		$post_author                 = $post->post_author;

		if ( ! $schedule_name ) {
			$schedule_name = sprintf( 'goft_wpjm_sch_%s', $post->post_name );
		}

		switch ( $period ) {

			case 'custom':
				$custom_period = $meta['_goft_wpjm_period_custom'][0];
				$from_date     = strtotime( sprintf( '-%s days', $custom_period ) );
				$to_date       = current_time( 'timestamp' );
				break;

			default:
				$from_date = current_time( 'timestamp' );
				$to_date   = current_time( 'timestamp' );

		}

		$from_date = date( 'Y-m-d 00:00:00', $from_date );
		$to_date   = date( 'Y-m-d 00:00:00', $to_date );

		$log_type = 'success';

		$templates = GoFetch_Helper::get_sanitized_templates();

		if ( empty( $templates[ $template_name ] ) ) {
			$message  = __( 'Template not found!', 'gofetch-wpjm' );
			$log_type = 'error';
		} else {

			// Get all the saved parameters for the current template.
			$defaults = $templates[ $template_name ];

			$args = apply_filters( 'goft_wpjm_schedule_params', compact( 'from_date', 'to_date', 'limit', 'keywords', 'keywords_comparison', 'keywords_exclude', 'keywords_exclude_comparison', 'post_author' ) );

			// Merge the default parameters with new parameters defined on each schedule.
			$params = wp_parse_args( $args, $defaults );

			// __LOG.
			$vars = array(
				'context'  => 'GOFT :: IMPORTING FEED FOR SCHEDULE',
				'test'     => $test ? 'Y' : 'N',
				'schedule' => $schedule_name,
			);
			BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );

			// Import the data.
			$result = GoFetch_Importer::import_feed( $params['rss_feed_import'], array( 'limit' => $limit ), $cache = false );

			// __LOG.
			$vars = array(
				'context' => 'GOFT :: FINISHED IMPORTING FEED FOR SCHEDULE',
				'result'  => $result,
			);
			BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );

			if ( is_wp_error( $result ) ) {

				$message  = sprintf( __( 'Error importing data from feed. Please check if you can load the feed on the \'Importer Jobs\' page. Error Message: <small>%s</small>', 'gofetch-wpjm' ), $result->get_error_message() );
				$log_type = 'error';

			} else {

				$items = array();

				if ( ! empty( $result['items'] ) ) {

					$items          = $result['items'];
					$params['test'] = $test;

					$results = GoFetch_Importer::import( $items, $params );

					list( $in_rss_feed, $imported, $limit, $duplicates, $excluded, $duration ) = array_values( $results );

					if ( ! empty( $imported ) ) {

						if ( is_wp_error( $imported ) ) {
							$message  = sprintf( __( 'Error importing data from feed. Error Message: <small>%s</small>', 'gofetch-wpjm' ), $imported->get_error_message() );
							$log_type = 'error';
						} else {
							$message         = $results;
							$message['test'] = $test;
						}
					} else {
						$message = __( 'No new jobs found in feed', 'gofetch-wpjm' );
					}
				} else {
					$message = __( 'No jobs found in feed', 'gofetch-wpjm' );
				}
			}
		}

		// __LOG.
		$vars = array(
			'context'  => 'GOFT :: FINISHED IMPORTING FOR SCHEDULE',
			'schedule' => $schedule_name,
			'test'     => $test ? 'Y' : 'N',
			'message'  => $message,
		);
		BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );

		$logger = new BC_Framework_Logger( $post->ID );
		$logger->log( $message, $log_type, GoFetch_Jobs()->log_limit );

		// Clear memory.
		$meta = $result = $results = $items = null;
		//

		// Only log the timestamp on Cron scheduled runs.
		if ( ! $test && ! $manual ) {
			update_post_meta( $post->ID, '_scheduler_log_timestamp', current_time( 'mysql' ) );
		}
		update_post_meta( $post->ID, '_scheduler_log_status', $message );
	}

	private static function process_param_mappings( $params ) {
		return $params;
	}

	/**
	 * CSS Styles for the schedule list page.
	 */
	public function schedule_list_style() {
		$screen = get_current_screen();

		if ( empty( GoFetch_Jobs()->post_type ) || empty( $screen ) || 'edit-' . GoFetch_Jobs()->post_type !== $screen->id ) {
			return;
		}
	?>
		<style type="text/css">
			.log-stats {
				padding-right: 5px;
			}
			.test-import {
				background-color: #ececec;
				color: #8a8a8a;
			}
		</style>
	<?php
	}

	/**
	 * Get a list of all the template names used in schedules.
	 */
	public static function get_used_templates( $with_id = false ) {
		global $wpdb;

		$sql = "Select ID, meta_value as template from $wpdb->posts a, $wpdb->postmeta b
				WHERE a.ID = b.post_id
				AND post_type = %s
				AND meta_key = '_goft_wpjm_template'
				AND post_status in ( 'publish', 'draft', 'pending' )
				group by 1";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, GoFetch_Jobs()->post_type ) );

		if ( ! $with_id ) {
			return wp_list_pluck( $results, 'template' );
		}
		return $results;
	}

	/**
	 * Creates a single schedule with specific parameters.
	 */
	public static function create_schedule( $title, $template, $post_status = 'pending', $meta = array() ) {

		$args = array(
			'post_type'   => GoFetch_Jobs()->post_type,
			'post_status' => 'any',
			'nopaging'    => true,
			's'           => $title,
		);
		$matches = new WP_Query( $args );

		$matches = get_posts( $args );

		// Avoid creating duplicate schedules.
		if ( $matches ) {
			return;
		}

		$defaults = array(
			'_goft_wpjm_template'      => $template,
			'_goft_wpjm_period'        => 'custom',
			'_goft_wpjm_period_custom' => 5,
			'_goft_wpjm_limit'         => 50,
			'_goft_wpjm_cron'          => 'daily',
		);
		$meta = wp_parse_args( $meta, $defaults );

		$post = array(
			'post_title'  => $title,
			'post_type'   => GoFetch_Jobs()->post_type,
			'post_status' => $post_status,
			'meta_input'  => $meta,
		);

		if ( ! empty( $meta['post_author_override'] ) ) {
			$post['post_author'] = $meta['post_author_override'];
		}

		return wp_insert_post( $post );
	}

	/**
	 * Admin notice for schedules with missing template names.
	 */
	public function warnings() {
		global $post;

		if ( empty( $_GET['post'] ) || empty( $post ) || GoFetch_Jobs()->post_type !== $post->post_type ) {
			return;
		}

		if ( ! get_post_meta( $post->ID, '_goft_wpjm_template', true ) ) {
			echo scb_admin_notice( __( "NOTE: You haven't selected a template for this schedule. It will remain inactive until you assign an existing template.", 'gofetch-wpjm' ), 'error' );
		}
	}

}

new GoFetch_Scheduler();
