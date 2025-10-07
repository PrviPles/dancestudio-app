<?php
/**
 * DanceStudio App Public Form Handlers
 */
if(!defined('WPINC')){die;}

// --- Handler for the front-end student profile update form ---
add_action( 'admin_post_dsa_update_profile', 'dsa_handle_profile_update_submission' );
function dsa_handle_profile_update_submission() {
    if ( ! is_user_logged_in() || ! isset($_POST['dsa_profile_nonce_field']) || ! wp_verify_nonce($_POST['dsa_profile_nonce_field'], 'dsa_update_profile_nonce') ) {
        wp_die('Security check failed!');
    }

    $user_id = get_current_user_id();
    $user_data = [];

    if ( isset($_POST['first_name']) ) $user_data['first_name'] = sanitize_text_field($_POST['first_name']);
    if ( isset($_POST['last_name']) ) $user_data['last_name'] = sanitize_text_field($_POST['last_name']);

    if ( !empty($_POST['user_email']) ) {
        $new_email = sanitize_email($_POST['user_email']);
        if ( $new_email !== wp_get_current_user()->user_email && email_exists($new_email) ) {
            wp_safe_redirect( add_query_arg('error', 'invalid_email', wp_get_referer()) );
            exit;
        }
        $user_data['user_email'] = $new_email;
    }

    if ( ! empty($user_data) ) {
        $user_data['ID'] = $user_id;
        wp_update_user($user_data);
    }

    if ( isset($_POST['phone_number']) ) update_user_meta($user_id, '_dsa_user_phone', sanitize_text_field($_POST['phone_number']));
    if ( isset($_POST['dsa_student_birthday']) ) update_user_meta($user_id, '_dsa_user_birth_date', sanitize_text_field($_POST['dsa_student_birthday']));
    
    update_user_meta( $user_id, '_dsa_is_retired', isset( $_POST['dsa_is_retired'] ) ? '1' : '0' );
    update_user_meta( $user_id, '_dsa_family_discount', isset( $_POST['dsa_family_discount'] ) ? '1' : '0' );

    if ( ! empty( $_POST['pass1'] ) && ! empty( $_POST['pass2'] ) ) {
        if ( $_POST['pass1'] === $_POST['pass2'] ) {
            wp_set_password( $_POST['pass1'], $user_id );
        } else {
            wp_safe_redirect( add_query_arg('error', 'password_mismatch', wp_get_referer()) );
            exit;
        }
    }

    wp_safe_redirect( add_query_arg('profile_updated', 'true', wp_get_referer()) );
    exit;
}


// --- Handler for login form ---
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
        wp_safe_redirect( add_query_arg('login', 'failed', wp_get_referer()) );
        exit;
    }
    
    $redirect_url = home_url('/student-dashboard/'); 
    wp_safe_redirect( $redirect_url );
    exit;
}


// --- Registration form handler with placeholder claiming logic ---
add_action( 'admin_post_nopriv_dsa_register', 'dsa_handle_registration_form_submission' );
function dsa_handle_registration_form_submission() {
    if ( ! isset( $_POST['dsa_register_nonce'] ) || ! wp_verify_nonce( $_POST['dsa_register_nonce'], 'dsa_register_action' ) ) {
        wp_die('Security check failed!');
    }

    $redirect_url = wp_get_referer() ? wp_get_referer() : home_url();

    // --- LOGIC: Check for an invitation code first ---
    if ( ! empty($_POST['invitation_code']) ) {
        $invitation_code = sanitize_text_field(wp_unslash($_POST['invitation_code']));
        $user_query = new WP_User_Query([
            'meta_key' => '_dsa_invitation_code', 'meta_value' => $invitation_code, 'fields' => 'ID',
        ]);
        $found_users = $user_query->get_results();

        if ( ! empty($found_users) ) {
            $user_id_to_claim = $found_users[0];
            if ( empty($_POST['pass1']) || $_POST['pass1'] !== $_POST['pass2'] ) {
                wp_safe_redirect( add_query_arg('reg_error', 'password_mismatch', $redirect_url) ); exit;
            }
            $user_data = [
                'ID' => $user_id_to_claim, 'user_email' => sanitize_email($_POST['user_email']),
                'first_name' => sanitize_text_field($_POST['first_name']), 'last_name'  => sanitize_text_field($_POST['last_name']),
                'user_pass'  => $_POST['pass1'],
            ];
            wp_update_user($user_data);
            update_user_meta($user_id_to_claim, '_dsa_account_status', 'claimed');
            delete_user_meta($user_id_to_claim, '_dsa_invitation_code');
            wp_set_auth_cookie($user_id_to_claim, true);
            wp_safe_redirect( home_url('/student-dashboard/') ); exit;
        } else {
            wp_safe_redirect( add_query_arg('reg_error', 'invalid_code', $redirect_url) ); exit;
        }
    }
    
    // --- If no invitation code, proceed with normal new user registration ---
    $required_fields = ['user_login', 'user_email', 'first_name', 'last_name', 'pass1', 'pass2'];
    foreach ( $required_fields as $field ) {
        if ( empty($_POST[$field]) ) { wp_safe_redirect( add_query_arg('reg_error', 'required', $redirect_url) ); exit; }
    }
    if ( ! is_email($_POST['user_email']) ) { wp_safe_redirect( add_query_arg('reg_error', 'email_invalid', $redirect_url) ); exit; }
    if ( username_exists(sanitize_user($_POST['user_login'])) ) { wp_safe_redirect( add_query_arg('reg_error', 'username_exists', $redirect_url) ); exit; }
    if ( email_exists($_POST['user_email']) ) { wp_safe_redirect( add_query_arg('reg_error', 'email_exists', $redirect_url) ); exit; }
    if ( $_POST['pass1'] !== $_POST['pass2'] ) { wp_safe_redirect( add_query_arg('reg_error', 'password_mismatch', $redirect_url) ); exit; }

    $user_data = [
        'user_login' => sanitize_user($_POST['user_login']), 'user_email' => sanitize_email($_POST['user_email']),
        'first_name' => sanitize_text_field($_POST['first_name']), 'last_name'  => sanitize_text_field($_POST['last_name']),
        'user_pass'  => $_POST['pass1'], 'role' => 'student'
    ];
    $user_id = wp_insert_user($user_data);

    if ( is_wp_error($user_id) ) {
        wp_safe_redirect( add_query_arg('reg_error', 'unknown', $redirect_url) ); exit;
    }
    
    if ( ! empty($_POST['phone']) ) { update_user_meta($user_id, '_dsa_user_phone', sanitize_text_field($_POST['phone'])); }
    if ( ! empty($_POST['birth_date']) ) { update_user_meta($user_id, '_dsa_user_birth_date', sanitize_text_field($_POST['birth_date'])); }
    update_user_meta( $user_id, '_dsa_account_status', 'claimed' );
    $login_page_url = home_url('/login/');
    wp_safe_redirect( add_query_arg('registered', 'true', $login_page_url) ); 
    exit;
}


