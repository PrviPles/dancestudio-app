<?php
/**
 * DanceStudio App Admin Menus
 */
if ( ! defined( 'WPINC' ) ) { die; }

add_action( 'admin_menu', 'dsa_add_admin_menu' );
if ( ! function_exists( 'dsa_add_admin_menu' ) ) {
    function dsa_add_admin_menu() {
        add_menu_page('DanceStudio App','DanceStudio App','edit_dsa_lessons','dsa-dashboard','dsa_render_settings_page_router','dashicons-groups',30);

        add_submenu_page('dsa-dashboard','Dashboard','Dashboard','edit_dsa_lessons','dsa-dashboard','dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard', __('Students', 'dancestudio-app'), __('Students', 'dancestudio-app'), 'edit_dsa_lessons', 'dsa-students-tab', 'dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard', __('Groups', 'dancestudio-app'), __('Groups', 'dancestudio-app'), 'edit_dsa_lessons', 'dsa-groups-tab', 'dsa_render_settings_page_router');
        
        // --- NEW: Add the Repertoire Tab ---
        add_submenu_page('dsa-dashboard', __('Repertoire', 'dancestudio-app'), __('Repertoire', 'dancestudio-app'), 'edit_dsa_lessons', 'dsa-repertoire-tab', 'dsa_render_settings_page_router');

        // --- REMOVED: Old Choreographies and Dance Figures tabs ---
        // add_submenu_page('dsa-dashboard', __('Choreographies', 'dancestudio-app'), __('Choreographies', 'dancestudio-app'), 'edit_dsa_lessons', 'dsa-choreographies-tab', 'dsa_render_settings_page_router');
        // add_submenu_page('dsa-dashboard','Dance Figures','Dance Figures','edit_dsa_lessons','dsa-dance-figures','dsa_render_settings_page_router');
        
        add_submenu_page('dsa-dashboard', __('Subscriptions', 'dancestudio-app'), __('Subscriptions', 'dancestudio-app'), 'manage_options', 'dsa-subscriptions-report', 'dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard','Couples','Couples','edit_dsa_lessons','dsa-couples-tab','dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard','Staff','Staff','manage_options','dsa-staff-tab','dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard','Calendar','Calendar','edit_dsa_lessons','dsa-calendar-tab','dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard','Attendance','Attendance','edit_dsa_lessons','dsa-attendance-tab','dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard', __('Statistics', 'dancestudio-app'), __('Statistics', 'dancestudio-app'), 'manage_options', 'dsa-statistics', 'dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard','Order Tracker','Order Tracker','manage_options','dsa-order-tracker','dsa_render_settings_page_router');
        add_submenu_page('dsa-dashboard','Settings','Settings','manage_options','dsa-settings-tab','dsa_render_settings_page_router');

        add_submenu_page('dsa-dashboard','','<span style="display:block; margin:1px 0 1px -5px; padding:0; height:1px; line-height:1px; background:#f0f0f1;"></span>','manage_options','#');

        add_submenu_page('dsa-dashboard','All Group Classes','All Group Classes','edit_dsa_lessons','dsa-all-classes','dsa_render_all_classes_page');
        add_submenu_page('dsa-dashboard','All Private Lessons','All Private Lessons','edit_dsa_lessons','dsa-private-lessons','dsa_render_private_lessons_page');
        add_submenu_page('dsa-dashboard', __('Difficulty Levels', 'dancestudio-app'), __('Difficulty Levels', 'dancestudio-app'), 'manage_options', 'edit-tags.php?taxonomy=dsa_difficulty_level');

    }
}