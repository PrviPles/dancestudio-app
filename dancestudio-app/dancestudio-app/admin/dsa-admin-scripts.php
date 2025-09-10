<?php
/**
 * DanceStudio App Admin Scripts Enqueuing
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action( 'admin_enqueue_scripts', 'dsa_enqueue_dsa_admin_assets' );
if ( ! function_exists( 'dsa_enqueue_dsa_admin_assets' ) ) {
    function dsa_enqueue_dsa_admin_assets( $hook_suffix ) {
        
        global $post;
        $post_type = $post ? get_post_type( $post ) : '';
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';

        // Load script on user profile pages to handle AJAX enrollments
        if ( in_array( $hook_suffix, ['profile.php', 'user-edit.php'] ) ) {
            wp_enqueue_script( 'dsa-admin-user-profile-js', DSA_PLUGIN_URL . 'admin/assets/js/admin-user-profile.js', ['jquery'], DSA_PLUGIN_VERSION, true );
            wp_localize_script( 'dsa-admin-user-profile-js', 'dsaUserProfile', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('dsa_user_profile_enrollment_nonce'),
            ]);
        }
        
        // Load scripts on the "Students" Tab for the enrollment modal
        if ( 'dancestudio-app_page_dsa-students-tab' === $hook_suffix ) {
            wp_enqueue_script( 'jquery-ui-dialog' );
            wp_enqueue_style( 'wp-jquery-ui-dialog' );
            wp_enqueue_script( 'dsa-admin-tab-students-js', DSA_PLUGIN_URL . 'admin/assets/js/admin-tab-students.js', ['jquery', 'jquery-ui-dialog'], DSA_PLUGIN_VERSION, true );
            
            wp_localize_script( 'dsa-admin-tab-students-js', 'dsaStudentTabData', [
                'ajax_url'     => admin_url('admin-ajax.php'),
                'get_nonce'    => wp_create_nonce('dsa_get_enrollment_modal_nonce'),
                'update_nonce' => wp_create_nonce('dsa_user_profile_enrollment_nonce'),
            ]);
        }

        // Enqueue scripts for the Group CPT editor screen
        if ( 'dsa_group' === $post_type && in_array( $hook_suffix, ['post.php', 'post-new.php'] ) ) {
            wp_enqueue_script( 'dsa-admin-enrollment-js', DSA_PLUGIN_URL . 'admin/assets/js/admin-enrollment.js', ['jquery'], DSA_PLUGIN_VERSION, true );
            wp_localize_script( 'dsa-admin-enrollment-js', 'dsaEnrollmentData', [
                'nonce'   => wp_create_nonce( 'dsa_ajax_enrollment_nonce_action' ),
                'groupId' => get_the_ID(),
            ]);
        }
        
        // Enqueue scripts for the Lesson/Class CPT editor screens
        $cpt_with_meta_box_scripts = ['dsa_private_lesson', 'dsa_group_class'];
        if ( in_array($post_type, $cpt_with_meta_box_scripts) && in_array($hook_suffix, ['post.php', 'post-new.php']) ) {
            wp_enqueue_script( 'dsa-admin-meta-boxes-js', DSA_PLUGIN_URL . 'admin/assets/js/dsa-meta-boxes.js', ['jquery'], DSA_PLUGIN_VERSION, true );
            wp_localize_script( 'dsa-admin-meta-boxes-js', 'dsaMetaBoxData', [
                'get_orders_nonce' => wp_create_nonce('dsa_get_student_orders_nonce')
            ]);
        }
        
        // Settings page script
        if ( strpos( $hook_suffix, 'dsa-settings-tab' ) !== false ) {
            wp_enqueue_media();
            wp_enqueue_script( 'dsa-admin-settings-js', DSA_PLUGIN_URL . 'admin/assets/js/dsa-settings-admin.js', ['jquery'], DSA_PLUGIN_VERSION, true );
            wp_localize_script( 'dsa-admin-settings-js', 'dsaSettingsData', ['l10n' => ['selectLogo' => __('Choose Studio Logo', 'dancestudio-app'), 'useLogo' => __('Use this logo', 'dancestudio-app')]]);
        }

        // Calendar Tab
        if ( strpos( $hook_suffix, 'dsa-calendar-tab' ) !== false ) {
            wp_enqueue_script( 'jquery-ui-dialog' );
            wp_enqueue_style( 'wp-jquery-ui-dialog' );
            wp_enqueue_script( 'dsa-fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js', [], '6.1.11', true );
            wp_enqueue_script( 'dsa-admin-calendar-modals', DSA_PLUGIN_URL . 'admin/assets/js/dsa-calendar-modals.js', ['jquery', 'jquery-ui-dialog'], DSA_PLUGIN_VERSION, true );
            wp_enqueue_script( 'dsa-admin-calendar', DSA_PLUGIN_URL . 'admin/assets/js/dsa-calendar.js', ['dsa-fullcalendar', 'dsa-admin-calendar-modals'], DSA_PLUGIN_VERSION, true );
            
            $studio_settings = get_option('dsa_studio_settings', []);
            $week_start_day = isset($studio_settings['calendar_week_start']) ? absint($studio_settings['calendar_week_start']) : 1;
            
            wp_localize_script('dsa-admin-calendar', 'dsaCalendarData', [
                'ajax_url'              => admin_url( 'admin-ajax.php' ),
                'firstDay'              => $week_start_day,
                'l10n'                  => ['areYouSure' => __('Are you sure you want to delete this event?', 'dancestudio-app')],
                'get_events_nonce'      => wp_create_nonce('dsa_get_admin_calendar_events_nonce'),
                'add_lesson_nonce'      => wp_create_nonce('dsa_add_lesson_nonce'),
                'add_class_nonce'       => wp_create_nonce('dsa_add_class_action_ajax'),
                'update_class_nonce'    => wp_create_nonce('dsa_update_class_nonce'),
                'delete_event_nonce'    => wp_create_nonce('dsa_delete_event_nonce'),
                'get_attendance_nonce'  => wp_create_nonce('dsa_get_class_attendance_nonce'),
                'save_attendance_nonce' => wp_create_nonce('dsa_save_class_attendance_nonce'),
                'get_dropdown_data_nonce' => wp_create_nonce('dsa_get_modal_dropdown_data_nonce'),
            ]);
        }

        // Statistics Page
        if ( strpos( $hook_suffix, 'dsa-statistics' ) !== false ) {
            wp_enqueue_script( 'dsa-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.1', true );
        }
    }
}