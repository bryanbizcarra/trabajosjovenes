<?php
global $jobsearch_plugin_options, $Jobsearch_User_Dashboard_Settings, $wpdb;
$real_user_id = $user_id = get_current_user_id();
$user_obj = get_user_by('ID', $user_id);

$page_id = $user_dashboard_page = isset($jobsearch_plugin_options['user-dashboard-template-page']) ? $jobsearch_plugin_options['user-dashboard-template-page'] : '';
$page_id = $user_dashboard_page = jobsearch__get_post_id($user_dashboard_page, 'page');
$page_url = jobsearch_wpml_lang_page_permalink($page_id, 'page'); //get_permalink($page_id);

$all_location_allow = isset($jobsearch_plugin_options['all_location_allow']) ? $jobsearch_plugin_options['all_location_allow'] : '';

$current_date = current_time('timestamp');

$is_user_member = false;
if (jobsearch_user_isemp_member($user_id)) {
    $is_user_member = true;
    $employer_id = jobsearch_user_isemp_member($user_id);
    $user_id = jobsearch_get_employer_user_id($employer_id);
} else {
    $employer_id = jobsearch_get_user_employer_id($user_id);
}

$reults_per_page = isset($jobsearch_plugin_options['user-dashboard-per-page']) && $jobsearch_plugin_options['user-dashboard-per-page'] > 0 ? $jobsearch_plugin_options['user-dashboard-per-page'] : 10;

