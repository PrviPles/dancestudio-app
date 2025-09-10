<?php
/**
 * DanceStudio App Admin Menus
 */
if ( ! defined( 'WPINC' ) ) { die; }

add_action( 'admin_menu', 'dsa_add_admin_menu' );
if ( ! function_exists( 'dsa_add_admin_menu' ) ) {
    function dsa_add_admin_menu() {
        add_menu_page('DanceStudio App','DanceStudio App','manage_options','dsa-dashboard','dsa_render_settings_page_router','dashicons-groups',30);
        
        // Main Dashboard Tabs & Links
        add_submenu_page('dsa-dashboard','Dashboard','Dashboard','manage_options','dsa-dashboard','dsa_render_settings_page_router');
        
        // --- CORRECTED: Changed capability from 'manage_options' back to 'edit_posts' ---
        add_submenu_page('dsa-dashboard', __('Students', 'dancestudio-app'), __('Students', 'dancestudio-app'), 'edit_posts', 'dsa-students-tab', 'dsa_render_settings_page_router');
        
        add_submenu_page('dsa-dashboard', __('Groups', 'dancestudio-app'), __('Groups', 'dancestudio-app'), 'edit_posts', 'dsa-groups', 'dsa_render_groups_page');
        add_submenu_page('dsa-dashboard', __('Statistics', 'dancestudio-app'), __('Statistics', 'dancestudio-app'), 'manage_options', 'dsa-statistics', 'dsa_render_statistics_page');
        add_submenu_page('dsa-dashboard','Income Report','Income Report','manage_options','dsa-income-report','dsa_render_income_report_page');
        add_submenu_page('dsa-dashboard','Couples','Couples','manage_options','dsa-couples-tab','dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard','Staff','Staff','manage_options','dsa-staff-tab','dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard','Calendar','Calendar','manage_options','dsa-calendar-tab','dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard','Attendance','Attendance','manage_options','dsa-attendance-tab','dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard','Order Tracker','Order Tracker','manage_options','dsa-order-tracker','dsa_render_order_tracker_page');
        add_submenu_page('dsa-dashboard','Settings','Settings','manage_options','dsa-settings-tab','dsa_render_settings_page_router');
        
        // Separator
        add_submenu_page('dsa-dashboard','','<span style="display:block; margin:1px 0 1px -5px; padding:0; height:1px; line-height:1px; background:#f0f0f1;"></span>','manage_options','#');
        
        // "All..." Links
        add_submenu_page('dsa-dashboard','All Students','All Students','manage_options','users.php');
        add_submenu_page('dsa-dashboard','All Private Lessons','All Private Lessons','edit_dsa_lessons','dsa-private-lessons','dsa_render_private_lessons_page');
        add_submenu_page('dsa-dashboard','All Group Classes','All Group Classes','manage_options','dsa-all-classes','dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard','All Dance Figures','All Dance Figures','manage_options','edit.php?post_type=dsa_dance_figure');
        add_submenu_page('dsa-dashboard', __('Holidays', 'dancestudio-app'), __('Holidays', 'dancestudio-app'), 'manage_options', 'edit.php?post_type=dsa_holiday');
    }
}