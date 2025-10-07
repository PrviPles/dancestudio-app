<?php
/**
 * AJAX Handler for creating a new Choreography from a modal.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action( 'wp_ajax_dsa_create_choreography_ajax', 'dsa_handle_create_choreography_ajax' );
function dsa_handle_create_choreography_ajax() {
    // 1. Security Checks
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'dsa_create_choreography_nonce' ) ) {
        wp_send_json_error( ['message' => 'Security check failed.'] );
    }
    if ( ! current_user_can( 'publish_posts' ) ) {
        wp_send_json_error( ['message' => 'You do not have permission to create choreographies.'] );
    }

    // 2. Sanitize and Validate required fields
    $post_title = isset( $_POST['post_title'] ) ? sanitize_text_field( $_POST['post_title'] ) : '';
    if ( empty( $post_title ) ) {
        wp_send_json_error( ['message' => 'Choreography Name (Title) is a required field.'] );
    }

    // 3. Create the new Choreography post
    $new_post_id = wp_insert_post( [
        'post_type'    => 'dsa_choreography',
        'post_title'   => $post_title,
        'post_content' => isset( $_POST['content'] ) ? wp_kses_post( $_POST['content'] ) : '',
        'post_status'  => 'publish',
    ], true );

    if ( is_wp_error( $new_post_id ) ) {
        wp_send_json_error( ['message' => $new_post_id->get_error_message()] );
    }

    // 4. Save all the custom meta data
    if ( isset( $_POST['dsa_details'] ) && is_array( $_POST['dsa_details'] ) ) {
        $details = $_POST['dsa_details'];
        $sanitized_details = [
            'song_title'    => sanitize_text_field( $details['song_title'] ?? '' ),
            'song_artist'   => sanitize_text_field( $details['song_artist'] ?? '' ),
            'choreographer' => sanitize_text_field( $details['choreographer'] ?? '' ),
            'counts'        => absint( $details['counts'] ?? 0 ),
            'walls'         => absint( $details['walls'] ?? 0 ),
            'restarts'      => sanitize_text_field( $details['restarts'] ?? '' ),
            'sequence'      => sanitize_textarea_field( $details['sequence'] ?? '' ),
            'song_file_url' => esc_url_raw( $details['song_file_url'] ?? '' ),
            'video_url'     => esc_url_raw( $details['video_url'] ?? '' ),
        ];
        update_post_meta($new_post_id, '_dsa_choreography_details', $sanitized_details);
    }
    
    // Save the selected difficulty level
    if ( isset($_POST['dsa_difficulty_level']) ) {
        $term_id = absint($_POST['dsa_difficulty_level']);
        if ($term_id > 0) {
            wp_set_post_terms($new_post_id, [$term_id], 'dsa_difficulty_level');
        }
    }

    // Save the assigned groups
    if ( isset($_POST['dsa_assigned_groups']) && is_array($_POST['dsa_assigned_groups']) ) {
        $sanitized_group_ids = array_map('absint', $_POST['dsa_assigned_groups']);
        update_post_meta($new_post_id, '_dsa_assigned_group_ids', $sanitized_group_ids);
    }

    // 5. Prepare a response to send back to the JavaScript
    // We will build the HTML for the new table row here.
    $difficulty_terms = get_the_terms($new_post_id, 'dsa_difficulty_level');
    $difficulty_display = !empty($difficulty_terms) ? esc_html($difficulty_terms[0]->name) : '—';
    $song_display = !empty($sanitized_details['song_title']) ? esc_html($sanitized_details['song_title']) : '—';
    $view_link = add_query_arg(['page' => 'dsa-choreographies-tab', 'action' => 'view', 'choreography_id' => $new_post_id], admin_url('admin.php'));
    
    $html  = '<tr>';
    $html .= '<td><strong><a href="' . esc_url($view_link) . '">' . esc_html($post_title) . '</a></strong></td>';
    $html .= '<td>' . $song_display . '</td>';
    $html .= '<td>' . $difficulty_display . '</td>';
    $html .= '<td>—</td>'; // Assigned groups, empty for now as it's complex to get names here. Page reload will show them.
    $html .= '</tr>';
    
    wp_send_json_success( [
        'message' => 'Choreography created successfully!',
        'html'    => $html
    ] );
}