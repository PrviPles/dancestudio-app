<?php
/**
 * AJAX Handlers for Calendar functions.
 *
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
            'post_type' => 'dsa_enroll_record',
            'post_status' => 'publish',
            'post_parent' => $group_id,
            'posts_per_page' => -1,
        ]);

        foreach( $active_enrollments as $record ) {
            $student_user = get_userdata( $record->post_author );
            if ( $student_user ) {
                $enrolled_students[] = $student_user;
            }
        }
        
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
        if( !empty($enrolled_students) ) {
            foreach ($enrolled_students as $student) {
                if ( isset($saved_attendance[$student->ID]['attended']) && $saved_attendance[$student->ID]['attended'] == '1' ) {
                    $present_student_count++;
                }
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
        $attendance_data = isset( $_POST['attendance_data'] ) && is_array( $_POST['attendance_data'] ) ? $_POST['attendance_data'] : [];
        if ( $class_id === 0 ) { wp_send_json_error( ['message' => 'Invalid Class ID.'], 400 ); }
        $sanitized_data = [];
        foreach ( $attendance_data as $student_id => $data ) {
            $sanitized_data[ absint($student_id) ] = ['attended' => isset( $data['attended'] ) && $data['attended'] === '1' ? 1 : 0, 'remarks'  => isset( $data['remarks'] ) ? sanitize_text_field( wp_unslash( $data['remarks'] ) ) : ''];
        }
        if ( ! empty( $sanitized_data ) ) {
            update_post_meta( $class_id, '_dsa_class_attendance', $sanitized_data );
            wp_send_json_success( ['message' => 'Attendance saved successfully!'] );
        } else {
            delete_post_meta( $class_id, '_dsa_class_attendance' );
            wp_send_json_error( ['message' => 'No attendance data received.'] );
        }
    }
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
        $post_data = ['post_type' => 'dsa_private_lesson', 'post_title' => sanitize_text_field( $_POST['dsa_lesson_title'] ), 'post_content' => isset( $_POST['dsa_lesson_notes'] ) ? sanitize_textarea_field( $_POST['dsa_lesson_notes'] ) : '', 'post_status'  => 'publish', 'post_author'  => get_current_user_id()];
        $new_post_id = wp_insert_post( $post_data, true );
        if ( is_wp_error( $new_post_id ) ) { wp_send_json_error( ['message' => 'Error creating lesson: ' . $new_post_id->get_error_message()] );}
        $meta_fields = ['_dsa_lesson_date' => 'sanitize_text_field', '_dsa_lesson_start_time' => 'sanitize_text_field', '_dsa_lesson_student1_id' => 'absint', '_dsa_lesson_student2_id' => 'absint', '_dsa_lesson_teacher_id' => 'absint', '_dsa_lesson_order_id' => 'absint'];
        foreach ( $meta_fields as $meta_key => $callback ) {
            $post_key = str_replace( '_dsa_', 'dsa_', $meta_key );
            if ( isset( $_POST[ $post_key ] ) ) { update_post_meta( $new_post_id, $meta_key, call_user_func( $callback, wp_unslash( $_POST[ $post_key ] ) ) ); }
        }
        wp_send_json_success( ['message' => 'Private lesson added successfully!'] );
    }
}

/**
 * Handles updating an existing group class via AJAX
 */
add_action( 'wp_ajax_dsa_update_class_session_ajax', 'dsa_update_class_session_ajax_handler');
if ( ! function_exists('dsa_update_class_session_ajax_handler') ) {
    function dsa_update_class_session_ajax_handler() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dsa_update_class_nonce' ) ) { wp_send_json_error( ['message' => 'Nonce verification failed.'], 403 ); }
        if ( ! current_user_can( 'edit_posts' ) ) { wp_send_json_error( ['message' => 'You do not have permission to edit classes.'], 403 ); }
        $class_id = isset($_POST['dsa_class_id']) ? absint($_POST['dsa_class_id']) : 0;
        if ( $class_id === 0 ) { wp_send_json_error(['message' => 'Invalid Class ID.'], 400); }
        if ( empty( $_POST['dsa_class_label'] ) ) { wp_send_json_error( ['message' => 'Class Title is required.'], 400 ); }
        $post_data = ['ID' => $class_id, 'post_title' => sanitize_text_field( $_POST['dsa_class_label'] ), 'post_content' => isset( $_POST['dsa_class_notes'] ) ? sanitize_textarea_field( $_POST['dsa_class_notes'] ) : ''];
        $result = wp_update_post( $post_data, true );
        if ( is_wp_error( $result ) ) { wp_send_json_error( ['message' => 'Error updating class: ' . $result->get_error_message()] ); }
        if ( isset( $_POST['dsa_class_date'] ) ) { update_post_meta( $class_id, '_dsa_class_date', sanitize_text_field( $_POST['dsa_class_date'] ) ); }
        if ( isset( $_POST['dsa_class_start_time'] ) ) { update_post_meta( $class_id, '_dsa_class_start_time', sanitize_text_field( $_POST['dsa_class_start_time'] ) ); }
        if ( isset( $_POST['dsa_class_group_id'] ) ) { update_post_meta( $class_id, '_dsa_class_group_id', absint( $_POST['dsa_class_group_id'] ) ); }
        wp_send_json_success( ['message' => 'Class updated successfully!'] );
    }
}

/**
 * AJAX handler to fetch all events for the main admin calendar.
 */
