<?php
/**
 * DanceStudio App Admin Scripts Enqueuing
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

// Main hook that calls our new router function
add_action( 'admin_enqueue_scripts', 'dsa_enqueue_dsa_admin_assets' );

/**
 * Main router for enqueuing admin assets.
 * It checks the current page and calls the appropriate helper function.
 */
function dsa_enqueue_dsa_admin_assets( $hook_suffix ) {
    
    global $post;
    $post_type = $post ? get_post_type( $post ) : '';

    // Enqueue responsive styles on all admin pages
    wp_enqueue_style( 'dsa-admin-responsive-css', DSA_PLUGIN_URL . 'admin/assets/css/dsa-admin-responsive.css', [], DSA_PLUGIN_VERSION );

    // Call helper for the Calendar Tab
    if ( strpos( $hook_suffix, 'dsa-calendar-tab' ) !== false ) {
        _dsa_enqueue_calendar_assets();
    }
    
    // Call helper for Choreographies Tab
    if ( strpos( $hook_suffix, 'dsa-choreographies-tab' ) !== false ) {
        _dsa_enqueue_choreographies_assets();
    }

    // Call helper for Couples Tab
    if ( strpos( $hook_suffix, 'dsa-couples-tab' ) !== false ) {
        _dsa_enqueue_couples_assets();
    }
    
    // Call helper for User Profile pages
    if ( in_array( $hook_suffix, ['profile.php', 'user-edit.php'] ) ) {
        _dsa_enqueue_user_profile_assets();
    }
    
    // Call helper for the Students Tab
    if ( strpos($hook_suffix, 'dsa-students-tab') !== false ) {
        _dsa_enqueue_students_assets($hook_suffix);
    }

    // Call helper for CPT editor screens
    if ( in_array( $hook_suffix, ['post.php', 'post-new.php'] ) ) {
        if ( 'dsa_group' === $post_type ) {
            _dsa_enqueue_group_editor_assets();
        }
        if ( in_array($post_type, ['dsa_private_lesson', 'dsa_group_class']) ) {
            _dsa_enqueue_lesson_editor_assets();
        }
    }
    
    // Call helper for the Settings page
    if ( strpos( $hook_suffix, 'dsa-settings-tab' ) !== false ) {
        _dsa_enqueue_settings_assets();
    }

    // Call helper for the Statistics Page
    if ( strpos( $hook_suffix, 'dsa-statistics' ) !== false ) {
        wp_enqueue_script( 'dsa-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.1', true );
    }
}


/**
 * Helper function to enqueue assets for the main Calendar Tab.
 */
