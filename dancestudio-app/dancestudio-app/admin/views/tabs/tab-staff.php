<?php
/**
 * Template part for displaying the Staff tab content.
 * @package DanceStudioApp
 */
if(!defined('WPINC')){die;}
if(!function_exists('dsa_render_staff_tab')){
    function dsa_render_staff_tab(){
        ?>
        <div id="staff-tab-content">
            <h3><?php _e('Studio Managers', 'dancestudio-app'); ?></h3>
            <table class="wp-list-table widefat fixed striped users">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-username"><?php _e('Username','dancestudio-app');?></th>
                        <th scope="col" class="manage-column column-name"><?php _e('Name','dancestudio-app');?></th>
                        <th scope="col" class="manage-column column-email"><?php _e('Email','dancestudio-app');?></th>
                        <th scope="col" class="manage-column"><?php _e('Date of Birth','dancestudio-app');?></th>
                        <th scope="col" class="manage-column" style="width:50px;"><?php _e('Age','dancestudio-app');?></th>
                        <th scope="col" class="manage-column"><?php _e('Actions','dancestudio-app');?></th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $studio_managers = get_users(['role__in' => ['studio_manager', 'administrator']]);
                if (!empty($studio_managers)):
                    foreach ($studio_managers as $staff):
                        $full_name = trim($staff->first_name . ' ' . $staff->last_name) ?: $staff->display_name;
                        $edit_link = get_edit_user_link($staff->ID);
                        $birth_date_raw = get_user_meta($staff->ID, '_dsa_user_birth_date', true);
                        $birth_date_formatted = $birth_date_raw ? date_i18n(get_option('date_format'), strtotime($birth_date_raw)) : '—';
                        $age = $birth_date_raw && function_exists('dsa_calculate_age') ? dsa_calculate_age($birth_date_raw) : '—';
                        ?>
                        <tr>
                            <td class="username column-username has-row-actions column-primary" data-colname="<?php esc_attr_e('Username');?>">
                                <?php echo get_avatar($staff->ID, 32); ?>
                                <strong><a href="<?php echo esc_url($edit_link); ?>"><?php echo esc_html($staff->user_login); ?></a></strong>
                            </td>
                            <td data-colname="<?php esc_attr_e('Name');?>"><?php echo esc_html($full_name); ?></td>
                            <td data-colname="<?php esc_attr_e('Email');?>"><a href="mailto:<?php echo esc_attr($staff->user_email); ?>"><?php echo esc_html($staff->user_email); ?></a></td>
                            <td data-colname="<?php esc_attr_e('Date of Birth','dancestudio-app');?>"><?php echo esc_html($birth_date_formatted); ?></td>
                            <td data-colname="<?php esc_attr_e('Age','dancestudio-app');?>"><?php echo esc_html($age); ?></td>
                            <td><a href="<?php echo esc_url($edit_link); ?>" class="button button-secondary button-small"><?php _e('Edit');?></a></td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr><td colspan="6"><?php _e('No users found with the "Studio Manager" or "Administrator" role.','dancestudio-app');?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <hr style="margin: 30px 0;">

            <h3>
                <?php _e('Teachers', 'dancestudio-app'); ?>
                <a href="<?php echo esc_url(admin_url('user-new.php'));?>" class="page-title-action"><?php _e('Add New Staff Member','dancestudio-app');?></a>
            </h3>
            <p><em><?php _e("To add someone to these lists, assign them the 'Teacher' or 'Studio Manager' role on their user profile.",'dancestudio-app');?></em></p>
            <table class="wp-list-table widefat fixed striped users">
                <thead><tr><th><?php _e('Username');?></th><th><?php _e('Name');?></th><th><?php _e('Email');?></th><th><?php _e('Date of Birth','dancestudio-app');?></th><th style="width:50px;"><?php _e('Age','dancestudio-app');?></th><th><?php _e('Actions');?></th></tr></thead>
                <tbody>
                <?php 
                $teachers = get_users(['role__in' => ['teacher']]);
                if(!empty($teachers)):
                    foreach ($teachers as $teacher):
                        $full_name = trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->display_name;
                        $edit_link = get_edit_user_link($teacher->ID);
                        $delete_link = wp_nonce_url(admin_url('users.php?action=delete&user=' . $teacher->ID), 'delete-user_' . $teacher->ID);
                        $birth_date_raw = get_user_meta($teacher->ID, '_dsa_user_birth_date', true);
                        $birth_date_formatted = $birth_date_raw ? date_i18n(get_option('date_format'), strtotime($birth_date_raw)) : '—';
                        $age = $birth_date_raw && function_exists('dsa_calculate_age') ? dsa_calculate_age($birth_date_raw) : '—';
                        ?>
                        <tr>
                            <td class="username column-username has-row-actions column-primary" data-colname="<?php esc_attr_e('Username');?>">
                                <?php echo get_avatar($teacher->ID, 32); ?>
                                <strong><a href="<?php echo esc_url($edit_link); ?>"><?php echo esc_html($teacher->user_login); ?></a></strong>
                            </td>
                            <td data-colname="<?php esc_attr_e('Name');?>"><?php echo esc_html($full_name); ?></td>
                            <td data-colname="<?php esc_attr_e('Email');?>"><a href="mailto:<?php echo esc_attr($teacher->user_email); ?>"><?php echo esc_html($teacher->user_email); ?></a></td>
                            <td data-colname="<?php esc_attr_e('Date of Birth','dancestudio-app');?>"><?php echo esc_html($birth_date_formatted); ?></td>
                            <td data-colname="<?php esc_attr_e('Age','dancestudio-app');?>"><?php echo esc_html($age); ?></td>
                            <td>
                                <a href="<?php echo esc_url($edit_link); ?>" class="button button-secondary button-small"><?php _e('Edit');?></a>
                                <a href="<?php echo esc_url($delete_link); ?>" class="button button-link-delete" onclick="return confirm('<?php echo esc_js(sprintf(__('Are you sure you want to delete %s?'), $teacher->display_name));?>');"><?php _e('Delete');?></a>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr><td colspan="6"><?php _e('No users found with the "Teacher" role.','dancestudio-app');?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
?>