add_action( 'wp_ajax_dsa_get_admin_calendar_events', 'dsa_get_admin_calendar_events_handler' );
function dsa_get_admin_calendar_events_handler() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'dsa_get_admin_calendar_events_nonce' ) ) {
        wp_send_json_error( 'Invalid security token.', 401 );
    }

    $events = [];
            
    // Query 1: Private Lessons
    $private_lessons = new WP_Query(['post_type' => 'dsa_private_lesson', 'posts_per_page' => -1, 'post_status' => 'publish']);
    if ($private_lessons->have_posts()) {
        while ($private_lessons->have_posts()) {
            $private_lessons->the_post();
            $s1_id = get_post_meta(get_the_ID(),'_dsa_lesson_student1_id',true);
            $s2_id = get_post_meta(get_the_ID(),'_dsa_lesson_student2_id',true);
            $t_id = get_post_meta(get_the_ID(),'_dsa_lesson_teacher_id',true);
            $s1_name = $s1_id ? get_the_author_meta('display_name', $s1_id) : '';
            $s2_name = $s2_id ? get_the_author_meta('display_name', $s2_id) : '';
            $event_title = get_the_title() . ' (' . $s1_name . ($s2_name ? ' & ' . $s2_name : '') . ')';
            $events[] = ['id' => 'private_' . get_the_ID(), 'title' => esc_js($event_title), 'start' => get_post_meta(get_the_ID(),'_dsa_lesson_date',true) . 'T' . get_post_meta(get_the_ID(),'_dsa_lesson_start_time',true), 'end' => get_post_meta(get_the_ID(),'_dsa_lesson_date',true) . 'T' . dsa_get_lesson_end_time(get_post_meta(get_the_ID(),'_dsa_lesson_date',true), get_post_meta(get_the_ID(),'_dsa_lesson_start_time',true)), 'backgroundColor' => '#46a546', 'borderColor' => '#46a546', 'url' => get_edit_post_link(get_the_ID()), 'extendedProps' => ['internalType'  => 'private_lesson', 'student1Name'  => $s1_name, 'student2Name'  => $s2_name, 'teacherName' => $t_id ? get_the_author_meta('display_name', $t_id) : '', 'topicNotes' => get_the_content()]];
        }
    }
    wp_reset_postdata();

    // Query 2: Group Classes
    $group_classes = new WP_Query(['post_type' => 'dsa_group_class', 'posts_per_page' => -1, 'post_status' => 'publish']);
    if ($group_classes->have_posts()) {
        while ($group_classes->have_posts()) {
            $group_classes->the_post();
            $group_id = get_post_meta(get_the_ID(), '_dsa_class_group_id', true);
            $events[] = ['id' => 'group_' . get_the_ID(), 'title' => esc_js(get_the_title()), 'start' => get_post_meta(get_the_ID(),'_dsa_class_date',true) . 'T' . get_post_meta(get_the_ID(),'_dsa_class_start_time',true), 'end' => get_post_meta(get_the_ID(),'_dsa_class_date',true) . 'T' . get_post_meta(get_the_ID(),'_dsa_class_end_time',true), 'backgroundColor' => '#3a87ad', 'borderColor' => '#3a87ad', 'url' => get_edit_post_link(get_the_ID()), 'extendedProps' => ['internalType'  => 'group_class', 'classId' => get_the_ID(), 'groupId' => $group_id, 'groupName' => get_the_title($group_id), 'danceStyle' => get_post_meta(get_the_ID(), '_dsa_class_dance_style', true), 'notes' => get_the_content()]];
        }
    }
    wp_reset_postdata();
    
    // Query 3: Birthdays
    $students_with_bdays = get_users(['role__in' => ['student', 'subscriber'], 'meta_key' => '_dsa_user_birth_date', 'meta_compare' => 'EXISTS']);
    foreach($students_with_bdays as $student){
        $bday_str=get_user_meta($student->ID,'_dsa_user_birth_date',true);
        if(!empty($bday_str) && strtotime($bday_str)){
            $bday_month_day=date('m-d',strtotime($bday_str));
            $current_year=(int)date('Y');
            $events[] = ['id' => 'bday_' . $student->ID, 'title' => '🎂 ' . esc_js($student->display_name), 'start' => $current_year . '-' . $bday_month_day, 'allDay' => true, 'backgroundColor' => '#f89406', 'borderColor' => '#f89406', 'extendedProps' => ['internalType' => 'birthday', 'userName' => esc_js($student->display_name), 'age' => dsa_calculate_age($bday_str)]];
        }
    }

    // Query 4: Holidays
    $holidays_query = new WP_Query(['post_type' => 'dsa_holiday', 'posts_per_page' => -1]);
    if ($holidays_query->have_posts()) {
        while ($holidays_query->have_posts()) {
            $holidays_query->the_post();
            $holiday_date = get_post_meta(get_the_ID(), '_dsa_holiday_date', true);
            if ( ! empty($holiday_date) ) {
                $events[] = [
                    'id'    => 'holiday_' . get_the_ID(),
                    'title' => '🚫 ' . esc_js(get_the_title()),
                    'start' => $holiday_date,
                    'allDay' => true,
                    'backgroundColor' => '#d9534f',
                    'borderColor' => '#d9534f',
                    'url'   => get_edit_post_link(get_the_ID()),
                    'extendedProps' => ['internalType'  => 'holiday']
                ];
            }
        }
    }
    wp_reset_postdata();

    wp_send_json_success( $events );
}