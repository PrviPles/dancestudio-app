<?php
/**
 * Enqueues all public-facing scripts and styles.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action( 'wp_enqueue_scripts', 'dsa_enqueue_public_assets' );
function dsa_enqueue_public_assets() {
    global $post;

    if ( ! is_a( $post, 'WP_Post' ) ) {
        return;
    }

    // --- 1. Enqueue for the STUDENT Dashboard ---
    if ( has_shortcode( $post->post_content, 'dancestudio_dashboard' ) ) {
        wp_enqueue_style(
            'dsa-student-dashboard-styles',
            DSA_PLUGIN_URL . 'public/assets/css/student-dashboard-styles.css',
            [],
            DSA_PLUGIN_VERSION
        );
    }

    // --- 2. Enqueue for the MANAGER Dashboard ---
    if ( has_shortcode( $post->post_content, 'dsa_manager_dashboard' ) ) {
        // General styles and dependencies
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        wp_enqueue_style(
            'dsa-manager-dashboard-styles',
            DSA_PLUGIN_URL . 'public/assets/css/manager-dashboard-styles.css',
            [],
            DSA_PLUGIN_VERSION
        );
        wp_enqueue_style(
            'dsa-manager-students-styles',
            DSA_PLUGIN_URL . 'public/assets/css/manager-tab-students.css',
            [],
            DSA_PLUGIN_VERSION
        );

        // Script dependencies
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script( 'dsa-fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js', [], '6.1.11', true );

        // Get the active tab to conditionally load scripts
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';

        // Base data for localization
        $studio_settings = get_option('dsa_studio_settings', []);
        $week_start_day = isset($studio_settings['calendar_week_start']) ? absint($studio_settings['calendar_week_start']) : 1;

        $manager_data = [
            'ajax_url'             => admin_url( 'admin-ajax.php' ),
            'firstDay'             => $week_start_day,
            'events_nonce'         => wp_create_nonce('dsa_manager_get_events_nonce'),
            'create_student_nonce' => wp_create_nonce('dsa_create_student_ajax_nonce'),
            'manage_student_nonce' => wp_create_nonce('dsa_manage_student_nonce_action'),
            'groups_nonce'         => wp_create_nonce('dsa_manage_groups_nonce'),
        ];
        
        // Conditionally load JS and localize data
        if ( 'calendar' === $active_tab ) {
            wp_enqueue_script( 'dsa-manager-dashboard-js', DSA_PLUGIN_URL . 'public/assets/js/manager-dashboard.js', ['jquery', 'dsa-fullcalendar'], DSA_PLUGIN_VERSION, true );
            wp_localize_script('dsa-manager-dashboard-js', 'dsaManagerData', $manager_data);
        }
        if ( 'students' === $active_tab ) {
            wp_enqueue_script( 'dsa-manager-students-js', DSA_PLUGIN_URL . 'public/assets/js/manager-tab-students.js', ['jquery', 'jquery-ui-dialog'], DSA_PLUGIN_VERSION, true );
            wp_localize_script('dsa-manager-students-js', 'dsaManagerData', $manager_data);
        }
        if ( 'groups' === $active_tab ) {
            wp_enqueue_script( 'dsa-manager-groups-js', DSA_PLUGIN_URL . 'public/assets/js/manager-tab-groups.js', ['jquery', 'jquery-ui-dialog'], DSA_PLUGIN_VERSION, true );
            wp_localize_script('dsa-manager-groups-js', 'dsaManagerData', $manager_data);
        }
    }
}