$page_num = isset($_GET['page_num']) ? $_GET['page_num'] : 1;
if ($employer_id > 0) {
    $args = array(
        'post_type' => 'package',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'fields' => 'ids',
        'order' => 'ASC',
        'orderby' => 'title',
        'meta_query' => array(
            array(
                'key' => 'jobsearch_field_package_type',
                'value' => 'feature_job',
                'compare' => '=',
            ),
        ),
    );
    $fpkgs_query = new WP_Query($args);
    wp_reset_postdata();

    $slected_job_status = isset($_GET['job_status']) ? $_GET['job_status'] : '';

    //
    $job_query_status = 'publish';
    if ($slected_job_status == 'draft') {
        $job_query_status = 'draft';
    }
    if ($slected_job_status == 'pending') {
        $job_query_status = 'awaiting-payment';
    }
    if ($slected_job_status == 'archived') {
        $job_query_status = 'trash';
    }
    if ($slected_job_status == '') {
        $job_query_status = array('publish', 'draft', 'awaiting-payment', 'trash');
    }
    $args = array(
        'post_type' => 'job',
        'posts_per_page' => $reults_per_page,
        'paged' => $page_num,
        'post_status' => $job_query_status, // 'draft', 'awaiting-payment'
        'order' => 'DESC',
        'orderby' => 'date',
    );
    $meta_query = [];
    $meta_query[] = array(
        'key' => 'jobsearch_field_job_posted_by',
        'value' => $employer_id,
        'compare' => '=',
    );
    if ($slected_job_status == 'active') {
        $meta_query[] = array(
            'key' => 'jobsearch_field_job_expiry_date',
            'value' => $current_date,
            'compare' => '>=',
        );
        $meta_query[] = array(
            'key' => 'jobsearch_field_job_filled',
            'value' => 'on',
            'compare' => '!=',
        );
        $meta_query[] = array(
            'key' => 'jobsearch_field_job_status',
            'value' => 'approved',
            'compare' => '=',
        );
    }
    if ($slected_job_status == 'expired') {
        $meta_query[] = array(
            'key' => 'jobsearch_field_job_expiry_date',
            'value' => $current_date,
            'compare' => '<',
        );
    }
    if ($slected_job_status == 'filled') {
        $meta_query[] = array(
            'key' => 'jobsearch_field_job_filled',
            'value' => 'on',
            'compare' => '=',
        );
    }

    $args['meta_query'] = $meta_query;

    if (jobsearch_user_isemp_member($real_user_id)) {
        //
        $att_user_pperms = get_user_meta($real_user_id, 'jobsearch_attchprof_perms', true);

        if (!empty($att_user_pperms) && in_array('u_manage_own_jobs', $att_user_pperms)) {
            $args['author__in'] = array($real_user_id);
        }
    }

    if (isset($_GET['keyword']) && $_GET['keyword'] != '') {
        $args['s'] = sanitize_text_field($_GET['keyword']);
    }

    $args = apply_filters('jobsearch_empdash_mnage_jobs_list_qargs', $args);

    $jobs_query = new WP_Query($args);

    $total_jobs = $jobs_query->found_posts;

    $manage_jobscon_class = '';
    if (isset($_GET['view']) && $_GET['view'] == 'applicants' && isset($_GET['job_id']) && $_GET['job_id'] > 0) {
        $manage_jobscon_class = ' jobsearch-injobapplics-con';
    }
    ?>
    <div class="jobsearch-employer-dasboard<?php echo ($manage_jobscon_class) ?>">
        <?php do_action('jobsearch_empdash_mangejobs_in_maincon') ?>
        <div class="jobsearch-employer-box-section">
            <?php
            if (isset($_GET['view']) && $_GET['view'] == 'applicants' && isset($_GET['job_id']) && $_GET['job_id'] > 0) {

                echo apply_filters('jobsearch_empdash_managejob_applicants', '');
            } else {
                $posible_job_statuses = ['draft', 'pending', 'active', 'expired', 'filled', 'archived'];
                if (!in_array($slected_job_status, $posible_job_statuses)) {
                    $slected_job_status = '';
                }
                ?>
                <div class="jobsearch-profile-title">
                    <h2><?php echo apply_filters('jobsearch_emp_dash_manage_jobs_maintitle', esc_html__('Manage Jobs', 'wp-jobsearch')) ?></h2>
                    <form autocomplete="off" method="get" class="jobsearch-employer-search" action="<?php echo ($page_url) ?>">
                        <div class="mangejob-filter-fieldscon">
                            <input type="hidden" name="tab" value="manage-jobs">
                            <div class="jobsearch-profile-select jobsearch-jobstatus-select">
                                <select name="job_status" class="selectize-select" placeholder="<?php _e('Job Status', 'wp-jobsearch') ?>">
                                    <option value=""><?php esc_html_e('Job Status', 'wp-jobsearch') ?></option>
                                    <option <?php echo ($slected_job_status == 'draft' ? 'selected="selected"' : '') ?> value="draft"><?php esc_html_e('Draft', 'wp-jobsearch') ?></option>
                                    <option <?php echo ($slected_job_status == 'pending' ? 'selected="selected"' : '') ?> value="pending"><?php esc_html_e('Pending', 'wp-jobsearch') ?></option>
                                    <option <?php echo ($slected_job_status == 'active' ? 'selected="selected"' : '') ?> value="active"><?php esc_html_e('Active', 'wp-jobsearch') ?></option>
                                    <option <?php echo ($slected_job_status == 'expired' ? 'selected="selected"' : '') ?> value="expired"><?php esc_html_e('Expired', 'wp-jobsearch') ?></option>
                                    <option <?php echo ($slected_job_status == 'filled' ? 'selected="selected"' : '') ?> value="filled"><?php esc_html_e('Filled', 'wp-jobsearch') ?></option>
                                    <option <?php echo ($slected_job_status == 'archived' ? 'selected="selected"' : '') ?> value="archived"><?php esc_html_e('Archived', 'wp-jobsearch') ?></option>
                                </select>
                            </div>
                            <div class="mangejob-serch-field">
                                <input placeholder="<?php esc_html_e('Search job', 'wp-jobsearch') ?>" name="keyword" type="text" value="<?php echo (isset($_GET['keyword']) ? $_GET['keyword'] : '') ?>">
                                <input type="submit" value="">
                                <i class="jobsearch-icon jobsearch-search"></i>
                            </div>
                        </div>
                    </form>
                </div>
                <?php
                add_action('wp_footer', function() {
                    ?>
                    <script>
                        jQuery('.jobsearch-jobstatus-select').on('change', 'select', function () {
                            jQuery(this).parents('form').trigger('submit');
                        });
                    </script>
                    <?php
                }, 54);
                echo apply_filters('jobsearch_empdashboard_mangejobs_after_mtitle', '', $employer_id);

                $all_featorder_ids = array();
                $feat_jobs_qargs = array(
                    'post_type' => 'shop_order',
                    'posts_per_page' => '-1',
                    'post_status' => 'wc-completed',
                    'order' => 'DESC',
                    'orderby' => 'ID',
                    'fields' => 'ids',
                    'meta_query' => array(
                        array(
                            'key' => 'package_type',
                            'value' => array('featured_jobs', 'emp_allin_one', 'employer_profile'),
                            'compare' => 'IN',
                        ),
                        array(
                            'key' => 'package_expiry_timestamp',
                            'value' => strtotime(current_time('d-m-Y H:i:s')),
                            'compare' => '>',
                        ),
                        array(
                            'key' => 'jobsearch_order_user',
                            'value' => $user_id,
                            'compare' => '=',
                        ),
                    ),
                );
                $pkgs_query = new WP_Query($feat_jobs_qargs);

                $pkgs_query_posts = $pkgs_query->posts;
                if (!empty($pkgs_query_posts)) {
                    foreach ($pkgs_query_posts as $order_post_id) {
                        $order_pkg_type = get_post_meta($order_post_id, 'package_type', true);
                        if ($order_pkg_type == 'featured_jobs') {
                            $remaining_jobs = jobsearch_pckg_order_remain_featjob_credits($order_post_id);
                        } else if ($order_pkg_type == 'emp_allin_one') {
                            $remaining_jobs = jobsearch_allinpckg_order_remaining_fjobs($order_post_id);
                        } else if ($order_pkg_type == 'employer_profile') {
                            $remaining_jobs = jobsearch_emprofpckg_order_remaining_fjobs($order_post_id);
                        }
                        if ($remaining_jobs > 0) {
                            $all_featorder_ids[] = $order_post_id;
                        }
                    }
                }

                //
                $job_deadline_allow = isset($jobsearch_plugin_options['job_appliction_deadline']) ? $jobsearch_plugin_options['job_appliction_deadline'] : '';

                $duplicate_jobs_allow = isset($jobsearch_plugin_options['duplicate_the_job']) ? $jobsearch_plugin_options['duplicate_the_job'] : '';
                $edit_the_joballow = isset($jobsearch_plugin_options['dash_edit_the_job']) ? $jobsearch_plugin_options['dash_edit_the_job'] : '';
                $free_jobs_allow = isset($jobsearch_plugin_options['free-jobs-allow']) ? $jobsearch_plugin_options['free-jobs-allow'] : '';

                $emp_email_apps_tab = isset($jobsearch_plugin_options['emp_dash_email_applics']) ? $jobsearch_plugin_options['emp_dash_email_applics'] : '';
                if ($jobs_query->have_posts()) {

                    $in_archive_jobs = false;
                    if (isset($_GET['job_status']) && $_GET['job_status'] == 'archived') {
                        $in_archive_jobs = true;
                    }

                    do_action('jobsearch_empdash_mangjobs_before_listins');
                    ?>
                    <script>
                        jQuery(function () {
                            jQuery('.jobsearch-elemnt-withtool').tooltip();
                            jQuery('.jobsearch-fill-the-job').tooltip();
                            jQuery('.jobsearch-duplict-cusjob').tooltip();
                            jQuery('.jobsearch-mangjob-act').tooltip();
                            if (jQuery('.jobsearch-featureto-job').length > 0) {
                                jQuery('.jobsearch-featureto-job').tooltip();
                            }
                        });
                    </script>
                    <div class="jobsearch-jobs-list-holder">
                        <div class="jobsearch-managejobs-list">
                            <!-- Manage Jobs Header -->
                            <div class="jobsearch-mngjob-thead">
                                <div class="jobsearch-mngjob-cell"><?php esc_html_e('Job Title', 'wp-jobsearch') ?></div>
                                <div class="jobsearch-mngjob-cell stuts-tabh-cell"><?php esc_html_e('Status', 'wp-jobsearch') ?></div>
                                <div class="jobsearch-mngjob-cell jobapps-tabh-cell"><?php esc_html_e('Featured', 'wp-jobsearch') ?></div>
                                <div class="jobsearch-mngjob-cell stuts-tabh-cell"><?php esc_html_e('Applicants', 'wp-jobsearch') ?></div>
                                <div class="jobsearch-mngjob-cell stuts-tabh-cell"><?php esc_html_e('Fill Job', 'wp-jobsearch') ?></div>
                                <div class="jobsearch-mngjob-cell"></div>
                            </div>
                            <?php
                            while ($jobs_query->have_posts()) : $jobs_query->the_post();
                                global $post;

                                $job_id = get_the_ID();

                                $job_post = get_post($job_id);

                                $job_post_status = $post->post_status;

                                $sectors = wp_get_post_terms($job_id, 'sector');
                                $job_sector = jobsearch_job_get_all_sectors($job_id, '', '', '', '<li><i class="jobsearch-icon jobsearch-filter-tool-black-shape"></i>', '</li>');

                                $jobtypes = wp_get_post_terms($job_id, 'jobtype');
                                $job_type = isset($jobtypes[0]->term_id) ? $jobtypes[0]->term_id : '';

                                $get_job_location = get_post_meta($job_id, 'jobsearch_field_location_address', true);

                                $job_publish_date = get_post_meta($job_id, 'jobsearch_field_job_publish_date', true);
                                $job_expiry_date = get_post_meta($job_id, 'jobsearch_field_job_expiry_date', true);

                                $job_deadline_date = get_post_meta($job_id, 'jobsearch_field_job_application_deadline_date', true);

                                $job_filled = get_post_meta($job_id, 'jobsearch_field_job_filled', true);

                                $job_status = 'pending';
                                $job_status = get_post_meta($job_id, 'jobsearch_field_job_status', true);

                                if ($job_expiry_date != '' && $job_expiry_date <= strtotime(current_time('d-m-Y H:i:s', 1))) {
                                    $job_status = 'expired';
                                }

                                $status_txt = '';
                                if ($job_status == 'pending') {
                                    $status_txt = esc_html__('Pending', 'wp-jobsearch');
                                } else if ($job_status == 'expired') {
                                    $status_txt = esc_html__('Expired', 'wp-jobsearch');
                                } else if ($job_status == 'canceled') {
                                    $status_txt = esc_html__('Canceled', 'wp-jobsearch');
                                } else if ($job_status == 'approved') {
                                    $status_txt = esc_html__('Approved', 'wp-jobsearch');
                                } else if ($job_status == 'admin-review') {
                                    $status_txt = esc_html__('Admin Review', 'wp-jobsearch');
                                }

                                if ($job_post_status == 'trash') {
                                    $status_txt = esc_html__('Trashed', 'wp-jobsearch');
                                }
                                if ($job_post_status == 'draft') {
                                    $status_txt = esc_html__('Draft', 'wp-jobsearch');
                                }
                                if ($job_post_status == 'awaiting-payment') {
                                    $status_txt = esc_html__('Awaiting Payment', 'wp-jobsearch');
                                }

                                $status_txt = apply_filters('jobsearch_job_mang_dash_job_status_str', $status_txt, $job_id);

                                $job_is_feature = get_post_meta($job_id, 'jobsearch_field_job_featured', true);

                                $job_applicants_list = get_post_meta($job_id, 'jobsearch_job_applicants_list', true);
                                $job_applicants_list = jobsearch_is_post_ids_array($job_applicants_list, 'candidate');
                                if (empty($job_applicants_list)) {
                                    $job_applicants_list = array();
                                }

                                $job_applicants_count = !empty($job_applicants_list) ? count($job_applicants_list) : 0;
                                $job_applicants_count = apply_filters('jobsearch_mnge_job_applicants_list_count', $job_applicants_count, $job_id);

                                $job_views_count = get_post_meta($job_id, 'jobsearch_job_views_count', true);
                                $job_aply_type = get_post_meta($job_id, 'jobsearch_field_job_apply_type', true);

                                $apllicans_link = add_query_arg(array('tab' => 'manage-jobs', 'view' => 'applicants', 'job_id' => $job_id), $page_url);

                                if ($job_aply_type == 'with_email') {
                                    if ($emp_email_apps_tab == 'on') {
                                        $apllicans_link = add_query_arg(array('tab' => 'all-applicants', 'view' => 'email-applicants', 'job_id' => $job_id), $page_url);
                                    } else {
                                        $apllicans_link = 'javascript:void(0);';
                                    }
                                    $job_applicants_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->posts AS posts"
                                                    . " LEFT JOIN $wpdb->postmeta AS postmeta ON(posts.ID = postmeta.post_id) "
                                                    . " WHERE post_type=%s AND (postmeta.meta_key = 'jobsearch_app_job_id' AND postmeta.meta_value={$job_id})", 'email_apps'));
                                }
                                if ($job_aply_type == 'external') {
                                    $emp_external_apps_tab = isset($jobsearch_plugin_options['emp_dash_external_applics']) ? $jobsearch_plugin_options['emp_dash_external_applics'] : '';
                                    if ($emp_external_apps_tab == 'on') {
                                        $apllicans_link = add_query_arg(array('tab' => 'all-applicants', 'view' => 'external-applicants', 'job_id' => $job_id), $page_url);
                                    } else {
                                        $apllicans_link = 'javascript:void(0);';
                                    }
                                    $job_extapplcs_list = get_post_meta($job_id, 'jobsearch_external_job_apply_data', true);
                                    $job_applicants_count = !empty($job_extapplcs_list) ? count($job_extapplcs_list) : 0;
                                }
                                ?>
                                <div class="jobsearch-mangjobs-list-inner">
                                    <div class="jobsearch-managejobs-body">


                                        <div class="jobsearch-mng-cell jobsearch-mng-title-cnt">
                                            <h6 class="jobsearch-pst-title"><a href="<?php echo get_permalink($job_id) ?>"><?php echo get_the_title() ?></a> <span class="job-filled"><?php echo ($job_filled == 'on' ? esc_html__('(Filled)', 'wp-jobsearch') : '') ?></span></h6>
                                            <?php do_action('jobsearch_emp_dash_manage_job_after_title', $job_id) ?>
                                            <?php
                                            ob_start();
                                            ?>
                                            <ul>
                                                <?php
                                                if ($job_publish_date != '') {
                                                    ?>
                                                    <li><i class="jobsearch-icon jobsearch-calendar"></i> <?php printf(wp_kses(__('Created: <span>%s</span>', 'wp-jobsearch'), array('span' => array())), date_i18n(get_option('date_format'), $job_publish_date)) ?></li>
                                                    <?php
                                                }
                                                if ($job_deadline_date != '' && $job_deadline_allow != 'off') {
                                                    ob_start();
                                                    if ($job_deadline_date <= $current_date && $job_expiry_date > $current_date) {
                                                        ?>
                                                        <li><i class="jobsearch-icon jobsearch-calendar"></i> <?php printf(wp_kses(__('Deadline: <span style="color:#ff0000;">%s</span>', 'wp-jobsearch'), array('span' => array('style' => array()))), date_i18n(get_option('date_format'), $job_deadline_date)) ?> <a href="javascript:void(0);" title="<?php esc_html_e('Make it Expire', 'wp-jobsearch') ?>" data-id="<?php echo ($job_id) ?>" class="jobsearch-makedeadjob-expire jobsearch-elemnt-withtool"><strong>(<?php esc_html_e('Expire Job', 'wp-jobsearch') ?>)</strong></a></li>
                                                        <?php
                                                    } else {
                                                        ?>
                                                        <li><i class="jobsearch-icon jobsearch-calendar"></i> <?php printf(wp_kses(__('Deadline: <span>%s</span>', 'wp-jobsearch'), array('span' => array())), date_i18n(get_option('date_format'), $job_deadline_date)) ?></li>
                                                        <?php
                                                    }
                                                    $list_dedline_html = ob_get_clean();
                                                    echo apply_filters('jobsearch_empdash_mngjob_deadline_html', $list_dedline_html, $job_id);
                                                }
                                                if ($get_job_location != '' && $all_location_allow == 'on') {
                                                    ?>
                                                    <li><i class="jobsearch-icon jobsearch-maps-and-flags"></i> <?php echo ($get_job_location) ?></li>
                                                    <?php
                                                }
                                                if ($job_sector != '') {
                                                    echo ($job_sector);
                                                }
                                                

                                                echo apply_filters('jobsearch_indash_mangjobs_lis_aftr_fill', '', $job_id);
                                                ?>
                                            </ul>
                                            <?php
                                            $itm_det_html = ob_get_clean();
                                            echo apply_filters('jobsearch_empdash_mnagjob_item_detail', $itm_det_html, $job_id);
                                            ?>
                                        </div>
                                        <div class="jobsearch-mng-cell">
                                            <span class="jobsearch-managejobs-option <?php echo ($job_status == 'approved' && ($job_post_status != 'draft' && $job_post_status != 'trash') ? 'active' : '') ?><?php echo ($job_status == 'expired' || $job_status == 'canceled' || $job_post_status == 'trash' ? 'expired' : '') ?>"><?php echo ($status_txt) ?></span>
                                        </div>
                                        <div class="jobsearch-mng-cell">
                                            <?php
                                            $allow_make_feat = true;
                                            $allow_freely_feature = false;
                                            $featanchr_classes = '';
                                            if ($job_is_feature == 'on') {
                                                $job_feature_link = 'href="javascript:void(0);"';
                                                $job_feature_until = get_post_meta($job_id, 'jobsearch_field_job_feature_till', true);
                                                if ($job_feature_until != '') {
                                                    $job_feature_until = date_i18n(get_option('date_format'), strtotime($job_feature_until));
                                                    $feat_job_tooltitle = sprintf(esc_html__('Featured Till: %s', 'wp-jobsearch'), $job_feature_until);
                                                } else {
                                                    $feat_job_tooltitle = esc_html__('Featured', 'wp-jobsearch');
                                                }
                                            } else {
                                                if ($free_jobs_allow == 'on') {
                                                    $allow_make_feat = false;
                                                    $free_feature_jobs = isset($jobsearch_plugin_options['free_feature_jobs']) ? $jobsearch_plugin_options['free_feature_jobs'] : '';
                                                    if ($free_feature_jobs == 'on') {
                                                        $allow_freely_feature = true;
                                                    }
                                                    $job_feature_link = 'href="javascript:void(0);"';
                                                    $feat_job_tooltitle = esc_html__('Make Featured Job', 'wp-jobsearch');
                                                } else {
                                                    $feat_job_tooltitle = esc_html__('Make Featured Job', 'wp-jobsearch');
                                                    $job_feature_link = 'href="' . add_query_arg(array('tab' => 'user-job', 'job_id' => $job_id, 'action' => 'update', 'step' => 'package'), $page_url) . '"';
                                                    $fpkgs_posts = $fpkgs_query->posts;

                                                    if (!empty($fpkgs_posts) || !empty($all_featorder_ids)) {
                                                        $featanchr_classes .= ' jobsearch-jobfeture-btn-' . ($job_id);
                                                        $job_feature_link = 'href="javascript:void(0);"';
                                                        ?>
                                                        <script>
                                                            jQuery(document).on('click', '.jobsearch-jobfeture-btn-<?php echo ($job_id) ?>', function () {
                                                                jobsearch_modal_popup_open('JobSearchModalFeatureJob<?php echo ($job_id) ?>');
                                                            });
                                                        </script>
                                                        <?php
                                                        $popup_args = array('p_job_id' => $job_id, 'p_fpkgs_posts' => $fpkgs_posts, 'all_featorder_ids' => $all_featorder_ids);
                                                        add_action('wp_footer', function () use ($popup_args) {

                                                            extract(shortcode_atts(array(
                                                                'p_job_id' => '',
                                                                'p_fpkgs_posts' => '',
                                                                'all_featorder_ids' => '',
                                                                            ), $popup_args));
                                                            ?>
                                                            <div class="jobsearch-modal fade" id="JobSearchModalFeatureJob<?php echo ($p_job_id) ?>">
                                                                <div class="modal-inner-area">&nbsp;</div>
                                                                <div class="modal-content-area">
                                                                    <div class="modal-box-area">
                                                                        <div class="jobsearch-modal-title-box">
                                                                            <h2><?php esc_html_e('Select Package', 'wp-jobsearch') ?></h2>
                                                                            <span class="modal-close"><i class="fa fa-times"></i></span>
                                                                        </div>
                                                                        <div id="fpkgs-lista-<?php echo ($p_job_id) ?>" class="jobsearch-feat-job-form">
                                                                            <ul>
                                                                                <?php
                                                                                if (!empty($all_featorder_ids)) {
                                                                                    foreach ($all_featorder_ids as $all_in_existpkg) {
                                                                                        if ($all_in_existpkg > 0) {
                                                                                            $pkg_order_name = '';
                                                                                            $pkg_order_price = 0;

                                                                                            if (function_exists('wc_get_order')) {
                                                                                                $pkg_order_obj = wc_get_order($all_in_existpkg);
                                                                                                if ($pkg_order_name == '') {
                                                                                                    foreach ($pkg_order_obj->get_items() as $oitem_id => $oitem_product) {
                                                                                                        //Get the WC_Product object
                                                                                                        $oproduct = $oitem_product->get_product();

                                                                                                        if (is_object($oproduct)) {
                                                                                                            $pkg_order_name = get_the_title($oproduct->get_ID());
                                                                                                            $pkg_order_price = $pkg_order_obj->get_total();
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                            $pkg_order_price = jobsearch_get_price_format($pkg_order_price);
                                                                                            ?>
                                                                                            <li>
                                                                                                <label id="<?php echo ('fpkgfor-' . $all_in_existpkg . '-' . $p_job_id) ?>" for="<?php echo ('fpkg-' . $all_in_existpkg . '-' . $p_job_id) ?>">
                                                                                                    <?php echo ($pkg_order_name) ?> - <span><?php echo ($pkg_order_price); ?> (<?php esc_html_e('Purchased', 'wp-jobsearch') ?>)</span>
                                                                                                </label>
                                                                                                <div class="fpkg-detail">
                                                                                                    <?php
                                                                                                    $unlimited_numfjobs = get_post_meta($all_in_existpkg, 'unlimited_numfjobs', true);
                                                                                                    if ($unlimited_numfjobs == 'yes') {
                                                                                                        $total_jobs = esc_html__('Unlimited', 'wp-jobsearch');
                                                                                                    }

                                                                                                    $order_pkg_type = get_post_meta($all_in_existpkg, 'package_type', true);
                                                                                                    if ($order_pkg_type == 'featured_jobs') {
                                                                                                        $total_jobs = get_post_meta($all_in_existpkg, 'feat_job_credits', true);
                                                                                                        $used_jobs = jobsearch_pckg_order_used_featjob_credits($all_in_existpkg);
                                                                                                        $remaining_jobs = jobsearch_pckg_order_remain_featjob_credits($all_in_existpkg);
                                                                                                    } else {
                                                                                                        $total_jobs = get_post_meta($all_in_existpkg, 'allin_num_fjobs', true);
                                                                                                        $used_jobs = jobsearch_allinpckg_order_used_fjobs($all_in_existpkg);
                                                                                                        $remaining_jobs = jobsearch_allinpckg_order_remaining_fjobs($all_in_existpkg);
                                                                                                    }
                                                                                                    if ($unlimited_numfjobs == 'yes') {
                                                                                                        $used_jobs = '-';
                                                                                                        $remaining_jobs = '-';
                                                                                                    }
                                                                                                    ?>
                                                                                                    <div class="item-detail-pkg"><span><?php esc_html_e('Total Featured Credits', 'wp-jobsearch') ?>: </span><?php echo ($total_jobs) ?></div>
                                                                                                    <div class="item-detail-pkg"><span><?php esc_html_e('Used Featured Credits', 'wp-jobsearch') ?>: </span><?php echo ($used_jobs) ?></div>
                                                                                                    <div class="item-detail-pkg"><span><?php esc_html_e('Remaining Featured Credits', 'wp-jobsearch') ?>: </span><?php echo ($remaining_jobs) ?></div>
                                                                                                </div>
                                                                                                <input id="<?php echo ('fpkg-' . $all_in_existpkg . '-' . $p_job_id) ?>" type="checkbox" name="alpur_feature_pkg" value="<?php echo ($all_in_existpkg) ?>">
                                                                                            </li>
                                                                                            <?php
                                                                                        }
                                                                                    }
                                                                                }
                                                                                $fet_pkgcount = 1;
                                                                                foreach ($p_fpkgs_posts as $fpkg_post) {
                                                                                    $pkg_attach_product = get_post_meta($fpkg_post, 'jobsearch_package_product', true);

                                                                                    if ($pkg_attach_product != '' && get_page_by_path($pkg_attach_product, 'OBJECT', 'product')) {
                                                                                        $fpkg_price = get_post_meta($fpkg_post, 'jobsearch_field_package_price', true);
                                                                                        ?>
                                                                                        <li>
                                                                                            <label id="<?php echo ('fpkgfor-' . $fpkg_post . '-' . $p_job_id) ?>" for="<?php echo ('fpkg-' . $fpkg_post . '-' . $p_job_id) ?>">
                                                                                                <?php echo get_the_title($fpkg_post) ?> - <span><?php echo jobsearch_get_price_format($fpkg_price); ?></span>
                                                                                            </label>
                                                                                            <input id="<?php echo ('fpkg-' . $fpkg_post . '-' . $p_job_id) ?>" <?php echo ($fet_pkgcount == 1 ? '' : '') ?> type="checkbox" name="feature_pkg" value="<?php echo ($fpkg_post) ?>">
                                                                                        </li>
                                                                                        <?php
                                                                                        $fet_pkgcount++;
                                                                                    }
                                                                                }
                                                                                ?>
                                                                            </ul>
                                                                            <a href="javascript:void(0);" class="jobsearch-feature-pkg-sbtn jobsearch-feature-pkg-buybtn" style="display:none;" data-id="<?php echo ($p_job_id) ?>"><?php esc_html_e('Checkout', 'wp-jobsearch') ?></a>
                                                                            <a href="javascript:void(0);" class="jobsearch-feature-pkg-sbtn jobsearch-feature-pkg-alpurbtn" style="display:none;" data-id="<?php echo ($p_job_id) ?>"><?php esc_html_e('Make Job Featured', 'wp-jobsearch') ?></a>
                                                                            <span class="fpkgs-loader"></span>
                                                                            <div class="fpkgs-msg"></div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php
                                                        }, 11, 1);
                                                    } else {
                                                        $allow_make_feat = false;
                                                    }
                                                }
                                            }
                                            ob_start();
                                            if ($allow_freely_feature) {
                                                ?>
                                                <a href="#" class="jobsearch-featureto-job<?php echo ($job_is_feature == 'on' ? ' job-is-fetured' : ' jobdash-to-make-feature') ?>" data-id="<?php echo ($job_id) ?>" title="<?php echo ($feat_job_tooltitle) ?>"><i class="<?php echo ($job_is_feature == 'on' ? 'fa fa-star' : 'fa fa-star-o') ?>"></i></a>
                                                <?php
                                            } else if (!$allow_make_feat) {
                                                ?>
                                                <a>&nbsp;</a>
                                                <?php
                                            } else {
                                                ?>
                                                <a <?php echo ($job_feature_link) ?> class="jobsearch-featureto-job<?php echo ($job_is_feature == 'on' ? ' job-is-fetured' : '') ?><?php echo ($featanchr_classes) ?>" title="<?php echo ($feat_job_tooltitle) ?>"><i class="<?php echo ($job_is_feature == 'on' ? 'fa fa-star' : 'fa fa-star-o') ?>"></i></a>
                                                <?php
                                            }
                                            $feature_btn_html = ob_get_clean();
                                            echo apply_filters('jobsearch_dash_mangejobs_feature_linkbtn', $feature_btn_html, $job_id);
                                            ?>
                                        </div>
                                        <div class="jobsearch-mng-cell">
                                            <a <?php echo ('href="' . $apllicans_link . '"') ?> class="jobsearch-managejobs-appli">
                                                <?php
                                                if ($job_aply_type == 'external') {
                                                    if ($job_applicants_count > 1) {
                                                        printf(__('%s Clicks', 'wp-jobsearch'), $job_applicants_count);
                                                    } else {
                                                        printf(__('%s Click', 'wp-jobsearch'), $job_applicants_count);
                                                    }
                                                } else {
                                                    if ($job_applicants_count > 1) {
                                                        printf(__('%s Applicants', 'wp-jobsearch'), $job_applicants_count);
                                                    } else {
                                                        printf(__('%s Applicant', 'wp-jobsearch'), $job_applicants_count);
                                                    }
                                                }
                                                ?>
                                            </a>
                                        </div>
                                        <div class="jobsearch-mng-cell">
                                            <?php
                                            $job_allow_filled = isset($jobsearch_plugin_options['job_allow_filled']) ? $jobsearch_plugin_options['job_allow_filled'] : '';
                                            if ($job_allow_filled == 'on') {
                                                $job_it_status = get_post_meta($job_id, 'jobsearch_field_job_status', true);
                                                if ($job_it_status == 'approved') {
                                                    ?>
                                                    <div class="jobsearch-filledjobs-links">
                                                        <?php
                                                        $fill_pop_title = esc_html__('Fill this Job', 'wp-jobsearch');
                                                        $is_fill_check = '';
                                                        if ($job_filled == 'on') {
                                                            $fill_pop_title = esc_html__('Filled Job', 'wp-jobsearch');
                                                            $is_fill_check = '<i class="fa fa-check"></i>';
                                                        }
                                                        ?>
                                                        <a href="javascript:void(0);" title="<?php echo ($fill_pop_title) ?>" data-id="<?php echo ($job_id) ?>" class="jobsearch-fill-the-job"><span></span><span class="fill-job-loader"></span><?php echo ($is_fill_check) ?></a>
                                                    </div>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </div>


                                        <?php
                                        ob_start();
                                        ?>
                                        <div class="jobsearch-mng-cell">
                                            <div class="jobsearch-mng-links-wrap">
                                                <div class="jobsearch-managejobs-links">
                                                    <a href="<?php echo get_permalink($job_id) ?>" class="jobsearch-icon jobsearch-view jobsearch-mangjob-act" title="<?php esc_html_e('View Job', 'wp-jobsearch') ?>"></a>
                                                    <?php
                                                    if ($duplicate_jobs_allow == 'on') {
                                                        ?>
                                                        <a href="javascript:void(0);" class="jobsearch-icon jobsearch-paper jobsearch-duplict-cusjob" title="<?php esc_html_e('Duplicate this Job', 'wp-jobsearch') ?>" data-id="<?php echo ($job_id) ?>"></a>
                                                        <br>
                                                        <?php
                                                    }
                                                    if ($edit_the_joballow != 'off') {
                                                        ?>
                                                        <a href="<?php echo add_query_arg(array('tab' => 'user-job', 'job_id' => $job_id, 'action' => 'update'), $page_url) ?>" title="<?php esc_html_e('Edit Job', 'wp-jobsearch') ?>" class="jobsearch-icon jobsearch-edit jobsearch-mangjob-act"></a>
                                                        <?php
                                                    }
                                                    if (!$in_archive_jobs) {
                                                        ?>
                                                        <a href="javascript:void(0);" data-id="<?php echo ($job_id) ?>" class="jobsearch-icon jobsearch-rubbish jobsearch-trash-job jobsearch-mangjob-act" title="<?php esc_html_e('Delete Job', 'wp-jobsearch') ?>"></a>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        $actions_html = ob_get_clean();
                                        echo apply_filters('jobsearch_empdash_managejobs_list_actions', $actions_html, $job_id, $page_url);
                                        ?>

                                    </div>
                                    <div class="jobsearch-recent-applicants-nav">
                                        <ul>
                                            <?php
                                            ob_start();
                                            if ($job_aply_type == 'with_email') {
                                                ?>
                                                <li><a <?php echo ('href="' . $apllicans_link . '"') ?>><small><?php esc_html_e('Total applicants', 'wp-jobsearch') ?></small><span><?php echo absint($job_applicants_count) ?></span> </a></li>
                                                <?php
                                            } else if ($job_aply_type == 'internal') {
                                                ?>
                                                <li><a <?php echo ('href="' . $apllicans_link . '"') ?>><small><?php esc_html_e('Total applicants', 'wp-jobsearch') ?></small><span><?php echo absint($job_applicants_count) ?></span> </a></li>
                                                <?php
                                            } else if ($job_aply_type == 'external') {
                                                ?>
                                                <li><a <?php echo ('href="' . $apllicans_link . '"') ?>><small><?php esc_html_e('Total clicks', 'wp-jobsearch') ?></small><span><?php echo absint($job_applicants_count) ?></span> </a></li>
                                                <?php
                                            }
                                            $list_tapps_html = ob_get_clean();
                                            echo apply_filters('jobsearch_empdash_stats_jobslist_tapps', $list_tapps_html, $job_applicants_count, $job_id);

                                            $job_salary = jobsearch_job_offered_salary($job_id);
                                            ob_start();
                                            if ($job_salary != '') {
                                                ?>
                                                <li><small><?php esc_html_e('Salary', 'wp-jobsearch') ?></small> <span><?php echo ($job_salary) ?></span></li>
                                                <?php
                                            }
                                            $list_jslary_html = ob_get_clean();
                                            echo apply_filters('jobsearch_empdash_stats_jobslist_jslary', $list_jslary_html, $job_salary, $job_id);

                                            $job_views_count_switch = isset($jobsearch_plugin_options['job_detail_views_count']) ? $jobsearch_plugin_options['job_detail_views_count'] : '';
                                            ob_start();
                                            if ($job_views_count_switch == 'on') {
                                                ?>
                                                <li><small><?php esc_html_e('Total visits', 'wp-jobsearch') ?></small><span><?php echo absint($job_views_count) ?></span></li>
                                                <?php
                                            }
                                            $list_tvists_html = ob_get_clean();
                                            echo apply_filters('jobsearch_empdash_stats_jobslist_tvists', $list_tvists_html, $job_views_count, $job_id);
                                            ob_start();
                                            ?>
                                            <li><?php echo apply_filters('jobsearch_emp_dash_stats_jobsitem_expirydate', sprintf(wp_kses(__('<small>Expiry Date</small> <span>%s</span>', 'wp-jobsearch'), array('span' => array(), 'small' => array())), date_i18n(get_option('date_format'), $job_expiry_date)), $job_expiry_date) ?></li>
                                            <?php
                                            $list_expiry_html = ob_get_clean();
                                            echo apply_filters('jobsearch_empdash_mngjob_expiry_html', $list_expiry_html, $job_id);
                                            $job_posttin_instamatch_cand = isset($jobsearch_plugin_options['job_posttin_instamatch_cand']) ? $jobsearch_plugin_options['job_posttin_instamatch_cand'] : '';
                                            if ($job_posttin_instamatch_cand == 'on') {
                                                $job_instamatch_list = get_post_meta($job_id, 'jobsearch_instamatch_cands', true);
                                                $job_instamatch_list = jobsearch_is_post_ids_array($job_instamatch_list, 'candidate');
                                                $job_insta_match_list_c = !empty($job_instamatch_list) ? count($job_instamatch_list) : 0;
                                                ?>
                                                <li class="job-instamatch-total"><a href="<?php echo add_query_arg(array('tab' => 'manage-jobs', 'view' => 'applicants', 'job_id' => $job_id, 'mod' => 'insta_match'), $page_url) ?>"><?php printf(wp_kses(__('<small>Insta Match</small> <span>%s</span>', 'wp-jobsearch'), array('span' => array(), 'small' => array())), $job_insta_match_list_c) ?></a></li>
                                                <?php
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                                <?php
                            endwhile;
                            wp_reset_postdata();
                            ?>
                        </div>
                    </div>
                    <?php
                    $total_pages = 1;
                    if ($total_jobs > 0 && $reults_per_page > 0 && $total_jobs > $reults_per_page) {
                        $total_pages = ceil($total_jobs / $reults_per_page);
                        ?>
                        <div class="jobsearch-pagination-blog">
                        <?php $Jobsearch_User_Dashboard_Settings->pagination($total_pages, $page_num, $page_url) ?>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <p><?php esc_html_e('No job found.', 'wp-jobsearch') ?></p>
                    <?php
                }
            }
            ?>

        </div>
    </div>
    <?php
}