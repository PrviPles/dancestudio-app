<?php
/**
 * Template part for displaying the Attendance tab content.
 * FINAL CORRECTED VERSION with full code for both sub-tabs.
 *
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! function_exists('dsa_render_attendance_tab') ) {
    /**
     * Renders the main content for the "Attendance" tab, including sub-tab navigation.
     */
    function dsa_render_attendance_tab() {
        $active_attendance_view = isset( $_GET['att_view'] ) && 'by_student' === $_GET['att_view'] ? 'by_student' : 'by_group';
        ?>
        <h3><?php esc_html_e( 'Attendance Reports', 'dancestudio-app' ); ?></h3>
        <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
            <a href="<?php echo esc_url(remove_query_arg(['group_id','student_id','date_range','orderby','order','view_report'], add_query_arg('att_view', 'by_group'))); ?>" class="nav-tab <?php echo $active_attendance_view === 'by_group' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Report by Group', 'dancestudio-app'); ?></a>
            <a href="<?php echo esc_url(remove_query_arg(['group_id','student_id','date_range','orderby','order','view_report'], add_query_arg('att_view', 'by_student'))); ?>" class="nav-tab <?php echo $active_attendance_view === 'by_student' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Report by Student', 'dancestudio-app'); ?></a>
        </h2>

        <?php
        if ( 'by_group' === $active_attendance_view ) {
            if ( function_exists('dsa_render_attendance_by_group_view') ) {
                dsa_render_attendance_by_group_view();
            }
        } else {
            if ( function_exists('dsa_render_attendance_by_student_view') ) {
                dsa_render_attendance_by_student_view();
            }
        }
    }
}

