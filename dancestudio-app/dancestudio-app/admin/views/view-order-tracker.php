<?php
/**
 * View: Renders the Order Tracker report page.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! function_exists( 'dsa_render_order_tracker_page' ) ) {
    function dsa_render_order_tracker_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Order & Lesson Package Tracker', 'dancestudio-app' ); ?></h1>
            <p><?php esc_html_e( 'This report shows all paid lesson packages and tracks how many lessons have been used.', 'dancestudio-app' ); ?></p>
            
            <?php 
            // Call our new reusable function to display the table
            if ( function_exists('dsa_render_order_tracker_table') ) {
                dsa_render_order_tracker_table();
            }
            ?>

        </div>
        <?php
    }
}
?>