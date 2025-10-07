<?php
/**
 * AJAX Handler for subscription-related actions.
 * UPDATED: Handles tiered pricing for standard vs. retiree students.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

/**
 * Handles the AJAX request to send a payment link email.
 */
add_action( 'wp_ajax_dsa_send_payment_link', 'dsa_handle_send_payment_link_ajax' );
function dsa_handle_send_payment_link_ajax() {
    // 1. Security Checks
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'dsa_send_payment_link_nonce' ) ) {
        wp_send_json_error( ['message' => 'Security check failed.'] );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( ['message' => 'You do not have permission to perform this action.'] );
    }

    $enrollment_id = isset( $_POST['enrollment_id'] ) ? absint( $_POST['enrollment_id'] ) : 0;
    if ( ! $enrollment_id ) {
        wp_send_json_error( ['message' => 'Invalid enrollment record ID.'] );
    }

    // 2. Get necessary data
    $enrollment_record = get_post($enrollment_id);
    if (!$enrollment_record || 'dsa_enroll_record' !== $enrollment_record->post_type) {
        wp_send_json_error( ['message' => 'Could not find the specified enrollment record.'] );
    }
    
    $student_id = $enrollment_record->post_author;
    $group_id = $enrollment_record->post_parent;
    
    $student_data = get_userdata($student_id);
    $group_post = get_post($group_id);

    // --- NEW: Tiered Pricing Logic ---
    $is_retiree = get_user_meta($student_id, '_dsa_is_retired', true);
    $standard_product_id = get_post_meta($group_id, '_dsa_linked_product_id', true);
    $retiree_product_id = get_post_meta($group_id, '_dsa_linked_retiree_product_id', true);
    
    $product_id_to_send = 0;

    // If the student is a retiree AND a specific retiree product is linked, use it.
    if ( '1' === $is_retiree && ! empty($retiree_product_id) ) {
        $product_id_to_send = $retiree_product_id;
    } else {
        // Otherwise, fall back to the standard product.
        $product_id_to_send = $standard_product_id;
    }

    if ( empty($product_id_to_send) || ! get_post($product_id_to_send) ) {
        $error_msg = 'This dance group is not linked to a standard WooCommerce product. Please link a product on the Edit Group page.';
        if ('1' === $is_retiree) {
            $error_msg = 'Could not find a valid product link. Please ensure a Standard Product (and optionally a Retiree Product) is linked to this group.';
        }
        wp_send_json_error( ['message' => $error_msg] );
    }
    // --- END: Tiered Pricing Logic ---

    // 3. Prepare and send the email
    $to = $student_data->user_email;
    $subject = sprintf( 'Your Dance Subscription for %s is Due for Renewal', $group_post->post_title );
    $product_url = get_permalink($product_id_to_send);

    $body = "<p>Hello " . esc_html($student_data->first_name) . ",</p>";
    $body .= "<p>This is a friendly reminder that your 8-lesson subscription for the dance group '<strong>" . esc_html($group_post->post_title) . "</strong>' is due for renewal.</p>";
    $body .= "<p>To continue with your classes, please follow the link below to purchase your next block of 8 lessons:</p>";
    $body .= '<p><a href="' . esc_url($product_url) . '" style="display:inline-block;padding:10px 20px;background-color:#3498db;color:#ffffff;text-decoration:none;border-radius:5px;">Pay for Next Subscription</a></p>';
    $body .= "<p>Thank you!</p>";
    
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    
    $sent = wp_mail( $to, $subject, $body, $headers );

    if ($sent) {
        wp_send_json_success( ['message' => 'Payment link email sent successfully to ' . esc_html($student_data->display_name) . '.'] );
    } else {
        wp_send_json_error( ['message' => 'The email could not be sent. Please check your WordPress email settings.'] );
    }
}

/**
 * Hook into WooCommerce to reset the subscription cycle upon successful payment.
 * UPDATED: Now robustly finds the group regardless of which product (standard/retiree) was purchased.
 */
add_action( 'woocommerce_order_status_completed', 'dsa_reset_subscription_on_payment', 20, 1 );
function dsa_reset_subscription_on_payment( $order_id ) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    $student_id = $order->get_customer_id();
    if (!$student_id) return;

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        
        // Find which group is linked to the purchased product, checking both standard and retiree fields.
        $group_query = new WP_Query([
            'post_type' => 'dsa_group',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                [ 'key' => '_dsa_linked_product_id', 'value' => $product_id ],
                [ 'key' => '_dsa_linked_retiree_product_id', 'value' => $product_id ],
            ]
        ]);
        
        if ( $group_query->have_posts() ) {
            $linked_group_id = $group_query->posts[0];
            $enrollment_record_id = dsa_get_active_enrollment_record($student_id, $linked_group_id);

            if ($enrollment_record_id) {
                // Reset the student's subscription cycle for this group
                update_post_meta($enrollment_record_id, '_dsa_subscription_start_date', '');
                update_post_meta($enrollment_record_id, '_dsa_lessons_attended_count', 0);
                update_post_meta($enrollment_record_id, '_dsa_current_subscription_order_id', $order_id);
            }
        }
    }
}