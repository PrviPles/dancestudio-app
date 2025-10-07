<?php
if ( ! defined( 'WPINC' ) ) die;
if ( ! function_exists( 'dsa_render_packages_orders_tab_content' ) ) {
    function dsa_render_packages_orders_tab_content( $student_data ) {
        if ( function_exists('dsa_render_order_tracker_table') ) {
            dsa_render_order_tracker_table(['customer_id' => $student_data->ID]);
        } else {
            echo '<p>' . esc_html__('Order tracker function not found.', 'dancestudio-app') . '</p>';
        }
    }
}