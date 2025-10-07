<?php
/**
 * DanceStudio App Group Class CPT Meta Boxes
 * UPDATED: Handles new attendance statuses and saving logic.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_dsa_group_class', 'dsa_add_class_meta_boxes' );
function dsa_add_class_meta_boxes() {
    add_meta_box(
        'dsa_class_details_mb',
        __( 'Class Details', 'dancestudio-app' ),
        'dsa_render_class_details_mb',
        'dsa_group_class',
        'normal',
        'high'
    );
    add_meta_box(
        'dsa_class_attendance_mb',
        __( 'Student Attendance', 'dancestudio-app' ),
        'dsa_render_class_attendance_mb',
        'dsa_group_class',
        'normal',
        'default'
    );
}

function dsa_render_class_details_mb( $post ) {
    wp_nonce_field( 'dsa_save_class_meta_action', 'dsa_class_meta_nonce' );

    $class_date = get_post_meta( $post->ID, '_dsa_class_date', true );
    $start_time = get_post_meta( $post->ID, '_dsa_class_start_time', true );
    $end_time = get_post_meta( $post->ID, '_dsa_class_end_time', true );
    $group_id = get_post_meta( $post->ID, '_dsa_class_group_id', true );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="dsa_class_date"><?php _e('Date', 'dancestudio-app'); ?></label></th>
            <td><input type="date" id="dsa_class_date" name="dsa_class_date" value="<?php echo esc_attr( $class_date ); ?>" required /></td>
        </tr>
        <tr>
            <th><label for="dsa_class_start_time"><?php _e('Time', 'dancestudio-app'); ?></label></th>
            <td>
                <input type="time" id="dsa_class_start_time" name="dsa_class_start_time" value="<?php echo esc_attr( $start_time ); ?>" required /> to
                <input type="time" id="dsa_class_end_time" name="dsa_class_end_time" value="<?php echo esc_attr( $end_time ); ?>" />
            </td>
        </tr>
        <tr>
            <th><label for="dsa_class_group_id_selector"><?php _e('Group', 'dancestudio-app'); ?></label></th>
            <td>
                <?php
                $all_groups = get_posts(['post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'post_title', 'order' => 'ASC']);
                echo '<select name="dsa_class_group_id" id="dsa_class_group_id_selector">';
                echo '<option value="0">' . esc_html__('-- Select Group --', 'dancestudio-app') . '</option>';
                if ( ! empty($all_groups) ) {
                    foreach ( $all_groups as $group_post ) {
                        echo '<option value="' . esc_attr( $group_post->ID ) . '" ' . selected( $group_id, $group_post->ID, false ) . '>';
                        echo esc_html( $group_post->post_title );
                        echo '</option>';
                    }
                }
                echo '</select>';
                ?>
            </td>
        </tr>
        <tr>
            <th><label><?php _e('Choreographies Practiced', 'dancestudio-app'); ?></label></th>
            <td>
                <div id="dsa-choreography-checklist-wrapper">
                    <?php
                    if ( ! $group_id ) {
                        echo '<em>' . esc_html__('Please save a group to see available choreographies.', 'dancestudio-app') . '</em>';
                    } else {
                        $all_choreos_query = new WP_Query([
                            'post_type' => 'dsa_choreography',
                            'posts_per_page' => -1,
                            'meta_query' => [
                                [
                                    'key' => '_dsa_assigned_group_ids',
                                    'value' => '"' . $group_id . '"',
                                    'compare' => 'LIKE'
                                ]
                            ]
                        ]);
                        
                        if (!$all_choreos_query->have_posts()) {
                             echo '<em>' . esc_html__('No choreographies are assigned to this group.', 'dancestudio-app') . '</em>';
                        } else {
                            $practiced_choreos = get_post_meta($post->ID, '_dsa_practiced_choreography_ids', true);
                            if (!is_array($practiced_choreos)) {
                                $practiced_choreos = [];
                            }
                            while($all_choreos_query->have_posts()){
                                $all_choreos_query->the_post();
                                $choreo_id = get_the_ID();
                                $is_checked = in_array($choreo_id, $practiced_choreos);
                                $field_id = 'dsa-choreo-' . $choreo_id;
                                echo '<div style="margin-bottom: 5px;">';
                                echo '<input type="checkbox" name="dsa_choreographies[]" value="' . esc_attr($choreo_id) . '" id="' . esc_attr($field_id) . '" ' . checked($is_checked, true, false) . '>';
                                echo '<label for="' . esc_attr($field_id) . '" style="display: inline-block; margin-left: 5px;">' . get_the_title() . '</label>';
                                echo '</div>';
                            }
                        }
                        wp_reset_postdata();
                    }
                    ?>
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="dsa_class_notes"><?php _e('Lesson Remarks', 'dancestudio-app'); ?></label></th>
            <td>
                <textarea name="dsa_class_notes" id="dsa_class_notes" class="widefat" rows="5"><?php echo esc_textarea($post->post_content); ?></textarea>
                <p class="description"><?php _e('General notes about this specific lesson.', 'dancestudio-app'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

function dsa_render_class_attendance_mb( $post ) {
    $group_id = get_post_meta($post->ID, '_dsa_class_group_id', true);
    if ( ! $group_id ) {
        echo '<p>' . esc_html__('Please select and save a group above to manage attendance.', 'dancestudio-app') . '</p>';
        return;
    }

    $enrolled_students = [];
    $enrollment_records = get_posts(['post_type' => 'dsa_enroll_record', 'post_status' => 'publish', 'post_parent' => $group_id, 'posts_per_page' => -1]);
    foreach ($enrollment_records as $record) {
        $student_user = get_userdata($record->post_author);
        if ($student_user) {
            $enrolled_students[] = $student_user;
        }
    }

    if (empty($enrolled_students)) {
        echo '<p>' . esc_html__('No students are currently enrolled in this group.', 'dancestudio-app') . '</p>';
        return;
    }

    $saved_attendance = get_post_meta($post->ID, '_dsa_class_attendance', true);
    if (!is_array($saved_attendance)) {
        $saved_attendance = [];
    }

    $attendance_statuses = [
        'present'    => __('Present', 'dancestudio-app'),
        'absent'     => __('Absent', 'dancestudio-app'),
        'incomplete' => __('Incomplete', 'dancestudio-app'),
        'excused'    => __('Excused Absence', 'dancestudio-app'),
    ];
    ?>
    <table class="wp-list-table widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Student Name', 'dancestudio-app'); ?></th>
                <th style="width: 180px;"><?php esc_html_e('Status', 'dancestudio-app'); ?></th>
                <th><?php esc_html_e('Remarks', 'dancestudio-app'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($enrolled_students as $student) :
                $student_attendance = $saved_attendance[$student->ID] ?? [];
                
                $current_status = $student_attendance['status'] ?? 'absent';
                if ( empty($student_attendance['status']) && isset($student_attendance['attended']) && $student_attendance['attended'] == '1' ) {
                    $current_status = 'present';
                }

                $remarks = $student_attendance['remarks'] ?? '';
            ?>
            <tr>
                <td><?php echo esc_html($student->display_name); ?></td>
                <td>
                    <select name="dsa_attendance[<?php echo esc_attr($student->ID); ?>][status]" class="widefat">
                        <?php foreach ($attendance_statuses as $status_key => $status_label) : ?>
                            <option value="<?php echo esc_attr($status_key); ?>" <?php selected($current_status, $status_key); ?>>
                                <?php echo esc_html($status_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="text" name="dsa_attendance[<?php echo esc_attr($student->ID); ?>][remarks]" value="<?php echo esc_attr($remarks); ?>" class="widefat" placeholder="<?php esc_attr_e('e.g., Arrived late', 'dancestudio-app'); ?>">
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/**
 * CONSOLIDATED SAVE FUNCTION
 * This function now handles all meta saving for the 'dsa_group_class' post type.
 * It combines logic from helper-functions.php and the previous save function here.
 */
