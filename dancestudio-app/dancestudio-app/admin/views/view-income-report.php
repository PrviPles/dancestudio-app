<?php
/**
 * View: Renders the main Income Report page.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Renders the filter form and displays the report table.
 */
function dsa_render_income_report_page() {
    $start_date_filter = isset($_GET['start_date']) && !empty($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
    $end_date_filter   = isset($_GET['end_date']) && !empty($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-t');
    $student_id_filter = isset($_GET['student_id']) ? absint($_GET['student_id']) : 0;
    
    $report_data = dsa_calculate_income_report_data($start_date_filter, $end_date_filter, $student_id_filter);

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Income Report (Services Rendered)', 'dancestudio-app' ); ?></h1>
        <p><?php esc_html_e( 'Analyze income based on services rendered. This report calculates income based on attendance records and logged lessons.', 'dancestudio-app' ); ?></p>
        
        <form method="get" style="margin-top: 20px;">
            <input type="hidden" name="page" value="dsa-income-report">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <label for="dsa_filter_start_date" style="vertical-align: middle;"><?php _e('From:'); ?></label>
                    <input type="date" id="dsa_filter_start_date" name="start_date" value="<?php echo esc_attr($start_date_filter); ?>">
                    
                    <label for="dsa_filter_end_date" style="margin-left:10px; vertical-align: middle;"><?php _e('To:'); ?></label>
                    <input type="date" id="dsa_filter_end_date" name="end_date" value="<?php echo esc_attr($end_date_filter); ?>">

                    <?php
                    wp_dropdown_users([
                        'name'             => 'student_id',
                        'role__in'         => ['student', 'subscriber'],
                        'show_option_none' => __( 'All Students', 'dancestudio-app' ),
                        'option_none_value'=> '0',
                        'selected'         => $student_id_filter,
                        'show_fullname'    => true
                    ]);
                    ?>
                    <input type="submit" class="button" value="Filter">
                </div>
            </div>
        </form>

        <?php dsa_display_income_report_table($report_data); ?>
    </div>
    <?php
}

function dsa_calculate_income_report_data( $start_date, $end_date, $student_id ) {
    
    $report_items = [];
    $total_income = 0.0;

    // --- 1. Process Private Lessons ---
    $private_lesson_args = [ /* ... unchanged ... */ ];
    $private_lessons = get_posts($private_lesson_args);

    foreach ($private_lessons as $lesson) {
        // ... Logic for calculating private lesson value is unchanged ...
    }

    // --- 2. Process Group Class Attendance ---
    $group_class_args = [ /* ... unchanged ... */ ];
    $group_classes = get_posts($group_class_args);

    foreach ( $group_classes as $class ) {
        $attendance_data = get_post_meta( $class->ID, '_dsa_class_attendance', true );
        $group_id = get_post_meta( $class->ID, '_dsa_class_group_id', true );

        if ( ! is_array($attendance_data) || ! $group_id ) continue;

        foreach ($attendance_data as $att_student_id => $att_details) {
            if ( empty($att_details['attended']) || ($student_id != 0 && $student_id != $att_student_id) ) continue;

            // --- MODIFICATION: Since there is no group pricing, the value is 0 ---
            $class_value = 0.00;
            
            $report_items[] = [
                'date' => get_post_meta( $class->ID, '_dsa_class_date', true ),
                'student_name' => get_the_author_meta('display_name', $att_student_id),
                'type' => 'Group Class Attendance',
                'description' => get_the_title($group_id),
                'amount' => $class_value, // Amount is now 0
            ];
            // Do not add to total income
            // $total_income += $class_value;
        }
    }

    usort($report_items, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });
    
    return ['items' => $report_items, 'total' => $total_income];
}


function dsa_display_income_report_table( $report_data ) {
    // ... This function's display logic is unchanged ...
}