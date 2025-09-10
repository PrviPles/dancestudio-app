<?php
/**
 * Student Dashboard -> Profile Tab
 *
 * @package DanceStudioApp
 */
if(!defined('WPINC')){die;}
$current_user_id=get_current_user_id();$user_data=get_userdata($current_user_id);$birthday=get_user_meta($current_user_id,'dsa_student_birthday',true);$phone=get_user_meta($current_user_id,'dsa_mobile_phone',true)?:get_user_meta($current_user_id,'billing_phone',true);$settings_page_url=add_query_arg('tab','settings',get_permalink());
?>
<div class="dsa-profile-page dsa-profile-view">
    <div class="dsa-profile-header" style="display:flex;align-items:center;margin-bottom:20px;gap:20px;flex-wrap:wrap;">
        <div class="dsa-profile-avatar"><?php echo get_avatar($current_user_id,150);?></div>
        <div class="dsa-profile-header-info">
            <h2><?php echo esc_html($user_data->display_name);?></h2>
            <div class="dsa-profile-groups"><strong><?php esc_html_e('Your Groups:','dancestudio-app');?></strong>
            <?php $assigned_groups=get_user_meta($current_user_id,'_dsa_user_member_of_groups',true);if(!empty($assigned_groups)&&is_array($assigned_groups)){$group_names=array_map(function($group_id){return esc_html(get_the_title($group_id));},$assigned_groups);echo '<span>'.implode(', ',array_filter($group_names)).'</span>';}else{echo '<span>'.esc_html__('Not assigned to any groups.','dancestudio-app').'</span>';}?>
            </div>
        </div>
    </div>
    <a href="<?php echo esc_url($settings_page_url);?>" style="display:inline-block;padding:10px 15px;background-color:#0073aa;color:#fff;text-decoration:none;border-radius:3px;margin-bottom:20px;"><?php esc_html_e('Edit Profile & Settings','dancestudio-app');?></a><hr>
    <h3><?php esc_html_e('Your Information','dancestudio-app');?></h3>
    <p><strong><?php esc_html_e('First Name:','dancestudio-app');?></strong> <?php echo esc_html($user_data->first_name);?></p>
    <p><strong><?php esc_html_e('Last Name:','dancestudio-app');?></strong> <?php echo esc_html($user_data->last_name);?></p>
    <p><strong><?php esc_html_e('Email Address:','dancestudio-app');?></strong> <?php echo esc_html($user_data->user_email);?></p>
    <p><strong><?php esc_html_e('Mobile Phone:','dancestudio-app');?></strong> <?php echo esc_html($phone?:__('Not provided','dancestudio-app'));?></p>
    <p><strong><?php esc_html_e('Birthday:','dancestudio-app');?></strong> <?php echo $birthday?esc_html(date_i18n(get_option('date_format'),strtotime($birthday))):__('Not provided','dancestudio-app');?></p>
</div>