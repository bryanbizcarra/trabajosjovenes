<?php
/**
 * Specific frontend code for Careerfy.
 *
 * @package GoFetch/Careerfy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $gofetch_careerfy_frontend;

class GoFetch_Careerfy_Frontend {

	public function __construct() {

		if ( is_admin() ) {
			return;
		}

		add_action( 'goft_wpjm_single_goft_job', array( $this, 'single_job_page_hooks' ) );

		// Call actions outside single job hooks to make sure jpb lists are also overriden.
		add_filter( 'jobsearch_jobemp_image_src', array( $this, 'company_logo' ), 10, 2 );
		add_filter( 'jobsearch_job_compny_title_str', array( $this, 'company_name' ), 10, 2 );
	}

	/**
	 * Actions that should run on the single job page.
	 */
	public function single_job_page_hooks( $post ) {
		global $goft_wpjm_options;

		if ( ! $goft_wpjm_options->override_employers ) {
			return;
		}

		add_filter( 'jobsearch_job_defdet_applybtn_boxhtml', array( $this, 'maybe_allow_visitors_apply' ), 10, 2 );
	}

	/**
	 * Override the default employer logo if there's an imported logo.
	 */
	public function company_logo( $post_thumbnail_src, $job_id ) {
		global $goft_wpjm_options;

		if ( $company_logo = get_post_meta( $job_id, $goft_wpjm_options->setup_field_company_logo, true ) ) {
			$post_thumbnail_src = $company_logo;
		}
		return $post_thumbnail_src;
	}

	/**
	 * Override the default employer name with the imported company name.
	 */
	public function company_name( $company_name_str, $job_id ) {
		global $goft_wpjm_options;

		if ( $company_name = get_post_meta( $job_id, $goft_wpjm_options->setup_field_company_name, true ) ) {
			$company_name_str = $company_name;

			// rRmove the contact button, for overriden employer names.
			add_filter( 'jobsearch_job_send_message_html_filter', '__return_false' );
		}
		return $company_name_str;
	}

	/**
	 * Hide the apply button for visitors, if applicable.
	 */
	public function maybe_allow_visitors_apply( $apply_bbox, $job_id ) {
		global $goft_wpjm_options;

		if ( ! is_user_logged_in() && ! $goft_wpjm_options->allow_visitors_apply ) {
			return '';
		}
		return $apply_bbox;
	}

}

$gofetch_careerfy_frontend = new GoFetch_Careerfy_Frontend();
