<?php
/**
 * Adds the "Schedule & Automation" meta box to the Dance Group CPT.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action( 'add_meta_boxes_dsa_group', 'dsa_add_schedule_meta_box' );
function dsa_add_schedule_meta_box() {
    add_meta_box(
        'dsa_group_schedule_mb',
        __( 'Schedule & Automation', 'dancestudio-app' ),
        'dsa_render_schedule_meta_box',
        'dsa_group',
        'normal',
        'high'
    );
}

function dsa_render_schedule_meta_box( $post ) {
    wp_nonce_field( 'dsa_save_schedule_meta_action', 'dsa_schedule_meta_nonce' );

    $days = get_post_meta( $post->ID, '_dsa_schedule_days', true );
    if ( ! is_array($days) ) $days = [];
    $frequency = get_post_meta( $post->ID, '_dsa_schedule_frequency', true ) ?: 'weekly';
    $start_time = get_post_meta( $post->ID, '_dsa_schedule_start_time', true );
    $end_time = get_post_meta( $post->ID, '_dsa_schedule_end_time', true );
    $start_date = get_post_meta( $post->ID, '_dsa_schedule_start_date', true );
    $end_date = get_post_meta( $post->ID, '_dsa_schedule_end_date', true );
    $total_classes = get_post_meta( $post->ID, '_dsa_total_classes', true );
    $primary_teacher_id = get_post_meta( $post->ID, '_dsa_primary_teacher_id', true );
    $sub_teacher_id = get_post_meta( $post->ID, '_dsa_substitute_teacher_id', true );

    $weekdays = [
        'monday'    => __('Monday', 'dancestudio-app'),
        'tuesday'   => __('Tuesday', 'dancestudio-app'),
        'wednesday' => __('Wednesday', 'dancestudio-app'),
        'thursday'  => __('Thursday', 'dancestudio-app'),
        'friday'    => __('Friday', 'dancestudio-app'),
        'saturday'  => __('Saturday', 'dancestudio-app'),
        'sunday'    => __('Sunday', 'dancestudio-app'),
    ];
    ?>
    <p class="description"><?php esc_html_e( 'Define the recurring schedule for this group, then click "Update" to save it. After saving, use the "Generate Classes" button at the bottom.', 'dancestudio-app' ); ?></p>
    <table class="form-table">
        <tr valign="top"><th scope="row"><label><?php esc_html_e('Class Days', 'dancestudio-app'); ?></label></th><td><fieldset><?php foreach ( $weekdays as $value => $label ) : ?><label style="margin-right: 15px;"><input type="checkbox" name="dsa_schedule_days[]" value="<?php echo esc_attr($value); ?>" <?php checked( in_array($value, $days) ); ?>> <?php echo esc_html($label); ?></label><?php endforeach; ?></fieldset><p class="description"><?php esc_html_e('Select one or more days this class occurs.', 'dancestudio-app'); ?></p></td></tr>
        <tr valign="top"><th scope="row"><label for="dsa_schedule_frequency"><?php esc_html_e('Frequency', 'dancestudio-app'); ?></label></th><td><select name="dsa_schedule_frequency" id="dsa_schedule_frequency"><option value="weekly" <?php selected($frequency, 'weekly'); ?>><?php esc_html_e('Weekly', 'dancestudio-app'); ?></option><option value="biweekly" <?php selected($frequency, 'biweekly'); ?>><?php esc_html_e('Every Second Week (Bi-weekly)', 'dancestudio-app'); ?></option><option value="monthly" <?php selected($frequency, 'monthly'); ?>><?php esc_html_e('Monthly', 'dancestudio-app'); ?></option></select></td></tr>
        <tr valign="top"><th scope="row"><label><?php esc_html_e('Class Time', 'dancestudio-app'); ?></label></th><td><label for="dsa_schedule_start_time"><?php esc_html_e('From:', 'dancestudio-app'); ?></label><input type="time" id="dsa_schedule_start_time" name="dsa_schedule_start_time" value="<?php echo esc_attr($start_time); ?>" /><label for="dsa_schedule_end_time" style="margin-left: 10px;"><?php esc_html_e('To:', 'dancestudio-app'); ?></label><input type="time" id="dsa_schedule_end_time" name="dsa_schedule_end_time" value="<?php echo esc_attr($end_time); ?>" /></td></tr>
        <tr valign="top"><th scope="row"><label><?php esc_html_e('Schedule Range', 'dancestudio-app'); ?></label></th><td><label for="dsa_schedule_start_date"><?php esc_html_e('From:', 'dancestudio-app'); ?></label><input type="date" id="dsa_schedule_start_date" name="dsa_schedule_start_date" value="<?php echo esc_attr($start_date); ?>" /><label for="dsa_schedule_end_date" style="margin-left: 10px;"><?php esc_html_e('To:', 'dancestudio-app'); ?></label><input type="date" id="dsa_schedule_end_date" name="dsa_schedule_end_date" value="<?php echo esc_attr($end_date); ?>" /></td></tr>
        <tr valign="top"><th scope="row"><label for="dsa_total_classes"><?php esc_html_e('Total Classes in Course', 'dancestudio-app'); ?></label></th><td><input type="number" id="dsa_total_classes" name="dsa_total_classes" value="<?php echo esc_attr($total_classes); ?>" min="1" step="1" /><p class="description"><?php esc_html_e('e.g., 16. Used for naming generated classes like "Class 1/16".', 'dancestudio-app'); ?></p></td></tr>
        <tr valign="top"><th scope="row"><label><?php esc_html_e('Assigned Teachers', 'dancestudio-app'); ?></label></th><td><label for="dsa_primary_teacher_id"><?php esc_html_e('Primary Teacher:', 'dancestudio-app'); ?></label><br/><?php wp_dropdown_users(['name' => 'dsa_primary_teacher_id', 'id' => 'dsa_primary_teacher_id', 'selected' => $primary_teacher_id, 'show_option_none' => __('-- Select Teacher --', 'dancestudio-app'), 'role__in' => ['teacher', 'studio_manager', 'administrator'], 'show_fullname' => true]); ?><br/><br/><label for="dsa_substitute_teacher_id"><?php esc_html_e('Substitute Teacher (Optional):', 'dancestudio-app'); ?></label><br/><?php wp_dropdown_users(['name' => 'dsa_substitute_teacher_id', 'id' => 'dsa_substitute_teacher_id', 'selected' => $sub_teacher_id, 'show_option_none' => __('-- Select Substitute --', 'dancestudio-app'), 'role__in' => ['teacher', 'studio_manager', 'administrator'], 'show_fullname' => true]); ?></td></tr>
        
        <tr valign="top">
            <th scope="row"><strong><?php esc_html_e('Generate Sessions', 'dancestudio-app'); ?></strong></th>
            <td>
                <p class="description"><?php esc_html_e('After saving the schedule above, click this button to automatically create all the individual class posts.', 'dancestudio-app'); ?></p>
                <?php
                $generation_url = wp_nonce_url(
                    admin_url('admin-post.php?action=dsa_generate_group_classes&group_id=' . $post->ID),
                    'dsa_generate_classes_action'
                );
                ?>
                <a href="<?php echo esc_url($generation_url); ?>" class="button button-primary button-large" style="margin-top: 10px;">
                    <?php esc_html_e('Generate Classes from Schedule', 'dancestudio-app'); ?>
                </a>
                <p class="description"><em><?php esc_html_e('Note: This will delete and re-generate all FUTURE classes for this group.', 'dancestudio-app'); ?></em></p>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'save_post_dsa_group', 'dsa_save_schedule_meta_data' );
function dsa_save_schedule_meta_data( $post_id ) {
    if ( ! isset( $_POST['dsa_schedule_meta_nonce'] ) || ! wp_verify_nonce( $_POST['dsa_schedule_meta_nonce'], 'dsa_save_schedule_meta_action' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $allowed_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    if ( isset($_POST['dsa_schedule_days']) && is_array($_POST['dsa_schedule_days']) ) {
        $sanitized_days = array_intersect($_POST['dsa_schedule_days'], $allowed_days);
        update_post_meta( $post_id, '_dsa_schedule_days', $sanitized_days );
    } else {
        delete_post_meta( $post_id, '_dsa_schedule_days' );
    }

    $allowed_freq = ['weekly', 'biweekly', 'monthly'];
    if ( isset($_POST['dsa_schedule_frequency']) && in_array($_POST['dsa_schedule_frequency'], $allowed_freq) ) {
        update_post_meta( $post_id, '_dsa_schedule_frequency', sanitize_key($_POST['dsa_schedule_frequency']) );
    }

    $text_fields = ['_dsa_schedule_start_time', '_dsa_schedule_end_time', '_dsa_schedule_start_date', '_dsa_schedule_end_date'];
    foreach ( $text_fields as $key ) {
        $post_key = str_replace('_dsa_', 'dsa_', $key);
        if ( isset($_POST[$post_key]) ) {
            update_post_meta( $post_id, $key, sanitize_text_field($_POST[$post_key]) );
        }
    }

    if ( isset($_POST['dsa_total_classes']) ) {
        update_post_meta( $post_id, '_dsa_total_classes', absint($_POST['dsa_total_classes']) );
    }

    if ( isset($_POST['dsa_primary_teacher_id']) ) {
        update_post_meta( $post_id, '_dsa_primary_teacher_id', absint($_POST['dsa_primary_teacher_id']) );
    }
    if ( isset($_POST['dsa_substitute_teacher_id']) ) {
        update_post_meta( $post_id, '_dsa_substitute_teacher_id', absint($_POST['dsa_substitute_teacher_id']) );
    }
}