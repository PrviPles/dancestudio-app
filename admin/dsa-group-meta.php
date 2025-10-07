<?php
/**
 * Adds meta boxes to the Dance Group CPT.
 * UPDATED: Adds a new meta box for linking a standard AND a retiree WooCommerce product.
 * @package DanceStudioApp
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_dsa_group', 'dsa_add_group_meta_boxes' );
function dsa_add_group_meta_boxes() {
    add_meta_box(
        'dsa_group_enrollments_mb',
        __( 'Manage Student Enrollments', 'dancestudio-app' ),
        'dsa_render_group_enrollments_mb',
        'dsa_group',
        'normal',
        'high'
    );

    add_meta_box(
        'dsa_group_subscription_mb',
        __( 'Subscription & Pricing', 'dancestudio-app' ),
        'dsa_render_group_subscription_mb',
        'dsa_group',
        'side',
        'default'
    );
}

function dsa_render_group_enrollments_mb( $post ) {
    // This existing function is unchanged.
    $group_id = $post->ID;
    $active_enrollments = get_posts( [
        'post_type'        => 'dsa_enroll_record',
        'post_status'      => 'publish',
        'post_parent'      => $group_id,
        'posts_per_page'   => -1,
        'fields'           => 'ids',
    ] );
    $enrolled_student_ids = [];
    foreach ( $active_enrollments as $record_id ) {
        $enrolled_student_ids[] = (int) get_post_field( 'post_author', $record_id );
    }
    $all_students = get_users( [
        'role__in' => [ 'student', 'subscriber' ],
        'orderby'  => 'display_name',
        'order'    => 'ASC',
    ] );
    ?>
    <div class="dsa-enrollment-manager">
        <div id="dsa-enrollment-messages" style="display: none; margin-bottom: 15px;"></div>
        <div style="border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 20px;">
            <h4><?php esc_html_e( 'Enroll a New Student', 'dancestudio-app' ); ?></h4>
            <div id="dsa-enroll-student-fields">
                <label for="dsa_student_to_enroll" style="margin-right: 10px;">
                    <?php esc_html_e( 'Select a student to add:', 'dancestudio-app' ); ?>
                </label>
                <select name="student_id" id="dsa_student_to_enroll">
                    <option value="0"><?php esc_html_e( '-- Select a Student --', 'dancestudio-app' ); ?></option>
                    <?php foreach ( $all_students as $student ) :
                        if ( ! in_array( $student->ID, $enrolled_student_ids, true ) ) : ?>
                            <option value="<?php echo esc_attr( $student->ID ); ?>">
                                <?php echo esc_html( $student->display_name ); ?>
                            </option>
                        <?php endif;
                    endforeach; ?>
                </select>
                <button type="button" id="dsa-enroll-student-button" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e( 'Enroll Student', 'dancestudio-app' ); ?>
                </button>
                <span class="spinner"></span>
            </div>
        </div>

        <h4><?php esc_html_e( 'Active Members in this Group', 'dancestudio-app' ); ?></h4>
        <table class="wp-list-table widefat striped" id="dsa-active-members-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Student Name', 'dancestudio-app' ); ?></th>
                    <th><?php esc_html_e( 'Enrolled On', 'dancestudio-app' ); ?></th>
                    <th style="text-align: right;"><?php esc_html_e( 'Actions', 'dancestudio-app' ); ?></th>
                </tr>
            </thead>
            <tbody id="dsa-active-members-list">
                <?php if ( ! empty( $active_enrollments ) ) : ?>
                    <?php foreach ( $active_enrollments as $record_id ) :
                        $student_id   = (int) get_post_field( 'post_author', $record_id );
                        $student_data = get_userdata( $student_id );
                        if ( ! $student_data ) {
                            continue;
                        }
                        $enroll_date = get_the_date( get_option( 'date_format' ), $record_id );
                        ?>
                        <tr id="dsa-member-row-<?php echo esc_attr( $student_id ); ?>">
                            <td>
                                <a href="<?php echo esc_url( get_edit_user_link( $student_id ) ); ?>">
                                    <strong><?php echo esc_html( $student_data->display_name ); ?></strong>
                                </a>
                            </td>
                            <td><?php echo esc_html( $enroll_date ); ?></td>
                            <td style="text-align: right;">
                                <button type="button" class="button button-link-delete dsa-dropout-button" data-student-id="<?php echo esc_attr( $student_id ); ?>">
                                    <?php esc_html_e( 'Drop Out', 'dancestudio-app' ); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr id="dsa-no-members-row">
                        <td colspan="3"><?php esc_html_e( 'No students are currently enrolled in this group.', 'dancestudio-app' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Renders the "Subscription & Pricing" meta box content.
 */
