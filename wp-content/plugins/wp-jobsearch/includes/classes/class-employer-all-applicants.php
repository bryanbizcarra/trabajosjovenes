<?php
if (!defined('ABSPATH')) {
    die;
}

use WP_Jobsearch\Candidate_Profile_Restriction;

global $empall_applicants_handle;
if (!class_exists('jobsearch_empall_applicants_handle')) {

    class jobsearch_empall_applicants_handle
    {

        // hook things up
        public function __construct()
        {
            global $empall_applicants_handle;
            add_action('wp_ajax_jobsearch_empdash_load_single_apswith_job_inlist', array($this, 'load_single_apswith_job_inlist'));
            //
            add_action('wp_ajax_jobsearch_empdash_load_all_apswith_job_posts', array($this, 'load_all_jobs_post_data'));
            add_action('wp_ajax_jobsearch_empdash_load_more_apswith_job_apps', array($this, 'load_more_apswith_job_apps'));
            add_action('wp_ajax_jobsearch_empdash_load_more_apswith_apps_lis', array($this, 'load_more_apswith_apps_lis'));
            //
            add_action('wp_ajax_jobsearch_empdash_alljobs_apps_count_loadboxes', array($this, 'alljobs_apps_count_loadboxes'));
            
            add_action('init', array($this, 'alljobs_apps_export_report'));
        }

        public function all_applicants_list()
        {
            global $jobsearch_plugin_options, $sitepress;

            $page_id = $user_dashboard_page = isset($jobsearch_plugin_options['user-dashboard-template-page']) ? $jobsearch_plugin_options['user-dashboard-template-page'] : '';
            $page_id = $user_dashboard_page = jobsearch__get_post_id($user_dashboard_page, 'page');
            $page_url = jobsearch_wpml_lang_page_permalink($page_id, 'page');

            $email_applicants = isset($jobsearch_plugin_options['emp_dash_email_applics']) ? $jobsearch_plugin_options['emp_dash_email_applics'] : '';
            $external_applicants = isset($jobsearch_plugin_options['emp_dash_external_applics']) ? $jobsearch_plugin_options['emp_dash_external_applics'] : '';

            if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                $sitepress_def_lang = $sitepress->get_default_language();
                $sitepress_curr_lang = $sitepress->get_current_language();
                $sitepress->switch_lang($sitepress_def_lang, true);
            }

            $user_id = get_current_user_id();
            $user_id = apply_filters('jobsearch_in_fromdash_alljobaplics_user_id', $user_id);
            $employer_id = jobsearch_get_user_employer_id($user_id);

            if ($employer_id > 0) {
                $aplics_num_jobs = apply_filters('jobsearch_empdash_all_applics_num_perpage', 5);
                $args = array(
                    'post_type' => 'job',
                    'posts_per_page' => $aplics_num_jobs,
                    'post_status' => array('publish', 'draft'),
                    'fields' => 'ids',
                    'order' => 'DESC',
                    'orderby' => 'ID',
                    'meta_query' => array(
                        array(
                            'key' => 'jobsearch_field_job_posted_by',
                            'value' => $employer_id,
                            'compare' => '=',
                        ),
                        array(
                            'relation' => 'OR',
                            array(
                                'key' => 'jobsearch_job_applicants_list',
                                'value' => '',
                                'compare' => '!=',
                            ),
                            array(
                                'key' => '_job_reject_interview_list',
                                'value' => '',
                                'compare' => '!=',
                            ),
                        ),
                    ),
                );
                
                $filter_from_date = isset($_GET['alljobs_wafrom_date']) ? $_GET['alljobs_wafrom_date'] : '';
                $filter_to_date = isset($_GET['alljobs_wato_date']) ? $_GET['alljobs_wato_date'] : '';
                
                if ($filter_from_date != '') {
                    $filter_from_date_str = strtotime($filter_from_date);
                    $args['meta_query'][] = array(
                        'key' => 'jobsearch_field_job_publish_date',
                        'value' => $filter_from_date_str,
                        'compare' => '>=',
                    );
                }
                if ($filter_to_date != '') {
                    $filter_to_date_str = strtotime($filter_to_date);
                    $args['meta_query'][] = array(
                        'key' => 'jobsearch_field_job_publish_date',
                        'value' => $filter_to_date_str,
                        'compare' => '<=',
                    );
                }
                
                if (jobsearch_user_isemp_member($user_id)) {
                    //
                    $att_user_pperms = get_user_meta($user_id, 'jobsearch_attchprof_perms', true);

                    if (!empty($att_user_pperms) && in_array('u_manage_own_jobs', $att_user_pperms)) {
                        $args['author__in'] = array($user_id);
                    }
                }
                if (isset($_GET['job_id']) && $_GET['job_id'] > 0) {
                    $get_job_id = absint($_GET['job_id']);
                    $args['post__in'] = array($get_job_id);
                }
                $jobs_query = new WP_Query($args);
                $totl_found_jobs = $jobs_query->found_posts;
                $jobs_posts = $jobs_query->posts;
                if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                    $sitepress->switch_lang($sitepress_curr_lang, true);
                }
                
                $com_date_format = jobsearch_common_dateformat_picker();
                ?>

                <div class="jobsearch-typo-wrap">
                    <script>
                        jQuery(document).ready(function () {
                            jobsearch_alljobs_apps_count_load();
                            
                            jQuery('.aplics-mnemp-filterbtn').on('click', function() {
                                var this_prnet = jQuery(this).parents('.allapps-monthemp-filtrmain');
                                this_prnet.find('.filtr-loder').removeAttr('style').html('<i class="fa fa-refresh fa-spin"></i>');
                                this_prnet.find('form').submit();
                            });
                            
                            var todayDate = new Date().getDate();
                            jQuery('input[name="alljobs_wafrom_date"]').datetimepicker({
                                maxDate: new Date(new Date().setDate(todayDate)),
                                timepicker: false,
                                format: '<?php echo ($com_date_format) ?>',
                                onSelectDate: function (ct, $i) {

                                }
                            });
                            jQuery('input[name="alljobs_wato_date"]').datetimepicker({
                                maxDate: new Date(new Date().setDate(todayDate)),
                                timepicker: false,
                                format: '<?php echo ($com_date_format) ?>',
                                onSelectDate: function (ct, $i) {

                                }
                            });
                            //
                            jQuery('.allplics-export-excel').on('click', function() {
                                //jQuery(this).parents('.allapps-monthemp-filtrmain').find('.exprt-loder').html('<i class="fa fa-refresh fa-spin"></i>');
                                jQuery('#jobsearch-empmonth-reports-form').submit();
                            });
                        });
                    </script>
                    <div class="jobsearch-employer-box-section">
                        <div class="jobsearch-profile-title">
                            <h2><?php esc_html_e('All Applicants', 'wp-jobsearch') ?></h2>
                            <?php
                            if ($email_applicants == 'on') {
                                ?>
                                <a href="<?php echo add_query_arg(array('tab' => 'all-applicants', 'view' => 'email-applicants'), $page_url) ?>"
                                   class="applicnts-view-btn"><i
                                            class="fa fa-envelope"></i> <?php esc_html_e('Applied with Email', 'wp-jobsearch') ?>
                                </a>
                                <?php
                            }
                            if ($external_applicants == 'on') {
                                ?>
                                <a href="<?php echo add_query_arg(array('tab' => 'all-applicants', 'view' => 'external-applicants'), $page_url) ?>"
                                   class="applicnts-view-btn"><i
                                            class="fa fa-link"></i> <?php esc_html_e('External URL Applicants', 'wp-jobsearch') ?>
                                </a>
                                <?php
                            }
                            ?>
                        </div>
                        <div class="jobsearch-allaplicants-holder" data-uid="<?php echo($user_id) ?>" data-eid="<?php echo($employer_id) ?>">

                            <div class="select-appsjob-con">
                                <div class="filter-allreps-mainholdr">
                                    <div class="allapps-selctcounts-holdr">
                                        <div class="allapps-job-label"><h2><?php esc_html_e('Filter by Job', 'wp-jobsearch') ?></h2></div>
                                        <div class="allapps-jobselct-con"
                                             style="display: inline-block; position: relative;">
                                            <?php
                                            $job_selcted_id = 0;
                                            if (isset($_GET['job_id'])) {
                                                $job_selcted_id = $_GET['job_id'];
                                            }
                                            self::get_custom_post_field($job_selcted_id, 'job', esc_html__('Jobs', 'wp-jobsearch'), 'all_jobs_wapps_selctor');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="allapps-monthemp-filtrmain">
                                        <form method="get" autocomplete="off">
                                            <input type="hidden" name="tab" value="all-applicants">
                                            <div class="repapps-filtr-fieldscon">
                                                <div class="allapps-mnthefiltr-date date-from">
                                                    <label><?php esc_html_e('From Date', 'wp-jobsearch') ?></label>
                                                    <input type="text" name="alljobs_wafrom_date" value="<?php echo ($filter_from_date) ?>">
                                                </div>
                                                <div class="allapps-mnthefiltr-date date-to">
                                                    <label><?php esc_html_e('To Date', 'wp-jobsearch') ?></label>
                                                    <input type="text" name="alljobs_wato_date" value="<?php echo ($filter_to_date) ?>">
                                                </div>
                                            </div>
                                            <div class="filtr-mnth-btns-sec">
                                                <a href="javascript:void(0);" class="button aplics-mnemp-filterbtn"><?php esc_html_e('Submit', 'wp-jobsearch') ?></a>
                                                <span class="filtr-loder" style="display:none;"></span>
                                                <a href="javascript:void(0);" class="button allplics-export-excel"><?php esc_html_e('Export to Excel', 'wp-jobsearch') ?></a>
                                                <span class="exprt-loder" style="display:none;"></span>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="overall-appcreds-con">
                                    <ul>
                                        <li>
                                            <span class="tot-apps"><?php esc_html_e('Total Applicants ', 'wp-jobsearch') ?></span>
                                            <div class="applicnt-count-box tot-apps"><a class="overall-site-aplicnts">0</a></div>
                                        </li>
                                        <li>
                                            <span class="sh-apps"><?php esc_html_e('Shortlisted Applicants ', 'wp-jobsearch') ?></span>
                                            <div class="applicnt-count-box sh-apps"><a class="overall-site-shaplicnts">0</a></div>
                                        </li>
                                        <li>
                                            <span class="rej-apps"><?php esc_html_e('Rejected Applicants ', 'wp-jobsearch') ?></span>
                                            <div class="applicnt-count-box rej-apps"><a class="overall-site-rejaplicnts">0</a></div>
                                        </li>
                                        <li>
                                            <span class="unviewd-apps"><?php esc_html_e('Unviewed Applicants', 'wp-jobsearch') ?></span>
                                            <div class="applicnt-count-box unviewd-apps"><a class="overall-site-unviewdaplicnts">0</a></div>
                                        </li>
                                    </ul>
                                </div>
                                <form id="jobsearch-empmonth-reports-form" method="post" autocomplete="off">
                                    <input type="hidden" name="date_from" value="<?php echo ($filter_from_date) ?>">
                                    <input type="hidden" name="date_to" value="<?php echo ($filter_to_date) ?>">
                                    <?php wp_nonce_field('jobsearch_export_allapsrept') ?>
                                    <input type="hidden" name="jobsearch_empmnth_frntapps_reprts_export" value="1">
                                </form>
                            </div>
                            <?php
                            if (!empty($jobs_posts)) {
                                add_action('wp_footer', function () {
                                    ?>
                                    <div class="jobsearch-modal fade" id="JobSearchModalSendEmailComm">
                                        <div class="modal-inner-area">&nbsp;</div>
                                        <div class="modal-content-area">
                                            <div class="modal-box-area">
                                                <span class="modal-close"><i class="fa fa-times"></i></span>
                                                <div class="jobsearch-send-message-form">
                                                    <form method="post" id="jobsearch_send_email_form0" autocomplete="off">
                                                        <div class="jobsearch-user-form">
                                                            <ul class="email-fields-list">
                                                                <li>
                                                                    <label>
                                                                        <?php echo esc_html__('Subject', 'wp-jobsearch'); ?>
                                                                        :
                                                                    </label>
                                                                    <div class="input-field">
                                                                        <input type="text" name="send_message_subject"
                                                                               value=""/>
                                                                    </div>
                                                                </li>
                                                                <li>
                                                                    <label>
                                                                        <?php echo esc_html__('Message', 'wp-jobsearch'); ?>
                                                                        :
                                                                    </label>
                                                                    <div class="input-field">
                                                                        <textarea
                                                                                name="send_message_content"></textarea>
                                                                    </div>
                                                                </li>
                                                                <li>
                                                                    <div class="input-field-submit">
                                                                        <input type="submit"
                                                                               class="applicantto-email-submit-btn"
                                                                               data-jid="0" data-eid="0" data-cid="0"
                                                                               data-randid="0"
                                                                               name="send_message_content"
                                                                               value="<?php esc_html_e('Send', 'wp-jobsearch'); ?>"/>
                                                                        <span class="loader-box loader-box-0"></span>
                                                                    </div>
                                                                    <?php jobsearch_terms_and_con_link_txt(); ?>
                                                                </li>
                                                            </ul>
                                                            <div class="message-box message-box-0"
                                                                 style="display:none;"></div>
                                                        </div>
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }, 11, 1);
                                ?>
                                <div class="jobsearch-all-aplicantslst">
                                    <?php
                                    self::load_wapp_jobs_posts($jobs_posts, $employer_id);
                                    ?>
                                </div>

                                <?php
                                $show_pagination = apply_filters('jobsearch_empdash_all_applics_show_job_pagination', true);
                                if ($totl_found_jobs > 5 && $show_pagination) {
                                    $total_pages = ceil($totl_found_jobs / 5);
                                    ?>
                                    <div class="lodmore-apps-btnsec">
                                        <a href="javascript:void(0);" class="lodmore-apps-btn"
                                           data-tpages="<?php echo($total_pages) ?>"
                                           data-gtopage="2"><?php esc_html_e('Load More Jobs', 'wp-jobsearch') ?></a>
                                    </div>
                                    <?php
                                }
                            } else { ?>
                                <p><?php esc_html_e('No job found with applicants.', 'wp-jobsearch') ?></p>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <?php
            } else {
                if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                    $sitepress->switch_lang($sitepress_curr_lang, true);
                }
                ?>
                <div class="jobsearch-employer-dasboard jobsearch-typo-wrap">
                    <div class="jobsearch-employer-box-section">
                        <div class="jobsearch-profile-title">
                            <h2><?php esc_html_e('All Applicants', 'wp-jobsearch') ?></h2>
                        </div>
                        <p><?php esc_html_e('No Applicants found.', 'wp-jobsearch') ?></p>
                    </div>
                </div>
                <?php
            }
        }
        
        //
        public function alljobs_apps_export_report() {
            if (isset($_POST['jobsearch_empmnth_frntapps_reprts_export'])) {
                
                $from_date = isset($_POST['date_from']) ? $_POST['date_from'] : '';
                $to_date = isset($_POST['date_to']) ? $_POST['date_to'] : '';

                $user_id = get_current_user_id();
                $user_id = apply_filters('jobsearch_in_fromdash_alljobaplics_user_id', $user_id);
                $employer_id = jobsearch_get_user_employer_id($user_id);
                
                if ($from_date != '') {
                    $from_date = strtotime($from_date);
                }
                if ($to_date != '') {
                    $to_date = strtotime($to_date);
                }
                
                $args = array(
                    'post_type' => 'job',
                    'posts_per_page' => -1,
                    'post_status' => array('publish', 'draft'),
                    'fields' => 'ids',
                    'order' => 'DESC',
                    'orderby' => 'ID',
                    'meta_query' => array(
                        array(
                            'key' => 'jobsearch_field_job_posted_by',
                            'value' => $employer_id,
                            'compare' => '=',
                        ),
                        array(
                            'relation' => 'OR',
                            array(
                                'key' => 'jobsearch_job_applicants_list',
                                'value' => '',
                                'compare' => '!=',
                            ),
                            array(
                                'key' => '_job_reject_interview_list',
                                'value' => '',
                                'compare' => '!=',
                            ),
                        ),
                    ),
                );
                
                if ($from_date != '') {
                    $args['meta_query'][] = array(
                        'key' => 'jobsearch_field_job_publish_date',
                        'value' => $from_date,
                        'compare' => '>=',
                    );
                }
                if ($to_date != '') {
                    $args['meta_query'][] = array(
                        'key' => 'jobsearch_field_job_publish_date',
                        'value' => $to_date,
                        'compare' => '<=',
                    );
                }
                
                $jobs_query = new WP_Query($args);
                $totl_found_jobs = $jobs_query->found_posts;
                $job_posts = $jobs_query->posts;
                
                if (!empty($job_posts)) {
                    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
                    header("Content-Disposition: attachment; filename=applicants-report-" . time() . ".xlsx");
                    header('Cache-Control: max-age=0');

                    header('Cache-Control: max-age=1');

                    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
                    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
                    header('Pragma: public');
                    //
                    include_once("xlsxwriter.class.php");
                    
                    $header = array(
                        esc_html__('Job Title', 'wp-jobsearch') => 'string',//text
                        esc_html__('Employer', 'wp-jobsearch') => 'string', //text
                        esc_html__('Employer Email', 'wp-jobsearch') => 'string', //text
                        esc_html__('Applicant Name', 'wp-jobsearch') => 'string',
                        esc_html__('Email', 'wp-jobsearch') => 'string',
                        esc_html__('Phone', 'wp-jobsearch') => 'string',
                        esc_html__('Age', 'wp-jobsearch') => 'string',
                        esc_html__('Salary', 'wp-jobsearch') => 'string',
                        esc_html__('Apply Date', 'wp-jobsearch') => 'string',
                    );
                    $rows = array();
                    
                    foreach ($job_posts as $job_id) {
                        
                        $job_applicants_list = get_post_meta($job_id, 'jobsearch_job_applicants_list', true);
                        $job_applicants_list = jobsearch_is_post_ids_array($job_applicants_list, 'candidate');
                        
                        $employer_id = get_post_meta($job_id, 'jobsearch_field_job_posted_by', true);
                        $employer_name = get_the_title($employer_id);
                        
                        $emp_user_id = jobsearch_get_employer_user_id($employer_id);
                        $emp_user_obj = get_user_by('id', $emp_user_id);
                        $emp_email = isset($emp_user_obj->user_email) ? $emp_user_obj->user_email : '';
                        
                        if (!empty($job_applicants_list)) {
                            foreach ($job_applicants_list as $candidate_id) {
                                
                                $aplic_name = get_the_title($candidate_id);
                                $candidate_user_id = jobsearch_get_candidate_user_id($candidate_id);
                                $user_obj = get_user_by('id', $candidate_user_id);
                                $user_email = isset($user_obj->user_email) ? $user_obj->user_email : '';
                                
                                $user_apply_data = get_user_meta($candidate_user_id, 'jobsearch-user-jobs-applied-list', true);

                                $aply_date_time = '';
                                if (!empty($user_apply_data)) {
                                    $user_apply_key = array_search($job_id, array_column($user_apply_data, 'post_id'));
                                    $aply_date_time = isset($user_apply_data[$user_apply_key]['date_time']) ? $user_apply_data[$user_apply_key]['date_time'] : '';
                                }
                                if ($aply_date_time != '') {
                                    $aply_date_time = date(get_option('date_format'), $aply_date_time);
                                }
                                
                                $candidate_salary = jobsearch_candidate_current_salary($candidate_id);
                                $candidate_salary = $candidate_salary != '' ? $candidate_salary : '-';
                                $candidate_age = jobsearch_candidate_age($candidate_id);
                                $candidate_age = $candidate_age != '' ? $candidate_age : '-';
                                $candidate_phone = get_post_meta($candidate_id, 'jobsearch_field_user_phone', true);
                                $candidate_phone = $candidate_phone != '' ? $candidate_phone : '-';
                                
                                $rows[] = array(
                                    get_the_title($job_id),
                                    $employer_name,
                                    $emp_email,
                                    $aplic_name,
                                    $user_email,
                                    $candidate_phone,
                                    $candidate_age,
                                    $candidate_salary,
                                    $aply_date_time,
                                );
                            }
                        }
                    }
                    $writer = new XLSXWriter();
                    $writer->writeSheetHeader('Sheet1', $header);
                    foreach ($rows as $row) {
                        $writer->writeSheetRow('Sheet1', $row);
                    }
                    echo $writer->writeToString();
                    exit();
                }
            }
        }

        public static function get_custom_post_field($selected_id, $custom_post_slug, $field_label, $field_name, $custom_name = '')
        {
            global $jobsearch_form_fields;
            $custom_post_first_element = sprintf(esc_html_x('All %s', 'all applicants jobs', 'wp-jobsearch'), $field_label);
            
            $custom_posts = array(
                '' => $custom_post_first_element,
            );
            if ($selected_id) {
                $this_custom_posts = get_the_title($selected_id);
                $custom_posts[$selected_id] = $this_custom_posts;
            }

            $rand_num = rand(1234568, 6867867);
            $field_params = array(
                'classes' => 'job_post_cajax_field',
                'id' => 'custom_post_field_' . $rand_num,
                'name' => $field_name,
                'cus_name' => $field_name,
                'options' => $custom_posts,
                'force_std' => $selected_id,
                'ext_attr' => ' data-randid="' . $rand_num . '" data-forcestd="' . $selected_id . '" data-loaded="false" data-posttype="' . $custom_post_slug . '"',
            );
            if (isset($custom_name) && $custom_name != '') {
                $field_params['cus_name'] = $custom_name;
            }
            echo '<div class="jobsearch-profile-select">';
            $jobsearch_form_fields->select_field($field_params);
            echo '</div>';
            ?>
            <span class="jobsearch-field-loader custom_post_loader_<?php echo absint($rand_num); ?>"></span>
            <?php
        }

        public static function list_job_all_apps($_job_id, $employer_id, $apps_start = 0)
        {
            global $jobsearch_plugin_options;
            
            $user_id = get_current_user_id();
            
            $free_shortlist_allow = isset($jobsearch_plugin_options['free-shortlist-allow']) ? $jobsearch_plugin_options['free-shortlist-allow'] : '';
            
            $employer_resumes_list = get_post_meta($employer_id, 'jobsearch_candidates_list', true);
            $employer_resumes_list = explode(',', $employer_resumes_list);
            
            $cand_profile_restrict = new Candidate_Profile_Restriction;
            $job_applicants_list = get_post_meta($_job_id, 'jobsearch_job_applicants_list', true);
            $job_applicants_list = jobsearch_is_post_ids_array($job_applicants_list, 'candidate');
            arsort($job_applicants_list);

            if (empty($job_applicants_list)) {
                $job_applicants_list = array();
            }

            $viewed_candidates = get_post_meta($_job_id, 'jobsearch_viewed_candidates', true);
            if (empty($viewed_candidates)) {
                $viewed_candidates = array();
            }
            $viewed_candidates = jobsearch_is_post_ids_array($viewed_candidates, 'candidate');

            $job_short_int_list = get_post_meta($_job_id, '_job_short_interview_list', true);
            $job_short_int_list = $job_short_int_list != '' ? explode(',', $job_short_int_list) : '';
            if (empty($job_short_int_list)) {
                $job_short_int_list = array();
            }
            $job_short_int_list = jobsearch_is_post_ids_array($job_short_int_list, 'candidate');
            $job_short_int_list_c = !empty($job_short_int_list) ? count($job_short_int_list) : 0;

            $job_reject_int_list = get_post_meta($_job_id, '_job_reject_interview_list', true);
            $job_reject_int_list = $job_reject_int_list != '' ? explode(',', $job_reject_int_list) : '';
            if (empty($job_reject_int_list)) {
                $job_reject_int_list = array();
            }
            $job_reject_int_list = jobsearch_is_post_ids_array($job_reject_int_list, 'candidate');
            $job_reject_int_list_c = !empty($job_reject_int_list) ? count($job_reject_int_list) : 0;

            //
            $apps_offset = 6;
            if ($apps_start > 0) {
                $apps_start = ($apps_start - 1) * ($apps_offset);
            }
            $job_applicants_list = array_slice($job_applicants_list, $apps_start, $apps_offset);

            if (!empty($job_applicants_list)) {
                foreach ($job_applicants_list as $_candidate_id) {
                    $candidate_user_id = jobsearch_get_candidate_user_id($_candidate_id);
                    if (absint($candidate_user_id) <= 0) {
                        continue;
                    }
                    $candidate_user_obj = get_user_by('ID', $candidate_user_id);
                    $user_def_avatar_url = jobsearch_candidate_img_url_comn($_candidate_id);

                    $candidate_jobtitle = get_post_meta($_candidate_id, 'jobsearch_field_candidate_jobtitle', true);
                    $get_candidate_location = get_post_meta($_candidate_id, 'jobsearch_field_location_address', true);

                    $candidate_city_title = '';
                    $get_candidate_city = get_post_meta($_candidate_id, 'jobsearch_field_location_location3', true);
                    if ($get_candidate_city == '') {
                        $get_candidate_city = get_post_meta($_candidate_id, 'jobsearch_field_location_location2', true);
                    }
                    if ($get_candidate_city == '') {
                        $get_candidate_city = get_post_meta($_candidate_id, 'jobsearch_field_location_location1', true);
                    }

                    $candidate_city_tax = $get_candidate_city != '' ? get_term_by('slug', $get_candidate_city, 'job-location') : '';
                    if (is_object($candidate_city_tax)) {
                        $candidate_city_title = $candidate_city_tax->name;
                    }

                    $sectors = wp_get_post_terms($_candidate_id, 'sector');
                    $candidate_sector = isset($sectors[0]->name) ? $sectors[0]->name : '';

                    $candidate_salary = jobsearch_candidate_current_salary($_candidate_id);
                    $candidate_age = jobsearch_candidate_age($_candidate_id);

                    $candidate_phone = get_post_meta($_candidate_id, 'jobsearch_field_user_phone', true);

                    $job_cver_ltrs = get_post_meta($_job_id, 'jobsearch_job_apply_cvrs', true);
                    
                    $job_cver_attachs = get_post_meta($_job_id, 'job_apps_cover_attachs', true);

                    $send_message_form_rand = rand(100000, 999999);

                    $applicant_status = jobsearch_get_applicant_status_tarr($_candidate_id, $_job_id);
                    $candidate_status = get_post_meta($_candidate_id, 'jobsearch_field_candidate_approved', true);
                    
                    ob_start();
                    ?>
                    <li class="jobsearch-column-12<?php echo ((isset($applicant_status['status']) && $applicant_status['status'] == 'pending') || $candidate_status != 'on' ? ' applicant-status-pending' : '') ?>">
                        <script>
                            jQuery(document).on('click', '.jobsearch-modelemail-btn-<?php echo($send_message_form_rand) ?>', function () {
                                jobsearch_modal_popup_open('JobSearchModalSendEmailComm');
                                jQuery('#JobSearchModalSendEmailComm').find('form').attr('id', 'jobsearch_send_email_form<?php echo($send_message_form_rand) ?>');
                                jQuery('#JobSearchModalSendEmailComm').find('.loader-box').attr('class', 'loader-box loader-box-<?php echo($send_message_form_rand) ?>');
                                jQuery('#JobSearchModalSendEmailComm').find('.message-box').attr('class', 'message-box message-box-<?php echo($send_message_form_rand) ?>');
                                jQuery('#JobSearchModalSendEmailComm').find('.input-field-submit').find('input[type=submit]').attr('data-randid', '<?php echo($send_message_form_rand) ?>');
                                jQuery('#JobSearchModalSendEmailComm').find('.input-field-submit').find('input[type=submit]').attr('data-jid', '<?php echo($_job_id) ?>');
                                jQuery('#JobSearchModalSendEmailComm').find('.input-field-submit').find('input[type=submit]').attr('data-cid', '<?php echo($_candidate_id) ?>');
                                jQuery('#JobSearchModalSendEmailComm').find('.input-field-submit').find('input[type=submit]').attr('data-eid', '<?php echo($employer_id) ?>');
                            });
                            jQuery(document).on('click', '.jobsearch-modelcvrltr-btn-<?php echo($send_message_form_rand) ?>', function () {
                                jobsearch_modal_popup_open('JobSearchCandCovershwModal<?php echo($send_message_form_rand) ?>');
                            });
                        </script>
                        <div class="jobsearch-applied-jobs-wrap">
                            <?php
                            $cand_is_pending = false;
                            if ($candidate_status != 'on') {
                                $cand_is_pending = true;
                                echo jobsearch_applicant_cand_profile_notaprov_txt();
                            } else if (isset($applicant_status['status']) && $applicant_status['status'] == 'pending') {
                                $cand_is_pending = true;
                                echo jobsearch_applicant_pend_profile_review_txt();
                            }
                            ?>
                            <a class="jobsearch-applied-jobs-thumb">
                                <?php
                                if (!$cand_is_pending) {
                                    echo do_action('jobsearch_export_selection_emp', $_candidate_id, $_job_id);
                                }
                                ?>
                                <img src="<?php echo($user_def_avatar_url) ?>" alt="">
                            </a>
                            <div class="jobsearch-applied-jobs-text">
                                <div class="jobsearch-applied-jobs-left">
                                    <?php
                                    $user_apply_data = get_user_meta($candidate_user_id, 'jobsearch-user-jobs-applied-list', true);
                                    $aply_date_time = '';
                                    if (!empty($user_apply_data)) {
                                        $user_apply_key = array_search($_job_id, array_column($user_apply_data, 'post_id'));
                                        $aply_date_time = isset($user_apply_data[$user_apply_key]['date_time']) ? $user_apply_data[$user_apply_key]['date_time'] : '';
                                    }
                                    if ($candidate_jobtitle != '' && !$cand_profile_restrict::cand_field_is_locked('profile_fields|job_title', 'applicants', $_candidate_id)) { ?>
                                        <span> <?php echo apply_filters('jobsearch_cand_jobtitle_indisplay', $candidate_jobtitle, $_candidate_id) ?></span>
                                        <?php
                                    }

                                    if (in_array($_candidate_id, $viewed_candidates)) { ?>
                                        <small class="profile-view viewed"><?php esc_html_e('(Viewed)', 'wp-jobsearch') ?></small>
                                        <?php
                                    } else {
                                        ?>
                                        <small class="profile-view unviewed"><?php esc_html_e('(Unviewed)', 'wp-jobsearch') ?></small>
                                        <?php
                                    }
                                    $user_email_adr = isset($candidate_user_obj->user_email) ? $candidate_user_obj->user_email : '';
                                    if (!$cand_profile_restrict::cand_field_is_locked('profile_fields|email', 'applicants', $_candidate_id)) {
                                        ?>
                                        <a href="javascript:void(0);"
                                           class="jobsearch-modelemail-btn-<?php echo($send_message_form_rand) ?> jobsearch-user-email">
                                            <small> <?php printf(esc_html__('Email: %s', 'wp-jobsearch'), $user_email_adr) ?></small>
                                        </a>
                                        <?php
                                    }
                                    if (in_array($_candidate_id, $job_short_int_list)) {
                                        ?>
                                        <small class="profile-view viewed"><?php esc_html_e('(Shortlisted)', 'wp-jobsearch') ?></small>
                                        <?php
                                    }

                                    if (in_array($_candidate_id, $job_reject_int_list)) { ?>
                                        <small class="profile-view unviewed"><?php esc_html_e('(Rejected)', 'wp-jobsearch') ?></small>
                                    <?php }

                                    apply_filters('Jobsearch_Cand_shortlisted_View', $_candidate_id, $job_short_int_list);

                                    echo apply_filters('jobsearch_applicants_list_before_title', '', $_candidate_id, $_job_id);
                                    ?>
                                    <h2 class="jobsearch-pst-title">
                                        <a href="<?php echo add_query_arg(array('job_id' => $_job_id, 'employer_id' => $employer_id, 'action' => 'preview_profile'), get_permalink($_candidate_id)) ?>"><?php echo get_the_title($_candidate_id) ?></a>
                                        <?php if ($candidate_age != '' && !$cand_profile_restrict::cand_field_is_locked('profile_fields|date_of_birth', 'applicants', $_candidate_id)) { ?>
                                            <small><?php echo apply_filters('jobsearch_dash_applicants_age_html', sprintf(esc_html__('(Age: %s years)', 'wp-jobsearch'), $candidate_age)) ?></small>
                                            <?php
                                        }
                                        if ($candidate_phone != '' && !$cand_profile_restrict::cand_field_is_locked('profile_fields|phone', 'applicants', $_candidate_id)) { ?>
                                            <small><?php printf(esc_html__('Phone: %s', 'wp-jobsearch'), $candidate_phone) ?></small>
                                        <?php } ?>
                                    </h2>
                                    <ul>
                                        <?php
                                        if ($aply_date_time > 0) {
                                            ?>
                                            <li>
                                                <i class="jobsearch-icon jobsearch-calendar"></i> <?php printf(esc_html__('Applied at: %s', 'wp-jobsearch'), (date_i18n(get_option('date_format'), $aply_date_time) . ' ' . date_i18n(get_option('time_format'), $aply_date_time))) ?>
                                            </li>
                                            <?php
                                        }
                                        if ($candidate_salary != '' && !$cand_profile_restrict::cand_field_is_locked('profile_fields|salary', 'applicants', $_candidate_id)) {
                                            ?>
                                            <li>
                                                <i class="fa fa-money"></i> <?php printf(esc_html__('Salary: %s', 'wp-jobsearch'), $candidate_salary) ?>
                                            </li>
                                            <?php
                                        }
                                        if ($candidate_city_title != '' && !$cand_profile_restrict::cand_field_is_locked('address_defields', 'applicants', $_candidate_id)) {
                                            ?>
                                            <li><i class="fa fa-map-marker"></i> <?php echo($candidate_city_title) ?>
                                            </li>
                                            <?php
                                        }
                                        if ($candidate_sector != '' && !$cand_profile_restrict::cand_field_is_locked('profile_fields|sector', 'applicants', $_candidate_id)) {
                                            ?>
                                            <li><i class="jobsearch-icon jobsearch-filter-tool-black-shape"></i>
                                                <a><?php echo($candidate_sector) ?></a></li>
                                            <?php
                                        }
                                        ?>
                                    </ul>
                                </div>
                                <div class="jobsearch-applied-job-btns">
                                    <?php
                                    echo apply_filters('employer_dash_apps_acts_listul_after', '', $_candidate_id, $_job_id);
                                    ?>
                                    <ul>
                                        <li>
                                            <a href="<?php echo add_query_arg(array('job_id' => $_job_id, 'employer_id' => $employer_id, 'action' => 'preview_profile'), get_permalink($_candidate_id)) ?>"
                                               class="preview-candidate-profile"><i
                                                        class="fa fa-eye"></i> <?php esc_html_e('Preview', 'wp-jobsearch') ?>
                                            </a>
                                        </li>
                                        <li>
                                            <div class="candidate-more-acts-con">
                                                <a href="javascript:void(0);"
                                                   class="more-actions"><?php esc_html_e('Actions', 'wp-jobsearch') ?>
                                                    <i class="fa fa-angle-down"></i></a>
                                                <ul>
                                                    <?php
                                                    $multiple_cv_files_allow = isset($jobsearch_plugin_options['multiple_cv_uploads']) ? $jobsearch_plugin_options['multiple_cv_uploads'] : '';
                                                    $candidate_cv_file = get_post_meta($_candidate_id, 'candidate_cv_file', true);

                                                    if ((in_array($_candidate_id, $employer_resumes_list) || ($free_shortlist_allow == 'on')) && apply_filters('jobsearch_allow_free_cv_download_check', true, $user_id)) {
                                                        if ($multiple_cv_files_allow == 'on') {
                                                            $ca_at_cv_files = get_post_meta($_candidate_id, 'candidate_cv_files', true);
                                                            if (!empty($ca_at_cv_files)) {
                                                                
                                                                ob_start();
                                                                ?>
                                                                <li>
                                                                    <a href="<?php echo apply_filters('jobsearch_user_attach_cv_file_url', '', $_candidate_id, $_job_id) ?>"
                                                                       oncontextmenu="javascript: return false;"
                                                                       onclick="javascript: if ((event.button == 0 && event.ctrlKey)) {return false};"
                                                                       download="<?php echo apply_filters('jobsearch_user_attach_cv_file_title', '', $_candidate_id, $_job_id) ?>"><?php esc_html_e('Download CV', 'wp-jobsearch') ?></a>
                                                                </li>
                                                                <?php
                                                                $cvfile_html = ob_get_clean();
                                                                echo apply_filters('employer_dash_applics_list_cv_download_link', $cvfile_html, $_candidate_id, $_job_id, 'list');
                                                            }
                                                        } else if (!empty($candidate_cv_file)) {
                                                            $file_attach_id = isset($candidate_cv_file['file_id']) ? $candidate_cv_file['file_id'] : '';
                                                            $file_url = isset($candidate_cv_file['file_url']) ? $candidate_cv_file['file_url'] : '';

                                                            $filename = isset($candidate_cv_file['file_name']) ? $candidate_cv_file['file_name'] : '';

                                                            $file_url = apply_filters('wp_jobsearch_user_cvfile_downlod_url', $file_url, $file_attach_id, $_candidate_id);
                                                            
                                                            ob_start();
                                                            ?>
                                                            <li><a href="<?php echo($file_url) ?>"
                                                                   oncontextmenu="javascript: return false;"
                                                                   onclick="javascript: if ((event.button == 0 && event.ctrlKey)) {return false};"
                                                                   download="<?php echo($filename) ?>"><?php esc_html_e('Download CV', 'wp-jobsearch') ?></a>
                                                            </li>
                                                            <?php
                                                            $cvfile_html = ob_get_clean();
                                                            echo apply_filters('employer_dash_applics_list_cv_download_link', $cvfile_html, $_candidate_id, $_job_id, 'list');
                                                        }
                                                    }
                                                    echo apply_filters('employer_dash_apps_acts_list_after_download_link', '', $_candidate_id, $_job_id);

                                                    //
                                                    if (isset($job_cver_attachs[$_candidate_id]) && $job_cver_attachs[$_candidate_id] != '') {
                                                        $apply_with_coverfile = $job_cver_attachs[$_candidate_id];
                                                        $file_attach_id = isset($apply_with_coverfile['file_id']) ? $apply_with_coverfile['file_id'] : '';
                                                        $file_url = isset($apply_with_coverfile['file_url']) ? $apply_with_coverfile['file_url'] : '';
                                                        $filename = isset($apply_with_coverfile['file_name']) ? $apply_with_coverfile['file_name'] : '';
                                                        $file_url = apply_filters('wp_jobsearch_user_coverfile_downlod_url', $file_url, $file_attach_id, $_candidate_id);
                                                        ?>
                                                        <li><a href="<?php echo($file_url) ?>"
                                                               oncontextmenu="javascript: return false;"
                                                               onclick="javascript: if ((event.button == 0 && event.ctrlKey)) {return false};"
                                                               download="<?php echo($filename) ?>"><?php esc_html_e('Download Cover Letter', 'wp-jobsearch') ?></a>
                                                        </li>
                                                        <?php
                                                    } else if (isset($job_cver_ltrs[$_candidate_id]) && $job_cver_ltrs[$_candidate_id] != '') {
                                                        ?>
                                                        <li><a href="javascript:void(0);"
                                                               class="jobsearch-modelcvrltr-btn-<?php echo($send_message_form_rand) ?>"><?php esc_html_e('View Cover Letter', 'wp-jobsearch') ?></a>
                                                        </li>
                                                        <?php
                                                    }
                                                    if (!$cand_profile_restrict::cand_field_is_locked('profile_fields|email', 'applicants', $_candidate_id)) {
                                                        ?>
                                                        <li>
                                                            <a href="javascript:void(0);"
                                                               class="jobsearch-modelemail-btn-<?php echo($send_message_form_rand) ?>"><?php esc_html_e('Email to Candidate', 'wp-jobsearch') ?></a>
                                                            <?php
                                                            $popup_args = array('p_job_id' => $_job_id, 'cand_id' => $_candidate_id, 'p_emp_id' => $employer_id, 'p_masg_rand' => $send_message_form_rand);
                                                            add_action('wp_footer', function () use ($popup_args) {

                                                                extract(shortcode_atts(array(
                                                                    'p_job_id' => '',
                                                                    'p_emp_id' => '',
                                                                    'cand_id' => '',
                                                                    'p_masg_rand' => ''
                                                                ), $popup_args));
                                                                ?>
                                                                <div class="jobsearch-modal fade"
                                                                     id="JobSearchModalSendEmail<?php echo($p_masg_rand) ?>">
                                                                    <div class="modal-inner-area">&nbsp;</div>
                                                                    <div class="modal-content-area">
                                                                        <div class="modal-box-area">
                                                                            <span class="modal-close"><i
                                                                                        class="fa fa-times"></i></span>
                                                                            <div class="jobsearch-send-message-form">
                                                                                <form method="post"
                                                                                      id="jobsearch_send_email_form<?php echo esc_html($p_masg_rand); ?>" autocomplete="off">
                                                                                    <div class="jobsearch-user-form">
                                                                                        <ul class="email-fields-list">
                                                                                            <li>
                                                                                                <label>
                                                                                                    <?php echo esc_html__('Subject', 'wp-jobsearch'); ?>
                                                                                                    :
                                                                                                </label>
                                                                                                <div class="input-field">
                                                                                                    <input type="text"
                                                                                                           name="send_message_subject"
                                                                                                           value=""/>
                                                                                                </div>
                                                                                            </li>
                                                                                            <li>
                                                                                                <label>
                                                                                                    <?php echo esc_html__('Message', 'wp-jobsearch'); ?>
                                                                                                    :
                                                                                                </label>
                                                                                                <div class="input-field">
                                                                                                    <textarea
                                                                                                            name="send_message_content"></textarea>
                                                                                                </div>
                                                                                            </li>
                                                                                            <li>
                                                                                                <div class="input-field-submit">
                                                                                                    <input type="submit"
                                                                                                           class="applicantto-email-submit-btn"
                                                                                                           data-jid="<?php echo absint($p_job_id); ?>"
                                                                                                           data-eid="<?php echo absint($p_emp_id); ?>"
                                                                                                           data-cid="<?php echo absint($cand_id); ?>"
                                                                                                           data-randid="<?php echo esc_html($p_masg_rand); ?>"
                                                                                                           name="send_message_content"
                                                                                                           value="<?php echo esc_html__('Send', 'wp-jobsearch') ?>"/>
                                                                                                    <span class="loader-box loader-box-<?php echo esc_html($p_masg_rand); ?>"></span>
                                                                                                </div>
                                                                                                <?php jobsearch_terms_and_con_link_txt(); ?>
                                                                                            </li>
                                                                                        </ul>
                                                                                        <div class="message-box message-box-<?php echo esc_html($p_masg_rand); ?>"
                                                                                             style="display:none;"></div>
                                                                                    </div>
                                                                                </form>
                                                                            </div>

                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <?php
                                                            }, 11, 1);
                                                            ?>
                                                        </li>
                                                        <?php
                                                    }
                                                    if (in_array($_candidate_id, $job_reject_int_list)) { ?>
                                                        <li>
                                                            <a href="javascript:void(0);"
                                                               class="undoreject-cand-to-list ajax-enable"
                                                               data-jid="<?php echo absint($_job_id); ?>"
                                                               data-cid="<?php echo absint($_candidate_id); ?>"><?php esc_html_e('Undo Reject', 'wp-jobsearch') ?>
                                                                <span class="app-loader"></span></a>
                                                        </li>
                                                    <?php } else { ?>
                                                        <li>
                                                            <?php if (in_array($_candidate_id, $job_short_int_list)) { ?>
                                                                <a href="javascript:void(0);"
                                                                   class="shortlist-cand-to-intrview"><?php esc_html_e('Shortlisted', 'wp-jobsearch') ?></a>
                                                            <?php } else { ?>
                                                                <a href="javascript:void(0);"
                                                                   class="shortlist-cand-to-intrview ajax-enable"
                                                                   data-jid="<?php echo absint($_job_id); ?>"
                                                                   data-cid="<?php echo absint($_candidate_id); ?>"><?php esc_html_e('Shortlist for Interview', 'wp-jobsearch') ?>
                                                                    <span class="app-loader"></span></a>
                                                            <?php } ?>
                                                        </li>
                                                        <li>
                                                            <?php if (in_array($_candidate_id, $job_reject_int_list)) { ?>
                                                                <a href="javascript:void(0);"
                                                                   class="reject-cand-to-intrview"><?php esc_html_e('Rejected', 'wp-jobsearch') ?></a>
                                                            <?php } else { ?>
                                                                <a href="javascript:void(0);"
                                                                   class="reject-cand-to-intrview ajax-enable"
                                                                   data-jid="<?php echo absint($_job_id); ?>"
                                                                   data-cid="<?php echo absint($_candidate_id); ?>"><?php esc_html_e('Reject', 'wp-jobsearch') ?>
                                                                    <span class="app-loader"></span></a>
                                                            <?php } ?>
                                                        </li>
                                                        <?php
                                                    }
                                                    ?>
                                                    <li>
                                                        <a href="javascript:void(0);"
                                                           class="delete-cand-from-job ajax-enable"
                                                           data-jid="<?php echo absint($_job_id); ?>"
                                                           data-cid="<?php echo absint($_candidate_id); ?>"><?php esc_html_e('Delete', 'wp-jobsearch') ?>
                                                            <span class="app-loader"></span></a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>
                    <?php
                    $aplic_html = ob_get_clean();
                    echo apply_filters('jobseacrh_all_aplicants_aplicitem_html', $aplic_html, $_candidate_id, $_job_id);
                    $popup_args = array(
                        'job_id' => $_job_id,
                        'rand_num' => $send_message_form_rand,
                        'candidate_id' => $_candidate_id,
                    );
                    add_action('wp_footer', function () use ($popup_args) {

                        global $jobsearch_plugin_options;

                        extract(shortcode_atts(array(
                            'job_id' => '',
                            'rand_num' => '',
                            'candidate_id' => '',
                        ), $popup_args));

                        $job_cver_ltrs = get_post_meta($job_id, 'jobsearch_job_apply_cvrs', true);
                        if (isset($job_cver_ltrs[$candidate_id]) && $job_cver_ltrs[$candidate_id] != '') {
                            ?>
                            <div class="jobsearch-modal jobsearch-typo-wrap jobsearch-candcover-popup fade"
                                 id="JobSearchCandCovershwModal<?php echo($rand_num) ?>">
                                <div class="modal-inner-area">&nbsp;</div>
                                <div class="modal-content-area">
                                    <div class="modal-box-area">
                                        <div class="jobsearch-modal-title-box">
                                            <h2><?php esc_html_e('Cover Letter', 'wp-jobsearch') ?></h2>
                                            <span class="modal-close"><i class="fa fa-times"></i></span>
                                        </div>
                                        <p><?php echo($job_cver_ltrs[$candidate_id]) ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    }, 11, 1);
                }
            }
        }

        public static function load_wapp_jobs_posts($jobs_posts, $employer_id)
        {
            if (!empty($jobs_posts)) {
                foreach ($jobs_posts as $_job_id) {
                    $job_applicants_list = get_post_meta($_job_id, 'jobsearch_job_applicants_list', true);
                    $job_applicants_list = jobsearch_is_post_ids_array($job_applicants_list, 'candidate');

                    if (empty($job_applicants_list)) {
                        $job_applicants_list = array();
                    }
                    $job_applicants_count = !empty($job_applicants_list) ? count($job_applicants_list) : 0;
                    //

                    $job_short_int_list = get_post_meta($_job_id, '_job_short_interview_list', true);
                    $job_short_int_list = $job_short_int_list != '' ? explode(',', $job_short_int_list) : '';
                    if (empty($job_short_int_list)) {
                        $job_short_int_list = array();
                    }
                    $job_short_int_list = jobsearch_is_post_ids_array($job_short_int_list, 'candidate');
                    $job_short_int_list_c = !empty($job_short_int_list) ? count($job_short_int_list) : 0;

                    $job_reject_int_list = get_post_meta($_job_id, '_job_reject_interview_list', true);
                    $job_reject_int_list = $job_reject_int_list != '' ? explode(',', $job_reject_int_list) : '';
                    if (empty($job_reject_int_list)) {
                        $job_reject_int_list = array();
                    }
                    $job_reject_int_list = jobsearch_is_post_ids_array($job_reject_int_list, 'candidate');
                    $job_reject_int_list_c = !empty($job_reject_int_list) ? count($job_reject_int_list) : 0;
                    //
                    $total_short_inc_apps_pages = 0;
                    $total_reject_int_apps_pages = 0;
                    if ($job_applicants_count > 6) {

                        $total_short_inc_apps_pages = ceil($job_short_int_list_c / 6);
                        $total_reject_int_apps_pages = ceil($job_reject_int_list_c / 6);
                    }
                    ?>
                    <div class="sjob-aplicants-list sjob-aplicants-<?php echo($_job_id) ?>">

                        <div class="thjob-title">
                            <h2><?php echo get_the_title($_job_id) ?></h2>
                            <?php ob_start(); ?>
                            <div class="total-appcreds-con total-aplicnt-cta-<?php echo($_job_id) ?>">
                                <ul>
                                    <li>
                                        <div class="applicnt-count-box tot-apps active">
                                            <a href="javascript:void(0)" class="all-applicnt-btn"

                                               data-tol-cands="<?php echo absint($job_applicants_count) ?>"
                                               data-job-id="<?php echo($_job_id) ?>"
                                               data-employer-id="<?php echo($employer_id) ?>" class="active">
                                                <span><?php esc_html_e('Total Applicants: ', 'wp-jobsearch') ?></span> <?php echo absint($job_applicants_count) ?>
                                            </a>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="applicnt-count-box sh-apps">
                                            <a href="javascript:void(0)"
                                               class="applicnt-shortlisted-btn"
                                               data-shortlist-gtopage="<?php echo absint($total_short_inc_apps_pages) ?>"
                                               data-tol-cands="<?php echo absint($job_short_int_list_c) ?>"
                                               data-job-id="<?php echo($_job_id) ?>"
                                               data-employer-id="<?php echo($employer_id) ?>"><span><?php esc_html_e('Shortlisted Applicants: ', 'wp-jobsearch') ?></span> <?php echo absint($job_short_int_list_c) ?>
                                            </a>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="applicnt-count-box rej-apps">
                                            <a href="javascript:void(0)" class="applicnt-rejected-btn"
                                               data-job-id="<?php echo($_job_id) ?>"
                                               data-rejected-gtopage="<?php echo absint($total_reject_int_apps_pages) ?>"
                                               data-tol-cands="<?php echo absint($job_reject_int_list_c) ?>"
                                               data-employer-id="<?php echo($employer_id) ?>">
                                                <span><?php esc_html_e('Rejected Applicants: ', 'wp-jobsearch') ?></span> <?php echo absint($job_reject_int_list_c) ?>
                                            </a>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <?php
                            $html = ob_get_clean();
                            echo apply_filters('jobsearch_alaplicnts_job_filters', $html, $_job_id, $job_applicants_count, $job_short_int_list_c, $job_reject_int_list_c);
                            ?>
                        </div>
                        <?php echo do_action('jobsearch_before_empdash_job_all_applics', $_job_id) ?>
                        <?php
                        if (apply_filters('jobsearch_empdash_allapps_before_isexport_btns', true)) {
                            echo do_action('jobsearch_export_select_all_emp', $_job_id);
                            echo do_action('jobsearch_emp_export_btns_list', $_job_id);
                        }
                        ?>
                        <div class="jobsearch-applied-jobs">
                            <?php
                            ob_start();
                            if (!empty($job_applicants_list)) {
                                ?>
                                <ul id="job-apps-list<?php echo($_job_id) ?>" class="jobsearch-row">
                                    <?php self::list_job_all_apps($_job_id, $employer_id); ?>
                                </ul>
                                <?php
                                if ($job_applicants_count > 6) {
                                    $total_apps_pages = ceil($job_applicants_count / 6);
                                    $total_short_inc_apps_pages = ceil($job_short_int_list_c / 6);
                                    $total_reject_int_apps_pages = ceil($job_reject_int_list_c / 6);

                                    ?>
                                    <div class="lodmore-jobapps-btnsec">
                                        <a href="javascript:void(0);" class="lodmore-jobapps-btn"
                                           data-jid="<?php echo($_job_id) ?>"
                                           data-employer-id="<?php echo($employer_id) ?>"
                                           data-tpages="<?php echo($total_apps_pages) ?>"
                                           data-rejected-gtopage="2"
                                           data-shortlist-gtopage="2"
                                           data-shortlisted-cands="<?php echo absint($total_short_inc_apps_pages) ?>"
                                           data-rejected-cands="<?php echo absint($total_reject_int_apps_pages) ?>"
                                           data-gtopage="2"><?php esc_html_e('Load More Applicants', 'wp-jobsearch') ?></a>
                                    </div>
                                    <?php
                                }
                            } else { ?>
                                <p class="jobsearch-no-job-msg"><?php esc_html_e('No applicant found.', 'wp-jobsearch') ?></p>
                                <ul id="job-apps-list<?php echo($_job_id) ?>" class="jobsearch-row"></ul>
                            <?php }
                            $aplics_list = ob_get_clean();
                            echo apply_filters('jobsearch_empdash_ajob_allaplics_list_html', $aplics_list, $_job_id);
                            ?>
                        </div>
                    </div>
                    <?php
                }
            }
        }

        public function load_all_jobs_post_data()
        {
            global $sitepress;
            if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                $sitepress_def_lang = $sitepress->get_default_language();
                $sitepress_curr_lang = $sitepress->get_current_language();
                $sitepress->switch_lang($sitepress_def_lang, true);
            }

            $force_std = $_POST['force_std'];
            $posttype = $_POST['posttype'];

            $employer_id = isset($_POST['emp_id']) ? $_POST['emp_id'] : 0;
            $employer_id = absint($employer_id);

            $args = array(
                'posts_per_page' => "-1",
                'post_type' => $posttype,
                'post_status' => array('publish', 'draft'),
                'fields' => 'ids',
                'order' => 'DESC',
                'orderby' => 'ID',
                'meta_query' => array(
                    array(
                        'key' => 'jobsearch_field_job_posted_by',
                        'value' => $employer_id,
                        'compare' => '=',
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => 'jobsearch_job_applicants_list',
                            'value' => '',
                            'compare' => '!=',
                        ),
                        array(
                            'key' => '_job_reject_interview_list',
                            'value' => '',
                            'compare' => '!=',
                        ),
                    ),
                ),
            );

            $custom_query = new WP_Query($args);
            $all_records = $custom_query->posts;

            if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                $sitepress->switch_lang($sitepress_curr_lang, true);
            }

            $html = "<option value=\"\">" . esc_html__('Please select job', 'wp-jobsearch') . "</option>" . "\n";
            if (isset($all_records) && !empty($all_records)) {
                foreach ($all_records as $user_var) {
                    $selected = $user_var == $force_std ? ' selected="selected"' : '';
                    $post_title = get_the_title($user_var);
                    $html .= "<option{$selected} value=\"{$user_var}\">{$post_title}</option>" . "\n";
                }
            }
            echo json_encode(array('html' => $html));

            wp_die();
        }

        public function load_more_apswith_job_apps()
        {
            global $sitepress;
            if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                $sitepress_def_lang = $sitepress->get_default_language();
                $sitepress_curr_lang = $sitepress->get_current_language();
                $sitepress->switch_lang($sitepress_def_lang, true);
            }
            
            $user_id = get_current_user_id();
            
            $page_num = $_POST['page_num'];
            $employer_id = isset($_POST['emp_id']) ? $_POST['emp_id'] : 0;
            $employer_id = absint($employer_id);

            $args = array(
                'post_type' => 'job',
                'posts_per_page' => 5,
                'paged' => $page_num,
                'post_status' => array('publish', 'draft'),
                'fields' => 'ids',
                'order' => 'DESC',
                'orderby' => 'ID',
                'meta_query' => array(
                    array(
                        'key' => 'jobsearch_field_job_posted_by',
                        'value' => $employer_id,
                        'compare' => '=',
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => 'jobsearch_job_applicants_list',
                            'value' => '',
                            'compare' => '!=',
                        ),
                        array(
                            'key' => '_job_reject_interview_list',
                            'value' => '',
                            'compare' => '!=',
                        ),
                    ),
                ),
            );
            if (jobsearch_user_isemp_member($user_id)) {
                //
                $att_user_pperms = get_user_meta($user_id, 'jobsearch_attchprof_perms', true);

                if (!empty($att_user_pperms) && in_array('u_manage_own_jobs', $att_user_pperms)) {
                    $args['author__in'] = array($user_id);
                }
            }
            $jobs_query = new WP_Query($args);
            $jobs_posts = $jobs_query->posts;
            if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                $sitepress->switch_lang($sitepress_curr_lang, true);
            }
            ob_start();
            self::load_wapp_jobs_posts($jobs_posts, $employer_id);
            $html = ob_get_clean();
            echo json_encode(array('html' => $html));
            wp_die();
        }

        public function load_more_apswith_apps_lis()
        {
            $page_num = absint($_POST['page_num']);
            $_job_id = absint($_POST['_job_id']);

            $employer_id = isset($_POST['emp_id']) ? $_POST['emp_id'] : 0;
            $employer_id = absint($employer_id);

            ob_start();
            self::list_job_all_apps($_job_id, $employer_id, $page_num);
            $html = ob_get_clean();
            echo json_encode(array('html' => $html));

            wp_die();
        }

        public function load_single_apswith_job_inlist()
        {

            $_job_id = absint($_POST['_job_id']);
            $jobs_posts = array($_job_id);
            ob_start();
            self::load_wapp_jobs_posts($jobs_posts, $employer_id);
            $html = ob_get_clean();
            echo json_encode(array('html' => $html));

            wp_die();
        }

        public function alljobs_apps_count_loadboxes()
        {
            global $sitepress;

            if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                $sitepress_def_lang = $sitepress->get_default_language();
                $sitepress_curr_lang = $sitepress->get_current_language();
                $sitepress->switch_lang($sitepress_def_lang, true);
            }
            
            $user_id = get_current_user_id();

            $employer_id = isset($_POST['emp_id']) ? $_POST['emp_id'] : 0;
            $employer_id = absint($employer_id);

            $appcounts = $shappcounts = $rejappcounts = $unviewappcounts = 0;

            $args = array(
                'post_type' => 'job',
                'posts_per_page' => -1,
                'post_status' => array('publish', 'draft'),
                'fields' => 'ids',
                'order' => 'DESC',
                'orderby' => 'ID',
                'meta_query' => array(
                    array(
                        'key' => 'jobsearch_field_job_posted_by',
                        'value' => $employer_id,
                        'compare' => '=',
                    ),
                    array(
                        'key' => 'jobsearch_job_applicants_list',
                        'compare' => 'EXISTS',
                    ),
                ),
            );
            if (jobsearch_user_isemp_member($user_id)) {
                //
                $att_user_pperms = get_user_meta($user_id, 'jobsearch_attchprof_perms', true);

                if (!empty($att_user_pperms) && in_array('u_manage_own_jobs', $att_user_pperms)) {
                    $args['author__in'] = array($user_id);
                }
            }
            $jobs_query = new WP_Query($args);
            $jobs_posts = $jobs_query->posts;

            if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                $sitepress->switch_lang($sitepress_curr_lang, true);
            }

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
                    
                    $viewed_candidates = get_post_meta($_job_id, 'jobsearch_viewed_candidates', true);
                    if (empty($viewed_candidates)) {
                        $viewed_candidates = array();
                    }
                    $viewed_candidates = jobsearch_is_post_ids_array($viewed_candidates, 'candidate');
                    $viewed_candidates_c = !empty($viewed_candidates) ? count($viewed_candidates) : 0;
                    $viewed_candidates_counts = 0;
                    if ($job_applicants_count > $viewed_candidates_c) {
                        $viewed_candidates_counts = $job_applicants_count - $viewed_candidates_c;
                    }
                    $unviewappcounts += $viewed_candidates_counts;
                    //
                }
            }

            echo json_encode(array('appcounts' => $appcounts, 'shappcounts' => $shappcounts, 'rejappcounts' => $rejappcounts, 'unviewappcounts' => $unviewappcounts));

            wp_die();
        }

    }

    $empall_applicants_handle = new jobsearch_empall_applicants_handle();
}
