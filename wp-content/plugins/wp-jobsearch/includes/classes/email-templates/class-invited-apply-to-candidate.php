<?php

if (!class_exists('jobsearch_invite_apply_to_candidate_template')) {

    class jobsearch_invite_apply_to_candidate_template {

        public $email_template_type;
        public $codes;
        public $type;
        public $group;
        public $user;
        public $emp_user_id;
        public $job_id;
        public $is_email_sent;
        public $email_template_prefix;
        public $email_template_group;
        public $default_content;
        public $default_subject;
        public $default_recipients;
        public $switch_label;
        public $email_template_db_id;
        public $default_var;
        public $rand;
        public static $is_email_sent1;

        public function __construct() {

            add_action('init', array($this, 'jobsearch_invite_apply_to_candidate_template_init'), 1, 0);
            add_filter('jobsearch_invite_apply_to_candidate_filter', array($this, 'jobsearch_invite_apply_to_candidate_filter_callback'), 1, 4);
            add_filter('jobsearch_email_template_settings', array($this, 'template_settings_callback'), 12, 1);
            add_action('jobsearch_invite_apply_to_candidate', array($this, 'jobsearch_invite_apply_to_candidate_callback'), 10, 3);
        }

        public function jobsearch_invite_apply_to_candidate_template_init() {
            $this->user = array();
            $this->rand = rand(0, 99999);
            $this->group = 'job';
            $this->type = 'invite_apply_to_candidate';
            $this->filter = 'invite_apply_to_candidate';
            $this->email_template_db_id = 'invite_apply_to_candidate';
            $this->switch_label = esc_html__('Invite Candidate to Apply', 'wp-jobsearch');
            $this->default_subject = esc_html__('You are invited to apply job', 'wp-jobsearch');
            $this->default_recipients = '';
            $default_content = esc_html__('Default content', 'wp-jobsearch');
            $default_content = apply_filters('jobsearch_invite_apply_to_candidate_filter', $default_content, 'html', 'job-invited-to-candidate', 'email-templates');
            $this->default_content = $default_content;
            $this->email_template_prefix = 'invite_apply_to_candidate';
            $this->email_template_group = 'candidate';
            $this->codes = apply_filters('jobsearch_jobaply_by_cand_tocand_codes', array(
                // value_callback replace with function_callback tag replace with var
                array(
                    'var' => '{candidate_name}',
                    'display_text' => esc_html__('Candidate name', 'wp-jobsearch'),
                    'function_callback' => array($this, 'get_candidate_name'),
                ),
                array(
                    'var' => '{job_posted_by}',
                    'display_text' => esc_html__('Employer Name', 'wp-jobsearch'),
                    'function_callback' => array($this, 'get_job_added_posted_by'),
                ),
                array(
                    'var' => '{job_employer_url}',
                    'display_text' => esc_html__('Employer URL', 'wp-jobsearch'),
                    'function_callback' => array($this, 'get_job_employer_url'),
                ),
                array(
                    'var' => '{jobs_list}',
                    'display_text' => esc_html__('Jobs list for invite', 'wp-jobsearch'),
                    'function_callback' => array($this, 'get_jobs_list'),
                ),
            ), $this);

            $this->default_var = array(
                'switch_label' => $this->switch_label,
                'default_subject' => $this->default_subject,
                'default_recipients' => $this->default_recipients,
                'default_content' => $this->default_content,
                'group' => $this->group,
                'type' => $this->type,
                'filter' => $this->filter,
                'codes' => $this->codes,
            );
        }

        public function jobsearch_invite_apply_to_candidate_callback($user = '', $job_ids = array(), $emp_user_id = '') {

            global $sitepress, $jobsearch_plugin_options;
            $lang_code = '';
            if ( function_exists('icl_object_id') && function_exists('wpml_init_language_switcher') ) {
                $lang_code = $sitepress->get_current_language();
            }
            
            $this->user = $user;
            $this->emp_user_id = $emp_user_id;
            $this->job_id = $job_ids;
            $template = $this->get_template();
            // checking email notification is enable/disable
            if (isset($template['switch']) && $template['switch'] == 1) {

                $blogname = get_option('blogname');
                $admin_email = get_option('admin_email');
                $sender_detail_header = '';
                if (isset($template['from']) && $template['from'] != '') {
                    $sender_detail_header = $template['from'];
                    if (isset($template['from_name']) && $template['from_name'] != '') {
                        $sender_detail_header = $template['from_name'] . ' <' . $sender_detail_header . '> ';
                    }
                }

                // getting template fields
                $subject = (isset($template['subject']) && $template['subject'] != '' ) ? $template['subject'] : __('Invite for apply job', 'wp-jobsearch');
                $subject = JobSearch_plugin::jobsearch_replace_variables($subject, $this->codes);
                
                $from = (isset($sender_detail_header) && $sender_detail_header != '') ? $sender_detail_header : esc_attr($blogname) . ' <' . $admin_email . '>';
                $recipients = (isset($template['recipients']) && $template['recipients'] != '') ? $template['recipients'] : $this->get_job_added_email();
                $email_type = (isset($template['email_type']) && $template['email_type'] != '') ? $template['email_type'] : 'html';
                
                $email_message = isset($template['email_template']) ? $template['email_template'] : '';
                
                if ( function_exists('icl_object_id') && function_exists('wpml_init_language_switcher') ) {
                    $temp_trnaslated = get_option('jobsearch_translate_email_templates');
                    $template_type = $this->type;
                    if (isset($temp_trnaslated[$template_type]['lang_' . $lang_code]['subject'])) {
                        $subject = $temp_trnaslated[$template_type]['lang_' . $lang_code]['subject'];
                        $subject = JobSearch_plugin::jobsearch_replace_variables($subject, $this->codes);
                    }
                    if (isset($temp_trnaslated[$template_type]['lang_' . $lang_code]['content'])) {
                        $email_message = $temp_trnaslated[$template_type]['lang_' . $lang_code]['content'];
                        $email_message = JobSearch_plugin::jobsearch_replace_variables($email_message, $this->codes);
                    }
                }
                
                //
                $empp_user = get_user_by('ID', $emp_user_id);
                $user_email = $empp_user->user_email;
                $user_name = $empp_user->display_name;
                $user_name = apply_filters('jobsearch_user_display_name', $user_name, $empp_user);
                
                $args = array(
                    'to' => $recipients,
                    'subject' => $subject,
                    'from' => $from,
                    'from_name' => $user_name,
                    'from_email' => $user_email,
                    'message' => $email_message,
                    'email_type' => $email_type,
                    'class_obj' => $this, // temprary comment
                );
                do_action('jobsearch_send_mail', $args);
                jobsearch_invite_apply_to_candidate_template::$is_email_sent1 = $this->is_email_sent;
            }
        }

        public static function template_path() {
            return apply_filters('jobsearch_plugin_template_path', 'wp-jobsearch/');
        }

        public function jobsearch_invite_apply_to_candidate_filter_callback($html, $slug = '', $name = '', $ext_template = '') {
            ob_start();
            $html = '';
            $template = '';
            if ($ext_template != '') {
                $ext_template = trailingslashit($ext_template);
            }
            if ($name) {
                $template = locate_template(array("{$slug}-{$name}.php", self::template_path() . "templates/{$ext_template}/{$slug}-{$name}.php"));
            }
            if (!$template && $name && file_exists(jobsearch_plugin_get_path() . "templates/{$ext_template}/{$slug}-{$name}.php")) {
                $template = jobsearch_plugin_get_path() . "templates/{$ext_template}{$slug}-{$name}.php";
            }
            if (!$template) {
                $template = locate_template(array("{$slug}.php", self::template_path() . "{$ext_template}/{$slug}.php"));
            }
            //echo $template;exit;
            if ($template) {
                load_template($template, false);
            }
            $html = ob_get_clean();
            return $html;
        }

        public function template_settings_callback($email_template_options) {

            $rand = rand(123, 8787987);
            $email_template_options['invite_apply_to_candidate']['rand'] = $this->rand;
            $email_template_options['invite_apply_to_candidate']['email_template_prefix'] = $this->email_template_prefix;
            $email_template_options['invite_apply_to_candidate']['email_template_group'] = $this->email_template_group;
            $email_template_options['invite_apply_to_candidate']['default_var'] = $this->default_var;
            return $email_template_options;
        }

        public function get_template() {
            return JobSearch_plugin::get_template($this->email_template_db_id, $this->codes, $this->default_content);
        }

        public function get_job_added_email() {

            $user_obj = $this->user;
            $user_email = $user_obj->user_email;
            return $user_email;
        }

        public function get_candidate_name() {

            $user_name = $this->user->display_name;
            $user_obj = $this->user;
            $user_name = apply_filters('jobsearch_user_display_name', $user_name, $user_obj);
            return $user_name;
        }

        public function get_job_added_posted_by() {
            
            $emp_user_id = $this->emp_user_id;
            $empp_user = get_user_by('ID', $emp_user_id);
            $user_email = $empp_user->user_email;
            $user_name = $empp_user->display_name;
            $job_posted_by_user = apply_filters('jobsearch_user_display_name', $user_name, $empp_user);
            return $job_posted_by_user;
        }

        public function get_job_employer_url() {
            
            $emp_user_id = $this->emp_user_id;
            
            $employer_id = jobsearch_get_user_employer_id($emp_user_id);
            
            $emp_url = get_permalink($employer_id);
            
            return $emp_url;
        }

        public function get_jobs_list() {
            $jobs_ids = $this->job_id;
            
            ob_start();
            if (!empty($jobs_ids)) {
                ?>
                <table cellspacing="0" width="100%" style="border-spacing: 0em 0.7em;">
                    <tbody>
                        <?php
                        foreach ($jobs_ids as $job_id) {
                            $job_random_id = rand(1111111, 9999999);

                            //$job_id = get_the_id();
                            $job_publish_date = get_post_meta($job_id, 'jobsearch_field_job_publish_date', true);
                            $post_thumbnail_id = jobsearch_job_get_profile_image($job_id);
                            $post_thumbnail_image = wp_get_attachment_image_src($post_thumbnail_id, 'thumbnail');
                            $post_thumbnail_src = isset($post_thumbnail_image[0]) && esc_url($post_thumbnail_image[0]) != '' ? $post_thumbnail_image[0] : '';
                            $post_thumbnail_src = $post_thumbnail_src == '' ? jobsearch_no_image_placeholder() : $post_thumbnail_src;
                            $jobsearch_job_featured = get_post_meta($job_id, 'jobsearch_field_job_featured', true);
                            $company_name = jobsearch_job_get_company_name($job_id, '@ ');
                            $get_job_location = get_post_meta($job_id, 'jobsearch_field_location_address', true);

                            $job_city_title = jobsearch_post_city_contry_txtstr($job_id, true, true, true);

                            $job_type_str = jobsearch_job_get_all_jobtypes($job_id, 'jobsearch-option-btn');
                            $sector_str = jobsearch_job_get_all_sectors($job_id, '', '', '', '<li><i class="jobsearch-icon jobsearch-filter-tool-black-shape"></i>', '</li>');
                            
                            $candidate_email = $this->get_job_added_email();
                            $apply_url = add_query_arg(array('apply_act' => 'invite', 'email' => $candidate_email), get_permalink($job_id));
                            ?>
                            <tr>
                                <td width="100" style="border: 1px solid #ececec; border-right: none; padding: 19px 0px 19px 19px;"><img src="<?php echo esc_url($post_thumbnail_src) ?>" alt="" style="border-radius: 100%; width: 100px;"></td>
                                <td style="padding-left: 30px; border: 1px solid #ececec; border-left: none; border-right: none;">
                                    <h2 style="display: block; font-size: 18px; margin-bottom: -10px;"><a href="<?php echo (get_permalink($job_id)) ?>"><?php echo (get_the_title($job_id)) ?></a></h2>
                                    <?php
                                    if ($sectors_enable_switch == 'on' && !empty($sector_str)) {
                                        ?>
                                        <br> <small style="font-size: 14px;"><?php esc_html_e('Sector', 'wp-jobsearch'); ?>: <?php echo wp_kses($sector_str, array()) ?></small>
                                        <?php
                                    }
                                    if ($company_name != '') {
                                        ?>
                                        <br> <small style="font-size: 14px;"><?php esc_html_e('Company', 'wp-jobsearch'); ?>: <?php echo ($company_name) ?></small>
                                        <?php
                                    }
                                    if ($job_publish_date != '') {
                                        ?>
                                        <br> <small style="font-size: 14px;"><?php printf(esc_html__('Published %s', 'wp-jobsearch'), jobsearch_time_elapsed_string($job_publish_date)); ?></small>
                                        <?php
                                    }
                                    if ($job_city_title != '') {
                                        ?>
                                        <br> <small style="font-size: 14px;"><?php esc_html_e('Address', 'wp-jobsearch'); ?>: <?php echo ($job_city_title) ?></small>
                                        <?php
                                    } else if (!empty($get_job_location)) {
                                        ?>
                                        <br> <small style="font-size: 14px;"><?php esc_html_e('Address', 'wp-jobsearch'); ?>: <?php echo ($get_job_location) ?></small>
                                        <?php
                                    }
                                    ?>
                                </td>
                                <td style="text-align: right; border: 1px solid #ececec; border-left: none; padding-right: 19px;"><a href="<?php echo ($apply_url) ?>" style="border: 1px solid #ececec; text-decoration: none; padding: 10px 22px; color: #333; font-size: 13px; outline: none; "><?php esc_html_e('Apply', 'wp-jobsearch'); ?></a></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
            } else {
                esc_html_e('No new jobs found.', 'wp-jobsearch');
            }
            $jobs_html = ob_get_clean();
            
            return $jobs_html;
        }

    }

    new jobsearch_invite_apply_to_candidate_template();
}