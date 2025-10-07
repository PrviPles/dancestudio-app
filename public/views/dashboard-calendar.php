<?php
/**
 * Student Dashboard -> Calendar Tab
 * @package DanceStudioApp
 */

if ( ! defined('WPINC') ) {
    die;
}
?>
<div class="dsa-calendar-wrapper">
    <h2><?php esc_html_e('Your Schedule', 'dancestudio-app'); ?></h2>
    <p><?php esc_html_e('This calendar shows your upcoming group classes, private lessons, and other studio events.', 'dancestudio-app'); ?></p>
    <div id="dsa-student-calendar">
        </div>
</div>