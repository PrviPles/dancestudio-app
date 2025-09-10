<?php
/**
 * Functions for calculating and retrieving statistics.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

function dsa_get_studio_statistics_data( $filters = [] ) {
    $year = isset($filters['year']) ? absint($filters['year']) : date('Y');
    $group_id = isset($filters['group_id']) ? absint($filters['group_id']) : 0;
    $teacher_ids = isset($filters['teacher_ids']) && is_array($filters['teacher_ids']) ? array_map('absint', $filters['teacher_ids']) : [];

    $all_classes = [];

    // Query 1: Get Group Classes
    $group_class_args = [
        'post_type' => 'dsa_group_class',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => [
            'relation' => 'AND',
            ['key' => '_dsa_class_date', 'compare' => 'BETWEEN', 'value' => [$year . '-01-01', $year . '-12-31'], 'type' => 'DATE']
        ]
    ];
    if ( $group_id > 0 ) {
        $group_class_args['meta_query'][] = ['key' => '_dsa_class_group_id', 'value' => $group_id];
    }
    if ( ! empty($teacher_ids) ) {
        $group_class_args['meta_query'][] = ['key' => '_dsa_primary_teacher_id', 'value' => $teacher_ids, 'compare' => 'IN'];
    }
    $group_classes_query = new WP_Query($group_class_args);
    $all_classes = array_merge($all_classes, $group_classes_query->get_posts());

    // Query 2: Get Private Lessons (only if no specific group is selected)
    if ( $group_id === 0 ) {
        $private_lesson_args = [
            'post_type' => 'dsa_private_lesson',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                ['key' => '_dsa_lesson_date', 'compare' => 'BETWEEN', 'value' => [$year . '-01-01', $year . '-12-31'], 'type' => 'DATE']
            ]
        ];
        if ( ! empty($teacher_ids) ) {
            $private_lesson_args['meta_query'][] = ['key' => '_dsa_lesson_teacher_id', 'value' => $teacher_ids, 'compare' => 'IN'];
        }
        $private_lessons_query = new WP_Query($private_lesson_args);
        $all_classes = array_merge($all_classes, $private_lessons_query->get_posts());
    }

    $total_lessons = count($all_classes);
    $unique_students = [];
    $student_hours = 0;
    $day_counts = array_fill(0, 7, 0);
    $group_attendance = [];

    foreach( $all_classes as $class ) {
        $class_date_str = get_post_meta($class->ID, '_dsa_class_date', true) ?: get_post_meta($class->ID, '_dsa_lesson_date', true);
        $start_time_str = get_post_meta($class->ID, '_dsa_class_start_time', true) ?: get_post_meta($class->ID, '_dsa_lesson_start_time', true);
        $end_time_str = get_post_meta($class->ID, '_dsa_class_end_time', true) ?: get_post_meta($class->ID, '_dsa_lesson_start_time', true);
        if (empty($class_date_str)) continue;

        $class_day = date('w', strtotime($class_date_str));
        $day_counts[$class_day]++;

        $attendees = [];
        if ($class->post_type === 'dsa_group_class') {
            $attendance_data = get_post_meta($class->ID, '_dsa_class_attendance', true);
            if(is_array($attendance_data)){
                foreach($attendance_data as $student_id => $data){
                    if(!empty($data['attended'])) $attendees[] = $student_id;
                }
            }
            $class_group_id = get_post_meta($class->ID, '_dsa_class_group_id', true);
            if ($class_group_id && !isset($group_attendance[$class_group_id])) $group_attendance[$class_group_id] = 0;
            if ($class_group_id) $group_attendance[$class_group_id] += count($attendees);
        } else {
            $s1 = get_post_meta($class->ID, '_dsa_lesson_student1_id', true);
            $s2 = get_post_meta($class->ID, '_dsa_lesson_student2_id', true);
            if($s1) $attendees[] = $s1;
            if($s2) $attendees[] = $s2;
        }

        $unique_students = array_merge($unique_students, $attendees);
        if($start_time_str && $end_time_str){
            $duration = (strtotime($end_time_str) - strtotime($start_time_str)) / 3600;
            $student_hours += $duration * count($attendees);
        }
    }

    $busiest_day_index = !empty($all_classes) ? array_keys($day_counts, max($day_counts))[0] : '-';
    $days_of_week = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $most_popular_group_id = !empty($group_attendance) ? array_keys($group_attendance, max($group_attendance))[0] : 0;
    
    $monthly_data = array_fill(1, 12, 0);
    foreach($all_classes as $class){
        $class_date_str = get_post_meta($class->ID, '_dsa_class_date', true) ?: get_post_meta($class->ID, '_dsa_lesson_date', true);
        if (empty($class_date_str)) continue;
        $month = (int)date('n', strtotime($class_date_str));
        if($month > 0) $monthly_data[$month]++;
    }

    return [
        'total_lessons' => $total_lessons,
        'unique_students' => count(array_unique($unique_students)),
        'student_hours' => round($student_hours, 1),
        'busiest_day' => $days_of_week[$busiest_day_index] ?? '-',
        'most_popular_group' => $most_popular_group_id ? get_the_title($most_popular_group_id) : 'N/A',
        'chart_data' => array_values($monthly_data),
    ];
}

/**
 * Gets the count of new students who registered this month.
 */
function dsa_get_new_students_this_month_count() {
    $today = new DateTime('today');
    $args = [
        'role__in' => ['student', 'subscriber'],
        'date_query' => [
            [
                'year'  => $today->format('Y'),
                'month' => $today->format('m'),
            ],
        ],
        'fields' => 'ID',
    ];
    $user_query = new WP_User_Query($args);
    return $user_query->get_total();
}

/**
 * Gets the count of private lessons scheduled in the next 7 days.
 */
function dsa_get_upcoming_lessons_count() {
    $today = date('Y-m-d');
    $seven_days_from_now = date('Y-m-d', strtotime('+7 days'));

    $args = [
        'post_type' => 'dsa_private_lesson',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => '_dsa_lesson_date',
                'value' => [$today, $seven_days_from_now],
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            ]
        ]
    ];
    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Gets the count of all students with an active enrollment in any group.
 */
function dsa_get_total_active_students_count() {
    $active_enrollments = get_posts([
        'post_type'      => 'dsa_enroll_record',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);

    if (empty($active_enrollments)) {
        return 0;
    }
    
    // --- CORRECTED LOGIC ---
    // First, get an array of just the student IDs (the post_author) from the enrollment objects.
    $student_ids = wp_list_pluck( $active_enrollments, 'post_author' );
    
    // Then, get a count of the unique student IDs.
    $unique_student_ids = array_unique($student_ids);
    return count($unique_student_ids);
}