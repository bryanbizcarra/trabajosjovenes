<?php
/**
 * Active module configuration.
 *
 * @package GoFetch/Module/Careerfy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $gofj_module_settings;

$settings = array(

	/**
	 * Specific options for the plugin/theme.
	 */
	'options' => array(

		'jobs_duration'            => get_option( 'jobsearch_plugin_options[free-job-post-expiry', 30 ),
		'admin_jobs_roles'         => array( 'jobsearch_employer', 'jobsearch_empmnger' ),

		// Taxonomies.
		'setup_post_type'          => 'job',
		'setup_job_category'       => 'sector',
		'setup_job_type'           => 'jobtype',
		'setup_job_location'       => '',

		'setup_expired_status'     => 'expired',

		'setup_field_company_name' => '_gofj_company',
		'setup_field_company_logo' => '_gofj_company_logo',
		'setup_field_company_url'  => '_gofj_company_url',

		'setup_field_application'       => 'jobsearch_field_job_apply_url',
		'setup_field_application_email' => 'jobsearch_field_job_apply_email',

		'setup_field_location'     => 'jobsearch_field_location_address',

		'setup_field_country'      => 'jobsearch_field_location_location2',
		'setup_field_city'         => 'jobsearch_field_location_address',

		'setup_field_state'        => 'jobsearch_field_location_location2',
		'setup_field_latitude'     => 'jobsearch_field_location_lat',
		'setup_field_longitude'    => 'jobsearch_field_location_lng',

		'setup_field_featured'     => 'jobsearch_field_job_featured',
		'setup_field_expiration'   => 'jobsearch_field_job_expiry_date',

		'setup_field_salary_min'   => 'jobsearch_field_job_salary',
		'setup_field_salary_max'   => 'jobsearch_field_job_max_salary',
		'setup_field_salary'       => 'jobsearch_field_job_salary',

		// Specific.
		'setup_job_status'            => 'jobsearch_field_job_status',
		'setup_job_apply_type'        => 'jobsearch_field_job_apply_type',
		'setup_job_posted_by'         => 'jobsearch_field_job_posted_by',
		'setup_job_publish_date'      => 'jobsearch_field_job_publish_date',
		'setup_job_presnt_status'     => 'jobsearch_job_presnt_status',
		'setup_job_employer_approved' => 'jobsearch_field_employer_approved',
		'setup_job_employer_status'   => 'jobsearch_job_employer_status',

		'setup_user_employer_id'      => 'jobsearch_employer_id',

		'override_employers'          => true,
	),

);

$gofj_module_settings = $settings;
