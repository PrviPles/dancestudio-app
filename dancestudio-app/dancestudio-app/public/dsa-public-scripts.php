<?php
// ...
function dsa_enqueue_public_assets() {
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'dsa_manager_dashboard' ) ) {
        // ... (General styles and jQuery UI are enqueued)

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';

        // ... (Conditional loading for calendar and students tabs is unchanged)

        // NEW: Load group management assets ONLY on the groups tab
        if ( 'groups' === $active_tab ) {
            wp_enqueue_script( 'dsa-manager-groups-js', DSA_PLUGIN_URL . 'public/assets/js/manager-tab-groups.js', ['jquery', 'jquery-ui-dialog'], DSA_PLUGIN_VERSION, true );
        }

        // UPDATED: Add the new groups nonce to the localized data
        $manager_data = [
            'ajax_url'             => admin_url( 'admin-ajax.php' ),
            'firstDay'             => $week_start_day,
            'events_nonce'         => wp_create_nonce('dsa_manager_get_events_nonce'),
            'create_student_nonce' => wp_create_nonce('dsa_create_student_ajax_nonce'),
            'manage_student_nonce' => wp_create_nonce('dsa_manage_student_nonce_action'),
            'groups_nonce'         => wp_create_nonce('dsa_manage_groups_nonce'), // New nonce
        ];
        
        // Pass data to the correct script handle
        if ( 'calendar' === $active_tab ) { wp_localize_script('dsa-manager-dashboard-js', 'dsaManagerData', $manager_data); }
        if ( 'students' === $active_tab ) { wp_localize_script('dsa-manager-students-js', 'dsaManagerData', $manager_data); }
        if ( 'groups' === $active_tab ) { wp_localize_script('dsa-manager-groups-js', 'dsaManagerData', $manager_data); }
    }
}