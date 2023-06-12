<?php

if (!defined('ABSPATH')) {
    die;
}

// main plugin class
class JobSearch_Indeed_Jobs_Scraping_Hooks {

    // hook things up
    public function __construct() {
        add_action('wp_ajax_jobsearch_import_scraping_indeed_jobs', array($this, 'jobsearch_import_indeed_jobs'));
        
        add_action('jobsearch_indeed_scraping_form_html', array($this, 'import_jobs_form'));
    }
    
    public function indeed_countries() {
        $countries = array(
            "https://ar.indeed.com" => "Argentina",

            "https://au.indeed.com" => "Australia",

            "https://at.indeed.com" => "Austria",

            "https://bh.indeed.com" => "Bahrain",

            "https://be.indeed.com" => "Belgium",

            "https://br.indeed.com" => "Brazil",

            "https://ca.indeed.com" => "Canada",

            "https://cl.indeed.com" => "Chile",

            "https://cn.indeed.com" => "China",

            "https://co.indeed.com" => "Colombia",

            "https://cr.indeed.com" => "Costa Rica",

            "https://cz.indeed.com" => "Czech Republic",

            "https://dk.indeed.com" => "Denmark",

            "https://ec.indeed.com" => "Ecuador",

            "https://eg.indeed.com" => "Egypt",

            "https://fi.indeed.com" => "Finland",

            "https://fr.indeed.com" => "France",

            "https://de.indeed.com" => "Germany",

            "https://gr.indeed.com" => "Greece",

            "https://hk.indeed.com" => "Hong Kong",

            "https://hu.indeed.com" => "Hungary",

            "https://in.indeed.com" => "India",

            "https://id.indeed.com" => "Indonesia",

            "https://ie.indeed.com" => "Ireland",

            "https://il.indeed.com" => "Israel",

            "https://it.indeed.com" => "Italy",

            "https://jp.indeed.com" => "Japan",

            "https://kw.indeed.com" => "Kuwait",

            "https://lu.indeed.com" => "Luxembourg",

            "https://malaysia.indeed.com" => "Malaysia",

            "https://mx.indeed.com" => "Mexico",

            "https://ma.indeed.com" => "Morocco",

            "https://nl.indeed.com" => "Netherlands",

            "https://nz.indeed.com" => "New Zealand",

            "https://ng.indeed.com" => "Nigeria",

            "https://no.indeed.com" => "Norway",

            "https://om.indeed.com" => "Oman",

            "https://pk.indeed.com" => "Pakistan",

            "https://pa.indeed.com" => "Panama",

            "https://pe.indeed.com" => "Peru",

            "https://ph.indeed.com" => "Philippines",

            "https://pl.indeed.com" => "Poland",

            "https://pt.indeed.com" => "Portugal",

            "https://qa.indeed.com" => "Qatar",

            "https://ro.indeed.com" => "Romania",

            "https://ru.indeed.com" => "Russia",

            "https://sa.indeed.com" => "Saudi Arabia",

            "https://sg.indeed.com" => "Singapore",

            "https://za.indeed.com" => "South Africa",

            "https://kr.indeed.com" => "South Korea",

            "https://es.indeed.com" => "Spain",

            "https://se.indeed.com" => "Sweden",

            "https://ch.indeed.com" => "Switzerland",

            "https://tw.indeed.com" => "Taiwan",

            "https://th.indeed.com" => "Thailand",

            "https://tr.indeed.com" => "Turkey",

            "https://ua.indeed.com" => "Ukraine",

            "https://ae.indeed.com" => "United Arab Emirates",

            "https://uk.indeed.com" => "United Kingdom",

            "https://indeed.com" => "United States",

            "https://uy.indeed.com" => "Uruguay",

            "https://ve.indeed.com" => "Venezuela",

            "https://vn.indeed.com" => "Vietnam",
        );
        
        return $countries;
    }

