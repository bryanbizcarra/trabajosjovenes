<?php

$jobsearch_builder_shortcodes['jobsearch_apply_job_form'] = array(
    'title' => esc_html__('Apply Job Form', 'wp-jobsearch'),
    'id' => 'jobsearch-apply-job-form-shortcode',
    'template' => '[jobsearch_apply_job_form {{attributes}}] {{content}} [/jobsearch_apply_job_form]',
    'params' => array(
        'title' => array(
            'std' => '',
            'type' => 'text',
            'label' => esc_html__('Title', 'wp-jobsearch'),
            'desc' => '',
        ),
    )
);
