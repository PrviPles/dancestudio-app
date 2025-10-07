<?php
/**
 * Public facing shortcodes for the DanceStudio App plugin.
 */
if ( ! defined( 'WPINC' ) ) die;

// --- Shortcode for the Main Student Dashboard ---
add_shortcode( 'dancestudio_dashboard', 'dsa_render_unified_dashboard_shortcode' );
function dsa_render_unified_dashboard_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>' . __( 'Please log in to view your dashboard.', 'dancestudio-app' ) . '</p>';
    }
    ob_start();
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'profile';
    $base_url = get_permalink();
    ?>
    <div class="dsa-dashboard-wrapper dsa-student-dashboard">
        <nav class="dsa-dashboard-nav">
            <ul>
                <li class="<?php if($active_tab == 'profile') echo 'active'; ?>"><a href="<?php echo esc_url(add_query_arg('tab', 'profile', $base_url)); ?>"><?php _e('My Profile', 'dancestudio-app'); ?></a></li>
                <li class="<?php if($active_tab == 'classes') echo 'active'; ?>"><a href="<?php echo esc_url(add_query_arg('tab', 'classes', $base_url)); ?>"><?php _e('My Group Classes', 'dancestudio-app'); ?></a></li>
                <li class="<?php if($active_tab == 'lessons') echo 'active'; ?>"><a href="<?php echo esc_url(add_query_arg('tab', 'lessons', $base_url)); ?>"><?php _e('My Private Lessons', 'dancestudio-app'); ?></a></li>
                <li class="<?php if($active_tab == 'repertoire') echo 'active'; ?>"><a href="<?php echo esc_url(add_query_arg('tab', 'repertoire', $base_url)); ?>"><?php _e('Repertoire', 'dancestudio-app'); ?></a></li>
                <li class="<?php if($active_tab == 'calendar') echo 'active'; ?>"><a href="<?php echo esc_url(add_query_arg('tab', 'calendar', $base_url)); ?>"><?php _e('Calendar', 'dancestudio-app'); ?></a></li>
                <li class="<?php if($active_tab == 'settings') echo 'active'; ?>"><a href="<?php echo esc_url(add_query_arg('tab', 'settings', $base_url)); ?>"><?php _e('Settings', 'dancestudio-app'); ?></a></li>
            </ul>
            <div class="dsa-logout-link">
                <a href="<?php echo wp_logout_url(home_url());?>"><?php _e('Log Out','dancestudio-app');?></a>
            </div>
        </nav>
        <main class="dsa-dashboard-content">
            <?php
            switch ($active_tab) {
                case 'classes':
                    $template_path = DSA_PLUGIN_DIR . 'public/views/dashboard-classes.php';
                    break;
                case 'lessons':
                    $template_path = DSA_PLUGIN_DIR . 'public/views/dashboard-lessons.php';
                    break;
                case 'repertoire':
                    $template_path = DSA_PLUGIN_DIR . 'public/views/dashboard-repertoire.php';
                    break;
                case 'calendar':
                    $template_path = DSA_PLUGIN_DIR . 'public/views/dashboard-calendar.php';
                    break;
                case 'settings':
                    $template_path = DSA_PLUGIN_DIR . 'public/views/dashboard-settings.php';
                    break;
                case 'profile':
                default:
                    $template_path = DSA_PLUGIN_DIR . 'public/views/dashboard-profile.php';
                    break;
            }
            if ( file_exists($template_path) ) {
                include $template_path;
            }
            ?>
        </main>
    </div>
    <?php
    return ob_get_clean();
}

