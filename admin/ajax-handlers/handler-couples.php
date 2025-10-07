<?php
/**
 * AJAX Handlers for Couple Management functions.
 * UPDATED: Saves the new "Couple Type" meta field.
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action( 'wp_ajax_dsa_pair_couple', 'dsa_pair_couple_ajax_handler' );
function dsa_pair_couple_ajax_handler() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'dsa_pairing_action_ajax' ) ) {
        wp_send_json_error( ['message' => 'Security check failed.'] );
    }

    if ( ! current_user_can( 'edit_users' ) ) {
        wp_send_json_error( ['message' => 'You do not have permission to manage users.'] );
    }

    $s1_id = isset( $_POST['dsa_student1_id'] ) ? absint( $_POST['dsa_student1_id'] ) : 0;
    $s2_id = isset( $_POST['dsa_student2_id'] ) ? absint( $_POST['dsa_student2_id'] ) : 0;

    if ( $s1_id === 0 || $s2_id === 0 ) {
        wp_send_json_error( ['message' => 'Please select two different students to pair.'] );
    }

    if ( $s1_id === $s2_id ) {
        wp_send_json_error( ['message' => 'A student cannot be paired with themselves.'] );
    }

    // --- NEW: Get, validate, and save the Couple Type ---
    if ( isset( $_POST['dsa_couple_type'] ) ) {
        $couple_type = sanitize_key( $_POST['dsa_couple_type'] );
        $allowed_types = ['wedding', 'recreation', 'competitive'];

        if ( in_array( $couple_type, $allowed_types, true ) ) {
            update_user_meta( $s1_id, '_dsa_couple_type', $couple_type );
            update_user_meta( $s2_id, '_dsa_couple_type', $couple_type );
        }
    }
    // --- END NEW ---

    $pairing_date = current_time( 'mysql' );
    update_user_meta( $s1_id, '_dsa_pairing_date', $pairing_date );
    update_user_meta( $s2_id, '_dsa_pairing_date', $pairing_date );
    
    update_user_meta( $s1_id, 'dsa_partner_user_id', $s2_id );
    update_user_meta( $s2_id, 'dsa_partner_user_id', $s1_id );
    
    wp_send_json_success( ['message' => 'Students have been successfully paired! The page will now reload.'] );
}