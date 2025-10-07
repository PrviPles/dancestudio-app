<?php
/**
 * View Part: Renders the "Enrollment History" tab on the single student profile page.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

if ( ! function_exists( 'dsa_render_enrollment_history_tab_content' ) ) {
    function dsa_render_enrollment_history_tab_content( $student_data ) {
        $student_id = $student_data->ID;
        $enrollment_records = get_posts([
            'post_type' => 'dsa_enroll_record',
            'post_status' => ['publish', 'dropped_out'],
            'author' => $student_id,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        ?>
        <p>
            <button type="button" class="button button-primary dsa-edit-enrollments-button" data-student-id="<?php echo esc_attr($student_id); ?>" data-student-name="<?php echo esc_attr($student_data->display_name); ?>">
                <span class="dashicons dashicons-groups" style="vertical-align: text-bottom;"></span> <?php esc_html_e('Enroll in New Group', 'dancestudio-app'); ?>
            </button>
        </p>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th><?php _e('Group Name');?></th><th><?php _e('Status');?></th><th><?php _e('Enrolled On');?></th><th><?php _e('Dropped Out On');?></th></tr></thead>
            <tbody>
                <?php if ( ! empty( $enrollment_records ) ) : foreach( $enrollment_records as $record ) :
                    $dropout_date = get_post_meta($record->ID, '_dsa_dropout_date', true);
                ?>
                <tr>
                    <td><strong><a href="<?php echo esc_url(get_edit_post_link($record->post_parent)); ?>"><?php echo esc_html(get_the_title($record->post_parent)); ?></a></strong></td>
                    <td><?php echo $record->post_status === 'publish' ? '<span style="color:green;">' . esc_html__('Active', 'dancestudio-app') . '</span>' : '<span style="color:red;">' . esc_html__('Dropped Out', 'dancestudio-app') . '</span>'; ?></td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($record->post_date))); ?></td>
                    <td><?php echo $dropout_date ? esc_html(date_i18n(get_option('date_format'), strtotime($dropout_date))) : '—'; ?></td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4"><?php esc_html_e('This student has no enrollment history.', 'dancestudio-app'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}