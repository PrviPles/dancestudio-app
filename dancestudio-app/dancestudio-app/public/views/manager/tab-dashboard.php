<?php
/**
 * View: Renders the content for the Manager Dashboard "Dashboard" tab.
 * UPGRADED VERSION with dynamic widgets.
 */
if ( ! defined( 'WPINC' ) ) die;

// Get the data from our new functions
$new_students = function_exists('dsa_get_new_students_this_month_count') ? dsa_get_new_students_this_month_count() : 0;
$upcoming_lessons = function_exists('dsa_get_upcoming_lessons_count') ? dsa_get_upcoming_lessons_count() : 0;
$active_students = function_exists('dsa_get_total_active_students_count') ? dsa_get_total_active_students_count() : 0;
?>
<style>
    .dsa-stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    .dsa-stat-card {
        background: #fff;
        border-radius: 8px;
        padding: 25px;
        border-left: 5px solid var(--dsa-secondary-color);
        box-shadow: var(--dsa-card-shadow);
    }
    .dsa-stat-card h3 {
        margin: 0 0 10px;
        font-size: 16px;
        color: #777;
    }
    .dsa-stat-card .stat-number {
        font-size: 42px;
        font-weight: bold;
        color: var(--dsa-primary-color);
    }
</style>

<h2><?php _e('Manager Dashboard', 'dancestudio-app'); ?></h2>
<p><?php _e('Here is a quick overview of your studio\'s activity.', 'dancestudio-app'); ?></p>

<div class="dsa-stat-grid" style="margin-top: 30px;">
    <div class="dsa-stat-card">
        <h3><?php _e('New Students This Month', 'dancestudio-app'); ?></h3>
        <div class="stat-number"><?php echo esc_html($new_students); ?></div>
    </div>

    <div class="dsa-stat-card">
        <h3><?php _e('Upcoming Private Lessons (Next 7 Days)', 'dancestudio-app'); ?></h3>
        <div class="stat-number"><?php echo esc_html($upcoming_lessons); ?></div>
    </div>

    <div class="dsa-stat-card">
        <h3><?php _e('Total Active Students', 'dancestudio-app'); ?></h3>
        <div class="stat-number"><?php echo esc_html($active_students); ?></div>
    </div>
</div>