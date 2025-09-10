<?php
/**
 * View: Renders the "Students" tab, including the list of students and the single student profile view with full details.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

/**
 * Main router for the Students tab.
 * Decides whether to show the list of all students or a single student's profile.
 */
if ( ! function_exists( 'dsa_render_students_tab' ) ) {
    function dsa_render_students_tab() {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'view_profile' && isset( $_GET['student_id'] ) ) {
            dsa_render_single_student_profile_page( absint( $_GET['student_id'] ) );
        } else {
            dsa_render_all_students_list_page();
        }
    }
}


/**
 * Renders the list of all students.
 */
if ( ! function_exists( 'dsa_render_all_students_list_page' ) ) {
    function dsa_render_all_students_list_page() {
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'last_name';
        $order   = ( isset( $_GET['order'] ) && in_array( strtolower( $_GET['order'] ), ['asc', 'desc'] ) ) ? strtoupper( $_GET['order'] ) : 'ASC';

        $query_args = [
            'role__in' => ['student', 'subscriber'], 'orderby'  => $orderby, 'order'    => $order,
            'meta_query' => [ 'relation' => 'OR', ['key' => 'last_name', 'compare' => 'EXISTS'], ['key' => 'last_name', 'compare' => 'NOT EXISTS'] ]
        ];
        
        $students_query = new WP_User_Query($query_args);
        $students = $students_query->get_results();
        ?>
        <h3><?php esc_html_e( 'All Students', 'dancestudio-app' ); ?></h3>
        <p><?php esc_html_e( 'A complete list of all students in your studio. Click on a student\'s name to view their full profile.', 'dancestudio-app' ); ?></p>
        <table class="wp-list-table widefat fixed striped users">
             <thead>
                <tr>
                    <?php
                    if (function_exists('dsa_render_sortable_table_header')) {
                        dsa_render_sortable_table_header( __('First Name', 'dancestudio-app'), 'first_name', $orderby, $order );
                        dsa_render_sortable_table_header( __('Last Name', 'dancestudio-app'), 'last_name', $orderby, $order );
                    }
                    ?>
                    <th scope="col"><?php esc_html_e('Enrolled In Group(s)', 'dancestudio-app'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'dancestudio-app'); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if ( ! empty( $students ) ) :
                    foreach ( $students as $student ) :
                        $profile_link = admin_url('admin.php?page=dsa-students-tab&action=view_profile&student_id=' . $student->ID);
                        $enrolled_groups = [];
                        $enrollment_records = get_posts(['post_type' => 'dsa_enroll_record', 'post_status' => 'publish', 'author' => $student->ID, 'posts_per_page' => -1]);
                        foreach ( $enrollment_records as $record ) { $enrolled_groups[] = get_the_title($record->post_parent); }
                        ?>
                        <tr>
                            <td><strong><a href="<?php echo esc_url($profile_link); ?>"><?php echo esc_html($student->first_name); ?></a></strong></td>
                            <td><strong><a href="<?php echo esc_url($profile_link); ?>"><?php echo esc_html($student->last_name); ?></a></strong></td>
                            <td><?php echo !empty($enrolled_groups) ? esc_html(implode(', ', $enrolled_groups)) : '—'; ?></td>
                            <td>
                                <a href="<?php echo esc_url($profile_link); ?>" class="button button-secondary"><?php esc_html_e('View Profile', 'dancestudio-app'); ?></a>
                                <button type="button" class="button button-secondary dsa-edit-enrollments-button" data-student-id="<?php echo esc_attr($student->ID); ?>" data-student-name="<?php echo esc_attr($student->display_name); ?>">
                                    <?php esc_html_e('Edit Enrollment', 'dancestudio-app'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr class="no-items"><td class="colspanchange" colspan="4"><?php esc_html_e('No students found.', 'dancestudio-app'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div id="dsa-enrollment-modal" style="display:none;" title="<?php esc_attr_e('Manage Enrollments', 'dancestudio-app'); ?>"><input type="hidden" id="dsa_enroll_student_id" value=""><div class="dsa-modal-content"><p>Loading...</p></div></div>
        <?php
    }
}

/**
 * Renders the new, detailed profile page for a single student.
 */
if ( ! function_exists( 'dsa_render_single_student_profile_page' ) ) {
    function dsa_render_single_student_profile_page( $student_id ) {
        $student_data = get_userdata( $student_id );
        if ( ! $student_data ) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Student not found.', 'dancestudio-app') . '</p></div>';
            return;
        }

        $active_tab = isset($_GET['profile_tab']) ? sanitize_key($_GET['profile_tab']) : 'details';
        $base_url = admin_url('admin.php?page=dsa-students-tab&action=view_profile&student_id=' . $student_id);
        ?>
        <div class="wrap dsa-student-profile">
            <h1 class="wp-heading-inline"><?php echo esc_html( $student_data->display_name ); ?></h1>
            <a href="<?php echo esc_url( get_edit_user_link($student_id) ); ?>" class="page-title-action" target="_blank"><?php esc_html_e('Edit in WordPress', 'dancestudio-app'); ?></a>
            <a href="<?php echo esc_url( admin_url('admin.php?page=dsa-students-tab') ); ?>" class="page-title-action"><?php esc_html_e('← Back to All Students', 'dancestudio-app'); ?></a>
            <hr class="wp-header-end">

            <h2 class="nav-tab-wrapper" style="margin-top: 20px;">
                <a href="<?php echo esc_url(add_query_arg('profile_tab', 'details', $base_url)); ?>" class="nav-tab <?php if($active_tab == 'details') echo 'nav-tab-active'; ?>"><?php _e('Profile Details','dancestudio-app');?></a>
                <a href="<?php echo esc_url(add_query_arg('profile_tab', 'enrollments', $base_url)); ?>" class="nav-tab <?php if($active_tab == 'enrollments') echo 'nav-tab-active'; ?>"><?php _e('Enrollment History','dancestudio-app');?></a>
                <a href="<?php echo esc_url(add_query_arg('profile_tab', 'lessons', $base_url)); ?>" class="nav-tab <?php if($active_tab == 'lessons') echo 'nav-tab-active'; ?>"><?php _e('Private Lessons','dancestudio-app');?></a>
                <a href="<?php echo esc_url(add_query_arg('profile_tab', 'packages', $base_url)); ?>" class="nav-tab <?php if($active_tab == 'packages') echo 'nav-tab-active'; ?>"><?php _e('Packages & Orders','dancestudio-app');?></a>
            </h2>
            
            <div class="dsa-profile-tab-content" style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-top: none;">
                <?php
                switch ($active_tab) {
                    case 'enrollments':
                        if(function_exists('dsa_render_enrollment_history_tab_content')) dsa_render_enrollment_history_tab_content( $student_data );
                        break;
                    case 'lessons':
                        if(function_exists('dsa_render_private_lessons_tab_content')) dsa_render_private_lessons_tab_content( $student_data );
                        break;
                    case 'packages':
                        if(function_exists('dsa_render_packages_orders_tab_content')) dsa_render_packages_orders_tab_content( $student_data );
                        break;
                    case 'details':
                    default:
                        if(function_exists('dsa_render_profile_details_tab_content')) dsa_render_profile_details_tab_content( $student_data );
                        break;
                }
                ?>
            </div>
             <div id="dsa-enrollment-modal" style="display:none;" title="<?php esc_attr_e('Manage Enrollments', 'dancestudio-app'); ?>"><input type="hidden" id="dsa_enroll_student_id" value=""><div class="dsa-modal-content"><p>Loading...</p></div></div>
        </div>
        <?php
    }
}

/**
 * Renders the content for the "Profile Details" tab.
 */
if ( ! function_exists( 'dsa_render_profile_details_tab_content' ) ) {
    function dsa_render_profile_details_tab_content( $student_data ) {
        $student_id = $student_data->ID;
        $phone = get_user_meta($student_id, '_dsa_user_phone', true);
        $birthday = get_user_meta($student_id, '_dsa_user_birth_date', true);
        $age = $birthday && function_exists('dsa_calculate_age') ? dsa_calculate_age($birthday) : '—';
        $partner_id = function_exists('dsa_get_partner_id') ? dsa_get_partner_id($student_id) : false;
        $log_lesson_url = admin_url('post-new.php?post_type=dsa_private_lesson&student_id=' . $student_id);
        ?>
        <div style="display: flex; gap: 30px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px;">
                <h3><?php esc_html_e('Contact Information', 'dancestudio-app'); ?></h3>
                <table class="form-table">
                    <tr><th><?php esc_html_e('First Name', 'dancestudio-app'); ?></th><td><?php echo esc_html($student_data->first_name); ?></td></tr>
                    <tr><th><?php esc_html_e('Last Name', 'dancestudio-app'); ?></th><td><?php echo esc_html($student_data->last_name); ?></td></tr>
                    <tr><th><?php esc_html_e('Email', 'dancestudio-app'); ?></th><td><a href="mailto:<?php echo esc_attr($student_data->user_email); ?>"><?php echo esc_html($student_data->user_email); ?></a></td></tr>
                    <tr><th><?php esc_html_e('Phone Number', 'dancestudio-app'); ?></th><td><?php echo esc_html($phone ?: '—'); ?></td></tr>
                </table>
                <h3 style="margin-top: 30px;"><?php esc_html_e('Personal Information', 'dancestudio-app'); ?></h3>
                <table class="form-table">
                    <tr><th><?php esc_html_e('Birth Date', 'dancestudio-app'); ?></th><td><?php echo $birthday ? esc_html(date_i18n(get_option('date_format'), strtotime($birthday))) : '—'; ?></td></tr>
                    <tr><th><?php esc_html_e('Age', 'dancestudio-app'); ?></th><td><?php echo esc_html($age); ?></td></tr>
                    <tr><th><?php esc_html_e('Dance Partner', 'dancestudio-app'); ?></th>
                        <td>
                            <?php if ($partner_id) : 
                                $partner_data = get_userdata($partner_id);
                                $partner_profile_url = admin_url('admin.php?page=dsa-students-tab&action=view_profile&student_id=' . $partner_id); ?>
                                <a href="<?php echo esc_url($partner_profile_url); ?>"><?php echo esc_html($partner_data->display_name); ?></a>
                            <?php else: echo esc_html__('Not Paired', 'dancestudio-app'); endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <div style="flex-basis: 250px;">
                <h3><?php esc_html_e('Actions', 'dancestudio-app'); ?></h3>
                <p>
                    <button type="button" class="button button-primary dsa-edit-enrollments-button" data-student-id="<?php echo esc_attr($student_id); ?>" data-student-name="<?php echo esc_attr($student_data->display_name); ?>">
                        <span class="dashicons dashicons-groups" style="vertical-align: text-bottom;"></span> <?php esc_html_e('Enroll in Group', 'dancestudio-app'); ?>
                    </button>
                </p>
                <p>
                    <a href="<?php echo esc_url($log_lesson_url); ?>" class="button button-secondary">
                         <span class="dashicons dashicons-plus-alt" style="vertical-align: text-bottom;"></span> <?php esc_html_e('Log New Private Lesson', 'dancestudio-app'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
}

/**
 * Renders the content for the "Enrollment History" tab.
 */
if ( ! function_exists( 'dsa_render_enrollment_history_tab_content' ) ) {
    function dsa_render_enrollment_history_tab_content( $student_data ) {
        $student_id = $student_data->ID;
        $enrollment_records = get_posts([
            'post_type' => 'dsa_enroll_record', 'post_status' => ['publish', 'dropped_out'],
            'author' => $student_id, 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC',
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

/**
 * Renders the content for the "Private Lessons" tab.
 */
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

/**
 * Renders the content for the "Packages & Orders" tab.
 */
if ( ! function_exists( 'dsa_render_packages_orders_tab_content' ) ) {
    function dsa_render_packages_orders_tab_content( $student_data ) {
        if ( function_exists('dsa_render_order_tracker_table') ) {
            dsa_render_order_tracker_table(['customer_id' => $student_data->ID]);
        } else {
            echo '<p>' . esc_html__('Order tracker function not found.', 'dancestudio-app') . '</p>';
        }
    }
}