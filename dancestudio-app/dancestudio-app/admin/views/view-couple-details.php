<?php
/**
 * View: Renders the detailed profile page for a single couple.
 */
if ( ! defined( 'WPINC' ) ) { die; }

if ( ! function_exists( 'dsa_render_single_couple_details_view' ) ) {
    function dsa_render_single_couple_details_view( $user1_id, $user2_id ) {
        $user1_data = get_userdata($user1_id); 
        $user2_data = get_userdata($user2_id);

        if ( ! $user1_data || ! $user2_data ) { 
            echo '<div class="wrap"><h1>'.esc_html__('Error').'</h1><p>'.esc_html__('Invalid user ID.').'</p></div>'; 
            return; 
        }

        $u1_fn = trim($user1_data->first_name.' '.$user1_data->last_name) ?: $user1_data->display_name;
        $u2_fn = trim($user2_data->first_name.' '.$user2_data->last_name) ?: $user2_data->display_name;
        
        if ( isset($_GET['message']) && $_GET['message'] === 'updated' ) {
            echo '<div id="message" class="updated notice is-dismissible"><p>' . esc_html__('Couple details have been updated successfully!', 'dancestudio-app') . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php printf(esc_html__('Details for Couple: %s & %s', 'dancestudio-app'), esc_html($u1_fn), esc_html($u2_fn)); ?></h1>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        
                        <div class="postbox">
                            <h2 class="hndle"><span><?php _e('Wedding & Song Details'); ?></span></h2>
                            <div class="inside">
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="dsa_update_couple_details">
                                    <input type="hidden" name="user1_id" value="<?php echo esc_attr($user1_id); ?>">
                                    <input type="hidden" name="user2_id" value="<?php echo esc_attr($user2_id); ?>">
                                    <?php wp_nonce_field('dsa_couple_details_action', 'dsa_couple_details_nonce'); ?>
                                    
                                    <table class="form-table">
                                        <tr>
                                            <th><label for="dsa_wedding_date"><?php _e('Wedding Date', 'dancestudio-app'); ?></label></th>
                                            <td><input type="date" id="dsa_wedding_date" name="dsa_wedding_date" value="<?php echo esc_attr(get_user_meta($user1_id, 'dsa_wedding_date', true)); ?>"></td>
                                        </tr>
                                    </table>
                                    
                                    <hr>
                                    
                                    <h4><?php _e('Wedding Songs', 'dancestudio-app'); ?></h4>
                                    <div id="dsa-songs-repeater-wrapper">
                                        <?php
                                        $songs = get_user_meta($user1_id, '_dsa_couple_songs', true);
                                        if (empty($songs) || !is_array($songs)) {
                                            $songs = [['name' => '', 'url' => '']];
                                        }

                                        foreach ($songs as $index => $song) : ?>
                                        <div class="dsa-song-entry" style="padding: 15px; border: 1px solid #ddd; margin-bottom: 10px; background: #f9f9f9;">
                                            <p>
                                                <label style="display: block; font-weight: bold; margin-bottom: 5px;"><?php _e('Song Name', 'dancestudio-app'); ?></label>
                                                <input type="text" name="dsa_songs[<?php echo $index; ?>][name]" value="<?php echo esc_attr($song['name']); ?>" class="widefat" placeholder="e.g., Ed Sheeran - Perfect">
                                            </p>
                                            <p>
                                                <label style="display: block; font-weight: bold; margin-bottom: 5px;"><?php _e('Song URL (YouTube)', 'dancestudio-app'); ?></label>
                                                <input type="url" name="dsa_songs[<?php echo $index; ?>][url]" value="<?php echo esc_attr($song['url']); ?>" class="widefat dsa-song-url-input">
                                            </p>
                                            <button type="button" class="button button-link-delete dsa-delete-song-button"><?php _e('Delete Song'); ?></button>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" id="dsa-add-song-button" class="button button-secondary"><?php _e('+ Add Another Song', 'dancestudio-app'); ?></button>
                                    
                                    <hr style="margin-top: 20px;">
                                    <?php submit_button(__('Save Couple\'s Details', 'dancestudio-app')); ?>
                                </form>
                            </div>
                        </div>

                        <div class="postbox">
                            <h2 class="hndle"><span><?php _e('Song Players', 'dancestudio-app'); ?></span></h2>
                            <div class="inside">
                                <?php
                                $songs_to_play = get_user_meta($user1_id, '_dsa_couple_songs', true);
                                if ( ! empty($songs_to_play) && is_array($songs_to_play) ) {
                                    $has_player = false;
                                    foreach ($songs_to_play as $song) {
                                        $song_url = isset($song['url']) ? $song['url'] : '';
                                        if ( ! empty($song_url) && filter_var($song_url, FILTER_VALIDATE_URL) ) {
                                            $embed_url = '';
                                            if ( strpos($song_url, 'youtu.be') !== false || strpos($song_url, 'youtube.com') !== false ) {
                                                preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $song_url, $match);
                                                $video_id = isset($match[1]) ? $match[1] : '';
                                                if ( $video_id ) {
                                                    $embed_url = 'https://www.youtube.com/embed/' . $video_id;
                                                }
                                            }
                                            if ( $embed_url ) {
                                                $song_name = isset($song['name']) && !empty($song['name']) ? $song['name'] : 'Song Player';
                                                echo '<h4>' . esc_html($song_name) . '</h4>';
                                                echo '<div class="dsa-responsive-embed"><iframe src="' . esc_url($embed_url) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div><hr style="margin-top:20px;">';
                                                $has_player = true;
                                            }
                                        }
                                    }
                                    if (!$has_player) { echo '<p><em>' . __('No playable song URLs have been added.', 'dancestudio-app') . '</em></p>'; }
                                } else {
                                    echo '<p><em>' . __('No songs have been added for this couple.', 'dancestudio-app') . '</em></p>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="postbox">
                            <h2 class="hndle"><span><?php _e('Lesson Package Tracker', 'dancestudio-app'); ?></span></h2>
                            <div class="inside">
                                <?php if(function_exists('dsa_render_order_tracker_table')) dsa_render_order_tracker_table(['customer_id' => [$user1_id, $user2_id]]); ?>
                            </div>
                        </div>

                        <div class="postbox">
                            <h2 class="hndle"><span><?php _e('Private Lesson History', 'dancestudio-app'); ?></span></h2>
                            <div class="inside">
                                <?php
                                $lesson_args = [
                                    'post_type' => 'dsa_private_lesson',
                                    'posts_per_page' => -1,
                                    'meta_query' => ['relation' => 'OR', ['key' => '_dsa_lesson_student1_id', 'value' => $user1_id], ['key' => '_dsa_lesson_student1_id', 'value' => $user2_id], ['key' => '_dsa_lesson_student2_id', 'value' => $user1_id], ['key' => '_dsa_lesson_student2_id', 'value' => $user2_id], ],
                                    'meta_key' => '_dsa_lesson_date', 'orderby' => 'meta_value', 'order' => 'DESC',
                                ];
                                $couple_lessons = get_posts($lesson_args);
                                ?>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead><tr><th><?php _e('Lesson Date', 'dancestudio-app'); ?></th><th><?php _e('Lesson Title', 'dancestudio-app'); ?></th><th><?php _e('Teacher', 'dancestudio-app'); ?></th><th><?php _e('Actions', 'dancestudio-app'); ?></th></tr></thead>
                                    <tbody>
                                        <?php if ( ! empty( $couple_lessons ) ) : ?>
                                            <?php foreach ( $couple_lessons as $lesson ) : ?>
                                                <tr>
                                                    <td><?php echo esc_html( get_post_meta( $lesson->ID, '_dsa_lesson_date', true ) ); ?></td>
                                                    <td><?php echo esc_html( $lesson->post_title ); ?></td>
                                                    <td><?php echo esc_html( get_the_author_meta( 'display_name', get_post_meta( $lesson->ID, '_dsa_lesson_teacher_id', true ) ) ); ?></td>
                                                    <td><a href="<?php echo get_edit_post_link( $lesson->ID ); ?>" class="button button-small"><?php _e('Edit Lesson'); ?></a></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr><td colspan="4"><?php _e('No private lessons found for this couple.', 'dancestudio-app'); ?></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                    <div id="postbox-container-1" class="postbox-container">
                        </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var wrapper = $('#dsa-songs-repeater-wrapper');
            
            $('#dsa-add-song-button').on('click', function(e) {
                e.preventDefault();
                var newIndex = wrapper.find('.dsa-song-entry').length;
                var newField = `
                <div class="dsa-song-entry" style="padding: 15px; border: 1px solid #ddd; margin-bottom: 10px; background: #f9f9f9;">
                    <p><label style="display: block; font-weight: bold; margin-bottom: 5px;">Song Name</label>
                    <input type="text" name="dsa_songs[${newIndex}][name]" value="" class="widefat" placeholder="e.g., Ed Sheeran - Perfect"></p>
                    <p><label style="display: block; font-weight: bold; margin-bottom: 5px;">Song URL (YouTube)</label>
                    <input type="url" name="dsa_songs[${newIndex}][url]" value="" class="widefat dsa-song-url-input"></p>
                    <button type="button" class="button button-link-delete dsa-delete-song-button">Delete Song</button>
                </div>`;
                wrapper.append(newField);
            });

            wrapper.on('click', '.dsa-delete-song-button', function(e) {
                e.preventDefault();
                if (wrapper.find('.dsa-song-entry').length > 1) {
                    $(this).closest('.dsa-song-entry').remove();
                } else {
                    alert('You must have at least one song entry.');
                    $(this).closest('.dsa-song-entry').find('input').val('');
                }
            });
        });
        </script>
        <style> .dsa-responsive-embed { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; } .dsa-responsive-embed iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; } </style>
        <?php
    }
}