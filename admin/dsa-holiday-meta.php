<?php
/**
 * Adds a meta box for the Holiday CPT.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

add_action( 'add_meta_boxes_dsa_holiday', function() {
    add_meta_box('dsa_holiday_date_mb', __('Holiday Details', 'dancestudio-app'), 'dsa_render_holiday_date_mb', 'dsa_holiday', 'normal', 'high');
});

function dsa_render_holiday_date_mb( $post ) {
    wp_nonce_field( 'dsa_save_holiday_meta_action', 'dsa_holiday_nonce' );
    $date = get_post_meta( $post->ID, '_dsa_holiday_date', true );
    echo '<p><label for="dsa_holiday_date">' . esc_html__('Date of Holiday:', 'dancestudio-app') . '</label>';
    echo '<input type="date" id="dsa_holiday_date" name="dsa_holiday_date" value="' . esc_attr($date) . '" required /></p>';
}

add_action( 'save_post_dsa_holiday', function( $post_id ) {
    if ( !isset($_POST['dsa_holiday_nonce']) || !wp_verify_nonce($_POST['dsa_holiday_nonce'], 'dsa_save_holiday_meta_action') ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( !current_user_can('edit_post', $post_id) ) return;
    if ( isset($_POST['dsa_holiday_date']) ) {
        update_post_meta($post_id, '_dsa_holiday_date', sanitize_text_field($_POST['dsa_holiday_date']));
    }
});