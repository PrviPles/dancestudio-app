<?php
if ( ! defined( 'WPINC' ) ) die;

if ( ! function_exists( 'dsa_render_profile_details_tab_content' ) ) {
    function dsa_render_profile_details_tab_content( $student_data ) {
        $student_id = $student_data->ID;
        $phone = get_user_meta($student_id, '_dsa_user_phone', true);
        $birthday = get_user_meta($student_id, '_dsa_user_birth_date', true);
        $age = $birthday && function_exists('dsa_calculate_age') ? dsa_calculate_age($birthday) : '—';
        $partner_id = function_exists('dsa_get_partner_id') ? dsa_get_partner_id($student_id) : false;
        $street = get_user_meta( $student_id, '_dsa_user_street', true ) ?: '—';
        $postal_code = get_user_meta( $student_id, '_dsa_user_postal_code', true ) ?: '—';
        $city = get_user_meta( $student_id, '_dsa_user_city', true ) ?: '—';
        $is_retired = get_user_meta( $student_id, '_dsa_is_retired', true );
        $has_family_discount = get_user_meta( $student_id, '_dsa_family_discount', true );
        ?>
        <div style="display: flex; gap: 30px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px;">
                <h3><?php esc_html_e('Contact Information', 'dancestudio-app'); ?></h3>
                <table class="form-table">
                    <tr><th><?php esc_html_e('First Name', 'dancestudio-app'); ?></th><td id="dsa-view-first-name"><?php echo esc_html($student_data->first_name); ?></td></tr>
                    <tr><th><?php esc_html_e('Last Name', 'dancestudio-app'); ?></th><td id="dsa-view-last-name"><?php echo esc_html($student_data->last_name); ?></td></tr>
                    <tr><th><?php esc_html_e('Email', 'dancestudio-app'); ?></th><td id="dsa-view-email"><a href="mailto:<?php echo esc_attr($student_data->user_email); ?>"><?php echo esc_html($student_data->user_email); ?></a></td></tr>
                    <tr><th><?php esc_html_e('Phone Number', 'dancestudio-app'); ?></th><td id="dsa-view-phone"><?php echo esc_html($phone ?: '—'); ?></td></tr>
                    <tr><th><?php esc_html_e('Street Address', 'dancestudio-app'); ?></th><td id="dsa-view-street"><?php echo esc_html($street); ?></td></tr>
                    <tr><th><?php esc_html_e('Postal Code', 'dancestudio-app'); ?></th><td id="dsa-view-postal-code"><?php echo esc_html($postal_code); ?></td></tr>
                    <tr><th><?php esc_html_e('City / Town', 'dancestudio-app'); ?></th><td id="dsa-view-city"><?php echo esc_html($city); ?></td></tr>
                </table>

                <h3 style="margin-top: 30px;"><?php esc_html_e('Personal Information', 'dancestudio-app'); ?></h3>
                <table class="form-table">
                    <tr><th><?php esc_html_e('Birth Date', 'dancestudio-app'); ?></th><td id="dsa-view-birth-date"><?php echo $birthday ? esc_html(date_i18n(get_option('date_format'), strtotime($birthday))) : '—'; ?></td></tr>
                    <tr><th><?php esc_html_e('Age', 'dancestudio-app'); ?></th><td id="dsa-view-age"><?php echo esc_html($age); ?></td></tr>
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

                <h3 style="margin-top: 30px;"><?php esc_html_e('Discounts', 'dancestudio-app'); ?></h3>
                <table class="form-table">
                     <tr><th><?php esc_html_e('Retiree Discount', 'dancestudio-app'); ?></th><td id="dsa-view-is-retired"><?php echo $is_retired === '1' ? 'Yes' : 'No'; ?></td></tr>
                     <tr><th><?php esc_html_e('Family Discount', 'dancestudio-app'); ?></th><td id="dsa-view-family-discount"><?php echo $has_family_discount === '1' ? 'Yes' : 'No'; ?></td></tr>
                </table>
            </div>
            <div style="flex-basis: 250px;">
                <h3><?php esc_html_e('Actions', 'dancestudio-app'); ?></h3>
                <p><button type="button" id="dsa-edit-profile-details-button" class="button button-primary" data-student-id="<?php echo esc_attr($student_id); ?>"><?php esc_html_e('Edit Details', 'dancestudio-app'); ?></button></p>
                <p><button type="button" class="button button-secondary dsa-enroll-modal-button" data-student-id="<?php echo esc_attr($student_id); ?>" data-student-name="<?php echo esc_attr($student_data->display_name); ?>"><span class="dashicons dashicons-groups" style="vertical-align: text-bottom;"></span> <?php esc_html_e('Enroll in Group', 'dancestudio-app'); ?></button></p>
                <p><a href="<?php echo esc_url(admin_url('post-new.php?post_type=dsa_private_lesson&student_id=' . $student_id)); ?>" class="button button-secondary"><span class="dashicons dashicons-plus-alt" style="vertical-align: text-bottom;"></span> <?php esc_html_e('Log New Private Lesson', 'dancestudio-app'); ?></a></p>
            </div>
        </div>

        <div id="dsa-edit-student-profile-modal" title="Edit Student Details" style="display:none;">
            <form id="dsa-edit-student-profile-form">
                <input type="hidden" id="dsa_edit_student_id" name="student_id" value="">
                <?php wp_nonce_field( 'dsa_save_student_details_nonce_action', 'dsa_save_student_details_nonce' ); ?>
                <table class="form-table">
                    <tr><th><label for="dsa_edit_first_name">First Name</label></th><td><input type="text" name="first_name" id="dsa_edit_first_name" class="widefat"></td></tr>
                    <tr><th><label for="dsa_edit_last_name">Last Name</label></th><td><input type="text" name="last_name" id="dsa_edit_last_name" class="widefat"></td></tr>
                    <tr><th><label for="dsa_edit_email">Email</label></th><td><input type="email" name="email" id="dsa_edit_email" class="widefat"></td></tr>
                    <tr><th><label for="dsa_edit_phone">Phone Number</label></th><td><input type="tel" name="phone" id="dsa_edit_phone" class="widefat"></td></tr>
                    <tr><th><label for="dsa_edit_street">Street Address</label></th><td><input type="text" name="street" id="dsa_edit_street" class="widefat"></td></tr>
                    <tr><th><label for="dsa_edit_postal_code">Postal Code</label></th><td><input type="text" name="postal_code" id="dsa_edit_postal_code" class="widefat"></td></tr>
                    <tr><th><label for="dsa_edit_city">City / Town</label></th><td><input type="text" name="city" id="dsa_edit_city" class="widefat"></td></tr>
                    <tr><th><label for="dsa_edit_birth_date">Birth Date</label></th><td><input type="date" name="birth_date" id="dsa_edit_birth_date" class="widefat"></td></tr>
                    <tr><th>Discounts</th><td>
                        <label><input type="checkbox" name="is_retired" id="dsa_edit_is_retired" value="1"> Retiree</label><br>
                        <label><input type="checkbox" name="family_discount" id="dsa_edit_family_discount" value="1"> Family</label>
                    </td></tr>
                </table>
            </form>
        </div>
        <?php
    }
}