// --- Shortcode for the Login Form ---
add_shortcode( 'dancestudio_login_form', 'dsa_render_login_form_shortcode' );
function dsa_render_login_form_shortcode() {
    if (is_user_logged_in()) {
        return '<p>' . __('You are already logged in.', 'dancestudio-app') . '</p>';
    }
    ob_start();
    ?>
    <div class="dsa-login-form">
        <?php 
        if (isset($_GET['login']) && $_GET['login'] == 'failed') {
            echo '<p class="dsa-error">' . __('Login failed. Please try again.', 'dancestudio-app') . '</p>';
        }
        if (isset($_GET['registered']) && $_GET['registered'] == 'true') {
            echo '<p class="dsa-success">' . __('Registration successful! You can now log in.', 'dancestudio-app') . '</p>';
        }
        ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="dsa_login">
            <?php wp_nonce_field('dsa_login_action', 'dsa_login_nonce'); ?>
            <p>
                <label for="dsa_user_login"><?php _e('Username or Email Address', 'dancestudio-app'); ?></label>
                <input type="text" name="log" id="dsa_user_login" required>
            </p>
            <p>
                <label for="dsa_user_pass"><?php _e('Password', 'dancestudio-app'); ?></label>
                <input type="password" name="pwd" id="dsa_user_pass" required>
            </p>
            <p>
                <label>
                    <input name="rememberme" type="checkbox" id="dsa_rememberme" value="forever"> <?php _e('Remember Me', 'dancestudio-app'); ?>
                </label>
            </p>
            <p>
                <input type="submit" value="<?php _e('Log In', 'dancestudio-app'); ?>">
            </p>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// --- Shortcode for the Registration Form ---
add_shortcode( 'dancestudio_registration_form', 'dsa_render_registration_form_shortcode' );
function dsa_render_registration_form_shortcode() {
    if (is_user_logged_in()) {
        return '';
    }
    ob_start();
    ?>
    <div class="dsa-registration-form">
        <?php
        if (isset($_GET['reg_error'])) {
            $error_msg = __('An unknown error occurred.', 'dancestudio-app');
            switch ($_GET['reg_error']) {
                case 'required': $error_msg = __('All required fields must be filled out.', 'dancestudio-app'); break;
                case 'email_invalid': $error_msg = __('The email address is not valid.', 'dancestudio-app'); break;
                case 'username_exists': $error_msg = __('That username is already taken.', 'dancestudio-app'); break;
                case 'email_exists': $error_msg = __('That email address is already registered.', 'dancestudio-app'); break;
                case 'password_mismatch': $error_msg = __('The passwords do not match.', 'dancestudio-app'); break;
                case 'invalid_code': $error_msg = __('The invitation code you entered is not valid.', 'dancestudio-app'); break;
            }
            echo '<p class="dsa-error">' . esc_html($error_msg) . '</p>';
        }
        ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="dsa_register">
            <?php wp_nonce_field('dsa_register_action', 'dsa_register_nonce'); ?>
            
            <p>
                <label for="dsa_user_login"><?php _e('Username', 'dancestudio-app'); ?> *</label>
                <input type="text" name="user_login" id="dsa_user_login" required>
            </p>
            <p>
                <label for="dsa_user_email"><?php _e('Email', 'dancestudio-app'); ?> *</label>
                <input type="email" name="user_email" id="dsa_user_email" required>
            </p>
            <p>
                <label for="dsa_first_name"><?php _e('First Name', 'dancestudio-app'); ?> *</label>
                <input type="text" name="first_name" id="dsa_first_name" required>
            </p>
            <p>
                <label for="dsa_last_name"><?php _e('Last Name', 'dancestudio-app'); ?> *</label>
                <input type="text" name="last_name" id="dsa_last_name" required>
            </p>
            <p>
                <label for="dsa_pass1"><?php _e('Password', 'dancestudio-app'); ?> *</label>
                <input type="password" name="pass1" id="dsa_pass1" required>
            </p>
            <p>
                <label for="dsa_pass2"><?php _e('Confirm Password', 'dancestudio-app'); ?> *</label>
                <input type="password" name="pass2" id="dsa_pass2" required>
            </p>
            <hr>
            <p>
                <label for="dsa_invitation_code"><?php _e('Invitation Code (Optional)', 'dancestudio-app'); ?></label>
                <input type="text" name="invitation_code" id="dsa_invitation_code">
            </p>
            <p class="description"><?php _e('If the studio has already created a profile for you, enter the invitation code here to claim it.', 'dancestudio-app'); ?></p>
            
            <p>
                <input type="submit" value="<?php _e('Register', 'dancestudio-app'); ?>">
            </p>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// --- Shortcode for the "Claim Profile" form ---
add_shortcode( 'dsa_claim_profile_form', 'dsa_render_claim_profile_form_shortcode' );
function dsa_render_claim_profile_form_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>' . __('You must be logged in to claim a profile.', 'dancestudio-app') . '</p>';
    }

    ob_start();
    ?>
    <div class="dsa-claim-form">
        <h3><?php _e('Claim Your Studio Profile', 'dancestudio-app'); ?></h3>

        <?php // Display success or error messages
        if (isset($_GET['claim_result']) && $_GET['claim_result'] === 'success') {
            echo '<p class="dsa-success">' . __('Success! Your account has been linked to your studio records.', 'dancestudio-app') . '</p>';
        }
        if (isset($_GET['claim_error'])) {
            $error_msg = __('An unknown error occurred.', 'dancestudio-app');
            if ($_GET['claim_error'] === 'invalid_code') {
                $error_msg = __('The invitation code you entered was not found or has already been used.', 'dancestudio-app');
            }
            echo '<p class="dsa-error">' . esc_html($error_msg) . '</p>';
        }
        ?>

        <p><?php _e('If your instructor gave you an invitation code, enter it below to link your login to your existing class history and packages.', 'dancestudio-app'); ?></p>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="dsa_claim_profile">
            <?php wp_nonce_field('dsa_claim_profile_action', 'dsa_claim_profile_nonce'); ?>

            <p>
                <label for="dsa_claim_invitation_code"><strong><?php _e('Invitation Code', 'dancestudio-app'); ?></strong></label>
                <input type="text" name="invitation_code" id="dsa_claim_invitation_code" required>
            </p>
            <p>
                <input type="submit" value="<?php _e('Claim My Profile', 'dancestudio-app'); ?>">
            </p>
        </form>
    </div>
    <?php
    return ob_get_clean();
}