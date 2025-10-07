<?php
/**
 * AJAX Handlers for fetching data for the Calendar.
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Fetches all details for a single private lesson to populate an edit modal.
 */
add_action( 'wp_ajax_dsa_get_private_lesson_details', 'dsa_get_private_lesson_details_handler' );
function dsa_get_private_lesson_details_handler() {
    check_ajax_referer( 'dsa_get_lesson_details_nonce', 'nonce' );

    $lesson_id = isset($_POST['lesson_id']) ? absint($_POST['lesson_id']) : 0;
    if ( ! $lesson_id || ! current_user_can('edit_post', $lesson_id) ) {
        wp_send_json_error(['message' => 'Invalid lesson or insufficient permissions.']);
    }

    $lesson = get_post($lesson_id);
    $practiced_figures = get_post_meta($lesson_id, '_dsa_practiced_figure_ids', true);
    $dance_term_id = null;

    // If figures were practiced, find the dance they belong to so we can pre-select the dropdown.
    if ( ! empty($practiced_figures) && is_array($practiced_figures) ) {
        // Get the terms for the first figure in the list.
        $terms = get_the_terms( $practiced_figures[0], 'dsa_dance' );
        if ( ! is_wp_error($terms) && ! empty($terms) ) {
            $dance_term_id = $terms[0]->term_id;
        }
    }

    // Get student names to populate the Select2 dropdowns initially
    $student1_id = get_post_meta($lesson_id, '_dsa_lesson_student1_id', true);
    $student2_id = get_post_meta($lesson_id, '_dsa_lesson_student2_id', true);
    $student1_name = $student1_id ? get_the_author_meta('display_name', $student1_id) : '';
    $student2_name = $student2_id ? get_the_author_meta('display_name', $student2_id) : '';

    $data = [
        'title'             => $lesson->post_title,
        'notes'             => $lesson->post_content,
        'date'              => get_post_meta($lesson_id, '_dsa_lesson_date', true),
        'start_time'        => get_post_meta($lesson_id, '_dsa_lesson_start_time', true),
        'student1_id'       => $student1_id,
        'student1_name'     => $student1_name,
        'student2_id'       => $student2_id,
        'student2_name'     => $student2_name,
        'teacher_id'        => get_post_meta($lesson_id, '_dsa_lesson_teacher_id', true),
        'practiced_dance'   => $dance_term_id,
        'practiced_figures' => is_array($practiced_figures) ? $practiced_figures : [],
    ];

    wp_send_json_success($data);
}