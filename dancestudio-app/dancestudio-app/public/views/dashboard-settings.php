<?php
/**
 * Student Dashboard -> Settings (Edit Profile) Tab
 * @package DanceStudioApp
 */
if(!defined('WPINC')){die;}
if(!is_user_logged_in()){return;}
$current_user_id=get_current_user_id();$user_data=get_userdata($current_user_id);
?>
<div class="dsa-profile-form-wrapper">
    <h2><?php esc_html_e('Edit Your Profile','dancestudio-app');?></h2>
    <?php if(isset($_GET['profile_updated'])&&$_GET['profile_updated']==='true'){echo '<p style="color:green;">'.esc_html__('Your profile has been updated successfully!','dancestudio-app').'</p>';}if(isset($_GET['error'])){$error_message='';switch($_GET['error']){case 'password_mismatch':$error_message=__('The passwords do not match.','dancestudio-app');break;case 'invalid_email':$error_message=__('The email address is not valid or is already in use.','dancestudio-app');break;}if($error_message){echo '<p style="color:red;">'.esc_html($error_message).'</p>';}}?>
    <form id="dsa-profile-form" action="<?php echo esc_url(admin_url('admin-post.php'));?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="dsa_update_profile">
        <?php wp_nonce_field('dsa_update_profile_nonce','dsa_profile_nonce_field');?>
        <h3><?php esc_html_e('Personal Information','dancestudio-app');?></h3>
        <p><label for="dsa_first_name"><?php esc_html_e('First Name','dancestudio-app');?></label><br/><input type="text" name="first_name" id="dsa_first_name" value="<?php echo esc_attr($user_data->first_name);?>"/></p>
        <p><label for="dsa_last_name"><?php esc_html_e('Last Name','dancestudio-app');?></label><br/><input type="text" name="last_name" id="dsa_last_name" value="<?php echo esc_attr($user_data->last_name);?>"/></p>
        <p><label for="dsa_user_email"><?php esc_html_e('Email Address','dancestudio-app');?> *</label><br/><input type="email" name="user_email" id="dsa_user_email" value="<?php echo esc_attr($user_data->user_email);?>" required/></p>
        <p><label for="dsa_phone_number"><?php esc_html_e('Mobile Phone','dancestudio-app');?></label><br/><input type="tel" name="phone_number" id="dsa_phone_number" value="<?php echo esc_attr(get_user_meta($current_user_id,'dsa_mobile_phone',true));?>"/></p>
        <p><label for="dsa_student_birthday"><?php esc_html_e('Birthday','dancestudio-app');?></label><br/><input type="date" name="dsa_student_birthday" id="dsa_student_birthday" value="<?php echo esc_attr(get_user_meta($current_user_id,'dsa_student_birthday',true));?>"/></p>
        <hr><h3><?php esc_html_e('Change Password','dancestudio-app');?></h3><p><em><?php esc_html_e('Leave blank to keep your current password.','dancestudio-app');?></em></p>
        <p><label for="dsa_pass1"><?php esc_html_e('New Password','dancestudio-app');?></label><br/><input type="password" name="pass1" id="dsa_pass1" autocomplete="new-password"/></p>
        <p><label for="dsa_pass2"><?php esc_html_e('Confirm New Password','dancestudio-app');?></label><br/><input type="password" name="pass2" id="dsa_pass2" autocomplete="new-password"/></p>
        <hr>
        <?php do_action('edit_user_profile',$user_data);?>
        <p class="dsa-form-submit"><input type="submit" name="submit" value="<?php esc_attr_e('Update Profile','dancestudio-app');?>"/></p>
    </form>
</div>