add_action( 'save_post_dsa_group_class', 'dsa_save_consolidated_class_meta', 10, 2 );
function dsa_save_consolidated_class_meta( $post_id, $post ) {
    // 1. Security Checks
    if ( ! isset( $_POST['dsa_class_meta_nonce'] ) || ! wp_verify_nonce( $_POST['dsa_class_meta_nonce'], 'dsa_save_class_meta_action' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // 2. Save Simple Meta Fields
    $fields_to_save = [
        '_dsa_class_date'       => 'sanitize_text_field',
        '_dsa_class_start_time' => 'sanitize_text_field',
        '_dsa_class_end_time'   => 'sanitize_text_field',
        '_dsa_class_group_id'   => 'absint',
    ];
    foreach ( $fields_to_save as $meta_key => $sanitize_cb ) {
        $post_key = str_replace( '_dsa_', 'dsa_', $meta_key );
        if ( isset( $_POST[ $post_key ] ) ) {
            $value = call_user_func( $sanitize_cb, wp_unslash( $_POST[ $post_key ] ) );
            update_post_meta( $post_id, $meta_key, $value );
        }
    }

    // 3. Save Post Content (Notes)
    if ( isset( $_POST['dsa_class_notes'] ) && $post->post_content !== $_POST['dsa_class_notes'] ) {
        remove_action( 'save_post_dsa_group_class', 'dsa_save_consolidated_class_meta', 10 );
        wp_update_post([
            'ID'           => $post_id,
            'post_content' => sanitize_textarea_field($_POST['dsa_class_notes'])
        ]);
        add_action( 'save_post_dsa_group_class', 'dsa_save_consolidated_class_meta', 10, 2 );
    }
    
    // 4. Save Practiced Choreographies
    if ( isset( $_POST['dsa_choreographies'] ) && is_array( $_POST['dsa_choreographies'] ) ) {
        $sane_choreo_ids = array_map( 'absint', $_POST['dsa_choreographies'] );
        update_post_meta($post_id, '_dsa_practiced_choreography_ids', $sane_choreo_ids);
    } else {
        delete_post_meta($post_id, '_dsa_practiced_choreography_ids');
    }

    // 5. Save Attendance Data & Apply Subscription Logic (Merged from helper-functions.php)
    if ( isset( $_POST['dsa_attendance'] ) && is_array( $_POST['dsa_attendance'] ) ) {
        $group_id = get_post_meta($post_id, '_dsa_class_group_id', true);
        $class_date = get_post_meta($post_id, '_dsa_class_date', true);

        $previous_attendance = get_post_meta($post_id, '_dsa_class_attendance', true);
        if (!is_array($previous_attendance)) $previous_attendance = [];

        $sanitized_attendance = [];
        $allowed_statuses = ['present', 'absent', 'incomplete', 'excused'];
        $statuses_that_count = ['present', 'incomplete'];

        foreach ( $_POST['dsa_attendance'] as $student_id => $data ) {
            $sane_id = absint($student_id);
            if ( $sane_id > 0 && isset($data['status']) ) {
                $status = sanitize_key($data['status']);
                $new_status = in_array($status, $allowed_statuses) ? $status : 'absent';

                $sanitized_attendance[$sane_id] = [
                    'status'  => $new_status,
                    'remarks' => isset($data['remarks']) ? sanitize_text_field($data['remarks']) : '',
                ];

                $enrollment_record_id = dsa_get_active_enrollment_record($sane_id, $group_id);
                if ( !$enrollment_record_id ) {
                    continue; // Skip if student isn't enrolled
                }

                $previous_status = $previous_attendance[$sane_id]['status'] ?? 'absent';
                $new_status_counts = in_array($new_status, $statuses_that_count);
                $previous_status_counted = in_array($previous_status, $statuses_that_count);
                $lesson_count = (int) get_post_meta($enrollment_record_id, '_dsa_lessons_attended_count', true);

                if ( $previous_status_counted && !$new_status_counts ) {
                    update_post_meta($enrollment_record_id, '_dsa_lessons_attended_count', max(0, $lesson_count - 1));
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
        update_post_meta( $post_id, '_dsa_class_attendance', $sanitized_attendance );
    }
}