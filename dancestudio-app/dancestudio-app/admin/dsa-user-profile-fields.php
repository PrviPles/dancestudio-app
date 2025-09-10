<?php
/**
 * Adds custom fields to the User Profile screen.
 */
if ( ! defined( 'WPINC' ) ) { die; }

add_action( 'show_user_profile', 'dsa_show_custom_user_profile_fields' );
add_action( 'edit_user_profile', 'dsa_show_custom_user_profile_fields' );
function dsa_show_custom_user_profile_fields( $user ) {
    if ( ! is_admin() ) {
        return;
    }
    ?>
    <h3><?php esc_html_e("Dance Studio Info", "dancestudio-app"); ?></h3>
    <table class="form-table">
        <tr><th><label for="dsa_user_birth_date"><?php esc_html_e("Birth Date"); ?></label></th><td><input type="date" name="dsa_user_birth_date" id="dsa_user_birth_date" value="<?php echo esc_attr( get_user_meta( $user->ID, '_dsa_user_birth_date', true ) ); ?>" class="regular-text" /></td></tr>
        <tr><th><label for="dsa_user_phone"><?php esc_html_e("Phone Number"); ?></label></th><td><input type="tel" name="dsa_user_phone" id="dsa_user_phone" value="<?php echo esc_attr( get_user_meta( $user->ID, '_dsa_user_phone', true ) ); ?>" class="regular-text" /></td></tr>
        <tr>
            <th scope="row"><strong><?php _e( 'Actively Enrolled In', 'dancestudio-app' ); ?></strong></th>
            <td>
                <fieldset>
                <?php
                $all_groups_query = new WP_Query(array('post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
                
                $active_enrollments = get_posts([
                    'post_type'   => 'dsa_enroll_record',
                    'post_status' => 'publish',
                    'author'      => $user->ID,
                    'posts_per_page' => -1,
                ]);
                $enrolled_group_ids = wp_list_pluck( $active_enrollments, 'post_parent' );

                if ( $all_groups_query->have_posts() ):
                    while ( $all_groups_query->have_posts() ): $all_groups_query->the_post();
                        $group_id = get_the_ID();
                        ?>
                        <label for="dsa-group-<?php echo esc_attr($group_id); ?>" style="display: block; margin-bottom: 5px;">
                            <input type="checkbox" class="dsa-enrollment-checkbox" data-user-id="<?php echo esc_attr($user->ID); ?>" name="dsa_user_groups[]" id="dsa-group-<?php echo esc_attr($group_id); ?>" value="<?php echo esc_attr($group_id); ?>" <?php checked( in_array( $group_id, $enrolled_group_ids ) ); ?>>
                            <?php the_title(); ?>
                        </label>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else:
                    echo '<em>' . __('No dance groups have been created yet.', 'dancestudio-app') . '</em>';
                endif;
                ?>
                </fieldset>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'personal_options_update', 'dsa_save_custom_user_profile_fields' );
add_action( 'edit_user_profile_update', 'dsa_save_custom_user_profile_fields' );
function dsa_save_custom_user_profile_fields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) return false;

    if ( isset( $_POST['dsa_user_birth_date'] ) ) { 
        update_user_meta( $user_id, '_dsa_user_birth_date', sanitize_text_field( $_POST['dsa_user_birth_date'] ) ); 
    }
    if ( isset( $_POST['dsa_user_phone'] ) ) { 
        update_user_meta( $user_id, '_dsa_user_phone', sanitize_text_field( $_POST['dsa_user_phone'] ) ); 
    }
    
    if ( isset( $_POST['dsa_user_groups'] ) ) {
        $submitted_groups = isset( $_POST['dsa_user_groups'] ) ? array_map( 'absint', $_POST['dsa_user_groups'] ) : [];
        $active_enrollments = get_posts(['post_type' => 'dsa_enroll_record', 'post_status' => 'publish', 'author' => $user_id, 'posts_per_page' => -1]);
        $active_group_ids = wp_list_pluck( $active_enrollments, 'post_parent' );

        $groups_to_enroll = array_diff( $submitted_groups, $active_group_ids );
        foreach ( $groups_to_enroll as $group_id ) { 
            dsa_enroll_student_in_group( $user_id, $group_id ); 
        }

        $groups_to_dropout = array_diff( $active_group_ids, $submitted_groups );
        foreach ( $groups_to_dropout as $group_id ) { 
            dsa_dropout_student_from_group( $user_id, $group_id ); 
        }
    }
}