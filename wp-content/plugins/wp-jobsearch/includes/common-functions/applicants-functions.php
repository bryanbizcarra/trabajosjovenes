<?php
if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('jobsearch_all_applicants_handle')) {

    class jobsearch_all_applicants_handle
    {

        // hook things up
        public function __construct()
        {
            add_action('admin_menu', array($this, 'jobsearch_all_applicants_create_menu'));
            add_action('wp_ajax_jobsearch_load_single_apswith_job_inlist', array($this, 'load_single_apswith_job_inlist'));
            
            add_action('wp_ajax_jobsearch_load_apswith_emps_inlist', array($this, 'load_apswith_emps_inlist'));
            //
            add_action('wp_ajax_jobsearch_load_all_apswith_job_posts', array($this, 'load_all_jobs_post_data'));
            add_action('wp_ajax_jobsearch_load_more_apswith_job_apps', array($this, 'load_more_apswith_job_apps'));
            add_action('wp_ajax_jobsearch_load_more_apswith_apps_lis', array($this, 'load_more_apswith_apps_lis'));
            //
            add_action('wp_ajax_jobsearch_alljobs_apps_count_loadboxes', array($this, 'alljobs_apps_count_loadboxes'));
            
            add_action('admin_init', array($this, 'alljobs_apps_export_report'));
        }

        public function jobsearch_all_applicants_create_menu()
        {
            //create new top-level menu
            add_menu_page(esc_html__('All Applicants', 'wp-jobsearch'), esc_html__('All Applicants', 'wp-jobsearch'), apply_filters('jobsearch_bk_all_applics_capability', 'administrator'), 'jobsearch-applicants-list', function () {

                $args = array(
                    'post_type' => 'job',
                    'posts_per_page' => 5,
                    'post_status' => array('publish', 'draft'),
                    'fields' => 'ids',
                    'meta_key' => 'jobsearch_job_applicants_list',
                    'order' => 'DESC',
                    'orderby' => 'ID',
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
                $args = apply_filters('jobsearch_bk_all_applics_queryargs', $args);
                add_filter('posts_where', array($this, 'reject_applics_jobs_where_cond'));
                $jobs_query = new WP_Query($args);
                remove_filter('posts_where', array($this, 'reject_applics_jobs_where_cond'));
                //var_dump($jobs_query->request);
                $totl_found_jobs = $jobs_query->found_posts;
                $jobs_posts = $jobs_query->posts;
                
                $com_date_format = jobsearch_common_dateformat_picker();
                ?>

                <div class="jobsearch-allaplicants-holder">
                    <script>
                        jQuery(document).ready(function () {
                            jobsearch_alljobs_apps_count_load();

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
                                <div class="allapps-job-label"><h2><?php esc_html_e('Filter by Job', 'wp-jobsearch') ?></h2>
                                </div>
                                <div class="allapps-jobselct-con" style="display: inline-block; position: relative;">
                                    <?php
                                    $job_selcted_by = '';
                                    self::get_custom_post_field($job_selcted_by, 'job', esc_html__('Jobs', 'wp-jobsearch'), 'all_jobs_wapps_selctor');
                                    ?>
                                </div>
                            </div>
                            <div class="allapps-monthemp-filtrmain">
                                <form method="get" autocomplete="off">
                                    <input type="hidden" name="page" value="jobsearch-applicants-list">
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
                        <div class="overall-appcreds-con">
                            <ul>
                                <li>
                                    <span class="tot-apps"><?php esc_html_e('Total Applicants: ', 'wp-jobsearch') ?></span>
                                    <div class="applicnt-count-box tot-apps"><a class="overall-site-aplicnts">0</a>
                                    </div>
                                </li>
                                <li>
                                    <span class="sh-apps"><?php esc_html_e('Shortlisted Applicants: ', 'wp-jobsearch') ?></span>
                                    <div class="applicnt-count-box sh-apps"><a class="overall-site-shaplicnts">0</a>
                                    </div>
                                </li>
                                <li>
                                    <span class="rej-apps"><?php esc_html_e('Rejected Applicants: ', 'wp-jobsearch') ?></span>
                                    <div class="applicnt-count-box rej-apps"><a class="overall-site-rejaplicnts">0</a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <form id="jobsearch-empmonth-reports-form" method="post" autocomplete="off">
                            <input type="hidden" name="date_from" value="<?php echo ($filter_from_date) ?>">
                            <input type="hidden" name="date_to" value="<?php echo ($filter_to_date) ?>">
                            <input type="hidden" name="employer_id" value="<?php echo ($selcted_emp) ?>">
                            <?php wp_nonce_field('jobsearch_export_allapsrept') ?>
                            <input type="hidden" name="jobsearch_empmnth_allapps_reprts_export" value="1">
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
                            <div class="lodmore-apps-btnsec">
                                <a href="javascript:void(0);" class="lodmore-apps-btn"
                                   data-tpages="<?php echo($total_pages) ?>"
                                   data-gtopage="2"><?php esc_html_e('Load More Jobs', 'wp-jobsearch') ?></a>
                            </div>
                            <?php
                        }
                        add_action('admin_footer', function() {
                            ?>
                            <script>
                                jQuery(document).on('click', '.candidate-more-acts-con .more-actions', function () {
                                    var _this = jQuery(this);
                                    var all_boxes = jQuery('.candidate-more-acts-con');
                                    //
                                    all_boxes.find('ul').slideUp();
                                    all_boxes.find('.more-actions').removeClass('open-options');
                                    //
                                    var this_parent = _this.parent('.candidate-more-acts-con');
                                    if (_this.hasClass('open-options')) {
                                        this_parent.find('ul').slideUp();
                                        _this.removeClass('open-options')
                                    } else {
                                        this_parent.find('ul').slideDown();
                                        _this.addClass('open-options')
                                    }
                                });
                                jQuery(document).on('click', 'body', function (evt) {
                                    var target = evt.target;
                                    var this_box = jQuery('.candidate-more-acts-con');
                                    if (!this_box.is(evt.target) && this_box.has(evt.target).length === 0) {
                                        this_box.find('ul').slideUp();
                                        this_box.find('.more-actions').removeClass('open-options');
                                    }

                                    var more_box = jQuery('.more-fields-act-btn');
                                    if (!more_box.is(evt.target) && more_box.has(evt.target).length === 0) {
                                        more_box.find('ul').slideUp();
                                        more_box.find('.more-actions').removeClass('open-options');
                                    }
                                });
                                jQuery(document).on('click', '.applicantto-email-submit-btn', function (e) {
                                    e.preventDefault();
                                    var _this = jQuery(this);
                                    var _this_rand = _this.attr('data-randid');
                                    var _job_id = _this.attr('data-jid');
                                    var _candidate_id = _this.attr('data-cid');
                                    var _employer_id = _this.attr('data-eid');

                                    var this_form = _this.parents('form');

                                    var this_loader = this_form.find('.loader-box-' + _this_rand);
                                    var this_msg_con = this_form.find('.message-box-' + _this_rand);

                                    var email_subject = this_form.find('input[name="send_message_subject"]');
                                    var email_content = this_form.find('textarea[name="send_message_content"]');

                                    var error = 0;
                                    if (email_subject.val() == '') {
                                        error = 1;
                                        email_subject.css({"border": "1px solid #ff0000"});
                                    } else {
                                        email_subject.css({"border": "1px solid #d3dade"});
                                    }
                                    if (email_content.val() == '') {
                                        error = 1;
                                        email_content.css({"border": "1px solid #ff0000"});
                                    } else {
                                        email_content.css({"border": "1px solid #d3dade"});
                                    }

                                    if (error == 0) {

                                        this_msg_con.hide();
                                        this_loader.html('<i class="fa fa-refresh fa-spin"></i>');
                                        var request = jQuery.ajax({
                                            url: ajaxurl,
                                            method: "POST",
                                            data: {
                                                _job_id: _job_id,
                                                _candidate_id: _candidate_id,
                                                _employer_id: _employer_id,
                                                email_subject: email_subject.val(),
                                                email_content: email_content.val(),
                                                action: 'jobsearch_send_email_to_applicant_by_employer',
                                            },
                                            dataType: "json"
                                        });

                                        request.done(function (response) {
                                            var msg_before = '';
                                            var msg_after = '';
                                            if (typeof response.error !== 'undefined') {
                                                if (response.error == '1') {
                                                    msg_before = '<div class="alert alert-danger"><i class="fa fa-times"></i> ';
                                                    msg_after = '</div>';
                                                } else if (response.error == '0') {
                                                    msg_before = '<div class="alert alert-success"><i class="fa fa-check"></i> ';
                                                    msg_after = '</div>';
                                                }
                                            }
                                            if (typeof response.msg !== 'undefined') {
                                                this_msg_con.html(msg_before + response.msg + msg_after);
                                                this_msg_con.slideDown();
                                                if (typeof response.error !== 'undefined' && response.error == '0') {
                                                    email_subject.val('');
                                                    email_content.val('');
                                                    this_form.find('ul.email-fields-list').slideUp();
                                                }
                                            } else {
                                                this_msg_con.html('<?php esc_html_e('There is some error.', 'wp-jobsearch') ?>');
                                            }
                                            this_loader.html('');

                                        });

                                        request.fail(function (jqXHR, textStatus) {
                                            this_loader.html('<?php esc_html_e('There is some error.', 'wp-jobsearch') ?>');
                                        });
                                    }
                                });
                                jQuery(document).on('click', '.shortlist-cand-to-intrview', function (e) {
                                    e.preventDefault();
                                    var _this = jQuery(this);
                                    var _job_id = _this.attr('data-jid');
                                    var _candidate_id = _this.attr('data-cid');

                                    var this_loader = _this.find('.app-loader');
                                    var this_msg_con = _this;

                                    if (_this.hasClass('ajax-enable')) {
                                        this_loader.html('<i class="fa fa-refresh fa-spin"></i>');
                                        var request = jQuery.ajax({
                                            url: ajaxurl,
                                            method: "POST",
                                            data: {
                                                _job_id: _job_id,
                                                _candidate_id: _candidate_id,
                                                action: 'jobsearch_applicant_to_interview_by_employer',
                                            },
                                            dataType: "json"
                                        });

                                        request.done(function (response) {
                                            if (typeof response.msg !== 'undefined' && typeof response.error !== 'undefined' && response.error == '0') {
                                                this_msg_con.html(response.msg);
                                                _this.removeClass('ajax-enable');
                                                window.location.reload();
                                            }
                                            this_loader.html('');
                                        });

                                        request.fail(function (jqXHR, textStatus) {
                                            this_loader.html('');
                                        });
                                    }
                                });
                                jQuery(document).on('click', '.reject-cand-to-intrview', function (e) {
                                    e.preventDefault();
                                    var _this = jQuery(this);
                                    var _job_id = _this.attr('data-jid');
                                    var _candidate_id = _this.attr('data-cid');

                                    var this_loader = _this.parent('li').find('.app-loader');
                                    var this_msg_con = _this;

                                    if (_this.hasClass('ajax-enable')) {
                                        this_loader.html('<i class="fa fa-refresh fa-spin"></i>');
                                        var request = jQuery.ajax({
                                            url: ajaxurl,
                                            method: "POST",
                                            data: {
                                                _job_id: _job_id,
                                                _candidate_id: _candidate_id,
                                                action: 'jobsearch_applicant_to_reject_by_employer',
                                            },
                                            dataType: "json"
                                        });

                                        request.done(function (response) {
                                            if (typeof response.msg !== 'undefined' && typeof response.error !== 'undefined' && response.error == '0') {
                                                this_msg_con.html(response.msg);
                                                _this.removeClass('ajax-enable');
                                            }
                                            this_loader.html('');

                                        });

                                        request.fail(function (jqXHR, textStatus) {
                                            this_loader.html('');
                                        });
                                    }
                                });

                                jQuery(document).on('click', '.undoreject-cand-to-list', function (e) {
                                    e.preventDefault();
                                    var _this = jQuery(this);
                                    var _job_id = _this.attr('data-jid');
                                    var _candidate_id = _this.attr('data-cid');

                                    var this_loader = _this.parent('li').find('.app-loader');
                                    var this_msg_con = _this;

                                    if (_this.hasClass('ajax-enable')) {
                                        this_loader.html('<i class="fa fa-refresh fa-spin"></i>');
                                        var request = jQuery.ajax({
                                            url: ajaxurl,
                                            method: "POST",
                                            data: {
                                                _job_id: _job_id,
                                                _candidate_id: _candidate_id,
                                                action: 'jobsearch_applicant_to_undoreject_by_employer',
                                            },
                                            dataType: "json"
                                        });

                                        request.done(function (response) {
                                            if (typeof response.msg !== 'undefined' && typeof response.error !== 'undefined' && response.error == '0') {
                                                this_msg_con.html(response.msg);
                                                _this.removeClass('ajax-enable');
                                                window.location.reload(true);

                                            }
                                            this_loader.html('');
                                        });

                                        request.fail(function (jqXHR, textStatus) {
                                            this_loader.html('');
                                        });
                                    }
                                });

                                jQuery(document).on('click', '.delete-cand-from-job', function (e) {
                                    e.preventDefault();
                                    var _this = jQuery(this);
                                    var _job_id = _this.attr('data-jid');
                                    var _candidate_id = _this.attr('data-cid');

                                    var this_loader = _this.parent('li').find('.app-loader');
                                    var this_msg_con = _this;

                                    if (_this.hasClass('ajax-enable')) {
                                        this_loader.html('<i class="fa fa-refresh fa-spin"></i>');
                                        var request = jQuery.ajax({
                                            url: ajaxurl,
                                            method: "POST",
                                            data: {
                                                _job_id: _job_id,
                                                _candidate_id: _candidate_id,
                                                action: 'jobsearch_delete_applicant_by_employer',
                                            },
                                            dataType: "json"
                                        });

                                        request.done(function (response) {
                                            if (typeof response.msg !== 'undefined' && typeof response.error !== 'undefined' && response.error == '0') {
                                                this_msg_con.html(response.msg);
                                                _this.removeClass('ajax-enable');
                                                _this.parents('li.jobsearch-column-12').slideUp();
                                            }
                                            this_loader.html('');
                                        });

                                        request.fail(function (jqXHR, textStatus) {
                                            this_loader.html('');
                                        });
                                    }
                                });
                            </script>
                            <?php
                        }, 35);
                    } else {
                        ?>
                        <p><?php esc_html_e('No job found with applicants.', 'wp-jobsearch') ?></p>
                        <?php
                    }
                    ?>
                </div>

                <?php
            }, '', 30);
        }
        
        public function reject_applics_jobs_where_cond($where) {
            global $wpdb;
            
            $postmeta = $wpdb->postmeta;
            
            $where .= " AND (({$postmeta}.meta_key = 'jobsearch_job_applicants_list' AND {$postmeta}.meta_value != '') OR ({$postmeta}.meta_key = '_job_reject_interview_list' AND {$postmeta}.meta_value != ''))";

            return $where;
        }
        
        public function reject_applics_jobs_ids() {
            global $wpdb;
            $query = "SELECT ID FROM $wpdb->posts AS posts";
            $query .= " LEFT JOIN $wpdb->postmeta AS postmeta ON postmeta.post_id=posts.ID";
            $query .= " WHERE posts.post_type=%s AND (posts.post_status='publish' OR posts.post_status='draft')";
            $query .= " AND (postmeta.meta_key='_job_reject_interview_list' AND postmeta.meta_value!='')";
            $query .= " GROUP BY posts.ID";

            $job_ids = $wpdb->get_col($wpdb->prepare($query, 'job'));

            return $job_ids;
        }
        
        public function alljobs_apps_export_report() {
            if (isset($_POST['jobsearch_empmnth_allapps_reprts_export'])) {
                check_admin_referer('jobsearch_export_allapsrept');
                
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
                    'meta_key' => 'jobsearch_job_applicants_list',
                    'order' => 'DESC',
                    'orderby' => 'ID',
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
                
                add_filter('posts_where', array($this, 'reject_applics_jobs_where_cond'));
                $jobs_query = new WP_Query($args);
                remove_filter('posts_where', array($this, 'reject_applics_jobs_where_cond'));
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
            $jobsearch_form_fields->select_field($field_params);
            ?>
            <span class="jobsearch-field-loader custom_post_loader_<?php echo absint($rand_num); ?>"></span>
            <?php
        }

        public static function list_job_all_apps($_job_id, $apps_start = 0)
        {
            global $jobsearch_plugin_options;
            $job_applicants_list = get_post_meta($_job_id, 'jobsearch_job_applicants_list', true);
            $job_applicants_list = jobsearch_is_post_ids_array($job_applicants_list, 'candidate');
            arsort($job_applicants_list);

            $employer_id = get_post_meta($_job_id, 'jobsearch_field_job_posted_by', true);
            
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
            
            if (empty($job_applicants_list)) {
                $job_applicants_list = array();
            }

            $viewed_candidates = get_post_meta($_job_id, 'jobsearch_viewed_candidates', true);
            if (empty($viewed_candidates)) {
                $viewed_candidates = array();
            }
            $viewed_candidates = jobsearch_is_post_ids_array($viewed_candidates, 'candidate');

            //
            $apps_offset = 6;
            if ($apps_start > 0) {
                $apps_start = ($apps_start - 1) * ($apps_offset);
            }
            $job_applicants_list = array_slice($job_applicants_list, $apps_start, $apps_offset);

            if (!empty($job_applicants_list)) {
                foreach ($job_applicants_list as $_candidate_id) {
                    $candidate_user_id = jobsearch_get_candidate_user_id($_candidate_id);
                    $user_apply_data = get_user_meta($candidate_user_id, 'jobsearch-user-jobs-applied-list', true);

                    $aply_date_time = '';
                    if (!empty($user_apply_data)) {
                        $user_apply_key = array_search($_job_id, array_column($user_apply_data, 'post_id'));
                        $aply_date_time = isset($user_apply_data[$user_apply_key]['date_time']) ? $user_apply_data[$user_apply_key]['date_time'] : '';
                    }

                    if (absint($candidate_user_id) <= 0) {
                        continue;
                    }
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
                    
                    $candidate_status = get_post_meta($_candidate_id, 'jobsearch_field_candidate_approved', true);
                    ?>
                    <li class="jobsearch-column-12<?php echo ($candidate_status != 'on' ? ' applicant-status-pending' : '') ?>">
                        <div class="jobsearch-applied-jobs-wrap">
                            <script>
                                jQuery(document).on('click', '.jobsearch-modelemail-btn-<?php echo($send_message_form_rand) ?>', function () {
                                    jobsearch_modal_popup_open('JobSearchModalSendEmail<?php echo($send_message_form_rand) ?>');
                                });
                                jQuery(document).on('click', '.jobsearch-modelcvrltr-btn-<?php echo($send_message_form_rand) ?>', function () {
                                    jobsearch_modal_popup_open('JobSearchCandCovershwModal<?php echo($send_message_form_rand) ?>');
                                });
                            </script>
                            <?php
                            $cand_is_pending = false;
                            if ($candidate_status != 'on') {
                                $cand_is_pending = true;
                                echo jobsearch_applicant_cand_profile_notaprov_txt();
                            }
                            ?>
                            <a class="jobsearch-applied-jobs-thumb">
                                <?php echo do_action('jobsearch_export_selection_aplicnts_admin', $_candidate_id, $_job_id) ?>
                                <img src="<?php echo($user_def_avatar_url) ?>" alt="">
                            </a>
                            <div class="jobsearch-applied-jobs-text">
                                <div class="jobsearch-applied-jobs-left">
                                    <?php if ($candidate_jobtitle != '') { ?>
                                        <span> <?php echo apply_filters('jobsearch_cand_jobtitle_indisplay', $candidate_jobtitle, $_candidate_id) ?></span>
                                        <?php
                                    }

                                    if (in_array($_candidate_id, $viewed_candidates)) {
                                        ?>
                                        <small class="profile-view viewed"><?php esc_html_e('(Viewed)', 'wp-jobsearch') ?></small>
                                        <?php
                                    } else {
                                        ?>
                                        <small class="profile-view unviewed"><?php esc_html_e('(Unviewed)', 'wp-jobsearch') ?></small>
                                        <?php
                                    }
                                    echo apply_filters('jobsearch_applicants_list_before_title', '', $_candidate_id, $_job_id);
                                    ?>
                                    <h2>
                                        <a href="<?php echo get_permalink($_candidate_id) ?>"><?php echo get_the_title($_candidate_id) ?></a>
                                        <?php
                                        if ($candidate_age != '') {
                                            ?>
                                            <small><?php echo apply_filters('jobsearch_dash_applicants_age_html', sprintf(esc_html__('(Age: %s years)', 'wp-jobsearch'), $candidate_age)) ?></small>
                                            <?php
                                        }
                                        if ($candidate_phone != '') {
                                            ?>
                                            <small><?php printf(esc_html__('Phone: %s', 'wp-jobsearch'), $candidate_phone) ?></small>
                                            <?php
                                        }
                                        ?>
                                    </h2>
                                    <ul>
                                        <?php
                                        if ($candidate_salary != '') {
                                            ?>
                                            <li>
                                                <i class="fa fa-money"></i> <?php printf(esc_html__('Salary: %s', 'wp-jobsearch'), $candidate_salary) ?>
                                            </li>
                                            <?php
                                        }
                                        if ($candidate_city_title != '') {
                                            ?>
                                            <li><i class="fa fa-map-marker"></i> <?php echo($candidate_city_title) ?>
                                            </li>
                                            <?php
                                        }
                                        if ($candidate_sector != '') {
                                            ?>
                                            <li><i class="jobsearch-icon jobsearch-filter-tool-black-shape"></i>
                                                <a><?php echo($candidate_sector) ?></a></li>
                                            <?php
                                        }
                                        //
                                        ?>
                                    </ul>
                                    <?php
                                    if ($aply_date_time > 0) {
                                        ?>
                                        <ul class="apply-time-mncon">
                                            <li> <?php printf(esc_html__('Applied at: %s', 'wp-jobsearch'), (date_i18n(get_option('date_format'), $aply_date_time) . ' ' . date_i18n(get_option('time_format'), $aply_date_time))) ?></li>
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
                                        echo apply_filters('bckend_all_apps_acts_list_after_download_link', '', $_candidate_id, $_job_id);

                                        ?>
                                        <li>
                                            <div class="candidate-more-acts-con">
                                                <a href="javascript:void(0);"
                                                   class="more-actions"><?php esc_html_e('Actions', 'wp-jobsearch') ?>
                                                    <i class="fa fa-angle-down"></i></a>
                                                <ul>
                                                    <?php
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
                                                    } else if (isset($job_cver_ltrs[$_candidate_id]) && $job_cver_ltrs[$_candidate_id] != '') { ?>
                                                        <li><a href="javascript:void(0);"
                                                               class="jobsearch-modelcvrltr-btn-<?php echo($send_message_form_rand) ?>"><?php esc_html_e('View Cover Letter', 'wp-jobsearch') ?></a>
                                                        </li>
                                                        <?php
                                                    }
                                                    ?>
                                                    <li>
                                                        <a href="javascript:void(0);"
                                                           class="jobsearch-modelemail-btn-<?php echo($send_message_form_rand) ?>"><?php esc_html_e('Email to Candidate', 'wp-jobsearch') ?></a>
                                                        <?php
                                                        $popup_args = array('p_job_id' => $_job_id, 'cand_id' => $_candidate_id, 'p_emp_id' => $employer_id, 'p_masg_rand' => $send_message_form_rand);
                                                        add_action('admin_footer', function () use ($popup_args) {

                                                            extract(shortcode_atts(array(
                                                                'p_job_id' => '',
                                                                'p_emp_id' => '',
                                                                'cand_id' => '',
                                                                'p_masg_rand' => ''
                                                            ), $popup_args));
                                                            ?>
                                                            <div class="jobsearch-modal fade"
                                                                 id="JobSearchModalSendEmail<?php echo($p_masg_rand) ?>">
                                                                <div class="modal-inner-area">
                                                                    &nbsp;
                                                                </div>
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
                                                                                            <?php //jobsearch_terms_and_con_link_txt(); ?>
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
                                                    if (in_array($_candidate_id, $job_reject_int_list)) {
                                                        ?>
                                                        <li>
                                                            <a href="javascript:void(0);"
                                                               class="undoreject-cand-to-list ajax-enable"
                                                               data-jid="<?php echo absint($_job_id); ?>"
                                                               data-cid="<?php echo absint($_candidate_id); ?>"><?php esc_html_e('Undo Reject', 'wp-jobsearch') ?>
                                                                <span class="app-loader"></span></a>
                                                        </li>
                                                        <?php
                                                    } else {
                                                        ?>
                                                        <li>
                                                            <?php
                                                            if (in_array($_candidate_id, $job_short_int_list)) {
                                                                ?>
                                                                <a href="javascript:void(0);"
                                                                   class="shortlist-cand-to-intrview"><?php esc_html_e('Shortlisted', 'wp-jobsearch') ?></a>
                                                                <?php
                                                            } else {
                                                                ?>
                                                                <a href="javascript:void(0);"
                                                                   class="shortlist-cand-to-intrview ajax-enable"
                                                                   data-jid="<?php echo absint($_job_id); ?>"
                                                                   data-cid="<?php echo absint($_candidate_id); ?>"><?php esc_html_e('Shortlist for Interview', 'wp-jobsearch') ?>
                                                                    <span class="app-loader"></span></a>
                                                                <?php
                                                            }
                                                            ?>
                                                        </li>
                                                        <?php
                                                        $args = array(
                                                            'candidate_id' => $_candidate_id,
                                                            'view' => 'list',
                                                        );
                                                        apply_filters('jobsearch_cand_generate_resume_btn', $args);
                                                        ?>
                                                        <li>
                                                            <?php
                                                            if (in_array($_candidate_id, $job_reject_int_list)) {
                                                                ?>
                                                                <a href="javascript:void(0);"
                                                                   class="reject-cand-to-intrview"><?php esc_html_e('Rejected', 'wp-jobsearch') ?></a>
                                                                <?php
                                                            } else {
                                                                ?>
                                                                <a href="javascript:void(0);"
                                                                   class="reject-cand-to-intrview ajax-enable"
                                                                   data-jid="<?php echo absint($_job_id); ?>"
                                                                   data-cid="<?php echo absint($_candidate_id); ?>"><?php esc_html_e('Reject', 'wp-jobsearch') ?>
                                                                    <span class="app-loader"></span></a>
                                                                <?php
                                                            }
                                                            ?>
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
                                        <?php

                                        $args = array(
                                            'candidate_id' => $_candidate_id,
                                            'view' => 'list',
                                            'class' => 'preview-candidate-profile',
                                            'icon' => 'fa fa-file-pdf-o'
                                        );
                                        apply_filters('jobsearch_cand_generate_resume_btn', $args);
                                        ?>

                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>
                    <?php
                    //
                    $popup_args = array(
                        'job_id' => $_job_id,
                        'rand_num' => $send_message_form_rand,
                        'candidate_id' => $_candidate_id,
                    );
                    add_action('admin_footer', function () use ($popup_args) {

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
                    //
                }
            }
        }

        public static function load_wapp_jobs_posts($jobs_posts)
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
                    $job_applicants_list = get_post_meta($_job_id, 'jobsearch_job_applicants_list', true);
                    $job_applicants_list = jobsearch_is_post_ids_array($job_applicants_list, 'candidate');
                    ?>

                    <div class="sjob-aplicants-list sjob-aplicants-<?php echo($_job_id) ?>">
                        <div class="thjob-title">
                            <h2><?php echo get_the_title($_job_id) ?></h2>
                            <div class="total-appcreds-con total-aplicnt-cta-<?php echo($_job_id) ?>">
                                <ul>
                                    <?php echo do_action('jobsearch_export_btns_list_admin', $_job_id, $job_applicants_list) ?>
                                    <li>
                                        <div class="applicnt-count-box tot-apps">
                                            <span><?php esc_html_e('Total Applicants: ', 'wp-jobsearch') ?></span> <?php echo absint($job_applicants_count) ?>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="applicnt-count-box sh-apps">
                                            <span><?php esc_html_e('Shortlisted Applicants: ', 'wp-jobsearch') ?></span> <?php echo absint($job_short_int_list_c) ?>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="applicnt-count-box rej-apps">
                                            <span><?php esc_html_e('Rejected Applicants: ', 'wp-jobsearch') ?></span> <?php echo absint($job_reject_int_list_c) ?>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <?php echo do_action('jobsearch_export_select_all_applicnts_admin', $_job_id, $job_applicants_list) ?>
                        <div class="jobsearch-applied-jobs">
                            <?php if (!empty($job_applicants_list)) { ?>
                                <ul id="job-apps-list<?php echo($_job_id) ?>" class="jobsearch-row">
                                    <?php
                                    self::list_job_all_apps($_job_id);
                                    ?>
                                </ul>
                                <?php
                                if ($job_applicants_count > 6) {
                                    $total_apps_pages = ceil($job_applicants_count / 6);
                                    ?>
                                    <div class="lodmore-jobapps-btnsec">
                                        <a href="javascript:void(0);" class="lodmore-jobapps-btn"
                                           data-jid="<?php echo($_job_id) ?>"
                                           data-tpages="<?php echo($total_apps_pages) ?>"
                                           data-gtopage="2"><?php esc_html_e('Load More Applicants', 'wp-jobsearch') ?></a>
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

        public function load_all_jobs_post_data()
        {
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
                );
            }

            $args = apply_filters('jobsearch_bk_all_applics_queryargs', $args);
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

        public function load_more_apswith_job_apps()
        {
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
            
            $args = apply_filters('jobsearch_bk_all_applics_queryargs', $args);
            $jobs_query = new WP_Query($args);
            $jobs_posts = $jobs_query->posts;

            ob_start();
            self::load_wapp_jobs_posts($jobs_posts);
            $html = ob_get_clean();
            echo json_encode(array('html' => $html));

            wp_die();
        }

        public function load_more_apswith_apps_lis()
        {
            $page_num = absint($_POST['page_num']);
            $_job_id = absint($_POST['_job_id']);


            ob_start();
            self::list_job_all_apps($_job_id, $page_num);
            $html = ob_get_clean();
            echo json_encode(array('html' => $html));

            wp_die();
        }

        public function load_single_apswith_job_inlist()
        {

            $_job_id = absint($_POST['_job_id']);
            $jobs_posts = array($_job_id);
            ob_start();
            self::load_wapp_jobs_posts($jobs_posts);
            $html = ob_get_clean();
            echo json_encode(array('html' => $html));

            wp_die();
        }
        
        public function load_apswith_emps_inlist() {
            $jobs_posts = array(0);
            ob_start();
            self::load_wapp_jobs_posts($jobs_posts);
            $html = ob_get_clean();
            echo json_encode(array('html' => $html));

            wp_die();
        }

        public function alljobs_apps_count_loadboxes() {

            //do_action('jobsearch_internal_applics_totalcounts');
            $get_tcounts = get_option('jobsearch_internal_applics_counts');
            
            if (isset($get_tcounts['applicants'])) {
                $appcounts = $get_tcounts['applicants'];
                $shappcounts = $get_tcounts['shortlisted'];
                $rejappcounts = $get_tcounts['rejected'];
                
                echo json_encode(array('appcounts' => $appcounts, 'shappcounts' => $shappcounts, 'rejappcounts' => $rejappcounts, 'unviewappcounts' => 0));
            } else {
                $appcounts = $shappcounts = $rejappcounts = 0;

                $args = array(
                    'post_type' => 'job',
                    'posts_per_page' => -1,
                    'post_status' => array('publish', 'draft'),
                    'fields' => 'ids',
                    'order' => 'DESC',
                    'orderby' => 'ID',
                    'meta_query' => array(
                        array(
                            'key' => 'jobsearch_job_applicants_list',
                            'compare' => 'EXISTS',
                        ),
                    ),
                );
                $jobs_query = new WP_Query($args);
                $jobs_posts = $jobs_query->posts;

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
                        //
                    }
                }
                
                do_action('jobsearch_internal_applics_totalupdate', $appcounts, $shappcounts, $rejappcounts);

                echo json_encode(array('appcounts' => $appcounts, 'shappcounts' => $shappcounts, 'rejappcounts' => $rejappcounts, 'unviewappcounts' => 0));
            }

            wp_die();
        }

    }

    return new jobsearch_all_applicants_handle();
}
