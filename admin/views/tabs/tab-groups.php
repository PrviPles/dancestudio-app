<?php
/**
 * View: Renders the content for the "Groups" tab in the main dashboard.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

if ( ! function_exists( 'dsa_render_groups_tab' ) ) {
    function dsa_render_groups_tab() {
        ?>
        <h3><?php esc_html_e( 'All Dance Groups', 'dancestudio-app' ); ?></h3>
        <p><?php esc_html_e( 'This is the central place to manage your dance groups. Click "Manage" on any group to view its detailed profile, statistics, and student roster.', 'dancestudio-app' ); ?></p>
        <p><a href="<?php echo admin_url( 'post-new.php?post_type=dsa_group' ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Group', 'dancestudio-app' ); ?></a></p>

        <?php
        // --- PERFORMANCE & BUG FIX START ---
        // 1. Get all active enrollments in a single query.
        $all_enrollments = get_posts([
            'post_type' => 'dsa_enroll_record',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'id=>parent', // Efficiently get an array of [enrollment_id => group_id]
        ]);

        // 2. Count the number of enrollments for each group ID.
        $enrollment_counts = [];
        if (!empty($all_enrollments)) {
            $enrollment_counts = array_count_values($all_enrollments);
        }
        // --- PERFORMANCE & BUG FIX END ---

        $groups_query = new WP_Query([
            'post_type'      => 'dsa_group',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        ?>
        <table class="wp-list-table widefat fixed striped posts" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Group Name', 'dancestudio-app'); ?></th>
                    <th scope="col"><?php esc_html_e('Enrolled Students', 'dancestudio-app'); ?></th>
                    <th scope="col"><?php esc_html_e('Primary Teacher', 'dancestudio-app'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'dancestudio-app'); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php
                if ( $groups_query->have_posts() ) :
                    while ( $groups_query->have_posts() ) : $groups_query->the_post();
                        $group_id = get_the_ID();
                        
                        // --- PERFORMANCE & BUG FIX START ---
                        // 3. Look up the pre-calculated count instead of running a new query.
                        $student_count = $enrollment_counts[$group_id] ?? 0;
                        // --- PERFORMANCE & BUG FIX END ---

                        $teacher_id = get_post_meta($group_id, '_dsa_primary_teacher_id', true);
                        $teacher_name = $teacher_id ? get_the_author_meta('display_name', $teacher_id) : '—';
                        
                        $manage_link = admin_url('admin.php?page=dsa-groups-tab&action=view_group&group_id=' . $group_id);
                        ?>
                        <tr id="post-<?php echo esc_attr($group_id); ?>">
                            <td class="title column-title">
                                <strong><a class="row-title" href="<?php echo esc_url(get_edit_post_link($group_id)); ?>"><?php the_title(); ?></a></strong>
                            </td>
                            <td><?php echo esc_html($student_count); ?></td>
                            <td><?php echo esc_html($teacher_name); ?></td>
                            <td><a href="<?php echo esc_url($manage_link); ?>" class="button button-secondary"><?php esc_html_e('Manage', 'dancestudio-app'); ?></a></td>
                        </tr>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else: ?>
                    <tr class="no-items"><td class="colspanchange" colspan="4"><?php esc_html_e('No dance groups have been created yet.', 'dancestudio-app'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}