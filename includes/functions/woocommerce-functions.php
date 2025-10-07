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
 * Handles the generation of a PDF invoice using TCPDF.
 */
add_action( 'admin_post_dsa_generate_invoice', 'dsa_handle_generate_invoice' );
function dsa_handle_generate_invoice() {
    if ( ! isset( $_GET['dsa_invoice_nonce'] ) || ! wp_verify_nonce( sanitize_text_field($_GET['dsa_invoice_nonce']), 'dsa_generate_invoice_nonce' ) ) { wp_die( 'Security check failed!' ); }
    if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'You do not have permission to generate invoices.' ); }
    
    $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
    if ( ! $order_id || ! ($order = wc_get_order($order_id)) ) { wp_die( 'Invalid Order ID.' ); }
    
    if ( ! class_exists( 'TCPDF' ) ) {
        wp_die('TCPDF Library not available. Please run "composer update" in your plugin directory.');
    }

    $studio_settings = get_option('dsa_studio_settings', []);
    $logo_id = $studio_settings['studio_logo_id'] ?? 0;
    $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
    if ($logo_url) {
        $logo_url = add_query_arg('ver', time(), $logo_url);
    }

    $brand_color = $studio_settings['invoice_brand_color'] ?? '#3a87ad';
    $legal_info = $studio_settings['legal_info'] ?? '';
    $logo_position = $studio_settings['invoice_logo_position'] ?? 'center';
    $logo_dimensions = !empty($studio_settings['invoice_logo_dimensions']) ? $studio_settings['invoice_logo_dimensions'] : 150;

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($studio_settings['studio_name'] ?? 'DanceStudio App');
    $pdf->SetTitle('Račun ' . $order->get_order_number());
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(20, 15, 20);
    $pdf->AddPage();
    
    $font_path = DSA_PLUGIN_DIR . 'assets/fonts/Poppins-Regular.ttf';
    $poppins_font = '';
    if (file_exists($font_path)) {
        $poppins_font = TCPDF_FONTS::addTTFfont($font_path, 'TrueTypeUnicode', '', 32);
        $pdf->SetFont($poppins_font, '', 10);
    } else {
        $pdf->SetFont('dejavusans', '', 10);
    }
    
    $bill_to = '<strong>' . $order->get_formatted_billing_full_name() . '</strong><br>' . $order->get_billing_address_1() . '<br>' . $order->get_billing_city();
    $from_address = '<strong>' . ($studio_settings['studio_name'] ?? '') . '</strong><br>' . ($studio_settings['street_address'] ?? '') . '<br>' . ($studio_settings['zip_code'] ?? '') . ' ' . ($studio_settings['city'] ?? '') . '<br>' . ($studio_settings['email'] ?? '');
    
    $items_html = '';
    foreach($order->get_items() as $item) {
        $items_html .= '<tr><td>' . esc_html($item->get_name()) . '</td><td style="text-align:right;">' . wp_kses_post($order->get_formatted_line_subtotal($item)) . '</td></tr>';
    }
    
    $invoice_title = 'RAČUN';

    $html = '
    <style>
        body { font-family: ' . ($poppins_font ?: 'sans-serif') . '; color: #333; }
        .invoice-box { width: 100%; }
        .header { border-bottom: 1px solid #999; padding-bottom: 10px; margin-bottom: 25px; }
        .logo-container { text-align: ' . esc_attr($logo_position) . '; margin-bottom: 10px; }
        .logo { max-height: ' . esc_attr($logo_dimensions) . 'px; width: auto; }
        .invoice-title { text-align: center; font-size: 22px; margin: 0; color: ' . esc_attr($brand_color) . '; font-weight: bold; }
        .invoice-details { text-align: center; margin-bottom: 30px; line-height: 1.5; font-size: 10px; }
        .address-table { margin-bottom: 30px; }
        .items-table { border-collapse: collapse; width: 100%; }
        .items-table tr.heading td { background-color: #333; color: #fff; font-weight: bold; padding: 8px; }
        .items-table tr.item td { padding: 10px 8px; border-bottom: 1px solid #ddd; }
        .items-table tr.total td { padding-top: 10px; border-top: 2px solid #333; font-weight: bold; text-align:right; }
        .footer { text-align: center; margin-top: 30px; font-size: 9px; color: #777; }
        .barcode-section { margin-top: 30px; margin-bottom: 20px; }
    </style>
    <div class="invoice-box">
        <div class="header">
            ' . ($logo_url ? '<div class="logo-container"><img src="' . esc_url($logo_url) . '" class="logo"></div>' : '') . '
            <h1 class="invoice-title">' . $invoice_title . '</h1>
        </div>
        <div class="invoice-details">
            <b>' . __('Račun br', 'dancestudio-app') . ':</b> ' . esc_html($order->get_order_number()) . '<br>
            <b>' . __('Datum izdavanja', 'dancestudio-app') . ':</b> ' . esc_html($order->get_date_created()->date_i18n('d.m.Y.')) . '<br>
            <b>' . __('Datum dospijeća', 'dancestudio-app') . ':</b> ' . esc_html($order->get_date_created()->date_i18n('d.m.Y.')) . '
        </div>
        <table class="address-table">
            <tr><td width="50%"><strong>' . __('Izdao', 'dancestudio-app') . ':</strong><br>' . $from_address . '</td><td width="50%" style="text-align:right;"><strong>' . __('Za', 'dancestudio-app') . ':</strong><br>' . $bill_to . '</td></tr>
        </table>
        <table class="items-table">
            <tr class="heading"><td width="70%">' . __('Stavka', 'dancestudio-app') . '</td><td width="30%" style="text-align:right;">' . __('Cijena', 'dancestudio-app') . '</td></tr>
            ' . $items_html . '
            <tr class="total"><td></td><td>' . __('Ukupno', 'dancestudio-app') . ': ' . wp_kses_post($order->get_formatted_order_total()) . '</td></tr>
        </table>
    </div>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $barcode_string = function_exists('dsa_generate_hub3a_barcode_string') ? dsa_generate_hub3a_barcode_string($order, $studio_settings) : '';
    if ( ! empty($barcode_string) ) {
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetY(-65);
        $pdf->writeHTML('<div class="footer">' . nl2br(esc_html($legal_info)) . '</div><div class="barcode-section" style="text-align:center;"><h3>' . esc_html__('Scan for Payment (HUB 3A)', 'dancestudio-app') . '</h3><p style="font-size: 9px;">Skenirajte barkod mobilnim bankarstvom.</p></div>', true, false, false, false, '');
        $style = ['position' => '', 'align' => 'C', 'stretch' => false, 'fitwidth' => true, 'cellfitalign' => '', 'border' => false, 'hpadding' => 'auto', 'vpadding' => 'auto', 'fgcolor' => [0,0,0], 'bgcolor' => false, 'text' => false, 'font' => 'helvetica', 'fontsize' => 8, 'stretchtext' => 4];
        $pdf->write2DBarcode($barcode_string, 'PDF417', '', '', 150, 35, $style, 'N');
    } else {
         $pdf->SetY(-65);
         $pdf->writeHTML('<div class="footer">' . nl2br(esc_html($legal_info)) . '</div>', true, false, false, false, '');
    }

    $pdf->Output('Racun-' . $order->get_order_number() . '.pdf', 'I');
    exit;
}