if ( ! function_exists('dsa_render_attendance_by_group_view') ) {
    function dsa_render_attendance_by_group_view() {
        $selected_group_id=isset($_GET['group_id'])?absint($_GET['group_id']):0;$selected_range=isset($_GET['date_range'])?sanitize_key($_GET['date_range']):'all_time';$sortable_columns=['name'=>'first_name','surname'=>'last_name','attended'=>'attended','missed'=>'missed','total'=>'total','percentage'=>'percentage'];$orderby=(isset($_GET['orderby'])&&array_key_exists($_GET['orderby'],$sortable_columns))?$_GET['orderby']:'surname';$order=(isset($_GET['order'])&&in_array(strtolower($_GET['order']),['asc','desc']))?$_GET['order']:'asc';
        ?>
        <p><?php esc_html_e( 'Select a group and a date range to generate a sortable attendance report for its members.', 'dancestudio-app' ); ?></p>
        <form method="GET" action="<?php echo esc_url( admin_url('admin.php') ); ?>" style="margin-bottom:20px;padding:15px;background:#f6f7f7;border:1px solid #c3c4c7;display:flex;flex-wrap:wrap;align-items:center;gap:20px;">
            <input type="hidden" name="page" value="dsa-attendance-tab"><input type="hidden" name="att_view" value="by_group">
            <div>
                <label for="dsa_group_select" style="display:block;margin-bottom:5px;font-weight:bold;"><?php esc_html_e('Select Dance Group:','dancestudio-app'); ?></label>
                <?php $all_groups_query=new WP_Query(['post_type'=>'dsa_group','posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC']);if($all_groups_query->have_posts()):echo '<select name="group_id" id="dsa_group_select" class="regular-text">';echo '<option value="0">'.esc_html__('-- Choose a Group --','dancestudio-app').'</option>';while($all_groups_query->have_posts()):$all_groups_query->the_post();echo '<option value="'.esc_attr(get_the_ID()).'" '.selected($selected_group_id,get_the_ID(),false).'>'.esc_html(get_the_title()).'</option>';endwhile;echo '</select>';wp_reset_postdata();else:echo '<span>'.esc_html__('No dance groups created.','dancestudio-app').'</span>';endif;?>
            </div>
            <div>
                <label for="dsa_date_range_select" style="display:block;margin-bottom:5px;font-weight:bold;"><?php esc_html_e('Date Range:','dancestudio-app');?></label>
                <select name="date_range" id="dsa_date_range_select"><option value="all_time" <?php selected('all_time',$selected_range);?>><?php esc_html_e('All Time');?></option><option value="this_month" <?php selected('this_month',$selected_range);?>><?php esc_html_e('This Month');?></option><option value="this_year" <?php selected('this_year',$selected_range);?>><?php esc_html_e('This Year');?></option></select>
            </div>
            <div><?php submit_button(__('Generate Report'),'primary','view_report',false,['style'=>'margin-top:24px;']);?></div>
        </form>
        <?php if($selected_group_id>0):echo '<hr><h2>'.sprintf(esc_html__('Report for: %s','dancestudio-app'),esc_html(get_the_title($selected_group_id))).'</h2>';$student_members=[];$all_students=get_users(['role__in'=>['student','subscriber']]);foreach($all_students as $student){$groups=get_user_meta($student->ID,'_dsa_user_member_of_groups',true);if(is_array($groups)&&in_array($selected_group_id,$groups)){$student_members[$student->ID]=['first_name'=>$student->first_name,'last_name'=>$student->last_name,'attended'=>0,'total'=>0];}}
        if(empty($student_members)){echo '<p>'.esc_html__('No students assigned.','dancestudio-app').'</p>';}else{$date_query_args=[];switch($selected_range){case'this_week':$date_query_args=['year'=>date('Y'),'week'=>date('W')];break;case'this_month':$date_query_args=['year'=>date('Y'),'monthnum'=>date('n')];break;case'this_year':$date_query_args=['year'=>date('Y')];break;}$classes_query_args=['post_type'=>'dsa_group_class','posts_per_page'=>-1,'meta_query'=>[['key'=>'_dsa_class_group_id','value'=>$selected_group_id]],'date_query'=>$date_query_args,];$classes_query=new WP_Query($classes_query_args);if($classes_query->have_posts()){while($classes_query->have_posts()){$classes_query->the_post();$att_data=get_post_meta(get_the_ID(),'_dsa_class_attendance',true);if(!is_array($att_data))continue;foreach($student_members as $sid=>&$data){if(array_key_exists($sid,$att_data)){$data['total']++;if(!empty($att_data[$sid]['attended'])){$data['attended']++;}}}}}unset($data);wp_reset_postdata();foreach($student_members as &$stats){$stats['missed']=$stats['total']-$stats['attended'];$stats['percentage']=($stats['total']>0)?round(($stats['attended']/$stats['total'])*100):0;}unset($stats);
        $sort_key=$sortable_columns[$orderby];uasort($student_members,function($a,$b)use($sort_key,$order){$val_a=$a[$sort_key];$val_b=$b[$sort_key];if(is_numeric($val_a)&&is_numeric($val_b)){$result=$val_a<=>$val_b;}else{$result=strnatcasecmp($val_a,$val_b);};return($order==='asc')?$result:-$result;});
        ?><table class="wp-list-table widefat striped" style="margin-top:20px;"><thead><tr><?php if(!function_exists('dsa_print_sortable_report_header')){function dsa_print_sortable_report_header($l,$k,$co,$o){$link=add_query_arg(['orderby'=>$k,'order'=>($co===$k&&$o==='asc')?'desc':'asc']);$css='manage-column';if($co===$k){$css.=' sorted '.$o;}else{$css.=' sortable desc';}echo '<th scope="col" class="'.esc_attr($css).'"><a href="'.esc_url($link).'"><span>'.esc_html($l).'</span><span class="sorting-indicator"></span></a></th>';}}dsa_print_sortable_report_header(__('Name'),'name',$orderby,$order);dsa_print_sortable_report_header(__('Surname'),'surname',$orderby,$order);dsa_print_sortable_report_header(__('Attended'),'attended',$orderby,$order);dsa_print_sortable_report_header(__('Missed'),'missed',$orderby,$order);dsa_print_sortable_report_header(__('Total Classes'),'total',$orderby,$order);dsa_print_sortable_report_header(__('Attendance %'),'percentage',$orderby,$order);?></tr></thead>
        <tbody><?php foreach($student_members as $stats):?><tr><td><strong><?php echo esc_html($stats['first_name']);?></strong></td><td><strong><?php echo esc_html($stats['last_name']);?></strong></td><td><?php echo esc_html($stats['attended']);?></td><td><?php echo esc_html($stats['missed']);?></td><td><?php echo esc_html($stats['total']);?></td><td><?php echo esc_html($stats['percentage']);?>%</td></tr><?php endforeach;?></tbody></table>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" style="margin-top:20px;"><input type="hidden" name="action" value="dsa_export_attendance_csv"><input type="hidden" name="group_id" value="<?php echo esc_attr($selected_group_id);?>"><input type="hidden" name="date_range" value="<?php echo esc_attr($selected_range);?>"><?php wp_nonce_field('dsa_export_nonce_action','dsa_export_nonce');?><?php submit_button(__('Export to CSV','dancestudio-app'),'secondary');?></form>
        <?php } endif;
    }
}

