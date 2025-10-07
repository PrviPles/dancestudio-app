<?php
/**
 * AJAX Handler for fetching all events for the Admin Calendar.
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
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
    $private_lessons_query = new WP_Query(['post_type' => 'dsa_private_lesson', 'posts_per_page' => -1, 'post_status' => 'publish']);
    if ($private_lessons_query->have_posts()) {
        while ($private_lessons_query->have_posts()) {
            $private_lessons_query->the_post();
            $lesson_id = get_the_ID();
            
            $s1_id = get_post_meta($lesson_id, '_dsa_lesson_student1_id', true);
            $s2_id = get_post_meta($lesson_id, '_dsa_lesson_student2_id', true);
            $t_id = get_post_meta($lesson_id, '_dsa_lesson_teacher_id', true);
            
            $s1_data = $s1_id ? get_userdata($s1_id) : null;
            $s2_data = $s2_id ? get_userdata($s2_id) : null;
            $teacher_data = $t_id ? get_userdata($t_id) : null;

            $lesson_title = get_the_title();
            $s1_name = $s1_data ? $s1_data->display_name : '';
            $s2_name = $s2_data ? $s2_data->display_name : '';
            $teacher_name = $teacher_data ? $teacher_data->display_name : '';
            
            $event_title = $lesson_title . ' (' . $s1_name . ($s2_name ? ' & ' . $s2_name : '') . ')';
            // --- FIX 2: Use html_entity_decode for better character conversion ---
            $final_title = html_entity_decode($event_title, ENT_QUOTES, 'UTF-8');

            $wedding_date = '';
            $wedding_songs = [];
            $lesson_history = [];
            $start_time = get_post_meta($lesson_id,'_dsa_lesson_start_time',true);
            $notes = get_the_content(null, false, $lesson_id);

            $figure_ids = get_post_meta($lesson_id, '_dsa_practiced_figure_ids', true);
            $figure_names = [];
            if (!empty($figure_ids) && is_array($figure_ids)) {
                foreach ($figure_ids as $figure_id) {
                    $title = get_the_title($figure_id);
                    if ($title) {
                        $figure_names[] = $title;
                    }
                }
            }

            if ($s1_id && $s2_id) {
                $wedding_date = get_user_meta($s1_id, 'dsa_wedding_date', true);
                $songs_meta = get_user_meta($s1_id, '_dsa_couple_songs', true);
                if (is_array($songs_meta)) {
                    $wedding_songs = $songs_meta;
                }

                $history_query = new WP_Query([
                    'post_type' => 'dsa_private_lesson',
                    'posts_per_page' => -1,
                    'meta_key' => '_dsa_lesson_date',
                    'orderby' => 'meta_value',
                    'order' => 'ASC',
                    'meta_query' => [
                        'relation' => 'AND',
                        [ 'key' => '_dsa_lesson_student1_id', 'value' => $s1_id ],
                        [ 'key' => '_dsa_lesson_student2_id', 'value' => $s2_id ],
                    ]
                ]);
                if ($history_query->have_posts()) {
                    $lesson_counter = 1;
                    while ($history_query->have_posts()) {
                        $history_query->the_post();
                        $history_lesson_id = get_the_ID();
                        $history_teacher_id = get_post_meta($history_lesson_id, '_dsa_lesson_teacher_id', true);
                        $history_teacher_data = $history_teacher_id ? get_userdata($history_teacher_id) : null;
                        
                        $history_notes = get_the_content(null, false, $history_lesson_id);
                        $history_figure_ids = get_post_meta($history_lesson_id, '_dsa_practiced_figure_ids', true);
                        $history_figure_names = [];
                        if (!empty($history_figure_ids) && is_array($history_figure_ids)) {
                            foreach ($history_figure_ids as $figure_id) {
                                $fig_title = get_the_title($figure_id);
                                if ($fig_title) $history_figure_names[] = $fig_title;
                            }
                        }

                        $lesson_history[] = [
                            'id'          => $history_lesson_id,
                            'number'      => $lesson_counter,
                            'notes'       => $history_notes,
                            'figures'     => implode(', ', $history_figure_names),
                            'date'        => get_post_meta($history_lesson_id, '_dsa_lesson_date', true),
                            'teacher'     => $history_teacher_data ? $history_teacher_data->display_name : 'N/A',
                        ];
                        $lesson_counter++;
                    }
                    $lesson_history = array_reverse($lesson_history);
                }
                wp_reset_postdata();
            }

            $events[] = [
                'id' => 'private_' . $lesson_id,
                'title' => $final_title,
                'start' => get_post_meta($lesson_id,'_dsa_lesson_date',true) . 'T' . $start_time,
                'end' => get_post_meta($lesson_id,'_dsa_lesson_date',true) . 'T' . dsa_get_lesson_end_time(get_post_meta($lesson_id,'_dsa_lesson_date',true), $start_time),
                'backgroundColor' => '#46a546', 'borderColor' => '#46a546',
                'url' => get_edit_post_link($lesson_id),
                'extendedProps' => [
                    'internalType'     => 'private_lesson',
                    'student1_id'      => $s1_id,
                    'student2_id'      => $s2_id,
                    'notes'            => $notes,
                    'practicedFigures' => $figure_names,
                    'teacherName'      => $teacher_name,
                    'startTime'        => $start_time,
                    'weddingDate'      => $wedding_date,
                    'weddingSongs'     => $wedding_songs,
                    'lessonHistory'    => $lesson_history,
                ]
            ];
        }
    }
    wp_reset_postdata();

    // Query 2: Group Classes
    $group_classes = new WP_Query(['post_type' => 'dsa_group_class', 'posts_per_page' => -1, 'post_status' => 'publish']);
    if ($group_classes->have_posts()) {
        while ($group_classes->have_posts()) {
            $group_classes->the_post();
            $class_id = get_the_ID();
            $group_id = get_post_meta($class_id, '_dsa_class_group_id', true);
            
            $event_title = get_the_title();
            $group_name = get_the_title($group_id);
            
            // --- FIX 2: Use html_entity_decode for better character conversion ---
            $final_title = html_entity_decode($event_title, ENT_QUOTES, 'UTF-8');
            $final_group_name = html_entity_decode($group_name, ENT_QUOTES, 'UTF-8');
            
            $practiced_choreo_ids = get_post_meta($class_id, '_dsa_practiced_choreography_ids', true);
            if (!is_array($practiced_choreo_ids)) $practiced_choreo_ids = [];
            $available_choreos = [];
            $all_difficulties = [];
            if ($group_id) {
                $all_choreos_posts = get_posts(['post_type' => 'dsa_choreography', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                foreach ($all_choreos_posts as $choreo_post) {
                    $assigned_group_ids = get_post_meta($choreo_post->ID, '_dsa_assigned_group_ids', true);
                    if ( is_array($assigned_group_ids) && in_array($group_id, $assigned_group_ids) ) {
                        $terms = get_the_terms($choreo_post->ID, 'dsa_difficulty_level');
                        $difficulty_slug = !empty($terms) && !is_wp_error($terms) ? $terms[0]->slug : 'uncategorized';
                        
                        $available_choreos[] = [
                            'id' => $choreo_post->ID,
                            'title' => html_entity_decode($choreo_post->post_title, ENT_QUOTES, 'UTF-8'),
                            'difficulty' => $difficulty_slug
                        ];
                    }
                }
                
                $difficulty_terms = get_terms(['taxonomy' => 'dsa_difficulty_level', 'hide_empty' => false]);
                if (!is_wp_error($difficulty_terms)) {
                    $all_difficulties = $difficulty_terms;
                }
            }

            $practiced_choreo_names = [];
            if (!empty($practiced_choreo_ids)) {
                foreach ($practiced_choreo_ids as $choreo_id) {
                    $choreo_title = get_the_title($choreo_id);
                    if ($choreo_title) $practiced_choreo_names[] = html_entity_decode($choreo_title, ENT_QUOTES, 'UTF-8');
                }
            }
            $events[] = [
                'id' => 'group_' . $class_id, 'title' => $final_title,
                'start' => get_post_meta($class_id,'_dsa_class_date',true) . 'T' . get_post_meta($class_id,'_dsa_class_start_time',true),
                'end' => get_post_meta($class_id,'_dsa_class_date',true) . 'T' . get_post_meta($class_id,'_dsa_class_end_time',true),
                'backgroundColor' => '#3a87ad', 'borderColor' => '#3a87ad', 'url' => get_edit_post_link($class_id),
                'extendedProps' => [
                    'internalType'  => 'group_class', 'classId' => $class_id, 'groupId' => $group_id,
                    'groupName' => $final_group_name, 'notes' => get_the_content(),
                    'practicedChoreos' => $practiced_choreo_ids,
                    'practicedChoreoNames' => $practiced_choreo_names,
                    'availableChoreos' => $available_choreos,
                    'allDifficulties' => $all_difficulties,
                ]
            ];
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
            
            $birthday_title = '🎂 ' . html_entity_decode($student->display_name, ENT_QUOTES, 'UTF-8');
            
            $events[] = [
                'id' => 'bday_' . $student->ID,
                'title' => $birthday_title,
                'start' => $current_year . '-' . $bday_month_day,
                'allDay' => true,
                'backgroundColor' => '#f89406',
                'borderColor' => '#f89406',
                'extendedProps' => [
                    'internalType' => 'birthday',
                    'userName' => html_entity_decode($student->display_name, ENT_QUOTES, 'UTF-8'),
                    'age' => dsa_calculate_age($bday_str)
                ]
            ];
        }
    }

    // Query 4: Holidays
    $holidays_query = new WP_Query(['post_type' => 'dsa_holiday', 'posts_per_page' => -1]);
    if ($holidays_query->have_posts()) {
        while ($holidays_query->have_posts()) {
            $holidays_query->the_post();
            $holiday_date = get_post_meta(get_the_ID(), '_dsa_holiday_date', true);
            if ( ! empty($holiday_date) ) {
                $decoded_title = '🚫 ' . html_entity_decode(get_the_title(), ENT_QUOTES, 'UTF-8');

                $events[] = [
                    'id' => 'holiday_' . get_the_ID(),
                    'title' => $decoded_title,
                    'start' => $holiday_date,
                    'allDay' => true,
                    'backgroundColor' => '#d9534f',
                    'borderColor' => '#d9534f',
                    'url'   => get_edit_post_link(get_the_ID()),
                    'extendedProps' => [
                        'internalType'  => 'holiday'
                    ]
                ];
            }
        }
    }
    wp_reset_postdata();

    wp_send_json_success( $events );
}