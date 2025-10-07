<?php
/**
 * AJAX Handlers for Student Profile Management.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

// AJAX handler to fetch data for a single student to populate the edit modal.
add_action( 'wp_ajax_dsa_get_student_details_for_edit', 'dsa_get_student_details_for_edit_handler' );
function dsa_get_student_details_for_edit_handler() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'dsa_edit_student_details_nonce_action' ) || ! current_user_can( 'edit_users' ) ) {
        wp_send_json_error( ['message' => 'Security check failed.'] );
    }

    $student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
    if ( ! $student_id || ! ($student_data = get_userdata( $student_id )) ) {
        wp_send_json_error( ['message' => 'Student not found.'] );
    }
    
    // Prepare data to send back as JSON
    $data = [
        'first_name'      => $student_data->first_name,
        'last_name'       => $student_data->last_name,
        'email'           => $student_data->user_email,
        'phone'           => get_user_meta( $student_id, '_dsa_user_phone', true ),
        'street'          => get_user_meta( $student_id, '_dsa_user_street', true ),
        'postal_code'     => get_user_meta( $student_id, '_dsa_user_postal_code', true ),
        'city'            => get_user_meta( $student_id, '_dsa_user_city', true ),
        'birth_date'      => get_user_meta( $student_id, '_dsa_user_birth_date', true ),
        'is_retired'      => get_user_meta( $student_id, '_dsa_is_retired', true ),
        'family_discount' => get_user_meta( $student_id, '_dsa_family_discount', true ),
    ];

    wp_send_json_success( $data );
}

// AJAX handler to save updated student data from the edit modal.
add_action( 'wp_ajax_dsa_save_student_details_from_modal', 'dsa_save_student_details_from_modal_handler' );
function dsa_save_student_details_from_modal_handler() {
    // CORRECTED: The nonce name must match the one created by wp_nonce_field() in the form.
    if ( ! isset( $_POST['dsa_save_student_details_nonce'] ) || ! wp_verify_nonce( $_POST['dsa_save_student_details_nonce'], 'dsa_save_student_details_nonce_action' ) || ! current_user_can( 'edit_users' ) ) {
        wp_send_json_error( ['message' => 'Security check failed on save.'] );
    }

    $student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
    if ( ! $student_id ) {
        wp_send_json_error( ['message' => 'Invalid student ID.'] );
    }

    // Sanitize and prepare core data for update
    $user_data = ['ID' => $student_id];
    if ( isset( $_POST['first_name'] ) ) $user_data['first_name'] = sanitize_text_field( $_POST['first_name'] );
    if ( isset( $_POST['last_name'] ) )  $user_data['last_name'] = sanitize_text_field( $_POST['last_name'] );
    if ( isset( $_POST['email'] ) )      $user_data['user_email'] = sanitize_email( $_POST['email'] );
    $result = wp_update_user( $user_data );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( ['message' => $result->get_error_message()] );
    }

    // Sanitize and update custom meta fields
    $meta_fields = [
        '_dsa_user_phone'         => 'phone',
        '_dsa_user_street'        => 'street',
        '_dsa_user_postal_code'   => 'postal_code',
        '_dsa_user_city'          => 'city',
        '_dsa_user_birth_date'    => 'birth_date',
    ];
    foreach($meta_fields as $meta_key => $post_key) {
        if ( isset( $_POST[$post_key] ) ) {
            update_user_meta( $student_id, $meta_key, sanitize_text_field( $_POST[$post_key] ) );
        }
    }
    
    // Update checkbox meta fields
    update_user_meta( $student_id, '_dsa_is_retired', isset( $_POST['is_retired'] ) ? '1' : '0' );
    update_user_meta( $student_id, '_dsa_family_discount', isset( $_POST['family_discount'] ) ? '1' : '0' );

    wp_send_json_success( ['message' => 'Student details updated successfully!'] );
}