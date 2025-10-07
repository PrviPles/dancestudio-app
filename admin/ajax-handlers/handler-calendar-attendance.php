<?php
/**
 * AJAX Handlers for Calendar Attendance functions.
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles fetching student and attendance data for a specific class.
 */
add_action( 'wp_ajax_dsa_get_class_attendance_data', 'dsa_get_class_attendance_data_handler' );
if( ! function_exists('dsa_get_class_attendance_data_handler') ) {
    function dsa_get_class_attendance_data_handler() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dsa_get_class_attendance_nonce' ) ) { wp_send_json_error( ['message' => 'Invalid security token.'], 401 ); }

        $class_id = isset( $_POST['class_id'] ) ? absint( $_POST['class_id'] ) : 0;
        $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
        if ( $class_id === 0 || $group_id === 0 ) { wp_send_json_error( ['message' => 'Missing Class or Group ID.'], 400 );}

        $enrolled_students = [];
        $active_enrollments = get_posts([
            'post_type' => 'dsa_enroll_record', 'post_status' => 'publish',
            'post_parent' => $group_id, 'posts_per_page' => -1,
        ]);
        foreach( $active_enrollments as $record ) {
            $student_user = get_userdata( $record->post_author );
            if ( $student_user ) $enrolled_students[] = $student_user;
        }
        usort($enrolled_students, function($a, $b) { return strnatcasecmp($a->last_name, $b->last_name); });
        $total_student_count = count($enrolled_students);
        $saved_attendance = get_post_meta( $class_id, '_dsa_class_attendance', true );
        if ( ! is_array( $saved_attendance ) ) { $saved_attendance = []; }
        $all_students_data = [];
        foreach ($enrolled_students as $student) {
            $all_students_data[] = [
                'id'   => $student->ID,
                'name' => esc_html( trim( $student->first_name . ' ' . $student->last_name ) ?: $student->display_name ),
            ];
        }

        $present_student_count = 0;
        $statuses_that_count = ['present', 'incomplete'];
        if( !empty($saved_attendance) ) {
            foreach ($saved_attendance as $student_id => $data) {
                $status = $data['status'] ?? '';
                if ( empty($status) && isset($data['attended']) && $data['attended'] == '1' ) {
                    $status = 'present';
                }
                if ( in_array($status, $statuses_that_count) ) $present_student_count++;
            }
        }

        $percentage = ($total_student_count > 0) ? round(($present_student_count / $total_student_count) * 100) : 0;
        $summary_data = [
            'present'    => $present_student_count,
            'total'      => $total_student_count,
            'percentage' => $percentage
        ];
        wp_send_json_success([
            'summary'    => $summary_data,
            'students'   => $all_students_data,
            'attendance' => $saved_attendance
        ]);
    }
}

/**
 * Handles saving the attendance data submitted from the calendar modal.
 */
add_action( 'wp_ajax_dsa_save_class_attendance', 'dsa_save_class_attendance_handler' );
if( ! function_exists('dsa_save_class_attendance_handler') ) {
    function dsa_save_class_attendance_handler() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dsa_save_class_attendance_nonce' ) ) { wp_send_json_error( ['message' => 'Invalid security token.'], 403 );}
        if ( ! current_user_can( 'edit_posts' ) ) { wp_send_json_error( ['message' => 'You do not have permission to save attendance.'], 403 ); }

        $class_id = isset( $_POST['class_id'] ) ? absint( $_POST['class_id'] ) : 0;
        if ( $class_id === 0 ) { wp_send_json_error( ['message' => 'Invalid Class ID.'], 400 ); }

        $group_id = get_post_meta($class_id, '_dsa_class_group_id', true);
        $class_date = get_post_meta($class_id, '_dsa_class_date', true);
        $attendance_data = isset( $_POST['attendance_data'] ) && is_array( $_POST['attendance_data'] ) ? $_POST['attendance_data'] : [];

        $previous_attendance = get_post_meta($class_id, '_dsa_class_attendance', true);
        if (!is_array($previous_attendance)) $previous_attendance = [];

        $sanitized_data = [];
        $allowed_statuses = ['present', 'absent', 'incomplete', 'excused'];
        $statuses_that_count = ['present', 'incomplete'];

        foreach ( $attendance_data as $student_id => $data ) {
            $sane_id = absint($student_id);
            if ( $sane_id > 0 && isset($data['status']) ) {
                $status = sanitize_key($data['status']);
                $new_status = in_array($status, $allowed_statuses) ? $status : 'absent';

                $sanitized_data[ $sane_id ] = [
                    'status' => $new_status,
                    'remarks'  => isset( $data['remarks'] ) ? sanitize_text_field( wp_unslash( $data['remarks'] ) ) : ''
                ];

                $enrollment_record_id = dsa_get_active_enrollment_record($sane_id, $group_id);
                if ( !$enrollment_record_id ) continue;

                $previous_status = $previous_attendance[$sane_id]['status'] ?? 'absent';
                $new_status_counts = in_array($new_status, $statuses_that_count);
                $previous_status_counted = in_array($previous_status, $statuses_that_count);
                $lesson_count = (int) get_post_meta($enrollment_record_id, '_dsa_lessons_attended_count', true);

                if ( $previous_status_counted && !$new_status_counts ) {
                    update_post_meta($enrollment_record_id, '_dsa_lessons_attended_count', $lesson_count - 1);
                }
                elseif ( !$previous_status_counted && $new_status_counts ) {
                    update_post_meta($enrollment_record_id, '_dsa_lessons_attended_count', $lesson_count + 1);
                    $start_date = get_post_meta($enrollment_record_id, '_dsa_subscription_start_date', true);
                    if ( empty($start_date) ) {
                        update_post_meta($enrollment_record_id, '_dsa_subscription_start_date', $class_date);
                    }
                }
            }
        }

        if ( ! empty( $sanitized_data ) ) {
            update_post_meta( $class_id, '_dsa_class_attendance', $sanitized_data );
            wp_send_json_success( ['message' => 'Attendance saved successfully!'] );
        } else {
            update_post_meta( $class_id, '_dsa_class_attendance', [] );
            wp_send_json_success( ['message' => 'Attendance updated.'] );
        }
    }
}