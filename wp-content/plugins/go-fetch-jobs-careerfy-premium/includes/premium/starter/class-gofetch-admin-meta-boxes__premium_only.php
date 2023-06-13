<?php
/**
 * Sets up the write panels used by the schedules (custom post types).
 *
 * @package GoFetch/Admin/Premium/Starter/Meta Boxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Schedules meta boxes base class.
 */
class GoFetch_Schedule_Meta_Boxes {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 10 );
		add_action( 'add_meta_boxes', array( $this, 'rename_meta_boxes' ), 20 );
		add_action( 'admin_init', array( $this, 'add_meta_boxes' ), 30 );
	}

	/**
	 * Removes Meta boxes.
	 */
	public function remove_meta_boxes() {

		$remove_boxes = array( 'authordiv' );

		foreach ( $remove_boxes as $id ) {
			remove_meta_box( $id, GoFetch_Jobs()->post_type, 'normal' );
		}

	}

	/**
	 * Renames Meta boxes.
	 */
	public function rename_meta_boxes() {
		add_meta_box( 'authordiv', __( 'Job Listers', 'gofetch-wpjm' ) , array( $this, 'post_author_meta_box' ), GoFetch_Jobs()->post_type, 'side', 'low' );
	}

	/**
	 * Add Meta boxes.
	 */
	public function add_meta_boxes() {
		new GoFetch_Schedule_Import_Meta_Box();
		new GoFetch_Schedule_Cron_Meta_Box();
		new GoFetch_Schedule_Actions_Meta_Box();
		new GoFetch_Schedule_Period_Meta_Box();
		new GoFetch_Schedule_Logger_Meta_Box();
	}

	/**
	 * Display custom form field with list of job listers.
	 */
	public function post_author_meta_box( $post ) {
		global $user_ID, $wp_version;
	?>
	<label class="screen-reader-text" for="post_author_override"><?php _e( 'Job Lister', 'gofetch-wpjm' ); ?></label>
	<?php
		$job_listers = apply_filters( 'goft_wpjm_job_listers', GoFetch_Admin_Settings::get_users() );

		$include = array();

	foreach ( $job_listers as $user ) {
		$include[] = $user->ID;
	}

	if ( version_compare( $wp_version, '4.5.0', '>=' ) ) {
		$show = 'display_name_with_login';
	} else {
		$show = 'display_name';
	}

		wp_dropdown_users(
			array(
				'name'     => 'post_author_override',
				'include'  => implode( ',', $include ),
				'show'     => $show,
				'class'    => 'gofj-select2',
				'selected' => empty( $post->ID ) ? $user_ID : $post->post_author,
			)
		);
	}

}

/**
 * The import settings meta box for the schedules.
 */
class GoFetch_Schedule_Import_Meta_Box extends scbPostMetabox {

	/**
	 * Constructor.
	 */
	public function __construct() {

		parent::__construct( 'goft_wpjm-export', __( 'Import Template', 'gofetch-wpjm' ), array(
			'post_type' => GoFetch_Jobs()->post_type,
			'context'   => 'normal',
			'priority'  => 'high',
		) );

	}

	public function before_form( $post ) {
		echo __( 'Select the pre-defined template to use in the import process. The process will use the selected template setup for importing jobs to your database.', 'gofetch-wpjm' );
	}

	/**
	 * Meta box custom meta fields.
	 */
	public function form_fields() {
		global $goft_wpjm_options, $post;

		if ( empty( $goft_wpjm_options->templates ) ) {
			$templates = array( '' => __( 'No templates found', 'gofetch-wpjm' ) );
		} else {
			$templates_list = GoFetch_Helper::get_sanitized_templates();
			$templates = array_keys( $templates_list );
		}

		$replace_jobs = false;

		if ( $template_name = get_post_meta( $post->ID, '_goft_wpjm_template', true ) ) {
			$template_settings = $templates_list[ $template_name ];

			$replace_jobs = ! empty( $template_settings['replace_jobs'] ) && 'yes' === $template_settings['replace_jobs'] ;
		}

		return array(
			array(
				'title'   => __( 'Template Name', 'gofetch-wpjm' ),
				'type'    => 'select',
				'name'    => '_goft_wpjm_template',
				'choices' => $templates,
				'desc'    => sprintf( __( '<a href="%s">Create Template</a>', 'gofetch-wpjm' ), esc_url( add_query_arg( 'page', GoFetch_Jobs()->slug, 'admin.php' ) ) )
					. ( $replace_jobs ? html( 'p style="color: #E65D5D;"', __( '<strong>NOTE:</strong> This template was configured to replace any previous jobs that already inserted to the database.' ) ) : '' )
			),
		);

	}

}

