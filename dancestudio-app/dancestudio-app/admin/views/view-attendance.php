<?php
/**
 * View: Main router for the Attendance reporting page.
 */
if ( ! defined( 'WPINC' ) ) { die; }

if ( ! function_exists( 'dsa_render_attendance_page' ) ) {
    function dsa_render_attendance_page() {
        $active_view = isset( $_GET['view'] ) && 'by_student' === $_GET['view'] ? 'by_student' : 'by_group';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Attendance Report', 'dancestudio-app' ); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url(add_query_arg('view', 'by_group', admin_url('admin.php?page=dsa-attendance-page'))); ?>" class="nav-tab <?php echo $active_view === 'by_group' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Report by Group', 'dancestudio-app'); ?></a>
                <a href="<?php echo esc_url(add_query_arg('view', 'by_student', admin_url('admin.php?page=dsa-attendance-page'))); ?>" class="nav-tab <?php echo $active_view === 'by_student' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Report by Student', 'dancestudio-app'); ?></a>
            </h2>
            <div class="dsa-tab-content-wrapper" style="margin-top: 1em;">
                <?php
                if ( 'by_group' === $active_view ) {
                    if (function_exists('dsa_render_attendance_by_group_view')) dsa_render_attendance_by_group_view();
                } else {
                    if (function_exists('dsa_render_attendance_by_student_view')) dsa_render_attendance_by_student_view();
                }
                ?>
            </div>
        </div>
        <?php
    }
}
?>