<?php
global $jobsearch_plugin_options;
$output = '';
$left_filter_count_switch = 'no';
$filters_op_sort = isset($jobsearch_plugin_options['emp_srch_filtrs_sort']) ? $jobsearch_plugin_options['emp_srch_filtrs_sort'] : '';
$filters_op_sort = isset($filters_op_sort['fields']) ? $filters_op_sort['fields'] : '';
//////
if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
    global $sitepress;
    $trans_able_options = $sitepress->get_setting('custom_posts_sync_option', array());
}

//////
$args_count['posts_per_page'] = '-1';
$actual_count_args = $sector_count_args = $args_count;
$jobs_loop_obj = new WP_Query($args_count);
$job_totnum = $all_get_posts = $jobs_loop_obj->posts;
if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher') && $job_totnum == 0 && isset($trans_able_options['employer']) && $trans_able_options['employer'] == '2') {
    $sitepress_def_lang = $sitepress->get_default_language();
    $sitepress_curr_lang = $sitepress->get_current_language();
    $sitepress->switch_lang($sitepress_def_lang, true);

    $job_qry = new WP_Query($args_count);

    $all_get_posts = $job_qry->posts;

    //
    $sitepress->switch_lang($sitepress_curr_lang, true);
}
$taxn_loc_counts = $sector_args_count = $args_count = $all_get_posts;

$all_locations_type = isset($jobsearch_plugin_options['all_locations_type']) ? $jobsearch_plugin_options['all_locations_type'] : '';
if ($all_locations_type != 'api' && function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
    $sitepress_def_lang = $sitepress->get_default_language();
    $sitepress_curr_lang = $sitepress->get_current_language();
    $sitepress->switch_lang($sitepress_def_lang, true);

    $job_qry = new WP_Query($actual_count_args);

    $taxn_loc_counts = $job_qry->posts;

    //
    $sitepress->switch_lang($sitepress_curr_lang, true);
}
?>
<div class="jobsearch-column-3 jobsearch-typo-wrap listin-filters-sidebar">
    <a href="javascript:void(0);" class="close-listin-mobfiltrs"><i class="fa fa-close"></i></a>
    <?php
    $sh_atts = isset($employer_arg['atts']) ? $employer_arg['atts'] : '';
    if (isset($sh_atts['employer_filters_count']) && $sh_atts['employer_filters_count'] == 'yes') {
        $left_filter_count_switch = 'yes';
    }

    $filter_sort_by = isset($sh_atts['employer_filters_sortby']) ? $sh_atts['employer_filters_sortby'] : '';

    if (!empty($filters_op_sort)) {
        global $jobsearch_onlycffield_name;
        $cusfields_names_arr = array();
        $emp_cus_fields = get_option("jobsearch_custom_field_employer");
        if (!empty($emp_cus_fields)) {
            foreach ($emp_cus_fields as $cus_fieldvar => $cus_field) {
                if (isset($cus_field['name']) && $cus_field['name'] != '') {
                    $cusfield_name = $cus_field['name'];
                    $cusfields_names_arr[] = $cusfield_name;
                }
            }
        }
        foreach ($filters_op_sort as $filter_sort_key => $filter_sort_val) {
            if ($filter_sort_key == 'date_posted') {
                $output .= apply_filters('jobsearch_employer_filter_date_posted_box_html', '', $global_rand_id, $args_count, $left_filter_count_switch, $sh_atts);
            } else if ($filter_sort_key == 'location') {
                $output .= apply_filters('jobsearch_employer_filter_location_box_html', '', $global_rand_id, $taxn_loc_counts, $left_filter_count_switch, $sh_atts);
            } else if ($filter_sort_key == 'sector') {
                $output .= apply_filters('jobsearch_employer_filter_sector_box_html', '', $global_rand_id, $sector_args_count, $left_filter_count_switch, $sh_atts);
            } else if ($filter_sort_key == 'job_type') {
                $output .= apply_filters('jobsearch_employer_filter_employertype_box_html', '', $global_rand_id, $args_count, $left_filter_count_switch, $sh_atts);
            } else if ($filter_sort_key == 'team_size') {
                $output .= apply_filters('jobsearch_team_size_filter_box_html', '', $global_rand_id, $args_count, $left_filter_count_switch, $sh_atts);
            } else if ($filter_sort_key == 'ads') {
                $filter_ads_code = isset($jobsearch_plugin_options['emps_filter_adcode']) ? $jobsearch_plugin_options['emps_filter_adcode'] : '';
                if ($filter_ads_code != '') {
                    ob_start();
                    echo do_shortcode($filter_ads_code);
                    $the_ad_code = ob_get_clean();
                    $output .= '<div class="jobsearch-filter-responsive-wrap"><div class="filter-ads-wrap">' . $the_ad_code . '</div></div>';
                }
            }
            if (!empty($cusfields_names_arr)) {
                foreach ($cusfields_names_arr as $cus_fieldname) {
                    if ($cus_fieldname == $filter_sort_key) {
                        $jobsearch_onlycffield_name = $cus_fieldname;
                        $output .= apply_filters('jobsearch_custom_fields_filter_box_html', '', 'employer', $global_rand_id, $args_count, $left_filter_count_switch, 'jobsearch_employer_content_load', $filter_sort_by);
                    }
                }
            }
        }
    }
    echo force_balance_tags($output);
    
    add_action('wp_footer', function() {
        ?>
        <div class="mobfiltrs-openrbtn-con"><a href="javascript:void(0);" class="open-listin-mobfiltrs"><i class="fa fa-filter"></i></a></div>
        <?php
    }, 14);
    ?>
</div>
