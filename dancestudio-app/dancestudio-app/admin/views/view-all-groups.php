<?php
/**
 * View: Renders the main "Groups" management page.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Renders the page that lists all Dance Groups.
 */
function dsa_render_groups_page() {
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e( 'Dance Groups', 'dancestudio-app' ); ?></h1>
        <a href="<?php echo admin_url( 'post-new.php?post_type=dsa_group' ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Group', 'dancestudio-app' ); ?></a>
        <hr class="wp-header-end">

        <p><?php esc_html_e( 'This is the central place to manage your dance groups. Click "Edit" to set up the recurring schedule and automation for a group.', 'dancestudio-app' ); ?></p>

        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Group Name', 'dancestudio-app'); ?></th>
                    <th scope="col"><?php esc_html_e('Enrolled Students', 'dancestudio-app'); ?></th>
                    <th scope="col"><?php esc_html_e('Primary Teacher', 'dancestudio-app'); ?></th>
                    <th scope="col"><?php esc_html_e('Schedule Summary', 'dancestudio-app'); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php
                $groups_query = new WP_Query([
                    'post_type'      => 'dsa_group',
                    'posts_per_page' => -1,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                ]);

                if ( $groups_query->have_posts() ) :
                    while ( $groups_query->have_posts() ) : $groups_query->the_post();
                        $group_id = get_the_ID();
                        
                        $enrollments = get_posts([
                            'post_type' => 'dsa_enroll_record',
                            'post_status' => 'publish',
                            'post_parent' => $group_id,
                            'fields' => 'ids'
                        ]);
                        $student_count = count($enrollments);

                        $teacher_id = get_post_meta($group_id, '_dsa_primary_teacher_id', true);
                        $teacher_name = $teacher_id ? get_the_author_meta('display_name', $teacher_id) : '—';

                        $days = get_post_meta($group_id, '_dsa_schedule_days', true);
                        $start_time = get_post_meta($group_id, '_dsa_schedule_start_time', true);
                        $schedule_summary = 'Not set';
                        if (!empty($days) && $start_time) {
                            $schedule_summary = ucwords(implode(', ', $days)) . ' at ' . esc_html($start_time);
                        }
                        ?>
                        <tr id="post-<?php echo esc_attr($group_id); ?>">
                            <td class="title column-title has-row-actions column-primary">
                                <strong><a class="row-title" href="<?php echo esc_url(get_edit_post_link($group_id)); ?>"><?php the_title(); ?></a></strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?php echo esc_url(get_edit_post_link($group_id)); ?>"><?php esc_html_e('Edit','dancestudio-app');?></a></span>
                                </div>
                            </td>
                            <td><?php echo esc_html($student_count); ?></td>
                            <td><?php echo esc_html($teacher_name); ?></td>
                            <td><?php echo esc_html($schedule_summary); ?></td>
                        </tr>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else: ?>
                    <tr class="no-items"><td class="colspanchange" colspan="4"><?php esc_html_e('No dance groups have been created yet.', 'dancestudio-app'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}