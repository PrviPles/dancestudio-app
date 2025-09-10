<?php
/**
 * View: Renders the content for the "Calendar" tab.
 * NOW INCLUDES AN INTERACTIVE LEGEND.
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) die;

if ( ! function_exists( 'dsa_render_calendar_tab' ) ) {
    function dsa_render_calendar_tab() {
        ?>
        <div class="dsa-calendar-wrap">
            <h2><?php _e('Studio Calendar', 'dancestudio-app'); ?></h2>
            <p><?php _e('Overview of all scheduled events. Click on any date to add a new event, or click an existing event to view its details.', 'dancestudio-app'); ?></p>
            
            <div id="dsa-calendar-legend" style="padding: 10px 15px; background: #f6f7f7; border: 1px solid #c3c4c7; margin-bottom: 20px; border-radius: 4px; display: flex; flex-wrap: wrap; gap: 20px;">
                <strong><?php _e('Show/Hide:', 'dancestudio-app'); ?></strong>
                <label>
                    <input type="checkbox" class="dsa-event-filter" value="group_class" checked="checked" />
                    <span style="display: inline-block; width: 12px; height: 12px; background-color: #3a87ad; border-radius: 50%; margin-right: 5px; vertical-align: middle;"></span>
                    <?php _e('Group Class', 'dancestudio-app'); ?>
                </label>
                <label>
                    <input type="checkbox" class="dsa-event-filter" value="private_lesson" checked="checked" />
                    <span style="display: inline-block; width: 12px; height: 12px; background-color: #46a546; border-radius: 50%; margin-right: 5px; vertical-align: middle;"></span>
                    <?php _e('Private Lesson', 'dancestudio-app'); ?>
                </label>
                <label>
                    <input type="checkbox" class="dsa-event-filter" value="birthday" checked="checked" />
                    <span style="display: inline-block; width: 12px; height: 12px; background-color: #f89406; border-radius: 50%; margin-right: 5px; vertical-align: middle;"></span>
                    <?php _e('Birthday', 'dancestudio-app'); ?>
                </label>
                <label>
                    <input type="checkbox" class="dsa-event-filter" value="holiday" checked="checked" />
                    <span style="display: inline-block; width: 12px; height: 12px; background-color: #d9534f; border-radius: 50%; margin-right: 5px; vertical-align: middle;"></span>
                    <?php _e('Holiday', 'dancestudio-app'); ?>
                </label>
            </div>

            <div id="dsa-admin-calendar"></div>
        </div>
        <?php
    }
}
?>