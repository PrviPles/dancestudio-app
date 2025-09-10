<?php
/**
 * WooCommerce Integration Functions
 * Handles order tracker, invoicing, subscriptions, etc.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

// --- Add a dropdown on the "Edit Product" page to link it to a Dance Group ---

add_action( 'woocommerce_product_options_general_product_data', 'dsa_add_group_link_to_products' );
function dsa_add_group_link_to_products() {
    echo '<div class="options_group">';

    $groups_query = new WP_Query([
        'post_type' => 'dsa_group',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);

    $options = [ '0' => __( 'Not a membership product', 'dancestudio-app' ) ];
    if ( $groups_query->have_posts() ) {
        while ( $groups_query->have_posts() ) {
            $groups_query->the_post();
            $options[get_the_ID()] = get_the_title();
        }
        wp_reset_postdata();
    }

    woocommerce_wp_select([
        'id'          => '_dsa_linked_group_id',
        'label'       => __( 'Linked Dance Group', 'dancestudio-app' ),
        'description' => __( 'If this product is a membership/subscription, select the group it grants access to.', 'dancestudio-app' ),
        'desc_tip'    => true,
        'options'     => $options,
    ]);

    echo '</div>';
}


// --- Save the linked Dance Group when the product is updated ---

add_action( 'woocommerce_process_product_meta_simple', 'dsa_save_group_link_field' );
add_action( 'woocommerce_process_product_meta_variable', 'dsa_save_group_link_field' );
add_action( 'woocommerce_process_product_meta_grouped', 'dsa_save_group_link_field' );
add_action( 'woocommerce_process_product_meta_external', 'dsa_save_group_link_field' );
add_action( 'woocommerce_process_product_meta_subscription', 'dsa_save_group_link_field' );
add_action( 'woocommerce_process_product_meta_variable-subscription', 'dsa_save_group_link_field' );
function dsa_save_group_link_field( $post_id ) {
    if ( isset( $_POST['_dsa_linked_group_id'] ) ) {
        update_post_meta( $post_id, '_dsa_linked_group_id', absint( $_POST['_dsa_linked_group_id'] ) );
    }
}


// --- Automatically enroll the student when their subscription order is completed ---

add_action( 'woocommerce_order_status_completed', 'dsa_handle_completed_subscription_order', 10, 1 );
function dsa_handle_completed_subscription_order( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    
    $student_id = $order->get_customer_id();
    if ( ! $student_id ) {
        return;
    }

    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        
        $linked_group_id = get_post_meta( $product_id, '_dsa_linked_group_id', true );

        if ( ! empty( $linked_group_id ) ) {
            dsa_enroll_student_in_group( $student_id, $linked_group_id );
        }
    }
}


// --- Order Tracker and Invoicing Functions ---

function dsa_render_order_tracker_table($args=[]){
    if(!class_exists('WooCommerce')){echo '<p><em>'.esc_html__('WooCommerce is not active. This feature is unavailable.','dancestudio-app').'</em></p>';return;}
    $query_args=['limit'=>-1,'status'=>['processing','completed'],'orderby'=>'date','order'=>'DESC',];if(!empty($args['customer_id'])){$query_args['customer']=$args['customer_id'];}$orders_query=new WC_Order_Query($query_args);$orders=$orders_query->get_orders();$has_lesson_orders=false;?>
    <table class="wp-list-table widefat fixed striped posts"><thead><tr><th style="width:80px;"><?php esc_html_e('Order','dancestudio-app');?></th><th><?php esc_html_e('Customer','dancestudio-app');?></th><th><?php esc_html_e('Package Purchased','dancestudio-app');?></th><th style="width:150px;"><?php esc_html_e('Lessons Used','dancestudio-app');?></th><th style="width:200px;"><?php esc_html_e('Progress','dancestudio-app');?></th><th><?php esc_html_e('Order Date','dancestudio-app');?></th></tr></thead><tbody id="the-list">
    <?php if(!empty($orders)){foreach($orders as $order){$order_id=$order->get_id();$items=$order->get_items();foreach($items as $item){$product_id=$item->get_product_id();if(has_term('lesson-packages','product_cat',$product_id)){$has_lesson_orders=true;$lessons_in_package=(int)get_post_meta($product_id,'_dsa_lessons_in_package',true);$lessons_used_query=new WP_Query(['post_type'=>'dsa_private_lesson','posts_per_page'=>-1,'meta_key'=>'_dsa_lesson_order_id','meta_value'=>$order_id]);$lessons_used_count=$lessons_used_query->found_posts;$progress_percent=($lessons_in_package>0)?($lessons_used_count/$lessons_in_package)*100:0;?>
    <tr><td><a href="<?php echo esc_url(get_edit_post_link($order_id));?>" target="_blank"><strong>#<?php echo esc_html($order_id);?></strong></a></td><td><?php echo esc_html($order->get_formatted_billing_full_name());?></td><td><?php echo esc_html($item->get_name());?></td><td><strong><?php echo esc_html($lessons_used_count);?> / <?php echo esc_html($lessons_in_package);?></strong></td><td><div style="background-color:#e0e0e0;border-radius:4px;overflow:hidden;"><div style="width:<?php echo esc_attr($progress_percent);?>%;background-color:#4caf50;padding:4px;color:white;text-align:center;font-size:12px;"><?php echo round($progress_percent);?>%</div></div></td><td><?php echo esc_html($order->get_date_created()->date_i18n('j. F Y.'));?></td></tr>
    <?php }}}}if(!$has_lesson_orders){echo '<tr class="no-items"><td class="colspanchange" colspan="6">'.esc_html__('No paid orders found for products in the "Lesson Packages" category for this user.','dancestudio-app').'</td></tr>';}?>
    </tbody></table><?php 
}

add_filter( 'manage_edit-shop_order_columns', 'dsa_add_invoice_actions_column_header' );
function dsa_add_invoice_actions_column_header( $columns ) {
    $new_columns = [];
    foreach ( $columns as $key => $column ) {
        $new_columns[$key] = $column;
        if ( $key === 'order_status' ) {
            $new_columns['dsa_invoice_actions'] = __( 'Invoice', 'dancestudio-app' );
        }
    }
    return $new_columns;
}

add_action( 'manage_shop_order_posts_custom_column', 'dsa_render_invoice_actions_column_content', 10, 2 );
function dsa_render_invoice_actions_column_content( $column, $post_id ) {
    if ( 'dsa_invoice_actions' === $column ) {
        $generation_url = wp_nonce_url(
            admin_url( 'admin-post.php?action=dsa_generate_invoice&order_id=' . $post_id ),
            'dsa_generate_invoice_nonce',
            'dsa_invoice_nonce'
        );
        echo '<a href="' . esc_url($generation_url) . '" class="button button-primary" target="_blank">' . esc_html__( 'Generate Invoice', 'dancestudio-app' ) . '</a>';
    }
}

/**
 * Handles the generation of a PDF invoice using Mpdf.
 * NOW INCLUDES A PDF417 BARCODE.
 */
