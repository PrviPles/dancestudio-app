<?php
/**
 * AJAX Handlers for Enrollment functions.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action( 'wp_ajax_dsa_handle_enrollment_ajax', 'dsa_handle_enrollment_ajax_submission' );
function dsa_handle_enrollment_ajax_submission() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'dsa_ajax_enrollment_nonce_action' ) ) {
        wp_send_json_error( ['message' => 'Security check failed!'] );
    }
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    if ( ! $group_id || ! get_post( $group_id ) ) {
        wp_send_json_error( ['message' => 'Invalid Group ID provided.'] );
    }
    if ( ! current_user_can( 'edit_post', $group_id ) ) {
        wp_send_json_error( ['message' => 'You do not have permission to manage this group.'] );
    }
    $student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
    if ( $student_id === 0 ) {
        wp_send_json_error( ['message' => 'Invalid Student ID.'] );
    }
    $sub_action = isset( $_POST['sub_action'] ) ? sanitize_key( $_POST['sub_action'] ) : '';

    if ( 'enroll' === $sub_action ) {
        $result = dsa_enroll_student_in_group( $student_id, $group_id );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( ['message' => $result->get_error_message()] );
        }
        $student_data = get_userdata( $student_id );
        $enroll_date = get_post_meta( $result, '_dsa_enrollment_date', true );
        
        $html = '<tr id="dsa-member-row-' . esc_attr( $student_id ) . '">';
        $html .= '<td><a href="' . esc_url( get_edit_user_link( $student_id ) ) . '"><strong>' . esc_html( $student_data->display_name ) . '</strong></a></td>';
        $html .= '<td>' . esc_html( date_i18n( get_option('date_format'), strtotime( $enroll_date ) ) ) . '</td>';
        $html .= '<td style="text-align: right;"><button type="button" class="button button-link-delete dsa-dropout-button" data-student-id="' . esc_attr( $student_id ) . '">Drop Out</button></td>';
        $html .= '</tr>';
        
        wp_send_json_success( ['html' => $html] );

    } elseif ( 'dropout' === $sub_action ) {
        $result = dsa_dropout_student_from_group( $student_id, $group_id );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( ['message' => $result->get_error_message()] );
        }
        wp_send_json_success();
    }
    wp_send_json_error( ['message' => 'Invalid action specified.'] );
}

add_action( 'wp_ajax_dsa_update_user_enrollment', 'dsa_update_user_enrollment_ajax_handler' );
function dsa_update_user_enrollment_ajax_handler() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'dsa_user_profile_enrollment_nonce' ) ) {
        wp_send_json_error( ['message' => 'Security check failed.'] );
    }
    $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        wp_send_json_error( ['message' => 'You do not have permission to edit this user.'] );
    }
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $is_enrolled = isset( $_POST['is_enrolled'] ) ? filter_var($_POST['is_enrolled'], FILTER_VALIDATE_BOOLEAN) : false;

    if ( ! $user_id || ! $group_id ) {
        wp_send_json_error( ['message' => 'Invalid user or group ID.'] );
    }
    if ( $is_enrolled ) {
        $result = dsa_enroll_student_in_group( $user_id, $group_id );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( ['message' => $result->get_error_message()] );
        }
        wp_send_json_success( ['message' => 'Enrolled successfully.'] );
    } else {
        $result = dsa_dropout_student_from_group( $user_id, $group_id );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( ['message' => $result->get_error_message()] );
        }
        wp_send_json_success( ['message' => 'Dropped out successfully.'] );
    }
}

/**
 * NEW: Handles fetching all groups and a specific student's enrollments for the modal.
 */
add_action( 'wp_ajax_dsa_get_enrollment_modal_data', 'dsa_get_enrollment_modal_data_handler' );
function dsa_get_enrollment_modal_data_handler() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'dsa_get_enrollment_modal_nonce' ) ) {
        wp_send_json_error( ['message' => 'Security check failed.'] );
    }
    $student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
    if ( ! current_user_can( 'edit_user', $student_id ) ) {
        wp_send_json_error( ['message' => 'You do not have permission to edit this user.'] );
    }

    $all_groups = [];
    $groups_query = new WP_Query(['post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    if ($groups_query->have_posts()) {
        foreach($groups_query->get_posts() as $group) {
            $all_groups[] = ['id' => (string)$group->ID, 'name' => $group->post_title];
        }
    }

    $active_enrollments = get_posts([
        'post_type'   => 'dsa_enroll_record',
        'post_status' => 'publish',
        'author'      => $student_id,
        'posts_per_page' => -1,
    ]);
    $enrolled_group_ids = wp_list_pluck( $active_enrollments, 'post_parent' );
    
    wp_send_json_success([
        'all_groups' => $all_groups,
        'enrolled_in' => $enrolled_group_ids,
    ]);
}