if(!function_exists('dsa_render_attendance_by_student_view')){
    function dsa_render_attendance_by_student_view(){
        $selected_student_id=isset($_GET['student_id'])?absint($_GET['student_id']):0;$selected_range=isset($_GET['date_range'])?sanitize_key($_GET['date_range']):'all_time';?>
        <p><?php esc_html_e('Select a student and a date range to view their complete attendance history.','dancestudio-app');?></p>
        <form method="GET" action="<?php echo esc_url(admin_url('admin.php'));?>" style="margin-bottom:20px;padding:15px;background:#f6f7f7;border:1px solid #c3c4c7;display:flex;flex-wrap:wrap;align-items:center;gap:20px;"><input type="hidden" name="page" value="dsa-attendance-tab"><input type="hidden" name="att_view" value="by_student"><div><label for="dsa_student_select" style="display:block;margin-bottom:5px;font-weight:bold;"><?php esc_html_e('Select Student:','dancestudio-app');?></label><?php wp_dropdown_users(['name'=>'student_id','id'=>'dsa_student_select','show_option_none'=>__('-- Choose a Student --','dancestudio-app'),'role__in'=>['subscriber','student'],'show_fullname'=>true,'orderby'=>'display_name','order'=>'ASC','selected'=>$selected_student_id]);?></div>
        <div><label for="dsa_date_range_select_student" style="display:block;margin-bottom:5px;font-weight:bold;"><?php esc_html_e('Select Date Range:','dancestudio-app');?></label><select name="date_range" id="dsa_date_range_select_student"><option value="all_time" <?php selected('all_time',$selected_range);?>><?php esc_html_e('All Time');?></option><option value="this_month" <?php selected('this_month',$selected_range);?>><?php esc_html_e('This Month');?></option><option value="this_year" <?php selected('this_year',$selected_range);?>><?php esc_html_e('This Year');?></option></select></div>
        <div><?php submit_button(__('Generate Report','dancestudio-app'),'primary','view_report',false,['style'=>'margin-top:24px;']);?></div></form>
        <?php if($selected_student_id>0):$student_data=get_userdata($selected_student_id);echo '<hr><h2>'.sprintf(esc_html__('Report for: %s','dancestudio-app'),esc_html($student_data->display_name)).'</h2>';$assigned_groups=get_user_meta($selected_student_id,'_dsa_user_member_of_groups',true);if(empty($assigned_groups)||!is_array($assigned_groups)){echo '<p>'.esc_html__('This student is not in any groups.').'</p>';return;}
        $date_query_args=[];switch($selected_range){case'this_month':$date_query_args=['year'=>date('Y'),'monthnum'=>date('n')];break;case'this_year':$date_query_args=['year'=>date('Y')];break;}$class_args=['post_type'=>'dsa_group_class','posts_per_page'=>-1,'meta_query'=>[['key'=>'_dsa_class_group_id','value'=>$assigned_groups,'compare'=>'IN']],'date_query'=>$date_query_args,'meta_key'=>'_dsa_class_date','orderby'=>'meta_value','order'=>'DESC'];$classes_query=new WP_Query($class_args);if($classes_query->have_posts()):$attended_count=0;$total_count=0;$lesson_log=[];while($classes_query->have_posts()):$classes_query->the_post();$cid=get_the_ID();$att_data=get_post_meta($cid,'_dsa_class_attendance',true);if(is_array($att_data)&&array_key_exists($selected_student_id,$att_data)){$total_count++;$is_attended=!empty($att_data[$selected_student_id]['attended']);if($is_attended)$attended_count++;$lesson_log[]=['date'=>get_post_meta($cid,'_dsa_class_date',true),'time'=>get_post_meta($cid,'_dsa_class_start_time',true),'title'=>get_the_title(),'group'=>get_the_title(get_post_meta($cid,'_dsa_class_group_id',true)),'status'=>$is_attended?__('Attended','dancestudio-app'):__('Absent','dancestudio-app'),'remarks'=>isset($att_data[$selected_student_id]['remarks'])?$att_data[$selected_student_id]['remarks']:''];}endwhile;wp_reset_postdata();
        $percentage=($total_count>0)?round(($attended_count/$total_count)*100):0;?><div style="margin-bottom:20px;"><strong><?php _e('Summary:','dancestudio-app');?></strong> <?php printf(__('Attended %d of %d classes (%s%%)','dancestudio-app'),$attended_count,$total_count,$percentage);?></div>
        <table class="wp-list-table widefat striped"><thead><tr><th><?php _e('Date');?></th><th><?php _e('Time');?></th><th><?php _e('Class');?></th><th><?php _e('Group');?></th><th><?php _e('Status');?></th><th><?php _e('Remarks');?></th></tr></thead>
        <tbody><?php foreach($lesson_log as $log):?><tr><td><?php echo esc_html(date_i18n(get_option('date_format'),strtotime($log['date'])));?></td><td><?php echo esc_html(date_i18n(get_option('time_format'),strtotime($log['time'])));?></td><td><?php echo esc_html($log['title']);?></td><td><?php echo esc_html($log['group']);?></td><td><span style="color:<?php echo $log['status']==__('Attended','dancestudio-app')?'green':'red';?>"><?php echo esc_html($log['status']);?></span></td><td><?php echo esc_html($log['remarks']);?></td></tr><?php endforeach;?></tbody></table>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" style="margin-top:20px;"><input type="hidden" name="action" value="dsa_export_student_attendance_csv"><input type="hidden" name="student_id" value="<?php echo esc_attr($selected_student_id);?>"><input type="hidden" name="date_range" value="<?php echo esc_attr($selected_range);?>"><?php wp_nonce_field('dsa_export_student_nonce_action','dsa_export_student_nonce');?><?php submit_button(__('Export to CSV','dancestudio-app'),'secondary');?></form>
        <?php else:echo '<p>'.__('No classes found for this student in the selected date range.','dancestudio-app').'</p>';endif;
        endif;
    }
}
?>