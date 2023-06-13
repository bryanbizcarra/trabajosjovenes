<?php

if (!class_exists('jobsearch_acc_member_by_employer_template')) {

    class jobsearch_acc_member_by_employer_template {

        public $email_template_type;
        public $codes;
        public $type;
        public $group;
        public $user;
        public $employer_id;
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

            add_action('init', array($this, 'jobsearch_acc_member_by_employer_template_init'), 1, 0);
            add_filter('jobsearch_acc_member_by_employer_filter', array($this, 'jobsearch_acc_member_by_employer_filter_callback'), 1, 4);
            add_filter('jobsearch_email_template_settings', array($this, 'template_settings_callback'), 12, 1);
            add_action('jobsearch_add_acc_member_by_employer_email', array($this, 'jobsearch_acc_member_by_employer_callback'), 10, 2);
        }

        public function jobsearch_acc_member_by_employer_template_init() {
            $this->rand = rand(0, 99999);
            $this->group = 'job';
            $this->type = 'acc_member_by_employer';
            $this->filter = 'acc_member_by_employer';
            $this->email_template_db_id = 'acc_member_by_employer';
            $this->switch_label = esc_html__('To account member by employer', 'wp-jobsearch');
            $this->default_subject = esc_html__('You are added as account member', 'wp-jobsearch');
            $this->default_recipients = '';
            $default_content = esc_html__('Default content', 'wp-jobsearch');
            $default_content = apply_filters('jobsearch_acc_member_by_employer_filter', $default_content, 'html', 'member-by-employer', 'email-templates');
            $this->default_content = $default_content;
            $this->email_template_prefix = 'acc_member_by_employer';
            $this->email_template_group = 'employer';
            $this->codes = array(
                // value_callback replace with function_callback tag replace with var
                array(
                    'var' => '{username}',
                    'display_text' => esc_html__('Member Name', 'wp-jobsearch'),
                    'function_callback' => array($this, 'get_username'),
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
            );

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

        public function jobsearch_acc_member_by_employer_callback($user = '', $employer_id = '') {

            global $sitepress;
            $lang_code = '';
            if ( function_exists('icl_object_id') && function_exists('wpml_init_language_switcher') ) {
                $lang_code = $sitepress->get_current_language();
            }
            
            $this->user = $user;
            $this->employer_id = $employer_id;
            
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
                $subject = (isset($template['subject']) && $template['subject'] != '' ) ? $template['subject'] : __('You are added as account member', 'wp-jobsearch');
                $subject = JobSearch_plugin::jobsearch_replace_variables($subject, $this->codes);
                
                $from = (isset($sender_detail_header) && $sender_detail_header != '') ? $sender_detail_header : esc_attr($blogname) . ' <' . $admin_email . '>';
                $recipients = (isset($template['recipients']) && $template['recipients'] != '') ? $template['recipients'] : $this->user->user_email;
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
                
                $user_name = get_the_title($employer_id);
                $emp_user_id = get_post_meta($employer_id, 'jobsearch_user_id', true);
                $employer_user = get_user_by('id', $emp_user_id);
                $user_email = isset($employer_user->user_email) ? $employer_user->user_email : '';

                $args = array(
                    'to' => $recipients,
                    'subject' => $subject,
                    'from' => $from,
                    'from_email' => $user_email,
                    'from_name' => $user_name,
                    'message' => $email_message,
                    'email_type' => $email_type,
                    'class_obj' => $this, // temprary comment
                );
                do_action('jobsearch_send_mail', $args);
                jobsearch_acc_member_by_employer_template::$is_email_sent1 = $this->is_email_sent;
                return true;
            }
        }

        public static function template_path() {
            return apply_filters('jobsearch_plugin_template_path', 'wp-jobsearch/');
        }

        public function jobsearch_acc_member_by_employer_filter_callback($html, $slug = '', $name = '', $ext_template = '') {
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
            $email_template_options['acc_member_by_employer']['rand'] = $this->rand;
            $email_template_options['acc_member_by_employer']['email_template_prefix'] = $this->email_template_prefix;
            $email_template_options['acc_member_by_employer']['email_template_group'] = $this->email_template_group;
            $email_template_options['acc_member_by_employer']['default_var'] = $this->default_var;
            return $email_template_options;
        }

        public function get_template() {
            return JobSearch_plugin::get_template($this->email_template_db_id, $this->codes, $this->default_content);
        }

        public function get_username() {

            $user_name = $this->user->display_name;
            return $user_name;
        }
        
        public function get_employer_name() {

            $employer_id = $this->employer_id;
            $employer_name = get_the_title($employer_id);
            
            return $employer_name;
        }
        
        public function get_employer_url() {

            $employer_id = $this->employer_id;
            $employer_url = get_permalink($employer_id);
            
            return $employer_url;
        }

    }

    new jobsearch_acc_member_by_employer_template();
}