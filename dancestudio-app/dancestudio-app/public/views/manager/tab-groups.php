<?php
/**
 * View: Renders the AJAX-powered "Groups" tab for the Manager Dashboard.
 */
if ( ! defined( 'WPINC' ) ) die; 
?>

<h2>
    <?php esc_html_e('Manage Dance Groups', 'dancestudio-app'); ?>
    <button type="button" id="dsa-new-group-button" class="button button-primary" style="margin-left: 10px;"><?php esc_html_e('New Group', 'dancestudio-app'); ?></button>
</h2>
<p><?php _e('Manage all your dance groups. To add or remove students, use the main admin area for now.', 'dancestudio-app'); ?></p>

<table class="dsa-front-end-table" id="dsa-all-groups-table">
    <thead>
        <tr>
            <th><?php _e('Group Name', 'dancestudio-app'); ?></th>
            <th><?php _e('Actions', 'dancestudio-app'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $all_groups = get_posts(['post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
        if (empty($all_groups)) : ?>
            <tr id="dsa-no-groups-row"><td colspan="2"><?php _e('No groups found.', 'dancestudio-app'); ?></td></tr>
        <?php else :
            foreach ($all_groups as $group): ?>
            <tr class="dsa-group-row" id="dsa-group-row-<?php echo esc_attr($group->ID); ?>">
                <td class="dsa-group-name"><?php echo esc_html(get_the_title($group->ID)); ?></td>
                <td class="dsa-actions">
                    <button type="button" class="button-secondary dsa-edit-group-button" data-group-id="<?php echo esc_attr($group->ID); ?>" data-group-name="<?php echo esc_attr(get_the_title($group->ID)); ?>"><?php _e('Edit', 'dancestudio-app'); ?></button>
                    <button type="button" class="button-link-delete dsa-delete-group-button" data-group-id="<?php echo esc_attr($group->ID); ?>"><?php _e('Delete', 'dancestudio-app'); ?></button>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<div id="dsa-new-group-modal" style="display:none;" title="<?php esc_attr_e('Create New Group', 'dancestudio-app'); ?>">
    <form id="dsa-new-group-form">
        <p>
            <label for="dsa_new_group_name"><?php _e('Group Name:', 'dancestudio-app'); ?></label>
            <input type="text" id="dsa_new_group_name" name="group_name" class="widefat" required>
        </p>
    </form>
</div>

<div id="dsa-edit-group-modal" style="display:none;" title="<?php esc_attr_e('Edit Group Name', 'dancestudio-app'); ?>">
    <form id="dsa-edit-group-form">
        <input type="hidden" id="dsa_edit_group_id" name="group_id" value="">
        <p>
            <label for="dsa_edit_group_name"><?php _e('Group Name:', 'dancestudio-app'); ?></label>
            <input type="text" id="dsa_edit_group_name" name="group_name" class="widefat" required>
        </p>
    </form>
</div>