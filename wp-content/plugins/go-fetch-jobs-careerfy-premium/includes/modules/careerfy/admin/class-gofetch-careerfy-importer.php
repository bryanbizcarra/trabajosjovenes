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
 * WPJM specific import functionality.
 */
class GoFetch_Careerfy_Import extends GoFetch_Importer {

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
		add_filter( 'goft_wpjm_field_mappings_core_fields', array( $this, 'additional_core_fields' ) );
		add_filter( 'goft_wpjm_field_nuclear_fields', array( $this, 'additional_nuclear_fields' ) );
		add_filter( 'goft_wpjm_item_meta_value', array( $this, 'replace_item_meta_placeholders' ), 20, 5 );
		add_action( 'goft_wpjm_after_insert_job', array( $this, 'set_additional_meta' ), 25, 4 );
		add_filter( 'load-toplevel_page_go-fetch-jobs-careerfy', array( $this, 'remove_location_filters' ) );
	}

	/**
	 * Add the apply by email field.
	 */
	public function additional_core_fields( $fields ) {
		$fields['jobsearch_field_job_apply_email'] = 'APPLICATION EMAIL';
		return $fields;
	}

	/**
	 * Add the apply by email field to the nuclear fields list.
	 */
	public function additional_nuclear_fields( $fields ) {
		$fields['dynamic']['application'][] = 'setup_field_application_email';
		return $fields;
	}

	/**
	 * Replaces string placeholders with valid data on a given meta key.
	 */
	public function replace_item_meta_placeholders( $meta_value, $meta_key, $item, $post_id, $params ) {
		global $goft_wpjm_options;

		switch ( $meta_key ) {

			case $goft_wpjm_options->setup_field_featured:
				$meta_value = $meta_value ? 'on' : '';
				break;

			case $goft_wpjm_options->setup_field_expiration:
				$curr_date = date( 'Y-m-d', current_time( 'timestamp' ) );

				// Get the value provided by the user (if greater then current date) or default to settings duration.
				if ( $meta_value && strtotime( $meta_value ) > strtotime( $curr_date ) ) {
					return strtotime( $meta_value );
				}

				if ( $duration = $goft_wpjm_options->jobs_duration ) {
					$date = current_time( 'timestamp' );
					$meta_value = strtotime( $date . ' +' . absint( $duration ) . ' days' );
				}
				break;

		}
		return $meta_value;
	}

	/**
	 * Add additional metadata.
	 */
	public function set_additional_meta( $post_id, $item, $params, $meta ) {
		global $goft_wpjm_options;

		// Set job status.
		if ( 'publish' === $goft_wpjm_options->post_status ) {
			$state = 'approved';
			update_post_meta( $post_id, $goft_wpjm_options->setup_job_status, $state );
		}

		// Set custom publish date.
		update_post_meta( $post_id, $goft_wpjm_options->setup_job_publish_date, current_time( 'timestamp' ) );

		// Set job apply type.
		$apply_type = 'external';

		if ( ! empty( $meta[ $goft_wpjm_options->setup_field_application_email ] ) ) {
			$apply_type = 'with_email';
		}
		update_post_meta( $post_id, $goft_wpjm_options->setup_job_apply_type, $apply_type );

		// Job and Employer status.

		// Get the employer ID to get his approval status.
		$employer_id = get_user_meta( $params['post_author'], $goft_wpjm_options->setup_user_employer_id, true );

		$approved_employer_state = get_post_meta( $employer_id, $goft_wpjm_options->setup_job_employer_approved, true );

		if ( 'on' === $approved_employer_state ) {
			update_post_meta( $post_id, $goft_wpjm_options->setup_job_employer_status, 'approved' );
		}

		update_post_meta( $post_id, $goft_wpjm_options->setup_job_posted_by, $employer_id );
		update_post_meta( $post_id, $goft_wpjm_options->setup_job_presnt_status, 'approved' );
	}

	/**
	 * Removes filters that cause issues with 'wp_set_object_terms()'.
	 */
	public function remove_location_filters() {
		remove_action( 'pre_get_terms', 'jobsearch_owncustax_chnge_get_terms' );
		remove_filter( 'get_terms', 'jobsearch_custom_modify_terms', 12, 4 );
	}

}

GoFetch_Careerfy_Import::instance();