/**
 * The cron settings meta box for the schedules.
 */
class GoFetch_Schedule_Cron_Meta_Box extends scbPostMetabox {

	/**
	 * Constructor.
	 */
	public function __construct() {

		parent::__construct( 'goft_wpjm-time', __( 'Schedule', 'gofetch-wpjm' ), array(
			'post_type' => GoFetch_Jobs()->post_type,
			'context'   => 'side',
		) );

	}

	public function after_form( $post ) {
		echo __( '<strong>Weekly /</strong> Runs every monday<br/><strong>Monthly /</strong> Runs on the 1st of each month', 'gofetch-wpjm' );
	}

	/**
	 * Meta box custom meta fields.
	 */
	public function form_fields() {

		return array(
			array(
				'title'   => __( 'Run Once Every...', 'gofetch-wpjm' ),
				'type'    => 'select',
				'name'    => '_goft_wpjm_cron',
				'choices' => apply_filters( 'goft_wpjm_recurrence_options', array(
					'hourly'      => __( 'Hour', 'gofetch-wpjm' ),
					'twice_daily' => __( '12 Hours', 'gofetch-wpjm' ),
					'daily'       => __( 'Day', 'gofetch-wpjm' ),
					'weekly'      => __( 'Week', 'gofetch-wpjm' ),
					'monthly'     => __( 'Month', 'gofetch-wpjm' ),
				)),
			),
		);

	}

}

/**
 * The cron settings meta box for the schedules.
 */
class GoFetch_Schedule_Actions_Meta_Box extends scbPostMetabox {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_footer', array( $this, 'actions_js' ) );

		parent::__construct( 'goft_wpjm-action', __( 'Actions', 'gofetch-wpjm' ), array(
			'post_type' => GoFetch_Jobs()->post_type,
			'context'   => 'side',
		) );

	}

	/**
	 * The metabox output.
	 */
	public function display( $post ) {

		$this->actions_styles();

		echo html( 'p', __( 'Use the actions below to manually run this schedule or to test the outcome.', 'gofetch-wpjm' ) );

		echo html( 'p', __( 'Make sure you save any configuration changes first.', 'gofetch-wpjm' ) );

		if ( in_array( $post->post_status, array( 'auto-draft' ) ) ) {
			echo html( 'p', html( 'strong', __( 'Please configure the schedule first.', 'gofetch-wpjm' ) ) );
			return;
		}

		echo '<div class="actions">';

		$admin_actions = array(
			'test' => array(
				'action' => 'test_import',
				'name'   => __( 'Test', 'wp-job-manager' ),
				'url'    => wp_nonce_url( add_query_arg( 'goft_test_import', $post->ID ), 'test_import' ),
			),
			'run' => array(
				'action' => 'run_import',
				'name' => __( 'Run', 'wp-job-manager' ),
				'url'  => wp_nonce_url( add_query_arg( 'goft_run_import', $post->ID ), 'run_import' ),
			),
		);

		foreach ( $admin_actions as $action ) {
			printf( '<a class="button %1$s goft-action" data-action="%2$s" data-postid="%3$d" href="%4$s" title="%5$s">%5$s</a>', 'run_import' == $action['action'] ? 'button-primary' : 'button-secondary' , esc_attr( $action['action'] ), esc_attr( $post->ID ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_html( $action['name'] ) );
		}
		echo '</div>';

		echo html( 'div class="goft-temp wait-message" style="display: none"', __( 'Schedule is running. Please wait....',  'gofetch-wpjm' ) );
		echo html( 'div class="goft-temp wait-message" style="display: none"', __( 'This page will refresh when the process ends and you\'ll be able to see the results on the table below.',  'gofetch-wpjm' ) );
	}

	/**
	 * Custom JS code.
	 */
	public function actions_js() {
	?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {

				$('.goft-action').on( 'click', function(e) {

					var button$ = $(this);

					$('.goft-action').hide();
					$(this).after('<div class="goft_wpjm processing-dog goft-temp">&nbsp;</div>');
					$('.wait-message').fadeIn();

					var data = {
						action      : 'goft_wpjm_run_schedule',
						_ajax_nonce : '<?php echo wp_create_nonce( 'goft_wpjm_nonce' ); ?>',
						post_id     : $(this).data('postid'),
						goft_action : $(this).data('action'),
					};

					$.post( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data, function( response ) {

						$('.goft-action').show();
						$('.goft-temp').fadeOut().remove();

						// Reload scheduler page on import
						location.reload();
					});

					e.preventDefault();
					return false;
				});

			});
		</script>
	<?php
	}

	/**
	 * Custom CSS for the meta box.
	 */
	protected function actions_styles() {
	?>
		<style type="text/css">
			.test-import {
			    background-color: #ececec;
			    color: #8a8a8a;
			}
			.test-import-msg {
				margin-left: 10px;
				color: #8a8a8a;
			}
			#goft_wpjm-action .actions {
				padding: 10px;
				text-align: center;
			}
			#goft_wpjm-action .actions a.button.goft-action {
				margin-right: 10px;
			    padding: 0 20px;
			}
			.wait-message {
			    display: block;
			    padding: 10px;
			    background-color: #f1f1f1;
			    margin: 10px;
			}
		</style>
	<?php
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

		self::_run_schedule( $post, '', $action === 'test_import' );
		die( 1 );
	}

}

