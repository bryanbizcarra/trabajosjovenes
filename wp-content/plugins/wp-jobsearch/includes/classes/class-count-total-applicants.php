<?php
if (!defined('ABSPATH')) {
    die;
}

class jobsearch_count_total_applicants {

    public function __construct() {
        add_action('jobsearch_internal_applics_totalupdate', array($this, 'total_internal_applics_update'), 10, 3);
        add_action('jobsearch_internal_applics_totalcounts', array($this, 'total_internal_applics'));
        
        //
        add_action('jobsearch_job_applic_custom_add', array($this, 'on_apply_internal_applics_count'));
        add_action('jobsearch_job_applying_save_action', array($this, 'on_apply_internal_applics_count'));
        
        add_action('jobsearch_applicant_rejected_for_job', array($this, 'on_reject_internal_applics_count'), 10, 2);
        
        add_action('jobsearch_applicant_shortlisted_for_job', array($this, 'on_shrtlist_internal_applics_count'));
        
        add_action('jobsearch_applicant_undoreject_for_job', array($this, 'on_undoreject_internal_applics_count'));
        
        add_action('jobsearch_applicant_deleted_for_job', array($this, 'applicant_deleted_for_job'), 10, 2);
        
        // before job delete
        add_action('wp_trash_post', array($this, 'on_delete_post_internal_applics_count'), 40);
        add_action('before_delete_post', array($this, 'on_delete_post_internal_applics_count'), 40);
    }
    
    public function total_internal_applics_update($appcounts, $shappcounts, $rejappcounts) {
        $total_counts = array(
            'applicants' => $appcounts,
            'shortlisted' => $shappcounts,
            'rejected' => $rejappcounts,
        );
        
        update_option('jobsearch_internal_applics_counts', $total_counts);
    }
    
