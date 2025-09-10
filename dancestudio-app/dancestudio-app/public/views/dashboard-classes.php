<?php
/**
 * Student Dashboard -> My Group Classes Tab
 * @package DanceStudioApp
 */
if(!defined('WPINC')){die;}
$current_user_id=get_current_user_id();
?>
<div class="dsa-group-classes-list-wrapper">
    <h2><?php esc_html_e('Your Group Class Schedule & History','dancestudio-app');?></h2>
    <?php $assigned_groups=get_user_meta($current_user_id,'_dsa_user_member_of_groups',true);
    if(empty($assigned_groups)||!is_array($assigned_groups)){echo '<p>'.esc_html__('You are not currently assigned to any dance groups.','dancestudio-app').'</p>';
    }else{$args=array('post_type'=>'dsa_group_class','posts_per_page'=>-1,'meta_query'=>array(array('key'=>'_dsa_class_group_id','value'=>$assigned_groups,'compare'=>'IN')),'meta_key'=>'_dsa_class_date','orderby'=>'meta_value','order'=>'DESC');
    $classes_query=new WP_Query($args);if($classes_query->have_posts()):?>
    <table class="dsa-front-end-table"><thead><tr><th><?php esc_html_e('Class','dancestudio-app');?></th><th><?php esc_html_e('Date','dancestudio-app');?></th><th><?php esc_html_e('Time','dancestudio-app');?></th><th><?php esc_html_e('Group','dancestudio-app');?></th><th><?php esc_html_e('Your Attendance','dancestudio-app');?></th></tr></thead><tbody>
    <?php while($classes_query->have_posts()):$classes_query->the_post();$class_id=get_the_ID();$group_id=get_post_meta($class_id,'_dsa_class_group_id',true);$class_date=get_post_meta($class_id,'_dsa_class_date',true);$start_time=get_post_meta($class_id,'_dsa_class_start_time',true);$attendance_data=get_post_meta($class_id,'_dsa_class_attendance',true);
    $group_name=$group_id?get_the_title($group_id):__('N/A','dancestudio-app');$attendance_status='—';if(is_array($attendance_data)&&array_key_exists($current_user_id,$attendance_data)){if(!empty($attendance_data[$current_user_id]['attended'])){$attendance_status='<span style="color:green;">'.__('Attended','dancestudio-app').'</span>';}else{$attendance_status='<span style="color:red;">'.__('Absent','dancestudio-app').'</span>';}}?>
    <tr><td data-label="<?php esc_attr_e('Class','dancestudio-app');?>"><?php the_title();?></td><td data-label="<?php esc_attr_e('Date','dancestudio-app');?>"><?php echo esc_html(date_i18n(get_option('date_format'),strtotime($class_date)));?></td><td data-label="<?php esc_attr_e('Time','dancestudio-app');?>"><?php echo esc_html(date_i18n(get_option('time_format'),strtotime($start_time)));?></td><td data-label="<?php esc_attr_e('Group','dancestudio-app');?>"><?php echo esc_html($group_name);?></td><td data-label="<?php esc_attr_e('Your Attendance','dancestudio-app');?>"><?php echo $attendance_status;?></td></tr>
    <?php endwhile;wp_reset_postdata();?></tbody></table>
    <?php else:?><p><?php esc_html_e('There are no classes scheduled for your group(s) at this time.','dancestudio-app');?></p><?php endif;}?>
</div>