<?php

namespace WP_JobsearchCandElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WP_Jobsearch\Candidate_Profile_Restriction;

if (!defined('ABSPATH'))
    exit;

/**
 * @since 1.1.0
 */
class SingleCandidateLanguages extends Widget_Base {

    /**
     * Retrieve the widget name.
     *
     * @since 1.1.0
     *
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'single-candidate-languages';
    }

    /**
     * Retrieve the widget title.
     *
     * @since 1.1.0
     *
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return __('Single Candidate Languages', 'wp-jobsearch');
    }

    /**
     * Retrieve the widget icon.
     *
     * @since 1.1.0
     *
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'fa fa-link';
    }

    /**
     * Retrieve the list of categories the widget belongs to.
     *
     * Used to determine where to display the widget in the editor.
     *
     * Note that currently Elementor supports only one category.
     * When multiple categories passed, Elementor uses the first one.
     *
     * @since 1.1.0
     *
     * @access public
     *
     * @return array Widget categories.
     */
    public function get_categories() {
        return ['jobsearch-cand-single'];
    }

    /**
     * Register the widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since 1.1.0
     *
     * @access protected
     */
    protected function register_controls() {
        
    }

    protected function render() {
        global $post, $jobsearch_plugin_options;
        $candidate_id = is_admin() ? jobsearch_candidate_id_elementor() : $post->ID;
        $cand_profile_restrict = new Candidate_Profile_Restriction;

        $inopt_resm_langs = isset($jobsearch_plugin_options['cand_resm_langs']) ? $jobsearch_plugin_options['cand_resm_langs'] : '';

        ob_start();
        
        ob_start();
        $exfield_list = get_post_meta($candidate_id, 'jobsearch_field_lang_title', true);
        $lang_percentagefield_list = get_post_meta($candidate_id, 'jobsearch_field_lang_percentage', true);
        $lang_level_list = get_post_meta($candidate_id, 'jobsearch_field_lang_level', true);
        if (is_array($exfield_list) && sizeof($exfield_list) > 0) {
            ?>
            <div class="jobsearch_progressbar_wrap jobsearch-candidate-langs">
                <div class="jobsearch-row">
                    <div class="jobsearch-column-12">
                        <div class="jobsearch-candidate-title">
                            <h2>
                                <i class="jobsearch-icon jobsearch-design-skills"></i> <?php esc_html_e('Languages', 'wp-jobsearch') ?>
                            </h2>
                        </div>
                    </div>
                    <?php
                    $exfield_counter = 0;
                    foreach ($exfield_list as $exfield) {
                        $rand_num = rand(1000000, 99999999);
                        $lang_percentagefield_val = isset($lang_percentagefield_list[$exfield_counter]) ? absint($lang_percentagefield_list[$exfield_counter]) : '';
                        $lang_percentagefield_val = $lang_percentagefield_val > 100 ? 100 : $lang_percentagefield_val;
                        $lang_level_val = isset($lang_level_list[$exfield_counter]) ? ($lang_level_list[$exfield_counter]) : '';

                        $exfield = jobsearch_esc_html($exfield);
                        $lang_level_val = jobsearch_esc_html($lang_level_val);
                        $lang_percentagefield_val = jobsearch_esc_html($lang_percentagefield_val);

                        $lang_level_str = esc_html__('Beginner', 'wp-jobsearch');
                        if ($lang_level_val == 'proficient') {
                            $lang_level_str = esc_html__('Proficient', 'wp-jobsearch');
                        } else if ($lang_level_val == 'intermediate') {
                            $lang_level_str = esc_html__('Intermediate', 'wp-jobsearch');
                        }
                        ?>
                        <div class="jobsearch-column-4">
                            <strong><?php echo($exfield) ?></strong>
                            <div class="jobsearch_progressbar1"
                                 data-width='<?php echo($lang_percentagefield_val) ?>'><?php echo($lang_level_str) ?></div>
                        </div>
                        <?php
                        $exfield_counter++;
                    }
                    ?>
                </div>
            </div>

            <?php
        }
        $languages_html = ob_get_clean();
        if ($inopt_resm_langs != 'off') {
            echo apply_filters('jobsearch_candidate_detail_languages_html', $languages_html, $candidate_id);
        }
        $html = ob_get_clean();
        echo $html;
    }

    protected function content_template() {
        
    }

}