add_action( 'admin_post_dsa_generate_invoice', 'dsa_handle_generate_invoice' );
function dsa_handle_generate_invoice() {
    if ( ! isset( $_GET['dsa_invoice_nonce'] ) || ! wp_verify_nonce( sanitize_text_field($_GET['dsa_invoice_nonce']), 'dsa_generate_invoice_nonce' ) ) { wp_die( 'Security check failed!' ); }
    if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'You do not have permission to generate invoices.' ); }
    
    $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
    if ( ! $order_id || ! ($order = wc_get_order($order_id)) ) { wp_die( 'Invalid Order ID.' ); }
    
    $studio_settings = get_option('dsa_studio_settings', []);

    // Generate the barcode data string
    $barcode_string = function_exists('dsa_generate_hub3a_barcode_string') ? dsa_generate_hub3a_barcode_string($order, $studio_settings) : '';
    
    // Build the HTML for the PDF
    $html = '
    <html>
    <head>
        <style>
            body { font-family: sans-serif; }
            .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); font-size: 16px; line-height: 24px; color: #555; }
            .invoice-box table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
            .invoice-box table td { padding: 5px; vertical-align: top; }
            .invoice-box table tr.top table td { padding-bottom: 20px; }
            .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
            .invoice-box table tr.item td{ border-bottom: 1px solid #eee; }
            .invoice-box table tr.total td{ border-top: 2px solid #eee; font-weight: bold; }
            .barcode-section { text-align: center; margin-top: 40px; page-break-inside: avoid; }
        </style>
    </head>
    <body>
        <div class="invoice-box">
            <h1>Invoice #' . esc_html($order->get_order_number()) . '</h1>
            <p>
                Created: ' . esc_html($order->get_date_created()->date('F j, Y')) . '<br>
                Due: ' . esc_html($order->get_date_created()->date('F j, Y')) . '
            </p>
            <p>
                <strong>Bill To:</strong><br>
                ' . esc_html($order->get_formatted_billing_full_name()) . '<br>
                ' . esc_html($order->get_billing_address_1()) . '<br>
                ' . esc_html($order->get_billing_city()) . '
            </p>
            <table>
                <tr class="heading"><td>Item</td><td style="text-align:right;">Price</td></tr>';
                foreach($order->get_items() as $item) {
                    $html .= '<tr class="item"><td>' . esc_html($item->get_name()) . '</td><td style="text-align:right;">' . wp_kses_post($order->get_formatted_line_subtotal($item)) . '</td></tr>';
                }
    $html .= '  <tr class="total"><td></td><td style="text-align:right;">Total: ' . wp_kses_post($order->get_formatted_order_total()) . '</td></tr>
            </table>';

    if ( ! empty($barcode_string) ) {
        $html .= '
            <div class="barcode-section">
                <h3>' . esc_html__('Scan for Payment (HUB 3A)', 'dancestudio-app') . '</h3>
                <p style="font-size: 12px;">Skenirajte barkod mobilnim bankarstvom.</p>
                <barcode code="' . esc_attr($barcode_string) . '" type="PDF417" size="1.0" error="M" disableborder="1" />
            </div>';
    }

    $html .= '</div></body></html>';

    try {
        if ( ! class_exists( '\Mpdf\Mpdf' ) ) {
            wp_die('PDF Library not available. Please run "composer install".');
        }
        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML($html);
        $mpdf->Output('Invoice-' . $order->get_order_number() . '.pdf', 'I');
        exit;
    } catch (\Mpdf\MpdfException $e) {
        wp_die('PDF Generation Error: ' . $e->getMessage());
    }
}