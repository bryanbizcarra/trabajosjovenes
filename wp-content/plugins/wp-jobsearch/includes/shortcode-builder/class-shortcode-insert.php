<?php
/**
 * Creates the admin interface to add shortcodes to the editor
 *
 * @package  Eyecix
 * @since 2.0
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * ec_shortcode_insert class
 */
if (!class_exists('jobsearch_builder_shortcode_insert')) {

    class jobsearch_builder_shortcode_insert {

        /**
         * __construct function
         *
         * @access public
         * @return  void
         */
        public function __construct() {
            add_action('media_buttons', array($this, 'jobsearch_builder_media_buttons'), 20);
            add_action('admin_footer', array($this, 'jobsearch_builder_popup_html'));
            add_action('wp_footer', array($this, 'jobsearch_builder_popup_html'));
        }

        /**
         * media_buttons function
         *
         * @access public
         * @return void
         */
        public function jobsearch_builder_media_buttons($editor_id = 'content') {
            global $pagenow;

            // Only run on add/edit screens
            $jobsearch_builder_output = '';
            //if (in_array($pagenow, array('post.php', 'page.php', 'post-new.php', 'post-edit.php'))) {
            $cond_pass = true;
            if (isset($_GET['page']) && $_GET['page'] == 'jobsearch-email-templates-fields') {
                $cond_pass = false;
            }
            if (is_admin() && $cond_pass) {
                $jobsearch_builder_output = '<a href="#TB_inline?width=4000&amp;inlineId=jobsearch-builder-choose-shortcode" class="thickbox button jobsearch-builder-thicbox" title="' . esc_html__('WP JobSearch Shortcodes', 'wp-jobsearch') . '">' . esc_html__('WP JobSearch Shortcodes', 'wp-jobsearch') . '</a>';
            }
            echo ($jobsearch_builder_output);
        }

        /**
         * Build out the input fields for shortcode content
         * @param  string $key
         * @param  array $param the parameters of the input
         * @return void
         */
        public function jobsearch_builder_build_fields($jobsearch_builder_key, $jobsearch_builder_param) {
            $label = isset($jobsearch_builder_param['label']) && $jobsearch_builder_param['label'] != "" ? $jobsearch_builder_param['label'] : "";
            $desc = isset($jobsearch_builder_param['desc']) && $jobsearch_builder_param['desc'] != "" ? $jobsearch_builder_param['desc'] : "";
            $jobsearch_builder_html = '<tr>';
            $jobsearch_builder_html .= '<td class="label">' . $label . ':</td>';
            switch ($jobsearch_builder_param['type']) {
                case 'text' :

                    // prepare
                    $jobsearch_builder_output = '<td><label class="screen-reader-text" for="' . $jobsearch_builder_key . '">' . $jobsearch_builder_param['label'] . '</label>';
                    $jobsearch_builder_output .= '<input type="text" class="jobsearch-builder-form-text '.(isset($jobsearch_builder_param['classes']) ? $jobsearch_builder_param['classes'] : '').' jobsearch-builder-input" name="' . $jobsearch_builder_key . '" id="' . $jobsearch_builder_key . '" value="' . $jobsearch_builder_param['std'] . '" />' . "\n";
                    $jobsearch_builder_output .= '<span class="jobsearch-builder-form-desc">' . $jobsearch_builder_param['desc'] . '</span></td>' . "\n";

                    // append
                    $jobsearch_builder_html .= $jobsearch_builder_output;

                    break;

                case 'textarea' :

                    // prepare
                    $jobsearch_builder_output = '<td><label class="screen-reader-text" for="' . $jobsearch_builder_key . '">' . $jobsearch_builder_param['label'] . '</label>';
                    $jobsearch_builder_output .= '<textarea rows="10" cols="30" name="' . $jobsearch_builder_key . '" id="' . $jobsearch_builder_key . '" class="jobsearch-builder-form-textarea jobsearch-builder-input">' . $jobsearch_builder_param['std'] . '</textarea>' . "\n";
                    $jobsearch_builder_output .= '<span class="jobsearch-builder-form-desc">' . $jobsearch_builder_param['desc'] . '</span></td>' . "\n";

                    // append
                    $jobsearch_builder_html .= $jobsearch_builder_output;

                    break;

                case 'select' :

                    // prepare
                    $jobsearch_builder_output = '<td><label class="screen-reader-text" for="' . $jobsearch_builder_key . '">' . $label . '</label>';
                    $jobsearch_builder_output .= '<select name="' . $jobsearch_builder_key . '" id="' . $jobsearch_builder_key . '" class="jobsearch-builder-form-select jobsearch-builder-input">' . "\n";

                    foreach ($jobsearch_builder_param['options'] as $jobsearch_builder_value => $jobsearch_builder_option) {
                        $jobsearch_builder_output .= '<option value="' . $jobsearch_builder_value . '">' . $jobsearch_builder_option . '</option>' . "\n";
                    }

                    $jobsearch_builder_output .= '</select>' . "\n";
                    $jobsearch_builder_output .= '<span class="jobsearch-builder-form-desc">' . $desc . '</span></td>' . "\n";

                    // append
                    $jobsearch_builder_html .= $jobsearch_builder_output;

                    break;

                case 'api_loc_select' :
                    
                    global $wpdb, $jobsearch_gdapi_allocation;
                    $jobsearch_locsetin_options = get_option('jobsearch_locsetin_options');

                    $loc_optionstype = isset($jobsearch_locsetin_options['loc_optionstype']) ? $jobsearch_locsetin_options['loc_optionstype'] : '';
                    
                    $saved_country = '';
                    $saved_state = '';
                    $saved_city = '';
                    
                    $api_contries_list = array();
                    if (class_exists('JobSearch_plugin')) {
                        $api_contries_list = $jobsearch_gdapi_allocation::get_countries();
                    }
                    $contry_arr_list = array();
                    if (!empty($api_contries_list)) {
                        foreach ($api_contries_list as $api_cntry_key => $api_cntry_val) {
                            if (isset($api_cntry_val->code)) {
                                $contry_arr_list[$api_cntry_val->code] = $api_cntry_val->name;
                            }
                        }
                    }
                    
                    $nameof_singl_contry = '';
                    $contry_singl_contry = isset($jobsearch_locsetin_options['contry_singl_contry']) ? $jobsearch_locsetin_options['contry_singl_contry'] : '';
                    if ($contry_singl_contry != '' && ($loc_optionstype == '2' || $loc_optionstype == '3')) {
                        $nameof_singl_contry = isset($api_contries_list[$contry_singl_contry]) ? $api_contries_list[$contry_singl_contry] : '';
                    }

                    // prepare
                    $jobsearch_builder_output = '<td><label class="screen-reader-text" for="' . $jobsearch_builder_key . '">' . $label . '</label>';
                    $rand_num = rand(1000000, 9999999);
                    ob_start();
                    ?>
                    <script>
                        var jobsearch_vc_custm_getJSON = function (url, callback) {
                            var xhr = new XMLHttpRequest();
                            xhr.open('GET', url, true);
                            xhr.responseType = 'json';
                            xhr.onload = function () {
                                var status = xhr.status;
                                if (status === 200) {
                                    callback(null, xhr.response);
                                } else {
                                    callback(status, xhr.response);
                                }
                            };
                            xhr.send();
                        };

                        function all_loc_str_snd_<?php echo($rand_num) ?>() {
                            var loc_contry = '';
                            if (jQuery('#countryId').length > 0) {
                                loc_contry = jQuery('#countryId').val();
                            }

                            var loc_state = jQuery('#stateId').val();

                            var loc_city = '';
                            if (jQuery('#cityId').val() != "pls_wait") {
                                loc_city = jQuery('#cityId').val();
                            }

                            var loc_str = '';
                            loc_str = loc_contry + '|' + loc_state + '|' + loc_city;
                            jQuery('.api_all_locs_<?php echo($rand_num) ?>').val(loc_str);
                        }

                        $('#countryId').on('change', function () {
                            all_loc_str_snd_<?php echo($rand_num) ?>();
                        });
                        $(document).on('change', '#stateId', function () {
                            all_loc_str_snd_<?php echo($rand_num) ?>();
                        });
                        $(document).on('change', '#cityId', function () {
                            all_loc_str_snd_<?php echo($rand_num) ?>();
                        });
                    </script>
                    <?php
                    if ($loc_optionstype == '0' || $loc_optionstype == '1') { ?>
                        <div class="jobsearch-vcloc-dropdwn-con">
                            <label><?php esc_html_e('Country', 'wp-jobsearch') ?></label>
                            <select id="countryId" class="countries">
                                <?php
                                foreach ($contry_arr_list as $dr_opt_key => $dr_opt_val) { ?>
                                    <option value="<?php echo esc_html($dr_opt_val) ?>"
                                            code="<?php echo esc_html($dr_opt_key) ?>" <?php echo($dr_opt_val == $saved_country ? 'selected="selected"' : '') ?>
                                            data-countryid="<?php echo esc_html($dr_opt_key) ?>"><?php echo esc_html($dr_opt_val) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    <?php } ?>
                    <?php if ($loc_optionstype != '4') { ?>
                    <div class="jobsearch-vcloc-dropdwn-con">
                        <label><?php esc_html_e('State', 'wp-jobsearch') ?></label>
                        <?php
                        $single_country_code = '';
                        //$total_countries = read_location_file('countries.json');
                        $total_countries = $countries = $wpdb->get_results("SELECT * FROM `{$wpdb->base_prefix}jobsearch_countries` order by name ");

                        $myArray = $total_countries;
                        $valueToCheckAgainst = $nameof_singl_contry != "" ? trim($nameof_singl_contry) : $saved_country;

                        $myNewArray = array_filter($myArray, function ($value) use ($valueToCheckAgainst) {
                            if ($value->name == $valueToCheckAgainst) {
                                return $value->code;
                            }
                        });
                        if (count($myNewArray) > 0) {
                            $arrayKey = array_keys($myNewArray);
                            $single_country_code = $myNewArray[$arrayKey[0]]->name;
                        }
                        $api_states_list = jobsearch_allocation_settings_handle::get_states($single_country_code);

                        if ($loc_optionstype == '2' || $loc_optionstype == '3') { ?>
                            <input type="hidden" id="countryId" value="<?php echo($single_country_code) ?>">
                        <?php } ?>
                        <select id="stateId">
                            <option value=""><?php esc_html_e('Select State', 'wp-jobsearch') ?></option>
                            <?php
                            if ($loc_optionstype == '2' || $loc_optionstype == '3') {
                                $states_cntry = $nameof_singl_contry;
                            } else {
                                $states_cntry = $saved_country;
                            }

                            if ($states_cntry != '') {
                                if (count($api_states_list) > 0) {
                                    foreach ($api_states_list as $api_state_key => $api_state_val) { ?>
                                        <option value="<?php echo($api_state_val->state_name) ?>" <?php echo($api_state_val->state_name == $saved_state ? 'selected="selected"' : '') ?>><?php echo($api_state_val->state_name) ?></option>
                                        <?php
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <?php } ?>

                    <?php
                    if ($loc_optionstype == '4') {
                        $api_cities_list = jobsearch_allocation_settings_handle::get_cities_by_state_ids();
                    } else {
                        $api_cities_list = jobsearch_allocation_settings_handle::get_cities($single_country_code, $saved_state);
                    }
                    if ($loc_optionstype == '1' || $loc_optionstype == '2' || $loc_optionstype == '4') { ?>
                        <div class="jobsearch-vcloc-dropdwn-con">
                            <label><?php esc_html_e('City', 'wp-jobsearch') ?></label>
                            <select id="cityId">
                                <option value=""><?php esc_html_e('Select City', 'wp-jobsearch') ?></option>
                                <?php
                                if ($loc_optionstype == '4') {
                                    foreach ($api_cities_list as $api_city_key => $api_city_val) { ?>
                                        <option value="<?php echo($api_city_val) ?>" <?php echo($api_city_val == $saved_city ? 'selected="selected"' : '') ?>
                                                data-cityid="<?php echo($api_city_key) ?>"><?php echo($api_city_val) ?></option>
                                        <?php
                                    }
                                } else if (isset($api_states_list) && !empty($api_states_list) && $saved_state != '') {
                                    foreach ($api_cities_list as $api_city_key => $api_city_val) { ?>
                                        <option value="<?php echo($api_city_val->city_name) ?>" <?php echo($api_city_val->city_name == $saved_city ? 'selected="selected"' : '') ?>
                                                data-cityid="<?php echo($api_city_key->city_name) ?>"><?php echo($api_city_val->city_name) ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <?php
                    }

                    $saved_value = '';
                    ?>
                    <input id="<?php echo esc_html($jobsearch_builder_key) ?>" type="hidden"
                           name="<?php echo esc_html($jobsearch_builder_key) ?>"
                           class="jobsearch-builder-form-select jobsearch-builder-input api_all_locs_<?php echo($rand_num) ?>" value="<?php echo($saved_value) ?>">
                    <?php
                    $jobsearch_builder_output .= ob_get_clean();
                    
                    $jobsearch_builder_output .= '</td>';

                    // append
                    $jobsearch_builder_html .= $jobsearch_builder_output;

                    break;

                case 'checkbox' :

                    // prepare
                    $jobsearch_builder_output = '<td><label class="screen-reader-text" for="' . $jobsearch_builder_key . '">' . $jobsearch_builder_param['label'] . '</label>';
                    $jobsearch_builder_output .= '<input type="checkbox" name="' . $jobsearch_builder_key . '" id="' . $jobsearch_builder_key . '" class="jobsearch-builder-form-checkbox jobsearch-builder-input"' . ( $jobsearch_builder_param['default'] ? 'checked' : '' ) . '>' . "\n";
                    $jobsearch_builder_output .= '<span class="jobsearch-builder-form-desc">' . $jobsearch_builder_param['desc'] . '</span></td>';

                    $jobsearch_builder_html .= $jobsearch_builder_output;

                    break;

                case 'multi_checkbox' :

                    // prepare
                    $multichk_rand_num = rand(10000000, 99999999);
                    $jobsearch_builder_output = '<td id="multichk-boxes-con' . $multichk_rand_num . '"><label class="screen-reader-text" for="' . $jobsearch_builder_key . '">' . $jobsearch_builder_param['label'] . '</label>';
                    foreach ($jobsearch_builder_param['options'] as $jobsearch_builder_value => $jobsearch_builder_option) {
                        $rand_num = rand(1000000, 9999999);
                        $jobsearch_builder_output .= '<div class="onechnbox-con"><input type="checkbox" data-id="multi_chekbox_' . $rand_num . '" data-val="' . $jobsearch_builder_value . '" class="jobsearch-builder-form-multi-checkbox jobsearch-builder-input">';
                        $jobsearch_builder_output .= '<label for="multi_chekbox_' . $rand_num . '">' . $jobsearch_builder_option . '</label></div>' . "\n";
                    }
                    $jobsearch_builder_output .= '<input type="hidden" name="' . $jobsearch_builder_key . '" id="' . $jobsearch_builder_key . '" value="">';
                    $jobsearch_builder_output .= '<span class="jobsearch-builder-form-desc">' . $jobsearch_builder_param['desc'] . '</span>';
                    
                    ob_start();
                    ?>
                    <script>
                        jQuery('#multichk-boxes-con<?php echo ($multichk_rand_num) ?>').on('click', 'input[type=checkbox]', function() {
                            var _this = jQuery(this);
                            var act_multi_input = jQuery('#<?php echo ($jobsearch_builder_key) ?>');
                            var to_apend_val = _this.attr('data-val');
                            var to_append_arr = [];
                            jQuery('#multichk-boxes-con<?php echo ($multichk_rand_num) ?> input[type=checkbox]').each(function(indexx, elmm) {
                                var _this_chkbox = jQuery(this);
                                if (_this_chkbox.is(":checked")) {
                                    to_append_arr.push(_this_chkbox.attr('data-val'));
                                }
                            });
                            if (to_append_arr.length > 0) {
                                to_apend_val = to_append_arr.join();
                            }
                            act_multi_input.val(to_apend_val);
                        });
                    </script>
                    <?php
                    $jobsearch_builder_output .= ob_get_clean();
                    $jobsearch_builder_output .= '</td>';

                    $jobsearch_builder_html .= $jobsearch_builder_output;

                    break;

                default :
                    break;
            }
            $jobsearch_builder_html .= '</tr>';

            return $jobsearch_builder_html;
        }

        /**
         * Popup window
         *
         * Print the footer code needed for the Insert Shortcode Popup
         *
         * @since 2.0
         * @global $pagenow
         * @return void Prints HTML
         */
        function jobsearch_builder_popup_html() {
            global $pagenow, $jobsearch_builder_shortcodes;
            include plugin_dir_path(dirname(__FILE__)) . 'shortcode-builder/shortcodes-config.php';
            $jobsearch_builder_shortcodes = apply_filters('jobsearch_shortcodes_builder_config_arr', $jobsearch_builder_shortcodes);
            //var_dump($pagenow);var_dump('sdsdsd12');
            // Only run in add/edit screens
            if (in_array($pagenow, array('post.php', 'page.php', 'post-new.php', 'post-edit.php'))) {
                ?>

                <script type="text/javascript">
                    function jobsearch_builder_InsertShortcode() {
                        // Grab input content, build the shortcodes, and insert them
                        // into the content editor
                        var select = jQuery('#select-jobsearch-builder-shortcode').val(),
                                type = select.replace('jobsearch-builder-', '').replace('-shortcode', ''),
                                template = jQuery('#' + select).data('shortcode-template'),
                                childTemplate = jQuery('#' + select).data('shortcode-child-template'),
                                tables = jQuery('#' + select).find('table').not('.jobsearch-builder-clone-template'),
                                attributes = '',
                                content = '',
                                contentToEditor = '';

                        // go over each table, build the shortcode content
                        for (var i = 0; i < tables.length; i++) {
                            var elems = jQuery(tables[i]).find('input:not(.jobsearch-builder-form-multi-checkbox), select, textarea');

                            // Build an attributes string by mapping over the input
                            // fields in a given table.
                            attributes = jQuery.map(elems, function (el, index) {
                                var $el = jQuery(el);
                                console.log(el);
                                if ($el.attr('id') === 'content') {
                                    if ($el.val() == null || $el.val() == '') {
                                        $el.val('');
                                    }
                                    content = $el.val();
                                    return '';
                                } else if ($el.attr('id') === 'last') {
                                    if ($el.is(':checked')) {
                                        return $el.attr('id') + '="true"';
                                    } else {
                                        return '';
                                    }
                                } else {
                                    if ($el.val() == null || $el.val() == '') {
                                        $el.val('');
                                    }
                                    return $el.attr('id') + '="' + $el.val() + '"';
                                }
                            });
                            attributes = attributes.join(' ').trim();
                            // Place the attributes and content within the provided
                            // shortcode template
                            if (childTemplate) {
                                // Run the replace on attributes for columns because the
                                // attributes are really the shortcodes
                                contentToEditor += childTemplate.replace('{{attributes}}', attributes).replace('{{attributes}}', attributes).replace('{{content}}', content);
                            } else {
                                // Run the replace on attributes for columns because the
                                // attributes are really the shortcodes
                                contentToEditor += template.replace('{{attributes}}', attributes).replace('{{attributes}}', attributes).replace('{{content}}', content);
                            }
                        }

                        // Insert built content into the parent template
                        if (childTemplate) {
                            contentToEditor = template.replace('{{child_shortcode}}', contentToEditor);
                        }

                        // Send the shortcode to the content editor and reset the fields
                        window.send_to_editor(contentToEditor);
                        //jobsearch_builder_ResetFields();
                    }

                    // Set the inputs to empty state
                    function jobsearch_builder_ResetFields() {
                        jQuery('#jobsearch-builder-shortcode-title').text('');
                        jQuery('#jobsearch-builder-shortcode-wrap').find('input[type=text], select').val('');
                        jQuery('#jobsearch-builder-shortcode-wrap').find('textarea').text('');
                        jQuery('.jobsearch-builder-was-cloned').remove();
                        jQuery('.jobsearch-builder-shortcode-type').hide();
                    }

                    // Function to redraw the thickbox for new content
                    function jobsearch_builder_ResizeTB() {
                        var ajaxCont = jQuery('#TB_ajaxContent'),
                                tbWindow = jQuery('#TB_window'),
                                ecPopup = jQuery('#jobsearch-builder-shortcode-wrap');

                        ajaxCont.css({
                            height: (tbWindow.outerHeight() - 47),
                            overflow: 'auto', // IMPORTANT
                            width: (tbWindow.outerWidth() - 30)
                        });
                    }

                    // Simple function to clone an included template
                    function jobsearch_builder_CloneContent(el) {
                        var clone = jQuery(el).find('.jobsearch-builder-clone-template').clone().removeClass('hidden jobsearch-builder-clone-template').removeAttr('id').addClass('jobsearch-builder-was-cloned');

                        jQuery(el).append(clone);
                    }

                    jQuery(document).ready(function ($) {
                        var $shortcodes = $('.jobsearch-builder-shortcode-type').hide(),
                                $title = $('#jobsearch-builder-shortcode-title');

                        // Show the selected shortcode input fields
                        $('#select-jobsearch-builder-shortcode').change(function () {
                            var text = $(this).find('option:selected').text();

                            $shortcodes.hide();
                            $title.text(text);
                            $('#' + $(this).val()).show();
                            jobsearch_builder_ResizeTB();
                        });

                        // Clone a set of input fields
                        $('.clone-content').on('click', function () {
                            var el = $(this).siblings('.jobsearch-builder-sortable');

                            jobsearch_builder_CloneContent(el);
                            jobsearch_builder_ResizeTB();
                            $('.jobsearch-builder-sortable').sortable('refresh');
                        });

                        // Remove a set of input fields
                        $('.jobsearch-builder-shortcode-type').on('click', '.jobsearch-builder-remove', function () {
                            $(this).closest('table').remove();
                        });

                        // Make content sortable using the jQuery UI Sortable method
                        $('.jobsearch-builder-sortable').sortable({
                            items: 'table:not(".hidden")',
                            placeholder: 'jobsearch-builder-sortable-placeholder'
                        });
                    });
                </script>

                <div id="jobsearch-builder-choose-shortcode" style="display: none;">
                    <div id="jobsearch-builder-shortcode-wrap" class="wrap jobsearch-builder-shortcode-wrap">
                        <div class="jobsearch-builder-shortcode-select">
                            <label for="jobsearch-builder-shortcode"><?php esc_html_e('Select the shortcode type', 'wp-jobsearch'); ?></label>
                            <select name="jobsearch-builder-shortcode" id="select-jobsearch-builder-shortcode">
                                <option><?php esc_html_e('Select Shortcode', 'wp-jobsearch'); ?></option>
                                <?php
                                foreach ($jobsearch_builder_shortcodes as $jobsearch_builder_shortcode) {
                                    echo '<option data-title="' . $jobsearch_builder_shortcode['title'] . '" value="' . $jobsearch_builder_shortcode['id'] . '">' . $jobsearch_builder_shortcode['title'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <h3 id="jobsearch-builder-shortcode-title"></h3>

                        <?php
                        $jobsearch_builder_html = '';
                        $jobsearch_builder_clone_button = array('show' => false);

                        // Loop through each shortcode building content
                        foreach ($jobsearch_builder_shortcodes as $jobsearch_builder_key => $jobsearch_builder_shortcode) {

                            // Add shortcode templates to be used when building with JS
                            $jobsearch_builder_shortcode_template = ' data-shortcode-template="' . $jobsearch_builder_shortcode['template'] . '"';
                            if (array_key_exists('child_shortcode', $jobsearch_builder_shortcode)) {
                                $jobsearch_builder_shortcode_template .= ' data-shortcode-child-template="' . $jobsearch_builder_shortcode['child_shortcode']['template'] . '"';
                            }

                            // Individual shortcode 'block'
                            $jobsearch_builder_html .= '<div id="' . $jobsearch_builder_shortcode['id'] . '" class="jobsearch-builder-shortcode-type" ' . $jobsearch_builder_shortcode_template . '>';

                            // If shortcode has children, it can be cloned and is sortable.
                            // Add a hidden clone template, and set clone button to be displayed.
                            if (array_key_exists('child_shortcode', $jobsearch_builder_shortcode)) {
                                $jobsearch_builder_html .= (isset($jobsearch_builder_shortcode['child_shortcode']['shortcode']) ? $jobsearch_builder_shortcode['child_shortcode']['shortcode'] : null);
                                $jobsearch_builder_shortcode['params'] = $jobsearch_builder_shortcode['child_shortcode']['params'];
                                $jobsearch_builder_clone_button['show'] = true;
                                $jobsearch_builder_clone_button['text'] = $jobsearch_builder_shortcode['child_shortcode']['clone_button'];
                                $jobsearch_builder_html .= '<div class="jobsearch-builder-sortable">';
                                $jobsearch_builder_html .= '<table id="clone-' . $jobsearch_builder_shortcode['id'] . '" class="hidden jobsearch-builder-clone-template"><tbody>';
                                foreach ($jobsearch_builder_shortcode['params'] as $jobsearch_builder_key => $jobsearch_builder_param) {
                                    $jobsearch_builder_html .= $this->jobsearch_builder_build_fields($jobsearch_builder_key, $jobsearch_builder_param);
                                }
                                if ($jobsearch_builder_clone_button['show']) {
                                    $jobsearch_builder_html .= '<tr><td colspan="2"><a href="#" class="jobsearch-builder-remove">' . esc_html__('Remove', 'wp-jobsearch') . '</a></td></tr>';
                                }
                                $jobsearch_builder_html .= '</tbody></table>';
                            }

                            // Build the actual shortcode input fields
                            $jobsearch_builder_html .= '<table><tbody>';
                            foreach ($jobsearch_builder_shortcode['params'] as $jobsearch_builder_key => $jobsearch_builder_param) {
                                $jobsearch_builder_html .= $this->jobsearch_builder_build_fields($jobsearch_builder_key, $jobsearch_builder_param);
                            }

                            // Add a link to remove a content block
                            if ($jobsearch_builder_clone_button['show']) {
                                $jobsearch_builder_html .= '<tr><td colspan="2"><a href="#" class="jobsearch-builder-remove">' . esc_html__('Remove', 'wp-jobsearch') . '</a></td></tr>';
                            }
                            $jobsearch_builder_html .= '</tbody></table>';

                            // Close out the sortable div and display the clone button as needed
                            if ($jobsearch_builder_clone_button['show']) {
                                $jobsearch_builder_html .= '</div>';
                                $jobsearch_builder_html .= '<a id="add-' . $jobsearch_builder_shortcode['id'] . '" href="#" class="button-secondary clone-content">' . $jobsearch_builder_clone_button['text'] . '</a>';
                                $jobsearch_builder_clone_button['show'] = false;
                            }

                            // Display notes if provided
                            if (array_key_exists('notes', $jobsearch_builder_shortcode)) {
                                $jobsearch_builder_html .= '<p class="jobsearch-builder-notes">' . $jobsearch_builder_shortcode['notes'] . '</p>';
                            }
                            $jobsearch_builder_html .= '</div>';
                        }

                        echo balanceTags($jobsearch_builder_html);
                        ?>

                        <p class="submit">
                            <input type="button" id="jobsearch-builder-insert-shortcode" class="button-primary" value="<?php esc_html_e('Insert Shortcode', 'wp-jobsearch'); ?>" onclick="jobsearch_builder_InsertShortcode();" />
                            <a href="#" id="jobsearch-builder-cancel-shortcode-insert" class="button-secondary jobsearch-builder-cancel-shortcode-insert" onclick="tb_remove();"><?php esc_html_e('Cancel', 'wp-jobsearch'); ?></a>
                        </p>
                    </div>
                </div>

                <?php
            }
        }

    }

    new jobsearch_builder_shortcode_insert();
}