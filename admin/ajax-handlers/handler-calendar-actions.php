<?php
/**
 * AJAX Handlers for Calendar Create, Update, and Delete actions.
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles deleting a calendar event.
 */
add_action( 'wp_ajax_dancestudio_app_delete_calendar_event', 'dancestudio_app_ajax_delete_event_callback' );
if( ! function_exists('dancestudio_app_ajax_delete_event_callback') ) {
    function dancestudio_app_ajax_delete_event_callback() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dsa_delete_event_nonce' ) ) { wp_send_json_error( ['message' => 'Security token is invalid.'], 401 ); }
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( $post_id === 0 ) { wp_send_json_error( ['message' => 'Error: Invalid Post ID received.'], 400 ); }
        if ( ! current_user_can( 'delete_post', $post_id ) ) { wp_send_json_error( ['message' => 'Error: You do not have permission to delete this event.'], 403 ); }
        $result = wp_delete_post( $post_id, true );
        if ( $result ) { wp_send_json_success( ['message' => 'Event deleted successfully.'] ); }
        else { wp_send_json_error( ['message' => 'An unknown error occurred on the server while trying to delete the event.'] ); }
    }
}

/**
 * Handles adding a new group class via AJAX from the modal.
 */
add_action( 'wp_ajax_dsa_add_class_session_ajax', 'dsa_add_class_session_ajax_handler' );
if ( ! function_exists( 'dsa_add_class_session_ajax_handler' ) ) {
    function dsa_add_class_session_ajax_handler() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dsa_add_class_action_ajax' ) ) { wp_send_json_error( ['message' => 'Nonce verification failed.'], 403 ); }
        if ( ! current_user_can( 'publish_posts' ) ) { wp_send_json_error( ['message' => 'You do not have permission to add classes.'], 403 ); }
        if ( empty( $_POST['dsa_class_label'] ) ) { wp_send_json_error( ['message' => 'Class Label/Title is a required field.'], 400 ); }
        if ( empty( $_POST['dsa_class_date'] ) ) { wp_send_json_error( ['message' => 'Date is a required field.'], 400 ); }
        $post_data = ['post_type' => 'dsa_group_class', 'post_title' => sanitize_text_field( $_POST['dsa_class_label'] ), 'post_content' => isset( $_POST['dsa_class_notes'] ) ? sanitize_textarea_field( $_POST['dsa_class_notes'] ) : '', 'post_status'  => 'publish'];
        $new_post_id = wp_insert_post( $post_data, true );
        if ( is_wp_error( $new_post_id ) ) { wp_send_json_error( ['message' => 'Error creating class: ' . $new_post_id->get_error_message()] ); }
        if ( isset( $_POST['dsa_class_date'] ) ) { update_post_meta( $new_post_id, '_dsa_class_date', sanitize_text_field( $_POST['dsa_class_date'] ) ); }
        if ( isset( $_POST['dsa_class_start_time'] ) ) { update_post_meta( $new_post_id, '_dsa_class_start_time', sanitize_text_field( $_POST['dsa_class_start_time'] ) ); }
        if ( isset( $_POST['dsa_class_end_time'] ) ) { update_post_meta( $new_post_id, '_dsa_class_end_time', sanitize_text_field( $_POST['dsa_class_end_time'] ) ); }
        if ( isset( $_POST['dsa_class_group_id'] ) ) { update_post_meta( $new_post_id, '_dsa_class_group_id', absint( $_POST['dsa_class_group_id'] ) ); }
        if ( isset( $_POST['dsa_class_dance_style'] ) ) { update_post_meta( $new_post_id, '_dsa_class_dance_style', sanitize_text_field( $_POST['dsa_class_dance_style'] ) ); }
        wp_send_json_success( ['message' => 'Group class added successfully!'] );
    }
}

/**
 * Handles adding a new private lesson via AJAX.
 */
add_action( 'wp_ajax_dsa_add_private_lesson_ajax', 'dsa_add_private_lesson_ajax_handler' );
if ( ! function_exists( 'dsa_add_private_lesson_ajax_handler' ) ) {
    function dsa_add_private_lesson_ajax_handler() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dsa_add_lesson_nonce' ) ) { wp_send_json_error( ['message' => 'Nonce verification failed.'], 403 ); }
        if ( ! current_user_can( 'publish_posts' ) ) { wp_send_json_error( ['message' => 'You do not have permission to add lessons.'], 403 ); }
        if ( empty( $_POST['dsa_lesson_title'] ) || empty( $_POST['dsa_lesson_date'] ) || empty( $_POST['dsa_lesson_student1_id'] ) ) { wp_send_json_error( ['message' => 'Title, Date, and at least one Student are required.'], 400 ); }
        
        $post_data = [
            'post_type'    => 'dsa_private_lesson',
            'post_title'   => sanitize_text_field( $_POST['dsa_lesson_title'] ),
            'post_content' => isset( $_POST['dsa_lesson_notes'] ) ? sanitize_textarea_field( $_POST['dsa_lesson_notes'] ) : '',
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id()
        ];
        $new_post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $new_post_id ) ) {
            wp_send_json_error( ['message' => 'Error creating lesson: ' . $new_post_id->get_error_message()] );
        }

        // Save standard meta fields
        $meta_fields = ['_dsa_lesson_date' => 'sanitize_text_field', '_dsa_lesson_start_time' => 'sanitize_text_field', '_dsa_lesson_student1_id' => 'absint', '_dsa_lesson_student2_id' => 'absint', '_dsa_lesson_teacher_id' => 'absint', '_dsa_lesson_order_id' => 'absint'];
        foreach ( $meta_fields as $meta_key => $callback ) {
            $post_key = str_replace( '_dsa_', 'dsa_', $meta_key );
            if ( isset( $_POST[ $post_key ] ) ) { 
                update_post_meta( $new_post_id, $meta_key, call_user_func( $callback, wp_unslash( $_POST[ $post_key ] ) ) );
            }
        }

        // Save the practiced figures
        if ( isset( $_POST['dsa_practiced_figures'] ) && is_array( $_POST['dsa_practiced_figures'] ) ) {
            $sanitized_figure_ids = array_map( 'absint', $_POST['dsa_practiced_figures'] );
            update_post_meta( $new_post_id, '_dsa_practiced_figure_ids', $sanitized_figure_ids );
        }

        wp_send_json_success( ['message' => 'Private lesson added successfully!'] );
    }
}

