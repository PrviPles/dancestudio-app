<?php
/**
 * Form Handler for updating couple details.
 * UPDATED: Saves the "Couple Type" field and handles archiving/unarchiving.
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action( 'admin_post_dsa_update_couple_details', 'dsa_handle_update_couple_details' );
function dsa_handle_update_couple_details() {
    
    // 1. Verify nonce and permissions
    if ( ! isset( $_POST['dsa_couple_details_nonce'] ) || ! wp_verify_nonce( $_POST['dsa_couple_details_nonce'], 'dsa_couple_details_action' ) ) {
        wp_die('Security check failed!');
    }
    if ( ! current_user_can( 'edit_users' ) ) {
        wp_die('You do not have permission to edit users.');
    }

    // 2. Get and validate the user IDs
    $user1_id = isset( $_POST['user1_id'] ) ? absint( $_POST['user1_id'] ) : 0;
    $user2_id = isset( $_POST['user2_id'] ) ? absint( $_POST['user2_id'] ) : 0;
    if ( ! $user1_id || ! $user2_id ) {
        wp_die('Invalid user IDs provided.');
    }

    // 3. Prepare the redirect URL
    $redirect_url = add_query_arg([
        'page'       => 'dsa-couples-tab',
        'action'     => 'view_couple_details',
        'user1_id'   => $user1_id,
        'user2_id'   => $user2_id,
    ], admin_url( 'admin.php' ));

    // 4. Handle specific actions like Archive/Unarchive first
    if ( isset($_POST['submit_archive_couple']) ) {
        update_user_meta( $user1_id, '_dsa_couple_status', 'archived' );
        update_user_meta( $user2_id, '_dsa_couple_status', 'archived' );
        // After archiving, redirect to the main list with a success message.
        wp_safe_redirect( add_query_arg(['page' => 'dsa-couples-tab', 'message' => 'archived'], admin_url('admin.php')) );
        exit;
    }
    if ( isset($_POST['submit_unarchive_couple']) ) {
        update_user_meta( $user1_id, '_dsa_couple_status', 'active' );
        update_user_meta( $user2_id, '_dsa_couple_status', 'active' );
        wp_safe_redirect( add_query_arg('message', 'unarchived', $redirect_url) );
        exit;
    }

    // 5. If not a special action, proceed with saving all other data from a "Save Changes" click
    if ( isset( $_POST['dsa_couple_type'] ) ) {
        $couple_type = sanitize_key( $_POST['dsa_couple_type'] );
        $allowed_types = ['wedding', 'recreation', 'competitive'];
        if ( in_array( $couple_type, $allowed_types, true ) ) {
            update_user_meta( $user1_id, '_dsa_couple_type', $couple_type );
            update_user_meta( $user2_id, '_dsa_couple_type', $couple_type );
        }
    }
    
    if ( isset( $_POST['dsa_pairing_date'] ) ) {
        $pairing_date = sanitize_text_field( $_POST['dsa_pairing_date'] );
        update_user_meta( $user1_id, '_dsa_pairing_date', $pairing_date );
        update_user_meta( $user2_id, '_dsa_pairing_date', $pairing_date );
    }

    if ( isset( $_POST['dsa_wedding_date'] ) ) {
        $wedding_date = sanitize_text_field( $_POST['dsa_wedding_date'] );
        update_user_meta( $user1_id, 'dsa_wedding_date', $wedding_date );
        update_user_meta( $user2_id, 'dsa_wedding_date', $wedding_date );
    }

    $sanitized_songs = [];
    if ( isset( $_POST['dsa_songs'] ) && is_array( $_POST['dsa_songs'] ) ) {
        foreach ( $_POST['dsa_songs'] as $song_data ) {
            if ( ! empty($song_data['name']) || ! empty($song_data['url']) ) {
                $sanitized_songs[] = [
                    'name' => sanitize_text_field( $song_data['name'] ),
                    'url'  => esc_url_raw( $song_data['url'] ),
                ];
            }
        }
    }
    update_user_meta( $user1_id, '_dsa_couple_songs', $sanitized_songs );
    update_user_meta( $user2_id, '_dsa_couple_songs', $sanitized_songs );

    // 6. Redirect back with a generic 'updated' success message
    wp_safe_redirect( add_query_arg('message', 'updated', $redirect_url) );
    exit;
}