<?php
/**
 * Form Handler for updating couple details.
 *
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

    // 3. Update the standard meta fields
    if ( isset( $_POST['dsa_wedding_date'] ) ) {
        $wedding_date = sanitize_text_field( $_POST['dsa_wedding_date'] );
        update_user_meta( $user1_id, 'dsa_wedding_date', $wedding_date );
        update_user_meta( $user2_id, 'dsa_wedding_date', $wedding_date );
    }

    // 4. --- NEW: Process and save the array of songs ---
    $sanitized_songs = [];
    if ( isset( $_POST['dsa_songs'] ) && is_array( $_POST['dsa_songs'] ) ) {
        foreach ( $_POST['dsa_songs'] as $song_data ) {
            // Only add the song if it has a name or a URL to avoid saving empty entries
            if ( ! empty($song_data['name']) || ! empty($song_data['url']) ) {
                $sanitized_songs[] = [
                    'name' => sanitize_text_field( $song_data['name'] ),
                    'url'  => esc_url_raw( $song_data['url'] ),
                ];
            }
        }
    }
    // Save the sanitized array to both users' meta
    update_user_meta( $user1_id, '_dsa_couple_songs', $sanitized_songs );
    update_user_meta( $user2_id, '_dsa_couple_songs', $sanitized_songs );


    // 5. Redirect back to the couple details page with a success message
    $redirect_url = add_query_arg([
        'page'       => 'dsa-couples-tab',
        'action'     => 'view_couple_details',
        'user1_id'   => $user1_id,
        'user2_id'   => $user2_id,
        'message'    => 'updated'
    ], admin_url( 'admin.php' ));

    wp_safe_redirect( $redirect_url );
    exit;
}