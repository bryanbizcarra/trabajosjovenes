<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
//
function jobsearch_chat_files_upload_dir($dir = '')
{
    $cus_dir = 'jobsearch-chat-share-files';
    $dir_path = array(
        'path' => $dir['basedir'] . '/' . $cus_dir,
        'url' => $dir['baseurl'] . '/' . $cus_dir,
        'subdir' => $cus_dir,
    );
    return $dir_path + $dir;
}

add_filter('jobsearch_cand_restrict_basic_profile_fields', 'add_in_basic_profile_fields');

function add_in_basic_profile_fields($fields) {
    
    $fields['chat'] = esc_html__('Start Chat', 'jobsearch-ajchat');
    
    return $fields;
}

add_filter('jobsearch_cand_restrict_prfil_fields_arr', 'add_in_prfil_fields_arr');

function add_in_prfil_fields_arr($fields) {
    
    $fields[] = 'profile_fields|chat';
    
    return $fields;
}