/**
 * Handles updating an existing group class via AJAX from the calendar modal.
 */
add_action( 'wp_ajax_dsa_update_class_session_ajax', 'dsa_update_class_session_ajax_handler');
if ( ! function_exists('dsa_update_class_session_ajax_handler') ) {
    function dsa_update_class_session_ajax_handler() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dsa_update_class_nonce' ) ) { wp_send_json_error( ['message' => 'Nonce verification failed.'], 403 ); }
        if ( ! current_user_can( 'edit_posts' ) ) { wp_send_json_error( ['message' => 'You do not have permission to edit classes.'], 403 ); }

        $class_id = isset($_POST['dsa_class_id']) ? absint($_POST['dsa_class_id']) : 0;
        if ( $class_id === 0 ) { wp_send_json_error(['message' => 'Invalid Class ID.'], 400); }
        if ( empty( $_POST['dsa_class_label'] ) ) { wp_send_json_error( ['message' => 'Class Title is required.'], 400 ); }

        $post_data = ['ID' => $class_id, 'post_title' => sanitize_text_field( $_POST['dsa_class_label'] ) ];
        $result = wp_update_post( $post_data, true );
        if ( is_wp_error( $result ) ) { wp_send_json_error( ['message' => 'Error updating class: ' . $result->get_error_message()] ); }

        if ( isset( $_POST['dsa_class_date'] ) ) { update_post_meta( $class_id, '_dsa_class_date', sanitize_text_field( $_POST['dsa_class_date'] ) ); }
        if ( isset( $_POST['dsa_class_start_time'] ) ) { update_post_meta( $class_id, '_dsa_class_start_time', sanitize_text_field( $_POST['dsa_class_start_time'] ) ); }
        if ( isset( $_POST['dsa_class_group_id'] ) ) { update_post_meta( $class_id, '_dsa_class_group_id', absint( $_POST['dsa_class_group_id'] ) ); }

        if ( ! empty($_POST['dsa_choreographies']) && is_array($_POST['dsa_choreographies']) ) {
            $sanitized_ids = array_map('absint', array_keys($_POST['dsa_choreographies']));
            update_post_meta( $class_id, '_dsa_practiced_choreography_ids', $sanitized_ids );
        } else {
            delete_post_meta( $class_id, '_dsa_practiced_choreography_ids' );
        }

        wp_send_json_success( ['message' => 'Class updated successfully!'] );
    }
}

/**
 * NEW: Handles updating an existing private lesson via AJAX from the calendar.
 */
add_action( 'wp_ajax_dsa_update_private_lesson_ajax', 'dsa_update_private_lesson_ajax_handler' );
function dsa_update_private_lesson_ajax_handler() {
    // We will need to create this nonce: 'dsa_update_lesson_nonce'
    check_ajax_referer( 'dsa_update_lesson_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( ['message' => 'You do not have permission to edit lessons.'], 403 );
    }

    $lesson_id = isset( $_POST['dsa_lesson_id'] ) ? absint( $_POST['dsa_lesson_id'] ) : 0;
    if ( ! $lesson_id ) {
        wp_send_json_error( ['message' => 'Invalid Lesson ID.'], 400 );
    }

    // Update post title and content (notes)
    $post_data = [
        'ID'           => $lesson_id,
        'post_title'   => sanitize_text_field( $_POST['dsa_lesson_title'] ),
        'post_content' => sanitize_textarea_field( $_POST['dsa_lesson_notes'] ),
    ];
    wp_update_post( $post_data );

    // Update meta fields
    $meta_fields = ['_dsa_lesson_date' => 'sanitize_text_field', '_dsa_lesson_start_time' => 'sanitize_text_field', '_dsa_lesson_student1_id' => 'absint', '_dsa_lesson_student2_id' => 'absint', '_dsa_lesson_teacher_id' => 'absint' ];
    foreach ( $meta_fields as $meta_key => $callback ) {
        $post_key = str_replace( '_dsa_', 'dsa_', $meta_key );
        if ( isset( $_POST[ $post_key ] ) ) { 
            update_post_meta( $lesson_id, $meta_key, call_user_func( $callback, wp_unslash( $_POST[ $post_key ] ) ) );
        }
    }

    // Update practiced figures
    if ( isset( $_POST['dsa_practiced_figures'] ) && is_array( $_POST['dsa_practiced_figures'] ) ) {
        $sanitized_figure_ids = array_map( 'absint', $_POST['dsa_practiced_figures'] );
        update_post_meta( $lesson_id, '_dsa_practiced_figure_ids', $sanitized_figure_ids );
    } else {
        delete_post_meta( $lesson_id, '_dsa_practiced_figure_ids' );
    }

    wp_send_json_success( ['message' => 'Private lesson updated successfully.'] );
}