function dsa_render_group_subscription_mb( $post ) {
    wp_nonce_field( 'dsa_save_group_meta_action', 'dsa_group_meta_nonce' );

    $linked_product_id = get_post_meta( $post->ID, '_dsa_linked_product_id', true );
    $linked_retiree_product_id = get_post_meta( $post->ID, '_dsa_linked_retiree_product_id', true );

    $products_query = get_posts([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish',
    ]);
    ?>
    <p>
        <label for="dsa_linked_product_id"><strong><?php esc_html_e('Linked Standard Product', 'dancestudio-app'); ?></strong></label>
    </p>
    <select name="_dsa_linked_product_id" id="dsa_linked_product_id" class="widefat">
        <option value="0"><?php esc_html_e('-- None --', 'dancestudio-app'); ?></option>
        <?php if ( ! empty($products_query) ) : ?>
            <?php foreach ($products_query as $product) : ?>
                <option value="<?php echo esc_attr($product->ID); ?>" <?php selected($linked_product_id, $product->ID); ?>>
                    <?php echo esc_html($product->post_title); ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
    <p class="description">
        <?php esc_html_e('Select the main WooCommerce product for this group\'s subscription.', 'dancestudio-app'); ?>
    </p>

    <hr style="margin: 20px 0;">

    <p>
        <label for="dsa_linked_retiree_product_id"><strong><?php esc_html_e('Linked Retiree Product (Optional)', 'dancestudio-app'); ?></strong></label>
    </p>
    <select name="_dsa_linked_retiree_product_id" id="dsa_linked_retiree_product_id" class="widefat">
        <option value="0"><?php esc_html_e('-- None --', 'dancestudio-app'); ?></option>
        <?php if ( ! empty($products_query) ) : ?>
            <?php foreach ($products_query as $product) : ?>
                <option value="<?php echo esc_attr($product->ID); ?>" <?php selected($linked_retiree_product_id, $product->ID); ?>>
                    <?php echo esc_html($product->post_title); ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
    <p class="description">
        <?php esc_html_e('Select a product with a special price for students marked as retirees.', 'dancestudio-app'); ?>
    </p>
    <?php
}

/**
 * Saves all meta data for the Dance Group CPT.
 */
add_action( 'save_post_dsa_group', 'dsa_save_group_meta_data' );
function dsa_save_group_meta_data( $post_id ) {
    if ( ! isset( $_POST['dsa_group_meta_nonce'] ) || ! wp_verify_nonce( $_POST['dsa_group_meta_nonce'], 'dsa_save_group_meta_action' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save the linked standard product ID
    if ( isset( $_POST['_dsa_linked_product_id'] ) ) {
        update_post_meta( $post_id, '_dsa_linked_product_id', absint( $_POST['_dsa_linked_product_id'] ) );
    }

    // Save the linked retiree product ID
    if ( isset( $_POST['_dsa_linked_retiree_product_id'] ) ) {
        update_post_meta( $post_id, '_dsa_linked_retiree_product_id', absint( $_POST['_dsa_linked_retiree_product_id'] ) );
    }
}