    public function import_jobs_form() {
        global $jobsearch_form_fields;
        ?>
        <hr>
        <div id="wrapper" class="jobsearch-post-settings jobsearch-indeed-import-sec">
            <h2><?php esc_html_e('Import Indeed Jobs', 'wp-jobsearch'); ?></h2>
            <form autocomplete="off" id="jobsearch-import-indeed-jobs" class="jobsearch-indeed-jobs" method="post" enctype="multipart/form-data">
                <?php
                wp_nonce_field('jobsearch-import-indeed-jobs-page', '_wpnonce-jobsearch-import-indeed-jobs-page');
                ?>
                <div class="jobsearch-element-field">
                    <div class="elem-label">
                        <label><?php esc_html_e('Keywords', 'wp-jobsearch') ?></label>
                    </div>
                    <div class="elem-field">
                        <?php
                        $field_params = array(
                            'force_std' => '',
                            'id' => 'search_keywords',
                            'cus_name' => 'keyword',
                            'field_desc' => esc_html__('Enter job title, keywords or company:(company name)', 'wp-jobsearch'),
                        );
                        $jobsearch_form_fields->input_field($field_params);
                        ?>
                    </div>
                </div>
                <div class="jobsearch-element-field">
                    <div class="elem-label">
                        <label><?php esc_html_e('Select Country', 'wp-jobsearch') ?></label>
                    </div>
                    <div class="elem-field">
                        <?php
                        $contries_list = $this->indeed_countries();
                        $field_params = array(
                            'force_std' => 'https://indeed.com',
                            'id' => 'country_domain',
                            'cus_name' => 'country_domain',
                            'options' => $contries_list,
                            'field_desc' => esc_html__('Select a country.', 'wp-jobsearch'),
                        );
                        $jobsearch_form_fields->select_field($field_params);
                        ?>
                    </div>
                </div>
                <div class="jobsearch-element-field">
                    <div class="elem-label">
                        <label><?php esc_html_e('Location', 'wp-jobsearch') ?></label>
                    </div>
                    <div class="elem-field">
                        <?php
                        $field_params = array(
                            'force_std' => '',
                            'id' => 'search_location',
                            'cus_name' => 'location',
                            'field_desc' => esc_html__('Enter a location for search.', 'wp-jobsearch'),
                        );
                        $jobsearch_form_fields->input_field($field_params);
                        ?>
                    </div>
                </div>
                <div class="jobsearch-element-field">
                    <div class="elem-label">
                        <label><?php esc_html_e('No. of Jobs to Import', 'wp-jobsearch') ?></label>
                    </div>
                    <div class="elem-field">
                        <?php
                        $field_params = array(
                            'force_std' => '10',
                            'id' => 'limit',
                            'cus_name' => 'num_jobs',
                            'field_desc' => esc_html__('Enter number of jobs to import. Default number of import jobs is 10.', 'wp-jobsearch'),
                        );
                        $jobsearch_form_fields->input_field($field_params);
                        ?>
                    </div>
                </div>
                <div class="jobsearch-element-field">
                    <div class="elem-label">
                        <label><?php esc_html_e('Expired on', 'wp-jobsearch') ?></label>
                    </div>
                    <div class="elem-field">
                        <?php
                        $field_params = array(
                            'force_std' => '0',
                            'id' => 'expire_days',
                            'cus_name' => 'expire_days',
                            'field_desc' => esc_html__('Enter number of days (numeric format) for expiray date after job posted date.', 'wp-jobsearch'),
                        );
                        $jobsearch_form_fields->input_field($field_params);
                        ?>
                    </div>
                </div>
                <div class="jobsearch-element-field">
                    <div class="elem-label">
                        <label><?php esc_html_e('Posted By', 'wp-jobsearch') ?></label>
                    </div>
                    <div class="elem-field">
                        <?php
                        jobsearch_get_custom_post_field('', 'employer', esc_html__('Auto Generate', 'wp-jobsearch'), 'job_username', 'job_username');
                        ?>
                    </div>
                </div>
                <div class="jobsearch-element-field">
                    <div class="elem-label">&nbsp;</div>
                    <div class="elem-field">
                        <a href="javascript:void(0);" class="impindeed-submit-btn button" data-gtopage="1"><?php esc_html_e('Import Jobs', 'wp-jobsearch') ?></a>
                    </div>
                    <div class="form-response-con">
                        <div class="response-loder"></div>
                        <div id="jobsync-proces-barcon" style="display: inline-block; width: 100%;"></div>
                        <div id="jobsync-proces-pgenums" style="display: inline-block; width: 100%;"></div>
                        <div class="response-msgcon"></div>
                    </div>
                </div>
            </form>
            <script>
                jQuery('form#jobsearch-import-indeed-jobs .impindeed-submit-btn').on('click', function (e) {
                    e.preventDefault();

                    var _this = jQuery(this),
                        this_form = _this.parents('form'),
                        page_num = _this.attr('data-gtopage'),
                        response_loder = this_form.find('.response-loder'),
                        response_msgcon = this_form.find('.response-msgcon'),
                        ajax_url = ajaxurl;

                    var get_form_dom = this_form[0];
                    var formData = new FormData(get_form_dom);

                    page_num = parseInt(page_num);
                    formData.append('page_num', page_num);

                    formData.append('action', 'jobsearch_import_scraping_indeed_jobs');

                    jQuery('#jobsync-proces-barcon').html('<div class="proces-bargray-con" style="display: inline-block; width: 100%; background-color: #cecece; height: 20px;">\
                        <div class="proces-bargreen-con" style="display: inline-block; width: 1%; background-color: #4caf50; height: 20px;"></div>\
                    </div>');
                    response_msgcon.html('');
                    response_loder.html('<?php esc_html_e('Please wait', 'wp-jobsearch') ?> <i class="fa fa-refresh fa-spin"></i>');
                    var request = jQuery.ajax({
                        url: ajax_url,
                        method: "POST",
                        processData: false,
                        contentType: false,
                        data: formData,
                        dataType: "json"
                    });

                    request.done(function (response) {
                        if (typeof response.msg !== undefined && response.msg != '' && response.msg != null) {
                            response_msgcon.html(response.msg);
                            response_loder.html('');
                        } else if (typeof response.html !== undefined && response.html != '' && response.html != null) {
                            response_msgcon.append(response.html);
                        }
                        if (typeof response.reload !== undefined && response.reload != null && response.reload == '1') {
                            window.location.reload();
                        }
                    });

                    request.fail(function (jqXHR, textStatus) {
                        response_loder.html('');
                    });

                    return false;

                });
            </script>
        </div>
        <?php
    }
    
