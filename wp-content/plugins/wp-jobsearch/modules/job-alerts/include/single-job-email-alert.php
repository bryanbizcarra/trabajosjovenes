<?php

if (!class_exists('jobsearch_single_job_alert_template')) {

    class jobsearch_single_job_alert_template {

        public $email_template_type;
        public $codes;
        public $type;
        public $group;
        public $alert_detail;
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

            add_action('init', array($this, 'jobsearch_single_job_alert_template_init'), 1, 0);
            add_filter('jobsearch_single_job_alert_filter', array($this, 'jobsearch_single_job_alert_filter_callback'), 1, 4);
            add_filter('jobsearch_email_template_settings', array($this, 'template_settings_callback'), 12, 1);
            add_action('jobsearch_new_single_job_email_alert', array($this, 'jobsearch_single_job_alert_callback'), 10, 1);
        }

        public function jobsearch_single_job_alert_template_init() {
            $this->alert_detail = array();
            $this->rand = rand(0, 99999);
            $this->group = 'job';
            $this->type = 'single_job_alert';
            $this->filter = 'single_job_alert';
            $this->email_template_db_id = 'single_job_alert';
            $this->switch_label = esc_html__('Single Job Alert Email', 'wp-jobsearch');
            $this->default_subject = esc_html__('New Job found for you', 'wp-jobsearch');
            $this->default_recipients = '';
            $default_content = esc_html__('Default content', 'wp-jobsearch');
            $default_content = apply_filters('jobsearch_single_job_alert_filter', $default_content, 'html', 'single-job-alert', '');
            $this->default_content = $default_content;
            $this->email_template_prefix = 'single_job_alert';
            $this->email_template_group = 'candidate';
            $this->codes = array(
                // value_callback replace with function_callback tag replace with var
                array(
                    'var' => '{candidate_title}',
                    'display_text' => esc_html__('Candidate Title', 'wp-jobsearch'),
                    'function_callback' => array($this, 'get_candidate_title'),
                ),
                array(
                    'var' => '{job_info}',
                    'display_text' => esc_html__('Job Info', 'wp-jobsearch'),
                    'function_callback' => array($this, 'get_job_info'),
                ),
                array(
                    'var' => '{jobs_listing_url}',
                    'display_text' => esc_html__('Jobs Listing URL', 'wp-jobsearch'),
                    'function_callback' => array($this, 'get_full_listing_url'),
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

        public function jobsearch_single_job_alert_callback($alert_detail = array()) {

            global $sitepress;
            $lang_code = '';
            if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                $lang_code = $sitepress->get_current_language();
            }

            $this->alert_detail = $alert_detail;
            $job_id = isset($this->alert_detail['job_id']) ? $this->alert_detail['job_id'] : 0;
            $candidate_id = isset($this->alert_detail['cand_id']) ? $this->alert_detail['cand_id'] : 0;
            
            $candidate_user_id = get_post_meta($candidate_id, 'jobsearch_user_id', true);
            $candidate_user_obj = get_user_by('id', $candidate_user_id);
            if (!isset($candidate_user_obj->user_email)) {
                return false;
            }
            
            $candidate_email = $candidate_user_obj->user_email;
            
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
                $subject = (isset($template['subject']) && $template['subject'] != '' ) ? $template['subject'] : sprintf(__('Job alert from %s', 'wp-jobsearch'), get_bloginfo('name'));
                $subject = JobSearch_plugin::jobsearch_replace_variables($subject, $this->codes);
                
                $from = (isset($sender_detail_header) && $sender_detail_header != '') ? $sender_detail_header : esc_attr($blogname) . ' <' . $admin_email . '>';

                $recipients = (isset($template['recipients']) && $template['recipients'] != '') ? $template['recipients'] : $candidate_email;
                $email_type = (isset($template['email_type']) && $template['email_type'] != '') ? $template['email_type'] : 'html';

                $email_message = isset($template['email_template']) ? $template['email_template'] : '';

                if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
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

                $args = array(
                    'to' => $recipients,
                    'subject' => $subject,
                    'from' => $from,
                    'message' => $email_message,
                    'email_type' => $email_type,
                    'class_obj' => $this, // temprary comment
                );
                do_action('jobsearch_send_mail', $args);
                update_post_meta($candidate_id, 'jobsearch_email_alert_job_' . $job_id, 1);
                jobsearch_single_job_alert_template::$is_email_sent1 = $this->is_email_sent;
            }
        }

        public static function template_path() {
            return apply_filters('jobsearch_plugin_template_path', 'wp-jobsearch/');
        }

        public function jobsearch_single_job_alert_filter_callback($html, $slug = '', $name = '', $ext_template = '') {
            ob_start();
            $html = '';
            $template = '';
            if ($ext_template != '') {
                $ext_template = trailingslashit($ext_template);
            }
            if ($name) {
                $template = locate_template(array("{$slug}-{$name}.php", self::template_path() . "{$ext_template}/{$slug}-{$name}.php"));
            }
            if (!$template && $name && file_exists(jobsearch_plugin_get_path() . "modules/job-alerts/templates/{$ext_template}/{$slug}-{$name}.php")) {
                $template = jobsearch_plugin_get_path() . "modules/job-alerts/templates/{$ext_template}{$slug}-{$name}.php";
            }

            if ($template) {
                load_template($template, false);
            }
            $html = ob_get_clean();
            return $html;
        }

        public function template_settings_callback($email_template_options) {

            $rand = rand(123, 8787987);
            $email_template_options['single_job_alert']['rand'] = $this->rand;
            $email_template_options['single_job_alert']['email_template_prefix'] = $this->email_template_prefix;
            $email_template_options['single_job_alert']['email_template_group'] = $this->email_template_group;
            $email_template_options['single_job_alert']['default_var'] = $this->default_var;
            return $email_template_options;
        }

        public function get_template() {
            return JobSearch_plugin::get_template($this->email_template_db_id, $this->codes, $this->default_content);
        }

        public function get_candidate_title() {
            $cand_id = isset($this->alert_detail['cand_id']) ? $this->alert_detail['cand_id'] : 0;
            
            $title = get_the_title($cand_id);
            
            return $title;
        }

        public function get_job_info() {
            global $jobsearch_plugin_options;
            
            $job_id = isset($this->alert_detail['job_id']) ? $this->alert_detail['job_id'] : 0;
            
            $sectors_enable_switch = isset($jobsearch_plugin_options['sectors_onoff_switch']) ? $jobsearch_plugin_options['sectors_onoff_switch'] : '';
            $job_views_publish_date = isset($jobsearch_plugin_options['job_views_publish_date']) ? $jobsearch_plugin_options['job_views_publish_date'] : '';
            
            ob_start();
            ?>
            <table cellspacing="0" width="100%" style="border-spacing: 0em 0.7em;">
                <tbody>
                    <?php
                    $job_publish_date = get_post_meta($job_id, 'jobsearch_field_job_publish_date', true);
                    $post_thumbnail_id = jobsearch_job_get_profile_image($job_id);
                    $post_thumbnail_image = wp_get_attachment_image_src($post_thumbnail_id, 'thumbnail');
                    $post_thumbnail_src = isset($post_thumbnail_image[0]) && esc_url($post_thumbnail_image[0]) != '' ? $post_thumbnail_image[0] : '';
                    $post_thumbnail_src = $post_thumbnail_src == '' ? jobsearch_no_image_placeholder() : $post_thumbnail_src;
                    $jobsearch_job_featured = get_post_meta($job_id, 'jobsearch_field_job_featured', true);
                    $company_name = '';
                    $job_field_user = get_post_meta($job_id, 'jobsearch_field_job_posted_by', true);
                    if (isset($job_field_user) && $job_field_user != '') {
                        $company_name = '<a href="' . get_permalink($job_field_user) . '" style="font-size: 14px; color: #05053d !important; text-decoration: none !important;">@ ' . get_the_title($job_field_user) . '</a>';
                    }
                    $get_job_location = get_post_meta($job_id, 'jobsearch_field_location_address', true);

                    $job_city_title = jobsearch_post_city_contry_txtstr($job_id, true, true, true);

                    $job_type_str = jobsearch_job_get_all_jobtypes($job_id, 'jobsearch-option-btn');
                    $sector_str = jobsearch_job_get_all_sectors($job_id, '', '', '', '<li><i class="jobsearch-icon jobsearch-filter-tool-black-shape"></i>', '</li>');
                    ?>
                    <tr>
                        <td style="border: 2px solid #a09c9c; border-left: none; border-right: none; padding-bottom: 30px; padding-top: 10px;">
                            <div style="float: left; width: 100%; margin-bottom: 15px;">
                                <h2 style="display: block; font-size: 18px; margin-bottom: -10px;"><a href="<?php echo (get_permalink($job_id)) ?>" style="color: #377dff !important; line-height: 18px !important; font-weight: bold !important; text-decoration: none !important;"><?php echo (get_the_title($job_id)) ?></a></h2>
                                <?php
                                if ($company_name != '') {
                                    ?>
                                    <br> <small style="font-size: 14px; margin-right: 10px; color: #05053d !important; line-height: 18px !important;"><?php echo ($company_name) ?></small>
                                    <?php
                                }
                                if ($job_publish_date != '') {
                                    ?>
                                    <br> <small style="font-size: 14px; margin-right: 10px; color: #05053d !important; line-height: 18px !important;"><?php printf(esc_html__('Published %s', 'wp-jobsearch'), jobsearch_time_elapsed_string($job_publish_date)); ?></small>
                                    <?php
                                }
                                ?>
                            </div>
                            <div style="float: left; width: 100%; padding: 10px 0;"><a href="<?php echo (get_permalink($job_id)) ?>" style="text-decoration: none; padding: 10px 22px; color: #fff; background-color: #377dff; font-size: 13px; outline: none; "><?php esc_html_e('Apply for this Job', 'wp-jobsearch'); ?></a></div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
            $html1 = ob_get_clean();
            return $html1;
        }

        public function get_full_listing_url() {
            $jobsearch__options = get_option('jobsearch_plugin_options');
            $jobs_search_page = isset($jobsearch__options['jobsearch_search_list_page']) ? $jobsearch__options['jobsearch_search_list_page'] : '';
            $page_id = jobsearch__get_post_id($jobs_search_page, 'page');
            
            $url_attrs = [];
            $job_id = isset($this->alert_detail['job_id']) ? $this->alert_detail['job_id'] : 0;
            $sec_terms_arr = wp_get_post_terms($job_id, 'sector', array('fields' => 'ids'));
            if (isset($sec_terms_arr[0])) {
                $job_sector_id = $sec_terms_arr[0];
                $sector_term = get_term_by('id', $job_sector_id, 'sector');
                if (isset($sector_term->slug)) {
                    $url_attrs['sector_cat'] = $sector_term->slug;
                }
            }
            
            if ($page_id > 0) {
                $page_url = get_permalink($page_id);
            } else {
                $page_url = home_url('/');
            }
            if (!empty($url_attrs)) {
                $url_attrs['sort-by'] = 'recent';
                $url_attrs['ajax_filter'] = 'true';
                $page_url = add_query_arg($url_attrs, $page_url);
            }
            
            return $page_url;
        }

    }

    new jobsearch_single_job_alert_template();
}