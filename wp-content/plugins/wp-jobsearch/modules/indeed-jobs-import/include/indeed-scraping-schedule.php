<?php

if (!defined('ABSPATH')) {
    die;
}

// main plugin class
class JobSearch_Indeed_Jobs_Scraping_Schedulr {

    // hook things up
    public function __construct() {
        
    }

    public function import_jobs($args) {
        
        $keyword = jobsearch_esc_html($args['keyword']);
        $location = jobsearch_esc_html($args['location']);
        $num_jobs = absint($args['num_jobs']);
        $expire_days = $args['expire_days'];
        $job_username = sanitize_text_field($args['job_username']);
        $country_domain = $args['country_domain'];
        
        $platform = 'indeed';
        if ($keyword != '') {
            
            $page_num = isset($args['page_num']) && $args['page_num'] > 1 ? $args['page_num'] : 1;
            $job_count = isset($args['job_count']) && $args['job_count'] > 1 ? $args['job_count'] : 1;
            $job_actcount = isset($args['job_actcount']) && $args['job_actcount'] > 0 ? $args['job_actcount'] : 0;
            
            $page1_count = isset($args['page1_count']) && $args['page1_count'] > 1 ? $args['page1_count'] : 1;

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
                $dom = new DOMDocument();

                $dom->loadHTML($html);

                $xpath = new DOMXPath($dom);

                $jobs_elements = $xpath->query("//a[contains(@class, 'jcs-JobTitle')]");
                
                //
                $extract_js_jks = [];
                $page_data = esc_html($html);
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
                //

                $indeed_pagenums_con = $xpath->query("//div[contains(@id, 'searchCountPages')]");

                if (isset($indeed_pagenums_con->length) && $indeed_pagenums_con->length > 0) {

                    if (!isset($args['indeed_num_jobs']) && $job_actcount == 0) {
                        foreach ($indeed_pagenums_con as $indeed_pagenum_obj) {
                            $indeed_pagenums_text = $indeed_pagenum_obj->textContent;
                            $indeed_pagenums_text = str_replace(array(','), array(''), $indeed_pagenums_text);
                            preg_match_all('!\d+!', $indeed_pagenums_text, $page_num_matches);
                            $indeed_page_nums = isset($page_num_matches[0][1]) ? absint($page_num_matches[0][1]) : 0;

                            if ($indeed_page_nums > 0 && $num_jobs > $indeed_page_nums) {
                                $num_jobs = $indeed_page_nums;
                            }
                        }
                    }
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

            if ($jobs_elements_length > 0 && $job_actcount < $num_jobs) {
                
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

                                $job_location = '';
                                
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
                        }

                        if ($found_jobs > $job_count && $skiping_job === false && $job_actcount < $num_jobs) {
                            $job_count++;
                            
                            $import_args = array(
                                'keyword' => $keyword,
                                'location' => $location,
                                'country_domain' => $country_domain,
                                'num_jobs' => $num_jobs,
                                'job_username' => $job_username,
                                'platform' => $platform,
                                'indeed_num_jobs' => (isset($indeed_page_nums) ? $indeed_page_nums : ''),
                                'page1_count' => $page1_count,
                                'expire_days' => $expire_days,
                                'page_num' => $page_num,
                                'found_jobs' => $found_jobs,
                                'job_count' => $job_count,
                                'job_actcount' => $job_actcount,
                            );
                            $this->import_jobs($import_args);
                            
                        }
                        if ($found_jobs > 1 && $found_jobs <= $job_count && $job_actcount < $num_jobs) {
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
                                
                                $import_args = array(
                                    'keyword' => $keyword,
                                    'location' => $location,
                                    'country_domain' => $country_domain,
                                    'num_jobs' => $num_jobs,
                                    'job_username' => $job_username,
                                    'platform' => $platform,
                                    'expire_days' => $expire_days,
                                    'page_num' => $page_num,
                                    'indeed_num_jobs' => (isset($indeed_page_nums) ? $indeed_page_nums : ''),
                                    'page1_count' => $page1_count,
                                    'found_jobs' => $found_jobs,
                                    'job_count' => 1,
                                    'job_actcount' => $job_actcount,
                                );
                                $this->import_jobs($import_args);
                                
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
                
                if ($job_actcount < $num_jobs && $page1_count < 6) {
                    
                    $import_args = array(
                        'keyword' => $keyword,
                        'location' => $location,
                        'country_domain' => $country_domain,
                        'num_jobs' => $num_jobs,
                        'indeed_num_jobs' => (isset($indeed_page_nums) ? $indeed_page_nums : ''),
                        'job_username' => $job_username,
                        'platform' => $platform,
                        'page1_count' => $page1_count,
                        'page_num' => 1,
                        'expire_days' => $expire_days,
                        'found_jobs' => $found_jobs,
                        'job_count' => 1,
                        'job_actcount' => $job_actcount,
                    );
                    $this->import_jobs($import_args);
                }
            }
            
            if ($job_actcount < $num_jobs && $page1_count < 6) {
                
                $import_args = array(
                    'keyword' => $keyword,
                    'location' => $location,
                    'country_domain' => $country_domain,
                    'num_jobs' => $num_jobs,
                    'indeed_num_jobs' => (isset($indeed_page_nums) ? $indeed_page_nums : ''),
                    'job_username' => $job_username,
                    'platform' => $platform,
                    'page1_count' => $page1_count,
                    'page_num' => 1,
                    'expire_days' => $expire_days,
                    'found_jobs' => $found_jobs,
                    'job_count' => 1,
                    'job_actcount' => $job_actcount,
                );
                $this->import_jobs($import_args);
            }
        }
    }

}

global $jobsearch_indeed_scraping_schedulr;

$jobsearch_indeed_scraping_schedulr = new JobSearch_Indeed_Jobs_Scraping_Schedulr();