/**
 * The time period meta box for the schedules.
 */
class GoFetch_Schedule_Period_Meta_Box extends scbPostMetabox {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_footer', array( $this, 'actions_js' ) );

		parent::__construct( 'goft_wpjm-content-period', __( 'Content', 'gofetch-wpjm' ), array(
			'post_type' => GoFetch_Jobs()->post_type,
			'context'   => 'normal',
		) );
	}

	public function before_form( $post ) {
		echo __( 'Limit the content being imported by choosing the time period that should match the jobs being imported and the number of jobs to import every time this scheduled import runs.', 'gofetch-wpjm' );
		echo ' ' . __( 'You can also limit the jobs being imported to those who contain a pre-set list of keywords.', 'gofetch-wpjm' );
	}

	/**
	 * Meta box custom meta fields.
	 */
	public function form_fields() {

		return array(
			array(
				'title'   => __( 'Jobs From...', 'gofetch-wpjm' ),
				'type'    => 'select',
				'name'    => '_goft_wpjm_period',
				'choices' => array(
					'custom' => __( 'Custom', 'gofetch-wpjm' ),
					'today'  => __( 'Today', 'gofetch-wpjm' ),
				),
				'extra' => array( 'id' => '_goft_wpjm_period' ),
				'desc'  => html( 'p class="today-warn"', '<strong>Important:</strong> ' . __( 'Not recommended unless you are sure the RSS feed you chose is updated everyday and the schedule runs after the feed is updated by the provider. Otherwise the schedule may not import any jobs.', 'gofetch-wpjm' ) ),
			),
			array(
				'title' => __( 'Last...', 'gofetch-wpjm' ),
				'type'  => 'text',
				'name'  => '_goft_wpjm_period_custom',
				'extra' => array(
					'id'    => '_goft_wpjm_period_custom',
					'class' => 'small-text',
				),
				'desc' => __( 'days *', 'gofetch-wpjm' ) .
						  '<br><br/><strong>(*)</strong> ' . __( 'The recommend minimum is <strong>2 days</strong> since most feeds are not usually updated to the current or previous day.', 'gofetch-wpjm' ),
			),
			array(
				'title' => __( 'Limit', 'gofetch-wpjm' ),
				'type'  => 'text',
				'name'  => '_goft_wpjm_limit',
				'extra' => array(
					'class'     => 'small-text',
					'maxlength' => 5,
				),
				'desc' => __( 'job(s)', 'gofetch-wpjm' ) .
						  '<br/><br/>' . __( 'Leave empty to import all jobs found.', 'gofetch-wpjm' ),
			),
			array(
				'title' => __( 'Keywords Filtering', 'gofetch-wpjm' ),
				'type'  => 'custom',
				'name'   => '_blank',
				'render' => function() {
					return '<em>Include/Exclude jobs based on their keywords</em><hr/>';
				},
				'tr'    => 'temp-tr-hide tr-keywords tr-advanced',
			),
			array(
				'title' => __( 'Positive Comparison', 'gofetch-wpjm' ),
				'type'  => 'select',
				'name'  => '_goft_wpjm_keywords_comparison',
				'choices'  => array(
					'OR' => 'OR',
					'AND' => 'AND',
				),
				'tip'  => __( '<code>OR</code> If at least one of the positive keywords is found inside the fields you\'ve selected to search, the job is considered valid.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( '<code>AND</code> ALL positive keywords MUST be found inside the fields you\'ve selected to search, to consider the job valid.', 'gofetch-wpjm' ),
				'tr'    => 'temp-tr-hide tr-keywords tr-advanced',
			),
			array(
				'title' => __( 'Positive Keywords', 'gofetch-wpjm' ),
				'type'  => 'text',
				'name'  => '_goft_wpjm_keywords',
				'extra' => array(
					'class'       => 'large-text',
					'placeholder' => 'e.g: sales manager, marketing assistant',
				),
				'desc'  => __( 'Comma separated list of keywords that jobs MUST contain.', 'gofetch-wpjm' ),
			),
			array(
				'title' => __( 'Negative Comparison', 'gofetch-wpjm' ),
				'type'  => 'select',
				'name'  => '_goft_wpjm_keywords_exclude_comparison',
				'choices'  => array(
					'OR' => 'OR',
					'AND' => 'AND',
				),
				'tip'  => __( '<code>OR</code> ANY negative keywords found on the fields you\'ve selected to search, will invalidate the job.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( '<code>AND</code> Discards jobs that contain ALL the negative keywords found inside the fields you\'ve selected to search.', 'gofetch-wpjm' ),
				'tr'    => 'temp-tr-hide tr-keywords tr-advanced',
			),
			array(
				'title' => __( 'Negative Keywords', 'gofetch-wpjm' ),
				'type'  => 'text',
				'name'  => '_goft_wpjm_keywords_exclude',
				'extra' => array(
					'class'       => 'large-text',
					'placeholder' => 'e.g: design, sales, marketing',
				),
				'desc'  => __( 'Comma separated list of keywords that jobs MUST not contain.', 'gofetch-wpjm' ),
			),
		);

	}

	/**
	 * Custom JS code.
	 */
	public function actions_js() {
	?>
		<script type="text/javascript">
		    jQuery(document).ready(function($) {

				$('#_goft_wpjm_period_custom').closest('tr').hide();

				$(document).on( 'change', '#_goft_wpjm_period', function() {

					$('.today-warn').hide();

					if ( 'custom' == $(this).val() ) {
						$('#_goft_wpjm_period_custom' ).addClass('required').closest('tr').fadeIn();
					} else {
						$('#_goft_wpjm_period_custom' ).closest('tr').hide();
						$('.today-warn').show();
					}

				});

				$('#_goft_wpjm_period').trigger('change');

			});
		</script>
	<?php
	}

}

