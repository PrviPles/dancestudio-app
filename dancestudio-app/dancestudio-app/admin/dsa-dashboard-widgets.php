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
    // Only show this widget to these specific roles
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
 */
function dsa_render_upcoming_birthdays_widget() {
    $days_to_check = 30; // How many days in advance to check for birthdays
    $today = new DateTime('today');
    
    // Get all users who have a birthday set
    $students_with_birthdays = get_users([
        'role__in'   => ['student', 'subscriber'],
        'meta_key'   => '_dsa_user_birth_date',
        'meta_value' => '', // Ensures the key exists
        'meta_compare' => '!=',
    ]);
    
    $upcoming_birthdays = [];
    
    // Loop through each student to check their birthday
    foreach ( $students_with_birthdays as $student ) {
        $birthday_str = get_user_meta( $student->ID, '_dsa_user_birth_date', true );
        
        if ( ! $birthday_str ) {
            continue;
        }

        try {
            $birthday_date = new DateTime( $birthday_str );
            // Set the birthday's year to the current year for comparison
            $birthday_this_year = new DateTime( date('Y') . '-' . $birthday_date->format('m-d') );

            // Check if the birthday is within the next 30 days
            $interval = $today->diff( $birthday_this_year );

            if ( $birthday_this_year >= $today && $interval->days <= $days_to_check ) {
                $upcoming_birthdays[] = [
                    'name' => $student->display_name,
                    'ID' => $student->ID,
                    'date' => $birthday_this_year
                ];
            }
        } catch (Exception $e) {
            // Ignore invalid date formats
            continue;
        }
    }

    // Sort the birthdays chronologically
    usort($upcoming_birthdays, function($a, $b) {
        return $a['date'] <=> $b['date'];
    });

    // Display the list
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
}