<?php

/**
 * Login Links Shortcode
 * @return html
 */
add_shortcode('jobsearch_apply_job_form', 'jobsearch_apply_job_form_shortcode');

function jobsearch_apply_job_form_shortcode($atts) {
    global $jobsearch_plugin_options;
    extract(shortcode_atts(array(
        'title' => '',
    ), $atts));
    
    $rand_id = rand(123400, 9999999);
    
    $arg = array();
    
    $job_id = isset($_GET['job_id']) ? $_GET['job_id'] : '';
    
    $btn_txt = "<small>" . esc_html__('Apply for the job', 'wp-jobsearch') . "</small>";
    $arg = array(
        'classes' => 'jobsearch-applyjob-btn jobsearch-job-apply-btn-con',
        'btn_before_label' => $btn_txt,
        'btn_after_label' => esc_html__('Successfully Applied', 'wp-jobsearch'),
        'btn_applied_label' => esc_html__('Applied', 'wp-jobsearch'),
        'before_icon' => '',
        'job_id' => $job_id
    );
    
    $aplybtn_args = isset($_GET['args']) ? $_GET['args'] : '';
    if ($aplybtn_args != '') {
        $aplybtn_args = stripslashes(urldecode($aplybtn_args));
        $aplybtn_args = maybe_unserialize($aplybtn_args);
        if (isset($aplybtn_args['classes'])) {
            $arg = $aplybtn_args;
        }
    }
    
    extract(shortcode_atts(array(
        'classes' => 'jobsearch-applyjob-btn',
        'btn_after_label' => '',
        'btn_before_label' => '',
        'btn_applied_label' => '',
        'before_icon' => '',
        'job_id' => ''
    ), $arg));
    
    wp_enqueue_script('jobsearch-job-application-functions-script');

    ob_start();
    ?>
    <div class="jobsearch-otherpage-applyform">
        <?php
        $job_extrnal_apply_switch_arr = isset($jobsearch_plugin_options['apply-methods']) ? $jobsearch_plugin_options['apply-methods'] : '';
        $without_login_signin_restriction = isset($jobsearch_plugin_options['without-login-apply-restriction']) ? $jobsearch_plugin_options['without-login-apply-restriction'] : '';
        $job_apply_switch = isset($jobsearch_plugin_options['job-apply-switch']) ? $jobsearch_plugin_options['job-apply-switch'] : 'on';

        if (isset($job_apply_switch) && $job_apply_switch != 'on') {
            return $html;
        }

        $job_extrnal_apply_internal_switch = '';
        $job_extrnal_apply_external_switch = '';
        $job_extrnal_apply_email_switch = '';


        if (isset($job_extrnal_apply_switch_arr) && is_array($job_extrnal_apply_switch_arr) && sizeof($job_extrnal_apply_switch_arr) > 0) {
            foreach ($job_extrnal_apply_switch_arr as $apply_switch) {
                if ($apply_switch == 'internal') {
                    $job_extrnal_apply_internal_switch = 'internal';
                }
                if ($apply_switch == 'external') {
                    $job_extrnal_apply_external_switch = 'external';
                }
                if ($apply_switch == 'email') {
                    $job_extrnal_apply_email_switch = 'email';
                }
            }
        }

        $job_aply_type = get_post_meta($job_id, 'jobsearch_field_job_apply_type', true);
        if (empty($job_aply_type)) {
            $job_aply_type = 'internal';
        }

        $job_aply_extrnal_url = get_post_meta($job_id, 'jobsearch_field_job_apply_url', true);

        $apply_without_login = isset($jobsearch_plugin_options['job-apply-without-login']) ? $jobsearch_plugin_options['job-apply-without-login'] : '';

        $multiple_cv_files_allow = isset($jobsearch_plugin_options['multiple_cv_uploads']) ? $jobsearch_plugin_options['multiple_cv_uploads'] : '';

        if ($job_id != '') {
            $classes_str = 'jobsearch-open-signin-tab jobsearch-wredirct-url';
            $multi_cvs = false;
            if (is_user_logged_in()) {
                if (jobsearch_user_is_candidate()) {
                    if ($multiple_cv_files_allow == 'on') {
                        $multi_cvs = true;
                    }
                    $classes_str = 'jobsearch-apply-btn';
                } else {
                    $classes_str = 'jobsearch-other-role-btn jobsearch-applyjob-msg-popup-btn';
                }
            }
            ob_start();
            $jobsearch_applied_list = array();
            $btn_text = $btn_before_label;

            // signin restriction on without login methods
            $internal_signin_switch = false;
            $external_signin_switch = false;
            $email_signin_switch = false;
            if (isset($without_login_signin_restriction) && is_array($without_login_signin_restriction) && sizeof($without_login_signin_restriction) > 0) {
                foreach ($without_login_signin_restriction as $restrict_signin_switch) {
                    if ($restrict_signin_switch == 'internal') {
                        $internal_signin_switch = true;
                    }
                    if ($restrict_signin_switch == 'external') {
                        $external_signin_switch = true;
                    }
                    if ($restrict_signin_switch == 'email') {
                        $email_signin_switch = true;
                    }
                }
            }

            $mubtn_withlogin_switch = false;
            if ($job_aply_type == 'internal' && $internal_signin_switch) {
                $mubtn_withlogin_switch = true;
            } else if ($job_aply_type == 'with_email' && $email_signin_switch) {
                $mubtn_withlogin_switch = true;
            } else if ($job_aply_type == 'external' && $external_signin_switch) {
                $mubtn_withlogin_switch = true;
            }

            if (!is_user_logged_in() && $apply_without_login != 'on' && $mubtn_withlogin_switch === true) {
                $btn_text = apply_filters('jobsearch_loginto_apply_job_btn_text', esc_html__('Login to Apply Job', 'wp-jobsearch'));
            }
            $is_applied = false;
            if (is_user_logged_in()) {
                $finded_result_list = jobsearch_find_index_user_meta_list($job_id, 'jobsearch-user-jobs-applied-list', 'post_id', jobsearch_get_user_id());
                if (is_array($finded_result_list) && !empty($finded_result_list)) {
                    $classes_str = 'jobsearch-applied-btn';
                    $btn_text = $btn_applied_label;
                    $is_applied = true;
                }
            }

            if (!is_user_logged_in()) {
                if ($apply_without_login != 'on' && $mubtn_withlogin_switch === true) {
                    //
                } else {
                    $classes_str = 'jobsearch-nonuser-apply-btn';
                }
            }

            //
            $insta_applied = false;
            if (isset($_GET['jobsearch_apply_instamatch']) && $_GET['jobsearch_apply_instamatch'] == '1') {
                $insta_id = isset($_GET['id']) ? $_GET['id'] : '';
                $insta_ids = explode('|', $insta_id);
                $insta_job_id = isset($insta_ids[0]) ? $insta_ids[0] : '';
                $insta_user_id = isset($insta_ids[1]) ? $insta_ids[1] : '';
                if ($insta_user_id > 0 && $insta_job_id > 0) {
                    $finded_instaresult_list = jobsearch_find_index_user_meta_list($job_id, 'jobsearch_instamatch_job_ids', 'post_id', $insta_user_id);
                    if (!empty($finded_instaresult_list) && is_array($finded_instaresult_list)) {
                        $insta_applied = true;
                    }
                }
            }

            if ($insta_applied) {
                $classes_str = 'jobsearch-applied-btn';
                $btn_text = $btn_applied_label;
                $is_applied = true;
            }

            if ($job_extrnal_apply_email_switch == 'email' && $job_aply_type == 'with_email') {
                if ($apply_without_login == 'off' && !is_user_logged_in() && $email_signin_switch) {
                    //
                } else {
                    $phone_validation_type = isset($jobsearch_plugin_options['intltell_phone_validation']) ? $jobsearch_plugin_options['intltell_phone_validation'] : '';
                    if ($phone_validation_type == 'on') {
                        wp_enqueue_style('jobsearch-intlTelInput');
                        wp_enqueue_script('jobsearch-intlTelInput');
                    }
                    
                    $popup_args = array(
                        'p_job_id' => $job_id,
                        'p_rand_id' => $rand_id,
                        'p_btn_text' => $btn_text,
                        'p_classes' => $classes,
                        'p_classes_str' => $classes_str,
                        'p_btn_after_label' => $btn_after_label,
                    );

                    $phone_validation_type = isset($jobsearch_plugin_options['intltell_phone_validation']) ? $jobsearch_plugin_options['intltell_phone_validation'] : '';

                    $wout_fields_sort = isset($jobsearch_plugin_options['aplywout_login_fields_sort']) ? $jobsearch_plugin_options['aplywout_login_fields_sort'] : '';
                    $wout_fields_sort = isset($wout_fields_sort['fields']) ? $wout_fields_sort['fields'] : '';

                    extract(shortcode_atts(array(
                        'p_job_id' => '',
                        'p_rand_id' => '',
                        'p_btn_text' => '',
                        'p_classes' => '',
                        'p_classes_str' => '',
                        'p_btn_after_label' => '',
                    ), $popup_args));

                    $user_dname = '';
                    $user_demail = '';

                    if (is_user_logged_in()) {
                        $cuser_id = get_current_user_id();
                        $cuser_obj = get_user_by('ID', $cuser_id);
                        $user_dname = $cuser_obj->display_name;
                        $user_demail = $cuser_obj->user_email;
                    }

                    $file_sizes_arr = array(
                        '300' => __('300KB', 'wp-jobsearch'),
                        '500' => __('500KB', 'wp-jobsearch'),
                        '750' => __('750KB', 'wp-jobsearch'),
                        '1024' => __('1Mb', 'wp-jobsearch'),
                        '2048' => __('2Mb', 'wp-jobsearch'),
                        '3072' => __('3Mb', 'wp-jobsearch'),
                        '4096' => __('4Mb', 'wp-jobsearch'),
                        '5120' => __('5Mb', 'wp-jobsearch'),
                        '10120' => __('10Mb', 'wp-jobsearch'),
                        '50120' => __('50Mb', 'wp-jobsearch'),
                        '100120' => __('100Mb', 'wp-jobsearch'),
                        '200120' => __('200Mb', 'wp-jobsearch'),
                        '300120' => __('300Mb', 'wp-jobsearch'),
                        '500120' => __('500Mb', 'wp-jobsearch'),
                        '1000120' => __('1Gb', 'wp-jobsearch'),
                    );
                    $cvfile_size = '5120';
                    $cvfile_size_str = __('5 Mb', 'wp-jobsearch');
                    $cand_cv_file_size = isset($jobsearch_plugin_options['cand_cv_file_size']) ? $jobsearch_plugin_options['cand_cv_file_size'] : '';
                    if (isset($file_sizes_arr[$cand_cv_file_size])) {
                        $cvfile_size = $cand_cv_file_size;
                        $cvfile_size_str = $file_sizes_arr[$cand_cv_file_size];
                    }
                    $filesize_act = ceil($cvfile_size / 1024);

                    $cand_files_types = isset($jobsearch_plugin_options['cand_cv_types']) ? $jobsearch_plugin_options['cand_cv_types'] : '';

                    if (empty($cand_files_types)) {
                        $cand_files_types = array(
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/pdf',
                        );
                    }
                    $sutable_files_arr = array();
                    $file_typs_comarr = array(
                        'text/plain' => __('text', 'wp-jobsearch'),
                        'image/jpeg' => __('jpeg', 'wp-jobsearch'),
                        'image/png' => __('png', 'wp-jobsearch'),
                        'application/msword' => __('doc', 'wp-jobsearch'),
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => __('docx', 'wp-jobsearch'),
                        'application/vnd.ms-excel' => __('xls', 'wp-jobsearch'),
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => __('xlsx', 'wp-jobsearch'),
                        'application/pdf' => __('pdf', 'wp-jobsearch'),
                    );
                    foreach ($file_typs_comarr as $file_typ_key => $file_typ_comar) {
                        if (in_array($file_typ_key, $cand_files_types)) {
                            $sutable_files_arr[] = '.' . $file_typ_comar;
                        }
                    }
                    $sutable_files_str = implode(', ', $sutable_files_arr);

                    ob_start();
                    ?>
                    <div class="jobsearch-applyform-area">
                        <?php
                        if (isset($_COOKIE["jobsearch_email_apply_job_" . $p_job_id])) { ?>
                            <p><?php esc_html_e('You have already applied for this job.', 'wp-jobsearch') ?></p>
                        <?php } else { ?>
                            <form autocomplete="off" id="apply-withemail-<?php echo($p_rand_id) ?>">
                                <?php
                                $from_args = array(
                                    'rand_num' => $p_rand_id,
                                    'job_id' => $p_job_id
                                );
                                do_action('jobsearch_apply_job_withemail_in_formtag_html', $from_args);

                                $candidate_id = 0;
                                $user_fname = '';
                                $user_lname = '';
                                $user_phone = $user_justphone = $job_title = $_candidate_salary = '';
                                if (is_user_logged_in()) {
                                    $curr_user_obj = wp_get_current_user();
                                    $curruser_id = get_current_user_id();
                                    $user_fname = isset($curr_user_obj->first_name) ? $curr_user_obj->first_name : '';
                                    $user_lname = isset($curr_user_obj->last_name) ? $curr_user_obj->last_name : '';

                                    $candidate_id = jobsearch_get_user_candidate_id($curruser_id);

                                    $user_phone = get_post_meta($candidate_id, 'jobsearch_field_user_phone', true);
                                    $user_justphone = get_post_meta($candidate_id, 'jobsearch_field_user_justphone', true);

                                    $job_title = get_post_meta($candidate_id, 'jobsearch_field_candidate_jobtitle', true);
                                    $_candidate_salary = get_post_meta($candidate_id, 'jobsearch_field_candidate_salary', true);
                                }
                                ?>
                                <div class="<?php echo apply_filters('jobsearch_apply_job_withemail_inform_class', 'jobsearch-apply-withemail-con jobsearch-user-form jobsearch-user-form-coltwo', $from_args) ?>"<?php echo apply_filters('jobsearch_apply_job_withemail_inform_tag_exattrs', '', $from_args) ?>>
                                    <ul class="apply-fields-list">
                                        <?php
                                        ob_start();
                                        if (isset($wout_fields_sort['name'])) {
                                            foreach ($wout_fields_sort as $field_sort_key => $field_sort_val) {
                                                $field_name_swich_key = 'aplywout_log_f' . $field_sort_key . '_swch';
                                                $field_name_swich = isset($jobsearch_plugin_options[$field_name_swich_key]) ? $jobsearch_plugin_options[$field_name_swich_key] : '';
                                                if ($field_sort_key == 'name' && ($field_name_swich == 'on' || $field_name_swich == 'on_req')) {
                                                    ?>
                                                    <li>
                                                        <label><?php esc_html_e('First Name:', 'wp-jobsearch') ?><?php echo($field_name_swich == 'on_req' ? ' *' : '') ?></label>
                                                        <input class="<?php echo($field_name_swich == 'on_req' ? 'required-apply-field' : 'required') ?>"
                                                               name="user_fullname" type="text"
                                                               placeholder="<?php esc_html_e('First Name', 'wp-jobsearch') ?>" value="<?php echo ($user_fname) ?>">
                                                    </li>
                                                    <li>
                                                        <label><?php esc_html_e('Last Name:', 'wp-jobsearch') ?><?php echo($field_name_swich == 'on_req' ? ' *' : '') ?></label>
                                                        <input class="<?php echo($field_name_swich == 'on_req' ? 'required-apply-field' : 'required') ?>"
                                                               name="user_surname" type="text"
                                                               placeholder="<?php esc_html_e('Last Name', 'wp-jobsearch') ?>" value="<?php echo ($user_lname) ?>">
                                                    </li>
                                                    <?php
                                                } else if ($field_sort_key == 'email') {
                                                    $logedusr_email = '';
                                                    if (is_user_logged_in()) {
                                                        $loged_user_obj = wp_get_current_user();
                                                        $logedusr_email = isset($loged_user_obj->user_email) ? $loged_user_obj->user_email : '';
                                                    }
                                                    ?>
                                                    <li>
                                                        <label><?php esc_html_e('Email: *', 'wp-jobsearch') ?></label>
                                                        <input class="required" name="user_email"
                                                               type="text" <?php if ($logedusr_email != '') { ?> value="<?php echo($logedusr_email) ?>" readonly<?php } ?>
                                                               placeholder="<?php esc_html_e('Email Address', 'wp-jobsearch') ?>">
                                                    </li>
                                                <?php } else if ($field_sort_key == 'phone' && ($field_name_swich == 'on' || $field_name_swich == 'on_req')) {
                                                    ?>
                                                    <li>
                                                        <label><?php esc_html_e('Phone:', 'wp-jobsearch') ?><?php echo($field_name_swich == 'on_req' ? ' *' : '') ?></label>
                                                        <?php
                                                        if ($phone_validation_type == 'on') {
                                                            $rand_numb = rand(10000000, 99999999);
                                                            $phone_field_req = false;
                                                            if ($field_name_swich == 'on_req') {
                                                                $phone_field_req = true;
                                                            }
                                                            jobsearch_phonenum_itltell_input('user_phone', $rand_numb, $user_justphone, array('is_required' => $phone_field_req));
                                                        } else {
                                                            ?>
                                                            <input class="<?php echo($field_name_swich == 'on_req' ? 'required-apply-field' : 'required') ?>"
                                                                   name="user_phone" type="tel"
                                                                   placeholder="<?php esc_html_e('Phone Number', 'wp-jobsearch') ?>" value="<?php echo ($user_phone) ?>">
                                                            <?php
                                                        }
                                                        ?>
                                                    </li>
                                                    <?php
                                                } else if ($field_sort_key == 'current_jobtitle' && ($field_name_swich == 'on' || $field_name_swich == 'on_req')) {
                                                    ?>
                                                    <li>
                                                        <label><?php esc_html_e('Current Job Title:', 'wp-jobsearch') ?><?php echo($field_name_swich == 'on_req' ? ' *' : '') ?></label>
                                                        <input class="<?php echo($field_name_swich == 'on_req' ? 'required-apply-field' : 'required') ?>"
                                                               name="user_job_title" type="text"
                                                               placeholder="<?php esc_html_e('Current Job Title', 'wp-jobsearch') ?>" value="<?php echo apply_filters('jobsearch_cand_jobtitle_indisplay', $job_title, $candidate_id) ?>">
                                                    </li>
                                                    <?php
                                                } else if ($field_sort_key == 'current_salary' && ($field_name_swich == 'on' || $field_name_swich == 'on_req')) {
                                                    ?>
                                                    <li>
                                                        <label><?php esc_html_e('Current Salary:', 'wp-jobsearch') ?><?php echo($field_name_swich == 'on_req' ? ' *' : '') ?></label>
                                                        <input class="<?php echo($field_name_swich == 'on_req' ? 'required-apply-field' : 'required') ?>"
                                                               name="user_salary" type="text"
                                                               placeholder="<?php esc_html_e('Current Salary', 'wp-jobsearch') ?>" value="<?php echo ($_candidate_salary) ?>">
                                                    </li>
                                                    <?php
                                                } else if ($field_sort_key == 'custom_fields' && $field_name_swich == 'on') {
                                                    do_action('jobsearch_form_custom_fields_load', $candidate_id, 'candidate');
                                                } else if ($field_sort_key == 'cv_attach' && ($field_name_swich == 'on' || $field_name_swich == 'on_req')) {
                                                    $have_resumes = false;
                                                    if (is_user_logged_in()) {
                                                        $user_id = get_current_user_id();
                                                        $candidate_id = jobsearch_get_user_candidate_id($user_id);
                                                        $multiple_cv_files_allow = isset($jobsearch_plugin_options['multiple_cv_uploads']) ? $jobsearch_plugin_options['multiple_cv_uploads'] : '';
                                                        if ($multiple_cv_files_allow == 'on') {
                                                            $ca_at_cv_files = get_post_meta($candidate_id, 'candidate_cv_files', true);

                                                            if (!empty($ca_at_cv_files)) {
                                                                $cv_files_count = count($ca_at_cv_files);

                                                                ob_start();
                                                                $cvfile_count = 1;
                                                                foreach ($ca_at_cv_files as $cv_file_key => $cv_file_val) {
                                                                    $file_attach_id = isset($cv_file_val['file_id']) ? $cv_file_val['file_id'] : '';
                                                                    $file_url = isset($cv_file_val['file_url']) ? $cv_file_val['file_url'] : '';
                                                                    $filename = isset($cv_file_val['file_name']) ? $cv_file_val['file_name'] : '';
                                                                    $filetype = isset($cv_file_val['mime_type']) ? $cv_file_val['mime_type'] : '';
                                                                    $fileuplod_time = isset($cv_file_val['time']) ? $cv_file_val['time'] : '';
                                                                    if (is_numeric($file_attach_id) && get_post_type($file_attach_id) == 'attachment') {
                                                                        $attach_mime = isset($attach_post->post_mime_type) ? $attach_post->post_mime_type : '';
                                                                        $filetype = array('type' => $attach_mime);
                                                                    }

                                                                    $cv_file_title = $filename;

                                                                    $attach_date = $fileuplod_time;
                                                                    $attach_mime = isset($filetype['type']) ? $filetype['type'] : '';

                                                                    if ($cvfile_count == 1) {
                                                                        $cv_primary = 'yes';
                                                                    } else {
                                                                        $cv_primary = isset($cv_file_val['primary']) ? $cv_file_val['primary'] : '';
                                                                    }

                                                                    if (is_numeric($file_attach_id) && get_post_type($file_attach_id) == 'attachment') {
                                                                        $cv_file_title = get_the_title($file_attach_id);
                                                                        $attach_post = get_post($file_attach_id);
                                                                        $file_path = get_attached_file($file_attach_id);
                                                                        $filename = basename($file_path);

                                                                        $attach_date = isset($attach_post->post_date) ? $attach_post->post_date : '';
                                                                        $attach_date = strtotime($attach_date);
                                                                        $attach_mime = isset($attach_post->post_mime_type) ? $attach_post->post_mime_type : '';
                                                                    }

                                                                    if ($attach_mime == 'application/pdf') {
                                                                        $attach_icon = 'fa fa-file-pdf-o';
                                                                    } else if ($attach_mime == 'application/msword' || $attach_mime == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                                                                        $attach_icon = 'fa fa-file-word-o';
                                                                    } else if ($attach_mime == 'text/plain') {
                                                                        $attach_icon = 'fa fa-file-text-o';
                                                                    } else if ($attach_mime == 'application/vnd.ms-excel' || $attach_mime == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                                                                        $attach_icon = 'fa fa-file-excel-o';
                                                                    } else if ($attach_mime == 'image/jpeg' || $attach_mime == 'image/png') {
                                                                        $attach_icon = 'fa fa-file-image-o';
                                                                    } else {
                                                                        $attach_icon = 'fa fa-file-word-o';
                                                                    }
                                                                    if (!empty($filetype)) {
                                                                        $have_resumes = true;
                                                                        ?>
                                                                        <li<?php echo($cv_primary == 'yes' ? ' class="active"' : '') ?>>
                                                                            <i class="<?php echo($attach_icon) ?>"></i>
                                                                            <label for="cv_file_<?php echo($file_attach_id) ?>">
                                                                                <input id="cv_file_<?php echo($file_attach_id) ?>"
                                                                                       type="radio" class="cv_file_item"
                                                                                       name="ucv_file_item" <?php echo($cv_primary == 'yes' ? 'checked="checked"' : '') ?>
                                                                                       value="<?php echo($file_attach_id) ?>">
                                                                                <?php echo(strlen($cv_file_title) > 40 ? substr($cv_file_title, 0, 40) . '...' : $cv_file_title) ?>
                                                                                <?php
                                                                                if ($attach_date != '') {
                                                                                    ?>
                                                                                    <span class="upload-datetime"><i
                                                                                                class="fa fa-calendar"></i> <?php echo date_i18n(get_option('date_format'), ($attach_date)) . ' ' . date_i18n(get_option('time_format'), ($attach_date)) ?></span>
                                                                                    <?php
                                                                                }
                                                                                ?>
                                                                            </label>
                                                                        </li>
                                                                        <?php
                                                                    }
                                                                    $cvfile_count++;
                                                                }
                                                                $resumes_list_html = ob_get_clean();
                                                                ?>
                                                                <li class="jobsearch-user-form-coltwo-full jobsearch-applyjob-cvslist jobsearch-apply-withcvs"<?php echo ($have_resumes ? '' : ' style="display: none;"') ?>>
                                                                    <h2><?php esc_html_e('Select CV', 'wp-jobsearch') ?></h2>
                                                                    <ul class="user-cvs-list">
                                                                        <?php
                                                                        echo ($resumes_list_html);
                                                                        ?>
                                                                    </ul>
                                                                </li>
                                                                <?php
                                                            }
                                                        } else {
                                                            $candidate_cv_file = get_post_meta($candidate_id, 'candidate_cv_file', true);
                                                            if (!empty($candidate_cv_file)) {
                                                                $filename = isset($candidate_cv_file['file_name']) ? $candidate_cv_file['file_name'] : '';
                                                                $filetype = isset($candidate_cv_file['mime_type']) ? $candidate_cv_file['mime_type'] : '';
                                                                $fileuplod_time = isset($candidate_cv_file['time']) ? $candidate_cv_file['time'] : '';
                                                                $file_attach_id = $file_uniqid = isset($candidate_cv_file['file_id']) ? $candidate_cv_file['file_id'] : '';

                                                                $cv_file_title = $filename;

                                                                $attach_date = $fileuplod_time;
                                                                $attach_mime = isset($filetype['type']) ? $filetype['type'] : '';

                                                                if (is_numeric($file_attach_id) && get_post_type($file_attach_id) == 'attachment') {
                                                                    $cv_file_title = get_the_title($file_attach_id);
                                                                    $attach_post = get_post($file_attach_id);
                                                                    $file_path = get_attached_file($file_attach_id);
                                                                    $filename = basename($file_path);

                                                                    $attach_date = isset($attach_post->post_date) ? $attach_post->post_date : '';
                                                                    $attach_date = strtotime($attach_date);
                                                                    $attach_mime = isset($attach_post->post_mime_type) ? $attach_post->post_mime_type : '';
                                                                }

                                                                if ($attach_mime == 'application/pdf') {
                                                                    $attach_icon = 'fa fa-file-pdf-o';
                                                                } else if ($attach_mime == 'application/msword' || $attach_mime == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                                                                    $attach_icon = 'fa fa-file-word-o';
                                                                } else if ($attach_mime == 'text/plain') {
                                                                    $attach_icon = 'fa fa-file-text-o';
                                                                } else if ($attach_mime == 'application/vnd.ms-excel' || $attach_mime == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                                                                    $attach_icon = 'fa fa-file-excel-o';
                                                                } else if ($attach_mime == 'image/jpeg' || $attach_mime == 'image/png') {
                                                                    $attach_icon = 'fa fa-file-image-o';
                                                                } else {
                                                                    $attach_icon = 'fa fa-file-word-o';
                                                                }
                                                                ob_start();
                                                                if (!empty($filetype)) {
                                                                    $have_resumes = true;
                                                                    ?>
                                                                    <li>
                                                                        <i class="<?php echo($attach_icon) ?>"></i>
                                                                        <label for="cv_file_<?php echo($file_attach_id) ?>">
                                                                            <input id="cv_file_<?php echo($file_attach_id) ?>"
                                                                                   type="radio" class="cv_file_item"
                                                                                   name="ucv_file_item" checked="checked" 
                                                                                   value="<?php echo($file_attach_id) ?>">
                                                                            <?php echo(strlen($cv_file_title) > 40 ? substr($cv_file_title, 0, 40) . '...' : $cv_file_title) ?>
                                                                            <?php
                                                                            if ($attach_date != '') {
                                                                                ?>
                                                                                <span class="upload-datetime"><i
                                                                                            class="fa fa-calendar"></i> <?php echo date_i18n(get_option('date_format'), ($attach_date)) . ' ' . date_i18n(get_option('time_format'), ($attach_date)) ?></span>
                                                                                <?php
                                                                            }
                                                                            ?>
                                                                        </label>
                                                                    </li>
                                                                    <?php
                                                                }
                                                                $resumes_list_html = ob_get_clean();
                                                                ?>
                                                                <li class="jobsearch-user-form-coltwo-full jobsearch-applyjob-cvslist jobsearch-apply-withcvs"<?php echo ($have_resumes ? '' : ' style="display: none;"') ?>>
                                                                    <ul class="user-cvs-list">
                                                                        <?php
                                                                        echo ($resumes_list_html);
                                                                        ?>
                                                                    </ul>
                                                                </li>
                                                                <?php
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                    <li class="jobsearch-user-form-coltwo-full">
                                                        <div id="jobsearch-upload-cv-main" class="jobsearch-upload-cv jobsearch-applyjob-upload-cv">
                                                            <label><?php esc_html_e('Resume', 'wp-jobsearch') ?><?php echo ($field_name_swich == 'on_req' && !$have_resumes ? ' *' : '') ?></label>
                                                            <div class="jobsearch-drpzon-con jobsearch-drag-dropcustom">
                                                                <div id="cvFilesDropzone"
                                                                     class="dropzone"
                                                                     ondragover="jobsearch_dragover_evnt(event)"
                                                                     ondragleave="jobsearch_leavedrop_evnt(event)"
                                                                     ondrop="jobsearch_ondrop_evnt(event)">
                                                                    <input type="file"
                                                                           id="cand_cv_filefield"
                                                                           class="jobsearch-upload-btn <?php echo ($field_name_swich == 'on_req' && !$have_resumes ? 'cv_is_req' : '') ?>"
                                                                           name="cuser_cv_file"
                                                                           onchange="jobsearchFileContainerChangeFile(event)">
                                                                    <div class="fileContainerFileName"
                                                                         ondrop="jobsearch_ondrop_evnt(event)"
                                                                         id="fileNameContainer">
                                                                        <div class="dz-message jobsearch-dropzone-template">
                                                                            <span class="upload-icon-con"><i
                                                                                        class="jobsearch-icon jobsearch-upload"></i></span>
                                                                            <strong><?php esc_html_e('Drop a resume file or click to upload.', 'wp-jobsearch') ?></strong>
                                                                            <div class="upload-inffo"><?php printf(__('To upload file size is <span>(Max %s)</span> <span class="uplod-info-and">and</span> allowed file types are <span>(%s)</span>', 'wp-jobsearch'), $cvfile_size_str, $sutable_files_str) ?></div>
                                                                            <div class="upload-or-con">
                                                                                <span><?php esc_html_e('or', 'wp-jobsearch') ?></span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <a class="jobsearch-drpzon-btn"><i
                                                                                class="jobsearch-icon jobsearch-arrows-2"></i> <?php esc_html_e('Upload Resume', 'wp-jobsearch') ?>
                                                                    </a>
                                                                </div>
                                                                <script type="text/javascript">
                                                                    jQuery('#cvFilesDropzone').find('input[name=cuser_cv_file]').css({
                                                                        position: 'absolute',
                                                                        width: '100%',
                                                                        height: '100%',
                                                                        top: '0',
                                                                        left: '0',
                                                                        opacity: '0',
                                                                        'z-index': '9',
                                                                    });

                                                                    function jobsearchFileContainerChangeFile(e) {
                                                                        var the_show_msg = '<?php esc_html_e('No file has been selected', 'wp-jobsearch') ?>';
                                                                        if (e.target.files.length > 0) {
                                                                            var slected_file_name = e.target.files[0].name;
                                                                            the_show_msg = '<?php esc_html_e('The file', 'wp-jobsearch') ?> "' + slected_file_name + '" <?php esc_html_e('has been selected', 'wp-jobsearch') ?>';
                                                                        }
                                                                        document.getElementById('cvFilesDropzone').classList.remove('fileContainerDragOver');
                                                                        try {
                                                                            droppedFiles = document.getElementById('cand_cv_filefield').files;
                                                                            document.getElementById('fileNameContainer').textContent = the_show_msg;
                                                                        } catch (error) {
                                                                            ;
                                                                        }
                                                                        try {
                                                                            aName = document.getElementById('cand_cv_filefield').value;
                                                                            if (aName !== '') {
                                                                                document.getElementById('fileNameContainer').textContent = the_show_msg;
                                                                            }
                                                                        } catch (error) {
                                                                            ;
                                                                        }
                                                                    }

                                                                    function jobsearch_ondrop_evnt(e) {
                                                                        var the_show_msg = '<?php esc_html_e('No file has been selected', 'wp-jobsearch') ?>';
                                                                        if (e.target.files.length > 0) {
                                                                            var slected_file_name = e.target.files[0].name;
                                                                            the_show_msg = '<?php esc_html_e('The file', 'wp-jobsearch') ?> "' + slected_file_name + '" <?php esc_html_e('has been selected', 'wp-jobsearch') ?>';
                                                                        }
                                                                        document.getElementById('cvFilesDropzone').classList.remove('fileContainerDragOver');
                                                                        try {
                                                                            droppedFiles = e.dataTransfer.files;
                                                                            document.getElementById('fileNameContainer').textContent = the_show_msg;
                                                                        } catch (error) {
                                                                            ;
                                                                        }
                                                                    }

                                                                    function jobsearch_dragover_evnt(e) {
                                                                        document.getElementById('cvFilesDropzone').classList.add('fileContainerDragOver');
                                                                        e.preventDefault();
                                                                        e.stopPropagation();
                                                                    }

                                                                    function jobsearch_leavedrop_evnt(e) {
                                                                        document.getElementById('cvFilesDropzone').classList.remove('fileContainerDragOver');
                                                                    }
                                                                </script>
                                                            </div>
                                                        </div>
                                                    </li>
                                                    <?php
                                                }
                                            }
                                            $cand_resm_coverletr = isset($jobsearch_plugin_options['cand_resm_cover_letr']) ? $jobsearch_plugin_options['cand_resm_cover_letr'] : '';
                                            if ($cand_resm_coverletr == 'on') {
                                                ?>
                                                <li class="form-textarea jobsearch-user-form-coltwo-full">
                                                    <label><?php esc_html_e('Cover Letter', 'wp-jobsearch') ?>:</label>
                                                    <?php
                                                    jobsearch_in_aplyjob_uplodin_withoutlog_cover_html();
                                                    ?>
                                                </li>
                                                <?php
                                            }
                                            ?>
                                            <li class="form-textarea jobsearch-user-form-coltwo-full">
                                                <label><?php esc_html_e('Message', 'wp-jobsearch') ?>
                                                    :</label>
                                                <textarea name="user_msg" placeholder="<?php esc_html_e('Type your Message', 'wp-jobsearch') ?>"></textarea>
                                            </li>
                                            <?php
                                        }
                                        $cv_html = ob_get_clean();
                                        echo apply_filters('jobsearch_aply_with_cv_form_cv_field', $cv_html, $p_job_id, $p_rand_id);
                                        
                                        $captcha_switch = isset($jobsearch_plugin_options['captcha_switch']) ? $jobsearch_plugin_options['captcha_switch'] : '';
                                        $jobsearch_sitekey = isset($jobsearch_plugin_options['captcha_sitekey']) ? $jobsearch_plugin_options['captcha_sitekey'] : '';
                                        if ($captcha_switch == 'on' && !is_user_logged_in()) {
                                            wp_enqueue_script('jobsearch_google_recaptcha');
                                            ?>
                                            <li class="jobsearch-user-form-coltwo-full">
                                                <script type="text/javascript">
                                                    var recaptcha_aply;
                                                    var jobsearch_multicap = function () {
                                                        //Render the recaptcha_aply on the element with ID "recaptcha_aply"
                                                        recaptcha_aply = grecaptcha.render('recaptcha_aply', {
                                                            'sitekey': '<?php echo ($jobsearch_sitekey); ?>', //Replace this with your Site key
                                                            'theme': 'light'
                                                        });
                                                    };
                                                    jQuery(document).ready(function () {
                                                        jQuery('.recaptcha-reload-a').click();
                                                    });
                                                </script>
                                                <div class="recaptcha-reload" id="recaptcha_aply_div">
                                                    <?php echo jobsearch_recaptcha('recaptcha_aply'); ?>
                                                </div>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                        <li class="jobsearch-user-form-coltwo-full">
                                            <input type="hidden" name="job_id"
                                                   value="<?php echo($p_job_id) ?>">
                                            <input type="hidden" name="action"
                                                   value="jobsearch_applying_job_with_email">
                                            <?php
                                            jobsearch_terms_and_con_link_txt();
                                            //
                                            ob_start();
                                            ?>
                                            <div class="terms-priv-chek-con">
                                                <p><input type="checkbox"
                                                          name="email_commun_check"> <?php esc_html_e('You accept email communication.', 'wp-jobsearch') ?>
                                                </p>
                                            </div>
                                            <?php
                                            $accpt_html = ob_get_clean();
                                            echo apply_filters('jobsearch_jobaply_byemail_comuni_chkhtml', $accpt_html);
                                            ?>
                                            <a href="javascript:void(0);"
                                               class="<?php echo esc_html($p_classes); ?> jobsearch-applyin-withemail"
                                               data-randid="<?php echo absint($p_rand_id); ?>"
                                               data-jobid="<?php echo absint($p_job_id); ?>"
                                               data-btnafterlabel="<?php echo esc_html($p_btn_after_label) ?>"
                                               data-btnbeforelabel="<?php echo wp_kses($p_btn_text,[]) ?>"><?php echo ($p_btn_text) ?></a>
                                        </li>
                                    </ul>
                                    <div class="apply-job-form-msg"></div>
                                    <div class="apply-job-loader"></div>
                                </div>
                            </form>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                    $popupp_hmtl = ob_get_clean();
                    echo apply_filters('jobsearch_applyjob_withemail_popup_html', $popupp_hmtl, $popup_args);
                }
            } else if ($job_extrnal_apply_internal_switch == 'internal' && $job_aply_type == 'internal') {

                $this_wredirct_url = jobsearch_server_protocol() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                if ($apply_without_login == 'off' && !is_user_logged_in() && $internal_signin_switch) {
                    //
                } else {
                    if ($multi_cvs === true) {
                        wp_enqueue_script('dropzone');

                        $max_cvs_allow = isset($jobsearch_plugin_options['max_cvs_allow']) && absint($jobsearch_plugin_options['max_cvs_allow']) > 0 ? absint($jobsearch_plugin_options['max_cvs_allow']) : 5;
                        $popup_args = array(
                            'p_job_id' => $job_id,
                            'p_rand_id' => $rand_id,
                            'p_btn_text' => $btn_text,
                            'p_classes' => $classes,
                            'p_classes_str' => $classes_str,
                            'p_btn_after_label' => $btn_after_label,
                            'max_cvs_allow' => $max_cvs_allow,
                        );

                        $user_id = get_current_user_id();

                        $candidate_id = jobsearch_get_user_candidate_id($user_id);

                        extract(shortcode_atts(array(
                            'p_job_id' => '',
                            'p_rand_id' => '',
                            'p_btn_text' => '',
                            'p_classes' => '',
                            'p_classes_str' => '',
                            'p_btn_after_label' => '',
                            'max_cvs_allow' => '',
                        ), $popup_args));

                        //
                        $file_sizes_arr = array(
                            '300' => __('300KB', 'wp-jobsearch'),
                            '500' => __('500KB', 'wp-jobsearch'),
                            '750' => __('750KB', 'wp-jobsearch'),
                            '1024' => __('1Mb', 'wp-jobsearch'),
                            '2048' => __('2Mb', 'wp-jobsearch'),
                            '3072' => __('3Mb', 'wp-jobsearch'),
                            '4096' => __('4Mb', 'wp-jobsearch'),
                            '5120' => __('5Mb', 'wp-jobsearch'),
                            '10120' => __('10Mb', 'wp-jobsearch'),
                            '50120' => __('50Mb', 'wp-jobsearch'),
                            '100120' => __('100Mb', 'wp-jobsearch'),
                            '200120' => __('200Mb', 'wp-jobsearch'),
                            '300120' => __('300Mb', 'wp-jobsearch'),
                            '500120' => __('500Mb', 'wp-jobsearch'),
                            '1000120' => __('1Gb', 'wp-jobsearch'),
                        );
                        $cvfile_size = '5120';
                        $cvfile_size_str = __('5 Mb', 'wp-jobsearch');
                        $cand_cv_file_size = isset($jobsearch_plugin_options['cand_cv_file_size']) ? $jobsearch_plugin_options['cand_cv_file_size'] : '';
                        if (isset($file_sizes_arr[$cand_cv_file_size])) {
                            $cvfile_size = $cand_cv_file_size;
                            $cvfile_size_str = $file_sizes_arr[$cand_cv_file_size];
                        }
                        $filesize_act = ceil($cvfile_size / 1024);

                        $cand_files_types = isset($jobsearch_plugin_options['cand_cv_types']) ? $jobsearch_plugin_options['cand_cv_types'] : '';

                        if (empty($cand_files_types)) {
                            $cand_files_types = array(
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/pdf',
                            );
                        }
                        $sutable_files_arr = array();
                        $file_typs_comarr = array(
                            'text/plain' => __('text', 'wp-jobsearch'),
                            'image/jpeg' => __('jpeg', 'wp-jobsearch'),
                            'image/png' => __('png', 'wp-jobsearch'),
                            'application/msword' => __('doc', 'wp-jobsearch'),
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => __('docx', 'wp-jobsearch'),
                            'application/vnd.ms-excel' => __('xls', 'wp-jobsearch'),
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => __('xlsx', 'wp-jobsearch'),
                            'application/pdf' => __('pdf', 'wp-jobsearch'),
                        );
                        foreach ($file_typs_comarr as $file_typ_key => $file_typ_comar) {
                            if (in_array($file_typ_key, $cand_files_types)) {
                                $sutable_files_arr[] = '.' . $file_typ_comar;
                            }
                        }
                        $sutable_files_str = implode(', ', $sutable_files_arr);
                        $cand_cvr_leter = get_post_meta($candidate_id, 'jobsearch_field_resume_cover_letter', true);
                        $cand_resm_coverletr = isset($jobsearch_plugin_options['cand_resm_cover_letr']) ? $jobsearch_plugin_options['cand_resm_cover_letr'] : '';
                        ?>
                        <div class="jobsearch-applyform-area">
                            <div class="jobsearch-applyform-titlebox">
                                <h2><?php esc_html_e('Select CV', 'wp-jobsearch') ?></h2>
                            </div>
                            <?php
                            $user_id = get_current_user_id();
                            $candidate_id = jobsearch_get_user_candidate_id($user_id);
                            $ca_at_cv_files = get_post_meta($candidate_id, 'candidate_cv_files', true);

                            //
                            $from_args = array(
                                'rand_num' => $p_rand_id,
                                'job_id' => $p_job_id,
                                'apply_type' => 'internal',
                            );
                            do_action('jobsearch_apply_job_internal_bfr_main_html', $from_args);
                            ?>
                            <div class="jobsearch-applyjob-internalmain jobsearch-apply-withcvs"<?php echo apply_filters('jobsearch_apply_job_internal_main_tag_exattrs', '', $from_args) ?>>
                                <?php
                                $cv_files_count = 0;
                                if (!empty($ca_at_cv_files)) {
                                    $cv_files_count = count($ca_at_cv_files);

                                    $have_resumes = false;
                                    ob_start();
                                    $cvfile_count = 1;
                                    foreach ($ca_at_cv_files as $cv_file_key => $cv_file_val) {
                                        $file_attach_id = isset($cv_file_val['file_id']) ? $cv_file_val['file_id'] : '';
                                        $file_url = isset($cv_file_val['file_url']) ? $cv_file_val['file_url'] : '';
                                        $filename = isset($cv_file_val['file_name']) ? $cv_file_val['file_name'] : '';
                                        $filetype = isset($cv_file_val['mime_type']) ? $cv_file_val['mime_type'] : '';
                                        $fileuplod_time = isset($cv_file_val['time']) ? $cv_file_val['time'] : '';
                                        if (is_numeric($file_attach_id) && get_post_type($file_attach_id) == 'attachment') {
                                            $attach_mime = isset($attach_post->post_mime_type) ? $attach_post->post_mime_type : '';
                                            $filetype = array('type' => $attach_mime);
                                        }

                                        $cv_file_title = $filename;

                                        $attach_date = $fileuplod_time;
                                        $attach_mime = isset($filetype['type']) ? $filetype['type'] : '';

                                        if ($cvfile_count == 1) {
                                            $cv_primary = 'yes';
                                        } else {
                                            $cv_primary = isset($cv_file_val['primary']) ? $cv_file_val['primary'] : '';
                                        }

                                        if (is_numeric($file_attach_id) && get_post_type($file_attach_id) == 'attachment') {
                                            $cv_file_title = get_the_title($file_attach_id);
                                            $attach_post = get_post($file_attach_id);
                                            $file_path = get_attached_file($file_attach_id);
                                            $filename = basename($file_path);

                                            $attach_date = isset($attach_post->post_date) ? $attach_post->post_date : '';
                                            $attach_date = strtotime($attach_date);
                                            $attach_mime = isset($attach_post->post_mime_type) ? $attach_post->post_mime_type : '';
                                        }

                                        if ($attach_mime == 'application/pdf') {
                                            $attach_icon = 'fa fa-file-pdf-o';
                                        } else if ($attach_mime == 'application/msword' || $attach_mime == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                                            $attach_icon = 'fa fa-file-word-o';
                                        } else if ($attach_mime == 'text/plain') {
                                            $attach_icon = 'fa fa-file-text-o';
                                        } else if ($attach_mime == 'application/vnd.ms-excel' || $attach_mime == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                                            $attach_icon = 'fa fa-file-excel-o';
                                        } else if ($attach_mime == 'image/jpeg' || $attach_mime == 'image/png') {
                                            $attach_icon = 'fa fa-file-image-o';
                                        } else {
                                            $attach_icon = 'fa fa-file-word-o';
                                        }
                                        if (!empty($filetype)) {
                                            $have_resumes = true;
                                            ?>
                                            <li<?php echo($cv_primary == 'yes' ? ' class="active"' : '') ?>>
                                                <i class="<?php echo($attach_icon) ?>"></i>
                                                <label for="cv_file_<?php echo($file_attach_id) ?>">
                                                    <input id="cv_file_<?php echo($file_attach_id) ?>"
                                                           type="radio" class="cv_file_item"
                                                           name="cv_file_item" <?php echo($cv_primary == 'yes' ? 'checked="checked"' : '') ?>
                                                           value="<?php echo($file_attach_id) ?>">
                                                    <?php echo(strlen($cv_file_title) > 40 ? substr($cv_file_title, 0, 40) . '...' : $cv_file_title) ?>
                                                    <?php
                                                    if ($attach_date != '') {
                                                        ?>
                                                        <span class="upload-datetime"><i
                                                                    class="fa fa-calendar"></i> <?php echo date_i18n(get_option('date_format'), ($attach_date)) . ' ' . date_i18n(get_option('time_format'), ($attach_date)) ?></span>
                                                        <?php
                                                    }
                                                    ?>
                                                </label>
                                            </li>
                                            <?php
                                        }
                                        $cvfile_count++;
                                    }
                                    $resumes_list_html = ob_get_clean();
                                    ?>
                                    <ul class="user-cvs-list"<?php echo($have_resumes ? '' : ' style="display: none;"') ?>>
                                        <?php
                                        echo($resumes_list_html);
                                        ?>
                                    </ul>
                                    <?php
                                    if (!$have_resumes) {
                                        ?>
                                        <div class="user-nocvs-found">
                                            <p><?php esc_html_e('No resume uploaded.', 'wp-jobsearch') ?></p>
                                        </div>
                                        <?php
                                    }
                                    if (isset($cv_files_count) && $cv_files_count < $max_cvs_allow) { ?>
                                        <div class="upload-cvs-sep">
                                            <div class="jobsearch-box-title">
                                                <span><?php esc_html_e('OR', 'wp-jobsearch') ?></span>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <ul class="user-cvs-list" style="display: none;"></ul>
                                    <div class="user-nocvs-found">
                                        <p><?php esc_html_e('No resume uploaded.', 'wp-jobsearch') ?></p>
                                    </div>
                                    <?php
                                }
                                if (isset($cv_files_count) && $cv_files_count < $max_cvs_allow) { ?>
                                    <div class="upload-new-cv-sec">
                                        <div class="jobsearch-drpzon-con">
                                            <script type="text/javascript">
                                                jQuery(document).ready(function () {
                                                    jQuery('#cvFilesDropzone').dropzone({
                                                        uploadMultiple: false,
                                                        maxFiles: 1,
                                                        <?php
                                                        if (!empty($cand_files_types)) {
                                                        ?>
                                                        acceptedFiles: '<?php echo implode(',', $cand_files_types) ?>',
                                                        <?php
                                                        }
                                                        ?>
                                                        maxFilesize: <?php echo absint($filesize_act) ?>,
                                                        paramName: 'on_apply_cv_file',
                                                        init: function () {
                                                            this.on("complete", function (file) {
                                                                //console.log(file);
                                                                if (file.status == 'success') {
                                                                    var ajresponse = file.xhr.response;
                                                                    ajresponse = jQuery.parseJSON(ajresponse);
                                                                    //console.log(ajresponse);
                                                                    jQuery('.jobsearch-apply-withcvs .user-cvs-list').append(ajresponse.filehtml);
                                                                    jQuery('.jobsearch-apply-withcvs .user-cvs-list').removeAttr('style');
                                                                    jQuery('.jobsearch-apply-withcvs .user-nocvs-found').hide();
                                                                    jQuery('.jobsearch-apply-withcvs .user-cvs-list li:last-child').find('input').trigger('click');
                                                                }
                                                                jQuery('.upload-new-cv-sec .fileUpLoader').html('');
                                                            });
                                                        },
                                                        addedfile: function () {
                                                            jQuery('.jobsearch-drpzon-con').css({
                                                                'pointer-events': 'none',
                                                                'opacity': '0.4'
                                                            });
                                                            jQuery('.upload-new-cv-sec .fileUpLoader').html('<i class="fa fa-refresh fa-spin"></i>');
                                                        }
                                                    });
                                                });
                                            </script>
                                            <form autocomplete="off" action="<?php echo admin_url('admin-ajax.php') ?>"
                                                  id="cvFilesDropzone" method="post" class="dropzone">
                                                <div class="dz-message jobsearch-dropzone-template">
                                                    <span class="upload-icon-con"><i
                                                                class="jobsearch-icon jobsearch-upload"></i></span>
                                                    <strong><?php esc_html_e('Drop files here to upload.', 'wp-jobsearch') ?></strong>
                                                    <div class="upload-inffo"><?php printf(__('To upload file size is <span>(Max %s)</span> <span class="uplod-info-and">and</span> allowed file types are <span>(%s)</span>', 'wp-jobsearch'), $cvfile_size_str, $sutable_files_str) ?></div>
                                                    <div class="upload-or-con">
                                                        <span><?php esc_html_e('or', 'wp-jobsearch') ?></span>
                                                    </div>
                                                    <a class="jobsearch-drpzon-btn"><i
                                                                class="jobsearch-icon jobsearch-arrows-2"></i> <?php esc_html_e('Upload Resume', 'wp-jobsearch') ?>
                                                    </a>
                                                </div>
                                                <input type="hidden" name="action"
                                                       value="jobsearch_apply_job_with_cv_file">
                                            </form>
                                        </div>
                                    </div>
                                    <?php
                                }

                                //
                                if ($cand_resm_coverletr == 'on') {
                                    ?>
                                    <div class="jobsearch-user-form jobsearch-user-form-coltwo jobsearch-frmfields-sec aply-cvr-letter">
                                        <ul class="apply-fields-list">
                                            <li class="form-textarea jobsearch-user-form-coltwo-full">
                                                <label><?php esc_html_e('Cover Letter', 'wp-jobsearch') ?>
                                                    :</label>
                                                <textarea name="cand_cover_letter" placeholder="<?php esc_html_e('Cover Letter', 'wp-jobsearch') ?>"><?php echo($cand_cvr_leter) ?></textarea>
                                                <?php jobsearch_in_aplyjob_uplodin_logcand_cover_html($candidate_id) ?>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php
                                }

                                echo apply_filters('jobsearch_applying_job_after_cv_upload_file', '');

                                $cand_require_pckgs = jobsearch_show_cand_onaply_pckges();
                                $onaply_pckgs_list = jobsearch_cand_onaply_pckges_list();
                                if ($cand_require_pckgs && !empty($onaply_pckgs_list)) {
                                    jobsearch_cand_onaply_pckge_chose_html();
                                }

                                echo apply_filters('jobsearch_applying_job_before_apply', '');
                                ?>
                                <a href="javascript:void(0);"
                                   class="<?php echo esc_html($p_classes_str); ?> jobsearch-applyjob-btn jobsearch-apply-btn-<?php echo absint($p_rand_id); ?> <?php echo esc_html($p_classes); ?>" <?php echo(!is_user_logged_in() ? 'data-wredircto="' . $this_wredirct_url . '"' : '') ?>
                                   data-randid="<?php echo absint($p_rand_id); ?>"
                                   data-jobid="<?php echo absint($p_job_id); ?>"
                                   data-btnafterlabel="<?php echo esc_html($p_btn_after_label) ?>"
                                   data-btnbeforelabel="<?php echo wp_kses($p_btn_text,[]) ?>"><?php echo ($p_btn_text) ?></a>
                                <small class="apply-bmsg"></small>
                            </div>
                        </div>
                        <?php
                    } else {

                        $cand_resm_coverletr = isset($jobsearch_plugin_options['cand_resm_cover_letr']) ? $jobsearch_plugin_options['cand_resm_cover_letr'] : '';
                        //
                        $ferd_classes = '';
                        if ($apply_without_login == 'on' && !is_user_logged_in()) {
                            $ferd_classes = 'jobsearch-nonuser-apply-btn';
                        } else if (!is_user_logged_in()) {
                            $ferd_classes = 'jobsearch-open-signin-tab jobsearch-wredirct-url';
                        }

                        $cand_member_check = true;
                        if (is_user_logged_in()) {
                            if (!jobsearch_user_is_candidate()) {
                                $cand_member_check = false;
                            }
                        }
                        $show_cand_pckgs = false;
                        $cand_require_pckgs = jobsearch_show_cand_onaply_pckges();
                        $onaply_pckgs_list = jobsearch_cand_onaply_pckges_list();
                        if ($cand_require_pckgs && !empty($onaply_pckgs_list)) {
                            $show_cand_pckgs = true;
                        }

                        ob_start();
                        $force_open_apply_popup = apply_filters('jobsearch_job_apply_simple_btn_popopen', false, $job_id);
                        if (($cand_resm_coverletr == 'on' || $show_cand_pckgs || $force_open_apply_popup === true) && $cand_member_check) {
                            
                            if (is_user_logged_in()) {
                                $popup_args = array(
                                    'p_job_id' => $job_id,
                                    'p_rand_id' => $rand_id,
                                    'p_btn_text' => $btn_text,
                                    'p_classes' => $classes,
                                    'p_classes_str' => $classes_str,
                                    'p_btn_after_label' => $btn_after_label,
                                    'this_wredirct_url' => $this_wredirct_url,
                                    'cand_resm_coverletr' => $cand_resm_coverletr,
                                    'show_cand_pckgs' => $show_cand_pckgs,
                                );
                                
                                $user_id = get_current_user_id();
                                $candidate_id = jobsearch_get_user_candidate_id($user_id);

                                extract(shortcode_atts(array(
                                    'p_job_id' => '',
                                    'p_rand_id' => '',
                                    'p_btn_text' => '',
                                    'p_classes' => '',
                                    'p_classes_str' => '',
                                    'p_btn_after_label' => '',
                                    'this_wredirct_url' => '',
                                    'cand_resm_coverletr' => '',
                                    'show_cand_pckgs' => '',
                                ), $popup_args));


                                $cand_cvr_leter = get_post_meta($candidate_id, 'jobsearch_field_resume_cover_letter', true);
                                ?>
                                <div class="jobsearch-applyform-area">
                                    <?php
                                    $from_args = array(
                                        'rand_num' => $p_rand_id,
                                        'job_id' => $p_job_id,
                                        'apply_type' => 'internal',
                                    );
                                    do_action('jobsearch_apply_job_internal_bfr_main_html', $from_args);
                                    ?>
                                    <div class="jobsearch-applyjob-internalmain"<?php echo apply_filters('jobsearch_apply_job_internal_main_tag_exattrs', '', $from_args) ?>>
                                        <?php
                                        if ($show_cand_pckgs) {
                                            jobsearch_cand_onaply_pckge_chose_html();
                                        }
                                        if ($cand_resm_coverletr == 'on') {
                                            ?>
                                            <div class="jobsearch-user-form jobsearch-user-form-coltwo jobsearch-frmfields-sec aply-cvr-letter">
                                                <ul class="apply-fields-list">
                                                    <li class="form-textarea jobsearch-user-form-coltwo-full">
                                                        <label><?php esc_html_e('Cover Letter', 'wp-jobsearch') ?>:</label>
                                                        <textarea name="cand_cover_letter" placeholder="<?php esc_html_e('Cover Letter', 'wp-jobsearch') ?>"><?php echo($cand_cvr_leter) ?></textarea>

                                                        <?php jobsearch_in_aplyjob_uplodin_logcand_cover_html($candidate_id) ?>
                                                    </li>
                                                </ul>
                                            </div>
                                            <?php
                                        }
                                        echo apply_filters('jobsearch_applying_job_before_apply_single', '');
                                        ?>
                                        <a href="javascript:void(0);"
                                           class="<?php echo esc_html($p_classes_str); ?> jobsearch-apply-btn-<?php echo absint($p_rand_id); ?> <?php echo esc_html($p_classes); ?>" <?php echo(!is_user_logged_in() ? 'data-wredircto="' . $this_wredirct_url . '"' : '') ?>
                                           data-randid="<?php echo absint($p_rand_id); ?>"
                                           data-jobid="<?php echo absint($p_job_id); ?>"
                                           data-btnafterlabel="<?php echo esc_html($p_btn_after_label) ?>"
                                           data-btnbeforelabel="<?php echo wp_kses($p_btn_text,[]) ?>"><?php echo ($p_btn_text) ?></a>
                                        <small class="apply-bmsg"></small>
                                    </div>
                                </div>
                                <?php
                            }
                            //

                        } else {
                            if (!is_user_logged_in() && $mubtn_withlogin_switch === true) {
                                $rand_num = rand(100000, 9999999);
                                ?>
                                <form autocomplete="off" id="apply-form-<?php echo absint($rand_num) ?>" method="post">
                                    <?php
                                    $from_args = array(
                                        'rand_num' => $rand_num,
                                        'job_id' => $job_id
                                    );
                                    do_action('jobsearch_apply_job_woutreg_in_formtag_html', $from_args);
                                    ?>
                                    <div class="<?php echo apply_filters('jobsearch_apply_job_woutreg_inform_class', 'jobsearch-user-form jobsearch-user-form-coltwo', $from_args) ?>"<?php echo apply_filters('jobsearch_apply_job_woutreg_inform_tag_exattrs', '', $from_args) ?>>
                                        <ul class="apply-fields-list">
                                            <?php
                                            $wout_fields_sort = isset($jobsearch_plugin_options['aplywout_login_fields_sort']) ? $jobsearch_plugin_options['aplywout_login_fields_sort'] : '';
                                            $wout_fields_sort = isset($wout_fields_sort['fields']) ? $wout_fields_sort['fields'] : '';
                                            if (isset($wout_fields_sort['name'])) {
                                                foreach ($wout_fields_sort as $field_sort_key => $field_sort_val) {
                                                    $field_name_swich_key = 'aplywout_log_f' . $field_sort_key . '_swch';
                                                    $field_name_swich = isset($jobsearch_plugin_options[$field_name_swich_key]) ? $jobsearch_plugin_options[$field_name_swich_key] : '';
                                                    if ($field_sort_key == 'name' && ($field_name_swich == 'on' || $field_name_swich == 'on_req')) {
                                                        ?>
                                                        <li>
                                                            <label><?php esc_html_e('First Name:', 'wp-jobsearch') ?><?php echo($field_name_swich == 'on_req' ? ' *' : '') ?></label>
                                                            <input class="<?php echo($field_name_swich == 'on_req' ? 'required-apply-field' : 'required') ?>"
                                                                   name="pt_user_fname" type="text"
                                                                   placeholder="<?php esc_html_e('First Name', 'wp-jobsearch') ?>">
                                                        </li>
                                                        <li>
                                                            <label><?php esc_html_e('Last Name:', 'wp-jobsearch') ?><?php echo($field_name_swich == 'on_req' ? ' *' : '') ?></label>
                                                            <input class="<?php echo($field_name_swich == 'on_req' ? 'required-apply-field' : 'required') ?>"
                                                                   name="pt_user_lname" type="text"
                                                                   placeholder="<?php esc_html_e('Last Name', 'wp-jobsearch') ?>">
                                                        </li>
                                                        <?php
                                                    } else if ($field_sort_key == 'email') {
                                                        ?>
                                                        <li>
                                                            <label><?php esc_html_e('Email: *', 'wp-jobsearch') ?></label>
                                                            <input class="required" name="user_email" type="text"
                                                                   placeholder="<?php esc_html_e('Email Address', 'wp-jobsearch') ?>">
                                                        </li>
                                                        <?php
                                                    } else if ($field_sort_key == 'phone' && ($field_name_swich == 'on' || $field_name_swich == 'on_req')) {
                                                        ?>
                                                        <li>
                                                            <label><?php esc_html_e('Phone:', 'wp-jobsearch') ?><?php echo($field_name_swich == 'on_req' ? ' *' : '') ?></label>
                                                            <?php
                                                            if ($phone_validation_type == 'on') {
                                                                $rand_numb = rand(100000000, 999999999);

                                                                $phone_field_req = false;
                                                                if ($field_name_swich == 'on_req') {
                                                                    $phone_field_req = true;
                                                                }
                                                                $itltell_input_ats = array(
                                                                    'sepc_name' => 'user_phone',
                                                                    'set_condial_intrvl' => 'yes',
                                                                    'is_required' => $phone_field_req
                                                                );
                                                                jobsearch_phonenum_itltell_input('pt_user_phone', $rand_numb, '', $itltell_input_ats);
                                                            } else {
                                                                ?>
                                                                <input class="<?php echo($field_name_swich == 'on_req' ? 'required-apply-field' : 'required') ?>"
                                                                       name="user_phone" type="tel"
                                                                       placeholder="<?php esc_html_e('Phone Number', 'wp-jobsearch') ?>">
                                                                <?php
                                                            }
                                                            ?>
                                                        </li>
                                                        <?php
                                                    } else if ($field_sort_key == 'current_jobtitle' && ($field_name_swich == 'on' || $field_name_swich == 'on_req')) {
                                                        ?>
                                                        <li>
                                                            <label><?php esc_html_e('Current Job Title:', 'wp-jobsearch') ?><?php echo($field_name_swich == 'on_req' ? ' *' : '') ?></label>
                                                            <input class="<?php echo($field_name_swich == 'on_req' ? 'required-apply-field' : 'required') ?>"
                                                                   name="user_job_title" type="text"
                                                                   placeholder="<?php esc_html_e('Current Job Title', 'wp-jobsearch') ?>">
                                                        </li>
                                                        <?php
                                                    } else if ($field_sort_key == 'current_salary' && ($field_name_swich == 'on' || $field_name_swich == 'on_req')) {
                                                        ?>
                                                        <li>
                                                            <label><?php esc_html_e('Current Salary:', 'wp-jobsearch') ?><?php echo($field_name_swich == 'on_req' ? ' *' : '') ?></label>
                                                            <input class="<?php echo($field_name_swich == 'on_req' ? 'required-apply-field' : 'required') ?>"
                                                                   name="user_salary" type="text"
                                                                   placeholder="<?php esc_html_e('Current Salary', 'wp-jobsearch') ?>">
                                                        </li>
                                                        <?php
                                                    } else if ($field_sort_key == 'custom_fields' && $field_name_swich == 'on') {
                                                        do_action('jobsearch_form_custom_fields_load', 0, 'candidate');
                                                    } else if ($field_sort_key == 'cv_attach' && ($field_name_swich == 'on' || $field_name_swich == 'on_req')) {
                                                        ?>
                                                        <li class="jobsearch-user-form-coltwo-full">
                                                            <div id="jobsearch-upload-cv-main"
                                                                 class="jobsearch-upload-cv jobsearch-applyjob-upload-cv">
                                                                <label><?php esc_html_e('Resume', 'wp-jobsearch') ?><?php echo($field_name_swich == 'on_req' ? ' *' : '') ?></label>
                                                                <div class="jobsearch-drpzon-con jobsearch-drag-dropcustom">
                                                                    <div id="cvFilesDropzone" class="dropzone"
                                                                         ondragover="jobsearch_dragover_evnt(event)"
                                                                         ondragleave="jobsearch_leavedrop_evnt(event)"
                                                                         ondrop="jobsearch_ondrop_evnt(event)">
                                                                        <input type="file" id="cand_cv_filefield"
                                                                               class="jobsearch-upload-btn <?php echo($field_name_swich == 'on_req' ? 'cv_is_req' : '') ?>"
                                                                               name="cand_woutreg_cv_file"
                                                                               onchange="jobsearchFileContainerChangeFile(event)">
                                                                        <div class="fileContainerFileName"
                                                                             ondrop="jobsearch_ondrop_evnt(event)"
                                                                             id="fileNameContainer">
                                                                            <div class="dz-message jobsearch-dropzone-template">
                                                                                <span class="upload-icon-con"><i
                                                                                            class="jobsearch-icon jobsearch-upload"></i></span>
                                                                                <strong><?php esc_html_e('Drop a resume file or click to upload.', 'wp-jobsearch') ?></strong>
                                                                                <div class="upload-inffo"><?php printf(__('To upload file size is <span>(Max %s)</span> <span class="uplod-info-and">and</span> allowed file types are <span>(%s)</span>', 'wp-jobsearch'), $cvfile_size_str, $sutable_files_str) ?></div>
                                                                                <div class="upload-or-con">
                                                                                    <span><?php esc_html_e('or', 'wp-jobsearch') ?></span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <a class="jobsearch-drpzon-btn"><i
                                                                                    class="jobsearch-icon jobsearch-arrows-2"></i> <?php esc_html_e('Upload Resume', 'wp-jobsearch') ?>
                                                                        </a>
                                                                    </div>
                                                                    <script type="text/javascript">
                                                                        jQuery('#cvFilesDropzone').find('input[name=cand_woutreg_cv_file]').css({
                                                                            position: 'absolute',
                                                                            width: '100%',
                                                                            height: '100%',
                                                                            top: '0',
                                                                            left: '0',
                                                                            opacity: '0',
                                                                            'z-index': '9',
                                                                        });

                                                                        function jobsearchFileContainerChangeFile(e) {
                                                                            var the_show_msg = '<?php esc_html_e('No file has been selected', 'wp-jobsearch') ?>';
                                                                            if (e.target.files.length > 0) {
                                                                                var slected_file_name = e.target.files[0].name;
                                                                                the_show_msg = '<?php esc_html_e('The file', 'wp-jobsearch') ?> "' + slected_file_name + '" <?php esc_html_e('has been selected', 'wp-jobsearch') ?>';
                                                                            }
                                                                            document.getElementById('cvFilesDropzone').classList.remove('fileContainerDragOver');
                                                                            try {
                                                                                droppedFiles = document.getElementById('cand_cv_filefield').files;
                                                                                document.getElementById('fileNameContainer').textContent = the_show_msg;
                                                                            } catch (error) {
                                                                                ;
                                                                            }
                                                                            try {
                                                                                aName = document.getElementById('cand_cv_filefield').value;
                                                                                if (aName !== '') {
                                                                                    document.getElementById('fileNameContainer').textContent = the_show_msg;
                                                                                }
                                                                            } catch (error) {
                                                                                ;
                                                                            }
                                                                        }

                                                                        function jobsearch_ondrop_evnt(e) {
                                                                            var the_show_msg = '<?php esc_html_e('No file has been selected', 'wp-jobsearch') ?>';
                                                                            if (e.target.files.length > 0) {
                                                                                var slected_file_name = e.target.files[0].name;
                                                                                the_show_msg = '<?php esc_html_e('The file', 'wp-jobsearch') ?> "' + slected_file_name + '" <?php esc_html_e('has been selected', 'wp-jobsearch') ?>';
                                                                            }
                                                                            document.getElementById('cvFilesDropzone').classList.remove('fileContainerDragOver');
                                                                            try {
                                                                                droppedFiles = e.dataTransfer.files;
                                                                                document.getElementById('fileNameContainer').textContent = the_show_msg;
                                                                            } catch (error) {
                                                                                ;
                                                                            }
                                                                        }

                                                                        function jobsearch_dragover_evnt(e) {
                                                                            document.getElementById('cvFilesDropzone').classList.add('fileContainerDragOver');
                                                                            e.preventDefault();
                                                                            e.stopPropagation();
                                                                        }

                                                                        function jobsearch_leavedrop_evnt(e) {
                                                                            document.getElementById('cvFilesDropzone').classList.remove('fileContainerDragOver');
                                                                        }
                                                                    </script>
                                                                </div>
                                                            </div>
                                                        </li>
                                                        <?php
                                                    }
                                                }
                                                do_action('jobsearch_applypop_wout_reg_b4r_cover');
                                                $cand_resm_coverletr = isset($jobsearch_plugin_options['cand_resm_cover_letr']) ? $jobsearch_plugin_options['cand_resm_cover_letr'] : '';
                                                if ($cand_resm_coverletr == 'on') {
                                                    ?>
                                                    <li class="form-textarea jobsearch-user-form-coltwo-full">
                                                        <label><?php esc_html_e('Cover Letter', 'wp-jobsearch') ?>:</label>
                                                        <textarea name="cand_cover_letter" placeholder="<?php esc_html_e('Cover Letter', 'wp-jobsearch') ?>"></textarea>
                                                        <?php
                                                        jobsearch_in_aplyjob_uplodin_withoutlog_cover_html();
                                                        ?>
                                                    </li>
                                                    <?php
                                                }
                                            } else {
                                                //
                                            }
                                            
                                            $captcha_switch = isset($jobsearch_plugin_options['captcha_switch']) ? $jobsearch_plugin_options['captcha_switch'] : '';
                                            $jobsearch_sitekey = isset($jobsearch_plugin_options['captcha_sitekey']) ? $jobsearch_plugin_options['captcha_sitekey'] : '';
                                            if ($captcha_switch == 'on' && !is_user_logged_in()) {
                                                wp_enqueue_script('jobsearch_google_recaptcha');
                                                ?>
                                                <li class="jobsearch-user-form-coltwo-full">
                                                    <script type="text/javascript">
                                                        var recaptcha_aply;
                                                        var jobsearch_multicap = function () {
                                                            //Render the recaptcha_aply on the element with ID "recaptcha_aply"
                                                            recaptcha_aply = grecaptcha.render('recaptcha_aply', {
                                                                'sitekey': '<?php echo ($jobsearch_sitekey); ?>', //Replace this with your Site key
                                                                'theme': 'light'
                                                            });
                                                        };
                                                        jQuery(document).ready(function () {
                                                            jQuery('.recaptcha-reload-a').click();
                                                        });
                                                    </script>
                                                    <div class="recaptcha-reload" id="recaptcha_aply_div">
                                                        <?php echo jobsearch_recaptcha('recaptcha_aply'); ?>
                                                    </div>
                                                </li>
                                                <?php
                                            }
                                            ?>
                                            <li class="jobsearch-user-form-coltwo-full">
                                                <input type="hidden" name="action"
                                                       value="<?php echo apply_filters('jobsearch_apply_btn_action_without_reg', 'jobsearch_job_apply_without_login') ?>">
                                                <input type="hidden" name="job_id"
                                                       value="<?php echo absint($job_id) ?>">
                                                <?php jobsearch_terms_and_con_link_txt() ?>
                                                <input class="<?php echo apply_filters('jobsearch_apply_btn_class_without_reg', 'jobsearch-apply-woutreg-btn') ?>"
                                                       data-id="<?php echo absint($rand_num) ?>" type="submit"
                                                       value="<?php esc_html_e('Apply Job', 'wp-jobsearch') ?>">
                                                <div class="form-loader"></div>
                                            </li>
                                        </ul>
                                        <div class="apply-job-form-msg"></div>
                                    </div>
                                </form>
                                <?php
                            }
                        }
                        $appbtn_html = ob_get_clean();
                        echo apply_filters('jobsearch_jobaplybtn_simple_default', $appbtn_html, $classes_str, $rand_id, $classes, $job_id, $btn_after_label, $btn_text);

                        //
                    }
                }
            }
        } else {
            ?>
            <div class="jobsearch-aply-nojob">
                <div class="alert alert-info">
                    <p><?php esc_html_e('No Job found to apply.', 'wp-jobsearch') ?></p>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
    $html = ob_get_clean();
    return $html;
}
