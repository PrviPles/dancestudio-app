<?php
/**
 * View: Main Settings Dashboard with Tabs
 */
if(!defined('WPINC')){die;}
if(!function_exists('dsa_render_main_settings_page_layout')){
    function dsa_render_main_settings_page_layout($active_tab = 'dashboard'){
        ?>
        <div class="wrap dsa-settings-wrap">
            <h1><?php echo esc_html(get_admin_page_title());?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo admin_url('admin.php?page=dsa-dashboard');?>" class="nav-tab <?php if($active_tab=='dashboard')echo 'nav-tab-active';?>"><?php _e('Dashboard','dancestudio-app');?></a>
                <a href="<?php echo admin_url('admin.php?page=dsa-students-tab');?>" class="nav-tab <?php if($active_tab=='students')echo 'nav-tab-active';?>"><?php _e('Students','dancestudio-app');?></a>
                <a href="<?php echo admin_url('admin.php?page=dsa-dance-figures');?>" class="nav-tab <?php if($active_tab=='dance_figures')echo 'nav-tab-active';?>"><?php _e('Dance Figures','dancestudio-app');?></a>
                <a href="<?php echo admin_url('admin.php?page=dsa-couples-tab');?>" class="nav-tab <?php if($active_tab=='couples')echo 'nav-tab-active';?>"><?php _e('Couples','dancestudio-app');?></a>
                <a href="<?php echo admin_url('admin.php?page=dsa-staff-tab');?>" class="nav-tab <?php if($active_tab=='staff')echo 'nav-tab-active';?>"><?php _e('Staff','dancestudio-app');?></a>
                <a href="<?php echo admin_url('admin.php?page=dsa-calendar-tab');?>" class="nav-tab <?php if($active_tab=='calendar')echo 'nav-tab-active';?>"><?php _e('Calendar','dancestudio-app');?></a>
                <a href="<?php echo admin_url('admin.php?page=dsa-attendance-tab');?>" class="nav-tab <?php if($active_tab=='attendance')echo 'nav-tab-active';?>"><?php _e('Attendance','dancestudio-app');?></a>
                <a href="<?php echo admin_url('admin.php?page=dsa-settings-tab');?>" class="nav-tab <?php if($active_tab=='settings')echo 'nav-tab-active';?>"><?php _e('Settings','dancestudio-app');?></a>
            </h2>

            <div class="dsa-tab-content-wrapper" style="background-color:#fff;padding:20px;border:1px solid #ccd0d4;border-top:none;">
            <?php
            switch($active_tab){
                case 'students': if(function_exists('dsa_render_students_tab')) dsa_render_students_tab(); break;
                case 'dance_figures': if(function_exists('dsa_render_dance_figures_tab')) dsa_render_dance_figures_tab(); break;
                case 'couples': if(function_exists('dsa_render_couples_tab')) dsa_render_couples_tab(); break;
                case 'staff': if(function_exists('dsa_render_staff_tab')) dsa_render_staff_tab(); break;
                case 'calendar': if(function_exists('dsa_render_calendar_tab')) dsa_render_calendar_tab(); break;
                case 'attendance': if(function_exists('dsa_render_attendance_tab')) dsa_render_attendance_tab(); break;
                case 'settings': if(function_exists('dsa_render_settings_tab')) dsa_render_settings_tab(); break;
                case 'dashboard': default: if(function_exists('dsa_render_dashboard_tab')) dsa_render_dashboard_tab(); break;
            }
            ?>
            </div>
        </div>
        <?php
    }
}
?>