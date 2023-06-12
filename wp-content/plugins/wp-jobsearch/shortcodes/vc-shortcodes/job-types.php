<?php
/**
 * Job Types Shortcode
 * @return html
 */
add_shortcode('jobsearch_job_types', 'jobsearch_job_types_shcallback');

function jobsearch_job_types_shcallback($atts) {
    global $jobsearch_plugin_options, $wpdb, $sitepress;

    extract(shortcode_atts(array(
        'cats_view' => '',
        'num_cats' => '',
        'result_page' => '',
        'cat_title' => '',
        'sector_job_counts' => 'yes',
        'sub_cats' => 'yes',
        'cat_link_text' => '',
        'cat_link_text_url' => '',
        'icon_color' => '',
        'job_bg_color' => '',
        'num_cats_child' => '',
        'order_by' => 'jobs_count',
                    ), $atts));

    $to_result_page = $result_page;

    $joptions_search_page = isset($jobsearch_plugin_options['jobsearch_search_list_page']) ? $jobsearch_plugin_options['jobsearch_search_list_page'] : '';
    if ($joptions_search_page != '') {
        $joptions_search_page = jobsearch__get_post_id($joptions_search_page, 'page');
    }
    if ($result_page <= 0 && $joptions_search_page > 0) {
        $to_result_page = $joptions_search_page;
    }

    $to_result_page = absint($to_result_page);

    if ($order_by == 'id') {
        $get_db_terms = array();
        $all_sectors = get_terms(array(
            'taxonomy' => 'jobtype',
            'hide_empty' => false,
        ));
        if (!empty($all_sectors) && !is_wp_error($all_sectors)) {
            foreach ($all_sectors as $term_sec_obj) {
                $get_db_terms[] = $term_sec_obj->term_id;
            }
        }
    } else {
        $cats_query = "SELECT terms.term_id FROM $wpdb->terms AS terms";
        $cats_query .= " LEFT JOIN $wpdb->term_taxonomy AS term_tax ON(terms.term_id = term_tax.term_id) ";
        $cats_query .= " LEFT JOIN $wpdb->termmeta AS term_meta ON(terms.term_id = term_meta.term_id) ";
        if (function_exists('icl_object_id')) {
            $trans_tble = $wpdb->prefix . 'icl_translations';
            $cats_query .= " LEFT JOIN $trans_tble AS icl_trans ON (terms.term_id = icl_trans.element_id)";
        }
        $cats_query .= " WHERE term_tax.taxonomy=%s AND term_meta.meta_key=%s";
        if ($sub_cats == 'yes') {
            $cats_query .= " AND term_tax.parent=0";
        }
        if (function_exists('icl_object_id')) {
            $cats_query .= " AND icl_trans.language_code='" . $sitepress->get_current_language() . "'";
        }
        if ($order_by == 'title') {
            $cats_query .= " ORDER BY terms.name ASC";
        } else {
            $cats_query .= " ORDER BY cast(term_meta.meta_value as unsigned) DESC";
        }
        $get_db_terms = $wpdb->get_col($wpdb->prepare($cats_query, 'jobtype', 'active_jobs_count'));
    }

    ob_start();
    ?>
    <div class="categories-list">
        <?php
        if (!empty($get_db_terms) && !is_wp_error($get_db_terms)) {
            ?>
            <ul class="jobsearch-row">
                <?php
                foreach ($get_db_terms as $term_id) {
                    $term_sector = get_term_by('id', $term_id, 'jobtype');
                    $job_args = array(
                        'posts_per_page' => '1',
                        'post_type' => 'job',
                        'post_status' => 'publish',
                        'fields' => 'ids', // only load ids
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'jobtype',
                                'field' => 'slug',
                                'terms' => $term_sector->slug
                            )
                        ),
                        'meta_query' => array(
                            array(
                                'key' => 'jobsearch_field_job_publish_date',
                                'value' => strtotime(current_time('d-m-Y H:i:s')),
                                'compare' => '<=',
                            ),
                            array(
                                'key' => 'jobsearch_field_job_expiry_date',
                                'value' => strtotime(current_time('d-m-Y H:i:s')),
                                'compare' => '>=',
                            ),
                            array(
                                'key' => 'jobsearch_field_job_status',
                                'value' => 'approved',
                                'compare' => '=',
                            )
                        ),
                    );
                    $jobs_query = new WP_Query($job_args);
                    $found_jobs = $jobs_query->found_posts;
                    wp_reset_postdata();

                    //
                    $cat_goto_link = add_query_arg(array('job_type' => $term_sector->slug), get_permalink($to_result_page));
                    ?>
                    <li class="jobsearch-column-3">
                        <a href="<?php echo($cat_goto_link) ?>"><?php echo($term_sector->name) ?></a>
                        <?php
                        if ($found_jobs == 1) {
                            ?>
                            <span><?php printf(esc_html__('(%s Open Vacancy)', 'wp-jobsearch'), $found_jobs) ?></span>
                            <?php
                        } else {
                            ?>
                            <span><?php printf(esc_html__('(%s Open Vacancies)', 'wp-jobsearch'), $found_jobs) ?></span>
                            <?php
                        }
                        ?>
                    </li>
                    <?php
                }
                ?>
            </ul>
            <?php
        }
        ?>
    </div>
    <?php
    $html = ob_get_clean();
    return $html;
}