function _dsa_enqueue_calendar_assets() {
    wp_enqueue_script( 'jquery-ui-dialog' );
    wp_enqueue_style( 'wp-jquery-ui-dialog' );
    wp_enqueue_style( 'dsa-admin-calendar-css', DSA_PLUGIN_URL . 'admin/assets/css/admin-calendar.css', [], DSA_PLUGIN_VERSION );
    
    wp_enqueue_style( 'dsa-select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0' );
    wp_enqueue_script( 'dsa-select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true );

    wp_enqueue_script( 'dsa-fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js', [], '6.1.11', true );
    wp_enqueue_script( 'dsa-admin-calendar-modals', DSA_PLUGIN_URL . 'admin/assets/js/dsa-calendar-modals.js', ['jquery', 'jquery-ui-dialog', 'dsa-select2-js'], DSA_PLUGIN_VERSION, true );
    wp_enqueue_script( 'dsa-admin-calendar', DSA_PLUGIN_URL . 'admin/assets/js/dsa-calendar.js', ['dsa-fullcalendar', 'dsa-admin-calendar-modals'], DSA_PLUGIN_VERSION, true );
    
    $studio_settings = get_option('dsa_studio_settings', []);
    $week_start_day = isset($studio_settings['calendar_week_start']) ? absint($studio_settings['calendar_week_start']) : 1;
    
    wp_localize_script('dsa-admin-calendar', 'dsaCalendarData', [
        'ajax_url'                => admin_url( 'admin-ajax.php' ),
        'firstDay'                => $week_start_day,
        'l10n'                    => ['areYouSure' => __('Are you sure you want to delete this event?', 'dancestudio-app')],
        'get_events_nonce'        => wp_create_nonce('dsa_get_admin_calendar_events_nonce'),
        'add_lesson_nonce'        => wp_create_nonce('dsa_add_lesson_nonce'),
        'add_class_nonce'         => wp_create_nonce('dsa_add_class_action_ajax'),
        'update_class_nonce'      => wp_create_nonce('dsa_update_class_nonce'),
        'update_lesson_nonce'     => wp_create_nonce('dsa_update_lesson_nonce'),
        'delete_event_nonce'      => wp_create_nonce('dsa_delete_event_nonce'),
        'get_attendance_nonce'    => wp_create_nonce('dsa_get_class_attendance_nonce'),
        'save_attendance_nonce'   => wp_create_nonce('dsa_save_class_attendance_nonce'),
        'get_dropdown_data_nonce' => wp_create_nonce('dsa_get_modal_dropdown_data_nonce'),
        'get_lesson_details_nonce'=> wp_create_nonce('dsa_get_lesson_details_nonce'),
        'search_students_nonce'   => wp_create_nonce('dsa_search_students_nonce_action'),
        'get_choreos_nonce'       => wp_create_nonce('dsa_get_assigned_choreos_nonce'),
        'get_figures_nonce'       => wp_create_nonce('dsa_get_figures_nonce'),
    ]);
}

/**
 * Helper function to enqueue assets for the Choreographies Tab.
 */
function _dsa_enqueue_choreographies_assets() {
    wp_enqueue_script( 'jquery-ui-dialog' );
    wp_enqueue_style( 'wp-jquery-ui-dialog' );
    wp_enqueue_media();
    
    wp_enqueue_script( 'dsa-admin-tab-choreographies-js', DSA_PLUGIN_URL . 'admin/assets/js/admin-tab-choreographies.js', ['jquery', 'jquery-ui-dialog'], DSA_PLUGIN_VERSION, true );
    wp_localize_script('dsa-admin-tab-choreographies-js', 'dsaChoreographyData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dsa_create_choreography_nonce'),
    ]);
}

/**
 * Helper function to enqueue assets for the Couples Tab.
 */
function _dsa_enqueue_couples_assets() {
    if ( isset($_GET['action']) && $_GET['action'] === 'view_couple_details' ) {
        wp_enqueue_media(); // For song uploader
        wp_enqueue_script( 'jquery-ui-dialog' );
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        wp_enqueue_script( 'dsa-admin-view-couple-details-js', DSA_PLUGIN_URL . 'admin/assets/js/admin-view-couple-details.js', ['jquery', 'jquery-ui-dialog'], DSA_PLUGIN_VERSION, true );
        wp_localize_script('dsa-admin-view-couple-details-js', 'dsaCoupleDetailsData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('dsa_add_lesson_nonce'),
        ]);

    } else {
        wp_enqueue_style( 'dsa-select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0' );
        wp_enqueue_script( 'dsa-select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true );
        wp_enqueue_script( 'dsa-admin-tab-couples-js', DSA_PLUGIN_URL . 'admin/assets/js/admin-tab-couples.js', ['jquery', 'dsa-select2-js'], DSA_PLUGIN_VERSION, true );
        
        wp_localize_script('dsa-admin-tab-couples-js', 'dsaCouplesData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('dsa_search_students_nonce_action'),
        ]);
    }
}

/**
 * Helper function to enqueue assets for the main WP User Profile pages.
 */
function _dsa_enqueue_user_profile_assets() {
    wp_enqueue_script( 'dsa-admin-user-profile-js', DSA_PLUGIN_URL . 'admin/assets/js/admin-user-profile.js', ['jquery'], DSA_PLUGIN_VERSION, true );
    wp_localize_script( 'dsa-admin-user-profile-js', 'dsaUserProfile', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dsa_user_profile_enrollment_nonce'),
    ]);
}

/**
 * Helper function to enqueue assets for the Students Tab.
 */
