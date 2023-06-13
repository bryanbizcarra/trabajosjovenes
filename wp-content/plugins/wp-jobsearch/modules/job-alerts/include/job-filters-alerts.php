<?php
// this is an include only WP file
if (!defined('ABSPATH')) {
    die;
}

class JobSearch_Job_Alerts_Job_Filters {

    public function __construct() {

        add_filter('jobsearch_job_alerts_filters_html', array($this, 'job_alerts_filters_html'), 10, 4);
        add_action('wp_ajax_jobsearch_alrtmodal_popup_openhtml', array($this, 'job_alerts_filters_html'));
        add_action('wp_ajax_nopriv_jobsearch_alrtmodal_popup_openhtml', array($this, 'job_alerts_filters_html'));
        
        add_action('wp_ajax_jobsearch_joblert_pop_salry_html', array($this, 'job_alertpop_filter_salry_html'));
        add_action('wp_ajax_nopriv_jobsearch_joblert_pop_salry_html', array($this, 'job_alertpop_filter_salry_html'));
    }

    public function keyword_filter_html($global_rand_id, $sh_atts) {

        $keyword_val = '';
        if (isset($_REQUEST['search_title']) && $_REQUEST['search_title'] != '') {
            $keyword_val = $_REQUEST['search_title'];
        }
        $keyword_val = jobsearch_esc_html($keyword_val);

        ob_start();
        ?>
        <div class="jobsearch-column-6">
            <div class="jobalert-filter-item">
                <label><?php esc_html_e('Keyword', 'wp-jobsearch') ?></label>
                <div class="filter-item-text">
                    <input type="text" name="search_title" class="chagn-keywords-field" value="<?php echo ($keyword_val) ?>">
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public function location_filter_html($global_rand_id, $left_filter_count_switch, $sh_atts) {

        global $jobsearch_plugin_options;
        
        $location_map_type = isset($jobsearch_plugin_options['location_map_type']) ? $jobsearch_plugin_options['location_map_type'] : '';
        if ($location_map_type == 'mapbox') {
            wp_enqueue_script('jobsearch-mapbox');
            wp_enqueue_script('jobsearch-mapbox-geocoder');
            wp_enqueue_script('mapbox-geocoder-polyfill');
            wp_enqueue_script('mapbox-geocoder-polyfillauto');
        } else {
            wp_enqueue_script('jobsearch-google-map');
        }
        //wp_enqueue_script('jobsearch-location-autocomplete');
        
        $loc_val = '';
        if (isset($_REQUEST['location']) && $_REQUEST['location'] != '') {
            $loc_val = $_REQUEST['location'];
        }
        if (isset($_REQUEST['location_location1']) && $_REQUEST['location_location1'] != '') {
            $loc_val = $_REQUEST['location_location1'];
            if (isset($_REQUEST['location_location2']) && $_REQUEST['location_location2'] != '') {
                $loc_val = $_REQUEST['location_location2'] . ', ' . $loc_val;
            }
            if (isset($_REQUEST['location_location3']) && $_REQUEST['location_location3'] != '') {
                $loc_val = $_REQUEST['location_location3'] . ', ' . $loc_val;
            }
        }
        $loc_val = jobsearch_esc_html($loc_val);

        $job_loc_filter = isset($sh_atts['job_filters_loc']) ? $sh_atts['job_filters_loc'] : '';

        ob_start();
        ?>
        <div class="jobsearch-column-6">
            <div class="jobalert-filter-item">
                <label><?php esc_html_e('Location', 'wp-jobsearch') ?></label>
                <div class="filter-item-text">
                    <div class="jobsearch_searchloc_div">
                        <span class="loc-loader"></span>
                        <?php
                        $citystat_zip_title = esc_html__('Location', 'wp-jobsearch');
                        $geo_rand_id = rand(1000000, 9999999);
                        if ($location_map_type == 'mapbox') {
                            $autocomplete_adres_type = isset($jobsearch_plugin_options['autocomplete_adres_type']) ? $jobsearch_plugin_options['autocomplete_adres_type'] : '';
                            $autocomplete_countries_json = '';
                            $autocomplete_countries = isset($jobsearch_plugin_options['restrict_contries_locsugg']) ? $jobsearch_plugin_options['restrict_contries_locsugg'] : '';
                            if (!empty($autocomplete_countries) && is_array($autocomplete_countries)) {
                                $autocomplete_countries_json = json_encode($autocomplete_countries);
                            }
                            $mapbox_access_token = isset($jobsearch_plugin_options['mapbox_access_token']) ? $jobsearch_plugin_options['mapbox_access_token'] : '';
                            $mapbox_style_url = isset($jobsearch_plugin_options['mapbox_style_url']) ? $jobsearch_plugin_options['mapbox_style_url'] : '';
                            if ($mapbox_access_token != '' && $mapbox_style_url != '') {
                                ?>
                                <div id="jobsearch-bodymapbox-gensbox-<?php echo ($geo_rand_id) ?>"></div>
                                <input id="lochiden_addr_<?php echo($geo_rand_id) ?>" type="hidden" name="search_loc" value="<?php echo urldecode($loc_val) ?>">
                                <script>
                                    jQuery('body').append('<div id="jobsearch-bodymapbox-genmap-<?php echo ($geo_rand_id) ?>" style="height:0;display:none;"></div>');
                                    mapboxgl.accessToken = '<?php echo ($mapbox_access_token) ?>';
                                    var cityAcMap = new mapboxgl.Map({
                                        container: 'jobsearch-bodymapbox-genmap-<?php echo ($geo_rand_id) ?>',
                                        style: '<?php echo ($mapbox_style_url) ?>',
                                        center: [-96, 37.8],
                                        scrollZoom: false,
                                        zoom: 3
                                    });
                                    var geocodParams = {
                                        accessToken: mapboxgl.accessToken,
                                        marker: false,
                                        flyTo: false,
                                        mapboxgl: mapboxgl
                                    };
                                    var selected_contries = '<?php echo ($autocomplete_countries_json) ?>';
                                    if (selected_contries != '') {
                                        var selected_contries_tojs = jQuery.parseJSON(selected_contries);
                                        var sel_countries_str = selected_contries_tojs.join();
                                        geocodParams['countries'] = sel_countries_str;
                                    }
                                    var mapboxGeocoder<?php echo($geo_rand_id) ?> = new MapboxGeocoder(geocodParams);
                                    document.getElementById('jobsearch-bodymapbox-gensbox-<?php echo ($geo_rand_id) ?>').appendChild(mapboxGeocoder<?php echo($geo_rand_id) ?>.onAdd(cityAcMap));
                                    mapboxGeocoder<?php echo($geo_rand_id) ?>.setInput('<?php echo urldecode($loc_val) ?>');

                                    mapboxGeocoder<?php echo($geo_rand_id) ?>.on('result', function(obj) {
                                        var place_name = obj.result.place_name;
                                        jQuery('#lochiden_addr_<?php echo($geo_rand_id) ?>').val(place_name);
                                    });
                                    jQuery(document).on('change', '#jobsearch-bodymapbox-gensbox-<?php echo ($geo_rand_id) ?> input[type=text]', function() {
                                        var this_input_val = jQuery(this).val();
                                        jQuery('#lochiden_addr_<?php echo ($geo_rand_id) ?>').val(this_input_val);
                                    });
                                </script>
                                <?php
                            } else {
                                ?>
                                <input placeholder="<?php esc_html_e('City, State or ZIP', 'wp-jobsearch') ?>" class="srch_autogeo_location" name="location" type="text" value="<?php echo urldecode($loc_val) ?>">
                                <?php
                            }
                        } else {
                            $autocomplete_adres_type = isset($jobsearch_plugin_options['autocomplete_adres_type']) ? $jobsearch_plugin_options['autocomplete_adres_type'] : '';
                            $autocomplete_countries_json = '';
                            $autocomplete_countries = isset($jobsearch_plugin_options['restrict_contries_locsugg']) ? $jobsearch_plugin_options['restrict_contries_locsugg'] : '';
                            if (!empty($autocomplete_countries) && is_array($autocomplete_countries)) {
                                $autocomplete_countries_json = json_encode($autocomplete_countries);
                            }
                            ?>
                            <script>
                                jQuery(document).ready(function() {
                                    var autocomplete_input = document.getElementById('location-address-<?php echo ($geo_rand_id) ?>');

                                    var autcomplete_options = {};
                                    <?php
                                    if ($autocomplete_adres_type == 'city_contry') {
                                    ?>
                                    var autcomplete_options = {
                                        types: ['(cities)'],
                                    };
                                    <?php
                                    }
                                    ?>
                                    var selected_contries_json = '';
                                    var selected_contries = '<?php echo ($autocomplete_countries_json) ?>';
                                    if (selected_contries != '') {
                                        var selected_contries_tojs = jQuery.parseJSON(selected_contries);
                                        selected_contries_json = {country: selected_contries_tojs};
                                        autcomplete_options.componentRestrictions = selected_contries_json;
                                    }

                                    var autocomplete = new google.maps.places.Autocomplete(autocomplete_input, autcomplete_options);
                                });
                            </script>
                            <input id="location-address-<?php echo($geo_rand_id) ?>" placeholder="<?php echo apply_filters('jobsearch_listin_serchbox_location_title', $citystat_zip_title) ?>"
                            name="search_loc"
                            value="<?php echo urldecode($loc_val) ?>"
                            type="text">
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <script>
        //jQuery('.jobsearch_search_location_field').cityAutocomplete();
        jQuery(document).on('click', '.jobsearch_searchloc_div', function () {
            jQuery('.jobsearch_search_location_field').prop('disabled', false);
        });
        jQuery(document).on('click', 'form', function () {
            var src_loc_val = jQuery(this).find('.jobsearch_search_location_field');
            src_loc_val.next('.loc_search_keyword').val(src_loc_val.val());
        });
        </script>
        <?php
        $html = ob_get_clean();
        $html = apply_filters('jobsearch_job_alerts_filter_location_html', $html);

        return $html;
    }

    public function sector_filter_html($global_rand_id, $left_filter_count_switch, $sh_atts) {

        $job_sector = '';
        if (isset($_REQUEST['sector_cat']) && $_REQUEST['sector_cat'] != '') {
            $job_sector = $_REQUEST['sector_cat'];
        }

        $job_sector_filter = isset($sh_atts['job_filters_sector']) ? $sh_atts['job_filters_sector'] : '';

        ob_start();
        $sector_args = array(
            'orderby' => 'name',
            'order' => 'ASC',
            'fields' => 'all',
            'hide_empty' => false,
            'slug' => '',
            //'parent' => 0,
        );
        $all_sector = get_terms('sector', $sector_args);
        if ($all_sector != '') {
            ?>
            <div class="jobsearch-column-6">
                <div class="jobalert-filter-item">
                    <label><?php esc_html_e('Sector', 'wp-jobsearch') ?></label>
                    <div class="jobsearch-profile-select to-fancyselect-con">
                        <select name="sector_cat[]" multiple="" placeholder="<?php esc_html_e('Select Job Sectors', 'wp-jobsearch') ?>">
                            <?php
                            foreach ($all_sector as $job_sectitem) {
                                $job_sect_selected = '';
                                if (is_array($job_sector) && in_array($job_sectitem->slug, $job_sector)) {
                                    $job_sect_selected = ' selected="selected"';
                                }
                                ?>
                                <option value="<?php echo ($job_sectitem->slug) ?>"<?php echo ($job_sect_selected) ?>><?php echo ($job_sectitem->name) ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <?php
        }
        $html = ob_get_clean();
        $html = apply_filters('jobsearch_job_alerts_filter_sector_html', $html, $all_sector);

        if ($job_sector_filter == 'no') {
            $html = '';
        }

        return $html;
    }

    public function skills_filter_html($global_rand_id, $sh_atts) {

        global $jobsearch_plugin_options;
        
        $job_skills = '';
        if (isset($_REQUEST['job_skills']) && $_REQUEST['job_skills'] != '') {
            $job_skills = $_REQUEST['job_skills'];
        }
        
        $job_skills_switch = isset($jobsearch_plugin_options['job-skill-switch']) ? $jobsearch_plugin_options['job-skill-switch'] : '';

        if ($job_skills_switch == 'on') {
            ob_start();
            $sector_args = array(
                'orderby' => 'term_id',
                'order' => 'DESC',
                'number' => 300,
                'fields' => 'all',
                'hide_empty' => false,
                'slug' => '',
                //'parent' => 0,
            );
            $all_sector = get_terms('skill', $sector_args);
            if ($all_sector != '') {
                ?>
                <div class="jobsearch-column-6">
                    <div class="jobalert-filter-item">
                        <label><?php esc_html_e('Skills', 'wp-jobsearch') ?></label>
                        <div class="jobsearch-profile-select to-fancyselect-con">
                            <select name="job_skills[]" multiple="" placeholder="<?php esc_html_e('Select Skills', 'wp-jobsearch') ?>">
                                <?php
                                foreach ($all_sector as $job_sectitem) {
                                    $job_sect_selected = '';
                                    if (is_array($job_skills) && in_array($job_sectitem->slug, $job_skills)) {
                                        $job_sect_selected = ' selected="selected"';
                                    }
                                    ?>
                                    <option value="<?php echo ($job_sectitem->slug) ?>"<?php echo ($job_sect_selected) ?>><?php echo ($job_sectitem->name) ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php
            }
            $html = ob_get_clean();
        } else {
            $html = '';
        }

        return $html;
    }

    public function type_filter_html($global_rand_id, $left_filter_count_switch, $sh_atts) {
        global $sitepress;
        
        $job_type = '';
        if (isset($_REQUEST['job_type']) && $_REQUEST['job_type'] != '') {
            $job_type = $_REQUEST['job_type'];
        }

        $job_type_filter = isset($sh_atts['job_filters_type']) ? $sh_atts['job_filters_type'] : '';

        ob_start();
        $typs_args = array(
            'taxonomy' => 'jobtype',
            'hide_empty' => false,
        );
        $typs_args = apply_filters('jobsearch_listing_jobtypes_filters_args', $typs_args);
        $all_job_type = get_terms($typs_args);
        if (empty($all_job_type) && function_exists('icl_object_id')) {
            $sitepress_def_lang = $sitepress->get_default_language();
            $sitepress_curr_lang = $sitepress->get_current_language();
            $sitepress->switch_lang($sitepress_def_lang, true);
            //
            $typs_args = array(
                'taxonomy' => 'jobtype',
                'hide_empty' => false,
            );
            $typs_args = apply_filters('jobsearch_listing_jobtypes_filters_args', $typs_args);
            $all_job_type = get_terms($typs_args);
            //
            $sitepress->switch_lang($sitepress_curr_lang, true);
        }
        if ($all_job_type != '') {
            ?>
            <div class="jobsearch-column-6">
                <div class="jobalert-filter-item">
                    <label><?php esc_html_e('Job Type', 'wp-jobsearch') ?></label>
                    <div class="jobsearch-profile-select to-fancyselect-con">
                        <select name="job_type[]" multiple="" placeholder="<?php esc_html_e('Select Job Types', 'wp-jobsearch') ?>">
                            <?php
                            foreach ($all_job_type as $job_typeitem) {
                                $job_type_selected = '';
                                if (is_array($job_type) && in_array($job_typeitem->slug, $job_type)) {
                                    $job_type_selected = ' selected="selected"';
                                }
                                ?>
                                <option value="<?php echo ($job_typeitem->slug) ?>"<?php echo ($job_type_selected) ?>><?php echo ($job_typeitem->name) ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <?php
        }
        $html = ob_get_clean();
        $html = apply_filters('jobsearch_job_alerts_filter_jobtype_html', $html, $all_job_type);

        if ($job_type_filter == 'no') {
            $html = '';
        }

        return $html;
    }

    public function custom_fields_filter_html($global_rand_id, $left_filter_count_switch, $sh_atts) {

        global $jobsearch_plugin_options, $sitepress, $jobsearch_onlycffield_name;

        $lang_code = '';
        if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
            $lang_code = $sitepress->get_current_language();
        }

        $salary_onoff_switch = isset($jobsearch_plugin_options['salary_onoff_switch']) ? $jobsearch_plugin_options['salary_onoff_switch'] : ''; // for job salary check

        $job_cus_fields = get_option("jobsearch_custom_field_job");
        ob_start();
        if (!empty($job_cus_fields)) {
            $selctize_remove_counter = 0;
            foreach ($job_cus_fields as $cus_fieldvar => $cus_field) {
                $all_item_empty = 0;
                if (isset($cus_field['options']['value']) && is_array($cus_field['options']['value'])) {
                    foreach ($cus_field['options']['value'] as $cus_field_options_value) {

                        if ($cus_field_options_value != '') {
                            $all_item_empty = 0;
                            break;
                        } else {
                            $all_item_empty = 1;
                        }
                    }
                }
                if ($cus_field['type'] == 'salary') {
                    $cus_field['enable-search'] = 'yes';
                }
                if ($cus_field['type'] == 'dependent_fields') {
                    echo apply_filters('jobsearch_dashboard_custom_field_dependent_fields_load', '', 0, $cus_field, '', $cus_fieldvar, 'job_alert');
                }
                if (isset($cus_field['enable-search']) && $cus_field['enable-search'] == 'yes' && ($all_item_empty == 0)) {
                    if ($cus_field['type'] == 'salary') {
                        $query_str_var_name = 'jobsearch_field_job_salary';
                        $str_salary_type_name = 'job_salary_type';
                    } else {
                        $query_str_var_name = isset($cus_field['name']) ? $cus_field['name'] : '';
                    }
                    
                    if ($jobsearch_onlycffield_name != '') {
                        if ($query_str_var_name != $jobsearch_onlycffield_name) {
                            continue;
                        }
                    }

                    $cus_field_label_arr = isset($cus_field['label']) ? $cus_field['label'] : '';
                    $type = isset($cus_field['type']) ? $cus_field['type'] : '';

                    if ($type == 'text') {
                        $cus_field_label_arr = apply_filters('wpml_translate_single_string', $cus_field_label_arr, 'Custom Fields', 'Text Field Label - ' . $cus_field_label_arr, $lang_code);
                    } else if ($type == 'email') {
                        $cus_field_label_arr = apply_filters('wpml_translate_single_string', $cus_field_label_arr, 'Custom Fields', 'Email Field Label - ' . $cus_field_label_arr, $lang_code);
                    } else if ($type == 'number') {
                        $cus_field_label_arr = apply_filters('wpml_translate_single_string', $cus_field_label_arr, 'Custom Fields', 'Number Field Label - ' . $cus_field_label_arr, $lang_code);
                    } else if ($type == 'date') {
                        $cus_field_label_arr = apply_filters('wpml_translate_single_string', $cus_field_label_arr, 'Custom Fields', 'Date Field Label - ' . $cus_field_label_arr, $lang_code);
                    } else if ($type == 'dropdown') {
                        $cus_field_label_arr = apply_filters('wpml_translate_single_string', $cus_field_label_arr, 'Custom Fields', 'Dropdown Field Label - ' . $cus_field_label_arr, $lang_code);
                    } else if ($type == 'range') {
                        $cus_field_label_arr = apply_filters('wpml_translate_single_string', $cus_field_label_arr, 'Custom Fields', 'Range Field Label - ' . $cus_field_label_arr, $lang_code);
                    } else if ($type == 'textarea') {
                        $cus_field_label_arr = apply_filters('wpml_translate_single_string', $cus_field_label_arr, 'Custom Fields', 'Textarea Field Label - ' . $cus_field_label_arr, $lang_code);
                    } else if ($type == 'heading') {
                        $cus_field_label_arr = apply_filters('wpml_translate_single_string', $cus_field_label_arr, 'Custom Fields', 'Heading Field Label - ' . $cus_field_label_arr, $lang_code);
                    } else if ($type == 'salary') {
                        $cus_field_label_arr = apply_filters('wpml_translate_single_string', $cus_field_label_arr, 'Custom Fields', 'Salary Label - ' . $cus_field_label_arr, $lang_code);
                    }

                    $custom_field_placeholder = isset($cus_field['placeholder']) ? $cus_field['placeholder'] : '';
                    $custom_field_placeholder = apply_filters('wpml_translate_single_string', $custom_field_placeholder, 'Custom Fields', 'Dropdown Field Placeholder - ' . $custom_field_placeholder, $lang_code);
                    
                    $custom_field_placeholder = apply_filters('jobsearch_jobalert_filterpop_drpdwn_placeholder', $custom_field_placeholder);

                    if ($cus_field['type'] == 'dropdown') {
                        if (isset($cus_field['options']['value']) && !empty($cus_field['options']['value'])) {
                            $cut_field_flag = 0;
                            $dropdwn_is_multi = isset($cus_field['multi']) ? $cus_field['multi'] : '';
                            $dropdwn_placeholder = $custom_field_placeholder;
                            ?>
                            <div class="jobsearch-column-6">
                                <div class="jobalert-filter-item">
                                    <label><?php echo esc_html(stripslashes($cus_field_label_arr)); ?></label>
                                    <div class="jobsearch-profile-select to-cffancyselect-con">
                                        <?php
                                        if ($dropdwn_is_multi != 'yes') {
                                            ?>
                                            <a class="jobsearch-alrtslectizecf-remove" style="display: none;" data-selid="<?php echo ($selctize_remove_counter) ?>"><i class="fa fa-times"></i></a>
                                            <?php
                                        }
                                        $selctize_remove_counter++;
                                        ?>
                                        <select name="<?php echo esc_html($query_str_var_name) . ($dropdwn_is_multi == 'yes' ? '[]' : ''); ?>" <?php echo ($dropdwn_is_multi == 'yes' ? 'multiple' : '') ?> placeholder="<?php echo ($dropdwn_placeholder) ?>">
                                            <?php
                                            if ($dropdwn_is_multi != 'yes') {
                                                ?>
                                                <option value=""><?php echo ($custom_field_placeholder != '' ? $custom_field_placeholder : esc_html__('Select', 'wp-jobsearch')) ?></option>
                                                <?php
                                            }
                                            foreach ($cus_field['options']['value'] as $cus_field_options_value) {
                                                $custom_dropdown_selected = '';
                                                if ($dropdwn_is_multi) {
                                                    $request_val = isset($_REQUEST[$query_str_var_name]) ? $_REQUEST[$query_str_var_name] : '';
                                                    $request_val_arr = explode(",", $request_val);
                                                    if (!empty($request_val_arr) && in_array($cus_field_options_value, $request_val_arr)) {
                                                        $custom_dropdown_selected = ' selected="selected"';
                                                    }
                                                } else {
                                                    if (isset($_REQUEST[$query_str_var_name]) && $_REQUEST[$query_str_var_name] == $cus_field_options_value) {
                                                        $custom_dropdown_selected = ' selected="selected"';
                                                    }
                                                }
                                                ?>
                                                <option value="<?php echo esc_html($cus_field_options_value); ?>"<?php echo ($custom_dropdown_selected) ?>><?php echo(apply_filters('wpml_translate_single_string', $cus_field['options']['label'][$cut_field_flag], 'Custom Fields', 'Dropdown Option Label - ' . $cus_field['options']['label'][$cut_field_flag], $lang_code)); ?></option>
                                                <?php
                                                $cut_field_flag++;
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else if ($cus_field['type'] == 'salary') {
                        $job_salary_types = isset($jobsearch_plugin_options['job-salary-types']) ? $jobsearch_plugin_options['job-salary-types'] : '';

                        if ($salary_onoff_switch != 'off') {

                            $salary_field_type = isset($cus_field['field-style']) ? $cus_field['field-style'] : 'simple'; //input, slider, input_slider

                            if (strpos($salary_field_type, '-') !== FALSE) {
                                $salary_field_type_arr = explode("_", $salary_field_type);
                            } else {
                                $salary_field_type_arr[0] = $salary_field_type;
                            }
                            ?>
                            <div class="jobsearch-column-6">
                                <div class="jobalert-filter-item jobalert-salrytype-filter" data-id="<?php echo ($cus_fieldvar) ?>">
                                    <label><?php echo esc_html(stripslashes($cus_field_label_arr)); ?></label>
                                    <?php
                                    // Salary Types
                                    if (!empty($job_salary_types)) {
                                        $slar_type_count = 1;
                                        ?>
                                        <div class="jobsearch-salary-types-filter">
                                            <ul>
                                                <?php
                                                foreach ($job_salary_types as $job_salary_type) {
                                                    $salary_countr = rand(100000, 9999999);
                                                    $job_salary_type = apply_filters('wpml_translate_single_string', $job_salary_type, 'JobSearch Options', 'Salary Type - ' . $job_salary_type, $lang_code);
                                                    $slalary_type_selected = '';
                                                    if ($slar_type_count == 1) {
                                                        $slalary_type_selected = ' checked="checked"';
                                                    }
                                                    ?>
                                                    <li class="salary-type-radio">
                                                        <input type="radio"
                                                               id="salary_type_<?php echo ($slar_type_count . '-' . $salary_countr) ?>"
                                                               name="<?php echo($str_salary_type_name) ?>"
                                                               class="job_salary_type jobalert-crti-typebtn"<?php echo ($slalary_type_selected) ?>
                                                               value="type_<?php echo($slar_type_count) ?>">
                                                        <label for="salary_type_<?php echo($slar_type_count . '-' . $salary_countr) ?>">
                                                            <span></span>
                                                            <small><?php echo($job_salary_type) ?></small>
                                                        </label>
                                                    </li>
                                                    <?php
                                                    $slar_type_count++;
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                        <?php
                                    }
                                    //
                                    ?>
                                    <div class="jobsearch-profile-select to-cffancyselect-con">
                                        <a class="jobsearch-alrtslectizecf-remove" style="display: none;" data-selid="<?php echo ($selctize_remove_counter) ?>"><i class="fa fa-times"></i></a>
                                        <?php
                                        $selctize_remove_counter++;
                                        $salary_flag = 0;
                                        while (count($salary_field_type_arr) > $salary_flag) {
                                            
                                            ob_start();
                                            if (!empty($job_salary_types)) {
                                                $slar_type_count = 1;
                                                foreach ($job_salary_types as $post_salary_typ) {

                                                    if ($slar_type_count == 1) {
                                                        $salary_min = isset($cus_field['min' . $slar_type_count]) ? $cus_field['min' . $slar_type_count] : '';
                                                        $salary_interval = isset($cus_field['interval' . $slar_type_count]) ? $cus_field['interval' . $slar_type_count] : '';
                                                        $salary_laps = isset($cus_field['laps' . $slar_type_count]) ? $cus_field['laps' . $slar_type_count] : '';
                                                        $salary_laps = $salary_laps > 200 ? 200 : $salary_laps;
                                                    }
                                                    $slar_type_count++;
                                                }
                                                $filter_more_counter = 1;
                                                ?>
                                                <div class="salarytypes-rangelist-con">
                                                    <select name="<?php echo esc_html($query_str_var_name); ?>" placeholder="<?php echo apply_filters('jobsearch_filters_salary_field_placeholder', esc_html__('Select', 'wp-jobsearch')) ?>">
                                                        <option value=""><?php echo apply_filters('jobsearch_filters_salary_field_placeholder', esc_html__('Select', 'wp-jobsearch')) ?></option>
                                                        <?php
                                                        $loop_flag = 1;
                                                        while ($loop_flag <= $salary_laps) {
                                                            $custom_slider_selected = '';
                                                            if (isset($_REQUEST[$query_str_var_name]) && $_REQUEST[$query_str_var_name] == (($salary_min + 1) . "-" . ($salary_min + $salary_interval))) {
                                                                $custom_slider_selected = ' selected="selected"';
                                                            }
                                                            $salary_from = ($salary_min + 1);
                                                            $salary_upto = ($salary_min + $salary_interval);
                                                            ?>
                                                            <option value="<?php echo esc_html((($salary_min + 1) . "-" . ($salary_min + $salary_interval))); ?>" <?php echo esc_html($custom_slider_selected); ?>><?php echo((($salary_from) . " - " . ($salary_upto))); ?></option>
                                                            <?php
                                                            $salary_min = $salary_min + $salary_interval;
                                                            $loop_flag++;
                                                            $filter_more_counter++;
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <?php
                                            } else {
                                                $salary_min = $cus_field['min'];
                                                $salary_laps = $cus_field['laps'];
                                                $salary_laps = $salary_laps > 200 ? 200 : $salary_laps;
                                                $salary_interval = $cus_field['interval'];
                                                $filter_more_counter = 1;
                                                ?>
                                                <select name="<?php echo esc_html($query_str_var_name); ?>" placeholder="<?php echo apply_filters('jobsearch_filters_salary_field_placeholder', esc_html__('Select', 'wp-jobsearch')) ?>">
                                                    <option value=""><?php echo apply_filters('jobsearch_filters_salary_field_placeholder', esc_html__('Select', 'wp-jobsearch')) ?></option>
                                                    <?php
                                                    $loop_flag = 1;
                                                    while ($loop_flag <= $salary_laps) {
                                                        $custom_slider_selected = '';
                                                        if (isset($_REQUEST[$query_str_var_name]) && $_REQUEST[$query_str_var_name] == (($salary_min + 1) . "-" . ($salary_min + $salary_interval))) {
                                                            $custom_slider_selected = ' selected="selected"';
                                                        }
                                                        $salary_from = ($salary_min + 1);
                                                        $salary_upto = ($salary_min + $salary_interval);
                                                        ?>
                                                        <option value="<?php echo esc_html((($salary_min + 1) . "-" . ($salary_min + $salary_interval))); ?>" <?php echo esc_html($custom_slider_selected); ?>><?php echo((($salary_from) . " - " . ($salary_upto))); ?></option>
                                                        <?php
                                                        $salary_min = $salary_min + $salary_interval;
                                                        $loop_flag++;
                                                        $filter_more_counter++;
                                                    }
                                                    ?>
                                                </select>
                                                <?php
                                            }
                                            $slary_html = ob_get_clean();
                                            echo apply_filters('jobsearch_inalert_popup_filters_salary_cffield', $slary_html, $query_str_var_name, $salary_laps, $salary_min, $salary_interval, $cus_field);
                                            $salary_flag++;
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        $custom_field_val = '';
                        if (isset($_REQUEST[$query_str_var_name]) && $_REQUEST[$query_str_var_name] == $cus_field_options_value) {
                            $custom_field_val = $_REQUEST[$query_str_var_name];
                        }
                        ?>
                        <div class="jobsearch-column-6">
                            <div class="jobalert-filter-item">
                                <label><?php echo esc_html(stripslashes($cus_field_label_arr)); ?></label>
                                <div class="filter-item-text">
                                    <input type="text" name="<?php echo esc_html($query_str_var_name) ?>" value="<?php echo ($custom_field_val) ?>">
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
            }
        }

        $html = ob_get_clean();

        return $html;
    }

    public function job_alerts_filters_html($html = '', $global_rand_id = 0, $left_filter_count_switch = '', $sh_atts = array()) {

        global $jobsearch_plugin_options;
        
        if (isset($_POST['job_shatts_str']) && $_POST['job_shatts_str'] != '') {
            $sh_atts = stripslashes($_POST['job_shatts_str']);
            $sh_atts = json_decode($sh_atts, true);
            //
            $global_rand_id = isset($_POST['sh_globrnd_id']) ? $_POST['sh_globrnd_id'] : '';
            $left_filter_count_switch = isset($sh_atts['job_filters_count']) ? $sh_atts['job_filters_count'] : '';
        }
        
        //
        $job_alfiltr_sectr = isset($jobsearch_plugin_options['job_alerts_filtr_sectr']) ? $jobsearch_plugin_options['job_alerts_filtr_sectr'] : '';
        $job_alfiltr_jobtype = isset($jobsearch_plugin_options['job_alerts_filtr_jobtype']) ? $jobsearch_plugin_options['job_alerts_filtr_jobtype'] : '';
        $job_alfiltr_loc = isset($jobsearch_plugin_options['job_alerts_filtr_location']) ? $jobsearch_plugin_options['job_alerts_filtr_location'] : '';
        $job_alfiltr_cusfields = isset($jobsearch_plugin_options['job_alerts_filtr_cusfield']) ? $jobsearch_plugin_options['job_alerts_filtr_cusfield'] : '';
        //

        $job_types_switch = isset($jobsearch_plugin_options['job_types_switch']) ? $jobsearch_plugin_options['job_types_switch'] : '';

        $filters_op_sort = isset($jobsearch_plugin_options['jobs_srch_filtrs_sort']) ? $jobsearch_plugin_options['jobs_srch_filtrs_sort'] : '';

        $filters_op_sort = isset($filters_op_sort['fields']) ? $filters_op_sort['fields'] : '';

        if (!empty($filters_op_sort)) {
            
            global $jobsearch_onlycffield_name;
            $cusfields_names_arr = array();
            $job_cus_fields = get_option("jobsearch_custom_field_job");
            if (!empty($job_cus_fields)) {
                foreach ($job_cus_fields as $cus_fieldvar => $cus_field) {
                    if (isset($cus_field['name']) && $cus_field['name'] != '') {
                        $cusfield_name = $cus_field['name'];
                        $cusfields_names_arr[] = $cusfield_name;
                    }
                    if (isset($cus_field['type']) && $cus_field['type'] == 'salary') {
                        $cusfields_names_arr[] = 'jobsearch_field_job_salary';
                    }
                }
            }
            
            $html .= '<div class="jobsearch-row">';
            
            $frequencies = array(
                'job_alerts_frequency_hourly' => esc_html__('Hourly', 'wp-jobsearch'),
                'job_alerts_frequency_daily' => esc_html__('Daily', 'wp-jobsearch'),
                'job_alerts_frequency_weekly' => esc_html__('Weekly', 'wp-jobsearch'),
                'job_alerts_frequency_fortnightly' => esc_html__('Fortnightly', 'wp-jobsearch'),
                'job_alerts_frequency_monthly' => esc_html__('Monthly', 'wp-jobsearch'),
                'job_alerts_frequency_biannually' => esc_html__('Biannually', 'wp-jobsearch'),
                'job_alerts_frequency_annually' => esc_html__('Annually', 'wp-jobsearch'),
                'job_alerts_frequency_never' => esc_html__('Never', 'wp-jobsearch'),
            );
            $frequencies = apply_filters('jobsearch_job_alert_frequencies_list', $frequencies);
            
            $alert_title = '';
            $alert_freq = '';
            if (isset($_REQUEST['alert_id'])) {
                $alert_id = $_REQUEST['alert_id'];
                if (get_post_type($alert_id) == 'job-alert') {
                    $alert_title = get_the_title($alert_id);
                    $chek_alert_freq = get_post_meta($alert_id, 'jobsearch_field_alert_hourly', true);
                    if ($chek_alert_freq == 'on') {
                        $alert_freq = 'job_alerts_frequency_hourly';
                    }
                    $chek_alert_freq = get_post_meta($alert_id, 'jobsearch_field_alert_daily', true);
                    if ($chek_alert_freq == 'on') {
                        $alert_freq = 'job_alerts_frequency_daily';
                    }
                    $chek_alert_freq = get_post_meta($alert_id, 'jobsearch_field_alert_weekly', true);
                    if ($chek_alert_freq == 'on') {
                        $alert_freq = 'job_alerts_frequency_weekly';
                    }
                    $chek_alert_freq = get_post_meta($alert_id, 'jobsearch_field_alert_fortnightly', true);
                    if ($chek_alert_freq == 'on') {
                        $alert_freq = 'job_alerts_frequency_fortnightly';
                    }
                    $chek_alert_freq = get_post_meta($alert_id, 'jobsearch_field_alert_monthly', true);
                    if ($chek_alert_freq == 'on') {
                        $alert_freq = 'job_alerts_frequency_monthly';
                    }
                    $chek_alert_freq = get_post_meta($alert_id, 'jobsearch_field_alert_biannually', true);
                    if ($chek_alert_freq == 'on') {
                        $alert_freq = 'job_alerts_frequency_biannually';
                    }
                    $chek_alert_freq = get_post_meta($alert_id, 'jobsearch_field_alert_annually', true);
                    if ($chek_alert_freq == 'on') {
                        $alert_freq = 'job_alerts_frequency_annually';
                    }
                }
            }
            if (isset($_REQUEST['alerts-name']) && $_REQUEST['alerts-name'] != '') {
                $alert_title = $_REQUEST['alerts-name'];
            }
            if (isset($_REQUEST['alert_frequency']) && $_REQUEST['alert_frequency'] != '') {
                $alert_freq = $_REQUEST['alert_frequency'];
            }
            
            $filters_html_arr = array();
            ob_start();
            ?>
            <div class="jobsearch-column-6">
                <div class="jobalert-filter-item">
                    <label><?php esc_html_e('Job Alert Label', 'wp-jobsearch') ?></label>
                    <div class="filter-item-text">
                        <input type="text" name="alerts_name" class="" value="<?php echo ($alert_title) ?>">
                    </div>
                </div>
            </div>
            <div class="jobsearch-column-6">
                <div class="jobalert-filter-item">
                    <label><?php esc_html_e('Select Frequency', 'wp-jobsearch') ?></label>
                    <div class="jobsearch-profile-select to-fancyselect-con">
                        <select name="alert_frequency" placeholder="<?php esc_html_e('Select Frequency', 'wp-jobsearch') ?>">
                            <?php
                            foreach ($frequencies as $frequency => $label) {
                                $freq_selected = '';
                                if ($frequency == $alert_freq) {
                                    $freq_selected = ' selected="selected"';
                                }
                                ?>
                                <option value="<?php echo ($frequency) ?>"<?php echo ($freq_selected) ?>><?php echo ($label) ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <?php
            $title_html = ob_get_clean();
            $filters_html_arr[] = $title_html;
            $filters_html_arr[] = $this->keyword_filter_html($global_rand_id, $sh_atts);
            foreach ($filters_op_sort as $filter_sort_key => $filter_sort_val) {
                if ($filter_sort_key == 'location' && $job_alfiltr_loc == 'on') {
                    $filters_html_arr[] = $this->location_filter_html($global_rand_id, $left_filter_count_switch, $sh_atts);
                } else if ($filter_sort_key == 'sector') {
                    if ($job_alfiltr_sectr == 'on') {
                        $filters_html_arr[] = $this->sector_filter_html($global_rand_id, $left_filter_count_switch, $sh_atts);
                    }
                    $filters_html_arr[] = $this->skills_filter_html($global_rand_id, $sh_atts);
                } else if ($filter_sort_key == 'job_type' && $job_alfiltr_jobtype == 'on') {
                    if ($job_types_switch != 'off') {
                        $filters_html_arr[] = $this->type_filter_html($global_rand_id, $left_filter_count_switch, $sh_atts);
                    }
                } else if ($job_alfiltr_cusfields == 'on') {
                    if (!empty($cusfields_names_arr)) {
                        foreach ($cusfields_names_arr as $cus_fieldname) {
                            if ($cus_fieldname == $filter_sort_key) {
                                $jobsearch_onlycffield_name = $cus_fieldname;
                                $filters_html_arr[] = $this->custom_fields_filter_html($global_rand_id, $left_filter_count_switch, $sh_atts);
                            }
                        }
                    }
                }
            }
            if (!empty($filters_html_arr)) {
                $filters_html_arr = apply_filters('jobsearch_alrtfiltrs_html_chunks_sortarr', $filters_html_arr);
                foreach ($filters_html_arr as $filter_html_itm) {
                    $html .= $filter_html_itm;
                }
            }
            $html .= '</div>';
            $html .= '<span class="jobsearch-job-shatts" data-id="' . $global_rand_id . '" style="display:none;">' . json_encode($sh_atts) . '</span>';
        }

        if (isset($_POST['job_shatts_str'])) {
            echo json_encode(array('pop_html' => $html));
            die;
        } else {
            return $html;
        }
    }
    
    public function job_alertpop_filter_salry_html() {
        global $jobsearch_plugin_options;
        $job_salary_types = isset($jobsearch_plugin_options['job-salary-types']) ? $jobsearch_plugin_options['job-salary-types'] : '';
        $type_id = $_POST['type_id'];
        $field_id = $_POST['fid'];
        $job_cus_fields = get_option("jobsearch_custom_field_job");
        
        if (!empty($job_salary_types)) {
            $slar_type_count = str_replace('type_', '', $type_id);
            $cus_field = isset($job_cus_fields[$field_id]) ? $job_cus_fields[$field_id] : '';
            $salary_min = isset($cus_field['min' . $slar_type_count]) ? $cus_field['min' . $slar_type_count] : '';
            $salary_interval = isset($cus_field['interval' . $slar_type_count]) ? $cus_field['interval' . $slar_type_count] : '';
            $salary_laps = isset($cus_field['laps' . $slar_type_count]) ? $cus_field['laps' . $slar_type_count] : '';
            $salary_laps = $salary_laps > 200 ? 200 : $salary_laps;

            if ($salary_min > 0 && $salary_interval > 0 && $salary_laps > 0) {
                $filter_more_counter = 1;
                ob_start();
                ?>
                <select name="jobsearch_field_job_salary" class="salary-load-selectize" placeholder="<?php echo apply_filters('jobsearch_filters_salary_field_placeholder', esc_html__('Select', 'wp-jobsearch')) ?>">
                    <option value=""><?php echo apply_filters('jobsearch_filters_salary_field_placeholder', esc_html__('Select', 'wp-jobsearch')) ?></option>
                    <?php
                    $loop_flag = 1;
                    while ($loop_flag <= $salary_laps) {
                        $salary_from = ($salary_min + 1);
                        $salary_upto = ($salary_min + $salary_interval);
                        ?>
                        <option value="<?php echo esc_html((($salary_min + 1) . "-" . ($salary_min + $salary_interval))); ?>"><?php echo((($salary_from) . " - " . ($salary_upto))); ?></option>
                        <?php
                        $salary_min = $salary_min + $salary_interval;
                        $loop_flag++;
                        $filter_more_counter++;
                    }
                    ?>
                </select>
                <?php
                $html = ob_get_clean();
                wp_send_json(array('html' => $html));
            }
        }
        
        die;
    }

}

new JobSearch_Job_Alerts_Job_Filters();