/**
 * Displays a list of messages for the current schedule.
 */
class GoFetch_Schedule_Logger_Meta_Box extends scbPostMetabox {

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct( 'goft_wpjm-import-log', __( 'Import Log', 'gofetch-wpjm' ), array(
			'post_type' => GoFetch_Jobs()->post_type,
			'context'   => 'normal',
		) );
	}

	public function display( $post ) {
		$table = new GoFetch_Log_Message_Table( new BC_Framework_Logger( $post->ID ) );
		$table->display();
	}
}

/**
 * The table class for the log messages.
 */
class GoFetch_Log_Message_Table {

	protected $log;

	public function __construct( BC_Framework_Log $log ) {
		$this->log = $log;
	}

	public function display() {

		$log_limit = GoFetch_Jobs()->log_limit;

		$this->log_styles();
		$messages = $this->log->get_log();

		if ( ! $messages ) {
			echo '<tr><td colspan="3">' . __( 'Move along. Nothing to see here yet.', 'gofetch-wpjm' ) . '</td></tr>';
			return;
		}

		echo '<table class="gofetch-message-log widefat">';
		echo '<tr>
			  <th>' . __( 'Date', 'gofetch-wpjm' ) . '</th>
			  <th>' . __( 'Stats', 'gofetch-wpjm' ) . '</th>
			  <th>' . __( 'Duration', 'gofetch-wpjm' ) . '</th>
			  <th>' . __( 'Status', 'gofetch-wpjm' ) . '</th>
			  </tr>';

		$messages = array_reverse( $messages );

		foreach ( $messages as $data ) {
			echo '<tr class="' . esc_attr( $data['type'] ) . '">';
			echo '<td><span class="timestamp" >' . date( get_option( 'date_format', 'Y-m-d' ) . ' H:i', strtotime( $data['time'] ) ) . '</span></td>';
			echo '<td><span class="message">' . ( is_array( $data['message'] ) ? self::get_stats_message( $data['message'] ) : $data['message'] ) . '</span></td>';
			echo '<td><span class="duration">' . ( is_array( $data['message'] ) && ! empty( $data['message']['duration'] ) ? date( 'i:s', $data['message']['duration'] ) : '-' ) . '</span></td>';
			echo '<td><span class="type" >' . $this->get_type_label( $data['type'] ) . '</span></td>';
			echo '</tr>';
		}

		echo '</table>';

		echo html( 'p', html( 'small', html( 'em', sprintf( __( '<strong>Note I:</strong> If you keep getting 0 jobs imported try tweaking the content period to include a longer period of time. It can also happen if the feed is not regularly updated by the provider. You can check it by opening the feed directly and look at the jobs date in &lt;pubdate&gt;.', 'gofetch-wpjm' ), $log_limit ) ) ) );
		echo html( 'p', html( 'small', html( 'em', sprintf( __( '<strong>Note II:</strong> The log keeps only the last %d import stats.', 'gofetch-wpjm' ), $log_limit ) ) ) );
	}

