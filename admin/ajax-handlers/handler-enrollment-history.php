<?php
/**
 * AJAX Handlers for the Enrollment History Tab.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

// Fetches groups to populate the enrollment modal.
add_action('wp_ajax_dsa_get_groups_for_enrollment_modal', function() {
    check_ajax_referer('dsa_enrollment_history_nonce', 'nonce');
    if (!current_user_can('edit_users')) wp_send_json_error('Permission denied.');
    
    $student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;
    
    // CORRECTED LOGIC: Get the full enrollment record objects first.
    $active_enrollment_records = get_posts([
        'post_type' => 'dsa_enroll_record', 
        'post_status' => 'publish', 
        'author' => $student_id,
        'posts_per_page' => -1,
    ]);
    
    // Then, use wp_list_pluck to get an array of just the group IDs (post_parent).
    $enrolled_group_ids = wp_list_pluck( $active_enrollment_records, 'post_parent' );

    // Get all groups, excluding the ones the student is already enrolled in.
    $all_groups = get_posts([
        'post_type' => 'dsa_group', 
        'posts_per_page' => -1, 
        'exclude' => $enrolled_group_ids,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    
    $groups_data = [];
    foreach ($all_groups as $group) {
        $groups_data[] = ['id' => $group->ID, 'text' => $group->post_title];
    }
    wp_send_json_success($groups_data);
});

// Enrolls a student with a specific date.
add_action('wp_ajax_dsa_enroll_student_with_date', function() {
    check_ajax_referer('dsa_enrollment_history_nonce', 'nonce');
    if (!current_user_can('edit_users')) wp_send_json_error('Permission denied.');

    $student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;
    $group_id = isset($_POST['group_id']) ? absint($_POST['group_id']) : 0;
    $enroll_date = isset($_POST['enroll_date']) ? sanitize_text_field($_POST['enroll_date']) : '';

    $result = dsa_enroll_student_in_group($student_id, $group_id, $enroll_date . ' 12:00:00');
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
    wp_send_json_success(['message' => 'Student enrolled successfully.']);
});

// Drops a student out with a specific date.
add_action('wp_ajax_dsa_dropout_student_with_date', function() {
    check_ajax_referer('dsa_enrollment_history_nonce', 'nonce');
    if (!current_user_can('edit_users')) wp_send_json_error('Permission denied.');

    $student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;
    $group_id = isset($_POST['group_id']) ? absint($_POST['group_id']) : 0;
    $dropout_date = isset($_POST['dropout_date']) ? sanitize_text_field($_POST['dropout_date']) : '';
    
    $result = dsa_dropout_student_from_group($student_id, $group_id, $dropout_date . ' 12:00:00');
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
    wp_send_json_success(['message' => 'Student dropped out successfully.']);
});

// Updates an existing enrollment or dropout date.
add_action('wp_ajax_dsa_update_enrollment_date', function() {
    check_ajax_referer('dsa_enrollment_history_nonce', 'nonce');
    $record_id = isset($_POST['record_id']) ? absint($_POST['record_id']) : 0;
    if (!current_user_can('edit_post', $record_id)) wp_send_json_error('Permission denied.');

    $date_type = isset($_POST['date_type']) ? sanitize_key($_POST['date_type']) : '';
    $new_date = isset($_POST['new_date']) ? sanitize_text_field($_POST['new_date']) : '';
    
    if (empty($new_date)) {
        wp_send_json_error(['message' => 'Date cannot be empty.']);
    }

    if ($date_type === 'enroll') {
        wp_update_post(['ID' => $record_id, 'post_date' => $new_date . ' 12:00:00']);
    } elseif ($date_type === 'dropout') {
        update_post_meta($record_id, '_dsa_dropout_date', $new_date . ' 12:00:00');
    }

    wp_send_json_success(['formatted_date' => date_i18n(get_option('date_format'), strtotime($new_date))]);
});

// Permanently deletes an enrollment record.
add_action('wp_ajax_dsa_delete_enrollment_record', function() {
    check_ajax_referer('dsa_enrollment_history_nonce', 'nonce');
    $record_id = isset($_POST['record_id']) ? absint($_POST['record_id']) : 0;
    
    if ( ! current_user_can('delete_post', $record_id) ) {
        wp_send_json_error(['message' => 'You do not have permission to delete this record.']);
    }

    $result = wp_delete_post( $record_id, true ); // true = force delete

    if ( $result ) {
        wp_send_json_success(['message' => 'Enrollment record deleted successfully.']);
    } else {
        wp_send_json_error(['message' => 'An error occurred while deleting the record.']);
    }
});