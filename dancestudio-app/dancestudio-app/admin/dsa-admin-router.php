<?php
/**
 * DanceStudio App Admin Page Router
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! function_exists( 'dsa_render_settings_page_router' ) ) {
    function dsa_render_settings_page_router() {
        $current_page_slug = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'dsa-dashboard';
        
        if ( 'view_couple_details' === (isset($_GET['action']) ? $_GET['action'] : '') ) {
             if ( function_exists('dsa_render_single_couple_details_view') ) {
                dsa_render_single_couple_details_view( absint($_GET['user1_id']), absint($_GET['user2_id']) );
             }
             return; 
        }
        
        switch ($current_page_slug) {
            case 'dsa-students-tab': if(function_exists('dsa_render_main_settings_page_layout')) dsa_render_main_settings_page_layout('students'); break;
            case 'dsa-groups': if(function_exists('dsa_render_groups_page')) dsa_render_groups_page(); break;
            case 'dsa-all-students': if(function_exists('dsa_render_all_students_page')) dsa_render_all_students_page(); break;
            case 'dsa-all-classes': if(function_exists('dsa_render_all_classes_page')) dsa_render_all_classes_page(); break;
            case 'dsa-order-tracker': if(function_exists('dsa_render_order_tracker_page')) dsa_render_order_tracker_page(); break;
            case 'dsa-dance-figures': if(function_exists('dsa_render_main_settings_page_layout')) dsa_render_main_settings_page_layout('dance_figures'); break;
            case 'dsa-couples-tab': if(function_exists('dsa_render_main_settings_page_layout')) dsa_render_main_settings_page_layout('couples'); break;
            case 'dsa-staff-tab': if(function_exists('dsa_render_main_settings_page_layout')) dsa_render_main_settings_page_layout('staff'); break;
            case 'dsa-calendar-tab': if(function_exists('dsa_render_main_settings_page_layout')) dsa_render_main_settings_page_layout('calendar'); break;
            case 'dsa-attendance-tab': if(function_exists('dsa_render_main_settings_page_layout')) dsa_render_main_settings_page_layout('attendance'); break;
            case 'dsa-settings-tab': if(function_exists('dsa_render_main_settings_page_layout')) dsa_render_main_settings_page_layout('settings'); break;
            case 'dsa-dashboard':
            default:
                if(function_exists('dsa_render_main_settings_page_layout')) dsa_render_main_settings_page_layout('dashboard'); 
                break;
        }
    }
}