	/**
	 * Retrieves the label that corresponds to the message type.
	 */
	protected function get_type_label( $type ) {

		$labels = array(
			'success' => __( 'Success', 'gofetch-wpjm' ),
			'error'   => __( 'Error', 'gofetch-wpjm' ),
		);

		if ( empty( $labels[ $type ] ) ) {
			return;
		}
		return $labels[ $type ];
	}

	/**
	 * Retrieves log messages stored as a stats array.
	 */
	public static function get_stats_message( $data ) {
		global $pagenow;

		$message = '';

		$is_test = false;

		if ( ! empty( $data['test'] ) ) {
			$is_test = true;
		}

		if ( isset( $data['test'] ) ) {
			unset( $data['test'] );
		}

		foreach ( $data as $type => $total ) {
			$message .= self::formatted_stats_message( $type, $total, $is_test );
		}

		if ( is_admin() && 'edit.php' !== $pagenow && $is_test ) {
			$message .= html( 'small class="test-import-msg"', __( 'Test Run (no jobs were imported or updated)', 'gofetch-wpjm' ) );
		}

		return $message;
	}

	/**
	 * Retrieves a formated stats message considering the stats type.
	 */
	protected static function formatted_stats_message( $type, $total, $test = false ) {

		$types = array( 'imported', 'limit', 'duplicates', 'updated', 'excluded', 'in_rss_feed' );

		if ( ! in_array( $type, $types ) ) {
			return '';
		}

		switch ( $type ) {

			case 'imported':
				$icon  = 'icon icon-goft-download-cloud';
				$desc = __( 'Jobs Imported', 'gofetch-wpjm' );
				break;

			case 'limit':
				$icon  = 'icon icon-goft-to-end-alt';
				$desc = __( 'Skipped Jobs (discarded - enforced import limit)', 'gofetch-wpjm' );
				break;

			case 'duplicates':
				$icon  = 'icon icon-goft-docs';
				$desc = __( 'Duplicate Jobs (discarded - already exist in DB)', 'gofetch-wpjm' );
				break;

			case 'updated':
				$icon  = 'icon icon-goft-pencil';
				$desc = __( 'Updated Jobs (already exist in DB)', 'gofetch-wpjm' );
				break;

			case 'excluded':
				$icon = 'icon icon-goft-attention';
				$desc = __( 'Excluded Jobs (discarded - criteria not met)', 'gofetch-wpjm' );
				break;

			default:
				$icon = 'icon icon-goft-rss';
				$desc = __( 'Jobs in RSS Feed', 'gofetch-wpjm' );
				break;
		}

		return sprintf( '<span class="log-stats %1$s" title="%2$s">%3$s %4$s</span>',  esc_attr( $test ? 'test-import' : '' ), esc_attr( $desc ), '<span alt="' . $type. '" class="' . esc_attr( $icon ) . '"></span>', $total );
	}

	/**
	 * Custom CSS for the meta box.
	 */
	protected function log_styles() {
	?>
		<style type="text/css">
			.gofetch-message-log.widefat {
				border: 0;
			}
			.gofetch-message-log th {
				font-weight: bold;
				padding-left: 3px;
			}
			.gofetch-message-log th {
				border-bottom: 1px solid rgba(0, 0, 0, 0.18);
			}
			.gofetch-message-log td {
				border-top: 1px solid #F3F3F3;
				padding: 5px;
			}
			.gofetch-message-log td:first-child {
				width: 200px;
			}
			.gofetch-message-log .major .message {
				font-weight: bold;
			}
			.gofetch-message-log .minor .timestamp {
				color: #999;
			}
			.gofetch-message-log .info .timestamp {
				display: none;
			}
			.gofetch-message-log .log-stats {
			    padding-right: 15px;
			}
			.gofetch-message-log.widefat .log-stats:first-of-type {
				min-width: 40px;
				display: inline-block;
			}
			.gofetch-message-log.widefat .log-stats:nth-child(2) {
				margin-left: 20px;
			}
			@media screen and (max-width: 782px) {
				.gofetch-message-log.widefat .log-stats {
					display: block;
				}
				.gofetch-message-log.widefat .log-stats:nth-child(2) {
					margin-left: 0;
				}
			}
		</style>
	<?php
	}

}

new GoFetch_Schedule_Meta_Boxes;
