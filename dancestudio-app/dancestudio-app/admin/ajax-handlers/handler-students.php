<?php
/**
 * AJAX Handlers for Student Management from the front-end dashboard.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * AJAX handler to fetch data for a single student to populate the edit modal.
 */
add_action( 'wp_ajax_dsa_get_student_data', 'dsa_get_student_data_ajax_handler' );
function dsa_get_student_data_ajax_handler() {
    // FINAL FIX: Use the correct action name for the nonce check to match the form.
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'dsa_manage_student_nonce_action' ) || ! current_user_can( 'edit_users' ) ) {
        wp_send_json_error( ['message' => 'Security check failed.'] );
    }

    $student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
    if ( ! $student_id || ! ($student_data = get_userdata( $student_id )) ) {
        wp_send_json_error( ['message' => 'Student not found.'] );
    }
    
    // Prepare data to send back as JSON
    $data = [
        'first_name' => $student_data->first_name,
        'last_name'  => $student_data->last_name,
        'email'      => $student_data->user_email,
        'birth_date' => get_user_meta( $student_id, '_dsa_user_birth_date', true ),
        'phone'      => get_user_meta( $student_id, '_dsa_user_phone', true ),
    ];

    wp_send_json_success( $data );
}


/**
 * AJAX handler to save updated student data from the edit modal.
 */
add_action( 'wp_ajax_dsa_update_student_data', 'dsa_update_student_data_ajax_handler' );
function dsa_update_student_data_ajax_handler() {
    // This function already uses the correct nonce check.
    if ( ! isset( $_POST['dsa_manage_student_nonce'] ) || ! wp_verify_nonce( $_POST['dsa_manage_student_nonce'], 'dsa_manage_student_nonce_action' ) || ! current_user_can( 'edit_users' ) ) {
        wp_send_json_error( ['message' => 'Security check failed on save.'] );
    }

    $student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
    if ( ! $student_id ) {
        wp_send_json_error( ['message' => 'Invalid student ID.'] );
    }

    // Sanitize and prepare data for update
    $user_data = ['ID' => $student_id];
    if ( isset( $_POST['first_name'] ) ) $user_data['first_name'] = sanitize_text_field( $_POST['first_name'] );
    if ( isset( $_POST['last_name'] ) )  $user_data['last_name'] = sanitize_text_field( $_POST['last_name'] );
    if ( isset( $_POST['email'] ) )      $user_data['user_email'] = sanitize_email( $_POST['email'] );

    $result = wp_update_user( $user_data );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( ['message' => $result->get_error_message()] );
    }

    // Update custom meta fields
    if ( isset( $_POST['birth_date'] ) ) update_user_meta( $student_id, '_dsa_user_birth_date', sanitize_text_field( $_POST['birth_date'] ) );
    if ( isset( $_POST['phone'] ) )      update_user_meta( $student_id, '_dsa_user_phone', sanitize_text_field( $_POST['phone'] ) );

    wp_send_json_success( ['message' => 'Student details updated successfully!'] );
}
