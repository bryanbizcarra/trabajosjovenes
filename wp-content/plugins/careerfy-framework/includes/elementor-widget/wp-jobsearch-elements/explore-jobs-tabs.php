<?php

namespace CareerfyElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;

/**
 * Elementor tabs widget.
 *
 * Elementor widget that displays vertical or horizontal tabs with different
 * pieces of content.
 *
 * @since 1.0.0
 */
class Explore_Jobs_Tabs extends Widget_Base {

    /**
     * Get widget name.
     *
     * Retrieve tabs widget name.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'explore_jobs_tabs';
    }

    /**
     * Get widget title.
     *
     * Retrieve tabs widget title.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return __('Explore Jobs Tabs', 'careerfy-frame');
    }

    /**
     * Get widget icon.
     *
     * Retrieve tabs widget icon.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-tabs';
    }
    
    public function get_categories()
    {
        return ['wp-jobsearch'];
    }

    /**
     * Get widget keywords.
     *
     * Retrieve the list of keywords the widget belongs to.
     *
     * @since 2.1.0
     * @access public
     *
     * @return array Widget keywords.
     */
    public function get_keywords() {
        return ['tabs', 'accordion', 'toggle'];
    }

    /**
     * Register tabs widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since 3.1.0
     * @access protected
     */
    protected function register_controls() {
        
        $all_page = array(esc_html__("Select Page", "careerfy-frame") => '');
        $args = array(
            'sort_order' => 'asc',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        $pages = get_pages($args);
        if (!empty($pages)) {
            foreach ($pages as $page) {
                $all_page[$page->ID] = $page->post_title;
            }
        }

        $categories = get_terms(array(
            'taxonomy' => 'sector',
            'hide_empty' => false,
        ));

        $cate_array = array(esc_html__("Select Sector", "careerfy-frame") => '');
        if (is_array($categories) && sizeof($categories) > 0) {
            foreach ($categories as $category) {
                $cate_array[$category->slug] = $category->name;
            }
        }
        
        $this->start_controls_section(
                'section_tabs', [
            'label' => __('Explore Jobs Tabs', 'careerfy-frame'),
                ]
        );
        
        $this->add_control(
            'button_text',
            [
                'label' => __('Button Text', 'careerfy-frame'),
                'type' => Controls_Manager::TEXT,
            ]
        );
        $this->add_control(
            'button_url',
            [
                'label' => __('Button URL', 'careerfy-frame'),
                'type' => Controls_Manager::TEXT,
            ]
        );
        
        $this->add_control(
            'load_more',
            [
                'label' => __('Load More', 'careerfy-frame'),
                'type' => Controls_Manager::SELECT2,
                'default' => 'yes',
                'options' => [
                    'yes' => __('Yes', 'careerfy-frame'),
                    'no' => __('No', 'careerfy-frame'),
                ],
            ]
        );
        $this->add_control(
            'load_more_text',
            [
                'label' => __('Load More Text', 'careerfy-frame'),
                'type' => Controls_Manager::TEXT,

            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
                'tab_title', [
            'label' => __('Title', 'careerfy-frame'),
            'type' => Controls_Manager::TEXT,
            'default' => __('Tab Title', 'careerfy-frame'),
            'placeholder' => __('Tab Title', 'careerfy-frame'),
            'label_block' => true,
            'dynamic' => [
                'active' => true,
            ],
                ]
        );

        $repeater->add_control(
            'jobs_by',
            [
                'label' => __('Jobs by', 'careerfy-frame'),
                'type' => Controls_Manager::SELECT2,
                'default' => 'jobtype',
                'options' => [
                    'jobtype' => __('Job Type', 'careerfy-frame'),
                    'skill' => __('Skills', 'careerfy-frame'),
                    'sector' => __('Category', 'careerfy-frame'),
                    'employer' => __('Top Companies', 'careerfy-frame'),
                ],
            ]
        );
        $repeater->add_control(
            'result_page',
            [
                'label' => __('Result Page', 'careerfy-frame'),
                'type' => Controls_Manager::SELECT2,
                'default' => '',
                'options' => $all_page,
            ]
        );
        $repeater->add_control(
            'employer_cat',
            [
                'label' => __('Jobs by', 'careerfy-frame'),
                'type' => Controls_Manager::SELECT2,
                'default' => '',
                'description' => __("Select Sector.", "careerfy-frame"),
                'options' => $cate_array,
                'condition' => [
                    'jobs_by' => 'employer'
                ]
            ]
        );
        
        $repeater->add_control(
            'jobs_numbers', [
                'label' => __('Number of jobs', 'careerfy-frame'),
                'type' => Controls_Manager::TEXT,
                'default' => '10',
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'job_order',
            [
                'label' => __('Order', 'careerfy-frame'),
                'type' => Controls_Manager::SELECT2,
                'default' => 'DESC',
                'options' => [
                    'DESC' => __('Descending', 'careerfy-frame'),
                    'ASC' => __('Ascending', 'careerfy-frame'),
                ],
            ]
        );

        $this->add_control(
                'tabs', [
            'label' => __('Tabs Items', 'careerfy-frame'),
            'type' => Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => '{{{ tab_title }}}',
                ]
        );

        $this->add_control(
                'view', [
            'label' => __('View', 'careerfy-frame'),
            'type' => Controls_Manager::HIDDEN,
            'default' => 'traditional',
                ]
        );

        $this->add_control(
                'type', [
            'label' => __('Position', 'careerfy-frame'),
            'type' => Controls_Manager::SELECT,
            'default' => 'horizontal',
            'options' => [
                'horizontal' => __('Horizontal', 'careerfy-frame'),
                'vertical' => __('Vertical', 'careerfy-frame'),
            ],
            'prefix_class' => 'elementor-tabs-view-',
            'separator' => 'before',
                ]
        );

        $this->add_control(
                'tabs_align_horizontal', [
            'label' => __('Alignment', 'careerfy-frame'),
            'type' => Controls_Manager::CHOOSE,
            'options' => [
                '' => [
                    'title' => __('Start', 'careerfy-frame'),
                    'icon' => 'eicon-h-align-left',
                ],
                'center' => [
                    'title' => __('Center', 'careerfy-frame'),
                    'icon' => 'eicon-h-align-center',
                ],
                'end' => [
                    'title' => __('End', 'careerfy-frame'),
                    'icon' => 'eicon-h-align-right',
                ],
                'stretch' => [
                    'title' => __('Justified', 'careerfy-frame'),
                    'icon' => 'eicon-h-align-stretch',
                ],
            ],
            'prefix_class' => 'elementor-tabs-alignment-',
            'condition' => [
                'type' => 'horizontal',
            ],
                ]
        );

        $this->add_control(
                'tabs_align_vertical', [
            'label' => __('Alignment', 'careerfy-frame'),
            'type' => Controls_Manager::CHOOSE,
            'options' => [
                '' => [
                    'title' => __('Start', 'careerfy-frame'),
                    'icon' => 'eicon-v-align-top',
                ],
                'center' => [
                    'title' => __('Center', 'careerfy-frame'),
                    'icon' => 'eicon-v-align-middle',
                ],
                'end' => [
                    'title' => __('End', 'careerfy-frame'),
                    'icon' => 'eicon-v-align-bottom',
                ],
                'stretch' => [
                    'title' => __('Justified', 'careerfy-frame'),
                    'icon' => 'eicon-v-align-stretch',
                ],
            ],
            'prefix_class' => 'elementor-tabs-alignment-',
            'condition' => [
                'type' => 'vertical',
            ],
                ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
                'section_tabs_style', [
            'label' => __('Tabs', 'careerfy-frame'),
            'tab' => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_control(
                'navigation_width', [
            'label' => __('Navigation Width', 'careerfy-frame'),
            'type' => Controls_Manager::SLIDER,
            'default' => [
                'unit' => '%',
            ],
            'range' => [
                '%' => [
                    'min' => 10,
                    'max' => 50,
                ],
            ],
            'selectors' => [
                '{{WRAPPER}} .elementor-tabs-wrapper' => 'width: {{SIZE}}{{UNIT}}',
            ],
            'condition' => [
                'type' => 'vertical',
            ],
                ]
        );

        $this->add_control(
                'border_width', [
            'label' => __('Border Width', 'careerfy-frame'),
            'type' => Controls_Manager::SLIDER,
            'default' => [
                'size' => 1,
            ],
            'range' => [
                'px' => [
                    'min' => 0,
                    'max' => 10,
                ],
            ],
            'selectors' => [
                '{{WRAPPER}} .elementor-tab-title, {{WRAPPER}} .elementor-tab-title:before, {{WRAPPER}} .elementor-tab-title:after, {{WRAPPER}} .elementor-tab-content, {{WRAPPER}} .elementor-tabs-content-wrapper' => 'border-width: {{SIZE}}{{UNIT}};',
            ],
                ]
        );

        $this->add_control(
                'border_color', [
            'label' => __('Border Color', 'careerfy-frame'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .elementor-tab-mobile-title, {{WRAPPER}} .elementor-tab-desktop-title.elementor-active, {{WRAPPER}} .elementor-tab-title:before, {{WRAPPER}} .elementor-tab-title:after, {{WRAPPER}} .elementor-tab-content, {{WRAPPER}} .elementor-tabs-content-wrapper' => 'border-color: {{VALUE}};',
            ],
                ]
        );

        $this->add_control(
                'background_color', [
            'label' => __('Background Color', 'careerfy-frame'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .elementor-tab-desktop-title.elementor-active' => 'background-color: {{VALUE}};',
                '{{WRAPPER}} .elementor-tabs-content-wrapper' => 'background-color: {{VALUE}};',
            ],
                ]
        );

        $this->add_control(
                'heading_title', [
            'label' => __('Title', 'careerfy-frame'),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
                ]
        );

        $this->add_control(
                'tab_color', [
            'label' => __('Color', 'careerfy-frame'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .elementor-tab-title, {{WRAPPER}} .elementor-tab-title a' => 'color: {{VALUE}}',
            ],
            'global' => [
                'default' => Global_Colors::COLOR_PRIMARY,
            ],
                ]
        );

        $this->add_control(
                'tab_active_color', [
            'label' => __('Active Color', 'careerfy-frame'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .elementor-tab-title.elementor-active,
					 {{WRAPPER}} .elementor-tab-title.elementor-active a' => 'color: {{VALUE}}',
            ],
            'global' => [
                'default' => Global_Colors::COLOR_ACCENT,
            ],
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(), [
            'name' => 'tab_typography',
            'selector' => '{{WRAPPER}} .elementor-tab-title',
            'global' => [
                'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
            ],
                ]
        );

        $this->add_group_control(
                Group_Control_Text_Shadow::get_type(), [
            'name' => 'title_shadow',
            'selector' => '{{WRAPPER}} .elementor-tab-title',
                ]
        );

        $this->add_control(
                'title_align', [
            'label' => __('Alignment', 'careerfy-frame'),
            'type' => Controls_Manager::CHOOSE,
            'options' => [
                'left' => [
                    'title' => __('Left', 'careerfy-frame'),
                    'icon' => 'eicon-text-align-left',
                ],
                'center' => [
                    'title' => __('Center', 'careerfy-frame'),
                    'icon' => 'eicon-text-align-center',
                ],
                'right' => [
                    'title' => __('Right', 'careerfy-frame'),
                    'icon' => 'eicon-text-align-right',
                ],
            ],
            'selectors' => [
                '{{WRAPPER}} .elementor-tab-title' => 'text-align: {{VALUE}};',
            ],
            'condition' => [
                'tabs_align' => 'stretch',
            ],
                ]
        );

        $this->end_controls_section();
    }
    
    public static function load_more_explore_jobs($jobs_by_detail) {
        global $jobsearch_plugin_options, $result_page, $jobs_by;
        $job_slug;
        if ($jobs_by == 'jobtype') {
            $job_slug = 'job_type';
        } else if ($jobs_by == 'skill') {
            $job_slug = 'job_skills';
        } else if ($jobs_by == 'sector') {
            $job_slug = 'sector_cat';
        }
        $to_result_page = $result_page;
        $joptions_search_page = isset($jobsearch_plugin_options['jobsearch_search_list_page']) ? $jobsearch_plugin_options['jobsearch_search_list_page'] : '';
        if ($joptions_search_page != '') {
            $joptions_search_page = careerfy__get_post_id($joptions_search_page, 'page');
        }
        if ($result_page <= 0 && $joptions_search_page > 0) {
            $to_result_page = $joptions_search_page;
        }
        $to_result_page = absint($to_result_page);


        foreach ($jobs_by_detail as $data) {
            $cat_goto_link = add_query_arg(array($job_slug => $data->slug), get_permalink($to_result_page));
            $cat_goto_link = apply_filters('jobsearch_job_sector_cat_result_link', $cat_goto_link, $data->slug);
            ?>
            <li><a href="<?php echo($cat_goto_link) ?>"><?php echo $data->name ?></a></li>
            <?php
        }
    }

    public static function load_more_top_companies($employers_posts) {

        foreach ($employers_posts as $data) {
            ?>
            <li><a href="<?php echo get_permalink($data->ID) ?>"><?php echo $data->post_title ?></a></li>
            <?php
        }
    }
    
    private function careerfy_explore_jobs_item($explore_jobs_atts) {
        global $load_more, $load_more_text, $result_page, $jobs_by;

        $jobs_numbers = $explore_jobs_atts['jobs_numbers'];
        $job_order = $explore_jobs_atts['job_order'];
        $employer_cat = $explore_jobs_atts['employer_cat'];
        $result_page = $explore_jobs_atts['result_page'];
        $jobs_by = $explore_jobs_atts['jobs_by'];
        $employers_posts = '';
        $totl_explore_jobs = '';
        $total_jobs = '';
        if ($jobs_by != 'employer') {

            $jobs_detail = get_terms(array(
                'taxonomy' => $jobs_by,
                'hide_empty' => false,
            ));
            $total_jobs = count($jobs_detail);

            $jobs_by_detail = get_terms(array(
                'taxonomy' => $jobs_by,
                'hide_empty' => false,
                'number' => $jobs_numbers,
                'offset' => 0,
                'order' => $job_order,
            ));
            $totl_explore_jobs = count($jobs_by_detail);

        } else {

            $element_filter_arr = array();
            $element_filter_arr[] = array(
                'key' => 'jobsearch_field_employer_approved',
                'value' => 'on',
                'compare' => '=',
            );

            $args = array(
                'posts_per_page' => $jobs_numbers,
                'post_type' => 'employer',
                'post_status' => 'publish',
                'order' => $job_order,
                'orderby' => 'meta_value_num',
                'meta_key' => 'jobsearch_field_employer_job_count',
                'meta_query' => array(
                    $element_filter_arr,
                ),
            );
            if ($employer_cat != '') {
                $args['tax_query'][] = array(
                    'taxonomy' => 'sector',
                    'field' => 'slug',
                    'terms' => $employer_cat
                );
            }

            $employers_query = new \WP_Query($args);
            $totl_found_jobs = $employers_query->found_posts;
            $employers_posts = $employers_query->posts;
        }

        $load_more_text = $load_more_text != "" ? $load_more_text : esc_html__('More Jobs', 'careerfy-frame');
        $rand_num = rand(10000000, 99909999);
        ?>

        <div class="tab-explore-jobs-links">

            <ul id="main-list-<?php echo($rand_num) ?>">
                <?php
                if ($jobs_by != 'employer') {
                    if ($totl_explore_jobs > 0) { ?>
                        <?php self::load_more_explore_jobs($jobs_by_detail); ?>
                        <?php if ($total_jobs >= $jobs_numbers && $load_more == 'yes') { ?>
                            <li class="morejobs-link"><a
                                        href="javascript:void(0)" class="load-more-<?php echo $rand_num ?>"
                                        data-tpages="<?php echo $totl_explore_jobs ?>"
                                        list-style=""
                                        data-totalJobs="<?php echo $total_jobs ?>"><?php echo ($load_more_text) ?></a>
                            </li>
                        <?php }
                    } else { ?>
                        <p><?php echo esc_html__("No record found", "careerfy-frame") ?></p>
                    <?php }

                } else {
                    //$more_text = esc_html__('More Companies', 'careerfy-frame');

                    if ($totl_found_jobs > 0) {

                        self::load_more_top_companies($employers_posts);
                        if ($totl_found_jobs >= $jobs_numbers && $load_more == 'yes') { ?>
                            <li class="morejobs-link"><a
                                        href="javascript:void(0)"
                                        class="load-more-companies-<?php echo $rand_num ?>"
                                        data-tpages="<?php echo $totl_found_jobs ?>"
                                        list-item-color=""
                                        jobs-results="<?php echo $jobs_numbers ?>"
                                        data-gtopage="2"><?php echo ($load_more_text) ?></a>
                            </li>
                        <?php }

                    } else { ?>

                        <p><?php echo esc_html__("No record found", "careerfy-frame") ?></p>

                    <?php }
                } ?>

            </ul>
        </div>
        <!-- Services Links -->
        <?php if ($jobs_by != 'employer') { ?>
            <script>
                jQuery(document).on('click', '.load-more-<?php echo($rand_num) ?>', function (e) {
                    e.preventDefault();
                    var _this = jQuery(this),
                        total_jobs_list = _this.attr('data-totalJobs'),
                        page_num = _this.attr('data-tpages'),
                        list_items_color = _this.attr('list-item-color'),
                        this_html = _this.html(),
                        appender_con = jQuery('#main-list-<?php echo($rand_num) ?> li:last'),
                        ajax_url = '<?php echo admin_url('admin-ajax.php') ?>';
                    if (!_this.hasClass('ajax-loadin')) {
                        _this.addClass('ajax-loadin');
                        _this.html(this_html + ' <i class="fa fa-refresh fa-spin"></i>');
                        page_num = parseInt(page_num);

                        var request = jQuery.ajax({
                            url: ajax_url,
                            method: "POST",
                            data: {
                                page_num: page_num,
                                list_items_color: list_items_color,
                                jobs_by: '<?php echo($jobs_by) ?>',
                                jobs_numbers: '<?php echo($jobs_numbers) ?>',
                                action: 'jobsearch_load_more_list'
                            },
                            dataType: "json"
                        });
                        request.done(function (response) {

                            if ('undefined' !== typeof response.html) {
                                page_num += <?php echo $jobs_numbers ?>;
                                _this.attr('data-tpages', page_num);
                                if (page_num >= total_jobs_list) {
                                    _this.parent('li').hide();
                                }
                                appender_con.before(response.html);
                            }
                            _this.html(this_html);
                            _this.removeClass('ajax-loadin');
                        });

                        request.fail(function (jqXHR, textStatus) {
                            _this.html(this_html);
                            _this.removeClass('ajax-loadin');
                        });
                    }
                    return false;
                })

            </script>
        <?php } else { ?>
            <script>
                jQuery(document).on('click', '.load-more-companies-<?php echo($rand_num) ?>', function (e) {
                    e.preventDefault();
                    var _this = jQuery(this),
                        total_pages = _this.attr('data-tpages'),
                        jobs_results = _this.attr('jobs-results'),
                        page_num = _this.attr('data-gtopage'),
                        list_items_color = _this.attr('list-item-color'),
                        this_html = _this.html(),
                        appender_con = jQuery('#main-list-<?php echo($rand_num) ?> li:last'),
                        ajax_url = '<?php echo admin_url('admin-ajax.php') ?>';

                    if (!_this.hasClass('ajax-loadin')) {
                        _this.addClass('ajax-loadin');
                        _this.html(this_html + ' <i class="fa fa-refresh fa-spin"></i>');
                        total_pages = parseInt(total_pages);
                        page_num = parseInt(page_num);
                        jobs_results = parseInt(jobs_results);
                        var request = jQuery.ajax({
                            url: ajax_url,
                            method: "POST",
                            data: {
                                page_num: page_num,
                                employer_cat: '<?php echo($employer_cat) ?>',
                                employer_order: '<?php echo($job_order) ?>',
                                jobs_numbers: '<?php echo($jobs_numbers) ?>',
                                list_view: '',
                                list_items_color: list_items_color,
                                action: 'jobsearch_load_more_top_companies_list'
                            },
                            dataType: "json"
                        });
                        request.done(function (response) {
                            if ('undefined' !== typeof response.html) {
                                page_num += <?php echo $jobs_numbers ?>;
                                jobs_results += <?php echo $jobs_numbers ?>;
                                _this.attr('data-gtopage', page_num);
                                _this.attr('jobs-results', jobs_results);
                                if (jobs_results >= total_pages) {
                                    _this.parent('li').hide();
                                }
                                appender_con.before(response.html);
                            }
                            _this.html(this_html);
                            _this.removeClass('ajax-loadin');
                        });
                        request.fail(function (jqXHR, textStatus) {
                            _this.html(this_html);
                            _this.removeClass('ajax-loadin');
                        });
                    }
                    return false;

                });
            </script>
            <?php
        }

    }

    /**
     * Render tabs widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function render() {
        global $load_more, $load_more_text;
        
        $atts = $this->get_settings_for_display();
        
        $button_text = $atts['button_text'];
        $button_url = $atts['button_url'];
        $load_more = $atts['load_more'];
        $load_more_text = $atts['load_more_text'];
        
        $tabs = $this->get_settings_for_display('tabs');

        $id_int = substr($this->get_id_int(), 0, 3);

        $this->add_render_attribute('elementor-tabs', 'class', 'elementor-tabs');
        ?>
        <div <?php echo $this->get_render_attribute_string('elementor-tabs'); ?>>
            <div class="elementor-tabs-wrapper" role="tablist" >
                <?php
                foreach ($tabs as $index => $item) :
                    $tab_count = $index + 1;
                    $tab_title_setting_key = $this->get_repeater_setting_key('tab_title', 'tabs', $index);
                    $tab_title = '<a href="">' . $item['tab_title'] . '</a>';

                    $title_classes = ['elementor-tab-title', 'explorejob-tab-title', 'elementor-tab-desktop-title'];
                    if ($tab_count == 1) {
                        $title_classes[] = 'elementor-active';
                    }
                    $this->add_render_attribute($tab_title_setting_key, [
                        'id' => 'elementor-tab-title-' . $id_int . $tab_count,
                        'class' => $title_classes,
                        'aria-selected' => 1 === $tab_count ? 'true' : 'false',
                        'data-tab' => $tab_count,
                        'data-id' => $id_int . $tab_count,
                        'role' => 'tab',
                        'tabindex' => 1 === $tab_count ? '0' : '-1',
                        'aria-controls' => 'elementor-tab-content-' . $id_int . $tab_count,
                        'aria-expanded' => 1 === $tab_count ? 'true' : 'false',
                    ]);
                    ?>
                    <div <?php echo $this->get_render_attribute_string($tab_title_setting_key); ?>><?php echo $tab_title; ?> <i class="fa fa-chevron-right"></i></div>
                <?php endforeach; ?>
            </div>
            <div class="elementor-tabs-content-wrapper" role="tablist" aria-orientation="vertical">
                <?php
                foreach ($tabs as $index => $item) :
                    $tab_count = $index + 1;

                    $tab_content_setting_key = $this->get_repeater_setting_key('tab_content', 'tabs', $index);

                    $tab_title_mobile_setting_key = $this->get_repeater_setting_key('tab_title_mobile', 'tabs', $tab_count);

                    $title_classes = ['elementor-tab-content', 'elementor-clearfix'];
                    if ($tab_count == 1) {
                        $title_classes[] = 'elementor-active';
                    }
                    
                    $cont_attrs = [
                        'id' => 'elementor-tab-content-' . $id_int . $tab_count,
                        'class' => $title_classes,
                        'data-tab' => $tab_count,
                        'role' => 'tabpanel',
                        'aria-labelledby' => 'elementor-tab-title-' . $id_int . $tab_count,
                        'tabindex' => '0',
                    ];
                    if ($tab_count > 1) {
                        $cont_attrs['hidden'] = 'hidden';
                    } else {
                        $cont_attrs['style'] = 'display:block;';
                    }
                    $this->add_render_attribute($tab_content_setting_key, $cont_attrs);

                    //
                    $content_classes = ['elementor-tab-title', 'explorejobm-tab-title', 'elementor-tab-mobile-title'];
                    if ($tab_count == 1) {
                        $content_classes[] = 'elementor-active';
                    }
                    $title_attrs = [
                        'id' => 'explore-titlcontnt-' . $id_int . $tab_count,
                        'class' => $content_classes,
                        'aria-selected' => 1 === $tab_count ? 'true' : 'false',
                        'data-tab' => $tab_count,
                        'data-id' => $id_int . $tab_count,
                        'role' => 'tab',
                        'tabindex' => 1 === $tab_count ? '0' : '-1',
                        'aria-controls' => 'elementor-tab-content-' . $id_int . $tab_count,
                        'aria-expanded' => 'false',
                    ];
                    $this->add_render_attribute($tab_title_mobile_setting_key, $title_attrs);

                    $this->add_inline_editing_attributes($tab_content_setting_key, 'advanced');
                    ?>

                    <div <?php echo $this->get_render_attribute_string($tab_title_mobile_setting_key); ?>><?php echo $item['tab_title']; ?> <i class="fa fa-chevron-right"></i></div>
                    <div <?php echo $this->get_render_attribute_string($tab_content_setting_key); ?>>
                        <div class="tabtitle-desktop"><strong><?php echo $item['tab_title']; ?></strong></div>
                        <?php
                        $this->careerfy_explore_jobs_item($item);

                        if ($button_text != "") {
                            ?>
                            <div class="careerfy-tabs-browse-btn"> <a href="<?php echo ($button_url) ?>"> <?php echo ($button_text) ?></a> </div>
                            <?php
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        add_action('wp_footer', function() {
            ?>
            <script>
                var exptabs_element_wraper = document.querySelector('.elementor-widget-explore_jobs_tabs');
                exptabs_element_wraper.classList.add('elementor-widget-tabs');
                jQuery('.explorejob-tab-title').on('click', function(ev) {
                    ev.preventDefault();
                    var _this = jQuery(this);
                    var this_id = _this.attr('data-id');
                    var this_parent = _this.parents('.elementor-tabs-wrapper');
                    
                    var content_parent = _this.parents('.elementor-tabs').find('.elementor-tabs-content-wrapper');
                    
                    this_parent.find('.explorejob-tab-title').removeClass('elementor-active').attr('aria-selected', false).attr('aria-expanded', false);
                    _this.addClass('elementor-active').attr('aria-selected', true).attr('aria-expanded', true);
                    
                    content_parent.find('.elementor-tab-title,.elementor-tab-content').removeClass('elementor-active');
                    content_parent.find('#explore-titlcontnt-' + this_id + ',#elementor-tab-content-' + this_id).addClass('elementor-active');
                    
                    //
                    content_parent.find('.elementor-tab-content').attr('hidden', 'hidden').hide();
                    content_parent.find('#elementor-tab-content-' + this_id).removeAttr('hidden').show();
                });
                jQuery('.explorejobm-tab-title').on('click', function(ev) {
                    ev.preventDefault();
                    var _this = jQuery(this);
                    var this_id = _this.attr('data-id');
                    
                    var content_parent = _this.parents('.elementor-tabs').find('.elementor-tabs-content-wrapper');
                    
                    content_parent.find('.explorejobm-tab-title').removeClass('elementor-active').attr('aria-selected', false).attr('aria-expanded', false);
                    _this.addClass('elementor-active').attr('aria-selected', true).attr('aria-expanded', true);
                    
                    content_parent.find('.elementor-tab-content').removeClass('elementor-active');
                    content_parent.find('#elementor-tab-content-' + this_id).addClass('elementor-active');
                    
                    //
                    content_parent.find('.elementor-tab-content').attr('hidden', 'hidden').hide();
                    content_parent.find('#elementor-tab-content-' + this_id).removeAttr('hidden').slideDown();
                });
            </script>
            <?php
        }, 30);
    }

    /**
     * Render tabs widget output in the editor.
     *
     * Written as a Backbone JavaScript template and used to generate the live preview.
     *
     * @since 2.9.0
     * @access protected
     */
    protected function content_template() {
        
    }

}
