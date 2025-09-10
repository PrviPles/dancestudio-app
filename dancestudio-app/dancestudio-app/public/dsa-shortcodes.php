<?php
/**
 * Public facing shortcodes for the DanceStudio App plugin.
 */
if ( ! defined( 'WPINC' ) ) die;

// --- Shortcode for the Main Student Dashboard ---
add_shortcode( 'dancestudio_dashboard', 'dsa_render_unified_dashboard_shortcode' );
function dsa_render_unified_dashboard_shortcode() {
    // ... (This existing function is unchanged)
}

// --- Shortcode for the Login Form ---
add_shortcode( 'dancestudio_login_form', 'dsa_render_login_form_shortcode' );
function dsa_render_login_form_shortcode() {
    // ... (This existing function is unchanged)
}

// --- Shortcode for the Registration Form ---
add_shortcode( 'dancestudio_registration_form', 'dsa_render_registration_form_shortcode' );
function dsa_render_registration_form_shortcode() {
    // ... (This existing function is unchanged)
}


// --- NEW: INDIVIDUAL DASHBOARD SHORTCODES ---

/**
 * [dsa_student_profile]
 * Displays the logged-in student's profile information.
 */
add_shortcode( 'dsa_student_profile', 'dsa_render_student_profile_shortcode' );
function dsa_render_student_profile_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>' . __( 'You must be logged in to view your profile.', 'dancestudio-app' ) . '</p>';
    }
    ob_start();
    // We can re-use the existing template file for this
    $template_path = DSA_PLUGIN_DIR . 'public/views/dashboard-profile.php';
    if ( file_exists($template_path) ) {
        include $template_path;
    }
    return ob_get_clean();
}


/**
 * [dsa_private_lessons_list]
 * Displays a table of the logged-in student's private lessons.
 */
add_shortcode( 'dsa_private_lessons_list', 'dsa_render_private_lessons_list_shortcode' );
function dsa_render_private_lessons_list_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>' . __( 'You must be logged in to view your lessons.', 'dancestudio-app' ) . '</p>';
    }
    ob_start();
    // We can re-use the existing template file for this
    $template_path = DSA_PLUGIN_DIR . 'public/views/dashboard-lessons.php';
    if ( file_exists($template_path) ) {
        include $template_path;
    }
    return ob_get_clean();
}


/**
 * [dsa_student_profile_edit]
 * Displays the form for a student to edit their own profile.
 */
add_shortcode( 'dsa_student_profile_edit', 'dsa_render_student_profile_edit_shortcode' );
function dsa_render_student_profile_edit_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>' . __( 'You must be logged in to edit your profile.', 'dancestudio-app' ) . '</p>';
    }
    ob_start();
    // We can re-use the existing template file for this
    $template_path = DSA_PLUGIN_DIR . 'public/views/dashboard-settings.php';
    if ( file_exists($template_path) ) {
        include $template_path;
    }
    return ob_get_clean();
}