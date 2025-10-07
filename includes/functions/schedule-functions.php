<?php
/**
 * All functions related to schedule processing and class generation.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

/**
 * HANDLER: Runs when the "Generate Classes" button is clicked.
 */
add_action( 'admin_post_dsa_generate_group_classes', 'dsa_handle_generate_classes_submission' );
function dsa_handle_generate_classes_submission() {
    // --- CORRECTED: Simplified the security check ---
    if ( !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'dsa_generate_classes_action') ) {
        wp_die('Security check failed!');
    }
    
    $group_id = isset($_GET['group_id']) ? absint($_GET['group_id']) : 0;
    if ( !$group_id ) {
        wp_die('Error: Missing Group ID.');
    }
    if ( ! current_user_can('edit_post', $group_id) ) {
        wp_die('You do not have permission to perform this action.');
    }

    // Call the generation engine
    dsa_generate_classes_for_group($group_id);

    // Redirect back to the edit screen with a success message
    $redirect_url = add_query_arg([
        'post' => $group_id,
        'action' => 'edit',
        'message' => '101' // Custom message ID for our success notice
    ], admin_url('post.php'));

    wp_safe_redirect($redirect_url);
    exit;
}

/**
 * Displays a custom success message after class generation.
 */
add_filter('post_updated_messages', 'dsa_group_updated_messages');
function dsa_group_updated_messages( $messages ) {
    $messages['dsa_group'][101] = __('Class sessions have been successfully generated based on the saved schedule.', 'dancestudio-app');
    return $messages;
}


/**
 * The core engine that generates dsa_group_class posts based on a schedule.
 */
function dsa_generate_classes_for_group( $group_id ) {
    // (The generation engine code is unchanged and remains here)
    $schedule_days = get_post_meta( $group_id, '_dsa_schedule_days', true );
    $frequency = get_post_meta( $group_id, '_dsa_schedule_frequency', true );
    $start_date_str = get_post_meta( $group_id, '_dsa_schedule_start_date', true );
    $end_date_str = get_post_meta( $group_id, '_dsa_schedule_end_date', true );
    $total_classes = absint(get_post_meta( $group_id, '_dsa_total_classes', true ));

    if ( empty($schedule_days) || empty($start_date_str) || empty($end_date_str) ) { return; }

    $future_classes_query = new WP_Query(['post_type' => 'dsa_group_class', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => ['relation' => 'AND', ['key' => '_dsa_class_group_id', 'value' => $group_id], ['key' => '_dsa_class_date', 'value' => date('Y-m-d'), 'compare' => '>=', 'type' => 'DATE']]]);
    foreach ( $future_classes_query->get_posts() as $post_to_delete ) { wp_delete_post( $post_to_delete, true ); }

    $holidays = [];
    $holidays_query = new WP_Query(['post_type' => 'dsa_holiday', 'posts_per_page' => -1, 'fields' => 'ids']);
    foreach($holidays_query->get_posts() as $holiday_id) { $holidays[] = get_post_meta($holiday_id, '_dsa_holiday_date', true); }
    
    try { $start_date = new DateTime($start_date_str); $end_date = new DateTime($end_date_str); } catch (Exception $e) { return; }
    
    $interval = new DateInterval('P1D');
    $date_range = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));
    $class_counter = 1; $conflicts = []; $week_of_start = (int)$start_date->format("W");
    $group_title = get_the_title($group_id);

    foreach ( $date_range as $date ) {
        $day_of_week = strtolower($date->format('l'));
        $is_valid_day = in_array($day_of_week, $schedule_days);
        $is_valid_freq = true;

        switch ($frequency) {
            case 'biweekly': $current_week = (int)$date->format("W"); if ( ($current_week - $week_of_start) % 2 !== 0 ) { $is_valid_freq = false; } break;
            case 'monthly': if ($date->format('d') !== $start_date->format('d')) { $is_valid_freq = false; } break;
        }

        if ( $is_valid_day && $is_valid_freq ) {
            $existing = get_posts(['post_type' => 'dsa_group_class', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_query' => [['key' => '_dsa_class_group_id', 'value' => $group_id], ['key' => '_dsa_class_date', 'value' => $date->format('Y-m-d')]]]);
            if (!empty($existing)) continue;

            $class_title = $group_title;
            if ( $total_classes > 0 ) { $class_title .= " {$class_counter}/{$total_classes}"; }

            $new_class_id = wp_insert_post(['post_type' => 'dsa_group_class', 'post_title' => $class_title, 'post_status' => 'publish']);
            if ($new_class_id) {
                update_post_meta($new_class_id, '_dsa_class_group_id', $group_id);
                update_post_meta($new_class_id, '_dsa_class_date', $date->format('Y-m-d'));
                update_post_meta($new_class_id, '_dsa_class_start_time', get_post_meta($group_id, '_dsa_schedule_start_time', true));
                update_post_meta($new_class_id, '_dsa_class_end_time', get_post_meta($group_id, '_dsa_schedule_end_time', true));
                update_post_meta($new_class_id, '_dsa_primary_teacher_id', get_post_meta($group_id, '_dsa_primary_teacher_id', true));
                update_post_meta($new_class_id, '_dsa_substitute_teacher_id', get_post_meta($group_id, '_dsa_substitute_teacher_id', true));
                $class_counter++;
                if (in_array($date->format('Y-m-d'), $holidays)) { $conflicts[] = $date->format(get_option('date_format')); }
            }
        }
    }

    if (!empty($conflicts)) {
        set_transient('dsa_schedule_notices', 'The following generated classes fall on a holiday: ' . implode(', ', $conflicts), 60);
    }
}

/**
 * Display transient admin notices
 */
add_action('admin_notices', 'dsa_display_transient_admin_notices');
function dsa_display_transient_admin_notices() {
    if ( $message = get_transient( 'dsa_schedule_notices' ) ) {
        echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html($message) . '</p></div>';
        delete_transient( 'dsa_schedule_notices' );
    }
}