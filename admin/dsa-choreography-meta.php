<?php
/**
 * Adds meta boxes to the Choreography CPT.
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the meta box and remove the default taxonomy box.
 */
add_action( 'add_meta_boxes_dsa_choreography', 'dsa_add_choreography_meta_box' );
function dsa_add_choreography_meta_box() {
    add_meta_box(
        'dsa_choreography_details_mb',
        __( 'Choreography Details', 'dancestudio-app' ),
        'dsa_render_choreography_details_mb',
        'dsa_choreography',
        'normal',
        'high'
    );
    remove_meta_box('dsa_difficulty_leveldiv', 'dsa_choreography', 'side');
}

/**
 * Render the HTML for the meta box form fields.
 */
function dsa_render_choreography_details_mb( $post ) {
    wp_nonce_field( 'dsa_save_choreography_meta_action', 'dsa_choreography_nonce' );

    $details = get_post_meta( $post->ID, '_dsa_choreography_details', true );
    $details = is_array($details) ? $details : [];

    $song_title    = $details['song_title'] ?? '';
    $song_artist   = $details['song_artist'] ?? '';
    $choreographer = $details['choreographer'] ?? '';
    $counts        = $details['counts'] ?? '';
    $walls         = $details['walls'] ?? '';
    $restarts      = $details['restarts'] ?? '';
    $tags          = $details['tags'] ?? '';
    $sequence      = $details['sequence'] ?? '';
    $song_file_url = $details['song_file_url'] ?? '';
    $video_url     = $details['video_url'] ?? '';

    wp_enqueue_media();
    ?>
    <p><em><?php esc_html_e( 'Use the main content editor above for the step sheets.', 'dancestudio-app'); ?></em></p>
    <table class="form-table">
        <tr>
            <th><label for="dsa_song_title"><?php esc_html_e('Song Title', 'dancestudio-app'); ?></label></th>
            <td><input type="text" id="dsa_song_title" name="dsa_details[song_title]" value="<?php echo esc_attr($song_title); ?>" class="widefat"></td>
        </tr>
        <tr>
            <th><label for="dsa_song_artist"><?php esc_html_e('Song Artist', 'dancestudio-app'); ?></label></th>
            <td><input type="text" id="dsa_song_artist" name="dsa_details[song_artist]" value="<?php echo esc_attr($song_artist); ?>" class="widefat"></td>
        </tr>
        <tr>
            <th><label for="dsa_choreographer"><?php esc_html_e('Choreographer', 'dancestudio-app'); ?></label></th>
            <td><input type="text" id="dsa_choreographer" name="dsa_details[choreographer]" value="<?php echo esc_attr($choreographer); ?>" class="widefat"></td>
        </tr>
        <tr>
            <th><label for="dsa_difficulty_level"><?php esc_html_e('Difficulty', 'dancestudio-app'); ?></label></th>
            <td>
                <?php
                $current_term_id = 0;
                $terms = wp_get_post_terms($post->ID, 'dsa_difficulty_level');
                if (!empty($terms) && !is_wp_error($terms)) {
                    $current_term_id = $terms[0]->term_id;
                }
                
                wp_dropdown_categories([
                    'show_option_none' => __( '-- Select a Level --', 'dancestudio-app' ),
                    'taxonomy'         => 'dsa_difficulty_level',
                    'name'             => 'dsa_difficulty_level',
                    'id'               => 'dsa_difficulty_level',
                    'selected'         => $current_term_id,
                    'hierarchical'     => true,
                    'hide_empty'       => false,
                ]);
                ?>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e('Dance Details', 'dancestudio-app'); ?></th>
            <td style="display: flex; flex-wrap: wrap; gap: 20px;">
                <div class="detail-item">
                    <label for="dsa_counts"><strong><?php esc_html_e('Counts', 'dancestudio-app'); ?></strong></label><br>
                    <input type="number" id="dsa_counts" name="dsa_details[counts]" value="<?php echo esc_attr($counts); ?>" class="small-text">
                </div>
                <div class="detail-item">
                    <label for="dsa_walls"><strong><?php esc_html_e('Walls', 'dancestudio-app'); ?></strong></label><br>
                    <input type="number" id="dsa_walls" name="dsa_details[walls]" value="<?php echo esc_attr($walls); ?>" class="small-text">
                </div>
                <div class="detail-item">
                    <label for="dsa_restarts"><strong><?php esc_html_e('Restarts', 'dancestudio-app'); ?></strong></label><br>
                    <input type="text" id="dsa_restarts" name="dsa_details[restarts]" value="<?php echo esc_attr($restarts); ?>" placeholder="<?php esc_attr_e('e.g., After wall 3', 'dancestudio-app'); ?>">
                </div>
                <div class="detail-item">
                    <label for="dsa_tags"><strong><?php esc_html_e('Tags', 'dancestudio-app'); ?></strong></label><br>
                    <input type="text" id="dsa_tags" name="dsa_details[tags]" value="<?php echo esc_attr($tags); ?>" placeholder="<?php esc_attr_e('e.g., 2', 'dancestudio-app'); ?>">
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="dsa_sequence"><?php esc_html_e('Sequence', 'dancestudio-app'); ?></label></th>
            <td><textarea id="dsa_sequence" name="dsa_details[sequence]" class="widefat" rows="4" placeholder="<?php esc_attr_e('e.g., A, A, B, Restart, A, B', 'dancestudio-app'); ?>"><?php echo esc_textarea($sequence); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="dsa_song_file_url"><?php esc_html_e('Song File (MP3)', 'dancestudio-app'); ?></label></th>
            <td>
                <input type="text" id="dsa_song_file_url" name="dsa_details[song_file_url]" value="<?php echo esc_url($song_file_url); ?>" class="widefat">
                <button type="button" class="button dsa-upload-song-button" style="margin-top: 5px;"><?php esc_html_e('Upload / Select Song', 'dancestudio-app'); ?></button>
            </td>
        </tr>
        <tr>
            <th><label for="dsa_video_url"><?php esc_html_e('Video URL', 'dancestudio-app'); ?></label></th>
            <td><input type="url" id="dsa_video_url" name="dsa_details[video_url]" value="<?php echo esc_url($video_url); ?>" class="widefat" placeholder="https://www.youtube.com/watch?v=..."></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Assign to Group(s)', 'dancestudio-app'); ?></th>
            <td>
                <fieldset>
                    <?php
                    $all_groups = get_posts(['post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                    $assigned_groups = get_post_meta($post->ID, '_dsa_assigned_group_ids', true);
                    if (!is_array($assigned_groups)) {
                        $assigned_groups = [];
                    }

                    if (empty($all_groups)) {
                        echo '<em>' . esc_html__('No dance groups have been created yet.', 'dancestudio-app') . '</em>';
                    } else {
                        foreach ($all_groups as $group) {
                            ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="dsa_assigned_groups[]" value="<?php echo esc_attr($group->ID); ?>" <?php checked(in_array($group->ID, $assigned_groups)); ?>>
                                <?php echo esc_html($group->post_title); ?>
                            </label>
                            <?php
                        }
                    }
                    ?>
                </fieldset>
            </td>
        </tr>
    </table>
    <script>
    jQuery(document).ready(function($){
        $('body').on('click', '.dsa-upload-song-button', function(e){
            e.preventDefault();
            var button = $(this);
            var mediaUploader = wp.media({
                title: '<?php esc_html_e("Select Song File", "dancestudio-app"); ?>',
                button: { text: '<?php esc_html_e("Use this song", "dancestudio-app"); ?>' },
                library: { type: 'audio' },
                multiple: false
            }).on('select', function(){
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                button.prev('input').val(attachment.url);
            }).open();
        });
    });
    </script>
    <?php
}

/**
 * Save the custom field data when the post is saved.
 */
add_action( 'save_post_dsa_choreography', 'dsa_save_choreography_meta_data' );
function dsa_save_choreography_meta_data( $post_id ) {
    if ( !isset($_POST['dsa_choreography_nonce']) || !wp_verify_nonce($_POST['dsa_choreography_nonce'], 'dsa_save_choreography_meta_action') ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( !current_user_can('edit_post', $post_id) ) return;

    if ( isset($_POST['dsa_details']) && is_array($_POST['dsa_details']) ) {
        $sanitized_details = [
            'song_title'    => sanitize_text_field( $_POST['dsa_details']['song_title'] ?? '' ),
            'song_artist'   => sanitize_text_field( $_POST['dsa_details']['song_artist'] ?? '' ),
            'choreographer' => sanitize_text_field( $_POST['dsa_details']['choreographer'] ?? '' ),
            'counts'        => absint( $_POST['dsa_details']['counts'] ?? 0 ),
            'walls'         => absint( $_POST['dsa_details']['walls'] ?? 0 ),
            'restarts'      => sanitize_text_field( $_POST['dsa_details']['restarts'] ?? '' ),
            'tags'          => sanitize_text_field( $_POST['dsa_details']['tags'] ?? '' ),
            'sequence'      => sanitize_textarea_field( $_POST['dsa_details']['sequence'] ?? '' ),
            'song_file_url' => esc_url_raw( $_POST['dsa_details']['song_file_url'] ?? '' ),
            'video_url'     => esc_url_raw( $_POST['dsa_details']['video_url'] ?? '' ),
        ];
        update_post_meta($post_id, '_dsa_choreography_details', $sanitized_details);
    }
    
    if ( isset($_POST['dsa_difficulty_level']) ) {
        $term_id = absint($_POST['dsa_difficulty_level']);
        if ($term_id > 0) {
            wp_set_post_terms($post_id, [$term_id], 'dsa_difficulty_level');
        } else {
            wp_set_post_terms($post_id, [], 'dsa_difficulty_level');
        }
    }

    if ( isset($_POST['dsa_assigned_groups']) && is_array($_POST['dsa_assigned_groups']) ) {
        $sanitized_group_ids = array_map('absint', $_POST['dsa_assigned_groups']);
        update_post_meta($post_id, '_dsa_assigned_group_ids', $sanitized_group_ids);
    } else {
        delete_post_meta($post_id, '_dsa_assigned_group_ids');
    }
}

/**
 * Adds a "< Back" button to the top of the edit screen.
 */
add_action( 'edit_form_top', 'dsa_add_back_button_to_choreography_edit_screen' );
function dsa_add_back_button_to_choreography_edit_screen( $post ) {
    if ( 'dsa_choreography' !== $post->post_type ) {
        return;
    }

    $back_url = admin_url('admin.php?page=dsa-choreographies-tab');
    
    echo '<div style="margin-bottom: 15px;">';
    echo '<a href="' . esc_url($back_url) . '" class="button">&larr; ' . esc_html__('Back to All Choreographies', 'dancestudio-app') . '</a>';
    echo '</div>';
}