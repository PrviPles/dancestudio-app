<?php
/**
 * View Part: Renders the shell for the single student profile page.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

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
                <a href="<?php echo esc_url(add_query_arg('profile_tab', 'knowledge', $base_url)); ?>" class="nav-tab <?php if($active_tab == 'knowledge') echo 'nav-tab-active'; ?>"><?php _e('Knowledge Base','dancestudio-app');?></a>
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
                    case 'knowledge':
                        if(function_exists('dsa_render_knowledge_base_tab_content')) dsa_render_knowledge_base_tab_content( $student_data );
                        break;
                    case 'details':
                    default:
                        if(function_exists('dsa_render_profile_details_tab_content')) dsa_render_profile_details_tab_content( $student_data );
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
}