    public function total_internal_applics() {
        $args = array(
            'post_type' => 'job',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'draft'),
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'jobsearch_job_applicants_list',
                    'compare' => 'EXISTS',
                ),
            ),
        );
        $jobs_query = new WP_Query($args);
        $jobs_posts = $jobs_query->posts;
        
        $appcounts = $shappcounts = $rejappcounts = 0;
        if (!empty($jobs_posts)) {
            foreach ($jobs_posts as $_job_id) {
                $job_applicants_list = get_post_meta($_job_id, 'jobsearch_job_applicants_list', true);
                $job_applicants_list = jobsearch_is_post_ids_array($job_applicants_list, 'candidate');

                if (empty($job_applicants_list)) {
                    $job_applicants_list = array();
                }

                $job_applicants_count = !empty($job_applicants_list) ? count($job_applicants_list) : 0;
                $appcounts += $job_applicants_count;

                //
                $job_short_int_list = get_post_meta($_job_id, '_job_short_interview_list', true);
                $job_short_int_list = $job_short_int_list != '' ? explode(',', $job_short_int_list) : '';
                if (empty($job_short_int_list)) {
                    $job_short_int_list = array();
                }
                $job_short_int_list = jobsearch_is_post_ids_array($job_short_int_list, 'candidate');
                $job_short_int_list_c = !empty($job_short_int_list) ? count($job_short_int_list) : 0;
                $shappcounts += $job_short_int_list_c;

                $job_reject_int_list = get_post_meta($_job_id, '_job_reject_interview_list', true);
                $job_reject_int_list = $job_reject_int_list != '' ? explode(',', $job_reject_int_list) : '';
                if (empty($job_reject_int_list)) {
                    $job_reject_int_list = array();
                }
                $job_reject_int_list = jobsearch_is_post_ids_array($job_reject_int_list, 'candidate');
                $job_reject_int_list_c = !empty($job_reject_int_list) ? count($job_reject_int_list) : 0;
                $rejappcounts += $job_reject_int_list_c;
            }
        }
        
        $this->total_internal_applics_update($appcounts, $shappcounts, $rejappcounts);
    }
    
    public function on_apply_internal_applics_count() {
        $get_tcounts = get_option('jobsearch_internal_applics_counts');
        if (isset($get_tcounts['applicants'])) {

            $appcounts = absint($get_tcounts['applicants']) + 1;
            $shappcounts = $get_tcounts['shortlisted'];
            $rejappcounts = $get_tcounts['rejected'];
            
            $this->total_internal_applics_update($appcounts, $shappcounts, $rejappcounts);
        }
    }
    
    public function on_shrtlist_internal_applics_count() {
        $get_tcounts = get_option('jobsearch_internal_applics_counts');
        if (isset($get_tcounts['applicants'])) {

            $appcounts = $get_tcounts['applicants'];
            $shappcounts = absint($get_tcounts['shortlisted']) + 1;
            $rejappcounts = $get_tcounts['rejected'];
            
            $this->total_internal_applics_update($appcounts, $shappcounts, $rejappcounts);
        }
    }
    
    public function on_reject_internal_applics_count($job_id, $candidate_id) {
        $get_tcounts = get_option('jobsearch_internal_applics_counts');
        if (isset($get_tcounts['applicants'])) {
            
            $minus_from_apliclist = $minus_from_shortlist = false;
            
            $job_applicants_list = get_post_meta($job_id, 'jobsearch_job_applicants_list', true);
            $job_applicants_list = jobsearch_is_post_ids_array($job_applicants_list, 'candidate');
            if (!empty($job_applicants_list) && in_array($candidate_id, $job_applicants_list)) {
                $minus_from_apliclist = true;
            }
            
            $job_short_int_list = get_post_meta($job_id, '_job_short_interview_list', true);
            $job_short_int_list = $job_short_int_list != '' ? explode(',', $job_short_int_list) : '';
            if (!empty($job_short_int_list) && in_array($candidate_id, $job_short_int_list)) {
                $minus_from_shortlist = true;
            }

            $appcounts = $minus_from_apliclist && $get_tcounts['applicants'] > 0 ? (absint($get_tcounts['applicants']) - 1) : $get_tcounts['applicants'];
            $shappcounts = $minus_from_shortlist && $get_tcounts['shortlisted'] > 0 ? (absint($get_tcounts['shortlisted']) - 1) : $get_tcounts['shortlisted'];
            $rejappcounts = absint($get_tcounts['rejected']) + 1;
            
            $this->total_internal_applics_update($appcounts, $shappcounts, $rejappcounts);
        }
    }
    
    public function on_undoreject_internal_applics_count() {
        $get_tcounts = get_option('jobsearch_internal_applics_counts');
        if (isset($get_tcounts['applicants'])) {

            $appcounts = absint($get_tcounts['applicants']) + 1;
            $shappcounts = $get_tcounts['shortlisted'];
            $rejappcounts = $get_tcounts['rejected'] > 0 ? (absint($get_tcounts['rejected']) - 1) : $get_tcounts['rejected'];
            
            $this->total_internal_applics_update($appcounts, $shappcounts, $rejappcounts);
        }
    }
    
    public function applicant_deleted_for_job($job_id, $candidate_id) {
        $get_tcounts = get_option('jobsearch_internal_applics_counts');
        if (isset($get_tcounts['applicants'])) {
            
            $minus_from_apliclist = $minus_from_shortlist = $minus_from_reject = false;
            
            $job_applicants_list = get_post_meta($job_id, 'jobsearch_job_applicants_list', true);
            $job_applicants_list = jobsearch_is_post_ids_array($job_applicants_list, 'candidate');
            if (!empty($job_applicants_list) && in_array($candidate_id, $job_applicants_list)) {
                $minus_from_apliclist = true;
            }
            
            $job_short_int_list = get_post_meta($job_id, '_job_short_interview_list', true);
            $job_short_int_list = $job_short_int_list != '' ? explode(',', $job_short_int_list) : '';
            if (!empty($job_short_int_list) && in_array($candidate_id, $job_short_int_list)) {
                $minus_from_shortlist = true;
            }
            
            $job_reject_int_list = get_post_meta($job_id, '_job_reject_interview_list', true);
            $job_reject_int_list = $job_reject_int_list != '' ? explode(',', $job_reject_int_list) : '';
            if (!empty($job_reject_int_list) && in_array($candidate_id, $job_reject_int_list)) {
                $minus_from_reject = true;
            }

            $appcounts = $minus_from_apliclist && $get_tcounts['applicants'] > 0 ? (absint($get_tcounts['applicants']) - 1) : $get_tcounts['applicants'];
            $shappcounts = $minus_from_shortlist && $get_tcounts['shortlisted'] > 0 ? (absint($get_tcounts['shortlisted']) - 1) : $get_tcounts['shortlisted'];
            $rejappcounts = $minus_from_reject && $get_tcounts['rejected'] > 0 ? (absint($get_tcounts['rejected']) - 1) : $get_tcounts['rejected'];
            
            $this->total_internal_applics_update($appcounts, $shappcounts, $rejappcounts);
        }
    }
    
    public function on_delete_post_internal_applics_count($post_id) {
        if (get_post_type($post_id) == 'job') {
            $job_id = $post_id;
            
            $job_applicants_list = get_post_meta($job_id, 'jobsearch_job_applicants_list', true);
            $job_applicants_list = jobsearch_is_post_ids_array($job_applicants_list, 'candidate');

            if (!empty($job_applicants_list)) {
                foreach ($job_applicants_list as $jobapp_id) {
                    jobsearch_remov_job_applicant_bycid($job_id, $jobapp_id);
                }
            }
            
            $job_reject_int_list = get_post_meta($job_id, '_job_reject_interview_list', true);
            $job_reject_int_list = $job_reject_int_list != '' ? explode(',', $job_reject_int_list) : '';

            if (!empty($job_reject_int_list)) {
                foreach ($job_reject_int_list as $jobapp_id) {
                    jobsearch_remov_job_applicant_bycid($job_id, $jobapp_id);
                }
            }
        }
        if (get_post_type($post_id) == 'candidate') {
            $candidate_id = $post_id;
            
            $user_id = jobsearch_get_candidate_user_id($candidate_id);
            
            $applied_jobs = get_user_meta($user_id, 'jobsearch-user-jobs-applied-list', true);
            
            if (!empty($applied_jobs) && is_array($applied_jobs)) {
                foreach ($applied_jobs as $aplied_itm) {
                    $job_id = isset($aplied_itm['post_id']) ? $aplied_itm['post_id'] : '';
                    
                    if ($job_id > 0) {
                        jobsearch_remov_job_applicant_bycid($job_id, $candidate_id);
                    }
                }
            }
            //
        }
    }

}

new jobsearch_count_total_applicants;
