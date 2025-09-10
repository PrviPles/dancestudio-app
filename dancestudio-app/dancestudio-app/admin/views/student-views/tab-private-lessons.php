<?php
/**
 * View Part: Renders the "Private Lessons" tab on the single student profile page.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

if ( ! function_exists( 'dsa_render_private_lessons_tab_content' ) ) {
    function dsa_render_private_lessons_tab_content( $student_data ) {
        $student_id = $student_data->ID;
        $lessons = get_posts([
            'post_type' => 'dsa_private_lesson', 'posts_per_page' => -1,
            'meta_query' => ['relation' => 'OR', ['key' => '_dsa_lesson_student1_id', 'value' => $student_id], ['key' => '_dsa_lesson_student2_id', 'value' => $student_id]],
            'meta_key' => '_dsa_lesson_date', 'orderby' => 'meta_value', 'order' => 'DESC',
        ]);
        $log_lesson_url = admin_url('post-new.php?post_type=dsa_private_lesson&student_id=' . $student_id);
        ?>
        <p><a href="<?php echo esc_url($log_lesson_url); ?>" class="button button-primary"><span class="dashicons dashicons-plus-alt" style="vertical-align: text-bottom;"></span> <?php esc_html_e('Log New Private Lesson for this Student', 'dancestudio-app'); ?></a></p>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th><?php _e('Lesson Date');?></th><th><?php _e('Lesson Title');?></th><th><?php _e('Teacher');?></th><th><?php _e('Linked Order');?></th></tr></thead>
            <tbody>
            <?php if ( ! empty( $lessons ) ) : foreach( $lessons as $lesson ) :
                $teacher_id = get_post_meta($lesson->ID, '_dsa_lesson_teacher_id', true);
                $order_id = get_post_meta($lesson->ID, '_dsa_lesson_order_id', true);
            ?>
            <tr>
                <td><strong><a href="<?php echo esc_url(get_edit_post_link($lesson->ID)); ?>"><?php echo esc_html(get_post_meta($lesson->ID, '_dsa_lesson_date', true)); ?></a></strong></td>
                <td><?php echo esc_html($lesson->post_title); ?></td>
                <td><?php echo $teacher_id ? esc_html(get_the_author_meta('display_name', $teacher_id)) : '—'; ?></td>
                <td><?php echo $order_id ? '<a href="' . get_edit_post_link($order_id) . '">#' . esc_html($order_id) . '</a>' : '—'; ?></td>
            </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="4"><?php esc_html_e('This student has no private lesson history.', 'dancestudio-app'); ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}