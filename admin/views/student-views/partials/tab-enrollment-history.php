<?php
if ( ! defined( 'WPINC' ) ) die;
if ( ! function_exists( 'dsa_render_enrollment_history_tab_content' ) ) {
    function dsa_render_enrollment_history_tab_content( $student_data ) {
        $student_id = $student_data->ID;
        $enrollment_records = get_posts([
            'post_type' => 'dsa_enroll_record', 'post_status' => ['publish', 'dropped_out'],
            'author' => $student_id, 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC',
        ]);
        ?>
        <p>
            <button type="button" class="button button-primary dsa-enroll-modal-button" data-student-id="<?php echo esc_attr($student_id); ?>">
                <span class="dashicons dashicons-groups" style="vertical-align: text-bottom;"></span> <?php esc_html_e('Enroll in New Group', 'dancestudio-app'); ?>
            </button>
        </p>
        <table class="wp-list-table widefat fixed striped" id="dsa-enrollment-history-table">
            <thead><tr><th><?php _e('Group Name');?></th><th><?php _e('Status');?></th><th><?php _e('Enrolled On');?></th><th><?php _e('Dropped Out On');?></th><th><?php _e('Actions');?></th></tr></thead>
            <tbody>
                <?php if ( ! empty( $enrollment_records ) ) : foreach( $enrollment_records as $record ) :
                    $enroll_date = $record->post_date;
                    $dropout_date = get_post_meta($record->ID, '_dsa_dropout_date', true);
                ?>
                <tr data-record-id="<?php echo esc_attr($record->ID); ?>">
                    <td><strong><a href="<?php echo esc_url(get_edit_post_link($record->post_parent)); ?>"><?php echo esc_html(get_the_title($record->post_parent)); ?></a></strong></td>
                    <td><?php echo $record->post_status === 'publish' ? '<span style="color:green;">' . esc_html__('Active', 'dancestudio-app') . '</span>' : '<span style="color:red;">' . esc_html__('Dropped Out', 'dancestudio-app') . '</span>'; ?></td>
                    <td>
                        <span class="dsa-editable-date" data-original-date="<?php echo esc_attr(date('Y-m-d', strtotime($enroll_date))); ?>"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($enroll_date))); ?></span>
                        <input type="date" class="dsa-date-input" data-date-type="enroll" data-record-id="<?php echo esc_attr($record->ID); ?>" value="<?php echo esc_attr(date('Y-m-d', strtotime($enroll_date))); ?>" style="display:none;">
                    </td>
                    <td>
                        <span class="dsa-editable-date" data-original-date="<?php echo esc_attr($dropout_date ? date('Y-m-d', strtotime($dropout_date)) : ''); ?>"><?php echo $dropout_date ? esc_html(date_i18n(get_option('date_format'), strtotime($dropout_date))) : '—'; ?></span>
                        <input type="date" class="dsa-date-input" data-date-type="dropout" data-record-id="<?php echo esc_attr($record->ID); ?>" value="<?php echo esc_attr($dropout_date ? date('Y-m-d', strtotime($dropout_date)) : ''); ?>" style="display:none;">
                    </td>
                    <td>
                        <?php if ($record->post_status === 'publish') : ?>
                            <button type="button" class="button-link dsa-dropout-button" data-group-id="<?php echo esc_attr($record->post_parent); ?>"><?php _e('Drop Out'); ?></button> |
                        <?php endif; ?>
                        <button type="button" class="button-link-delete dsa-delete-enrollment-button" data-record-id="<?php echo esc_attr($record->ID); ?>"><?php _e('Delete'); ?></button>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5"><?php esc_html_e('This student has no enrollment history.', 'dancestudio-app'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div id="dsa-enrollment-modal" title="<?php esc_attr_e('Enroll Student in Group', 'dancestudio-app'); ?>" style="display:none;">
            <p><label for="dsa-group-to-enroll">Group:</label><br><select id="dsa-group-to-enroll" style="width:100%;"></select></p>
            <p><label for="dsa-enroll-date">Enrollment Date:</label><br><input type="date" id="dsa-enroll-date" value="<?php echo date('Y-m-d'); ?>"></p>
        </div>
        <div id="dsa-dropout-modal" title="<?php esc_attr_e('Drop Out from Group', 'dancestudio-app'); ?>" style="display:none;">
            <input type="hidden" id="dsa-dropout-group-id" value="">
            <p><?php _e('Please select the date this student is dropping out.', 'dancestudio-app'); ?></p>
            <p><label for="dsa-dropout-date">Dropout Date:</label><br><input type="date" id="dsa-dropout-date" value="<?php echo date('Y-m-d'); ?>"></p>
        </div>
        <?php
    }
}