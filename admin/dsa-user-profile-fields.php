<?php
/**
 * Adds custom fields to the User Profile screen.
 */
if ( ! defined( 'WPINC' ) ) { die; }

// This function adds fields to the EDIT user screen
add_action( 'show_user_profile', 'dsa_show_custom_user_profile_fields' );
add_action( 'edit_user_profile', 'dsa_show_custom_user_profile_fields' );
function dsa_show_custom_user_profile_fields( $user ) {
    if ( ! is_admin() ) {
        return;
    }
    ?>
    <h3><?php esc_html_e("Dance Studio Info", "dancestudio-app"); ?></h3>
    <table class="form-table">
        <tr><th><label for="dsa_user_phone"><?php esc_html_e("Phone Number", "dancestudio-app"); ?></label></th><td><input type="tel" name="dsa_user_phone" id="dsa_user_phone" value="<?php echo esc_attr( get_user_meta( $user->ID, '_dsa_user_phone', true ) ); ?>" class="regular-text" /></td></tr>
        <tr><th><label for="dsa_user_birth_date"><?php esc_html_e("Birth Date", "dancestudio-app"); ?></label></th><td><input type="date" name="dsa_user_birth_date" id="dsa_user_birth_date" value="<?php echo esc_attr( get_user_meta( $user->ID, '_dsa_user_birth_date', true ) ); ?>" class="regular-text" /></td></tr>
        <tr><th><label for="dsa_user_street"><?php esc_html_e("Street & House Number", "dancestudio-app"); ?></label></th><td><input type="text" name="dsa_user_street" id="dsa_user_street" value="<?php echo esc_attr( get_user_meta( $user->ID, '_dsa_user_street', true ) ); ?>" class="regular-text" /></td></tr>
        <tr><th><label for="dsa_user_postal_code"><?php esc_html_e("Postal Code", "dancestudio-app"); ?></label></th><td><input type="text" name="dsa_user_postal_code" id="dsa_user_postal_code" value="<?php echo esc_attr( get_user_meta( $user->ID, '_dsa_user_postal_code', true ) ); ?>" class="regular-text" /></td></tr>
        <tr><th><label for="dsa_user_city"><?php esc_html_e("City / Town", "dancestudio-app"); ?></label></th><td><input type="text" name="dsa_user_city" id="dsa_user_city" value="<?php echo esc_attr( get_user_meta( $user->ID, '_dsa_user_city', true ) ); ?>" class="regular-text" /></td></tr>
    </table>
    <?php
}

// This function adds the placeholder checkbox to the ADD NEW user screen
add_action( 'user_new_form', 'dsa_add_placeholder_user_field' );
function dsa_add_placeholder_user_field() {
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Account Type', 'dancestudio-app'); ?></th>
            <td>
                <fieldset>
                    <label for="dsa_is_placeholder">
                        <input type="checkbox" name="dsa_is_placeholder" id="dsa_is_placeholder" value="1">
                        <?php esc_html_e('Create as a placeholder account (student will not have login access)', 'dancestudio-app'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Check this for students who will not be using the app. An invitation code will be generated for them to register later.', 'dancestudio-app'); ?></p>
                </fieldset>
            </td>
        </tr>
    </table>
    <?php
}


// This function saves data when an EXISTING user is updated
add_action( 'personal_options_update', 'dsa_save_custom_user_profile_fields' );
add_action( 'edit_user_profile_update', 'dsa_save_custom_user_profile_fields' );
function dsa_save_custom_user_profile_fields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) return false;

    if ( isset( $_POST['dsa_user_phone'] ) ) { update_user_meta( $user_id, '_dsa_user_phone', sanitize_text_field( $_POST['dsa_user_phone'] ) ); }
    if ( isset( $_POST['dsa_user_birth_date'] ) ) { update_user_meta( $user_id, '_dsa_user_birth_date', sanitize_text_field( $_POST['dsa_user_birth_date'] ) ); }
    if ( isset( $_POST['dsa_user_street'] ) ) { update_user_meta( $user_id, '_dsa_user_street', sanitize_text_field( $_POST['dsa_user_street'] ) ); }
    if ( isset( $_POST['dsa_user_postal_code'] ) ) { update_user_meta( $user_id, '_dsa_user_postal_code', sanitize_text_field( $_POST['dsa_user_postal_code'] ) ); }
    if ( isset( $_POST['dsa_user_city'] ) ) { update_user_meta( $user_id, '_dsa_user_city', sanitize_text_field( $_POST['dsa_user_city'] ) ); }
}


// This function runs when a NEW user is created
add_action( 'user_register', 'dsa_save_new_user_custom_fields', 10, 1 );
function dsa_save_new_user_custom_fields( $user_id ) {
    if ( isset( $_POST['dsa_user_phone'] ) ) { update_user_meta( $user_id, '_dsa_user_phone', sanitize_text_field( $_POST['dsa_user_phone'] ) ); }
    if ( isset( $_POST['dsa_user_birth_date'] ) ) { update_user_meta( $user_id, '_dsa_user_birth_date', sanitize_text_field( $_POST['dsa_user_birth_date'] ) ); }
    if ( isset( $_POST['dsa_user_street'] ) ) { update_user_meta( $user_id, '_dsa_user_street', sanitize_text_field( $_POST['dsa_user_street'] ) ); }
    if ( isset( $_POST['dsa_user_postal_code'] ) ) { update_user_meta( $user_id, '_dsa_user_postal_code', sanitize_text_field( $_POST['dsa_user_postal_code'] ) ); }
    if ( isset( $_POST['dsa_user_city'] ) ) { update_user_meta( $user_id, '_dsa_user_city', sanitize_text_field( $_POST['dsa_user_city'] ) ); }

    // Logic for creating a placeholder account
    if ( isset( $_POST['dsa_is_placeholder'] ) && $_POST['dsa_is_placeholder'] === '1' ) {
        // Generate a unique, fake email address that won't conflict with real users
        $placeholder_email = 'student.' . $user_id . '.' . time() . '@placeholder.dancestudio.app';

        // Update the user's email to the fake one
        wp_update_user( [
            'ID' => $user_id,
            'user_email' => $placeholder_email
        ] );

        // Generate a unique invitation code
        $invitation_code = 'DSA-' . strtoupper( substr( md5( uniqid( rand(), true ) ), 0, 8 ) );

        // Set the meta fields to identify this as a placeholder account
        update_user_meta( $user_id, '_dsa_account_status', 'placeholder' );
        update_user_meta( $user_id, '_dsa_invitation_code', $invitation_code );
    }
}