function _dsa_enqueue_students_assets($hook_suffix) {
    $is_single_profile_view = (isset($_GET['action']) && $_GET['action'] === 'view_profile' && isset($_GET['student_id']));

    wp_enqueue_script( 'jquery-ui-dialog' );
    wp_enqueue_style( 'wp-jquery-ui-dialog' );
    
    if ( $is_single_profile_view ) {
        $student_id = absint($_GET['student_id']);
        wp_enqueue_script( 'dsa-admin-profile-details-js', DSA_PLUGIN_URL . 'admin/assets/js/admin-tab-profile-details.js', ['jquery', 'jquery-ui-dialog'], DSA_PLUGIN_VERSION, true );
        wp_localize_script( 'dsa-admin-profile-details-js', 'dsaProfileData', [
            'ajax_url'    => admin_url('admin-ajax.php'),
            'get_nonce'   => wp_create_nonce('dsa_edit_student_details_nonce_action'),
            'save_nonce'  => wp_create_nonce('dsa_save_student_details_nonce_action'),
        ]);
        wp_enqueue_script( 'dsa-admin-enrollment-history-js', DSA_PLUGIN_URL . 'admin/assets/js/admin-tab-enrollment-history.js', ['jquery', 'jquery-ui-dialog'], DSA_PLUGIN_VERSION, true );
        wp_localize_script( 'dsa-admin-enrollment-history-js', 'dsaEnrollmentData', [
            'ajax_url'    => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('dsa_enrollment_history_nonce'),
            'studentId'   => $student_id
        ]);
    } else {
        wp_enqueue_script( 'dsa-admin-tab-students-js', DSA_PLUGIN_URL . 'admin/assets/js/admin-tab-students.js', ['jquery', 'jquery-ui-dialog'], DSA_PLUGIN_VERSION, true );
        wp_localize_script( 'dsa-admin-tab-students-js', 'dsaStudentTabData', [
            'ajax_url'     => admin_url('admin-ajax.php'),
            'get_nonce'    => wp_create_nonce('dsa_get_enrollment_modal_nonce'),
            'update_nonce' => wp_create_nonce('dsa_user_profile_enrollment_nonce'),
        ]);
    }
}

/**
 * Helper function to enqueue assets for the Group CPT editor.
 */
function _dsa_enqueue_group_editor_assets() {
    wp_enqueue_script( 'dsa-admin-enrollment-js', DSA_PLUGIN_URL . 'admin/assets/js/admin-enrollment.js', ['jquery'], DSA_PLUGIN_VERSION, true );
    wp_localize_script( 'dsa-admin-enrollment-js', 'dsaEnrollmentData', [
        'nonce'   => wp_create_nonce( 'dsa_ajax_enrollment_nonce_action' ),
        'groupId' => get_the_ID(),
    ]);
}

/**
 * Helper function to enqueue assets for the Lesson & Class CPT editors.
 */
function _dsa_enqueue_lesson_editor_assets() {
    wp_enqueue_script( 'dsa-admin-meta-boxes-js', DSA_PLUGIN_URL . 'admin/assets/js/dsa-meta-boxes.js', ['jquery'], DSA_PLUGIN_VERSION, true );
    
    $practiced_choreos = get_post_meta(get_the_ID(), '_dsa_practiced_choreography_ids', true);

    wp_localize_script( 'dsa-admin-meta-boxes-js', 'dsaMetaBoxData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'get_orders_nonce' => wp_create_nonce('dsa_get_student_orders_nonce'),
        'get_choreos_nonce' => wp_create_nonce('dsa_get_assigned_choreos_nonce'),
        'get_figures_nonce' => wp_create_nonce('dsa_get_figures_nonce'),
        'class_id' => get_the_ID(),
        'practiced_choreos' => is_array($practiced_choreos) ? $practiced_choreos : []
    ]);
}

/**
 * Helper function to enqueue assets for the Settings Tab.
 */
function _dsa_enqueue_settings_assets() {
    wp_enqueue_media();
    wp_enqueue_script( 'dsa-admin-settings-js', DSA_PLUGIN_URL . 'admin/assets/js/dsa-settings-admin.js', ['jquery'], DSA_PLUGIN_VERSION, true );
    wp_localize_script( 'dsa-admin-settings-js', 'dsaSettingsData', ['l10n' => ['selectLogo' => __('Choose Studio Logo', 'dancestudio-app'), 'useLogo' => __('Use this logo', 'dancestudio-app')]]);
}