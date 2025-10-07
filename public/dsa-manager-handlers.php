<?php
/**
 * Public Form & AJAX Handlers for the Studio Manager Dashboard.
 */
if ( ! defined( 'WPINC' ) ) die;

// ... (Existing handlers for students are unchanged) ...

// --- NEW: AJAX handler for creating a new Dance Group ---
add_action( 'wp_ajax_dsa_create_group_ajax', 'dsa_handle_create_group_ajax' );
function dsa_handle_create_group_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'dsa_manage_groups_nonce' ) ) {
        wp_send_json_error( ['message' => 'Security check failed.'] );
    }
    if ( ! current_user_can( 'publish_posts' ) ) {
        wp_send_json_error( ['message' => 'You do not have permission to create groups.'] );
    }
    $group_name = isset( $_POST['group_name'] ) ? sanitize_text_field( $_POST['group_name'] ) : '';
    if ( empty($group_name) ) {
        wp_send_json_error( ['message' => 'Group Name is a required field.'] );
    }

    $new_group_id = wp_insert_post([
        'post_type' => 'dsa_group',
        'post_title' => $group_name,
        'post_status' => 'publish',
    ]);

    if ( is_wp_error( $new_group_id ) ) {
        wp_send_json_error( ['message' => $new_group_id->get_error_message()] );
    }

    // Prepare the HTML for the new table row
    $html  = '<tr class="dsa-group-row" id="dsa-group-row-' . esc_attr($new_group_id) . '">';
    $html .= '<td class="dsa-group-name">' . esc_html($group_name) . '</td>';
    $html .= '<td class="dsa-actions">';
    $html .= '<button type="button" class="button-secondary dsa-edit-group-button" data-group-id="' . esc_attr($new_group_id) . '" data-group-name="' . esc_attr($group_name) . '">Edit</button> ';
    $html .= '<button type="button" class="button-link-delete dsa-delete-group-button" data-group-id="' . esc_attr($new_group_id) . '">Delete</button>';
    $html .= '</td>';
    $html .= '</tr>';
    
    wp_send_json_success( ['message' => 'Group created successfully!', 'html' => $html] );
}

// --- NEW: AJAX handler for updating a Dance Group's name ---
add_action( 'wp_ajax_dsa_update_group_ajax', 'dsa_handle_update_group_ajax' );
function dsa_handle_update_group_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'dsa_manage_groups_nonce' ) ) {
        wp_send_json_error( ['message' => 'Security check failed.'] );
    }
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    if ( ! current_user_can( 'edit_post', $group_id ) ) {
        wp_send_json_error( ['message' => 'You do not have permission to edit this group.'] );
    }
    $group_name = isset( $_POST['group_name'] ) ? sanitize_text_field( $_POST['group_name'] ) : '';
    if ( empty($group_name) ) {
        wp_send_json_error( ['message' => 'Group Name cannot be empty.'] );
    }

    $result = wp_update_post(['ID' => $group_id, 'post_title' => $group_name]);
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( ['message' => $result->get_error_message()] );
    }
    wp_send_json_success(['message' => 'Group updated successfully.']);
}

// --- NEW: AJAX handler for deleting a Dance Group ---
add_action( 'wp_ajax_dsa_delete_group_ajax', 'dsa_handle_delete_group_ajax' );
function dsa_handle_delete_group_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'dsa_manage_groups_nonce' ) ) {
        wp_send_json_error( ['message' => 'Security check failed.'] );
    }
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    if ( ! current_user_can( 'delete_post', $group_id ) ) {
        wp_send_json_error( ['message' => 'You do not have permission to delete this group.'] );
    }

    $result = wp_delete_post( $group_id, true ); // true = force delete
    if ( $result ) {
        wp_send_json_success(['message' => 'Group deleted successfully.']);
    } else {
        wp_send_json_error(['message' => 'An error occurred while deleting the group.']);
    }
}