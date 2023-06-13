<?php

use WP_Jobsearch\Package_Limits;

global $jobsearch_plugin_options, $Jobsearch_User_Dashboard_Settings, $diff_form_errs;

$user_id = get_current_user_id();
$user_obj = get_user_by('ID', $user_id);

$user_pkg_limits = new Package_Limits;

$page_id = $user_dashboard_page = isset($jobsearch_plugin_options['user-dashboard-template-page']) ? $jobsearch_plugin_options['user-dashboard-template-page'] : '';
$page_id = $user_dashboard_page = jobsearch__get_post_id($user_dashboard_page, 'page');
$page_url = jobsearch_wpml_lang_page_permalink($page_id, 'page'); //get_permalink($page_id);
$candidate_id = jobsearch_get_user_candidate_id($user_id);

$reults_per_page = isset($jobsearch_plugin_options['user-dashboard-per-page']) && $jobsearch_plugin_options['user-dashboard-per-page'] > 0 ? $jobsearch_plugin_options['user-dashboard-per-page'] : 10;
$page_num = isset($_GET['page_num']) ? $_GET['page_num'] : 1;

if ($candidate_id > 0) {

    $inopt_cover_letr = isset($jobsearch_plugin_options['cand_resm_cover_letr']) ? $jobsearch_plugin_options['cand_resm_cover_letr'] : '';
    $inopt_resm_education = isset($jobsearch_plugin_options['cand_resm_education']) ? $jobsearch_plugin_options['cand_resm_education'] : '';
    $inopt_resm_experience = isset($jobsearch_plugin_options['cand_resm_experience']) ? $jobsearch_plugin_options['cand_resm_experience'] : '';
    $inopt_resm_portfolio = isset($jobsearch_plugin_options['cand_resm_portfolio']) ? $jobsearch_plugin_options['cand_resm_portfolio'] : '';
    $inopt_resm_skills = isset($jobsearch_plugin_options['cand_resm_skills']) ? $jobsearch_plugin_options['cand_resm_skills'] : '';
    $inopt_resm_langs = isset($jobsearch_plugin_options['cand_resm_langs']) ? $jobsearch_plugin_options['cand_resm_langs'] : '';
    $inopt_resm_honsawards = isset($jobsearch_plugin_options['cand_resm_honsawards']) ? $jobsearch_plugin_options['cand_resm_honsawards'] : '';
    $cover_letter = get_post_meta($candidate_id, 'jobsearch_field_resume_cover_letter', true);
    $termscon_chek = get_post_meta($candidate_id, 'terms_cond_check', true);

    do_action('jobsearch_before_cand_dash_resume_contnt', $candidate_id);
    ?>
    <script>
        jQuery(document).ready(function () {
            jQuery("#jobsearch-resume-edu-con ul").sortable({
                handle: '.el-drag-item',
                cursor: 'move',
            });
            jQuery("#jobsearch-resume-expr-con ul").sortable({
                handle: '.el-drag-item',
                cursor: 'move',
            });
            jQuery("#jobsearch-resume-portfolio-con ul").sortable({
                handle: '.el-drag-item',
                cursor: 'move',
            });
            jQuery("#jobsearch-resume-skills-con ul").sortable({
                handle: '.el-drag-item',
                cursor: 'move',
            });
            jQuery("#jobsearch-resume-awards-con ul").sortable({
                handle: '.el-drag-item',
                cursor: 'move',
            });
        });
    </script>
    <form autocomplete="off" method="post" id="jobsearch-candidate-resumesub" class="jobsearch-candidate-dasboard"
          action="<?php echo add_query_arg(array('tab' => 'my-resume'), $page_url) ?>">
        
        <div class="jobsearch-employer-box-section">
            <div class="jobsearch-profile-title">
                <h2><?php esc_html_e('My Resume', 'wp-jobsearch') ?></h2>
            </div>

            <div class="jobsearch-candidate-section">
                <?php
                if (isset($_POST['user_resume_form']) && $_POST['user_resume_form'] == '1') {
                    if (isset($diff_form_errs['user_not_allow_mod']) && $diff_form_errs['user_not_allow_mod'] == true) { ?>
                        <div class="jobsearch-alert jobsearch-error-alert">
                            <p><?php echo wp_kses(__('<strong>Error!</strong> You are not allowed to modify settings.', 'wp-jobsearch'), array('strong' => array())) ?></p>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="jobsearch-alert jobsearch-success-alert">
                            <p><?php echo wp_kses(__('<strong>Success!</strong> All changes updated.', 'wp-jobsearch'), array('strong' => array())) ?></p>
                        </div>
                        <?php
                    }
                }
                
                $args = array(
                    'candidate_id' => $candidate_id,
                    'view' => 'package-view',
                );
                apply_filters('jobsearch_cand_generate_resume_btn', $args);
                
                ob_start();
                ?>
                <div class="jobsearch-candidate-title cv_cover_letter_skillid">
                    <h2>
                        <i class="jobsearch-icon jobsearch-resume-1"></i> <?php esc_html_e('Cover Letter', 'wp-jobsearch') ?>
                    </h2>
                </div>
                <?php
                if ($user_pkg_limits::cand_field_is_locked('coverltr_defields')) {
                    echo($user_pkg_limits::cand_gen_locked_html());
                } else {
                    ?>
                    <div class="jobsearch-candidate-dashboard-editor">
                        <textarea name="jobsearch_field_resume_cover_letter" rows="10" class="form-control"><?php echo($cover_letter) ?></textarea>
                        <br>
                        
                        <div class="jobsearch-candcover-uplodholdr">
                            <?php
                            $cand_cover_file = get_post_meta($candidate_id, 'candidate_cover_letter_file', true);

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
                            $cand_cv_file_size = isset($jobsearch_plugin_options['cand_cover_letter_file_size']) ? $jobsearch_plugin_options['cand_cover_letter_file_size'] : '';
                            if (isset($file_sizes_arr[$cand_cv_file_size])) {
                                $cvfile_size = $cand_cv_file_size;
                                $cvfile_size_str = $file_sizes_arr[$cand_cv_file_size];
                            }

                            $filesize_act = ($cvfile_size/1000);

                            $cand_files_types = isset($jobsearch_plugin_options['cand_cover_letter_types']) ? $jobsearch_plugin_options['cand_cover_letter_types'] : '';
                            if (empty($cand_files_types)) {
                                $cand_files_types = array(
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'application/pdf',
                                );
                            }
                            $sutable_files_arr = array();
                            $sutable_files_mimes = array();
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
                                    $sutable_files_mimes[] = $file_typ_key;
                                }
                            }
                            $sutable_files_str = implode(', ', $sutable_files_arr);
                            ?>
                            <div id="com-file-holder">
                                <?php
                                if (!empty($cand_cover_file)) {
                                    $filename = isset($cand_cover_file['file_name']) ? $cand_cover_file['file_name'] : '';
                                    $filetype = isset($cand_cover_file['mime_type']) ? $cand_cover_file['mime_type'] : '';
                                    $fileuplod_time = isset($cand_cover_file['time']) ? $cand_cover_file['time'] : '';
                                    $file_uniqid = isset($cand_cover_file['file_id']) ? $cand_cover_file['file_id'] : '';
                                    $file_url = isset($cand_cover_file['file_url']) ? $cand_cover_file['file_url'] : '';

                                    $file_url = apply_filters('wp_jobsearch_user_coverfile_downlod_url', $file_url, $file_uniqid, $candidate_id);

                                    $cv_file_title = $filename;

                                    $attach_date = $fileuplod_time;
                                    $attach_mime = isset($filetype['type']) ? $filetype['type'] : '';

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

                                    if (!empty($cand_cover_file)) {
                                        ?>
                                        <div class="jobsearch-cv-manager-list">
                                            <ul class="jobsearch-row">
                                                <li class="jobsearch-column-12">
                                                    <div class="jobsearch-cv-manager-wrap">
                                                        <a class="jobsearch-cv-manager-thumb"><i class="<?php echo ($attach_icon) ?>"></i></a>
                                                        <div class="jobsearch-cv-manager-text">
                                                            <div class="jobsearch-cv-manager-left">
                                                                <h2 class="jobsearch-pst-title"><a href="<?php echo ($file_url) ?>" oncontextmenu="javascript: return false;" onclick="javascript: if ((event.button == 0 && event.ctrlKey)) {return false};" download="<?php echo ($filename) ?>"><?php echo ($filename) ?></a></h2>
                                                                <?php
                                                                if ($attach_date != '') {
                                                                    ?>
                                                                    <ul>
                                                                        <li><i class="fa fa-calendar"></i> <?php echo date_i18n(get_option('date_format'), ($attach_date)) . ' ' . date_i18n(get_option('time_format'), ($attach_date)) ?></li>
                                                                    </ul>
                                                                    <?php
                                                                }
                                                                ?>
                                                            </div>
                                                            <a href="javascript:void(0);" class="jobsearch-cv-manager-link jobsearch-deluser-coverfile" data-id="<?php echo ($file_uniqid) ?>"><i class="jobsearch-icon jobsearch-rubbish"></i></a>
                                                            <a href="<?php echo ($file_url) ?>" class="jobsearch-cv-manager-link jobsearch-cv-manager-download" oncontextmenu="javascript: return false;" onclick="javascript: if ((event.button == 0 && event.ctrlKey)) {return false};" download="<?php echo ($filename) ?>"><i class="jobsearch-icon jobsearch-download-arrow"></i></a>
                                                        </div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                            <?php
                            $cover_rand_id = rand(100000, 999999);
                            ?>
                            <div id="jobsearch-upload-cover-<?php echo ($cover_rand_id) ?>" class="jobsearchupoldcover-con jobsearch-fileUpload">
                                <span><i class="jobsearch-icon jobsearch-arrows-2"></i> <?php esc_html_e('Upload Cover Letter', 'wp-jobsearch') ?></span>
                                <input name="candidate_cover_file" type="file" data-id="<?php echo ($cover_rand_id) ?>"
                                       class="upload jobsearch-upload jobsearch-uploadfile-field"
                                       onchange="jobsearch_upload_cand_cover_letter_file(event)">
                                <div class="fileUpLoader"></div>
                            </div>
                            <div class="jobsearch-fileUpload-info">
                                <p><?php printf(__('To upload file size is <strong>(Max %s)</strong> <strong class="uplod-info-and">and</strong> allowed file types are <strong>(%s)</strong>', 'wp-jobsearch'), $cvfile_size_str, $sutable_files_str) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php
                }

                $covrletr_html = ob_get_clean();
                if ($inopt_cover_letr != 'off') {
                    echo apply_filters('jobsearch_candidate_dash_resume_covrletr_html', $covrletr_html, $candidate_id);
                }

                //
                echo apply_filters('jobsearch_candidate_dash_resume_after_cover', '', $candidate_id);

                $cand_skills_switch = isset($jobsearch_plugin_options['cand_skills_switch']) ? $jobsearch_plugin_options['cand_skills_switch'] : '';
                $cand_max_skills_allow = isset($jobsearch_plugin_options['cand_max_skills']) && $jobsearch_plugin_options['cand_max_skills'] > 0 ? $jobsearch_plugin_options['cand_max_skills'] : 5;
                $cand_sugg_skills_allow = isset($jobsearch_plugin_options['cand_sugg_skills']) && $jobsearch_plugin_options['cand_sugg_skills'] > 0 ? $jobsearch_plugin_options['cand_sugg_skills'] : 0;

                if ($cand_skills_switch == 'on') {
                    ob_start();
                    ?>
                    <div class="jobsearch-candidate-resume-wrap jobsearch-employer-profile-form">
                        <div class="jobsearch-candidate-title">
                            <h2>
                                <i class="jobsearch-icon jobsearch-social-media"></i> <?php esc_html_e('Skills', 'wp-jobsearch') ?>
                            </h2>
                        </div>
                        <?php
                        wp_enqueue_script('jobsearch-tag-it');
                        $cand_saved_skills = wp_get_post_terms($candidate_id, 'skill');
                        ?>
                        <div class="jobseach-skills-con">
                            <script type="text/javascript">
                                jQuery(document).ready(function () {
                                    jQuery('#cand-skills').tagit({
                                        allowSpaces: true,
                                        tagLimit: '<?php echo($cand_max_skills_allow) ?>',
                                        placeholderText: '<?php esc_html_e('Add Skills', 'wp-jobsearch') ?>',
                                        fieldName: 'get_cand_skills[]',
                                        onTagLimitExceeded: function (event, ui) {
                                            jQuery(".tagit-new input").val("");
                                            alert('<?php printf(esc_html__('Only %s skills allowed.', 'wp-jobsearch'), $cand_max_skills_allow) ?>');
                                        }
                                    });
                                });
                                jQuery(document).on('focus', '.tagit-new input', function () {
                                    var _this = jQuery(this);
                                    _this.parents('.jobseach-skills-con').find('.suggested-skills-con').slideDown();
                                });
                                jQuery(document).on('click', 'body', function (evt) {
                                    var target = evt.target;
                                    var this_box = jQuery('.jobseach-skills-con');
                                    if (!this_box.is(evt.target) && this_box.has(evt.target).length === 0) {
                                        this_box.find('.suggested-skills-con').slideUp();
                                    }
                                });
                            </script>
                            <label><?php esc_html_e('Add Skills', 'wp-jobsearch') ?></label>
                            <ul id="cand-skills" class="jobseach-job-skills">
                                <?php
                                if (!empty($cand_saved_skills)) {
                                    foreach ($cand_saved_skills as $cand_saved_skill) {
                                        ?>
                                        <li><?php echo jobsearch_esc_html($cand_saved_skill->name) ?></li>
                                        <?php
                                    }
                                }
                                ?>
                            </ul>
                            <?php
                            if ($cand_sugg_skills_allow > 0) {
                                $sector_selct_method = isset($jobsearch_plugin_options['job_sector_selct_method']) ? $jobsearch_plugin_options['job_sector_selct_method'] : '';
                                
                                $cand_sectors = wp_get_post_terms($candidate_id, 'sector');
                                $candidate_sector = isset($cand_sectors[0]->term_id) ? $cand_sectors[0]->term_id : '';
                                $skills_terms = get_terms(array(
                                    'taxonomy' => 'skill',
                                    'orderby' => 'count',
                                    'number' => $cand_sugg_skills_allow,
                                    'hide_empty' => false,
                                ));
                                //
                                $sectr_terms = $wpdb->get_col($wpdb->prepare("SELECT terms.term_id FROM $wpdb->terms AS terms"
                                    . " LEFT JOIN $wpdb->term_taxonomy AS term_tax ON(terms.term_id = term_tax.term_id) "
                                    . " WHERE term_tax.taxonomy=%s"
                                    . " ORDER BY terms.term_id DESC", 'sector'));

                                if (!empty($sectr_terms) && !is_wp_error($sectr_terms)) {
                                    $ismulti_secs = false;
                                    $cand_sectors = wp_get_post_terms($candidate_id, 'sector');
                                    $job_sector = isset($cand_sectors[0]->term_id) ? $cand_sectors[0]->term_id : '';
                                    if ($sector_selct_method == 'multi' || $sector_selct_method == 'multi_req') {
                                        //
                                        $job_sectors = wp_get_post_terms($candidate_id, 'sector');

                                        $saved_sectors = array();
                                        if (!empty($job_sectors)) {
                                            foreach ($job_sectors as $jobsector_obj) {
                                                $saved_sectors[] = $jobsector_obj->term_id;
                                            }
                                        }
                                        $ismulti_secs = true;
                                    }
                                    $show_all_skills = true;
                                    ob_start();
                                    ?>
                                    <div class="suggested-skills-con">
                                        <label><?php esc_html_e('Suggested Skills', 'wp-jobsearch') ?></label>
                                        <?php
                                        foreach ($sectr_terms as $sectr_termid) {
                                            $sector_jmeta = get_term_meta($sectr_termid, 'careerfy_frame_cat_fields', true);
                                            $sector_skills = isset($sector_jmeta['skills']) ? $sector_jmeta['skills'] : '';
                                            if (!empty($sector_skills)) {
                                                $show_sec_skills = false;
                                                if ($ismulti_secs) {
                                                    $show_sec_skills = in_array($sectr_termid, $saved_sectors) ? true : false;
                                                } else {
                                                    $show_sec_skills = $job_sector == $sectr_termid ? true : false;
                                                }
                                                if ($show_sec_skills == true) {
                                                    $show_all_skills = false;
                                                }
                                                ?>
                                                <ul class="suggested-skills suggested-skills-sector-<?php echo($sectr_termid) ?>" style="display: <?php echo ($show_sec_skills ? 'block' : 'none') ?>;">
                                                    <?php
                                                    $sector_skills_count = 1;
                                                    foreach ($sector_skills as $sector_skill_sid) {
                                                        $skill_term_obj = get_term_by('id', $sector_skill_sid, 'skill');
                                                        $skill_trmobj_name = $skill_term_obj->name;
                                                        $skill_trmobj_name = str_replace("'", "\'", $skill_trmobj_name);
                                                        ?>
                                                        <li class="skills-cloud"
                                                            onclick="return jobsearch_add_skill_tolist('<?php echo($skill_trmobj_name) ?>');"><?php echo jobsearch_esc_html($skill_term_obj->name) ?></li>
                                                        <?php
                                                        if ($sector_skills_count >= $cand_sugg_skills_allow) {
                                                            break;
                                                        }
                                                        $sector_skills_count++;
                                                    }
                                                    ?>
                                                </ul>
                                                <?php
                                            }
                                        }
                                        if ($show_all_skills) {
                                            ?>
                                            <ul class="suggested-skills all-suggs-skills">
                                                <?php
                                                foreach ($skills_terms as $skill_term) {
                                                    $skill_trm_name = $skill_term->name;
                                                    $skill_trm_name = str_replace("'", "\'", $skill_trm_name);
                                                    ?>
                                                    <li class="skills-cloud" onclick="return jobsearch_add_skill_tolist('<?php echo($skill_trm_name) ?>');"><?php echo($skill_term->name) ?></li>
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
                                    echo apply_filters('jobsearch_post_cand_sugg_skills_html', $html, $skills_terms);
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                    $skills_html = ob_get_clean();
                    echo apply_filters('jobsearch_candash_resms_skills_html', $skills_html, $candidate_id);
                }

                echo apply_filters('jobsearch_candidate_dash_resume_after_skills', '', $candidate_id);

                $resm_edu_oall_html = $resm_exp_oall_html = $resm_port_oall_html = $resm_skill_oall_html = $resm_lang_oall_html = $resm_award_oall_html = '';
                //
                if ($inopt_resm_education != 'off') {
                    ob_start();
                    ?>
                    <div class="jobsearch-candidate-resume-wrap education_title_skillid">
                        <?php
                        if ($user_pkg_limits::cand_field_is_locked('resmedu_defields')) {
                            ob_start();
                            ?>
                            <div class="jobsearch-candidate-title">
                                <h2>
                                    <i class="jobsearch-icon jobsearch-mortarboard"></i> <?php esc_html_e('Education', 'wp-jobsearch') ?>
                                </h2>
                            </div>
                            <?php echo($user_pkg_limits::cand_gen_locked_html()) ?>
                            <?php
                            $lock_field_cushtml = ob_get_clean();
                            $lock_field_html = $user_pkg_limits->cand_field_locked_html($lock_field_cushtml);
                            echo($lock_field_html);
                        } else {
                            ob_start();
                            ?>
                            <div class="jobsearch-candidate-title">
                                <h2>
                                    <i class="jobsearch-icon jobsearch-mortarboard"></i> <?php esc_html_e('Education', 'wp-jobsearch') ?>
                                    <a href="javascript:void(0)" class="jobsearch-resume-addbtn"><span
                                                class="fa fa-plus"></span> <?php esc_html_e('Add education', 'wp-jobsearch') ?>
                                    </a>
                                </h2>
                            </div>
                            <div class="jobsearch-add-popup jobsearch-add-resume-item-popup">
                                <span class="close-popup-item"><i class="fa fa-times"></i></span>
                                <script>
                                    jQuery(document).ready(function () {
                                        var today_Date = new Date().getDate();
                                        jQuery('#add-edu-date-start').datetimepicker({
                                            timepicker: false,
                                            format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                            maxDate: new Date(new Date().setDate(today_Date)),
                                            onSelectDate: function (ct, $i) {
                                                var normal_date = jobsearch_get_date_to_num_str(ct);
                                                jQuery('#add-edu-date-start-hiden').val(normal_date);
                                                var min_to_date = ct;
                                                jQuery('#add-edu-date-end').datetimepicker({
                                                    timepicker: false,
                                                    format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                                    onShow: function () {
                                                        this.setOptions({
                                                            minDate: min_to_date
                                                        })
                                                    },
                                                });
                                            },
                                        });
                                        jQuery('#add-edu-date-end').datetimepicker({
                                            timepicker: false,
                                            format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                            maxDate: new Date(new Date().setDate(today_Date)),
                                            onSelectDate: function (ct, $i) {
                                                var normal_date = jobsearch_get_date_to_num_str(ct);
                                                jQuery('#add-edu-date-end-hiden').val(normal_date);
                                                var max_from_date = ct;
                                                jQuery('#add-edu-date-start').datetimepicker({
                                                    timepicker: false,
                                                    format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                                    onShow: function () {
                                                        this.setOptions({
                                                            maxDate: max_from_date
                                                        })
                                                    },
                                                });
                                            },
                                        });
                                        //
                                        jQuery(document).on('click', '.cand-edu-prsntchkbtn', function () {
                                            var _this = jQuery(this);
                                            var thisu_id = _this.attr('data-id');
                                            if (_this.is(":checked")) {
                                                jQuery('.cand-edu-todatefield-' + thisu_id).hide();
                                                _this.parent('.cand-edu-prsntfield').find('input[type="hidden"]').val('on').trigger('change');
                                            } else {
                                                jQuery('.cand-edu-todatefield-' + thisu_id).show();
                                                _this.parent('.cand-edu-prsntfield').find('input[type="hidden"]').val('').trigger('change');
                                            }
                                        });
                                    });
                                </script>
                                <ul class="jobsearch-row jobsearch-employer-profile-form">
                                    <li class="jobsearch-column-12">
                                        <?php
                                        ob_start();
                                        ?>
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Title *', 'wp-jobsearch'), 'tooltip_candi_edu_title') ?>
                                        <?php
                                        $title_html = ob_get_clean();
                                        echo apply_filters('jobsearch_candash_resume_edutitle_label', $title_html);
                                        ?>
                                        <input id="add-edu-title" class="jobsearch-req-field" type="text" placeholder="<?php echo apply_filters('jobsearch_resmdash_educ_title_placholdr_txt', esc_html__('Masters of Business Administration (MBA)', 'wp-jobsearch')) ?>">
                                    </li>
                                    <?php echo apply_filters('Jobsearch_Cand_Education_studies_add', '', $candidate_id) ?>
                                    <li class="jobsearch-column-4">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Start Date *', 'wp-jobsearch'), 'tooltip_candi_edu_start_date') ?>
                                        <input id="add-edu-date-start" class="jobsearch-req-field" type="text">
                                        <input id="add-edu-date-start-hiden" type="hidden">
                                    </li>
                                    <li class="jobsearch-column-4 cand-edu-todatefield-0">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('End Date *', 'wp-jobsearch'), 'tooltip_candi_edu_end_date') ?>
                                        <input id="add-edu-date-end" type="text">
                                        <input id="add-edu-date-end-hiden" type="hidden">
                                    </li>
                                    <li class="jobsearch-column-4 cand-edu-prsntfield">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Present', 'wp-jobsearch'), 'tooltip_candi_edu_present') ?>
                                        <input class="cand-edu-prsntchkbtn" data-id="0" type="checkbox">
                                        <input id="add-edu-date-prsent" type="hidden">
                                    </li>
                                    <li class="jobsearch-column-12">
                                        <?php $institute = apply_filters('jobsearch_resmdash_educ_institute_title_txt', esc_html__('Institute *', 'wp-jobsearch')) ?>
                                        <?php echo jobsearch_add_field_label_tooltip($institute, 'tooltip_candi_edu_institute') ?>
                                        <input id="add-edu-institute" class="jobsearch-req-field" type="text" placeholder="<?php echo apply_filters('jobsearch_resmdash_educ_institute_placholdr_txt', esc_html__('University of London, UK', 'wp-jobsearch')) ?>">
                                    </li>
                                    <?php
                                    echo apply_filters('jobsearch_cand_dash_resume_edu_add_bfor_desc', '');
                                    ?>
                                    <li class="jobsearch-column-12">
                                        <?php $description = esc_html(_x('Description', 'Resume Education Description', 'wp-jobsearch')) ?>
                                        <?php echo jobsearch_add_field_label_tooltip($description, 'tooltip_candi_edu_desc') ?>
                                        <textarea id="add-edu-desc" placeholder="<?php echo apply_filters('jobsearch_resmdash_educ_desc_placholdr_txt', esc_html__('I have passed Master\'s in Business Adminisration, Major in Human Resources from the University of London, United Kingdom.', 'wp-jobsearch')) ?>" <?php echo apply_filters('jobsearch_candash_resume_edudesc_atts', ''); ?>></textarea>
                                    </li>
                                    <li class="jobsearch-column-12">
                                        <input id="<?php echo apply_filters('jobsearch_cand_dash_resume_edu_add_btnid', 'add-education-btn') ?>"
                                               type="submit"
                                               value="<?php esc_html_e('Add education', 'wp-jobsearch') ?>">
                                        <span class="edu-loding-msg"></span>
                                    </li>
                                </ul>
                            </div>
                            <?php
                            $edu_add_html = ob_get_clean();
                            echo apply_filters('jobsearch_cand_dash_resume_addedu_html', $edu_add_html, $candidate_id);
                            ob_start();
                            ?>
                            <div id="jobsearch-resume-edu-con" class="jobsearch-resume-education">
                                <ul class="jobsearch-row">
                                    <?php
                                    $exfield_list = get_post_meta($candidate_id, 'jobsearch_field_education_title', true);
                                    $exfield_list_val = get_post_meta($candidate_id, 'jobsearch_field_education_description', true);
                                    $education_academyfield_list = get_post_meta($candidate_id, 'jobsearch_field_education_academy', true);
                                    $education_start_datefield_list = get_post_meta($candidate_id, 'jobsearch_field_education_start_date', true);
                                    $education_end_datefield_list = get_post_meta($candidate_id, 'jobsearch_field_education_end_date', true);
                                    
                                    $start_datelist_hiden = get_post_meta($candidate_id, 'jobsearch_field_edu_start_date_hiden', true);
                                    $end_datelist_hiden = get_post_meta($candidate_id, 'jobsearch_field_edu_end_date_hiden', true);
                                    
                                    $education_prsnt_datefield_list = get_post_meta($candidate_id, 'jobsearch_field_education_date_prsnt', true);


                                    if (is_array($exfield_list) && sizeof($exfield_list) > 0) {

                                        $new_edu_filedsarr = array();
                                        $exfield_counter = 0;
                                        foreach ($exfield_list as $exfield) {

                                            $exfield_desc = isset($exfield_list_val[$exfield_counter]) ? $exfield_list_val[$exfield_counter] : '';
                                            $exfield_start_date = isset($education_start_datefield_list[$exfield_counter]) ? $education_start_datefield_list[$exfield_counter] : '';
                                            $exfield_end_date = isset($education_end_datefield_list[$exfield_counter]) ? $education_end_datefield_list[$exfield_counter] : '';
                                            
                                            $start_datefield_hiden = isset($start_datelist_hiden[$exfield_counter]) ? $start_datelist_hiden[$exfield_counter] : '';
                                            $end_datefield_hiden = isset($end_datelist_hiden[$exfield_counter]) ? $end_datelist_hiden[$exfield_counter] : '';
                                            
                                            $exfield_prsnt_field = isset($education_prsnt_datefield_list[$exfield_counter]) ? $education_prsnt_datefield_list[$exfield_counter] : '';
                                            $exfield_institute = isset($education_academyfield_list[$exfield_counter]) ? $education_academyfield_list[$exfield_counter] : '';

                                            if ($start_datefield_hiden != '') {
                                                $edu_sort_date = strtotime($start_datefield_hiden);
                                            } else {
                                                $edu_sort_date = 0;
                                                if ($exfield_start_date != '') {
                                                    $exfield_start_date = str_replace('/', '-', $exfield_start_date);
                                                    $edu_sort_date = strtotime($exfield_start_date);
                                                }
                                            }

                                            $new_exp_fileditm = array(
                                                'title' => $exfield,
                                                'desc' => $exfield_desc,
                                                'start_date' => $exfield_start_date,
                                                'end_date' => $exfield_end_date,
                                                'start_date_hiden' => $start_datefield_hiden,
                                                'end_date_hiden' => $end_datefield_hiden,
                                                'present' => $exfield_prsnt_field,
                                                'institute' => $exfield_institute,
                                                'sort_date' => $edu_sort_date,
                                            );
                                            $new_exp_fileditm = apply_filters('jobsearch_candash_eduction_list_before_sort', $new_exp_fileditm, $exfield_counter, $candidate_id);
                                            $new_edu_filedsarr[] = $new_exp_fileditm;

                                            $exfield_counter++;
                                        }

                                        //usort($new_edu_filedsarr, function($a, $b) {
                                        //return $b['sort_date'] <=> $a['sort_date'];
                                        //});
                                        usort($new_edu_filedsarr, function ($a, $b) {
                                            if ($a['sort_date'] == $b['sort_date']) {
                                                $ret_val = 0;
                                            }
                                            $ret_val = ($b['sort_date'] < $a['sort_date']) ? -1 : 1;
                                            return $ret_val;
                                        });
                                        
                                        //echo '<pre>';
                                        //var_dump($new_edu_filedsarr);
                                        //echo '</pre>';

                                        $exfield_counter = 0;
                                        foreach ($new_edu_filedsarr as $new_exfield) {
                                            $rand_num = rand(1000000, 99999999);


                                            $exfield = isset($new_exfield['title']) ? $new_exfield['title'] : '';
                                            $exfield_val = isset($new_exfield['desc']) ? $new_exfield['desc'] : '';
                                            $education_start_datefield_val = isset($new_exfield['start_date']) ? $new_exfield['start_date'] : '';
                                            $education_end_datefield_val = isset($new_exfield['end_date']) ? $new_exfield['end_date'] : '';
                                            $education_prsnt_datefield_val = isset($new_exfield['present']) ? $new_exfield['present'] : '';
                                            $education_academyfield_val = isset($new_exfield['institute']) ? $new_exfield['institute'] : '';
                                            
                                            $start_datefield_hiden = isset($new_exfield['start_date_hiden']) ? $new_exfield['start_date_hiden'] : '';
                                            $end_datefield_hiden = isset($new_exfield['end_date_hiden']) ? $new_exfield['end_date_hiden'] : '';

                                            $exfield = jobsearch_esc_html($exfield);
                                            $exfield_val = jobsearch_esc_html($exfield_val);
                                            $education_start_datefield_val = ($education_start_datefield_val);
                                            $education_end_datefield_val = ($education_end_datefield_val);
                                            $education_prsnt_datefield_val = jobsearch_esc_html($education_prsnt_datefield_val);
                                            $education_academyfield_val = jobsearch_esc_html($education_academyfield_val);
                                            ?>
                                            <li class="jobsearch-column-12 resume-list-item resume-list-edu">
                                                <div class="jobsearch-resume-education-wrap">
                                                    <small><?php echo ($education_start_datefield_val) ?>
                                                        - <?php echo($education_prsnt_datefield_val == 'on' ? 'Present' : '') ?><?php echo($education_end_datefield_val != '' && $education_prsnt_datefield_val != 'on' ? $education_end_datefield_val : '') ?></small>
                                                    <?php echo apply_filters('Jobsearch_candidate_Education_studies_box_html', '', $candidate_id, $exfield_counter, '', $new_exfield) ?>
                                                    <h2 class="jobsearch-pst-title">
                                                        <a><?php echo jobsearch_esc_html($exfield) ?></a></h2>
                                                    <span><?php echo jobsearch_esc_html($education_academyfield_val) ?></span>
                                                </div>
                                                <div class="jobsearch-resume-education-btn">
                                                    <a href="javascript:void(0);"
                                                       class="jobsearch-icon jobsearch-edit jobsearch-tooltipcon update-resume-item"
                                                       title="<?php esc_html_e('Update', 'wp-jobsearch') ?>"></a>
                                                    <a href="javascript:void(0);"
                                                       class="jobsearch-icon jobsearch-rubbish jobsearch-tooltipcon <?php echo(apply_filters('jobsearch_candash_resume_edulist_itmdelclass', 'del-resume-item', $rand_num)) ?>"
                                                       data-id="<?php echo($rand_num) ?>"
                                                       title="<?php esc_html_e('Delete', 'wp-jobsearch') ?>"></a>
                                                </div>
                                                <div class="jobsearch-add-popup jobsearch-update-resume-items-sec">
                                                    <span class="close-popup-item"><i class="fa fa-times"></i></span>
                                                    <ul class="jobsearch-row jobsearch-employer-profile-form">
                                                        <li class="jobsearch-column-12">
                                                            <?php
                                                            ob_start();
                                                            ?>
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('Title *', 'wp-jobsearch'), 'tooltip_candi_edu_title') ?>
                                                            <?php
                                                            $title_html = ob_get_clean();
                                                            echo apply_filters('jobsearch_candash_resume_edutitle_label', $title_html);
                                                            ?>
                                                            <input name="jobsearch_field_education_title[]" type="text"
                                                                   value="<?php echo jobsearch_esc_html($exfield) ?>" placeholder="<?php echo apply_filters('jobsearch_resmdash_educ_title_placholdr_txt', esc_html__('Masters of Business Administration (MBA)', 'wp-jobsearch')) ?>">
                                                        </li>
                                                        <?php echo apply_filters('Jobsearch_Cand_Education_studies_update', '', $candidate_id, $exfield_counter, $new_exfield) ?>
                                                        <li class="jobsearch-column-4">
                                                           
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('Start Date *', 'wp-jobsearch'), 'tooltip_candi_edu_start_date') ?>
                                                            <input name="jobsearch_field_education_start_date[]" id="date-start-<?php echo $rand_num ?>"
                                                                   type="text"
                                                                   value="<?php echo ($education_start_datefield_val) ?>">
                                                            <input id="date-start-hiden-<?php echo($rand_num) ?>" type="hidden" name="jobsearch_field_edu_start_date_hiden[]" value="<?php echo ($start_datefield_hiden) ?>">
                                                        </li>
                                                        <li class="jobsearch-column-4 cand-edu-todatefield-<?php echo($rand_num) ?>" <?php echo($education_prsnt_datefield_val == 'on' ? 'style="display: none;"' : '') ?>>
                                                             <?php echo jobsearch_add_field_label_tooltip(esc_html__('End Date *', 'wp-jobsearch'), 'tooltip_candi_edu_end_date') ?>
                                                            <input name="jobsearch_field_education_end_date[]"
                                                                   type="text" id="date-end-<?php echo $rand_num ?>"
                                                                   value="<?php echo ($education_end_datefield_val) ?>">
                                                            <input id="date-end-hiden-<?php echo($rand_num) ?>" type="hidden" name="jobsearch_field_edu_end_date_hiden[]" value="<?php echo ($start_datefield_hiden) ?>">
                                                        </li>
                                                        <li class="jobsearch-column-4 cand-edu-prsntfield">
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('Present', 'wp-jobsearch'), 'tooltip_candi_edu_present') ?>
                                                            <input class="cand-edu-prsntchkbtn"
                                                                   data-id="<?php echo($rand_num) ?>"
                                                                   type="checkbox" <?php echo($education_prsnt_datefield_val == 'on' ? 'checked' : '') ?>>
                                                            <input name="jobsearch_field_education_date_prsnt[]"
                                                                   type="hidden"
                                                                   value="<?php echo($education_prsnt_datefield_val) ?>">
                                                        </li>
                                                        <li class="jobsearch-column-12">
                                                            <?php $institute = apply_filters('jobsearch_resmdash_educ_institute_title_txt', esc_html__('Institute *', 'wp-jobsearch')) ?>
                                                            <?php echo jobsearch_add_field_label_tooltip($institute, 'tooltip_candi_edu_institute') ?>
                                                            <input name="jobsearch_field_education_academy[]"
                                                                   type="text" placeholder="<?php echo apply_filters('jobsearch_resmdash_educ_institute_placholdr_txt', esc_html__('University of London, UK', 'wp-jobsearch')) ?>"
                                                                   value="<?php echo jobsearch_esc_html($education_academyfield_val) ?>">
                                                        </li>
                                                        <?php
                                                        echo apply_filters('jobsearch_cand_dash_resume_edu_updt_bfor_desc', '', $candidate_id, $exfield_counter, $new_exfield);
                                                        ?>
                                                        <li class="jobsearch-column-12">
                                                            <?php $description = esc_html(_x('Description', 'Resume Education Description', 'wp-jobsearch')) ?>
                                                            <?php echo jobsearch_add_field_label_tooltip($description, 'tooltip_candi_edu_desc') ?>
                                                            <textarea
                                                                name="jobsearch_field_education_description[]" placeholder="<?php echo apply_filters('jobsearch_resmdash_educ_desc_placholdr_txt', esc_html__('I have passed Master\'s in Business Adminisration, Major in Human Resources from the University of London, United Kingdom.', 'wp-jobsearch')) ?>" <?php echo apply_filters('jobsearch_candash_resume_edudesc_atts', ''); ?>><?php echo jobsearch_esc_html($exfield_val) ?></textarea>
                                                        </li>
                                                        <li class="jobsearch-column-12">
                                                            <input class="update-resume-list-btn" type="submit"
                                                                   value="<?php esc_html_e('Update', 'wp-jobsearch') ?>">
                                                        </li>
                                                    </ul>
                                                    <script>
                                                        jQuery(document).ready(function () {
                                                            var today_Date_<?php echo($rand_num) ?> = new Date().getDate();
                                                            jQuery('#date-start-<?php echo($rand_num) ?>').datetimepicker({
                                                                timepicker: false,
                                                                format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                                                maxDate: new Date(new Date().setDate(today_Date_<?php echo($rand_num) ?>)),
                                                                onSelectDate: function (ct, $i) {
                                                                    var normal_date = jobsearch_get_date_to_num_str(ct);
                                                                    jQuery('#date-start-hiden-<?php echo($rand_num) ?>').val(normal_date);
                                                                    var min_to_date = ct;
                                                                    jQuery('#date-end-<?php echo($rand_num) ?>').datetimepicker({
                                                                        timepicker: false,
                                                                        format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                                                        onShow: function () {
                                                                            this.setOptions({
                                                                                minDate: min_to_date
                                                                            })
                                                                        },
                                                                    });
                                                                },
                                                            });
                                                            jQuery('#date-end-<?php echo($rand_num) ?>').datetimepicker({
                                                                timepicker: false,
                                                                format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                                                maxDate: new Date(new Date().setDate(today_Date_<?php echo($rand_num) ?>)),
                                                                onSelectDate: function (ct, $i) {
                                                                    var normal_date = jobsearch_get_date_to_num_str(ct);
                                                                    jQuery('#date-end-hiden-<?php echo($rand_num) ?>').val(normal_date);
                                                                    var max_from_date = ct;
                                                                    jQuery('#date-start-<?php echo($rand_num) ?>').datetimepicker({
                                                                        timepicker: false,
                                                                        format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                                                        onShow: function () {
                                                                            this.setOptions({
                                                                                maxDate: max_from_date
                                                                            })
                                                                        },
                                                                    });
                                                                },
                                                            });
                                                        });
                                                    </script>
                                                </div>
                                            </li>
                                            <?php
                                            $exfield_counter++;
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                            <?php
                            $edu_list_html = ob_get_clean();
                            echo apply_filters('jobsearch_cand_dash_resume_eduslist_html', $edu_list_html, $candidate_id);
                        }
                        ?>
                    </div>
                    <?php
                    $resm_edu_oall_html = ob_get_clean();
                    $resm_edu_oall_html = apply_filters('jobsearch_candidate_dash_resume_educ_html', $resm_edu_oall_html, $candidate_id);
                }

                if ($inopt_resm_experience != 'off') {
                    ob_start();
                    ?>
                    <div class="jobsearch-candidate-resume-wrap experience_title_skillid">
                        <?php
                        if ($user_pkg_limits::cand_field_is_locked('resmexp_defields')) {
                            ob_start();
                            ?>
                            <div class="jobsearch-candidate-title">
                                <h2>
                                    <?php
                                    ob_start();
                                    ?>
                                    <i class="jobsearch-icon jobsearch-social-media"></i> <?php esc_html_e('Experience', 'wp-jobsearch') ?>
                                    <?php
                                    $title_html = ob_get_clean();
                                    echo apply_filters('jobsearch_candash_resume_expmain_label', $title_html);
                                    ?>
                                </h2>
                            </div>
                            <?php echo($user_pkg_limits::cand_gen_locked_html()) ?>
                            <?php
                            $lock_field_cushtml = ob_get_clean();
                            $lock_field_html = $user_pkg_limits->cand_field_locked_html($lock_field_cushtml);
                            echo($lock_field_html);
                        } else {
                            ob_start();
                            ?>
                            <div class="jobsearch-candidate-title">
                                <h2>
                                    <?php
                                    ob_start();
                                    ?>
                                    <i class="jobsearch-icon jobsearch-social-media"></i> <?php esc_html_e('Experience', 'wp-jobsearch') ?>
                                    <?php
                                    $title_html = ob_get_clean();
                                    echo apply_filters('jobsearch_candash_resume_expmain_label', $title_html);
                                    ?>
                                    <a href="javascript:void(0)" class="jobsearch-resume-addbtn"><span
                                                class="fa fa-plus"></span> <?php esc_html_e('Add experience', 'wp-jobsearch') ?>
                                    </a>
                                </h2>
                            </div>

                            <div class="jobsearch-add-popup jobsearch-add-resume-item-popup">
                                <span class="close-popup-item"><i class="fa fa-times"></i></span>
                                <script>
                                    jQuery(document).ready(function () {
                                        var today_Date = new Date().getDate();
                                        jQuery('#add-expr-date-start').datetimepicker({
                                            timepicker: false,
                                            format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                            maxDate: new Date(new Date().setDate(today_Date)),
                                            onSelectDate: function (ct, $i) {
                                                var normal_date = jobsearch_get_date_to_num_str(ct);
                                                jQuery('#add-expr-date-start-hiden').val(normal_date);
                                                var min_to_date = ct;
                                                jQuery('#add-expr-date-end').datetimepicker({
                                                    timepicker: false,
                                                    format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                                    onShow: function () {
                                                        this.setOptions({
                                                            minDate: min_to_date
                                                        })
                                                    },
                                                });
                                            },
                                        });
                                        jQuery('#add-expr-date-end').datetimepicker({
                                            timepicker: false,
                                            format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                            maxDate: new Date(new Date().setDate(today_Date)),
                                            onSelectDate: function (ct, $i) {
                                                var normal_date = jobsearch_get_date_to_num_str(ct);
                                                jQuery('#add-expr-date-end-hiden').val(normal_date);
                                                var max_from_date = ct;
                                                jQuery('#add-expr-date-start').datetimepicker({
                                                    timepicker: false,
                                                    format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                                    onShow: function () {
                                                        this.setOptions({
                                                            maxDate: max_from_date
                                                        })
                                                    },
                                                });
                                            },
                                        });
                                        //
                                        jQuery(document).on('click', '.cand-expr-prsntchkbtn', function () {
                                            var _this = jQuery(this);
                                            var thisu_id = _this.attr('data-id');
                                            if (_this.is(":checked")) {
                                                jQuery('.cand-expr-todatefield-' + thisu_id).hide();
                                                _this.parent('.cand-expr-prsntfield').find('input[type="hidden"]').val('on').trigger('change');
                                            } else {
                                                jQuery('.cand-expr-todatefield-' + thisu_id).show();
                                                _this.parent('.cand-expr-prsntfield').find('input[type="hidden"]').val('').trigger('change');
                                            }
                                        });
                                    });
                                </script>
                                <ul class="jobsearch-row jobsearch-employer-profile-form">
                                    <li class="jobsearch-column-12">
                                        <?php $title = apply_filters('jobsearch_resmdash_experience_title_txt', esc_html__('Title *', 'wp-jobsearch')) ?>
                                        <?php echo jobsearch_add_field_label_tooltip($title, 'tooltip_candi_experience_title') ?>
                                        <input id="add-expr-title" class="jobsearch-req-field" type="text" placeholder="<?php echo apply_filters('jobsearch_resmdash_experience_title_placholdr_txt', esc_html__('Marketing Manager', 'wp-jobsearch')) ?>">
                                    </li>

                                    <?php echo apply_filters('Jobsearch_Cand_sectors', '', $candidate_id); ?>

                                    <li class="jobsearch-column-4">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Start Date *', 'wp-jobsearch'), 'tooltip_candi_experience_start_date') ?>
                                        <input id="add-expr-date-start" class="jobsearch-req-field" type="text">
                                        <input id="add-expr-date-start-hiden" type="hidden">
                                    </li>
                                    <li class="jobsearch-column-4 cand-expr-todatefield-0">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('End Date *', 'wp-jobsearch'), 'tooltip_candi_experience_end_date') ?>
                                        <input id="add-expr-date-end" type="text">
                                        <input id="add-expr-date-end-hiden" type="hidden">
                                    </li>
                                    <li class="jobsearch-column-4 cand-expr-prsntfield">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Present', 'wp-jobsearch'), 'tooltip_candi_experience_present') ?>
                                        <input class="cand-expr-prsntchkbtn" data-id="0" type="checkbox">
                                        <input id="add-expr-date-prsent" type="hidden">
                                    </li>
                                    <li class="jobsearch-column-12">
                                        <?php
                                        ob_start();
                                        ?>
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Company *', 'wp-jobsearch'), 'tooltip_candi_experience_company') ?>
                                        <?php
                                        $title_html = ob_get_clean();
                                        echo apply_filters('jobsearch_candash_resume_expcompny_label', $title_html);
                                        ?>
                                        <input id="add-expr-company" class="jobsearch-req-field" type="text" placeholder="<?php echo apply_filters('jobsearch_resmdash_experience_copmny_placholdr_txt', esc_html__('Qatar Airways', 'wp-jobsearch')) ?>">
                                    </li>
                                    <?php
                                    echo apply_filters('jobsearch_cand_dash_resume_expr_add_bfor_desc', '');
                                    ?>
                                    <li class="jobsearch-column-12">
                                        <?php
                                        ob_start();
                                        ?>
                                        <?php $description = esc_html(_x('Description', 'Resume Experience Description', 'wp-jobsearch')) ?>
                                        <?php echo jobsearch_add_field_label_tooltip($description, 'tooltip_candi_experience_desc') ?>
                                        <?php
                                        $title_html = ob_get_clean();
                                        echo apply_filters('jobsearch_candash_resume_expdesc_label', $title_html);
                                        
                                        ob_start();
                                        ?>
                                        <textarea id="add-expr-desc" placeholder="<?php echo apply_filters('jobsearch_resmdash_experience_desc_placholdr_txt', esc_html__('Greeting customers and helping them with their inquiries or concerns. Proposed and gained the
company\'s Main Board\'s acceptance of revised bonus schemes for sales staff. Reduced costs by
merging software technologies through different departments.', 'wp-jobsearch')) ?>" <?php echo apply_filters('jobsearch_candash_resume_expdesc_atts', '') ?>></textarea>
                                        <?php
                                        $desc_html = ob_get_clean();
                                        echo apply_filters('jobsearch_candash_resm_expdesc_add_field', $desc_html);
                                        ?>
                                    </li>
                                    <li class="jobsearch-column-12">
                                        <input id="<?php echo apply_filters('jobsearch_cand_dash_resume_expr_add_btnid', 'add-experience-btn') ?>"
                                               type="submit"
                                               value="<?php esc_html_e('Add experience', 'wp-jobsearch') ?>">
                                        <span class="expr-loding-msg edu-loding-msg"></span>
                                    </li>
                                </ul>
                            </div>
                            <?php
                            $res_exp_html = ob_get_clean();
                            echo apply_filters('jobsearch_candidate_dash_resume_expadd_html', $res_exp_html, $candidate_id);

                            ob_start();
                            ?>
                            <div id="jobsearch-resume-expr-con" class="jobsearch-resume-education">
                                <ul class="jobsearch-row">
                                    <?php

                                    $exfield_list = get_post_meta($candidate_id, 'jobsearch_field_experience_title', true);
                                    $exfield_list_val = get_post_meta($candidate_id, 'jobsearch_field_experience_description', true);
                                    $experience_start_datefield_list = get_post_meta($candidate_id, 'jobsearch_field_experience_start_date', true);
                                    $experience_end_datefield_list = get_post_meta($candidate_id, 'jobsearch_field_experience_end_date', true);
                                    
                                    $start_datelist_hiden = get_post_meta($candidate_id, 'jobsearch_field_exp_start_date_hiden', true);
                                    $end_datelist_hiden = get_post_meta($candidate_id, 'jobsearch_field_exp_end_date_hiden', true);
                                    
                                    $experience_prsnt_datefield_list = get_post_meta($candidate_id, 'jobsearch_field_experience_date_prsnt', true);
                                    $experience_company_field_list = get_post_meta($candidate_id, 'jobsearch_field_experience_company', true);

                                    $cand_work_area_id = 0;
                                    $cand_specialities_id = 0;
                                    $cand_work_area_id = get_post_meta($candidate_id, 'jobsearch_field_cand_work_area', true);
                                    $cand_specialities_id = get_post_meta($candidate_id, 'jobsearch_field_cand_specialities', true);

                                    if (is_array($exfield_list) && sizeof($exfield_list) > 0) {

                                        $new_exp_filedsarr = array();
                                        $exfield_counter = 0;
                                        foreach ($exfield_list as $exfield) {

                                            $exfield_desc = isset($exfield_list_val[$exfield_counter]) ? $exfield_list_val[$exfield_counter] : '';
                                            $exfield_start_date = isset($experience_start_datefield_list[$exfield_counter]) ? $experience_start_datefield_list[$exfield_counter] : '';
                                            $exfield_end_date = isset($experience_end_datefield_list[$exfield_counter]) ? $experience_end_datefield_list[$exfield_counter] : '';
                                            
                                            $start_datefield_hiden = isset($start_datelist_hiden[$exfield_counter]) ? $start_datelist_hiden[$exfield_counter] : '';
                                            $end_datefield_hiden = isset($end_datelist_hiden[$exfield_counter]) ? $end_datelist_hiden[$exfield_counter] : '';
                                            
                                            $exfield_prsnt_field = isset($experience_prsnt_datefield_list[$exfield_counter]) ? $experience_prsnt_datefield_list[$exfield_counter] : '';
                                            $exfield_company = isset($experience_company_field_list[$exfield_counter]) ? $experience_company_field_list[$exfield_counter] : '';


                                            if ($start_datefield_hiden != '') {
                                                $exp_sort_date = strtotime($start_datefield_hiden);
                                            } else {
                                                $exp_sort_date = 0;
                                                if ($exfield_start_date != '') {
                                                    $exfield_start_date = str_replace('/', '-', $exfield_start_date);
                                                    $exp_sort_date = strtotime($exfield_start_date);
                                                }
                                            }

                                            $new_exp_fileditm = array(
                                                'title' => $exfield,
                                                'desc' => $exfield_desc,
                                                'start_date' => $exfield_start_date,
                                                'end_date' => $exfield_end_date,
                                                'start_date_hiden' => $start_datefield_hiden,
                                                'end_date_hiden' => $end_datefield_hiden,
                                                'present' => $exfield_prsnt_field,
                                                'company' => $exfield_company,
                                                'sort_date' => $exp_sort_date,
                                            );
                                            $new_exp_fileditm = apply_filters('jobsearch_candash_exprince_list_before_sort', $new_exp_fileditm, $exfield_counter, $candidate_id);
                                            $new_exp_filedsarr[] = $new_exp_fileditm;
                                            $exfield_counter++;
                                        }

                                        //usort($new_exp_filedsarr, function($a, $b) {
                                        //return $b['sort_date'] <=> $a['sort_date'];
                                        //});
                                        usort($new_exp_filedsarr, function ($a, $b) {
                                            if ($a['sort_date'] == $b['sort_date']) {
                                                $ret_val = 0;
                                            }
                                            $ret_val = ($b['sort_date'] < $a['sort_date']) ? -1 : 1;
                                            return $ret_val;
                                        });

                                        $exfield_counter = 0;
                                        foreach ($new_exp_filedsarr as $new_exfield) {
                                            $rand_num = rand(1000000, 99999999);

                                            $exfield = isset($new_exfield['title']) ? $new_exfield['title'] : '';
                                            $exfield_val = isset($new_exfield['desc']) ? $new_exfield['desc'] : '';
                                            $experience_start_datefield_val = isset($new_exfield['start_date']) ? $new_exfield['start_date'] : '';
                                            $experience_end_datefield_val = isset($new_exfield['end_date']) ? $new_exfield['end_date'] : '';
                                            $experience_prsnt_datefield_val = isset($new_exfield['present']) ? $new_exfield['present'] : '';
                                            $experience_end_companyfield_val = isset($new_exfield['company']) ? $new_exfield['company'] : '';
                                            $start_datefield_hiden = isset($new_exfield['start_date_hiden']) ? $new_exfield['start_date_hiden'] : '';
                                            $end_datefield_hiden = isset($new_exfield['end_date_hiden']) ? $new_exfield['end_date_hiden'] : '';

                                            $exfield = jobsearch_esc_html($exfield);
                                            $exfield_val = apply_filters('the_content', $exfield_val);
                                            $exfield_val = jobsearch_esc_wp_editor($exfield_val);
                                            $experience_start_datefield_val = ($experience_start_datefield_val);
                                            $experience_end_datefield_val = ($experience_end_datefield_val);
                                            $experience_prsnt_datefield_val = jobsearch_esc_html($experience_prsnt_datefield_val);
                                            $experience_end_companyfield_val = jobsearch_esc_html($experience_end_companyfield_val);
                                            ?>
                                            <li class="jobsearch-column-12 resume-list-item resume-list-exp">
                                                <div class="jobsearch-resume-education-wrap">
                                                    <small><?php echo($experience_start_datefield_val) ?>
                                                        - <?php echo($experience_prsnt_datefield_val == 'on' ? 'Present' : '') ?><?php echo($experience_end_datefield_val != '' && $experience_prsnt_datefield_val != 'on' ? $experience_end_datefield_val : '') ?></small>
                                                    <?php echo apply_filters('jobsearch_candidate_filter_sector_exp_box_html', '', $cand_work_area_id, $cand_specialities_id, $exfield_counter, $new_exfield); ?>
                                                    <h2 class="jobsearch-pst-title"><a><?php echo($exfield) ?></a></h2>
                                                    <span><?php echo($experience_end_companyfield_val) ?></span>
                                                </div>
                                                <div class="jobsearch-resume-education-btn">
                                                    <a href="javascript:void(0);"
                                                       class="jobsearch-icon jobsearch-edit jobsearch-tooltipcon update-resume-item"
                                                       title="<?php esc_html_e('Update', 'wp-jobsearch') ?>"></a>
                                                    <a href="javascript:void(0);"
                                                       class="jobsearch-icon jobsearch-rubbish jobsearch-tooltipcon <?php echo(apply_filters('jobsearch_candash_resume_explist_itmdelclass', 'del-resume-item', $rand_num)) ?>"
                                                       data-id="<?php echo($rand_num) ?>"
                                                       title="<?php esc_html_e('Delete', 'wp-jobsearch') ?>"></a>
                                                </div>
                                                <div class="jobsearch-add-popup jobsearch-update-resume-items-sec">
                                                    <span class="close-popup-item"><i class="fa fa-times"></i></span>
                                                    <ul class="jobsearch-row jobsearch-employer-profile-form">
                                                        <li class="jobsearch-column-12">
                                                            <?php $title = apply_filters('jobsearch_resmdash_experience_title_txt', esc_html__('Title *', 'wp-jobsearch')) ?>
                                                            <?php echo jobsearch_add_field_label_tooltip($title, 'tooltip_candi_experience_title') ?>
                                                            <input name="jobsearch_field_experience_title[]" type="text" value="<?php echo($exfield) ?>" placeholder="<?php echo apply_filters('jobsearch_resmdash_experience_title_placholdr_txt', esc_html__('Marketing Manager', 'wp-jobsearch')) ?>">
                                                        </li>

                                                        <?php echo apply_filters('Jobsearch_Cand_sectors_update', '', $candidate_id, $exfield_counter, $new_exfield); ?>

                                                        <li class="jobsearch-column-4">
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('Start Date *', 'wp-jobsearch'), 'tooltip_candi_experience_start_date') ?>
                                                            <input id="date-start-<?php echo($rand_num) ?>"
                                                                   name="jobsearch_field_experience_start_date[]"
                                                                   type="text"
                                                                   value="<?php echo ($experience_start_datefield_val) ?>">
                                                            <input id="date-start-hiden-<?php echo($rand_num) ?>" type="hidden" name="jobsearch_field_exp_start_date_hiden[]" value="<?php echo ($start_datefield_hiden) ?>">
                                                        </li>
                                                        <li class="jobsearch-column-4 cand-expr-todatefield-<?php echo($rand_num) ?>" <?php echo($experience_prsnt_datefield_val == 'on' ? 'style="display: none;"' : '') ?>>
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('End Date *', 'wp-jobsearch'), 'tooltip_candi_experience_end_date') ?>
                                                            <input id="date-end-<?php echo($rand_num) ?>"
                                                                   name="jobsearch_field_experience_end_date[]"
                                                                   type="text"
                                                                   value="<?php echo ($experience_end_datefield_val) ?>">
                                                            <input id="date-end-hiden-<?php echo($rand_num) ?>" type="hidden" name="jobsearch_field_exp_end_date_hiden[]" value="<?php echo ($end_datefield_hiden) ?>">
                                                        </li>
                                                        <li class="jobsearch-column-4 cand-expr-prsntfield">
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('Present', 'wp-jobsearch'), 'tooltip_candi_experience_present') ?>
                                                            <input class="cand-expr-prsntchkbtn"
                                                                   data-id="<?php echo($rand_num) ?>"
                                                                   type="checkbox" <?php echo($experience_prsnt_datefield_val == 'on' ? 'checked' : '') ?>>
                                                            <input name="jobsearch_field_experience_date_prsnt[]"
                                                                   type="hidden"
                                                                   value="<?php echo($experience_prsnt_datefield_val) ?>">
                                                        </li>
                                                        <li class="jobsearch-column-12">
                                                            <?php
                                                            ob_start();
                                                            ?>
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('Company *', 'wp-jobsearch'), 'tooltip_candi_experience_company') ?>
                                                            <?php
                                                            $title_html = ob_get_clean();
                                                            echo apply_filters('jobsearch_candash_resume_expcompny_label', $title_html);
                                                            ?>
                                                            <input name="jobsearch_field_experience_company[]"
                                                                   type="text" placeholder="<?php echo apply_filters('jobsearch_resmdash_experience_copmny_placholdr_txt', esc_html__('Qatar Airways', 'wp-jobsearch')) ?>"
                                                                   value="<?php echo($experience_end_companyfield_val) ?>">
                                                        </li>
                                                        <?php
                                                        echo apply_filters('jobsearch_cand_dash_resume_expr_updt_bfor_desc', '', $candidate_id, $exfield_counter, $new_exfield);
                                                        ?>
                                                        <li class="jobsearch-column-12">
                                                            <?php
                                                            ob_start();
                                                            ?>
                                                            <?php $description = esc_html(_x('Description', 'Resume Experience Description', 'wp-jobsearch')) ?>
                                                            <?php echo jobsearch_add_field_label_tooltip($description, 'tooltip_candi_experience_desc') ?>
                                                            <?php
                                                            $title_html = ob_get_clean();
                                                            echo apply_filters('jobsearch_candash_resume_expdesc_label', $title_html);
                                                            ob_start();
                                                            ?>
                                                            <textarea name="jobsearch_field_experience_description[]" placeholder="<?php echo apply_filters('jobsearch_resmdash_experience_desc_placholdr_txt', esc_html__('Greeting customers and helping them with their inquiries or concerns. Proposed and gained the
company\'s Main Board\'s acceptance of revised bonus schemes for sales staff. Reduced costs by
merging software technologies through different departments.', 'wp-jobsearch')) ?>" <?php echo apply_filters('jobsearch_candash_resume_expdesc_atts', '') ?>><?php echo($exfield_val) ?></textarea>
                                                            <?php
                                                            $desc_html = ob_get_clean();
                                                            echo apply_filters('jobsearch_candash_resm_expdesc_field', $desc_html, $exfield_val);
                                                            ?>
                                                        </li>
                                                        <li class="jobsearch-column-12">
                                                            <input class="update-resume-list-btn" type="submit" value="<?php esc_html_e('Update', 'wp-jobsearch') ?>">
                                                        </li>
                                                    </ul>
                                                    <script>
                                                        jQuery(document).ready(function () {
                                                            var today_Date_<?php echo($rand_num) ?> = new Date().getDate();
                                                            jQuery('#date-start-<?php echo($rand_num) ?>').datetimepicker({
                                                                timepicker: false,
                                                                format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                                                maxDate: new Date(new Date().setDate(today_Date_<?php echo($rand_num) ?>)),
                                                                onSelectDate: function (ct, $i) {
                                                                    var normal_date = jobsearch_get_date_to_num_str(ct);
                                                                    jQuery('#date-start-hiden-<?php echo($rand_num) ?>').val(normal_date);
                                                                    var min_to_date = ct;
                                                                    jQuery('#date-end-<?php echo($rand_num) ?>').datetimepicker({
                                                                        timepicker: false,
                                                                        format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                                                        onShow: function () {
                                                                            this.setOptions({
                                                                                minDate: min_to_date
                                                                            })
                                                                        },
                                                                    });
                                                                },
                                                            });
                                                            jQuery('#date-end-<?php echo($rand_num) ?>').datetimepicker({
                                                                timepicker: false,
                                                                format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                                                maxDate: new Date(new Date().setDate(today_Date_<?php echo($rand_num) ?>)),
                                                                onSelectDate: function (ct, $i) {
                                                                    var normal_date = jobsearch_get_date_to_num_str(ct);
                                                                    jQuery('#date-end-hiden-<?php echo($rand_num) ?>').val(normal_date);
                                                                    var max_from_date = ct;
                                                                    jQuery('#date-start-<?php echo($rand_num) ?>').datetimepicker({
                                                                        timepicker: false,
                                                                        format: '<?php echo apply_filters('jobsearch_datepicker_custom_format', get_option('date_format')) ?>',
                                                                        onShow: function () {
                                                                            this.setOptions({
                                                                                maxDate: max_from_date
                                                                            })
                                                                        },
                                                                    });
                                                                },
                                                            });
                                                        });
                                                    </script>
                                                </div>
                                            </li>
                                            <?php
                                            $exfield_counter++;
                                        }
                                    }
                                    ?>

                                </ul>
                            </div>
                            <?php
                            $res_exp_html = ob_get_clean();
                            echo apply_filters('jobsearch_candidate_dash_resume_expslist_html', $res_exp_html, $candidate_id);
                        }
                        ?>
                    </div>
                    <?php
                    $resm_exp_oall_html = ob_get_clean();
                    $resm_exp_oall_html = apply_filters('jobsearch_cand_dash_resume_exp_oall', $resm_exp_oall_html, $candidate_id);
                }
                if ($inopt_resm_portfolio != 'off') {
                    if ($user_pkg_limits::cand_field_is_locked('resmport_defields')) {
                        ob_start();
                        ?>
                        <div class="jobsearch-candidate-title portfolio_title_skillid">
                            <h2>
                                <i class="jobsearch-icon jobsearch-social-media"></i> <?php esc_html_e('Portfolio', 'wp-jobsearch') ?>
                            </h2>
                        </div>
                        <?php echo($user_pkg_limits::cand_gen_locked_html()) ?>
                        <?php
                        $lock_field_cushtml = ob_get_clean();
                        $lock_field_html = $user_pkg_limits->cand_field_locked_html($lock_field_cushtml);
                        echo($lock_field_html);
                    } else {
                        ob_start();
                        ?>
                        <div class="jobsearch-candidate-resume-wrap portfolio_title_skillid">
                            <?php
                            ob_start();
                            ?>
                            <div class="jobsearch-candidate-title">
                                <h2>
                                    <i class="jobsearch-icon jobsearch-briefcase"></i> <?php esc_html_e('Portfolio', 'wp-jobsearch') ?>
                                    <a href="javascript:void(0)"
                                       class="jobsearch-resume-addbtn jobsearch-portfolio-add-btn"><span
                                                class="fa fa-plus"></span> <?php esc_html_e('Add Portfolio', 'wp-jobsearch') ?>
                                    </a>
                                </h2>
                            </div>
                            <div class="jobsearch-add-popup jobsearch-add-resume-item-popup">
                                <span class="close-popup-item"><i class="fa fa-times"></i></span>
                                <ul class="jobsearch-row jobsearch-employer-profile-form">
                                    <li class="jobsearch-column-12">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Title *', 'wp-jobsearch'), 'tooltip_candi_port_title') ?>
                                        <input id="add-portfolio-title" class="jobsearch-req-field" placeholder="<?php esc_html_e('Logo Design', 'wp-jobsearch') ?>" type="text">
                                    </li>

                                    <li class="jobsearch-column-6">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Image *', 'wp-jobsearch'), 'tooltip_candi_port_image') ?>
                                        <div class="upload-img-holder-sec">
                                            <span class="file-loader"></span>
                                            <img src="" alt="">
                                            <br>
                                            <input name="add_portfolio_img" type="file" style="display: none;">
                                            <input type="hidden" id="add-portfolio-img-input"
                                                   class="jobsearch-req-field">
                                            <a href="javascript:void(0)" class="upload-port-img-btn"><i
                                                        class="jobsearch-icon jobsearch-add"></i> <?php esc_html_e('Upload Photo', 'wp-jobsearch') ?>
                                            </a>
                                        </div>
                                    </li>
                                    <?php
                                    ob_start();
                                    ?>
                                    <li class="jobsearch-column-12">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Video URL', 'wp-jobsearch'), 'tooltip_candi_port_video') ?>
                                        <input id="add-portfolio-vurl" type="text" placeholder="<?php esc_html_e('https://www.example.com', 'wp-jobsearch') ?>">
                                        <em><?php esc_html_e('Add video URL of Youtube, Vimeo.', 'wp-jobsearch') ?></em>
                                    </li>
                                    <?php
                                    $vidurl_html = ob_get_clean();
                                    echo apply_filters('jobsearch_cand_dash_resume_port_add_vurl', $vidurl_html);
                                    ?>
                                    <li class="jobsearch-column-12">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('URL', 'wp-jobsearch'), 'tooltip_candi_port_url') ?>
                                        <input id="add-portfolio-url" type="text" placeholder="<?php esc_html_e('https://www.example.com', 'wp-jobsearch') ?>">
                                    </li>
                                    <li class="jobsearch-column-12">
                                        <input type="submit" id="add-resume-portfolio-btn"
                                               value="<?php esc_html_e('Add Portfolio', 'wp-jobsearch') ?>">
                                        <span class="portfolio-loding-msg edu-loding-msg"></span>
                                    </li>
                                </ul>
                            </div>
                            <?php
                            $res_port_html = ob_get_clean();
                            echo apply_filters('jobsearch_candidate_dash_resume_portadd_html', $res_port_html, $candidate_id);

                            //
                            ob_start();
                            ?>
                            <div id="jobsearch-resume-portfolio-con" class="jobsearch-company-gallery">
                                <ul class="jobsearch-row jobsearch-portfolios-list-con">

                                    <?php
                                    $exfield_list = get_post_meta($candidate_id, 'jobsearch_field_portfolio_title', true);
                                    $exfield_list_val = get_post_meta($candidate_id, 'jobsearch_field_portfolio_image', true);
                                    $exfield_portfolio_url = get_post_meta($candidate_id, 'jobsearch_field_portfolio_url', true);
                                    $exfield_portfolio_vurl = get_post_meta($candidate_id, 'jobsearch_field_portfolio_vurl', true);
                                    if (is_array($exfield_list) && sizeof($exfield_list) > 0) {

                                        $exfield_counter = 0;
                                        foreach ($exfield_list as $exfield) {
                                            $rand_num = rand(1000000, 99999999);

                                            $portfolio_img = isset($exfield_list_val[$exfield_counter]) ? $exfield_list_val[$exfield_counter] : '';
                                            $portfolio_url = isset($exfield_portfolio_url[$exfield_counter]) ? $exfield_portfolio_url[$exfield_counter] : '';
                                            $portfolio_vurl = isset($exfield_portfolio_vurl[$exfield_counter]) ? $exfield_portfolio_vurl[$exfield_counter] : '';

                                            $exfield = jobsearch_esc_html($exfield);
                                            $portfolio_img = jobsearch_esc_html($portfolio_img);
                                            $portfolio_url = jobsearch_esc_html($portfolio_url);
                                            $portfolio_vurl = jobsearch_esc_html($portfolio_vurl);

                                            $portfolio_img_src = jobsearch_get_cand_portimg_url($candidate_id, $portfolio_img);
                                            ?>
                                            <li class="jobsearch-column-3 resume-list-item resume-list-port">
                                                <figure>
                                                    <a class="portfolio-img-holder"><span id="cand-portfolio-img-<?php echo ($exfield_counter) ?>"></span></a>
                                                    <figcaption>
                                                        <span><?php echo($exfield) ?></span>
                                                        <div class="jobsearch-company-links">
                                                            <a href="javascript:void(0);"
                                                               class="jobsearch-icon jobsearch-sort jobsearch-tooltipcon el-drag-item"
                                                               title="<?php esc_html_e('Drag', 'wp-jobsearch') ?>"></a>
                                                            <a href="javascript:void(0);"
                                                               class="jobsearch-icon jobsearch-edit jobsearch-tooltipcon update-resume-item"
                                                               title="<?php esc_html_e('Update', 'wp-jobsearch') ?>"></a>
                                                            <a href="javascript:void(0);"
                                                               class="jobsearch-icon jobsearch-rubbish jobsearch-tooltipcon <?php echo(apply_filters('jobsearch_candash_resume_portlist_itmdelclass', 'del-resume-item', $rand_num)) ?>"
                                                               data-id="<?php echo($rand_num) ?>"
                                                               title="<?php esc_html_e('Delete', 'wp-jobsearch') ?>"></a>
                                                        </div>
                                                    </figcaption>
                                                </figure>
                                                <div class="jobsearch-add-popup jobsearch-update-resume-items-sec">
                                                    <span class="close-popup-item"><i class="fa fa-times"></i></span>
                                                    <ul class="jobsearch-row jobsearch-employer-profile-form">
                                                        <li class="jobsearch-column-12">
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('Title *', 'wp-jobsearch'), 'tooltip_candi_port_title') ?>
                                                            <input name="jobsearch_field_portfolio_title[]" type="text" placeholder="<?php esc_html_e('Logo Design', 'wp-jobsearch') ?>" value="<?php echo($exfield) ?>">
                                                        </li>
                                                        <li class="jobsearch-column-6">
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('Image *', 'wp-jobsearch'), 'tooltip_candi_port_image') ?>
                                                            <div class="upload-img-holder-sec">
                                                                <span class="file-loader"></span>
                                                                <img id="cand-portfolio-upd-img-<?php echo ($exfield_counter) ?>" src="" alt="">
                                                                <br>
                                                                <input name="add_portfolio_img" type="file"
                                                                       style="display: none;">
                                                                <input type="hidden" class="img-upload-save-field"
                                                                       name="jobsearch_field_portfolio_image[]"
                                                                       value="<?php echo($portfolio_img) ?>">
                                                                <a href="javascript:void(0)"
                                                                   class="upload-port-img-btn"><i
                                                                            class="jobsearch-icon jobsearch-add"></i> <?php esc_html_e('Upload Photo', 'wp-jobsearch') ?>
                                                                </a>
                                                            </div>
                                                        </li>
                                                        <?php
                                                        ob_start();
                                                        ?>
                                                        <li class="jobsearch-column-12">
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('Video URL', 'wp-jobsearch'), 'tooltip_candi_port_video') ?>
                                                            <input name="jobsearch_field_portfolio_vurl[]" type="text"
                                                                   value="<?php echo($portfolio_vurl) ?>" placeholder="<?php esc_html_e('https://www.example.com', 'wp-jobsearch') ?>">
                                                            <em><?php esc_html_e('Add video URL of Youtube, Vimeo.', 'wp-jobsearch') ?></em>
                                                        </li>
                                                        <?php
                                                        $vidurl_html = ob_get_clean();
                                                        echo apply_filters('jobsearch_cand_dash_resume_port_updte_vurl', $vidurl_html, $portfolio_vurl, $candidate_id);
                                                        ?>
                                                        <li class="jobsearch-column-12">
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('URL', 'wp-jobsearch'), 'tooltip_candi_port_url') ?>
                                                            <input name="jobsearch_field_portfolio_url[]" type="text"
                                                                   value="<?php echo($portfolio_url) ?>" placeholder="<?php esc_html_e('https://www.example.com', 'wp-jobsearch') ?>">
                                                        </li>
                                                        <li class="jobsearch-column-12">
                                                            <input class="update-resume-list-btn" type="submit" value="<?php esc_html_e('Update', 'wp-jobsearch') ?>">
                                                        </li>
                                                    </ul>
                                                </div>
                                            </li>
                                            <?php
                                            $exfield_counter++;
                                        }
                                    }
                                    ?>

                                </ul>
                            </div>
                            <?php
                            $res_port_html = ob_get_clean();
                            echo apply_filters('jobsearch_candidate_dash_resume_portslist_html', $res_port_html, $candidate_id);
                            ?>
                        </div>
                        <?php
                        $resm_port_oall_html = ob_get_clean();
                        $resm_port_oall_html = apply_filters('jobsearch_cand_dash_resume_port_oall', $resm_port_oall_html, $candidate_id);
                    }
                }
                
                if ($inopt_resm_skills != 'off') {
                    if ($user_pkg_limits::cand_field_is_locked('resmskills_defields')) {
                        ob_start();
                        ?>
                        <div class="jobsearch-candidate-title skill_title_skillid">
                            <h2>
                                <?php
                                ob_start();
                                ?>
                                <i class="jobsearch-icon jobsearch-social-media"></i> <?php esc_html_e('Expertise', 'wp-jobsearch') ?>
                                <?php
                                $title_html = ob_get_clean();
                                echo apply_filters('jobsearch_candash_resume_exprtizemain_label', $title_html);
                                ?>
                            </h2>
                        </div>
                        <?php echo($user_pkg_limits::cand_gen_locked_html()) ?>
                        <?php
                        $lock_field_cushtml = ob_get_clean();
                        $lock_field_html = $user_pkg_limits->cand_field_locked_html($lock_field_cushtml);
                        echo($lock_field_html);
                    } else {
                        $cand_expertise_percntage = isset($jobsearch_plugin_options['cand_resm_skills_percntage']) ? $jobsearch_plugin_options['cand_resm_skills_percntage'] : '';
                        ob_start();
                        ?>
                        <div class="jobsearch-candidate-resume-wrap skill_title_skillid">
                            <?php
                            ob_start();
                            ?>
                            <div class="jobsearch-candidate-title">
                                <h2>
                                    <?php
                                    ob_start();
                                    ?>
                                    <i class="jobsearch-icon jobsearch-social-media"></i> <?php echo apply_filters('jobsearch_candash_resume_exprtize_label_txt', esc_html__('Expertise', 'wp-jobsearch')) ?>
                                    <?php
                                    $title_html = ob_get_clean();
                                    echo apply_filters('jobsearch_candash_resume_exprtizemain_label', $title_html);
                                    ?>
                                    <a href="javascript:void(0)" class="jobsearch-resume-addbtn"><span class="fa fa-plus"></span> <?php echo apply_filters('jobsearch_candash_resume_addexprtize_label_txt', esc_html__('Add Expertise', 'wp-jobsearch')) ?>
                                    </a>
                                </h2>
                            </div>

                            <div class="jobsearch-add-popup jobsearch-add-resume-item-popup">
                                <span class="close-popup-item"><i class="fa fa-times"></i></span>
                                <ul class="jobsearch-row jobsearch-employer-profile-form">
                                    <li class="jobsearch-column-12">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Label *', 'wp-jobsearch'), 'tooltip_candi_expertise_label') ?>
                                        <input id="add-skill-title" class="jobsearch-req-field" type="text" placeholder="<?php esc_html_e('Social Media Marketing', 'wp-jobsearch') ?>">
                                    </li>
                                    <?php
                                    $no__percntage = true;
                                    if ($cand_expertise_percntage == 'on' || $cand_expertise_percntage == 'on_req') {
                                        $no__percntage = false;
                                        $percnt_title = jobsearch_add_field_label_tooltip(esc_html__('Percentage', 'wp-jobsearch'), 'tooltip_candi_expertise_percent');
                                        if ($cand_expertise_percntage == 'on_req') {
                                            $percnt_title = jobsearch_add_field_label_tooltip(esc_html__('Percentage *', 'wp-jobsearch'), 'tooltip_candi_expertise_percent');
                                        }
                                        ob_start();
                                        ?>
                                        <li class="jobsearch-column-12">
                                            <?php echo ($percnt_title) ?>
                                            <input id="add-skill-percentage" class="<?php echo ($cand_expertise_percntage == 'on_req' ? 'jobsearch-req-field' : '') ?>" type="number"
                                                   placeholder="<?php esc_html_e('Enter a number between 1 to 100', 'wp-jobsearch') ?>"
                                                   min="1" max="100">
                                        </li>
                                        <?php
                                        $perct_html = ob_get_clean();
                                        echo apply_filters('jobsearch_canddash_resm_exprtadd_perctfield_html', $perct_html, $candidate_id);
                                    }
                                    ?>
                                    <li class="jobsearch-column-12">
                                        <?php
                                        if ($no__percntage) {
                                            ?><input type="hidden" id="add-skill-percentage" value="100"><?php
                                        }
                                        ?>
                                        <input type="submit" id="add-resume-skills-btn" value="<?php echo apply_filters('jobsearch_candash_resume_addexprtize_label_txt', esc_html__('Add Expertise', 'wp-jobsearch')) ?>">
                                        <span class="skills-loding-msg edu-loding-msg"></span>
                                    </li>
                                </ul>
                            </div>
                            <?php
                            $res_skill_html = ob_get_clean();
                            echo apply_filters('jobsearch_candidate_dash_resume_skilladd_html', $res_skill_html, $candidate_id);
                            ?>
                            <div id="jobsearch-resume-skills-con"
                                 class="<?php echo apply_filters('jobsearch_canddash_resume_skillist_mainclass', 'jobsearch-add-skills') ?>">
                                <ul class="jobsearch-row">
                                    <?php
                                    $exfield_list = get_post_meta($candidate_id, 'jobsearch_field_skill_title', true);
                                    $skill_percentagefield_list = get_post_meta($candidate_id, 'jobsearch_field_skill_percentage', true);
                                    if (is_array($exfield_list) && sizeof($exfield_list) > 0) {

                                        $exfield_counter = 0;
                                        foreach ($exfield_list as $exfield) {
                                            $rand_num = rand(1000000, 99999999);

                                            $skill_percentagefield_val = isset($skill_percentagefield_list[$exfield_counter]) ? $skill_percentagefield_list[$exfield_counter] : '';

                                            $exfield = jobsearch_esc_html($exfield);
                                            $skill_percentagefield_val = jobsearch_esc_html($skill_percentagefield_val);
                                            ?>
                                            <li class="jobsearch-column-12 resume-list-item resume-list-skill">
                                                <?php
                                                ob_start();
                                                ?>
                                                <div class="jobsearch-add-skills-wrap">
                                                    <span><?php echo($skill_percentagefield_val) ?></span>
                                                    <h2 class="jobsearch-pst-title"><a><?php echo($exfield) ?></a></h2>
                                                </div>
                                                <?php
                                                $skillist_html = ob_get_clean();
                                                echo apply_filters('jobsearch_canddash_resume_skillist_itmhtml', $skillist_html, $candidate_id, $exfield_counter);
                                                ?>
                                                <div class="jobsearch-resume-education-btn">
                                                    <a href="javascript:void(0);"
                                                       class="jobsearch-icon jobsearch-sort jobsearch-tooltipcon el-drag-item"
                                                       title="<?php esc_html_e('Drag', 'wp-jobsearch') ?>"></a>
                                                    <a href="javascript:void(0);"
                                                       class="jobsearch-icon jobsearch-edit jobsearch-tooltipcon update-resume-item"
                                                       title="<?php esc_html_e('Update', 'wp-jobsearch') ?>"></a>
                                                    <a href="javascript:void(0);"
                                                       class="jobsearch-icon jobsearch-rubbish jobsearch-tooltipcon <?php echo(apply_filters('jobsearch_candash_resume_skilllist_itmdelclass', 'del-resume-item', $rand_num)) ?>"
                                                       data-id="<?php echo($rand_num) ?>"
                                                       title="<?php esc_html_e('Delete', 'wp-jobsearch') ?>"></a>
                                                </div>
                                                <div class="jobsearch-add-popup jobsearch-update-resume-items-sec">
                                                    <span class="close-popup-item"><i class="fa fa-times"></i></span>
                                                    <?php
                                                    ob_start();
                                                    ?>
                                                    <ul class="jobsearch-row jobsearch-employer-profile-form">
                                                        <li class="jobsearch-column-12">
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('Label *', 'wp-jobsearch'), 'tooltip_candi_expertise_label') ?>
                                                            <input name="jobsearch_field_skill_title[]" type="text" placeholder="<?php esc_html_e('Social Media Marketing', 'wp-jobsearch') ?>"
                                                                   value="<?php echo($exfield) ?>">
                                                        </li>
                                                        <?php
                                                        $no__percntage = true;
                                                        if ($cand_expertise_percntage == 'on' || $cand_expertise_percntage == 'on_req') {
                                                            $no__percntage = false;
                                                            $percnt_title = jobsearch_add_field_label_tooltip(esc_html__('Percentage', 'wp-jobsearch'), 'tooltip_candi_expertise_percent');
                                                            if ($cand_expertise_percntage == 'on_req') {
                                                                $percnt_title = jobsearch_add_field_label_tooltip(esc_html__('Percentage *', 'wp-jobsearch'), 'tooltip_candi_expertise_percent');
                                                            }
                                                            ob_start();
                                                            ?>
                                                            <li class="jobsearch-column-6">
                                                                <?php echo ($percnt_title) ?>
                                                                <input name="jobsearch_field_skill_percentage[]"
                                                                       type="number"
                                                                       placeholder="<?php esc_html_e('Enter a number between 1 to 100', 'wp-jobsearch') ?>"
                                                                       min="1" max="100"
                                                                       value="<?php echo($skill_percentagefield_val) ?>">
                                                            </li>
                                                            <?php
                                                            $perct_html = ob_get_clean();
                                                            echo apply_filters('jobsearch_canddash_resm_exprtupd_perctfield_html', $perct_html, $candidate_id, $exfield_counter);
                                                        }
                                                        ?>
                                                        <li class="jobsearch-column-12">
                                                            <?php
                                                            if ($no__percntage) {
                                                                ?><input type="hidden" name="jobsearch_field_skill_percentage[]" value="100"><?php
                                                            }
                                                            ?>
                                                            <input class="update-resume-list-btn" type="submit"
                                                                   value="<?php esc_html_e('Update', 'wp-jobsearch') ?>">
                                                        </li>
                                                    </ul>
                                                    <?php
                                                    $res_skill_html = ob_get_clean();
                                                    echo apply_filters('jobsearch_canddash_resm_skillupd_fields_html', $res_skill_html, $candidate_id, $exfield_counter);
                                                    ?>
                                                </div>
                                            </li>
                                            <?php
                                            $exfield_counter++;
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                        <?php
                        $resm_skill_oall_html = ob_get_clean();
                        $resm_skill_oall_html = apply_filters('jobsearch_cand_dash_resume_skill_oall', $resm_skill_oall_html, $candidate_id);
                    }
                }
                if ($inopt_resm_langs != 'off') {
                    $cand_lang_percntage = isset($jobsearch_plugin_options['cand_resm_langs_percntage']) ? $jobsearch_plugin_options['cand_resm_langs_percntage'] : '';
                    ob_start();
                    ?>
                    <div class="jobsearch-candidate-resume-wrap">
                        <?php
                        ob_start();
                        ?>
                        <div class="jobsearch-candidate-title">
                            <h2>
                                <i class="jobsearch-icon jobsearch-social-media"></i> <?php esc_html_e('Languages', 'wp-jobsearch') ?>
                                <a href="javascript:void(0)" class="jobsearch-resume-addbtn"><span
                                            class="fa fa-plus"></span> <?php esc_html_e('Add Language', 'wp-jobsearch') ?>
                                </a>
                            </h2>
                        </div>

                        <div class="jobsearch-add-popup jobsearch-add-resume-item-popup">
                            <span class="close-popup-item"><i class="fa fa-times"></i></span>
                            <ul class="jobsearch-row jobsearch-employer-profile-form">
                                <li class="jobsearch-column-6">
                                    <?php echo jobsearch_add_field_label_tooltip(esc_html__('Label *', 'wp-jobsearch'), 'tooltip_candi_lang_label') ?>
                                    <input id="add-lang-title" class="jobsearch-req-field" type="text" placeholder="<?php esc_html_e('English', 'wp-jobsearch') ?>">
                                </li>
                                <?php
                                ob_start();
                                ?>
                                <li class="jobsearch-column-6">
                                    <?php echo jobsearch_add_field_label_tooltip(esc_html__('Level', 'wp-jobsearch'), 'tooltip_candi_lang_level') ?>
                                    <div class="jobsearch-profile-select">
                                        <select id="add-lang-level" class="selectize-select"
                                                placeholder="<?php _e('Speaking Level', 'wp-jobsearch') ?>">
                                            <option value="beginner"><?php esc_html_e('Beginner', 'wp-jobsearch') ?></option>
                                            <option value="intermediate"><?php esc_html_e('Intermediate', 'wp-jobsearch') ?></option>
                                            <option value="proficient"><?php esc_html_e('Proficient', 'wp-jobsearch') ?></option>
                                        </select>
                                    </div>
                                </li>
                                <?php
                                $lvls_html = ob_get_clean();
                                echo apply_filters('jobsearch_canddash_resm_langadd_lvlsfield_html', $lvls_html, $candidate_id);
                                
                                echo apply_filters('jobsearch_canddash_resm_langadd_afterlvl_field', '', $candidate_id);
                                
                                $no__percntage = true;
                                if ($cand_lang_percntage == 'on' || $cand_lang_percntage == 'on_req') {
                                    $no__percntage = false;
                                    $percnt_title = jobsearch_add_field_label_tooltip(esc_html__('Percentage', 'wp-jobsearch'), 'tooltip_candi_lang_percent');
                                    if ($cand_lang_percntage == 'on_req') {
                                        $percnt_title = jobsearch_add_field_label_tooltip(esc_html__('Percentage *', 'wp-jobsearch'), 'tooltip_candi_lang_percent');
                                    }
                                    ob_start();
                                    ?>
                                    <li class="jobsearch-column-12">
                                        <?php echo ($percnt_title) ?>
                                        <input id="add-lang-percentage" class="<?php echo ($cand_lang_percntage == 'on_req' ? 'jobsearch-req-field' : '') ?>" type="number"
                                               placeholder="<?php esc_html_e('Enter a number between 1 to 100', 'wp-jobsearch') ?>"
                                               min="1" max="100">
                                    </li>
                                    <?php
                                    $perct_html = ob_get_clean();
                                    echo apply_filters('jobsearch_canddash_resm_langadd_perctfield_html', $perct_html, $candidate_id);
                                }
                                ?>
                                <li class="jobsearch-column-12">
                                    <?php
                                    if ($no__percntage) {
                                        ?><input type="hidden" id="add-lang-percentage" value="100"><?php
                                    }
                                    ?>
                                    <input type="submit" id="add-resume-langs-btn"
                                           value="<?php esc_html_e('Add Language', 'wp-jobsearch') ?>">
                                    <span class="langs-loding-msg edu-loding-msg"></span>
                                </li>
                            </ul>
                        </div>
                        <?php
                        $res_lang_html = ob_get_clean();
                        echo apply_filters('jobsearch_candidate_dash_resume_langadd_html', $res_lang_html, $candidate_id);
                        ?>
                        <div id="jobsearch-resume-langs-con"
                             class="<?php echo apply_filters('jobsearch_canddash_resume_langist_mainclass', 'jobsearch-add-skills') ?>">
                            <ul class="jobsearch-row">
                                <?php
                                $exfield_list = get_post_meta($candidate_id, 'jobsearch_field_lang_title', true);
                                $lang_percentagefield_list = get_post_meta($candidate_id, 'jobsearch_field_lang_percentage', true);
                                $lang_level_list = get_post_meta($candidate_id, 'jobsearch_field_lang_level', true);
                                if (is_array($exfield_list) && sizeof($exfield_list) > 0) {

                                    $exfield_counter = 0;
                                    foreach ($exfield_list as $exfield) {
                                        $rand_num = rand(1000000, 99999999);

                                        $lang_percentagefield_val = isset($lang_percentagefield_list[$exfield_counter]) ? $lang_percentagefield_list[$exfield_counter] : '';
                                        $lang_level = isset($lang_level_list[$exfield_counter]) ? $lang_level_list[$exfield_counter] : '';

                                        $exfield = jobsearch_esc_html($exfield);
                                        $lang_percentagefield_val = jobsearch_esc_html($lang_percentagefield_val);
                                        ?>
                                        <li class="jobsearch-column-12 resume-list-item resume-list-lang">
                                            <?php
                                            ob_start();
                                            ?>
                                            <div class="jobsearch-add-skills-wrap">
                                                <span><?php echo($lang_percentagefield_val) ?></span>
                                                <h2 class="jobsearch-pst-title"><a><?php echo($exfield) ?></a></h2>
                                            </div>
                                            <?php
                                            $langist_html = ob_get_clean();
                                            echo apply_filters('jobsearch_canddash_resume_langist_itmhtml', $langist_html, $candidate_id, $exfield_counter);
                                            ?>
                                            <div class="jobsearch-resume-education-btn">
                                                <a href="javascript:void(0);"
                                                   class="jobsearch-icon jobsearch-sort jobsearch-tooltipcon el-drag-item"
                                                   title="<?php esc_html_e('Drag', 'wp-jobsearch') ?>"></a>
                                                <a href="javascript:void(0);"
                                                   class="jobsearch-icon jobsearch-edit jobsearch-tooltipcon update-resume-item"
                                                   title="<?php esc_html_e('Update', 'wp-jobsearch') ?>"></a>
                                                <a href="javascript:void(0);"
                                                   class="jobsearch-icon jobsearch-rubbish jobsearch-tooltipcon <?php echo(apply_filters('jobsearch_candash_resume_langlist_itmdelclass', 'del-resume-item', $rand_num)) ?>"
                                                   data-id="<?php echo($rand_num) ?>"
                                                   title="<?php esc_html_e('Delete', 'wp-jobsearch') ?>"></a>
                                            </div>
                                            <div class="jobsearch-add-popup jobsearch-update-resume-items-sec">
                                                <span class="close-popup-item"><i class="fa fa-times"></i></span>
                                                <?php
                                                ob_start();
                                                ?>
                                                <ul class="jobsearch-row jobsearch-employer-profile-form">
                                                    <li class="jobsearch-column-6">
                                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Label *', 'wp-jobsearch'), 'tooltip_candi_lang_label') ?>
                                                        <input name="jobsearch_field_lang_title[]" type="text" placeholder="<?php esc_html_e('English', 'wp-jobsearch') ?>"
                                                               value="<?php echo($exfield) ?>">
                                                    </li>
                                                    <?php
                                                    ob_start();
                                                    ?>
                                                    <li class="jobsearch-column-6">
                                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Level', 'wp-jobsearch'), 'tooltip_candi_lang_level') ?>
                                                        <div class="jobsearch-profile-select">
                                                            <select name="jobsearch_field_lang_level[]"
                                                                    class="selectize-select"
                                                                    placeholder="<?php _e('Speaking Level', 'wp-jobsearch') ?>">
                                                                <option value="beginner" <?php echo($lang_level == 'beginner' ? 'selected="selected"' : '') ?>><?php esc_html_e('Beginner', 'wp-jobsearch') ?></option>
                                                                <option value="intermediate" <?php echo($lang_level == 'intermediate' ? 'selected="selected"' : '') ?>><?php esc_html_e('Intermediate', 'wp-jobsearch') ?></option>
                                                                <option value="proficient" <?php echo($lang_level == 'proficient' ? 'selected="selected"' : '') ?>><?php esc_html_e('Proficient', 'wp-jobsearch') ?></option>
                                                            </select>
                                                        </div>
                                                    </li>
                                                    <?php
                                                    $lvls_html = ob_get_clean();
                                                    echo apply_filters('jobsearch_canddash_resm_langupd_lvlsfield_html', $lvls_html, $candidate_id, $exfield_counter);
                                                    
                                                    echo apply_filters('jobsearch_canddash_resm_langupd_afterlvl_field', '', $candidate_id, $exfield_counter);
                                                    
                                                    $no__percntage = true;
                                                    if ($cand_lang_percntage == 'on' || $cand_lang_percntage == 'on_req') {
                                                        $no__percntage = false;
                                                        $percnt_title = jobsearch_add_field_label_tooltip(esc_html__('Percentage', 'wp-jobsearch'), 'tooltip_candi_lang_percent');
                                                        if ($cand_lang_percntage == 'on_req') {
                                                            $percnt_title = jobsearch_add_field_label_tooltip(esc_html__('Percentage *', 'wp-jobsearch'), 'tooltip_candi_lang_percent');
                                                        }
                                                        ob_start();
                                                        ?>
                                                        <li class="jobsearch-column-12">
                                                            <?php echo ($percnt_title) ?>
                                                            <input name="jobsearch_field_lang_percentage[]"
                                                                   type="number"
                                                                   placeholder="<?php esc_html_e('Enter a number between 1 to 100', 'wp-jobsearch') ?>"
                                                                   min="1" max="100"
                                                                   value="<?php echo($lang_percentagefield_val) ?>">
                                                        </li>
                                                        <?php
                                                        $perct_html = ob_get_clean();
                                                        echo apply_filters('jobsearch_canddash_resm_langupd_perctfield_html', $perct_html, $candidate_id, $exfield_counter);
                                                    }
                                                    ?>
                                                    <li class="jobsearch-column-12">
                                                        <?php
                                                        if ($no__percntage) {
                                                            ?><input type="hidden" name="jobsearch_field_lang_percentage[]" value="100"><?php
                                                        }
                                                        ?>
                                                        <input class="update-resume-list-btn" type="submit"
                                                               value="<?php esc_html_e('Update', 'wp-jobsearch') ?>">
                                                    </li>
                                                </ul>
                                                <?php
                                                $res_lang_html = ob_get_clean();
                                                echo apply_filters('jobsearch_canddash_resm_langupd_fields_html', $res_lang_html, $candidate_id, $exfield_counter);
                                                ?>
                                            </div>
                                        </li>
                                        <?php
                                        $exfield_counter++;
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <?php
                    $resm_lang_oall_html = ob_get_clean();
                    $resm_lang_oall_html = apply_filters('jobsearch_cand_dash_resume_lang_oall', $resm_lang_oall_html, $candidate_id);
                }
                if ($inopt_resm_honsawards != 'off') {
                    if ($user_pkg_limits::cand_field_is_locked('resmawards_defields')) {
                        ob_start();
                        ?>
                        <div class="jobsearch-candidate-title award_title_skillid">
                            <h2>
                                <i class="jobsearch-icon jobsearch-social-media"></i> <?php esc_html_e('Honors & Awards', 'wp-jobsearch') ?>
                            </h2>
                        </div>
                        <?php echo($user_pkg_limits::cand_gen_locked_html()) ?>
                        <?php
                        $lock_field_cushtml = ob_get_clean();
                        $lock_field_html = $user_pkg_limits->cand_field_locked_html($lock_field_cushtml);
                        echo($lock_field_html);
                    } else {
                        ob_start();
                        ?>
                        <div class="jobsearch-candidate-resume-wrap award_title_skillid">
                            <div class="jobsearch-candidate-title">
                                <h2>
                                    <i class="jobsearch-icon jobsearch-trophy"></i> <?php esc_html_e('Honors & Awards', 'wp-jobsearch') ?>
                                    <a href="javascript:void(0)" class="jobsearch-resume-addbtn"><span
                                                class="fa fa-plus"></span> <?php esc_html_e('Add Award', 'wp-jobsearch') ?>
                                    </a>
                                </h2>
                            </div>

                            <div class="jobsearch-add-popup jobsearch-add-resume-item-popup">
                                <span class="close-popup-item"><i class="fa fa-times"></i></span>
                                <ul class="jobsearch-row jobsearch-employer-profile-form">
                                    <li class="jobsearch-column-6">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Award Title *', 'wp-jobsearch'), 'tooltip_candi_award_title') ?>
                                        <input id="add-award-title" class="jobsearch-req-field" type="text" placeholder="<?php esc_html_e('Top Seller', 'wp-jobsearch') ?>">
                                    </li>

                                    <li class="jobsearch-column-6">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Year *', 'wp-jobsearch'), 'tooltip_candi_award_year') ?>
                                        <input id="add-award-year" class="jobsearch-req-field" type="text" placeholder="<?php esc_html_e('2020', 'wp-jobsearch') ?>">
                                    </li>
                                    <li class="jobsearch-column-12">
                                        <?php $description = esc_html(_x('Description', 'Resume Awards Description', 'wp-jobsearch')) ?>
                                        <?php echo jobsearch_add_field_label_tooltip($description, 'tooltip_candi_award_year') ?>
                                        <textarea id="add-award-desc" placeholder="<?php esc_html_e('I got this award for my extraordinary performance.', 'wp-jobsearch') ?>"></textarea>
                                    </li>
                                    <li class="jobsearch-column-12">
                                        <input id="add-resume-awards-btn" type="submit"
                                               value="<?php esc_html_e('Add Award', 'wp-jobsearch') ?>">
                                        <span class="awards-loding-msg edu-loding-msg"></span>
                                    </li>
                                </ul>
                            </div>
                            <div id="jobsearch-resume-awards-con"
                                 class="jobsearch-resume-education jobsearch-resume-awards">
                                <ul class="jobsearch-row">
                                    <?php
                                    $exfield_list = get_post_meta($candidate_id, 'jobsearch_field_award_title', true);
                                    $exfield_list_val = get_post_meta($candidate_id, 'jobsearch_field_award_description', true);
                                    $award_yearfield_list = get_post_meta($candidate_id, 'jobsearch_field_award_year', true);
                                    if (is_array($exfield_list) && sizeof($exfield_list) > 0) {

                                        $exfield_counter = 0;
                                        foreach ($exfield_list as $exfield) {
                                            $rand_num = rand(1000000, 99999999);

                                            $exfield_val = isset($exfield_list_val[$exfield_counter]) ? $exfield_list_val[$exfield_counter] : '';
                                            $award_yearfield_val = isset($award_yearfield_list[$exfield_counter]) ? $award_yearfield_list[$exfield_counter] : '';

                                            $exfield = jobsearch_esc_html($exfield);
                                            $exfield_val = jobsearch_esc_html($exfield_val);
                                            $award_yearfield_val = jobsearch_esc_html($award_yearfield_val);
                                            ?>
                                            <li class="jobsearch-column-12 resume-list-item resume-list-award">
                                                <div class="jobsearch-resume-education-wrap">
                                                    <small><?php echo($award_yearfield_val) ?></small>
                                                    <h2 class="jobsearch-pst-title"><a><?php echo($exfield) ?></a></h2>
                                                </div>
                                                <div class="jobsearch-resume-education-btn">
                                                    <a href="javascript:void(0);"
                                                       class="jobsearch-icon jobsearch-sort jobsearch-tooltipcon el-drag-item"
                                                       title="<?php esc_html_e('Drag', 'wp-jobsearch') ?>"></a>
                                                    <a href="javascript:void(0);"
                                                       class="jobsearch-icon jobsearch-edit jobsearch-tooltipcon update-resume-item"
                                                       title="<?php esc_html_e('Update', 'wp-jobsearch') ?>"></a>
                                                    <a href="javascript:void(0);"
                                                       class="jobsearch-icon jobsearch-rubbish jobsearch-tooltipcon <?php echo(apply_filters('jobsearch_candash_resume_awardlist_itmdelclass', 'del-resume-item', $rand_num)) ?>"
                                                       data-id="<?php echo($rand_num) ?>"
                                                       title="<?php esc_html_e('Delete', 'wp-jobsearch') ?>"></a>
                                                </div>
                                                <div class="jobsearch-add-popup jobsearch-update-resume-items-sec">
                                                    <span class="close-popup-item"><i class="fa fa-times"></i></span>
                                                    <ul class="jobsearch-row jobsearch-employer-profile-form">
                                                        <li class="jobsearch-column-6">
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('Award Title *', 'wp-jobsearch'), 'tooltip_candi_award_title') ?>
                                                            <input name="jobsearch_field_award_title[]" type="text" placeholder="<?php esc_html_e('Top Seller', 'wp-jobsearch') ?>"
                                                                   value="<?php echo($exfield) ?>">
                                                        </li>
                                                        <li class="jobsearch-column-6">
                                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('Year *', 'wp-jobsearch'), 'tooltip_candi_award_year') ?>
                                                            <input name="jobsearch_field_award_year[]" type="text" placeholder="<?php esc_html_e('2020', 'wp-jobsearch') ?>"
                                                                   value="<?php echo($award_yearfield_val) ?>">
                                                        </li>
                                                        <li class="jobsearch-column-12">
                                                            <?php $description = esc_html(_x('Description', 'Resume Awards Description', 'wp-jobsearch')) ?>
                                                            <?php echo jobsearch_add_field_label_tooltip($description, 'tooltip_candi_award_year') ?>
                                                            <textarea name="jobsearch_field_award_description[]" placeholder="<?php esc_html_e('I got this award for my extraordinary performance.', 'wp-jobsearch') ?>"><?php echo($exfield_val) ?></textarea>
                                                        </li>
                                                        <li class="jobsearch-column-12">
                                                            <input class="update-resume-list-btn" type="submit"
                                                                   value="<?php esc_html_e('Update', 'wp-jobsearch') ?>">
                                                        </li>
                                                    </ul>
                                                </div>
                                            </li>
                                            <?php
                                            $exfield_counter++;
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                        <?php
                        $resm_award_oall_html = ob_get_clean();
                        $resm_award_oall_html = apply_filters('jobsearch_cand_dash_resume_award_oall', $resm_award_oall_html, $candidate_id);
                    }
                }
                $oall_fields_arr = array(
                    'resm_edu_item' => $resm_edu_oall_html,
                    'resm_exp_item' => $resm_exp_oall_html,
                    'resm_port_item' => $resm_port_oall_html,
                    'resm_skill_item' => $resm_skill_oall_html,
                    'resm_lang_item' => $resm_lang_oall_html,
                    'resm_award_item' => $resm_award_oall_html,
                );
                $oall_fields_arr = apply_filters('jobsearch_cand_dash_resm_oall_farray', $oall_fields_arr, $candidate_id);
                foreach ($oall_fields_arr as $oall_field_key => $oall_field_item) {
                    if ($oall_field_key == 'resm_award_item') {
                        echo apply_filters('jobsearch_candash_resume_before_awards_html', '', $candidate_id);
                    }
                    echo $oall_field_item;
                    if ($oall_field_key == 'resm_port_item') {
                        echo apply_filters('jobsearch_candash_resume_after_portfolios_html', '', $candidate_id);
                    }
                    if ($oall_field_key == 'resm_exp_item') {
                        echo apply_filters('jobsearch_candash_resume_after_experience_html', '', $candidate_id);
                    }
                }
                ?>
            </div>
        </div>
        <input type="hidden" name="user_resume_form" value="1">
        <?php
        add_action('wp_footer', function() {
            ?>
            <script>
                function jobsearch_escape_useresume_fileds(s) {
                    return s.replace(/&/g, ' ').replace(/</g, ' ').replace(/"/g, ' ');
                }
                // For education
                <?php
                ob_start();
                ?>
                jQuery(document).on('change', '#jobsearch-resume-edu-con input,#jobsearch-resume-edu-con select,#jobsearch-resume-edu-con textarea', function() {
                    var _thisfield = jQuery(this);
                    if (typeof _thisfield.attr('name') !== 'undefined' && _thisfield.attr('name') != '' && _thisfield.attr('name') != 'undefined' && _thisfield.attr('type') != 'file') {
                        var parent_ul = _thisfield.parents('.jobsearch-update-resume-items-sec');
                        var appender_con = parent_ul.parents('li').find('.jobsearch-resume-education-wrap');
                        var edu_title = parent_ul.find('input[name="jobsearch_field_education_title[]"]').val();
                        var edu_startdate = parent_ul.find('input[name="jobsearch_field_education_start_date[]"]').val();
                        var edu_endate = parent_ul.find('input[name="jobsearch_field_education_end_date[]"]').val();
                        var edu_is_present = parent_ul.find('input[name="jobsearch_field_education_date_prsnt[]"]').val();
                        var edu_institute = parent_ul.find('input[name="jobsearch_field_education_academy[]"]').val();
                        
                        edu_title = jobsearch_escape_useresume_fileds(edu_title);
                        edu_startdate = jobsearch_escape_useresume_fileds(edu_startdate);
                        edu_endate = jobsearch_escape_useresume_fileds(edu_endate);
                        edu_is_present = jobsearch_escape_useresume_fileds(edu_is_present);
                        edu_institute = jobsearch_escape_useresume_fileds(edu_institute);
                        
                        var edu_date_str = edu_startdate + (edu_is_present == 'on' ? ' - <?php esc_html_e('Present', 'wp-jobsearch') ?>' : (edu_endate != '' ? ' - ' + edu_endate : ''));
                        appender_con.html('<small>' + edu_date_str + '</small>\
                            <small> </small>\
                            <h2 class="jobsearch-pst-title"><a>' + edu_title + '</a></h2>\
                            <span>' + edu_institute + '</span>');
                    }
                });
                <?php
                $educ_script = ob_get_clean();
                echo apply_filters('jobsearch_candash_resm_education_autosave_scrpt', $educ_script);
                ?>
                
                // For experience
                <?php
                ob_start();
                ?>
                jQuery(document).on('change', '#jobsearch-resume-expr-con input,#jobsearch-resume-expr-con select,#jobsearch-resume-expr-con textarea', function() {
                    var _thisfield = jQuery(this);
                    if (typeof _thisfield.attr('name') !== 'undefined' && _thisfield.attr('name') != '' && _thisfield.attr('name') != 'undefined' && _thisfield.attr('type') != 'file') {
                        var parent_ul = _thisfield.parents('.jobsearch-update-resume-items-sec');
                        var appender_con = parent_ul.parents('li').find('.jobsearch-resume-education-wrap');
                        var exp_title = parent_ul.find('input[name="jobsearch_field_experience_title[]"]').val();
                        var exp_startdate = parent_ul.find('input[name="jobsearch_field_experience_start_date[]"]').val();
                        var exp_endate = parent_ul.find('input[name="jobsearch_field_experience_end_date[]"]').val();
                        var exp_is_present = parent_ul.find('input[name="jobsearch_field_experience_date_prsnt[]"]').val();
                        var exp_company = parent_ul.find('input[name="jobsearch_field_experience_company[]"]').val();
                        
                        exp_title = jobsearch_escape_useresume_fileds(exp_title);
                        exp_startdate = jobsearch_escape_useresume_fileds(exp_startdate);
                        exp_endate = jobsearch_escape_useresume_fileds(exp_endate);
                        exp_is_present = jobsearch_escape_useresume_fileds(exp_is_present);
                        exp_company = jobsearch_escape_useresume_fileds(exp_company);
                        
                        var exp_date_str = exp_startdate + (exp_is_present == 'on' ? ' - <?php esc_html_e('Present', 'wp-jobsearch') ?>' : (exp_endate != '' ? ' - ' + exp_endate : ''));
                        appender_con.html('<small>' + exp_date_str + '</small>\
                        <h2 class="jobsearch-pst-title"><a>' + exp_title + '</a></h2>\
                        <span>' + exp_company + '</span>');
                    }
                });
                <?php
                $expm_script = ob_get_clean();
                echo apply_filters('jobsearch_candash_resm_experience_autosave_scrpt', $expm_script);
                ?>
                
                // For expertise
                jQuery(document).on('change', '#jobsearch-resume-skills-con input,#jobsearch-resume-skills-con select', function() {
                    var _thisfield = jQuery(this);
                    if (typeof _thisfield.attr('name') !== 'undefined' && _thisfield.attr('name') != '' && _thisfield.attr('name') != 'undefined' && _thisfield.attr('type') != 'file') {
                        var parent_ul = _thisfield.parents('.jobsearch-update-resume-items-sec');
                        var appender_con = parent_ul.parents('li').find('.jobsearch-add-skills-wrap');
                        var exp_title = parent_ul.find('input[name="jobsearch_field_skill_title[]"]').val();
                        var exp_percent = parent_ul.find('input[name="jobsearch_field_skill_percentage[]"]').val();
                        
                        exp_title = jobsearch_escape_useresume_fileds(exp_title);
                        exp_percent = jobsearch_escape_useresume_fileds(exp_percent);
                        
                        appender_con.html('<span>' + exp_percent + '</span>\
                        <h2 class="jobsearch-pst-title"><a>' + exp_title + '</a></h2>');
                    }
                });
                
                // For portfolio
                jQuery(document).on('change', '#jobsearch-resume-portfolio-con input,#jobsearch-resume-portfolio-con select', function() {
                    var _thisfield = jQuery(this);
                    if (typeof _thisfield.attr('name') !== 'undefined' && _thisfield.attr('name') != '' && _thisfield.attr('name') != 'undefined' && _thisfield.attr('type') != 'file') {
                        var parent_ul = _thisfield.parents('.jobsearch-update-resume-items-sec');
                        var appender_con = parent_ul.parents('li').find('figure');
                        var port_title = parent_ul.find('input[name="jobsearch_field_portfolio_title[]"]').val();
                        var port_img_src = parent_ul.find('.upload-img-holder-sec').find('img').attr('src');
                        
                        port_title = jobsearch_escape_useresume_fileds(port_title);
                        port_img_src = jobsearch_escape_useresume_fileds(port_img_src);
                        
                        appender_con.find('.portfolio-img-holder').html('<span style="background-image: url(' + port_img_src + ')"></span>');
                        appender_con.find('figcaption span').html(port_title);
                    }
                });
                
                // For languages
                jQuery(document).on('change', '#jobsearch-resume-langs-con input,#jobsearch-resume-langs-con select', function() {
                    var _thisfield = jQuery(this);
                    if (typeof _thisfield.attr('name') !== 'undefined' && _thisfield.attr('name') != '' && _thisfield.attr('name') != 'undefined' && _thisfield.attr('type') != 'file') {
                        var parent_ul = _thisfield.parents('.jobsearch-update-resume-items-sec');
                        var appender_con = parent_ul.parents('li').find('.jobsearch-add-skills-wrap');
                        var lang_title = parent_ul.find('input[name="jobsearch_field_lang_title[]"]').val();
                        var lang_percent = parent_ul.find('input[name="jobsearch_field_lang_percentage[]"]').val();
                        
                        lang_title = jobsearch_escape_useresume_fileds(lang_title);
                        lang_percent = jobsearch_escape_useresume_fileds(lang_percent);
                        
                        appender_con.html('<span>' + lang_percent + '</span>\
                        <h2 class="jobsearch-pst-title"><a>' + lang_title + '</a></h2>');
                    }
                });
                
                // For honors & awards
                jQuery(document).on('change', '#jobsearch-resume-awards-con input,#jobsearch-resume-awards-con select', function() {
                    var _thisfield = jQuery(this);
                    if (typeof _thisfield.attr('name') !== 'undefined' && _thisfield.attr('name') != '' && _thisfield.attr('name') != 'undefined' && _thisfield.attr('type') != 'file') {
                        var parent_ul = _thisfield.parents('.jobsearch-update-resume-items-sec');
                        var appender_con = parent_ul.parents('li').find('.jobsearch-resume-education-wrap');
                        var aw_title = parent_ul.find('input[name="jobsearch_field_award_title[]"]').val();
                        var aw_year = parent_ul.find('input[name="jobsearch_field_award_year[]"]').val();
                        
                        aw_title = jobsearch_escape_useresume_fileds(aw_title);
                        aw_year = jobsearch_escape_useresume_fileds(aw_year);
                        
                        appender_con.html('<small>' + aw_year + '</small>\
                        <h2 class="jobsearch-pst-title"><a>' + aw_title + '</a></h2>');
                    }
                });
                
                // Portfolio load on page load
                if (jQuery('#jobsearch-resume-portfolio-con').length > 0 && jQuery('#cand-portfolio-img-0').length > 0) {
                    jQuery.ajax({
                        url: '<?php echo admin_url('admin-ajax.php') ?>',
                        method: "POST",
                        data: {
                            load: 'portfolio',
                            action: 'jobsearch_candash_load_portfolio_html_call',
                        },
                        dataType: "json",
                        success: function(data) {
                            jQuery('#jobsearch-resume-portfolio-con').append(data.html);
                        }
                    });
                }
            </script>
            <?php
        }, 30);
        ob_start();
        jobsearch_terms_and_con_link_txt($termscon_chek);
        $upres_btn = ob_get_clean();
        echo apply_filters('jobsearch_canddash_resumesett_update_termscon', $upres_btn, $candidate_id);

        ob_start();
        ?>
        <input type="submit" class="jobsearch-employer-profile-submit" value="<?php esc_html_e('Update Resume', 'wp-jobsearch') ?>">
        <?php
        $upres_btn = ob_get_clean();
        echo apply_filters('jobsearch_canddash_resume_update_mainbtn', $upres_btn, $candidate_id);
        ?>
    </form>
    <?php
}