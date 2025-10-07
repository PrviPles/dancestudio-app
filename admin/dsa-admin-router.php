<?php
/**
 * DanceStudio App Admin Page Router
 */
if ( ! defined( 'WPINC' ) ) { die; }

if ( ! function_exists( 'dsa_render_settings_page_router' ) ) {
    function dsa_render_settings_page_router() {
        $current_page_slug = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'dsa-dashboard';

        if ( 'view_couple_details' === (isset($_GET['action']) ? $_GET['action'] : '') ) {
             if ( function_exists('dsa_render_single_couple_details_view') ) {
                dsa_render_single_couple_details_view( absint($_GET['user1_id']), absint($_GET['user2_id']) );
             }
             return;
        }

        $tabs = [
            'dsa-dashboard',
            'dsa-students-tab',
            'dsa-groups-tab',
            'dsa-repertoire-tab', // <-- ADDED
            'dsa-subscriptions-report',
            'dsa-couples-tab',
            'dsa-staff-tab',
            'dsa-calendar-tab',
            'dsa-attendance-tab',
            'dsa-statistics',
            'dsa-order-tracker',
            'dsa-settings-tab'
        ];

        if (in_array($current_page_slug, $tabs)) {
            $active_tab = str_replace(['dsa-', '-tab', '-report'], '', $current_page_slug);
            $active_tab = str_replace('-', '_', $active_tab);

            if (function_exists('dsa_render_main_settings_page_layout')) {
                dsa_render_main_settings_page_layout($active_tab);
            }
            return;
        }
    }
}