    public function jobsearch_import_indeed_jobs() {
        global $jobsearch_plugin_options;
        $keyword = jobsearch_esc_html($_POST['keyword']);
        $location = jobsearch_esc_html($_POST['location']);
        $num_jobs = absint($_POST['num_jobs']);
        $expire_days = $_POST['expire_days'];
        $country_domain = $_POST['country_domain'];
        $platform = 'indeed';
        if ($keyword != '') {
            $job_username = sanitize_text_field($_POST['job_username']);
            
            $page_num = isset($_POST['page_num']) && $_POST['page_num'] > 1 ? $_POST['page_num'] : 1;
            $job_count = isset($_POST['job_count']) && $_POST['job_count'] > 1 ? $_POST['job_count'] : 1;
            $job_actcount = isset($_POST['job_actcount']) && $_POST['job_actcount'] > 0 ? $_POST['job_actcount'] : 0;
            
            $page1_count = isset($_POST['page1_count']) && $_POST['page1_count'] > 1 ? $_POST['page1_count'] : 1;

            $det_base_url = $country_domain . '/';
            $base_url = $country_domain . '/jobs/';

            $query_arr = array();
            $query_arr[] = 'q=' . urlencode($keyword);

            if ($location != '') {
                $query_arr[] = 'l=' . urlencode($location);
            }
            if ($page_num > 1) {
                $query_arr[] = 'start=' . ($page_num - 1) * 10;
            }

            if (!empty($query_arr)) {
                $base_url = $base_url . '?' . implode('&', $query_arr);
            }

            //
            $base_url_transient = get_transient('jobsearch_indeed_import_base_url');
            $job_elems_transient = get_transient('jobsearch_indeed_import_job_elems');

            $save_transient_list = false;
            if ($base_url_transient == $base_url && $job_elems_transient != '') {
                $save_transient_list = true;
                $jobs_elements = $job_elems_transient;
            } else {
                $html = wp_remote_get($base_url,
                array(
                    'timeout' => 120,
                    'httpversion' => '1.1',
                ));
                $html = $html['body'];

                //$html = file_get_contents($base_url);
                if ($html == false || $html == '' || $html == null) {
                    if (function_exists('curl_init')) {
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_URL, $base_url);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($curl, CURLOPT_HEADER, false);
                        $html = curl_exec($curl);
                        curl_close($curl);
                    }
                }
                
                $dom = new DOMDocument();

                //var_dump($base_url);var_dump($html); die;
                $dom->loadHTML($html);
                //un-comment this below line for check captcha
                //wp_send_json(array('error' => '0', 'html' => $html));

                $xpath = new DOMXPath($dom);

                $jobs_elements = $xpath->query("//a[contains(@class, 'jcs-JobTitle')]");
                //var_dump($jobs_elements);
                
                $extract_js_jks = [];
                $page_data = esc_html($html);
                //var_dump($page_data);
                if ($page_data != '' && strpos($page_data, 'jobmap[0]') !== false) {
                    for ($jmp_c = 0; $jmp_c < 15; $jmp_c++) {
                        if (strpos($page_data, 'jobmap[' . $jmp_c . ']') !== false) {
                            $jmp_c_str = substr($page_data, strpos($page_data, 'jobmap[' . $jmp_c . ']'), 550);
                            if (strpos($jmp_c_str, 'jk:&#039;') !== false) {
                                
                                // address
                                $the_strt_pos = strpos($jmp_c_str, 'loc:&#039;');
                                $the_end_pos = strpos($jmp_c_str, '&#039;,country');
                                $jstr_loc_address = substr($jmp_c_str, $the_strt_pos, ($the_end_pos - $the_strt_pos));
                                $jstr_loc_address = str_replace(array('loc:&#039;'), array(''), $jstr_loc_address);
                                if ($jstr_loc_address == '') {
                                    $jstr_loc_address = '-';
                                }
                                
                                // zip
                                $the_strt_pos = strpos($jmp_c_str, 'zip:&#039;');
                                $the_end_pos = strpos($jmp_c_str, '&#039;,city');
                                $jstr_loc_zip = substr($jmp_c_str, $the_strt_pos, ($the_end_pos - $the_strt_pos));
                                $jstr_loc_zip = str_replace(array('zip:&#039;'), array(''), $jstr_loc_zip);
                                if ($jstr_loc_zip == '') {
                                    $jstr_loc_zip = '-';
                                }
                                
                                // country
                                $the_strt_pos = strpos($jmp_c_str, 'country:&#039;');
                                $the_end_pos = strpos($jmp_c_str, '&#039;,zip');
                                $jstr_loc_contry = substr($jmp_c_str, $the_strt_pos, ($the_end_pos - $the_strt_pos));
                                $jstr_loc_contry = str_replace(array('country:&#039;'), array(''), $jstr_loc_contry);
                                if ($jstr_loc_contry == '') {
                                    $jstr_loc_contry = '-';
                                }
                                
                                // city
                                $the_strt_pos = strpos($jmp_c_str, 'city:&#039;');
                                $the_end_pos = strpos($jmp_c_str, '&#039;,title');
                                $jstr_loc_city = substr($jmp_c_str, $the_strt_pos, ($the_end_pos - $the_strt_pos));
                                $jstr_loc_city = str_replace(array('city:&#039;'), array(''), $jstr_loc_city);
                                if ($jstr_loc_city == '') {
                                    $jstr_loc_city = '-';
                                }
                                
                                //var_dump('addr: ' . $jstr_loc_address . ' | ' . 'zip: ' . $jstr_loc_zip . ' | ' . 'city: ' . $jstr_loc_city . ' | ' . 'country: ' . $jstr_loc_contry . ' | ');
                                
                                $jk_str = substr($jmp_c_str, strpos($jmp_c_str, 'jk:&#039;'), strpos($jmp_c_str, '&#039;,'));

                                $jk_pure_val = str_replace(array('jk:', '&#039;', 'efccid', 'efcci', ','), array('', '', '', '', ''), $jk_str);
                                
                                $jk_dumy_url = 'http://my-url.com?jk=' . $jk_pure_val . '&from=serp&vjs=3&loc=[' . $jstr_loc_address . '|' . $jstr_loc_contry . '|' . $jstr_loc_city . '|' . $jstr_loc_zip . ']';
                                
                                $extract_js_jks[] = $jk_dumy_url;
                            }
                        }
                    }
                }
                //var_dump($extract_js_jks);
                //die;
                
                // just for check
//                foreach ($jobs_elements as $job_element) {
//                    $u = explode('?', $job_element->getAttribute('href'));
//                    var_dump($job_element->getAttribute('href'));
//                    $ur = explode('&', $u[1]);
//                    $url = explode('=', $ur[0]);
//                    $jk = $url[1];
//                    $job_url = $det_base_url . 'viewjob?jk=' . $jk . '&from=serp&vjs=3';
//                    var_dump($job_url);
//                    
//                    $job_detail = @file_get_html($job_url);
//
//                    $job_title = '';
//                    if ($job_detail) {
//                        foreach ($job_detail->find('h1.jobsearch-JobInfoHeader-title') as $job_title_html) {
//                            $job_title = wp_kses($job_title_html, array());
//                        }
//                    }
//
//                    var_dump($job_title);
//                }
//                die;
                
                $indeed_pagenums_con = $xpath->query("//div[contains(@id, 'searchCountPages')]");

                if (isset($indeed_pagenums_con->length) && $indeed_pagenums_con->length > 0) {
                    
                    if (!isset($_REQUEST['indeed_num_jobs']) && $job_actcount == 0) {
                        foreach ($indeed_pagenums_con as $indeed_pagenum_obj) {
                            $indeed_pagenums_text = $indeed_pagenum_obj->textContent;
                            $indeed_pagenums_text = str_replace(array(','), array(''), $indeed_pagenums_text);
                            preg_match_all('!\d+!', $indeed_pagenums_text, $page_num_matches);
                            $indeed_page_nums = isset($page_num_matches[0][1]) ? absint($page_num_matches[0][1]) : 0;

                            if ($indeed_page_nums > 0 && $num_jobs > $indeed_page_nums) {
                                $num_jobs = $indeed_page_nums;

                                ob_start();
                                ?>
                                <script>
                                    var this_form = jQuery('form#jobsearch-import-indeed-jobs');
                                    var num_job_input = this_form.find('input[name=num_jobs]');
                                    num_job_input.val('<?php echo ($indeed_page_nums) ?>');

                                    var response_loder = this_form.find('.response-loder'),
                                    response_msgcon = this_form.find('.response-msgcon');

                                    var pging_html = 'Job <?php echo ($job_actcount) ?> of <?php echo ($num_jobs) ?> jobs found';
                                    jQuery('#jobsync-proces-pgenums').html(pging_html);

                                    jQuery('#jobsync-proces-barcon').find('.proces-bargreen-con').css({width:'1%'});

                                    var after_pnum_request = jQuery.ajax({
                                        url: '<?php echo admin_url('admin-ajax.php') ?>',
                                        method: "POST",
                                        data: {
                                            keyword: '<?php echo ($keyword) ?>',
                                            location: '<?php echo ($location) ?>',
                                            country_domain: '<?php echo ($country_domain) ?>',
                                            num_jobs: '<?php echo ($num_jobs) ?>',
                                            platform: '<?php echo ($platform) ?>',
                                            page_num: '<?php echo ($page_num) ?>',
                                            found_jobs: '',
                                            indeed_num_jobs: '<?php echo ($indeed_page_nums) ?>',
                                            expire_days: '<?php echo ($expire_days) ?>',
                                            job_count: '<?php echo ($job_count) ?>',
                                            job_actcount: '<?php echo ($job_actcount) ?>',
                                            action: 'jobsearch_import_scraping_indeed_jobs'
                                        },
                                        dataType: "json"
                                    });

                                    after_pnum_request.done(function (response) {
                                        if (typeof response.msg !== undefined && response.msg != '' && response.msg != null) {
                                            response_msgcon.html(response.msg);
                                            response_loder.html('');
                                        } else if (typeof response.html !== undefined && response.html != '' && response.html != null) {
                                            response_msgcon.append(response.html);
                                        }
                                        if (typeof response.reload !== undefined && response.reload != null && response.reload == '1') {
                                            window.location.reload();
                                        }
                                    });

                                    after_pnum_request.fail(function (jqXHR, textStatus) {
                                        response_loder.html('');
                                    });
                                </script>
                                <?php
                                $js_html = ob_get_clean();
                                wp_send_json(array('error' => '0', 'html' => $js_html));
                            }
                        }
                    }
                } else {
                    $msg = esc_html__('Please try later. Maybe indeed doesn\'t allow you to import data.' , 'wp-jobsearch');
                    wp_send_json(array('error' => '1', 'msg' => $msg));
                }
            }
            if ($save_transient_list) {
                $jobs_elements_length = is_array($jobs_elements) && !empty($jobs_elements) ? count($jobs_elements) : 0;
            } else {
                if (!empty($extract_js_jks)) {
                    $jobs_elements = $extract_js_jks;
                    $jobs_elements_length = count($extract_js_jks);
                } else {
                    $jobs_elements_length = isset($jobs_elements->length) ? $jobs_elements->length : 0;
                }
            }

            if ($jobs_elements_length > 0) {
                if ($base_url_transient != $base_url) {
                    set_transient('jobsearch_indeed_import_base_url', $base_url, 900);
                    $tosve_elements_arr = array();
                    foreach ($jobs_elements as $job_element) {
                        if (!empty($extract_js_jks)) {
                            $job_elem_href = explode('?', $job_element);
                        } else {
                            $job_elem_href = explode('?', $job_element->getAttribute('href'));
                        }
                        $tosve_elements_arr[] = $job_elem_href;
                    }
                    set_transient('jobsearch_indeed_import_job_elems', $tosve_elements_arr, 900);
                }
                
                // just for check
//                if (!empty($extract_js_jks)) {
//                    foreach ($jobs_elements as $job_element) {
//                        if ($save_transient_list) {
//                            $u = $job_element;
//                        } else {
//                            $u = explode('?', $job_element);
//                        }
//                        $ur = explode('&', $u[1]);
//                        $url = explode('=', $ur[0]);
//                        $jk = $url[1];
//                        $job_url = $det_base_url . 'viewjob?jk=' . $jk . '&from=serp&vjs=3';
//                        var_dump($job_url);
//
//                        $job_detail = @file_get_html($job_url);
//
//                        $job_title = '';
//                        if ($job_detail) {
//                            foreach ($job_detail->find('h1.jobsearch-JobInfoHeader-title') as $job_title_html) {
//                                $job_title = wp_kses($job_title_html, array());
//                            }
//                        }
//
//                        var_dump($job_title);
//                    }
//                }
//                var_dump($jobs_elements);
//                die;
                //

                $found_jobs = $jobs_elements_length;
                $found_elems_counter = 1;
                $js_html = '';
                foreach ($jobs_elements as $job_element) {
                    if ($found_elems_counter == $job_count) {
                        if ($save_transient_list) {
                            $u = $job_element;
                        } else {
                            if (!empty($extract_js_jks)) {
                                $u = explode('?', $job_element);
                            } else {
                                $u = explode('?', $job_element->getAttribute('href'));
                            }
                        }
                        $ur = explode('&', $u[1]);
                        $url = explode('=', $ur[0]);
                        $jk = $url[1];
                        
                        // for location
                        $loc_get_str = isset($ur[3]) ? $ur[3] : '';
                        if ($loc_get_str != '' && preg_match('/loc=/i', $loc_get_str)) {
                            $loc_whole_str = $ur[3];
                            $loc_whole_str = str_replace(array('loc=[', ']'), array('', ''), $loc_whole_str);
                            $loc_whole_strarr = explode('|', $loc_whole_str);
                        }
                        //

                        $job_url = $det_base_url . 'viewjob?jk=' . $jk . '&from=serp&vjs=3';

                        $existing_id = jobsearch_get_postmeta_id_byval('jobsearch_field_job_detail_url', $job_url);
                        $skiping_job = false;
                        if ($existing_id > 0) {
                            //
                            $skiping_job = true;
                        } else {
                            $job_detail = @file_get_html($job_url);

                            $job_title = '';
                            if ($job_detail) {
                                foreach ($job_detail->find('h1.jobsearch-JobInfoHeader-title') as $job_title_html) {
                                    $job_title = wp_kses($job_title_html, array());
                                }
                            }
                            
                            if ($job_title != '') {

                                $job_location_addres = $job_location_loc1 = $job_location_loc2 = $job_location_loc3 = $job_location_zip = '';
                                
                                $company_image = '';
                                $job_company = '';
                                $job_salary = '';
                                $job_desc = '';
                                foreach ($job_detail->find('div.jobsearch-CompanyInfoWithoutHeaderImage') as $company_image_html) {
                                    $company_image = $company_image_html;
                                }
                                foreach ($job_detail->find('div.jobsearch-InlineCompanyRating div.icl-u-xs-mr--xs') as $job_company_html) {
                                    $job_company = wp_kses($job_company_html, array());
                                }
                                foreach ($job_detail->find('div.jobsearch-JobMetadataHeader-item') as $job_salary_html) {
                                    $job_salary = wp_kses($job_salary_html, array());
                                }
                                foreach ($job_detail->find('div#jobDescriptionText') as $job_desc_html) {
                                    $job_desc = esc_html($job_desc_html);
                                }
                                
                                // location container
                                if (isset($loc_whole_strarr[0]) && $loc_whole_strarr[0] != '' && $loc_whole_strarr[0] != '-' && $loc_whole_strarr[0] != 'Remote') {
                                    $job_location_addres = $loc_whole_strarr[0];
                                }
                                if (isset($loc_whole_strarr[1]) && $loc_whole_strarr[1] != '' && $loc_whole_strarr[1] != '-' && $loc_whole_strarr[1] != 'Remote') {
                                    $job_location_loc1 = $loc_whole_strarr[1];
                                }
                                if (isset($loc_whole_strarr[2]) && $loc_whole_strarr[2] != '' && $loc_whole_strarr[2] != '-' && $loc_whole_strarr[2] != 'Remote') {
                                    $job_location_loc3 = $loc_whole_strarr[2];
                                }
                                if (isset($loc_whole_strarr[3]) && $loc_whole_strarr[3] != '' && $loc_whole_strarr[3] != '-' && $loc_whole_strarr[3] != 'Remote') {
                                    $job_location_zip = $loc_whole_strarr[3];
                                }

                                $job_desc = str_replace(array('&lt;', '&gt;'), array('<', '>'), $job_desc);
                                //var_dump($job_desc);
                                
                                $indeed_job_type = $job_detail->find('span.jobsearch-JobMetadataHeader-item');
                                $indeed_job_type = isset($indeed_job_type[0]) ? $indeed_job_type[0] : '';

                                if ($indeed_job_type != '') {
                                    $indeed_job_type = wp_kses($indeed_job_type, array());
                                    $indeed_job_type = str_replace(array('<', '>', '-', '!'), array('', '', '', ''), $indeed_job_type);
                                }

                                $job_salary_min = '';
                                $job_salary_max = '';
                                
                                if ($job_salary != '') {
                                    $job_salary = str_replace(array(','), array(''), $job_salary);
                                    $job_salary_parts = explode('-', $job_salary);
                                    if (isset($job_salary_parts[0]) && isset($job_salary_parts[1])) {
                                        preg_match('!\d+!', $job_salary_parts[0], $job_salary_min);
                                        if (isset($job_salary_min[0])) {
                                            $job_salary_min = $job_salary_min[0];
                                        } else {
                                            $job_salary_min = '';
                                        }
                                        preg_match('!\d+!', $job_salary_parts[1], $job_salary_max);
                                        if (isset($job_salary_max[0])) {
                                            $job_salary_max = $job_salary_max[0];
                                        } else {
                                            $job_salary_max = '';
                                        }
                                    } else {
                                        preg_match('!\d+!', $job_salary, $job_salary_min);
                                        if (isset($job_salary_min[0])) {
                                            $job_salary_min = $job_salary_min[0];
                                        } else {
                                            $job_salary_min = '';
                                        }
                                    }
                                }
                                $post_data = array(
                                    'post_type' => 'job',
                                    'post_title' => $job_title,
                                    //'post_content' => '',
                                    'post_content' => $job_desc,
                                    'post_status' => 'publish',
                                );
                                // Insert the job into the database
                                $post_id = wp_insert_post($post_data);

                                //
                                update_post_meta($post_id, 'jobsearch_job_employer_status', 'approved');
                                update_post_meta($post_id, 'jobsearch_field_job_featured', '');

                                // Insert job username meta key
                                if ($job_username > 0) {
                                    update_post_meta($post_id, 'jobsearch_field_job_posted_by', $job_username, true);
                                } else {
                                    if ($job_company != '') {
                                        jobsearch_fake_generate_employer_byname($job_company, $post_id);
                                    }
                                }

                                // Insert job posted on meta key
                                update_post_meta($post_id, 'jobsearch_field_job_publish_date', current_time('timestamp'), true);

                                // Insert job expired on meta key
                                $expired_date = date('d-m-Y H:i:s', strtotime("$expire_days days", current_time('timestamp')));
                                update_post_meta($post_id, 'jobsearch_field_job_expiry_date', strtotime($expired_date), true);

                                // Insert job status meta key
                                update_post_meta($post_id, 'jobsearch_field_job_status', 'approved', true);

                                // Insert job address meta key
                                if ($job_location_addres != '') {
                                    update_post_meta($post_id, 'jobsearch_field_location_address', $job_location_addres, true);
                                }
                                if ($job_location_loc1 != '') {
                                    update_post_meta($post_id, 'jobsearch_field_location_location1', $job_location_loc1, true);
                                }
                                if ($job_location_loc2 != '') {
                                    update_post_meta($post_id, 'jobsearch_field_location_location2', $job_location_loc2, true);
                                }
                                if ($job_location_loc3 != '') {
                                    update_post_meta($post_id, 'jobsearch_field_location_location3', $job_location_loc3, true);
                                }
                                if ($job_location_zip != '') {
                                    update_post_meta($post_id, 'jobsearch_field_location_postalcode', $job_location_zip, true);
                                }

                                update_post_meta($post_id, 'jobsearch_field_job_salary', $job_salary_min, true);
                                update_post_meta($post_id, 'jobsearch_field_job_max_salary', $job_salary_max, true);

                                // Insert job referral meta key
                                update_post_meta($post_id, 'jobsearch_job_referral', 'indeed', true);

                                // Insert job detail url meta key
                                update_post_meta($post_id, 'jobsearch_field_job_detail_url', ($job_url), true);
                                update_post_meta($post_id, 'jobsearch_field_job_jk', ($jk), true);

                                update_post_meta($post_id, 'jobsearch_field_job_apply_type', 'external', true);
                                update_post_meta($post_id, 'jobsearch_field_job_apply_url', $job_url, true);
                                
                                if ($indeed_job_type != '') {
                                    if (strpos($indeed_job_type, ',')) {
                                        $indeed_job_types = explode(',', $indeed_job_type);
                                        
                                        $type_term_ids = array();
                                        foreach ($indeed_job_types as $the_job_type) {
                                            $type_term = get_term_by('name', $the_job_type, 'jobtype');
                                            if (empty($type_term)) {
                                                wp_insert_term($the_job_type, 'jobtype');
                                                $type_term = get_term_by('name', $the_job_type, 'jobtype');
                                            }
                                            $type_term_ids[] = $type_term->term_id;
                                        }
                                        wp_set_post_terms($post_id, $type_term_ids, 'jobtype');
                                    } else {
                                        $type_term = get_term_by('name', $indeed_job_type, 'jobtype');
                                        if (empty($type_term)) {
                                            wp_insert_term($indeed_job_type, 'jobtype');
                                            $type_term = get_term_by('name', $indeed_job_type, 'jobtype');
                                        }
                                        wp_set_post_terms($post_id, $type_term->term_id, 'jobtype');
                                    }
                                }

                                $job_actcount++;
                            }
                        }

                        if ($job_actcount >= $num_jobs) {
                            break;
                            $msg = sprintf(esc_html__('%s Jobs Imported Successfully.', 'wp-jobsearch'), $job_actcount);
                            wp_send_json(array('error' => '0', 'msg' => $msg, 'reload' => '1'));
                        }

                        if ($found_jobs > $job_count && $skiping_job === false) {
                            $job_count++;
                            ob_start();
                            
                            ?>
                            <script>
                                var this_form = jQuery('form#jobsearch-import-indeed-jobs'),
                                page_num = this_form.find('.import-submit-btn').attr('data-gtopage'),
                                response_loder = this_form.find('.response-loder'),
                                response_msgcon = this_form.find('.response-msgcon');
                        
                                var pging_html = 'Job <?php echo ($job_actcount) ?> of <?php echo ($num_jobs) ?> jobs found';
                                jQuery('#jobsync-proces-pgenums').html(pging_html);

                                <?php
                                if ($job_actcount > 0) {
                                    ?>
                                    var perc = (parseInt(<?php echo ($job_actcount) ?>) * 100) / parseInt(<?php echo ($num_jobs) ?>);
                                    if (perc > 100) {
                                        perc = 100;
                                    }
                                    <?php
                                } else {
                                    ?>
                                    var perc = 1;
                                    <?php
                                }
                                ?>
                                jQuery('#jobsync-proces-barcon').find('.proces-bargreen-con').css({width: perc + '%'});
                        
                                var request = jQuery.ajax({
                                    url: '<?php echo admin_url('admin-ajax.php') ?>',
                                    method: "POST",
                                    data: {
                                        keyword: '<?php echo ($keyword) ?>',
                                        location: '<?php echo ($location) ?>',
                                        country_domain: '<?php echo ($country_domain) ?>',
                                        num_jobs: '<?php echo ($num_jobs) ?>',
                                        platform: '<?php echo ($platform) ?>',
                                        indeed_num_jobs: '<?php echo (isset($indeed_page_nums) ? $indeed_page_nums : '') ?>',
                                        page1_count: '<?php echo ($page1_count) ?>',
                                        expire_days: '<?php echo ($expire_days) ?>',
                                        page_num: '<?php echo ($page_num) ?>',
                                        found_jobs: '<?php echo ($found_jobs) ?>',
                                        job_count: '<?php echo ($job_count) ?>',
                                        job_actcount: '<?php echo ($job_actcount) ?>',
                                        action: 'jobsearch_import_scraping_indeed_jobs'
                                    },
                                    dataType: "json"
                                });

                                request.done(function (response) {
                                    if (typeof response.msg !== undefined && response.msg != '' && response.msg != null) {
                                        response_msgcon.html(response.msg);
                                        response_loder.html('');
                                    } else if (typeof response.html !== undefined && response.html != '' && response.html != null) {
                                        response_msgcon.append(response.html);
                                    }
                                    if (typeof response.reload !== undefined && response.reload != null && response.reload == '1') {
                                        window.location.reload();
                                    }
                                });

                                request.fail(function (jqXHR, textStatus) {
                                    response_loder.html('');
                                });
                            </script>
                            <?php
                            $js_html = ob_get_clean();
                        }
                        if ($found_jobs > 1 && $found_jobs <= $job_count) {
                            $page_num++;
                            
                            if ($page_num == 2) {
                                $page1_count++;
                            }

                            $base_url = $country_domain . '/jobs/';
                            
                            $query_arr = array();
                            $query_arr[] = 'q=' . urlencode($keyword);

                            if ($location != '') {
                                $query_arr[] = 'l=' . urlencode($location);
                            }
                            if ($page_num > 1) {
                                $query_arr[] = 'start=' . ($page_num - 1) * 10;
                            }

                            if (!empty($query_arr)) {
                                $base_url = $base_url . '?' . implode('&', $query_arr);
                            }

                            $html = wp_remote_get($base_url,
                            array(
                                'timeout' => 120,
                                'httpversion' => '1.1',
                            ));
                            $html = $html['body'];

                            //$html = file_get_contents($base_url);
                            
                            $dom = new DOMDocument();

                            $dom->loadHTML($html);

                            $xpath = new DOMXPath($dom);

                            $jobs_elements = $xpath->query("//a[contains(@class, 'jcs-JobTitle')]");
                            
                            if (isset($jobs_elements->length) && $jobs_elements->length > 0) {
                                $found_jobs = $jobs_elements->length;
                                ob_start();
                            
                                ?>
                                <script>
                                    var this_form = jQuery('form#jobsearch-import-indeed-jobs'),
                                    page_num = this_form.find('.import-submit-btn').attr('data-gtopage'),
                                    response_loder = this_form.find('.response-loder'),
                                    response_msgcon = this_form.find('.response-msgcon');
                                    var request = jQuery.ajax({
                                        url: '<?php echo admin_url('admin-ajax.php') ?>',
                                        method: "POST",
                                        data: {
                                            keyword: '<?php echo ($keyword) ?>',
                                            location: '<?php echo ($location) ?>',
                                            country_domain: '<?php echo ($country_domain) ?>',
                                            num_jobs: '<?php echo ($num_jobs) ?>',
                                            platform: '<?php echo ($platform) ?>',
                                            expire_days: '<?php echo ($expire_days) ?>',
                                            page_num: '<?php echo ($page_num) ?>',
                                            indeed_num_jobs: '<?php echo (isset($indeed_page_nums) ? $indeed_page_nums : '') ?>',
                                            page1_count: '<?php echo ($page1_count) ?>',
                                            found_jobs: '<?php echo ($found_jobs) ?>',
                                            job_count: 1,
                                            job_actcount: '<?php echo ($job_actcount) ?>',
                                            action: 'jobsearch_import_scraping_indeed_jobs'
                                        },
                                        dataType: "json"
                                    });

                                    request.done(function (response) {
                                        if (typeof response.msg !== undefined && response.msg != '' && response.msg != null) {
                                            response_msgcon.html(response.msg);
                                            response_loder.html('');
                                        } else if (typeof response.html !== undefined && response.html != '' && response.html != null) {
                                            response_msgcon.append(response.html);
                                        }
                                        if (typeof response.reload !== undefined && response.reload != null && response.reload == '1') {
                                            window.location.reload();
                                        }
                                    });

                                    request.fail(function (jqXHR, textStatus) {
                                        response_loder.html('');
                                    });
                                </script>
                                <?php
                                $js_html = ob_get_clean();
                                break;
                            }
                        }
                        if ($skiping_job === false) {
                            break;
                        } else {
                            $job_count++;
                        }
                    }
                    $found_elems_counter++;
                }
                if ($js_html != '') {
                    wp_send_json(array('error' => '0', 'html' => $js_html));
                }
                if ($page1_count > 5) {
                    if ($job_actcount > 0) {
                        $msg = sprintf(esc_html__('%s Jobs found and Imported Successfully.', 'wp-jobsearch'), $job_actcount);
                        wp_send_json(array('error' => '0', 'msg' => $msg));
                    } else {
                        $msg = esc_html__('No Jobs Found.', 'wp-jobsearch');
                        wp_send_json(array('error' => '0', 'msg' => $msg));
                    }
                }
                if ($job_actcount < $num_jobs) {
                    ob_start();
                            
                    ?>
                    <script>
                        setTimeout(function(){
                            var this_form = jQuery('form#jobsearch-import-indeed-jobs'),
                            response_loder = this_form.find('.response-loder'),
                            response_msgcon = this_form.find('.response-msgcon');

                            var pging_html = 'Job <?php echo ($job_actcount) ?> of <?php echo ($num_jobs) ?> jobs found';
                            jQuery('#jobsync-proces-pgenums').html(pging_html);

                            <?php
                            if ($job_actcount > 0) {
                                ?>
                                var perc = (parseInt(<?php echo ($job_actcount) ?>) * 100) / parseInt(<?php echo ($num_jobs) ?>);
                                if (perc > 100) {
                                    perc = 100;
                                }
                                <?php
                            } else {
                                ?>
                                var perc = 1;
                                <?php
                            }
                            ?>
                            jQuery('#jobsync-proces-barcon').find('.proces-bargreen-con').css({width: perc + '%'});

                            var request = jQuery.ajax({
                                url: '<?php echo admin_url('admin-ajax.php') ?>',
                                method: "POST",
                                data: {
                                    keyword: '<?php echo ($keyword) ?>',
                                    location: '<?php echo ($location) ?>',
                                    country_domain: '<?php echo ($country_domain) ?>',
                                    num_jobs: '<?php echo ($num_jobs) ?>',
                                    indeed_num_jobs: '<?php echo (isset($indeed_page_nums) ? $indeed_page_nums : '') ?>',
                                    platform: '<?php echo ($platform) ?>',
                                    page1_count: '<?php echo ($page1_count) ?>',
                                    page_num: '1',
                                    expire_days: '<?php echo ($expire_days) ?>',
                                    found_jobs: '<?php echo ($found_jobs) ?>',
                                    job_count: '1',
                                    job_actcount: '<?php echo ($job_actcount) ?>',
                                    action: 'jobsearch_import_scraping_indeed_jobs'
                                },
                                dataType: "json"
                            });

                            request.done(function (response) {
                                if (typeof response.msg !== undefined && response.msg != '' && response.msg != null) {
                                    response_msgcon.html(response.msg);
                                    response_loder.html('');
                                } else if (typeof response.html !== undefined && response.html != '' && response.html != null) {
                                    response_msgcon.append(response.html);
                                }
                                if (typeof response.reload !== undefined && response.reload != null && response.reload == '1') {
                                    window.location.reload();
                                }
                            });

                            request.fail(function (jqXHR, textStatus) {
                                response_loder.html('');
                            });
                        }, 500);
                    </script>
                    <?php
                    $js_html = ob_get_clean();
                    wp_send_json(array('error' => '0', 'html' => $js_html));
                } else {
                    $msg = sprintf(esc_html__('%s Jobs found and Imported Successfully.', 'wp-jobsearch'), $job_actcount);
                    wp_send_json(array('error' => '0', 'msg' => $msg));
                }
            }
            
            if ($page1_count > 5) {
                if ($job_actcount > 0) {
                    $msg = sprintf(esc_html__('%s Jobs found and Imported Successfully.', 'wp-jobsearch'), $job_actcount);
                    wp_send_json(array('error' => '0', 'msg' => $msg));
                } else {
                    $msg = esc_html__('No Jobs Found.', 'wp-jobsearch');
                    wp_send_json(array('error' => '0', 'msg' => $msg));
                }
            }
            if ($job_actcount < $num_jobs) {
                ob_start();
                            
                ?>
                <script>
                    setTimeout(function(){
                        var this_form = jQuery('form#jobsearch-import-indeed-jobs'),
                        response_loder = this_form.find('.response-loder'),
                        response_msgcon = this_form.find('.response-msgcon');

                        var pging_html = 'Job <?php echo ($job_actcount) ?> of <?php echo ($num_jobs) ?> jobs found';
                        jQuery('#jobsync-proces-pgenums').html(pging_html);

                        <?php
                        if ($job_actcount > 0) {
                            ?>
                            var perc = (parseInt(<?php echo ($job_actcount) ?>) * 100) / parseInt(<?php echo ($num_jobs) ?>);
                            if (perc > 100) {
                                perc = 100;
                            }
                            <?php
                        } else {
                            ?>
                            var perc = 1;
                            <?php
                        }
                        ?>
                        jQuery('#jobsync-proces-barcon').find('.proces-bargreen-con').css({width: perc + '%'});

                        var request = jQuery.ajax({
                            url: '<?php echo admin_url('admin-ajax.php') ?>',
                            method: "POST",
                            data: {
                                keyword: '<?php echo ($keyword) ?>',
                                location: '<?php echo ($location) ?>',
                                country_domain: '<?php echo ($country_domain) ?>',
                                num_jobs: '<?php echo ($num_jobs) ?>',
                                indeed_num_jobs: '<?php echo (isset($indeed_page_nums) ? $indeed_page_nums : '') ?>',
                                platform: '<?php echo ($platform) ?>',
                                page1_count: '<?php echo ($page1_count) ?>',
                                page_num: '1',
                                expire_days: '<?php echo ($expire_days) ?>',
                                found_jobs: '<?php echo ($found_jobs) ?>',
                                job_count: '1',
                                job_actcount: '<?php echo ($job_actcount) ?>',
                                action: 'jobsearch_import_scraping_indeed_jobs'
                            },
                            dataType: "json"
                        });

                        request.done(function (response) {
                            if (typeof response.msg !== undefined && response.msg != '' && response.msg != null) {
                                response_msgcon.html(response.msg);
                                response_loder.html('');
                            } else if (typeof response.html !== undefined && response.html != '' && response.html != null) {
                                response_msgcon.append(response.html);
                            }
                            if (typeof response.reload !== undefined && response.reload != null && response.reload == '1') {
                                window.location.reload();
                            }
                        });

                        request.fail(function (jqXHR, textStatus) {
                            response_loder.html('');
                        });
                    }, 500);
                </script>
                <?php
                $js_html = ob_get_clean();
                wp_send_json(array('error' => '0', 'html' => $js_html));
            } else {
                $msg = sprintf(esc_html__('%s Jobs found and Imported Successfully.', 'wp-jobsearch'), $job_actcount);
                wp_send_json(array('error' => '0', 'msg' => $msg));
            }
        } else {
            $msg = esc_html__('Please enter the keyword.', 'wp-jobsearch');
            wp_send_json(array('error' => '1', 'msg' => $msg));
        }
    }

}

global $jobsearch_indeed_scraping_obj;

$jobsearch_indeed_scraping_obj = new JobSearch_Indeed_Jobs_Scraping_Hooks();
