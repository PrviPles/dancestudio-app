<?php
/**
 * DanceStudio App Public Form Handlers
 */
if(!defined('WPINC')){die;}

// --- Handler for the front-end student profile update form ---
add_action( 'admin_post_dsa_update_profile', 'dsa_handle_profile_update_submission' );
function dsa_handle_profile_update_submission() {
    // ... (This existing function is unchanged)
}


// --- NEW: Handler for the custom login form ---
add_action( 'admin_post_nopriv_dsa_login', 'dsa_handle_login_form_submission' );
function dsa_handle_login_form_submission() {
    if ( ! isset( $_POST['dsa_login_nonce'] ) || ! wp_verify_nonce( $_POST['dsa_login_nonce'], 'dsa_login_action' ) ) {
        wp_die('Security check failed!');
    }

    $creds = [
        'user_login'    => sanitize_user( $_POST['log'] ),
        'user_password' => $_POST['pwd'],
        'remember'      => isset( $_POST['rememberme'] ),
    ];
    
    $user = wp_signon( $creds, is_ssl() );

    if ( is_wp_error( $user ) ) {
        wp_safe_redirect( add_query_arg('login', 'failed', $_POST['_wp_http_referer']) );
        exit;
    }

    // Redirect to the student dashboard on successful login
    wp_safe_redirect( home_url('/student-dashboard/') ); // Adjust to your student dashboard page URL
    exit;
}


// --- NEW: Handler for the custom registration form ---
add_action( 'admin_post_nopriv_dsa_register', 'dsa_handle_registration_form_submission' );
function dsa_handle_registration_form_submission() {
    if ( ! isset( $_POST['dsa_register_nonce'] ) || ! wp_verify_nonce( $_POST['dsa_register_nonce'], 'dsa_register_action' ) ) {
        wp_die('Security check failed!');
    }

    $redirect_url = $_POST['_wp_http_referer'];
    
    // --- Validation ---
    $required_fields = ['user_login', 'user_email', 'first_name', 'last_name', 'pass1', 'pass2'];
    foreach ( $required_fields as $field ) {
        if ( empty($_POST[$field]) ) { wp_safe_redirect( add_query_arg('reg_error', 'required', $redirect_url) ); exit; }
    }
    if ( ! is_email($_POST['user_email']) ) { wp_safe_redirect( add_query_arg('reg_error', 'email_invalid', $redirect_url) ); exit; }
    if ( username_exists(sanitize_user($_POST['user_login'])) ) { wp_safe_redirect( add_query_arg('reg_error', 'username_exists', $redirect_url) ); exit; }
    if ( email_exists($_POST['user_email']) ) { wp_safe_redirect( add_query_arg('reg_error', 'email_exists', $redirect_url) ); exit; }
    if ( $_POST['pass1'] !== $_POST['pass2'] ) { wp_safe_redirect( add_query_arg('reg_error', 'password_mismatch', $redirect_url) ); exit; }

    // --- Create User ---
    $user_data = [
        'user_login' => sanitize_user($_POST['user_login']),
        'user_email' => sanitize_email($_POST['user_email']),
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name'  => sanitize_text_field($_POST['last_name']),
        'user_pass'  => $_POST['pass1'],
        'role'       => 'student'
    ];
    
    $user_id = wp_insert_user($user_data);

    if ( is_wp_error($user_id) ) {
        wp_safe_redirect( add_query_arg('reg_error', 'unknown', $redirect_url) );
        exit;
    }
    
    // Save custom meta fields
    if ( ! empty($_POST['phone']) ) { update_user_meta($user_id, '_dsa_user_phone', sanitize_text_field($_POST['phone'])); }
    if ( ! empty($_POST['birth_date']) ) { update_user_meta($user_id, '_dsa_user_birth_date', sanitize_text_field($_POST['birth_date'])); }
    
    // Redirect to the login page with a success message
    wp_safe_redirect( add_query_arg('registered', 'true', home_url('/login/')) ); // Adjust to your login page URL
    exit;
}