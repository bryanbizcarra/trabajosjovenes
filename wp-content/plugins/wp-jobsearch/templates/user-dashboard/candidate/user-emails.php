<?php
global $jobsearch_plugin_options, $Jobsearch_User_Dashboard_Settings, $sitepress;
$user_id = get_current_user_id();
$user_obj = get_user_by('ID', $user_id);

$page_id = $user_dashboard_page = isset($jobsearch_plugin_options['user-dashboard-template-page']) ? $jobsearch_plugin_options['user-dashboard-template-page'] : '';
$page_id = $user_dashboard_page = jobsearch__get_post_id($user_dashboard_page, 'page');
$page_url = jobsearch_wpml_lang_page_permalink($page_id, 'page'); //get_permalink($page_id);

$candidate_id = jobsearch_get_user_candidate_id($user_id);
$reults_per_page = isset($jobsearch_plugin_options['user-dashboard-per-page']) && $jobsearch_plugin_options['user-dashboard-per-page'] > 0 ? $jobsearch_plugin_options['user-dashboard-per-page'] : 10;
$page_num = isset($_GET['page_num']) ? $_GET['page_num'] : 1;

if ($candidate_id > 0) {
    $user_email = isset($user_obj->user_email) ? $user_obj->user_email : '';
    ?>
    <div class="jobsearch-employer-dasboard">
        <div class="jobsearch-employer-box-section">

            <div class="jobsearch-profile-title">
                <h2><?php esc_html_e('Emails', 'wp-jobsearch') ?></h2>
            </div>
            <?php
            if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                $sitepress_def_lang = $sitepress->get_default_language();
                $sitepress_curr_lang = $sitepress->get_current_language();
                $sitepress->switch_lang($sitepress_def_lang, true);
            }
            $args = array(
                'post_type' => 'email',
                'posts_per_page' => $reults_per_page,
                'paged' => $page_num,
                'post_status' => 'publish',
                'order' => 'DESC',
                'orderby' => 'ID',
                'meta_query' => array(
                    array(
                        'key' => 'email_send_to',
                        'value' => $user_email,
                        'compare' => 'LIKE',
                    ),
                ),
            );
            $email_query = new WP_Query($args);
            $total_trans = $email_query->found_posts;
            if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                $sitepress->switch_lang($sitepress_curr_lang, true);
            }
            if ($email_query->have_posts()) {
                ?>
                <div class="jobsearch-transactions-list-holder">
                    <div class="jobsearch-employer-transactions">
                        <div class="jobsearch-table-layer jobsearch-transactions-thead">
                            <div class="jobsearch-table-row">
                                <div class="jobsearch-table-cell"><?php esc_html_e('Subject', 'wp-jobsearch') ?></div>
                                <div class="jobsearch-table-cell"><?php esc_html_e('Sending Date/Time', 'wp-jobsearch') ?></div>
                                <div class="jobsearch-table-cell"><?php esc_html_e('Status', 'wp-jobsearch') ?></div>
                            </div>
                        </div>
                        <?php
                        //
                        while ($email_query->have_posts()) : $email_query->the_post();
                            $email_rand = rand(10000000, 99999999);
                            $email_id = get_the_ID();
                            $email_status = get_post_meta($email_id, 'jobsearch_email_read_status', true);
                            $email_status_str = esc_html__('Unread', 'wp-jobsearch');
                            if ($email_status == '1') {
                                $email_status_str = esc_html__('Read', 'wp-jobsearch');
                            }
                            $email_sending_date = get_the_time('l, F j, Y H:i:s', $email_id);
                            $email_content = get_the_content($email_id);
                            ?>
                            <div class="jobsearch-table-layer jobsearch-transactions-tbody">
                                <div class="jobsearch-table-row">
                                    <div class="jobsearch-table-cell"><a href="javascript:void(0);" class="email-detailbox-btn" data-ststr="<?php esc_html_e('Read', 'wp-jobsearch') ?>" data-id="<?php echo ($email_id) ?>"><?php echo get_the_title($email_id) ?></a></div>
                                    <div class="jobsearch-table-cell"><?php echo ($email_sending_date) ?></div>
                                    <div class="jobsearch-table-cell">
                                        <span id="email-status-<?php echo ($email_id) ?>" class="email-status-core"><?php echo ($email_status_str) ?></span>
                                        <?php
                                        ob_start();
                                        ?>
                                        <a href="javascript:void(0);" class="reply-tothis-email" data-id="<?php echo ($email_id) ?>" data-subjct="<?php echo get_the_title($email_id) ?>"> <?php esc_html_e('(Reply)', 'wp-jobsearch') ?></a>
                                        <?php
                                        $email_reply_btn = ob_get_clean();
                                        echo apply_filters('jobsearch_candash_email_reply_btn_html', $email_reply_btn, $email_id);
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                            //
                            $popup_args = array('p_content' => $email_content, 'email_id' => $email_id);
                            add_action('wp_footer', function () use ($popup_args) {
                                extract(shortcode_atts(array(
                                    'p_content' => '',
                                    'email_id' => ''
                                                ), $popup_args));
                                ?>
                                <div class="jobsearch-modal user-email-logpop fade" id="JobSearchModalEmailLog<?php echo ($email_id) ?>">
                                    <div class="modal-inner-area">&nbsp;</div>
                                    <div class="modal-content-area">
                                        <div class="modal-box-area">
                                            <span class="modal-close"><i class="fa fa-times"></i></span>
                                            <?php echo ($p_content) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }, 20, 1);
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
                <?php
                $total_pages = 1;
                if ($total_trans > 0 && $reults_per_page > 0 && $total_trans > $reults_per_page) {
                    $total_pages = ceil($total_trans / $reults_per_page);
                    ?>
                    <div class="jobsearch-pagination-blog">
                        <?php $Jobsearch_User_Dashboard_Settings->pagination($total_pages, $page_num, $page_url) ?>
                    </div>
                    <?php
                }
                add_action('wp_footer', function() {
                    ?>
                    <div class="jobsearch-modal reply-email-pop fade" id="JobSearchModalReplyEmailPop">
                        <div class="modal-inner-area">&nbsp;</div>
                        <div class="modal-content-area">
                            <div class="modal-box-area">
                                <div class="jobsearch-modal-title-box">
                                    <h2><?php esc_html_e('Reply', 'wp-jobsearch') ?></h2>
                                    <span class="modal-close"><i class="fa fa-times"></i></span>
                                </div>
                                <div class="jobsearch-send-message-form">
                                    <form autocomplete="off" method="post" id="jobsearch_reply_email_form">
                                        <div class="jobsearch-user-form">
                                            <ul class="email-fields-list">
                                                <li>
                                                    <label>
                                                        <?php echo esc_html__('Subject', 'wp-jobsearch'); ?>:
                                                    </label>
                                                    <div class="input-field">
                                                        <input type="text" name="send_message_subject" value="">
                                                    </div>
                                                </li>
                                                <li>
                                                    <label>
                                                        <?php echo esc_html__('Message', 'wp-jobsearch'); ?> :
                                                    </label>
                                                    <div class="input-field">
                                                        <textarea name="send_message_content"></textarea>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="input-field-submit">
                                                        <input type="submit" class="jobearch-replyemail-submitbtn"
                                                               data-eid="0"
                                                               value="<?php esc_html_e('Send', 'wp-jobsearch') ?>"/>
                                                        <span class="loader-box"></span>
                                                    </div>
                                                </li>
                                            </ul>
                                            <div class="message-box" style="display:none;"></div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <script>
                            jQuery(document).on('click', '.reply-tothis-email', function () {
                                var this_id = jQuery(this).attr('data-id');
                                var email_subjct = jQuery(this).attr('data-subjct');
                                jQuery('#JobSearchModalReplyEmailPop').find('.jobearch-replyemail-submitbtn').attr('data-eid', this_id);
                                jQuery('#JobSearchModalReplyEmailPop').find('input[name="send_message_subject"]').val('<?php esc_html_e('Reply', 'wp-jobsearch') ?>: ' + email_subjct);
                                jQuery('#JobSearchModalReplyEmailPop').find('ul.email-fields-list').removeAttr('style');
                                jQuery('#JobSearchModalReplyEmailPop').find('form').find('.message-box').hide();
                                jobsearch_modal_popup_open('JobSearchModalReplyEmailPop');
                            });
                        </script>
                    </div>
                    <?php
                }, 25);
            } else {
                ?>
                <p><?php esc_html_e('No record found.', 'wp-jobsearch') ?></p>
                <?php
            }
            ?>
        </div>
    </div>
    <?php
}