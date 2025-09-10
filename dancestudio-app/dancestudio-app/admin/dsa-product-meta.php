<?php
/**
 * Adds custom meta fields to WooCommerce products for lesson tracking.
 *
 * @package DanceStudioApp
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add the custom field to the "General" product data tab.
 */
if ( ! function_exists( 'dsa_add_lessons_in_package_field' ) ) {
    function dsa_add_lessons_in_package_field() {
        echo '<div class="options_group">';

        woocommerce_wp_text_input(
            array(
                'id'                => '_dsa_lessons_in_package',
                'label'             => __( 'Number of Lessons', 'dancestudio-app' ),
                'placeholder'       => __( 'e.g., 10', 'dancestudio-app' ),
                'desc_tip'          => true,
                'description'       => __( 'Enter the number of lessons this package contains. This is used for tracking attendance.', 'dancestudio-app' ),
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => '1',
                    'min'  => '0',
                ),
                // Pull in current saved value so Woo outputs it.
                'value'             => get_post_meta( get_the_ID(), '_dsa_lessons_in_package', true ),
            )
        );

        echo '</div>';
    }
    add_action( 'woocommerce_product_options_general_product_data', 'dsa_add_lessons_in_package_field' );
}

/**
 * Save the custom field value when the product is saved.
 */
if ( ! function_exists( 'dsa_save_lessons_in_package_field' ) ) {
    function dsa_save_lessons_in_package_field( $post_id ) {
        // Capability check
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['_dsa_lessons_in_package'] ) ) {
            $lessons_count = absint( wp_unslash( $_POST['_dsa_lessons_in_package'] ) );
            update_post_meta( $post_id, '_dsa_lessons_in_package', $lessons_count );
        } else {
            delete_post_meta( $post_id, '_dsa_lessons_in_package' );
        }
    }
    add_action( 'woocommerce_process_product_meta', 'dsa_save_lessons_in_package_field' );
}