// --- ADDED: Handler for the "Claim Profile" form for social login users ---
add_action( 'admin_post_dsa_claim_profile', 'dsa_handle_claim_profile_submission' );
function dsa_handle_claim_profile_submission() {
    // Security checks: must be logged in and have a valid nonce.
    if ( ! is_user_logged_in() || ! isset($_POST['dsa_claim_profile_nonce']) || ! wp_verify_nonce($_POST['dsa_claim_profile_nonce'], 'dsa_claim_profile_action') ) {
        wp_die('Security check failed!');
    }

    $redirect_url = wp_get_referer() ? wp_get_referer() : home_url();
    $current_user_id = get_current_user_id();
    $invitation_code = isset($_POST['invitation_code']) ? sanitize_text_field(wp_unslash($_POST['invitation_code'])) : '';

    // Find the placeholder user with the matching code.
    $user_query = new WP_User_Query([
        'meta_query' => [
            'relation' => 'AND',
            ['key' => '_dsa_invitation_code', 'value' => $invitation_code],
            ['key' => '_dsa_account_status', 'value' => 'placeholder']
        ],
        'fields' => 'ID',
    ]);
    $found_users = $user_query->get_results();

    // If no valid placeholder user is found, redirect with an error.
    if ( empty($found_users) ) {
        wp_safe_redirect( add_query_arg('claim_error', 'invalid_code', $redirect_url) );
        exit;
    }

    $placeholder_user_id = $found_users[0];

    // --- Start the Merge Process ---

    // 1. Reassign enrollment records
    $enrollment_records = get_posts(['post_type' => 'dsa_enroll_record', 'author' => $placeholder_user_id, 'posts_per_page' => -1]);
    foreach ($enrollment_records as $record) {
        wp_update_post(['ID' => $record->ID, 'post_author' => $current_user_id]);
    }

    // 2. Reassign private lessons where placeholder is student 1
    $private_lessons_s1 = get_posts(['post_type' => 'dsa_private_lesson', 'posts_per_page' => -1, 'meta_key' => '_dsa_lesson_student1_id', 'meta_value' => $placeholder_user_id]);
    foreach ($private_lessons_s1 as $lesson) {
        update_post_meta($lesson->ID, '_dsa_lesson_student1_id', $current_user_id);
    }
    
    // 3. Reassign private lessons where placeholder is student 2
    $private_lessons_s2 = get_posts(['post_type' => 'dsa_private_lesson', 'posts_per_page' => -1, 'meta_key' => '_dsa_lesson_student2_id', 'meta_value' => $placeholder_user_id]);
    foreach ($private_lessons_s2 as $lesson) {
        update_post_meta($lesson->ID, '_dsa_lesson_student2_id', $current_user_id);
    }
    
    // 4. Reassign WooCommerce orders
    if (class_exists('WooCommerce')) {
        $placeholder_user_data = get_userdata($placeholder_user_id);
        $orders = wc_get_orders(['customer' => $placeholder_user_data->user_email]);
        foreach ($orders as $order) {
            update_post_meta($order->get_id(), '_customer_user', $current_user_id);
        }
    }

    // 5. Transfer user meta (phone, birthday, etc.) if the current user's fields are empty.
    $meta_to_transfer = ['_dsa_user_phone', '_dsa_user_birth_date', '_dsa_user_street', '_dsa_user_postal_code', '_dsa_user_city', '_dsa_is_retired', '_dsa_family_discount'];
    foreach ($meta_to_transfer as $meta_key) {
        $current_value = get_user_meta($current_user_id, $meta_key, true);
        if (empty($current_value)) {
            $placeholder_value = get_user_meta($placeholder_user_id, $meta_key, true);
            if (!empty($placeholder_value)) {
                update_user_meta($current_user_id, $meta_key, $placeholder_value);
            }
        }
    }

    // 6. Delete the old placeholder user.
    wp_delete_user($placeholder_user_id);

    // --- Merge Complete ---
    wp_safe_redirect( add_query_arg('claim_result', 'success', $redirect_url) );
    exit;
}