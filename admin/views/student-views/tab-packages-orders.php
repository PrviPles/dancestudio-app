<?php
/**
 * View Part: Renders the "Packages & Orders" tab on the single student profile page.
 * UPDATED: Now shows active subscriptions and a complete order history.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

if ( ! function_exists( 'dsa_render_packages_orders_tab_content' ) ) {
    function dsa_render_packages_orders_tab_content( $student_data ) {
        ?>
        <h3><?php esc_html_e('Active Group Subscriptions', 'dancestudio-app'); ?></h3>
        <?php
        $active_enrollment_records = get_posts([
            'post_type' => 'dsa_enroll_record',
            'post_status' => 'publish',
            'author' => $student_data->ID,
            'posts_per_page' => -1,
        ]);

        if ( empty($active_enrollment_records) ) : ?>
            <p><em><?php esc_html_e('This student is not actively enrolled in any groups.', 'dancestudio-app'); ?></em></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Group Name', 'dancestudio-app'); ?></th>
                        <th><?php esc_html_e('Subscription Start Date', 'dancestudio-app'); ?></th>
                        <th><?php esc_html_e('Lessons Used in Cycle', 'dancestudio-app'); ?></th>
                        <th><?php esc_html_e('Payment Status', 'dancestudio-app'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_enrollment_records as $record) :
                        $group_id = $record->post_parent;
                        $start_date = get_post_meta($record->ID, '_dsa_subscription_start_date', true);
                        $lesson_count = (int) get_post_meta($record->ID, '_dsa_lessons_attended_count', true);
                        $status_color = ($lesson_count >= 8) ? 'red' : 'green';
                        $status_text = ($lesson_count >= 8) ? __('Due for Renewal', 'dancestudio-app') : __('Active', 'dancestudio-app');
                    ?>
                        <tr>
                            <td><strong><a href="<?php echo esc_url(get_edit_post_link($group_id)); ?>"><?php echo esc_html(get_the_title($group_id)); ?></a></strong></td>
                            <td><?php echo !empty($start_date) ? esc_html(date_i18n(get_option('date_format'), strtotime($start_date))) : '<em>' . esc_html__('Awaiting First Attendance', 'dancestudio-app') . '</em>'; ?></td>
                            <td><strong><?php echo esc_html($lesson_count); ?> / 8</strong></td>
                            <td><span style="color: <?php echo esc_attr($status_color); ?>; font-weight: bold;"><?php echo esc_html($status_text); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h3 style="margin-top: 30px;"><?php esc_html_e('Complete Order History', 'dancestudio-app'); ?></h3>
        <?php
        if ( function_exists('dsa_render_order_tracker_table') ) {
            // We pass an argument to tell the function to show ALL orders for this customer
            dsa_render_order_tracker_table(['customer_id' => $student_data->ID, 'show_all' => true]);
        } else {
            echo '<p>' . esc_html__('Order tracker function not found.', 'dancestudio-app') . '</p>';
        }
    }
}