<?php
/**
 * Adds meta boxes to the Private Lesson CPT.
 *
 * @package DanceStudioApp
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'dsa_add_private_lesson_meta_boxes' ) ) {
    function dsa_add_private_lesson_meta_boxes( $post_type, $post ) {
        if ( 'dsa_private_lesson' !== $post_type ) {
            return;
        }

        add_meta_box(
            'dsa_private_lesson_details_mb',
            __( 'Lesson Details', 'dancestudio-app' ),
            'dsa_render_private_lesson_details_mb',
            'dsa_private_lesson',
            'normal',
            'high'
        );
        
        // NEW Meta Box for Knowledge Base
        add_meta_box(
            'dsa_lesson_knowledge_mb',
            __( 'Practiced Dances & Figures', 'dancestudio-app' ),
            'dsa_render_lesson_knowledge_mb',
            'dsa_private_lesson',
            'normal',
            'high'
        );

        add_meta_box(
            'dsa_private_lesson_order_mb',
            __( 'Link to Order', 'dancestudio-app' ),
            'dsa_render_private_lesson_order_mb',
            'dsa_private_lesson',
            'side',
            'default'
        );
    }
    add_action( 'add_meta_boxes', 'dsa_add_private_lesson_meta_boxes', 10, 2 );
}

if ( ! function_exists( 'dsa_render_private_lesson_details_mb' ) ) {
    function dsa_render_private_lesson_details_mb( $post ) {
        wp_nonce_field( 'dsa_save_private_lesson_meta_action', 'dsa_private_lesson_meta_nonce' );

        $date       = get_post_meta( $post->ID, '_dsa_lesson_date', true );
        $start_time = get_post_meta( $post->ID, '_dsa_lesson_start_time', true );
        $student1   = get_post_meta( $post->ID, '_dsa_lesson_student1_id', true );
        $student2   = get_post_meta( $post->ID, '_dsa_lesson_student2_id', true );
        $teacher_id = get_post_meta( $post->ID, '_dsa_lesson_teacher_id', true );
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="dsa_meta_lesson_date"><?php esc_html_e( 'Date:', 'dancestudio-app' ); ?></label></th>
                <td><input type="date" id="dsa_meta_lesson_date" name="dsa_lesson_date" value="<?php echo esc_attr( $date ); ?>" required /></td>
            </tr>
            <tr>
                <th scope="row"><label for="dsa_meta_lesson_start_time"><?php esc_html_e( 'Start Time:', 'dancestudio-app' ); ?></label></th>
                <td><input type="time" id="dsa_meta_lesson_start_time" name="dsa_lesson_start_time" value="<?php echo esc_attr( $start_time ); ?>" required /></td>
            </tr>
            <tr>
                <th scope="row"><label for="dsa_lesson_student1_id"><?php esc_html_e( 'Student 1:', 'dancestudio-app' ); ?></label></th>
                <td><?php
                    wp_dropdown_users( [
                        'name'            => 'dsa_lesson_student1_id',
                        'id'              => 'dsa_lesson_student1_id',
                        'selected'        => $student1,
                        'show_option_none'=> __( '-- Select --', 'dancestudio-app' ),
                        'role__in'        => [ 'subscriber', 'student' ],
                        'show_fullname'   => true,
                        'orderby'         => 'display_name',
                    ] );
                ?></td>
            </tr>
            <tr>
                <th scope="row"><label for="dsa_lesson_student2_id"><?php esc_html_e( 'Student 2 (if couple):', 'dancestudio-app' ); ?></label></th>
                <td><?php
                    wp_dropdown_users( [
                        'name'            => 'dsa_lesson_student2_id',
                        'id'              => 'dsa_lesson_student2_id',
                        'selected'        => $student2,
                        'show_option_none'=> __( '-- None --', 'dancestudio-app' ),
                        'role__in'        => [ 'subscriber', 'student' ],
                        'show_fullname'   => true,
                        'orderby'         => 'display_name',
                    ] );
                ?></td>
            </tr>
            <tr>
                <th scope="row"><label for="dsa_lesson_teacher_id"><?php esc_html_e( 'Teacher:', 'dancestudio-app' ); ?></label></th>
                <td><?php
                    wp_dropdown_users( [
                        'name'            => 'dsa_lesson_teacher_id',
                        'id'              => 'dsa_lesson_teacher_id',
                        'selected'        => $teacher_id,
                        'show_option_none'=> __( '-- Select Staff --', 'dancestudio-app' ),
                        'role__in'        => [ 'teacher', 'studio_manager', 'administrator' ],
                        'show_fullname'   => true,
                        'orderby'         => 'display_name',
                    ] );
                ?></td>
            </tr>
        </table>
        <?php
    }
}

function dsa_render_lesson_knowledge_mb( $post ) {
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="dsa_practiced_dance"><?php esc_html_e( 'Dance:', 'dancestudio-app' ); ?></label></th>
            <td>
                <?php
                wp_dropdown_categories( [
                    'show_option_none' => __( '-- Select a Dance --', 'dancestudio-app' ),
                    'taxonomy'         => 'dsa_dance',
                    'name'             => 'dsa_practiced_dance',
                    'id'               => 'dsa_practiced_dance',
                    'selected'         => 0, // Always start with none selected, JS will handle loading
                    'hierarchical'     => true,
                    'hide_empty'       => false,
                ] );
                ?>
                <p class="description"><?php esc_html_e('Select a dance to load the available figures.', 'dancestudio-app'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row" style="vertical-align: top;"><label><?php esc_html_e( 'Figures Practiced:', 'dancestudio-app' ); ?></label></th>
            <td>
                <div id="dsa-figures-checklist-wrapper" style="height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                    <em><?php esc_html_e('Please select a dance above.', 'dancestudio-app'); ?></em>
                </div>
                <span class="spinner" style="float:none; vertical-align: middle;"></span>
            </td>
        </tr>
    </table>
    <?php
}

if ( ! function_exists( 'dsa_render_private_lesson_order_mb' ) ) {
    function dsa_render_private_lesson_order_mb( $post ) {
        $order_id = get_post_meta( $post->ID, '_dsa_lesson_order_id', true );
        ?>
        <div id="dsa-order-linking-wrapper">
            <p>
                <label for="dsa_lesson_order_id"><?php esc_html_e( 'WooCommerce Order:', 'dancestudio-app' ); ?></label>
                <select name="dsa_lesson_order_id" id="dsa_lesson_order_id" class="widefat" disabled data-selected-order-id="<?php echo esc_attr( $order_id ); ?>">
                    <option value="0"><?php esc_html_e( 'Please select a student first', 'dancestudio-app' ); ?></option>
                </select>
                <span class="spinner" style="float:none; vertical-align: middle;"></span>
            </p>
            <p class="description"><?php esc_html_e( 'Select Student 1 above to see their available lesson packages.', 'dancestudio-app' ); ?></p>
        </div>
        <?php
    }
}

if ( ! function_exists( 'dsa_save_private_lesson_meta' ) ) {
    function dsa_save_private_lesson_meta( $post_id, $post ) {
        if ( ! isset( $_POST['dsa_private_lesson_meta_nonce'] )
            || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dsa_private_lesson_meta_nonce'] ) ), 'dsa_save_private_lesson_meta_action' )
        ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields_to_save = [
            '_dsa_lesson_date'       => 'sanitize_text_field',
            '_dsa_lesson_start_time' => 'sanitize_text_field',
            '_dsa_lesson_student1_id'=> 'absint',
            '_dsa_lesson_student2_id'=> 'absint',
            '_dsa_lesson_teacher_id' => 'absint',
            '_dsa_lesson_order_id'   => 'absint',
        ];

        foreach ( $fields_to_save as $meta_key => $sanitize_cb ) {
            $post_key = str_replace( '_dsa_', 'dsa_', $meta_key );
            if ( isset( $_POST[ $post_key ] ) ) {
                $value = call_user_func( $sanitize_cb, wp_unslash( $_POST[ $post_key ] ) );
                update_post_meta( $post_id, $meta_key, $value );
            }
        }

        // --- ADDED: Save the practiced figures ---
        if ( isset( $_POST['dsa_practiced_figures'] ) && is_array( $_POST['dsa_practiced_figures'] ) ) {
            $sanitized_figure_ids = array_map( 'absint', $_POST['dsa_practiced_figures'] );
            update_post_meta( $post_id, '_dsa_practiced_figure_ids', $sanitized_figure_ids );
        } else {
            // If no figures are checked, remove the meta key to keep the database clean.
            delete_post_meta( $post_id, '_dsa_practiced_figure_ids' );
        }
    }
    add_action( 'save_post_dsa_private_lesson', 'dsa_save_private_lesson_meta', 10, 2 );
}