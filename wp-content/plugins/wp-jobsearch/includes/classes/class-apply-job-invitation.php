<?php

if (!defined('ABSPATH')) {
    die;
}

class Jobsearch_Apply_Job_Invitations {

    // hook things up
    public function __construct() {
        add_action('wp', array($this, 'apply_job_bycand_email'));
        
        add_filter('jobsearch_candetail_atend_basicinfo_html', array($this, 'candetail_invite_btn'), 10, 2);
        
        add_action('wp_ajax_jobsearch_invite_cand_to_apply_byemp', array($this, 'invite_for_apply_call'));
        
        add_action('jobsearch_candash_notific_settins_btns_after', array($this, 'candash_notific_settins_btn'));
        add_filter('jobsearch_user_notifics_list_item_html', array($this, 'user_notifics_list_item_html'), 10, 2);
    }

    public function candetail_invite_btn($html, $candidate_id) {
        global $jobsearch_plugin_options;
        $invite_btn_switch = isset($jobsearch_plugin_options['cand_invite_apply_btn']) ? $jobsearch_plugin_options['cand_invite_apply_btn'] : '';
        
        if ($invite_btn_switch == 'on') {
            $user_id = get_current_user_id();
            $free_cand_invite_allow = isset($jobsearch_plugin_options['free_cand_invite_allow']) ? $jobsearch_plugin_options['free_cand_invite_allow'] : '';
            $can_invite_free = true;
            $ask_for_pkg = false;
            if ($free_cand_invite_allow != 'on') {
                $can_invite_free = false;
                $user_cv_pkg = jobsearch_invites_first_pkg_subscribed($user_id);
                if (!$user_cv_pkg) {
                    $ask_for_pkg = true;
                }
            }
            
            $invite_btn_class = 'jobsearch-invite-candaply';
            if (!is_user_logged_in()) {
                $invite_btn_class = 'jobsearch-open-signin-tab';
            } else {
                $is_employer = jobsearch_user_is_employer($user_id);
                if (!$is_employer) {
                    $invite_btn_class = 'jobsearch-invite-bynonemp';
                } else {
                    if ($ask_for_pkg) {
                        $invite_btn_class = 'jobsearch-invite-bypkgbuy';
                    }
                }
            }
            ob_start();
            ?>
            <div class="candinvite-btn-con">
                <a href="javascript:void(0);" class="jobsearch-candinvite-btn <?php echo ($invite_btn_class) ?>"><?php esc_html_e('Invite', 'wp-jobsearch'); ?></a>
            </div>
            <?php
            $html .= ob_get_clean();
            
            if (!$ask_for_pkg) {
                add_action('wp_footer', function() use ($candidate_id) {
                    if (is_user_logged_in()) {
                        $user_id = get_current_user_id();
                        $is_employer = jobsearch_user_is_employer($user_id);
                        if ($is_employer) {
                            $jobsearch__options = get_option('jobsearch_plugin_options');
                            $emporler_approval = isset($jobsearch__options['job_listwith_emp_aprov']) ? $jobsearch__options['job_listwith_emp_aprov'] : '';
                            $employer_id = jobsearch_get_user_employer_id($user_id);

                            global $jobsearch_shortcode_jobs_frontend;
                            $all_post_ids = array();
                            if (is_object($jobsearch_shortcode_jobs_frontend)) {
                                $all_post_ids = $jobsearch_shortcode_jobs_frontend->job_general_query_filter(array());
                            }

                            $job_per_page = 10;
                            $job_order = 'DESC';
                            $job_orderby = 'ID';
                            $element_filter_arr = array(
                                array(
                                    'key' => 'jobsearch_field_job_posted_by',
                                    'value' => $employer_id,
                                    'compare' => '=',
                                )
                            );
                            $element_filter_arr[] = array(
                                'relation' => 'OR',
                                array(
                                    'key' => 'jobsearch_field_job_filled',
                                    'compare' => 'NOT EXISTS',
                                ),
                                array(
                                    array(
                                        'key' => 'jobsearch_field_job_filled',
                                        'value' => 'on',
                                        'compare' => '!=',
                                    ),
                                ),
                            );

                            if ($emporler_approval != 'off') {
                                $element_filter_arr[] = array(
                                    'key' => 'jobsearch_job_employer_status',
                                    'value' => 'approved',
                                    'compare' => '=',
                                );
                            }

                            $args = array(
                                'posts_per_page' => $job_per_page,
                                'post_type' => 'job',
                                'post_status' => 'publish',
                                'order' => $job_order,
                                'orderby' => $job_orderby,
                                'fields' => 'ids', // only load ids
                                'meta_query' => array(
                                    $element_filter_arr,
                                ),
                            );

                            if (!empty($all_post_ids)) {
                                $args['post__in'] = $all_post_ids;
                            }

                            $jobs_query = new WP_Query($args);

                            $totl_found_jobs = $jobs_query->found_posts;
                            $jobs_posts = $jobs_query->posts;
                            ?>
                            <div class="jobsearch-modal candinvite-modal-popup fade" id="JobSearchModalInviteCandPopup">
                                <div class="modal-inner-area">&nbsp;</div>
                                <div class="modal-content-area">
                                    <div class="modal-box-area">
                                        <div class="jobsearch-modal-title-box">
                                            <h2><?php esc_html_e('Invite to apply job', 'wp-jobsearch'); ?></h2>
                                            <span class="modal-close"><i class="fa fa-times"></i></span>
                                        </div>
                                        <div class="jobsearch-boinvite-main">
                                            <?php
                                            if (!empty($jobs_posts)) {
                                                ?>
                                                <div class="invitefor-job-hdin"><h4><?php esc_html_e('Select job to invite this user', 'wp-jobsearch'); ?></h4></div>
                                                <div class="invitefor-jobmsg-con" style="display: none;"></div>
                                                <div class="invitefor-job-loder" style="display: none;"></div>
                                                <div class="invitefor-jobs-con">
                                                    <?php
                                                    foreach ($jobs_posts as $job_id) {
                                                        ?>
                                                        <label for="invit-job<?php echo ($job_id) ?>">
                                                            <input id="invit-job<?php echo ($job_id) ?>" type="checkbox" name="invite_to_apply_job[]" value="<?php echo ($job_id) ?>">
                                                            <span><?php echo get_the_title($job_id) ?></span>
                                                        </label>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <div class="invite-aplycall-con">
                                                    <a href="javascript:void(0);" class="invite-aplycall-btn" data-id="<?php echo ($candidate_id) ?>"><?php esc_html_e('Invite', 'wp-jobsearch'); ?></a>
                                                </div>
                                                <?php
                                            } else {
                                                $user_dashboard_page = isset($jobsearch__options['user-dashboard-template-page']) ? $jobsearch__options['user-dashboard-template-page'] : '';
                                                $page_id = jobsearch__get_post_id($user_dashboard_page, 'page');
                                                $page_url = jobsearch_wpml_lang_page_permalink($page_id, 'page');
                                                $post_job_url = add_query_arg(array('tab' => 'user-job'), $page_url);
                                                ?>
                                                <div class="nojob-invite-con">
                                                    <strong><?php esc_html_e('No job available to invite!', 'wp-jobsearch'); ?></strong>
                                                    <p><?php esc_html_e('There are currently no jobs available to invite. Start posting jobs to invite.', 'wp-jobsearch'); ?></p>
                                                    <div class="cncle-nojobinvite-btns">
                                                        <a href="<?php echo ($post_job_url) ?>" class="invite-postjob-btn"><?php esc_html_e('Post Job', 'wp-jobsearch'); ?></a>
                                                        <a href="javascript:void(0);" class="invite-cancel-btn"><?php esc_html_e('Cancel', 'wp-jobsearch'); ?></a>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <script>
                                    jQuery(document).on('click', '.jobsearch-invite-candaply', function () {
                                        jobsearch_modal_popup_open('JobSearchModalInviteCandPopup');
                                    });
                                    jQuery(document).on('click', '.invite-cancel-btn', function () {
                                        jQuery('.jobsearch-modal').removeClass('fade-in').addClass('fade');
                                        jQuery('body').removeClass('jobsearch-modal-active');
                                    });

                                    jQuery(document).on('click', '.invite-aplycall-btn', function () {
                                        var _this = jQuery(this);
                                        var cand_id = _this.attr('data-id');
                                        var th_msg_con = _this.parents('.jobsearch-boinvite-main').find('.invitefor-jobmsg-con');
                                        var th_loder = _this.parents('.jobsearch-boinvite-main').find('.invitefor-job-loder');

                                        var job_ids = jQuery('input[name^=invite_to_apply_job]');
                                        var job_ids_arr = [];
                                        job_ids.each(function() {
                                            if (jQuery(this).is(':checked')) {
                                                job_ids_arr.push(jQuery(this).val());
                                            }
                                        });
                                        if (job_ids_arr.length == 0) {
                                            var msg_html = '<div class="alert alert-danger"><i class="fa fa-times"></i> <?php esc_html_e('Please select jobs first.', 'wp-jobsearch'); ?>';
                                            msg_html += '</div>';
                                            th_msg_con.html(msg_html);
                                            th_msg_con.slideDown();
                                            return false;
                                        }
                                        var job_ids_list = job_ids_arr.join();

                                        th_loder.html('<i class="fa fa-refresh fa-spin"></i>');
                                        th_loder.removeAttr('style');
                                        th_msg_con.hide();
                                        var request = jQuery.ajax({
                                            url: '<?php echo admin_url('admin-ajax.php') ?>',
                                            method: "POST",
                                            data: {
                                                job_ids: job_ids_list,
                                                cand_id: cand_id,
                                                action: 'jobsearch_invite_cand_to_apply_byemp',
                                            },
                                            dataType: "json"
                                        });

                                        request.done(function (response) {
                                            if (typeof response.msg !== undefined && response.msg != '') {
                                                if (response.err == '0') {
                                                    var msg_html = '<div class="alert alert-success"><i class="fa fa-check"></i> ' + response.msg;
                                                    msg_html += '</div>';
                                                } else {
                                                    var msg_html = '<div class="alert alert-danger"><i class="fa fa-times"></i> ' + response.msg;
                                                    msg_html += '</div>';
                                                }
                                                th_msg_con.html(msg_html);
                                                th_msg_con.slideDown();
                                            }
                                            th_loder.html('').hide();
                                        });

                                        request.fail(function (jqXHR, textStatus) {
                                            th_loder.html('').hide();
                                        });
                                    });
                                </script>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="jobsearch-modal candinvite-modal-popup fade" id="JobSearchModalInviteCandNonEmp">
                                <div class="modal-inner-area">&nbsp;</div>
                                <div class="modal-content-area">
                                    <div class="modal-box-area">
                                        <div class="jobsearch-modal-title-box">
                                            <h2><?php esc_html_e('Invite to apply job', 'wp-jobsearch'); ?></h2>
                                            <span class="modal-close"><i class="fa fa-times"></i></span>
                                        </div>
                                        <p><?php esc_html_e('You are not an employer. Only an employer can invite a candidate for apply job.', 'wp-jobsearch'); ?></p>
                                    </div>
                                </div>
                                <script>
                                    jQuery(document).on('click', '.jobsearch-invite-bynonemp', function () {
                                        jobsearch_modal_popup_open('JobSearchModalInviteCandNonEmp');
                                    });
                                </script>
                            </div>
                            <?php
                        }
                    }
                }, 35);
            } else {
                add_action('wp_footer', function() {
                    $pop_rand = rand(10000, 99999);
                    ?>
                    <div class="jobsearch-modal jobsearch-typo-wrap fade" id="JobSearchToInviteCandModal<?php echo ($pop_rand) ?>">
                        <div class="modal-inner-area">&nbsp;</div>
                        <div class="modal-content-area">
                            <div class="modal-box-area">
                                <div class="user-shortlist-area">
                                    <div class="jobsearch-modal-title-box">
                                        <h2><?php esc_html_e('You must have to buy a package first.', 'wp-jobsearch') ?></h2>
                                        <span class="modal-close"><i class="fa fa-times"></i></span>
                                    </div>
                                    <?php
                                    self::oninvite_pckge_chose_html();
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                        jQuery('.jobsearch-invite-bypkgbuy').on('click', function() {
                            jobsearch_modal_popup_open('JobSearchToInviteCandModal<?php echo ($pop_rand) ?>');
                        });
                    </script>
                    <?php
                }, 44);
            }
        }
        
        return $html;
    }
    
    private static function oninvite_pckges_list() {
        global $jobsearch_plugin_options;
        $onaply_slectd_pkgs = isset($jobsearch_plugin_options['preselect_oninvitecand_pkgs']) ? $jobsearch_plugin_options['preselect_oninvitecand_pkgs'] : '';
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
                    'value' => array('job', 'featured_jobs', 'emp_allin_one', 'employer_profile', 'invites_cred'),
                    'compare' => 'IN',
                ),
            ),
        );
        if (!empty($onaply_slectd_pkgs)) {
            $args['post__in'] = $onaply_slectd_pkgs;
        }
        $pkgs_query = new WP_Query($args);
        $pkgs_items = $pkgs_query->posts;
        return $pkgs_items;
    }
    
    private static function oninvite_pckge_chose_html() {
        $onsave_pckgs_list = self::oninvite_pckges_list();
        if (!empty($onsave_pckgs_list)) {
            wp_enqueue_script('jobsearch-packages-scripts');
            ?>
            <div class="jobsearch-onaply-priceplan">
                <?php
                foreach ($onsave_pckgs_list as $pkg_id) {
                    $pkg_type = get_post_meta($pkg_id, 'jobsearch_field_charges_type', true);
                    $pkg_price = get_post_meta($pkg_id, 'jobsearch_field_package_price', true);
                    $pkg_recomnded = get_post_meta($pkg_id, 'jobsearch_field_feature_pkg', true);
                    $pkg_exfield_title = get_post_meta($pkg_id, 'jobsearch_field_package_exfield_title', true);
                    $pkg_exfield_status = get_post_meta($pkg_id, 'jobsearch_field_package_exfield_status', true);
                    $package_type = get_post_meta($pkg_id, 'jobsearch_field_package_type', true);

                    $unlimited_pkg = get_post_meta($pkg_id, 'jobsearch_field_unlimited_pkg', true);
                    $pkg_exp_dur = get_post_meta($pkg_id, 'jobsearch_field_package_expiry_time', true);
                    $pkg_exp_dur_unit = get_post_meta($pkg_id, 'jobsearch_field_package_expiry_time_unit', true);

                    $expiry_text = absint($pkg_exp_dur) . ' ' . jobsearch_get_duration_unit_str($pkg_exp_dur_unit);
                    if ($unlimited_pkg == 'on') {
                        $expiry_text = esc_html__('Never Expire', 'wp-jobsearch');
                    }

                    $buy_btn_class = 'jobsearch-subscribe-cv-pkg';
                    if ($package_type == 'emp_allin_one') {
                        $buy_btn_class = 'jobsearch-subs-allinone-pkg';
                    }
                    if ($package_type == 'employer_profile') {
                        $buy_btn_class = 'jobsearch-subsemp-profile-pkg';
                    }
                    ?>
                    <div class="jobsearch-popupplan-wrap<?php echo($pkg_recomnded == 'yes' ? ' jobsearch-recmnded-plan' : '') ?>">
                        <div class="jobsearch-popupplan">
                            <h2><?php echo get_the_title($pkg_id) ?></h2>
                            <?php
                            if (!empty($pkg_exfield_title)) { ?>
                                <ul class="popupplan-features-list">
                                    <?php
                                    if (!empty($pkg_exfield_title)) {
                                        $_exf_counter = 0;
                                        foreach ($pkg_exfield_title as $_exfield_title) {
                                            $_exfield_status = isset($pkg_exfield_status[$_exf_counter]) ? $pkg_exfield_status[$_exf_counter] : '';
                                            ?>
                                            <li<?php echo($_exfield_status == 'active' ? ' class="active"' : '') ?>><i
                                                        class="fa fa-check-square-o"></i><?php echo($_exfield_title) ?></li>
                                            <?php
                                            $_exf_counter++;
                                        }
                                    }
                                    ?>
                                </ul>
                                <?php
                            }
                            ?>
                            <div class="popupplan-pricebtn-con">
                                <div class="jobsearch-poprice-wrp">
                                    <?php
                                    if ($pkg_type == 'paid') {
                                        $ret_price = '';
                                        if (!empty($pkg_price)) {
                                            if (function_exists('wc_price')) {
                                                $ret_price = wc_price($pkg_price);
                                                $ret_price = wp_kses($pkg_price, array());
                                            } else {
                                                $ret_price = preg_replace("/[^0-9,.]+/iu", "", $pkg_price);
                                                $ret_price = number_format($ret_price, 2, ".", ",");
                                            }
                                        }
                                        if (!empty($ret_price)) {
                                            echo '<span class="price-holdr">' . jobsearch_get_currency_symbol() . '' . ($ret_price) . ' / </span>' . '<span class="expiry-holdr">' . $expiry_text . '</span>';
                                        }
                                    } else {
                                        echo '<span class="price-holdr">' . esc_html__('Free', 'wp-jobsearch') . '</span>';
                                    }
                                    ?>
                                </div>
                                <div class="jobsearch-popupplan-btn">
                                    <a href="javascript:void(0);"
                                       class="<?php echo($buy_btn_class) ?>"
                                       data-id="<?php echo($pkg_id) ?>"><?php esc_html_e('Select Plan', 'wp-jobsearch') ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php
        }
    }

    public function invite_for_apply_call() {
        $cand_id = $_POST['cand_id'];
        $job_ids = $_POST['job_ids'];
        
        $job_ids = explode(',', $job_ids);
        
        if (!empty($job_ids)) {
            
            $clean_job_ids = array();
            foreach ($job_ids as $job_id) {
                $job_invites_list = get_post_meta($job_id, 'jobsearch_job_invited_cands_forapply', true);
                $job_invites_list = !empty($job_invites_list) ? $job_invites_list : array();
                if (!in_array($cand_id, $job_invites_list)) {
                    $clean_job_ids[] = $job_id;
                }
                //update_post_meta($job_id, 'jobsearch_job_invited_cands_forapply', '');
            }
            //die;
            if (empty($clean_job_ids)) {
                $json_arr = array('err' => '1', 'msg' => esc_html__('You already invited this user for these jobs.', 'wp-jobsearch'));
                wp_send_json($json_arr);
            } else {
                
                $free_cand_invite_allow = isset($jobsearch_plugin_options['free_cand_invite_allow']) ? $jobsearch_plugin_options['free_cand_invite_allow'] : '';
                if ($free_cand_invite_allow != 'on') {
                    $user_cv_pkg = jobsearch_invites_first_pkg_subscribed($user_id);
                    if (!$user_cv_pkg) {
                        $json_arr = array('err' => '1', 'msg' => esc_html__('Buy a package to invite candidate.', 'wp-jobsearch'));
                        wp_send_json($json_arr);
                    }
                }

                $cand_user_id = get_post_meta($cand_id, 'jobsearch_user_id', true);

                $user_obj = get_user_by('id', $cand_user_id);
                
                foreach ($clean_job_ids as $job_id) {
                    $job_invites_list = get_post_meta($job_id, 'jobsearch_job_invited_cands_forapply', true);
                    $job_invites_list = !empty($job_invites_list) ? $job_invites_list : array();
                    
                    $job_invites_list[] = $cand_id;
                    update_post_meta($job_id, 'jobsearch_job_invited_cands_forapply', $job_invites_list);
                    
                    self::notify_cand_for_invite($job_id, $cand_id);
                }
                do_action('jobsearch_invite_apply_to_candidate', $user_obj, $clean_job_ids, get_current_user_id());
                if (isset($user_cv_pkg) && $user_cv_pkg) {
                    $imp_job_ids = implode(',', $clean_job_ids);
                    do_action('jobsearch_invite_apply_to_cand_add_to_pkg', $imp_job_ids, $user_cv_pkg);
                }
                $json_arr = array('err' => '0', 'msg' => esc_html__('Invited successfully.', 'wp-jobsearch'));
                wp_send_json($json_arr);
            }
        }
        
        $json_arr = array('err' => '1', 'msg' => esc_html__('There is some problem.', 'wp-jobsearch'));
        wp_send_json($json_arr);
    }
    
    public function apply_job_bycand_email() {
        if (is_singular('job') && isset($_GET['apply_act']) && $_GET['apply_act'] == 'invite' && isset($_GET['email']) && $_GET['email'] != '') {
            $job_id = get_the_id();
            
            $user_email = $_GET['email'];
            $user_obj = get_user_by('email', $user_email);
            
            $user_id = isset($user_obj->ID) ? $user_obj->ID : '';
            
            if (jobsearch_user_is_candidate($user_id)) {
                $candidate_id = jobsearch_get_user_candidate_id($user_id);
                
                $job_invites_list = get_post_meta($job_id, 'jobsearch_job_invited_cands_forapply', true);
                $job_invites_list = !empty($job_invites_list) ? $job_invites_list : array();
                
                if (in_array($candidate_id, $job_invites_list)) {
                    $this->jobsearch_job_apply_by_job_id($job_id, $user_id);
                }
            }
            //
        }
    }
    
    public function jobsearch_job_apply_by_job_id($job_id, $user_id = '') {
        
        $candidate_id = jobsearch_get_user_candidate_id($user_id);
        if ($job_id > 0 && $candidate_id > 0) {

            jobsearch_create_user_meta_list($job_id, 'jobsearch-user-jobs-applied-list', $user_id);

            //
            $job_applicants_list = get_post_meta($job_id, 'jobsearch_job_applicants_list', true);
            if ($job_applicants_list != '') {
                $job_applicants_list = explode(',', $job_applicants_list);
                if (!in_array($candidate_id, $job_applicants_list)) {
                    $job_applicants_list[] = $candidate_id;
                }
                $job_applicants_list = implode(',', $job_applicants_list);
            } else {
                $job_applicants_list = $candidate_id;
            }
            update_post_meta($job_id, 'jobsearch_job_applicants_list', $job_applicants_list);

            //
            do_action('jobsearch_job_applying_save_action', $candidate_id, $job_id);

            $user_obj = get_user_by('ID', $user_id);
            do_action('jobsearch_job_applied_to_employer', $user_obj, $job_id);
            do_action('jobsearch_job_applied_to_candidate', $user_obj, $job_id);
        }
    }
    
    public function candash_notific_settins_btn($candidate_id) {
        $notifics_me_inviteforjob = get_post_meta($candidate_id, 'jobsearch_field_notific_inviteforjob', true);
        ?>
        <li class="jobsearch-column-6">
            <?php
            Jobsearch_Dashboard_Notifications::seting_onoff_btn('opt-notific-inviteforjob', 'notific_inviteforjob', $notifics_me_inviteforjob, esc_html__('Notify me for Job apply invitation', 'wp-jobsearch'));
            ?>
        </li>
        <?php
    }
    
    public static function notify_cand_for_invite($job_id, $candidate_id) {
        global $jobsearch_plugin_options;

        $dash_notifics_switch = isset($jobsearch_plugin_options['dash_notifics_switch']) ? $jobsearch_plugin_options['dash_notifics_switch'] : '';

        $add_notifics_for_cands = isset($jobsearch_plugin_options['add_notifics_for_cands']) ? $jobsearch_plugin_options['add_notifics_for_cands'] : '';

        $employer_id = get_post_meta($job_id, 'jobsearch_field_job_posted_by', true);
        if ($dash_notifics_switch == 'on' && $add_notifics_for_cands == 'on') {
            
            $notifics_me_inviteforjob = get_post_meta($candidate_id, 'jobsearch_field_notific_inviteforjob', true);
            if ($notifics_me_inviteforjob == 'yes') {
                $notifics_list = get_post_meta($candidate_id, 'jobsearch_cand_notifics_list', true);;
                $notifics_list = !empty($notifics_list) ? $notifics_list : array();
                //
                $notific_data = array(
                    'unique_id' => uniqid(),
                    'type' => 'invited_for_apply',
                    'time' => current_time('timestamp'),
                    'job_id' => $job_id,
                    'employer_id' => $employer_id,
                    'viewed' => 0,
                );
                $notifics_list[] = $notific_data;
                update_post_meta($candidate_id, 'jobsearch_cand_notifics_list', $notifics_list);
            }
        }
        //
    }
    
    public function user_notifics_list_item_html($html, $notific_item) {
        
        $notific_unique_id = isset($notific_item['unique_id']) ? $notific_item['unique_id'] : '';
        $notific_type = isset($notific_item['type']) ? $notific_item['type'] : '';
        $notific_time = isset($notific_item['time']) ? $notific_item['time'] : '';
        $notific_job_id = isset($notific_item['job_id']) ? $notific_item['job_id'] : '';
        $notific_employer_id = isset($notific_item['employer_id']) ? $notific_item['employer_id'] : '';
        $notific_cand_id = isset($notific_item['candidate_id']) ? $notific_item['candidate_id'] : '';
        $notific_viewed = isset($notific_item['viewed']) ? $notific_item['viewed'] : '';

        if ($notific_type == 'invited_for_apply') {
            $anchor_allowed_tags = array(
                'a' => array(
                    'href' => array(),
                    'title' => array(),
                )
            );
            $html = sprintf(wp_kses(__('You are invited to apply job \'<a href="%s">%s</a>\' by \'<a href="%s">%s</a>\'.', 'wp-jobsearch'), $anchor_allowed_tags), get_permalink($notific_job_id), get_the_title($notific_job_id), get_permalink($notific_employer_id), get_the_title($notific_employer_id));
        }
        
        return $html;
    }

}

return new Jobsearch_Apply_Job_Invitations();

