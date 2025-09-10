<?php
/**
 * Template part for displaying the Dashboard tab content.
 * @package DanceStudioApp
 */
if(!defined('WPINC')){die;}
if(!function_exists('dsa_render_dashboard_tab')){
    function dsa_render_dashboard_tab(){
        $total_students = count(get_users(['role__in' => ['student', 'subscriber'], 'fields' => 'ID']));
        $total_staff = count(get_users(['role__in' => ['teacher', 'studio_manager', 'administrator'], 'fields' => 'ID']));
        
        $paired_user_ids = get_users([
            'role__in'     => ['student', 'subscriber'],
            'meta_key'     => 'dsa_partner_user_id',
            'meta_compare' => 'EXISTS',
            'fields'       => 'ID',
        ]);
        
        // Correctly calculate the number of couples
        $total_couples = count($paired_user_ids) / 2;
        
        ?>
        <div id="dashboard-tab-content">
            <p><?php _e('Welcome! Manage students, classes, and groups from the dedicated menu items on the left. Use the tabs above for other sections.','dancestudio-app');?></p>
            
            <div id="dsa-dashboard-stats" style="margin-top:15px;margin-bottom:25px;overflow:auto;display:flex;flex-wrap:wrap;gap:2%;">
                <div style="flex:1;min-width:200px;">
                    <div class="postbox">
                        <h2 class="hndle"><span><span class="dashicons dashicons-groups"></span> <?php _e('Total Students','dancestudio-app');?></span></h2>
                        <div class="inside"><p style="font-size:2em;text-align:center;margin:10px 0;"><?php echo esc_html($total_students);?></p></div>
                    </div>
                </div>
                <div style="flex:1;min-width:200px;">
                    <div class="postbox">
                        <h2 class="hndle"><span><span class="dashicons dashicons-heart"></span> <?php _e('Active Couples','dancestudio-app');?></span></h2>
                        <div class="inside"><p style="font-size:2em;text-align:center;margin:10px 0;"><?php echo esc_html(floor($total_couples));?></p></div>
                    </div>
                </div>
                <div style="flex:1;min-width:200px;">
                    <div class="postbox">
                        <h2 class="hndle"><span><span class="dashicons dashicons-businessperson"></span> <?php _e('Total Staff','dancestudio-app');?></span></h2>
                        <div class="inside"><p style="font-size:2em;text-align:center;margin:10px 0;"><?php echo esc_html($total_staff);?></p></div>
                    </div>
                </div>
            </div>
            <div style="clear:both;"></div>
        </div>
    <?php
    }
}
?>