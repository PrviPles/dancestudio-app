<?php
/**
 * Functions for managing Student Enrollments.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Checks if a student has an active enrollment in a specific group.
 *
 * @param int $student_id The user ID of the student.
 * @param int $group_id   The post ID of the group.
 * @return int|false The ID of the active enrollment record, or false if none exists.
 */
function dsa_get_active_enrollment_record( $student_id, $group_id ) {
    $enrollments = get_posts([
        'post_type'      => 'dsa_enroll_record', // <-- FIXED
        'post_status'    => 'publish',
        'author'         => absint( $student_id ),
        'post_parent'    => absint( $group_id ),
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    return ! empty( $enrollments ) ? $enrollments[0] : false;
}

/**
 * Enrolls a student in a dance group by creating a new enrollment record.
 *
 * @param int    $student_id The user ID of the student.
 * @param int    $group_id   The post ID of the group.
 * @param string $enroll_date The enrollment date in Y-m-d H:i:s format. Defaults to today.
 * @return int|WP_Error The new enrollment record post ID on success, or WP_Error on failure.
 */
function dsa_enroll_student_in_group( $student_id, $group_id, $enroll_date = '' ) {
    $student_id = absint( $student_id );
    $group_id   = absint( $group_id );

    $student = get_userdata( $student_id );
    $group   = get_post( $group_id );

    if ( ! $student || ! $group || 'dsa_group' !== $group->post_type ) {
        return new WP_Error( 'invalid_data', 'Invalid student or group ID provided.' );
    }

    if ( dsa_get_active_enrollment_record( $student_id, $group_id ) ) {
        return new WP_Error( 'already_enrolled', 'This student is already actively enrolled in this group.' );
    }

    $enrollment_data = [
        'post_type'   => 'dsa_enroll_record', // <-- FIXED
        'post_title'  => sprintf( 'Enrollment: %s - %s', $student->display_name, $group->post_title ),
        'post_author' => $student_id,
        'post_parent' => $group_id,
        'post_status' => 'publish',
    ];

    $record_id = wp_insert_post( $enrollment_data, true );

    if ( is_wp_error( $record_id ) ) {
        return $record_id;
    }

    $date_to_store = ! empty( $enroll_date ) ? $enroll_date : current_time( 'mysql' );
    update_post_meta( $record_id, '_dsa_enrollment_date', $date_to_store );

    return $record_id;
}

/**
 * Drops a student out of a dance group by updating their active enrollment record.
 *
 * @param int    $student_id The user ID of the student.
 * @param int    $group_id   The post ID of the group.
 * @param string $dropout_date The dropout date in Y-m-d H:i:s format. Defaults to today.
 * @return int|WP_Error The updated enrollment record post ID on success, or WP_Error on failure.
 */
function dsa_dropout_student_from_group( $student_id, $group_id, $dropout_date = '' ) {
    $student_id = absint( $student_id );
    $group_id   = absint( $group_id );

    $record_id = dsa_get_active_enrollment_record( $student_id, $group_id );

    if ( ! $record_id ) {
        return new WP_Error( 'not_enrolled', 'This student does not have an active enrollment in this group to drop out from.' );
    }

    $update_data = [
        'ID'          => $record_id,
        'post_status' => 'dropped_out',
    ];

    $updated_id = wp_update_post( $update_data, true );
    
    if ( is_wp_error( $updated_id ) ) {
        return $updated_id;
    }
    
    $date_to_store = ! empty( $dropout_date ) ? $dropout_date : current_time( 'mysql' );
    update_post_meta( $record_id, '_dsa_dropout_date', $date_to_store );

    return $updated_id;
}