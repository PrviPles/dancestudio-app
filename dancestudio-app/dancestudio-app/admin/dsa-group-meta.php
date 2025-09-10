<?php
/**
 * Adds meta boxes to the Dance Group CPT.
 *
 * @package DanceStudioApp
 */

defined( 'ABSPATH' ) || exit; // Standard WP guard

if ( ! function_exists( 'dsa_add_group_meta_boxes' ) ) {
    function dsa_add_group_meta_boxes() {
        add_meta_box(
            'dsa_group_enrollments_mb',
            __( 'Manage Student Enrollments', 'dancestudio-app' ),
            'dsa_render_group_enrollments_mb',
            'dsa_group',
            'normal',
            'high'
        );
    }
    add_action( 'add_meta_boxes_dsa_group', 'dsa_add_group_meta_boxes' );
}

function dsa_render_group_enrollments_mb( $post ) {
    $group_id = $post->ID;

    // Get all student IDs actively enrolled in this group
    $active_enrollments = get_posts( [
        'post_type'        => 'dsa_enroll_record',
        'post_status'      => 'publish',
        'post_parent'      => $group_id,
        'posts_per_page'   => -1,
        'orderby'          => 'post_title',
        'order'            => 'ASC',
        'fields'           => 'ids',
        'suppress_filters' => true,
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
                        $enroll_date = get_post_meta( $record_id, '_dsa_enrollment_date', true );
                        $timestamp   = strtotime( $enroll_date );
                        ?>
                        <tr id="dsa-member-row-<?php echo esc_attr( $student_id ); ?>">
                            <td>
                                <a href="<?php echo esc_url( get_edit_user_link( $student_id ) ); ?>">
                                    <strong><?php echo esc_html( $student_data->display_name ); ?></strong>
                                </a>
                            </td>
                            <td>
                                <?php
                                if ( $timestamp ) {
                                    echo esc_html( date_i18n( get_option( 'date_format' ), $timestamp ) );
                                } else {
                                    esc_html_e( 'Unknown', 'dancestudio-app' );
                                }
                                ?>
                            </td>
                            <td style="text-align: right;">
                                <button type="button"
                                        class="button button-link-delete dsa-dropout-button"
                                        data-student-id="<?php echo esc_attr( $student_id ); ?>">
                                    <?php esc_html_e( 'Drop Out', 'dancestudio-app' ); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr id="dsa-no-members-row">
                        <td colspan="3">
                            <?php esc_html_e( 'No students are currently enrolled in this group.', 'dancestudio-app' ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
