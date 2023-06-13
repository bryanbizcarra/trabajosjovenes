<?php

if (!class_exists('jobsearch_msg_to_save_cand_template')) {

    class jobsearch_msg_to_save_cand_template {

        public $email_template_type;
        public $codes;
        public $type;
        public $group;
        public $user;
        public $job_id;
        public $message_subject;
        public $message_contentt;
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

            add_action('init', array($this, 'jobsearch_msg_to_save_cand_template_init'), 1, 0);
            add_filter('jobsearch_msg_to_save_cand_filter', array($this, 'jobsearch_msg_to_save_cand_filter_callback'), 1, 4);
            add_filter('jobsearch_email_template_settings', array($this, 'template_settings_callback'), 12, 1);
            add_action('jobsearch_msg_to_save_cand_byemp_email', array($this, 'jobsearch_msg_to_save_cand_callback'), 10, 4);
        }

        public function jobsearch_msg_to_save_cand_template_init() {
            $this->user = array();
            $this->rand = rand(0, 99999);
            $this->group = 'job';
            $this->type = 'msg_to_save_cand';
            $this->filter = 'msg_to_save_cand';
            $this->email_template_db_id = 'msg_to_save_cand';
            $this->switch_label = esc_html__('Message to saved Candidate by Employer', 'wp-jobsearch');
            $this->default_subject = '{subject}';
            $this->default_recipients = '';
            $default_content = esc_html__('Default content', 'wp-jobsearch');
            $default_content = apply_filters('jobsearch_msg_to_save_cand_filter', $default_content, 'html', 'msgto-saved-candidate', 'email-templates');
            $this->default_content = $default_content;
            $this->email_template_prefix = 'msg_to_save_cand';
            $this->email_template_group = 'employer';
            $this->codes = apply_filters('jobsearch_message_tosavecand_byemp_codes', array(
                // value_callback replace with function_callback tag replace with var
                array(
                    'var' => '{subject}',
                    'display_text' => esc_html__('Message Subject', 'wp-jobsearch'),
                    'function_callback' => array($this, 'get_message_subject'),
                ),
                array(
                    'var' => '{message}',
                    'display_text' => esc_html__('Message', 'wp-jobsearch'),
                    'function_callback' => array($this, 'get_message_content'),
                ),
                array(
                    'var' => '{candidate_name}',
                    'display_text' => esc_html__('Candidate name', 'wp-jobsearch'),
                    'function_callback' => array($this, 'get_candidate_name'),
                ),
                array(
                    'var' => '{employer_name}',
                    'display_text' => esc_html__('Employer Name', 'wp-jobsearch'),
                    'function_callback' => array($this, 'get_employer_name'),
                ),
                array(
                    'var' => '{employer_url}',
                    'display_text' => esc_html__('Employer URL', 'wp-jobsearch'),
                    'function_callback' => array($this, 'get_employer_url'),
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

        public function jobsearch_msg_to_save_cand_callback($user = '', $job_id = '', $msg_subject = '', $msg_content = '') {

            global $sitepress, $jobsearch_plugin_options, $jobsearch_glob_userobj;
            $lang_code = '';
            if ( function_exists('icl_object_id') && function_exists('wpml_init_language_switcher') ) {
                $lang_code = $sitepress->get_current_language();
            }
            
            $jobsearch_glob_userobj = $user;
            
            $this->user = $user;
            $this->job_id = $job_id;
            $this->message_subject = $msg_subject;
            $this->message_content = $msg_content;
            
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
                $subject = (isset($template['subject']) && $template['subject'] != '' ) ? $template['subject'] : 'Message';
                $subject = JobSearch_plugin::jobsearch_replace_variables($subject, $this->codes);

                $from = (isset($sender_detail_header) && $sender_detail_header != '') ? $sender_detail_header : esc_attr($blogname) . ' <' . $admin_email . '>';
                $recipients = (isset($template['recipients']) && $template['recipients'] != '') ? $template['recipients'] : $user->user_email;
                $recipients = apply_filters('jobsearch_job_aplyto_emp_email_recipients', $recipients, $job_id);
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
                //var_dump($att_file_id);
                $user_email = $this->get_job_added_email();
                $user_name = $this->get_job_added_posted_by();
                
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
                if (isset($att_file_id) && is_numeric($att_file_id) && $att_file_id > 0 && get_post_type($att_file_id) == 'attachment') {
                    $att_file_path = get_attached_file($att_file_id);
                    //var_dump($att_file_path);
                    if ($att_file_path != '') {
                        $args['att_file_path'] = $att_file_path;
                    }
                } else if (isset($file_path) && $file_path != '') {
                    $args['att_file_path'] = $file_path;
                }
                do_action('jobsearch_send_mail', $args);
                jobsearch_msg_to_save_cand_template::$is_email_sent1 = $this->is_email_sent;
            }
        }

        public static function template_path() {
            return apply_filters('jobsearch_plugin_template_path', 'wp-jobsearch/');
        }

        public function jobsearch_msg_to_save_cand_filter_callback($html, $slug = '', $name = '', $ext_template = '') {
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
            $email_template_options['msg_to_save_cand']['rand'] = $this->rand;
            $email_template_options['msg_to_save_cand']['email_template_prefix'] = $this->email_template_prefix;
            $email_template_options['msg_to_save_cand']['email_template_group'] = $this->email_template_group;
            $email_template_options['msg_to_save_cand']['default_var'] = $this->default_var;
            return $email_template_options;
        }

        public function get_template() {
            return JobSearch_plugin::get_template($this->email_template_db_id, $this->codes, $this->default_content);
        }

        public function get_job_added_email() {

            $job_posted_by = get_post_meta($this->job_id, 'jobsearch_field_job_posted_by', true);
            if ($job_posted_by) {
                $employer_user_id = jobsearch_get_employer_user_id($job_posted_by);
                $user_obj = get_user_by('ID', $employer_user_id);

                $email = $user_obj->user_email;
                return $email;
            }
        }

        public function get_candidate_name() {

            $user_name = $this->user->display_name;
            $user_obj = $this->user;
            $user_name = apply_filters('jobsearch_user_display_name', $user_name, $user_obj);
            return $user_name;
        }

        public function get_candidate_profile_link() {

            $user_id = $this->user->ID;
            $profile_link = '-';
            $user_is_candidate = jobsearch_user_is_candidate($user_id);
            if ($user_is_candidate) {
                $candidate_id = jobsearch_get_user_candidate_id($user_id);
                $profile_link = get_permalink($candidate_id);
            }
            return $profile_link;
        }

        public function get_job_added_jobtitle() {
            $job_title = get_the_title($this->job_id);
            return $job_title;
        }

        public function get_job_added_joburl() {
            $job_title = get_permalink($this->job_id);
            return $job_title;
        }

        public function get_message_subject() {
            $subject = $this->message_subject;
            
            $subject = $subject != '' ? $subject : '-';
            
            return $subject;
        }

        public function get_message_content() {
            $message = $this->message_content;
            
            $message = $message != '' ? $message : '-';
            
            return $message;
        }

        public function get_job_added_posted_by() {
            $job_posted_by = get_post_meta($this->job_id, 'jobsearch_field_job_posted_by', true);
            $job_posted_by_user = get_the_title($job_posted_by);
            return $job_posted_by_user;
        }

        public function get_job_added_employer_logo() {
            $job_posted_by = get_post_meta($this->job_id, 'jobsearch_field_job_posted_by', true);
            $post_thumbnail_id = get_post_thumbnail_id($job_posted_by);
            $post_thumbnail_image = wp_get_attachment_image_src($post_thumbnail_id, 'thumbnail');
            $post_thumbnail_src = isset($post_thumbnail_image[0]) && esc_url($post_thumbnail_image[0]) != '' ? $post_thumbnail_image[0] : '';
            $image_html = '-';
            if ($post_thumbnail_src != '') {
                $image_html .= '<img src="' . esc_url($post_thumbnail_src) . '" alt="">';
            }
            return $image_html;
        }

    }

    new jobsearch_msg_to_save_cand_template();
}