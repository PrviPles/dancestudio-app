<?php
/**
 * View: Renders the content for the Manager Dashboard "Students" tab.
 * UPGRADED with full AJAX functionality.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}
?>

<h2><?php esc_html_e('Manage Students', 'dancestudio-app'); ?></h2>

<div class="dsa-card">
    <h3><?php esc_html_e('Create New Student', 'dancestudio-app'); ?></h3>
    <div class="card-body">
        <form id="dsa-create-student-form">
            <?php wp_nonce_field( 'dsa_create_student_ajax_nonce', 'dsa_create_student_nonce' ); ?>
            <table class="form-table">
                <tr><th><label for="dsa_new_username"><?php _e('Username *', 'dancestudio-app'); ?></label></th><td><input type="text" name="dsa_new_username" id="dsa_new_username" required></td></tr>
                <tr><th><label for="dsa_new_email"><?php _e('Email *', 'dancestudio-app'); ?></label></th><td><input type="email" name="dsa_new_email" id="dsa_new_email" required></td></tr>
                <tr><th><label for="dsa_new_fname"><?php _e('First Name', 'dancestudio-app'); ?></label></th><td><input type="text" name="dsa_new_fname" id="dsa_new_fname"></td></tr>
                <tr><th><label for="dsa_new_lname"><?php _e('Last Name', 'dancestudio-app'); ?></label></th><td><input type="text" name="dsa_new_lname" id="dsa_new_lname"></td></tr>
            </table>
            <p><?php _e('A password will be automatically generated and emailed to the new user.', 'dancestudio-app'); ?></p>
            <p class="submit">
                <input type="submit" class="dsa-submit-button button button-primary" value="<?php esc_attr_e('Create Student', 'dancestudio-app'); ?>">
                <span class="spinner" style="float: none; vertical-align: middle;"></span>
            </p>
        </form>
    </div>
</div>

<h3><?php esc_html_e('All Students', 'dancestudio-app'); ?></h3>

<div class="dsa-filters">
    <div>
        <label for="dsa_filter_name"><?php _e('Search by Name', 'dancestudio-app'); ?></label>
        <input type="text" id="dsa_filter_name" placeholder="Type to filter...">
    </div>
</div>

<div class="dsa-table-responsive-wrapper">
    <table class="dsa-front-end-table" id="dsa-all-students-table">
        <thead>
            <tr>
                <th><?php _e('Full Name', 'dancestudio-app'); ?></th>
                <th><?php _e('Age', 'dancestudio-app'); ?></th>
                <th><?php _e('Email', 'dancestudio-app'); ?></th>
                <th><?php _e('Phone', 'dancestudio-app'); ?></th>
                <th><?php _e('Partner', 'dancestudio-app'); ?></th>
                <th><?php _e('Active Groups', 'dancestudio-app'); ?></th>
                <th><?php _e('Actions', 'dancestudio-app'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $all_students_query = get_users(['role__in' => ['student', 'subscriber'], 'orderby' => 'display_name', 'order' => 'ASC']);
            $students_to_display = [];
            foreach ($all_students_query as $student) {
                $active_groups = [];
                $active_enrollments = get_posts(['post_type' => 'dsa_enroll_record', 'post_status' => 'publish', 'author' => $student->ID, 'posts_per_page' => -1]);
                foreach ($active_enrollments as $record) { $active_groups[] = get_the_title($record->post_parent); }
                
                $students_to_display[] = [
                    'id' => $student->ID, 
                    'full_name' => trim($student->first_name . ' ' . $student->last_name) ?: $student->user_login,
                    'age' => ($bday = get_user_meta($student->ID, '_dsa_user_birth_date', true)) ? dsa_calculate_age($bday) : '—',
                    'user_email' => $student->user_email,
                    'phone' => get_user_meta($student->ID, '_dsa_user_phone', true) ?: '—',
                    'partner' => ($pid = get_user_meta($student->ID, 'dsa_partner_user_id', true)) ? get_the_author_meta('display_name', $pid) : '—',
                    'groups' => !empty($active_groups) ? implode(', ', $active_groups) : '—'
                ];
            }
            
            if (empty($students_to_display)) : ?>
                <tr id="dsa-no-students-row"><td colspan="7"><?php _e('No students found.', 'dancestudio-app'); ?></td></tr>
            <?php else :
                foreach ($students_to_display as $student):
                ?>
                <tr class="dsa-student-row" id="dsa-student-row-<?php echo esc_attr($student['id']); ?>">
                    <td class="dsa-full-name"><?php echo esc_html($student['full_name']); ?></td>
                    <td class="dsa-age"><?php echo esc_html($student['age']); ?></td>
                    <td class="dsa-email"><?php echo esc_html($student['user_email']); ?></td>
                    <td class="dsa-phone"><?php echo esc_html($student['phone']); ?></td>
                    <td class="dsa-partner"><?php echo esc_html($student['partner']); ?></td>
                    <td class="dsa-groups"><?php echo esc_html($student['groups']); ?></td>
                    <td class="dsa-actions">
                        <button type="button" class="button-secondary dsa-edit-student-button" data-student-id="<?php echo esc_attr($student['id']); ?>"><?php _e('Edit', 'dancestudio-app'); ?></button>
                        <button type="button" class="button-link-delete dsa-delete-student-button" data-student-id="<?php echo esc_attr($student['id']); ?>"><?php _e('Delete', 'dancestudio-app'); ?></button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
             <tr id="dsa-no-filter-results" style="display: none;"><td colspan="7"><?php _e('No students match your filter.', 'dancestudio-app'); ?></td></tr>
        </tbody>
    </table>
</div>

<div id="dsa-edit-student-modal" style="display:none;" title="<?php esc_attr_e('Edit Student Details', 'dancestudio-app'); ?>">
    <form id="dsa-edit-student-form">
        <input type="hidden" id="dsa_edit_student_id" name="student_id" value="">
        <?php wp_nonce_field( 'dsa_manage_student_nonce_action', 'dsa_manage_student_nonce' ); ?>
        <table class="form-table">
            <tr><th><label for="dsa_edit_first_name"><?php _e('First Name', 'dancestudio-app'); ?></label></th><td><input type="text" name="first_name" id="dsa_edit_first_name" class="widefat"></td></tr>
            <tr><th><label for="dsa_edit_last_name"><?php _e('Last Name', 'dancestudio-app'); ?></label></th><td><input type="text" name="last_name" id="dsa_edit_last_name" class="widefat"></td></tr>
            <tr><th><label for="dsa_edit_email"><?php _e('Email', 'dancestudio-app'); ?></label></th><td><input type="email" name="email" id="dsa_edit_email" class="widefat"></td></tr>
            <tr><th colspan="2"><h3><?php _e('Dance Studio Info', 'dancestudio-app'); ?></h3></th></tr>
            <tr><th><label for="dsa_edit_birth_date"><?php _e('Birth Date', 'dancestudio-app'); ?></label></th><td><input type="date" name="birth_date" id="dsa_edit_birth_date" class="widefat"></td></tr>
            <tr><th><label for="dsa_edit_phone"><?php _e('Phone', 'dancestudio-app'); ?></label></th><td><input type="tel" name="phone" id="dsa_edit_phone" class="widefat"></td></tr>
        </table>
    </form>
</div>