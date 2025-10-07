<?php
/**
 * Adds custom dashboard widgets for the plugin.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Registers all custom dashboard widgets and controls their visibility based on user role.
 */
add_action( 'wp_dashboard_setup', 'dsa_register_dashboard_widgets' );
function dsa_register_dashboard_widgets() {
    $user = wp_get_current_user();
    $allowed_roles = ['teacher', 'studio_manager', 'administrator'];

    if ( array_intersect( $allowed_roles, $user->roles ) ) {
        wp_add_dashboard_widget(
            'dsa_upcoming_birthdays_widget',
            __( 'Upcoming Birthdays', 'dancestudio-app' ),
            'dsa_render_upcoming_birthdays_widget'
        );
    }
}

/**
 * Renders the content for the 'Upcoming Birthdays' dashboard widget.
 * OPTIMIZED: Now uses a transient to cache results for 12 hours.
 */
function dsa_render_upcoming_birthdays_widget() {
    // --- PERFORMANCE OPTIMIZATION START ---
    // 1. Try to get the birthday list from the cache first.
    $cached_html = get_transient( 'dsa_upcoming_birthdays_widget' );

    if ( false !== $cached_html ) {
        // If the cache exists, print it and we are done!
        echo $cached_html;
        return;
    }
    // --- PERFORMANCE OPTIMIZATION END ---

    // If cache is empty, we run the expensive query.
    ob_start(); // Start output buffering to capture the HTML we generate.

    $days_to_check = 30;
    $today = new DateTime('today');
    
    $students_with_birthdays = get_users([
        'role__in'   => ['student', 'subscriber'],
        'meta_key'   => '_dsa_user_birth_date',
        'meta_value' => '',
        'meta_compare' => '!=',
    ]);
    
    $upcoming_birthdays = [];
    
    foreach ( $students_with_birthdays as $student ) {
        $birthday_str = get_user_meta( $student->ID, '_dsa_user_birth_date', true );
        
        if ( ! $birthday_str ) {
            continue;
        }

        try {
            $birthday_date = new DateTime( $birthday_str );
            $birthday_this_year = new DateTime( date('Y') . '-' . $birthday_date->format('m-d') );

            $interval = $today->diff( $birthday_this_year );

            if ( $birthday_this_year >= $today && $interval->days <= $days_to_check ) {
                $upcoming_birthdays[] = [
                    'name' => $student->display_name,
                    'ID' => $student->ID,
                    'date' => $birthday_this_year
                ];
            }
        } catch (Exception $e) {
            continue;
        }
    }

    usort($upcoming_birthdays, function($a, $b) {
        return $a['date'] <=> $b['date'];
    });

    if ( empty( $upcoming_birthdays ) ) {
        echo '<p>' . esc_html__( 'No upcoming birthdays in the next 30 days.', 'dancestudio-app' ) . '</p>';
    } else {
        echo '<ul style="margin-top: 0;">';
        foreach ( $upcoming_birthdays as $student_bday ) {
            $days_away = $today->diff($student_bday['date'])->days;
            $days_text = $days_away === 0 ? __('(Today!)', 'dancestudio-app') : sprintf( _n( '(%d day away)', '(%d days away)', $days_away, 'dancestudio-app' ), $days_away );

            echo '<li style="margin-bottom: 8px;">';
            echo '<a href="' . esc_url( get_edit_user_link( $student_bday['ID'] ) ) . '">' . esc_html( $student_bday['name'] ) . '</a>';
            echo '<br><small>' . esc_html( date_i18n( get_option('date_format'), $student_bday['date']->getTimestamp() ) ) . ' ' . esc_html($days_text) . '</small>';
            echo '</li>';
        }
        echo '</ul>';
    }

    // --- PERFORMANCE OPTIMIZATION START ---
    // 2. Get the generated HTML from the buffer.
    $widget_html = ob_get_clean();

    // 3. Save the HTML into our transient, with a 12-hour expiration.
    set_transient( 'dsa_upcoming_birthdays_widget', $widget_html, 12 * HOUR_IN_SECONDS );

    // 4. Finally, echo the HTML to the screen for this first-time load.
    echo $widget_html;
    // --- PERFORMANCE OPTIMIZATION END ---
}