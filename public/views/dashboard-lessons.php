<?php
/**
 * Student Dashboard -> Private Lessons Tab
 * @package DanceStudioApp
 */
if(!defined('WPINC')){die;}
$current_user_id=get_current_user_id();
?>
<div class="dsa-lessons-list-wrapper">
    <?php $wedding_date=get_user_meta($current_user_id,'dsa_wedding_date',true);$wedding_song=get_user_meta($current_user_id,'dsa_wedding_song',true);$song_url=get_user_meta($current_user_id,'dsa_couple_song_url',true);
    if($wedding_date||$wedding_song||$song_url):?>
    <div class="dsa-wedding-details" style="padding:15px;border:1px solid #eee;margin-bottom:30px;border-radius:5px;">
        <h3><?php esc_html_e('Your Wedding Dance Details','dancestudio-app');?></h3>
        <?php if($wedding_date):?><p><strong><?php esc_html_e('Wedding Date:','dancestudio-app');?></strong> <?php echo esc_html(date_i18n(get_option('date_format'),strtotime($wedding_date)));?></p><?php endif;?>
        <?php if($wedding_song):?><p><strong><?php esc_html_e('Wedding Song:','dancestudio-app');?></strong> <?php echo esc_html($wedding_song);?></p><?php endif;?>
        <?php if($song_url&&filter_var($song_url,FILTER_VALIDATE_URL)):?><p><strong><?php esc_html_e('Listen to Song:','dancestudio-app');?></strong></p><audio controls src="<?php echo esc_url($song_url);?>" style="width:100%;max-width:400px;"></audio><p><a href="<?php echo esc_url($song_url);?>" download><?php esc_html_e('Download Song','dancestudio-app');?></a></p><?php endif;?>
    </div>
    <?php endif;?>
    <h2><?php esc_html_e('Your Private Lessons History','dancestudio-app');?></h2>
    <?php $args=array('post_type'=>'dsa_private_lesson','posts_per_page'=>-1,'meta_query'=>array('relation'=>'OR',array('key'=>'_dsa_lesson_student1_id','value'=>$current_user_id),array('key'=>'_dsa_lesson_student2_id','value'=>$current_user_id)),'meta_key'=>'_dsa_lesson_date','orderby'=>'meta_value','order'=>'DESC');
    $lessons_query=new WP_Query($args);if($lessons_query->have_posts()):?>
    <table class="dsa-front-end-table"><thead><tr><th style="width:40px;">#</th><th><?php esc_html_e('Lesson & Notes','dancestudio-app');?></th><th><?php esc_html_e('Date','dancestudio-app');?></th><th><?php esc_html_e('Time','dancestudio-app');?></th><th><?php esc_html_e('Partner','dancestudio-app');?></th><th><?php esc_html_e('Teacher','dancestudio-app');?></th></tr></thead><tbody>
    <?php $lesson_count=$lessons_query->found_posts;while($lessons_query->have_posts()):$lessons_query->the_post();$lesson_id=get_the_ID();$s1_id=get_post_meta($lesson_id,'_dsa_lesson_student1_id',true);$s2_id=get_post_meta($lesson_id,'_dsa_lesson_student2_id',true);$t_id=get_post_meta($lesson_id,'_dsa_lesson_teacher_id',true);$l_date=get_post_meta($lesson_id,'_dsa_lesson_date',true);$s_time=get_post_meta($lesson_id,'_dsa_lesson_start_time',true);$notes=get_the_content();
    $partner_name=__('Solo Lesson','dancestudio-app');if($s1_id&&$s2_id){$p_id=($current_user_id==$s1_id)?$s2_id:$s1_id;$p_data=get_userdata($p_id);if($p_data)$partner_name=$p_data->display_name;}
    $teacher_name=$t_id?get_the_author_meta('display_name',$t_id):__('N/A','dancestudio-app');?>
    <tr><td data-label="#"><?php echo esc_html($lesson_count);?></td><td data-label="<?php esc_attr_e('Lesson','dancestudio-app');?>"><strong><?php the_title();?></strong><?php if(!empty($notes)):?><details style="margin-top:10px;font-size:0.9em;"><summary style="cursor:pointer;color:#555;"><?php esc_html_e('View Notes','dancestudio-app');?></summary><div style="margin-top:8px;padding:10px;border:1px solid #eee;border-radius:3px;background:#f9f9f9;"><?php echo wp_kses_post(wpautop($notes));?></div></details><?php endif;?></td>
    <td data-label="<?php esc_attr_e('Date','dancestudio-app');?>"><?php echo esc_html(date_i18n(get_option('date_format'),strtotime($l_date)));?></td><td data-label="<?php esc_attr_e('Time','dancestudio-app');?>"><?php echo esc_html(date_i18n(get_option('time_format'),strtotime($s_time)));?></td>
    <td data-label="<?php esc_attr_e('Partner','dancestudio-app');?>"><?php echo esc_html($partner_name);?></td><td data-label="<?php esc_attr_e('Teacher','dancestudio-app');?>"><?php echo esc_html($teacher_name);?></td></tr>
    <?php $lesson_count--;endwhile;wp_reset_postdata();?></tbody></table>
    <?php else:?><p><?php esc_html_e('You have no private lessons recorded.','dancestudio-app');?></p><?php endif;?>
</div>