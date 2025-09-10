<?php
/**
 * General Helper Functions
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

if(!function_exists('dsa_get_partner_id')){
    function dsa_get_partner_id($user_id){
        $partner_id=get_user_meta($user_id,'dsa_partner_user_id',true);
        return $partner_id?absint($partner_id):false;
    }
}

if(!function_exists('dsa_calculate_age')){
    function dsa_calculate_age($birth_date){
        if(!$birth_date)return null;
        try{
            $birthDate=new DateTime($birth_date);
            $today=new DateTime('today');
            return $birthDate->diff($today)->y;
        }catch(Exception $e){
            return null;
        }
    }
}

if(!function_exists('dsa_get_lesson_end_time')){
    function dsa_get_lesson_end_time($date_string,$time_string,$duration_minutes=45){
        if(!$date_string||!$time_string)return false;
        try{
            $start_datetime=new DateTime($date_string.' '.$time_string);
            $start_datetime->add(new DateInterval('PT'.(int)$duration_minutes.'M'));
            return $start_datetime->format('H:i');
        }catch(Exception $e){
            return false;
        }
    }
}

if ( ! function_exists('dsa_render_sortable_table_header') ) {
    function dsa_render_sortable_table_header( $label, $orderby_key, $current_orderby, $current_order ) {
        $order_for_link = ($current_orderby === $orderby_key && strtolower($current_order) === 'asc') ? 'desc' : 'asc';
        $css_class = 'manage-column';
        if ($current_orderby === $orderby_key) {
            $css_class .= ' sorted ' . strtolower($current_order);
        } else {
            $css_class .= ' sortable desc';
        }
        $link = add_query_arg(['orderby' => $orderby_key, 'order' => $order_for_link]);
        echo '<th scope="col" class="' . esc_attr($css_class) . '"><a href="' . esc_url($link) . '"><span>' . esc_html($label) . '</span><span class="sorting-indicator"></span></a></th>';
    }
}

function dsa_generate_hub3a_barcode_string( $order, $studio_settings ) {
    $lines = [];
    $lines[] = "HRVHUB30";
    $lines[] = "HRK";
    $amount_in_cents = number_format($order->get_total(), 2, '', '');
    $lines[] = str_pad($amount_in_cents, 15, '0', STR_PAD_LEFT);
    $lines[] = strtoupper(substr($order->get_formatted_billing_full_name(), 0, 25));
    $lines[] = strtoupper(substr($order->get_billing_address_1(), 0, 25));
    $lines[] = strtoupper(substr($order->get_billing_city(), 0, 25));
    $lines[] = strtoupper(!empty($studio_settings['studio_name']) ? substr($studio_settings['studio_name'], 0, 25) : '');
    $lines[] = strtoupper(!empty($studio_settings['street_address']) ? substr($studio_settings['street_address'], 0, 25) : '');
    $lines[] = strtoupper(!empty($studio_settings['zip_code']) && !empty($studio_settings['city']) ? substr($studio_settings['zip_code'] . ' ' . $studio_settings['city'], 0, 25) : '');
    $lines[] = !empty($studio_settings['iban']) ? str_replace(' ', '', $studio_settings['iban']) : '';
    $lines[] = "HR99";
    $lines[] = "UPLATA-" . $order->get_order_number();
    $lines[] = "UPLATA ZA PLESNI TECAJ";
    return implode("\n", $lines);
}

function dsa_get_couple_package_progress( $user1_id, $user2_id ) {
    if ( ! class_exists('WooCommerce') ) return 'N/A';
    $orders_query = new WC_Order_Query(['limit' => 1, 'customer' => [$user1_id, $user2_id], 'status' => ['processing', 'completed'], 'orderby' => 'date', 'order' => 'DESC']);
    $orders = $orders_query->get_orders();
    if ( empty($orders) ) return 'No Package';
    $latest_order = reset($orders);
    $order_id = $latest_order->get_id();
    $lessons_in_package = 0;
    foreach ( $latest_order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        if ( has_term( 'lesson-packages', 'product_cat', $product_id ) ) {
            $lessons_in_package = (int) get_post_meta($product_id, '_dsa_lessons_in_package', true);
            break;
        }
    }
    if ( $lessons_in_package === 0 ) return 'N/A';
    $lessons_used_query = new WP_Query(['post_type' => 'dsa_private_lesson', 'posts_per_page' => -1, 'meta_key' => '_dsa_lesson_order_id', 'meta_value' => $order_id, 'fields' => 'ids']);
    $lessons_used_count = $lessons_used_query->found_posts;
    $percentage = round( ($lessons_used_count / $lessons_in_package) * 100 );
    return sprintf( '%d / %d (%d%%)', $lessons_used_count, $lessons_in_package, $percentage );
}

add_filter('set-screen-option', function ($status, $option, $value) {
    if ('students_per_page' === $option) return $value;
    return $status;
}, 10, 3);

add_action('admin_head', function() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'dancestudio-app_page_dsa-all-students') { // The hook for our page
        add_screen_option('per_page', [
            'label'   => __('Students per page', 'dancestudio-app'),
            'default' => 20,
            'option'  => 'students_per_page',
        ]);
    }
});