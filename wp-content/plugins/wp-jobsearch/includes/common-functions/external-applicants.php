<?php
if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('jobsearch_allexternal_applicants_handle')) {

    class jobsearch_allexternal_applicants_handle {

        // hook things up
        public function __construct() {
            //
            add_action('admin_menu', array($this, 'external_applicants_create_menu'));
            
            add_action('wp_ajax_jobsearch_external_single_apswith_job_inlist', array($this, 'load_single_apswith_job_inlist'));
            //
            add_action('wp_ajax_jobsearch_load_external_apswith_job_posts', array($this, 'load_all_jobs_post_data'));
            add_action('wp_ajax_jobsearch_external_more_apswith_job_apps', array($this, 'load_more_apswith_job_apps'));
            add_action('wp_ajax_jobsearch_load_external_apswith_apps_lis', array($this, 'load_more_apswith_apps_lis'));
            
            add_action('admin_init', array($this, 'alljobs_apps_export_report'));
        }

        public function external_applicants_create_menu() {
            //create new top-level menu
            add_submenu_page('jobsearch-applicants-list', esc_html__('External Applicants', 'wp-jobsearch'), esc_html__('External Applicants', 'wp-jobsearch'), apply_filters('jobsearch_bk_all_externalapplics_capability', 'administrator'), 'jobsearch-externalapps-list', function () {

                $args = array(
                    'post_type' => 'job',
                    'posts_per_page' => 5,
                    'post_status' => array('publish', 'draft'),
                    'fields' => 'ids',
                    'order' => 'DESC',
                    'orderby' => 'ID',
                    'meta_query' => array(
                        array(
                            'key' => 'jobsearch_external_job_apply_data',
                            'value' => '',
                            'compare' => '!=',
                        ),
                    ),
                );
                
                $selcted_emp = isset($_GET['all_emps_wapps_selctor']) ? $_GET['all_emps_wapps_selctor'] : '';
                $filter_from_date = isset($_GET['alljobs_wafrom_date']) ? $_GET['alljobs_wafrom_date'] : '';
                $filter_to_date = isset($_GET['alljobs_wato_date']) ? $_GET['alljobs_wato_date'] : '';
                
                if ($selcted_emp != '') {
                    $args['meta_query'][] = array(
                        'key' => 'jobsearch_field_job_posted_by',
                        'value' => $selcted_emp,
                        'compare' => '=',
                    );
                }
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
                
                $get_job_id = isset($_GET['job_id']) ? $_GET['job_id'] : '';
                if ($get_job_id > 0 && get_post_type($get_job_id) == 'job') {
                    $args['post__in'] = array($get_job_id);
                }
                $args = apply_filters('jobsearch_bk_all_emailapplics_queryargs', $args);
                $jobs_query = new WP_Query($args);
                $totl_found_jobs = $jobs_query->found_posts;
                $jobs_posts = $jobs_query->posts;
                
                $com_date_format = jobsearch_common_dateformat_picker();
                ?>

                <div class="jobsearch-allaplicants-holder jobsearch-emailaplicants-holder">
                    <script>
                        jQuery(document).ready(function () {
                            jobsearch_jobs_emailapps_count_load();
                            
                            jQuery('.aplics-mnemp-filterbtn').on('click', function() {
                                var this_prnet = jQuery(this).parents('.allapps-monthemp-filtrmain');
                                this_prnet.find('.filtr-loder').html('<i class="fa fa-refresh fa-spin"></i>');
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
                    <div class="select-appsjob-con">
                        <div class="filter-allreps-mainholdr">
                            <div class="allapps-selctcounts-holdr">
                                <div class="allapps-job-label"><h2><?php esc_html_e('Filter by Job', 'wp-jobsearch') ?></h2></div>
                                <div class="allapps-jobselct-con" style="display: inline-block; position: relative;">
                                    <?php
                                    $job_selcted_by = '';
                                    self::get_custom_post_field($job_selcted_by, 'job', esc_html__('Jobs', 'wp-jobsearch'), 'external_jobs_wapps_selctor');
                                    ?>
                                </div>
                            </div>
                            <div class="allapps-monthemp-filtrmain">
                                <form autocomplete="off" method="get">
                                    <input type="hidden" name="page" value="jobsearch-externalapps-list">
                                    <div class="repapps-filtr-fieldscon">
                                        <div class="allapps-allemp-filtrholdr">
                                            <div class="allapps-job-label"><h2><?php esc_html_e('Filter by Employer', 'wp-jobsearch') ?></h2></div>
                                            <div class="allapps-jobselct-con" style="display: inline-block; position: relative;">
                                                <?php
                                                self::get_custom_post_field($selcted_emp, 'employer', esc_html__('Employers', 'wp-jobsearch'), 'all_emps_wapps_selctor');
                                                ?>
                                            </div>
                                        </div>
                                        <div class="allapps-months-filtrholdr">
                                            <div class="allapps-mnthefiltr-date date-from">
                                                <label><?php esc_html_e('From', 'wp-jobsearch') ?></label>
                                                <input type="text" name="alljobs_wafrom_date" value="<?php echo ($filter_from_date) ?>">
                                            </div>
                                            <div class="allapps-mnthefiltr-date date-to">
                                                <label><?php esc_html_e('To', 'wp-jobsearch') ?></label>
                                                <input type="text" name="alljobs_wato_date" value="<?php echo ($filter_to_date) ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="filtr-mnth-btns-sec">
                                        <a href="javascript:void(0);" class="button aplics-mnemp-filterbtn"><?php esc_html_e('Submit', 'wp-jobsearch') ?></a>
                                        <span class="filtr-loder"></span>
                                        <a href="javascript:void(0);" class="button allplics-export-excel"><?php esc_html_e('Export to Excel', 'wp-jobsearch') ?></a>
                                        <span class="exprt-loder"></span>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php
                        $appcounts = 0;

                        $args = array(
                            'post_type' => 'job',
                            'posts_per_page' => -1,
                            'post_status' => array('publish', 'draft'),
                            'fields' => 'ids',
                            'order' => 'DESC',
                            'orderby' => 'ID',
                            'meta_query' => array(
                                array(
                                    'key' => 'jobsearch_external_job_apply_data',
                                    'value' => '',
                                    'compare' => '!=',
                                ),
                            ),
                        );
                        $jobs_query = new WP_Query($args);
                        $jobs_posts = $jobs_query->posts;

                        if (!empty($jobs_posts)) {
                            foreach ($jobs_posts as $_job_id) {
                                $job_applicants_list = get_post_meta($_job_id, 'jobsearch_external_job_apply_data', true);

                                if (empty($job_applicants_list)) {
                                    $job_applicants_list = array();
                                }

                                $job_applicants_count = !empty($job_applicants_list) ? count($job_applicants_list) : 0;
                                $appcounts += $job_applicants_count;
                            }
                        }
                        ?>
                        <div class="overall-appcreds-con">
                            <ul>
                                <li><span class="tot-apps"><?php esc_html_e('Total Clicks: ', 'wp-jobsearch') ?></span><div class="applicnt-count-box tot-apps"> <a><?php echo ($appcounts) ?></a></div></li>
                            </ul>
                        </div>
                        <form autocomplete="off" id="jobsearch-empmonth-reports-form" method="post">
                            <input type="hidden" name="date_from" value="<?php echo ($filter_from_date) ?>">
                            <input type="hidden" name="date_to" value="<?php echo ($filter_to_date) ?>">
                            <input type="hidden" name="employer_id" value="<?php echo ($selcted_emp) ?>">
                            <?php wp_nonce_field('jobsearch_export_extrnalapsrept') ?>
                            <input type="hidden" name="jobsearch_empmnth_extrnalapps_reprts_export" value="1">
                        </form>
                    </div>
                    <?php
                    if (!empty($jobs_posts)) {
                        ?>
                        <div class="jobsearch-all-aplicantslst">
                            <?php
                            self::load_wapp_jobs_posts($jobs_posts);
                            ?>
                        </div>
                        <?php
                        if ($totl_found_jobs > 5) {
                            $total_pages = ceil($totl_found_jobs / 5);
                            ?>
                            <div class="lodemail-apps-btnsec">
                                <a href="javascript:void(0);" class="lodemail-apps-btn" data-tpages="<?php echo ($total_pages) ?>" data-gtopage="2"><?php esc_html_e('Load More Jobs', 'wp-jobsearch') ?></a>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <p><?php esc_html_e('No job found with applicants.', 'wp-jobsearch') ?></p>
                        <?php
                    }
                    ?>
                </div>
                <?php
            });
        }
        
        public function alljobs_apps_export_report() {
            if (isset($_POST['jobsearch_empmnth_extrnalapps_reprts_export'])) {
                check_admin_referer('jobsearch_export_extrnalapsrept');
                
                $from_date = isset($_POST['date_from']) ? $_POST['date_from'] : '';
                $to_date = isset($_POST['date_to']) ? $_POST['date_to'] : '';
                $employer_id = isset($_POST['employer_id']) ? $_POST['employer_id'] : '';

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
                            'key' => 'jobsearch_external_job_apply_data',
                            'value' => '',
                            'compare' => '!=',
                        ),
                    ),
                );
                
                if ($employer_id != '') {
                    $args['meta_query'][] = array(
                        'key' => 'jobsearch_field_job_posted_by',
                        'value' => $employer_id,
                        'compare' => '=',
                    );
                }
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
                        'Job Title' => 'string',//text
                        'Applicant Name' => 'string',
                        'Email' => 'string',
                        'User Agent' => 'string',
                        'IP Address' => 'string',
                        'Apply Date' => 'string',
                    );
                    $rows = array();
                    
                    foreach ($job_posts as $job_id) {
                        
                        $job_applicants_list = get_post_meta($job_id, 'jobsearch_external_job_apply_data', true);
                        
                        if (!empty($job_applicants_list)) {
                            foreach ($job_applicants_list as $apply_data) {
                                
                                $user_agent = isset($apply_data['user_agent']) ? $apply_data['user_agent'] : '';
                                $ip_address = isset($apply_data['ip_address']) ? $apply_data['ip_address'] : '';
                                $apply_time = isset($apply_data['time']) ? $apply_data['time'] : '';
                                $user_email = isset($apply_data['email']) ? $apply_data['email'] : '';
                                $user_name = isset($apply_data['name']) ? $apply_data['name'] : '';
                                
                                $aply_date_time = '-';
                                if ($apply_time != '') {
                                    $aply_date_time = date(get_option('date_format'), $apply_time);
                                }
                                
                                $rows[] = array(
                                    get_the_title($job_id),
                                    $user_name,
                                    $user_email,
                                    $user_agent,
                                    $ip_address,
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

        public static function get_custom_post_field($selected_id, $custom_post_slug, $field_label, $field_name, $custom_name = '') {
            global $jobsearch_form_fields;
            $custom_post_first_element = esc_html__('All ', 'wp-jobsearch');
            $custom_posts = array(
                '' => $custom_post_first_element . $field_label,
            );
            if ($selected_id) {
                $this_custom_posts = get_the_title($selected_id);
                $custom_posts[$selected_id] = $this_custom_posts;
            }

            $rand_num = rand(1234568, 6867867);
            $field_params = array(
                'classes' => 'job_external_post_cajax',
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
            $jobsearch_form_fields->select_field($field_params);
            ?>
            <span class="jobsearch-field-loader custom_post_loader_<?php echo absint($rand_num); ?>"></span>
            <?php
        }
        
        public static function list_job_all_apps($_job_id, $apps_start = 0) {
            global $jobsearch_plugin_options;
            //update_post_meta($_job_id, 'jobsearch_external_job_apply_data', '');
            $job_applicants_list = get_post_meta($_job_id, 'jobsearch_external_job_apply_data', true);
            arsort($job_applicants_list);
            
            if (empty($job_applicants_list)) {
                $job_applicants_list = array();
            }

            //
            $apps_offset = 6;
            if ($apps_start > 0) {
                $apps_start = ($apps_start - 1) * ($apps_offset);
            }
            $job_applicants_list = array_slice($job_applicants_list, $apps_start, $apps_offset);

            if (!empty($job_applicants_list)) {
                foreach ($job_applicants_list as $apply_data) {
                    
                    $user_agent = isset($apply_data['user_agent']) ? $apply_data['user_agent'] : '';
                    $ip_address = isset($apply_data['ip_address']) ? $apply_data['ip_address'] : '';
                    $apply_time = isset($apply_data['time']) ? $apply_data['time'] : '';
                    $user_email = isset($apply_data['email']) ? $apply_data['email'] : '';
                    $user_name = isset($apply_data['name']) ? $apply_data['name'] : '';
                    
                    $_candidate_id = '';
                    $user_phone = $user_def_avatar_url = $job_title = $current_salary = '';
                    if ($user_email != '' && email_exists($user_email)) {
                        $_user_obj = get_user_by('email', $user_email);
                        $_user_id = isset($_user_obj->ID) ? $_user_obj->ID : '';
                        $_candidate_id = jobsearch_get_user_candidate_id($_user_id);
                        if ($_candidate_id) {
                            $current_salary = jobsearch_candidate_current_salary($_candidate_id);
                            $job_title = get_post_meta($_candidate_id, 'jobsearch_field_candidate_jobtitle', true);
                            $user_phone = get_post_meta($_candidate_id, 'jobsearch_field_user_phone', true);
                            $user_def_avatar_url = jobsearch_candidate_img_url_comn($_candidate_id);
                        }
                    }

                    $_rand_id = rand(1000000, 9999999);
                    ?>
                    <li class="jobsearch-column-12">
                        <div class="jobsearch-applied-jobs-wrap">
                            <?php
                            if ($user_def_avatar_url != '') {
                                ?>
                                <a class="jobsearch-applied-jobs-thumb">
                                    <img src="<?php echo ($user_def_avatar_url) ?>" alt="">
                                </a>
                                <?php
                            }
                            ?>
                            <div class="jobsearch-applied-jobs-text">
                                <div class="jobsearch-applied-jobs-left">
                                    <?php
                                    if ($user_name != '') {
                                        ?>
                                        <h2 class="jobsearch-pst-title">
                                            <a><?php echo ($user_name) ?></a>
                                            <?php
                                            if ($user_phone != '') {
                                                ?>
                                                <small><a href="tel:<?php echo ($user_phone) ?>"><?php printf(esc_html__('Phone: %s', 'wp-jobsearch'), $user_phone) ?></a></small>
                                                <?php
                                            }
                                            ?>
                                        </h2>
                                        <?php
                                    }
                                    if ($job_title != '') {
                                        ?>
                                        <span> <?php echo apply_filters('jobsearch_cand_jobtitle_indisplay', $job_title, $_candidate_id) ?></span>
                                        <?php
                                    }
                                    ?>
                                    <ul>
                                        <li><?php printf(esc_html__('IP Address: %s', 'wp-jobsearch'), $ip_address) ?></li>
                                        <li><?php printf(esc_html__('User Agent: %s', 'wp-jobsearch'), str_replace(array('(', ')'), array('<br>(', ')<br>'), $user_agent)) ?></li>
                                    </ul>
                                    <?php
                                    if ($user_email != '') {
                                        ?>
                                        <ul>
                                            <?php
                                            if ($current_salary != '') {
                                                ?>
                                                <li><i class="fa fa-money"></i> <?php printf(esc_html__('Salary: %s', 'wp-jobsearch'), $current_salary) ?></li>
                                                <?php
                                            }
                                            ?>
                                            <li><i class="fa fa-envelope"></i> <a href="mailto:<?php echo ($user_email) ?>"><?php printf(esc_html__('Email: %s', 'wp-jobsearch'), $user_email) ?></a></li>
                                        </ul>
                                        <?php
                                    }
                                    if ($apply_time > 0) {
                                        ?>
                                        <ul class="apply-time-mncon">
                                            <li> <?php printf(esc_html__('Applied at: %s', 'wp-jobsearch'), (date_i18n(get_option('date_format'), $apply_time) . ' ' . date_i18n(get_option('time_format'), $apply_time))) ?></li>
                                        </ul>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <div class="jobsearch-applied-job-btns">
                                    <ul>
                                        <?php
                                        $multiple_cv_files_allow = isset($jobsearch_plugin_options['multiple_cv_uploads']) ? $jobsearch_plugin_options['multiple_cv_uploads'] : '';
                                        $candidate_cv_file = get_post_meta($_candidate_id, 'candidate_cv_file', true);

                                        if ($multiple_cv_files_allow == 'on') {
                                            $ca_at_cv_files = get_post_meta($_candidate_id, 'candidate_cv_files', true);
                                            if (!empty($ca_at_cv_files)) { ?>
                                                <li>
                                                    <a href="<?php echo apply_filters('jobsearch_user_attach_cv_file_url', '', $_candidate_id, $_job_id) ?>"
                                                       class="preview-candidate-profile"
                                                       oncontextmenu="javascript: return false;"
                                                       onclick="javascript: if ((event.button == 0 && event.ctrlKey)) {return false};"
                                                       download="<?php echo apply_filters('jobsearch_user_attach_cv_file_title', '', $_candidate_id, $_job_id) ?>"><i
                                                                class="fa fa-download"></i> <?php esc_html_e('Download CV', 'wp-jobsearch') ?>
                                                    </a></li>
                                                <?php
                                            }
                                        } else if (!empty($candidate_cv_file)) {
                                            $file_attach_id = isset($candidate_cv_file['file_id']) ? $candidate_cv_file['file_id'] : '';
                                            $file_url = isset($candidate_cv_file['file_url']) ? $candidate_cv_file['file_url'] : '';

                                            $filename = isset($candidate_cv_file['file_name']) ? $candidate_cv_file['file_name'] : '';
                                            if (is_numeric($file_attach_id) && get_post_type($file_attach_id) == 'attachment') {
                                                $file_path = get_attached_file($file_attach_id);
                                                $filename = basename($file_path);
                                            }

                                            $file_url = apply_filters('wp_jobsearch_user_cvfile_downlod_url', $file_url, $file_attach_id, $_candidate_id);
                                            ?>
                                            <li><a href="<?php echo($file_url) ?>" class="preview-candidate-profile"
                                                   oncontextmenu="javascript: return false;"
                                                   onclick="javascript: if ((event.button == 0 && event.ctrlKey)) {return false};"
                                                   download="<?php echo($filename) ?>"><i
                                                            class="fa fa-download"></i> <?php esc_html_e('Download CV', 'wp-jobsearch') ?>
                                                </a></li>
                                            <?php
                                        }

                                        if ($_candidate_id > 0) {
                                            $args = array(
                                                'candidate_id' => $_candidate_id,
                                                'view' => 'list',
                                                'class' => 'preview-candidate-profile',
                                                'icon' => 'fa fa-file-pdf-o'
                                            );
                                            apply_filters('jobsearch_cand_generate_resume_btn', $args);
                                        }
                                        ?>

                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>
                    <?php
                }
            }
        }

        public static function load_wapp_jobs_posts($jobs_posts) {
            if (!empty($jobs_posts)) {
                foreach ($jobs_posts as $_job_id) {
                    $job_applicants_list = get_post_meta($_job_id, 'jobsearch_external_job_apply_data', true);
                    $job_aplly_email = get_post_meta($_job_id, 'jobsearch_field_job_apply_email', true);

                    if (empty($job_applicants_list)) {
                        $job_applicants_list = array();
                    }

                    $job_applicants_count = !empty($job_applicants_list) ? count($job_applicants_list) : 0;
                    ?>

                    <div class="sjob-aplicants-list">
                        <div class="thjob-title">
                            <h2>
                                <?php echo get_the_title($_job_id) ?>
                            </h2>
                            <div class="total-appcreds-con">
                                <ul>
                                    <li><div class="applicnt-count-box tot-apps"><span><?php esc_html_e('Total Clicks: ', 'wp-jobsearch') ?></span> <?php echo absint($job_applicants_count) ?></div></li>
                                </ul>
                            </div>
                        </div>
                        <div class="jobsearch-applied-jobs">
                            <?php
                            if (!empty($job_applicants_list)) {
                                ?>
                                <ul id="job-apps-list<?php echo ($_job_id) ?>" class="jobsearch-row">
                                    <?php
                                    self::list_job_all_apps($_job_id);
                                    ?>
                                </ul>
                                <?php
                                if ($job_applicants_count > 6) {
                                    $total_apps_pages = ceil($job_applicants_count / 6);
                                    ?>
                                    <div class="lodemail-jobapps-btnsec">
                                        <a href="javascript:void(0);" class="lodemail-jobapps-btn" data-jid="<?php echo ($_job_id) ?>" data-tpages="<?php echo ($total_apps_pages) ?>" data-gtopage="2"><?php esc_html_e('Load More Applicants', 'wp-jobsearch') ?></a>
                                    </div>
                                    <?php
                                }
                            } else {
                                ?>
                                <p><?php esc_html_e('No applicant found.', 'wp-jobsearch') ?></p>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
            }
        }

        public function load_all_jobs_post_data() {
            $force_std = $_POST['force_std'];
            $posttype = $_POST['posttype'];
            $args = array(
                'posts_per_page' => "-1",
                'post_type' => $posttype,
                'post_status' => array('publish', 'draft'),
                'fields' => 'ids',
                'order' => 'DESC',
                'orderby' => 'ID',
            );
            if ($posttype == 'job') {
                $args['meta_query'] = array(
                    array(
                        'key' => 'jobsearch_external_job_apply_data',
                        'value' => '',
                        'compare' => '!=',
                    ),
                );
            }
            
            $args = apply_filters('jobsearch_bk_all_emailapplics_queryargs', $args);
            $custom_query = new WP_Query($args);
            $all_records = $custom_query->posts;
            
            $fist_opt_label = esc_html__('Please select job', 'wp-jobsearch');
            if ($posttype == 'employer') {
                $fist_opt_label = esc_html__('Please select employer', 'wp-jobsearch');
            }

            $html = "<option value=\"\">" . $fist_opt_label . "</option>" . "\n";
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

        public function load_more_apswith_job_apps() {
            $page_num = $_POST['page_num'];

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
                        'key' => 'jobsearch_external_job_apply_data',
                        'value' => '',
                        'compare' => '!=',
                    ),
                ),
            );
            
            $selcted_emp = isset($_POST['all_emps_wapps_selctor']) ? $_POST['all_emps_wapps_selctor'] : '';
            $filter_from_date = isset($_POST['alljobs_wafrom_date']) ? $_POST['alljobs_wafrom_date'] : '';
            $filter_to_date = isset($_POST['alljobs_wato_date']) ? $_POST['alljobs_wato_date'] : '';

            if ($selcted_emp != '') {
                $args['meta_query'][] = array(
                    'key' => 'jobsearch_field_job_posted_by',
                    'value' => $selcted_emp,
                    'compare' => '=',
                );
            }
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
            
            $args = apply_filters('jobsearch_bk_all_emailapplics_queryargs', $args);
            $jobs_query = new WP_Query($args);
            $jobs_posts = $jobs_query->posts;

            ob_start();
            self::load_wapp_jobs_posts($jobs_posts);
            $html = ob_get_clean();
            echo json_encode(array('html' => $html));

            wp_die();
        }

        public function load_more_apswith_apps_lis() {
            $page_num = absint($_POST['page_num']);
            $_job_id = absint($_POST['_job_id']);


            ob_start();
            self::list_job_all_apps($_job_id, $page_num);
            $html = ob_get_clean();
            echo json_encode(array('html' => $html));

            wp_die();
        }

        public function load_single_apswith_job_inlist() {

            $_job_id = absint($_POST['_job_id']);
            $jobs_posts = array($_job_id);
            ob_start();
            self::load_wapp_jobs_posts($jobs_posts);
            $html = ob_get_clean();
            echo json_encode(array('html' => $html));

            wp_die();
        }

    }

    return new jobsearch_allexternal_applicants_handle();
}
