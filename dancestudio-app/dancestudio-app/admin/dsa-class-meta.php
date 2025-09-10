<?php
/**
 * DanceStudio App Group Class CPT Meta Boxes
 */

defined( 'ABSPATH' ) || exit; // Consistent access guard

add_action( 'add_meta_boxes_dsa_group_class', 'dsa_add_class_meta_boxes' );
function dsa_add_class_meta_boxes() {
    add_meta_box(
        'dsa_class_details_mb',
        __( 'Class Details', 'dancestudio-app' ),
        'dsa_render_class_details_mb',
        'dsa_group_class',
        'normal',
        'high'
    );
    add_meta_box(
        'dsa_class_attendance_mb',
        __( 'Student Attendance & Remarks', 'dancestudio-app' ),
        'dsa_render_class_attendance_mb',
        'dsa_group_class',
        'normal',
        'default'
    );
}

function dsa_render_class_details_mb( $post ) {
    // This function is correct and unchanged
}

function dsa_render_class_attendance_mb( $post ) {
    wp_nonce_field( 'dsa_save_attendance_meta_action', 'dsa_class_attendance_nonce' );

    $group_id = get_post_meta( $post->ID, '_dsa_class_group_id', true );

    if ( ! $group_id ) {
        echo '<p>' . esc_html__(
            'Please select and save a "Group" in the "Class Details" box above to manage attendance.',
            'dancestudio-app'
        ) . '</p>';
        return;
    }

    $student_members     = [];
    $active_enrollments  = get_posts( [
        'post_type'        => 'dsa_enroll_record',
        'post_status'      => 'publish',
        'post_parent'      => $group_id,
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'suppress_filters' => true,
    ] );

    foreach ( $active_enrollments as $record_id ) {
        $author_id    = (int) get_post_field( 'post_author', $record_id );
        $student_user = get_userdata( $author_id );
        if ( $student_user ) {
            $student_members[] = $student_user;
        }
    }

    if ( empty( $student_members ) ) {
        printf(
            '<p>' . esc_html__( 'No students are actively enrolled in this group. Memberships are managed on the %s.', 'dancestudio-app' ) . '</p>',
            '<a href="' . esc_url( get_edit_post_link( $group_id ) ) . '">' . esc_html__( 'Group Edit page', 'dancestudio-app' ) . '</a>'
        );
        return;
    }

    $saved_attendance_data = get_post_meta( $post->ID, '_dsa_class_attendance', true );
    if ( ! is_array( $saved_attendance_data ) ) {
        $saved_attendance_data = [];
    }

    $attended_count = 0;
    ?>
    <table class="form-table dsa-attendance-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Student', 'dancestudio-app' ); ?></th>
                <th style="text-align:center;"><?php esc_html_e( 'Attended', 'dancestudio-app' ); ?></th>
                <th><?php esc_html_e( 'Remarks', 'dancestudio-app' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $student_members as $student_user ) :
            $sid       = $student_user->ID;
            $sfn       = trim( $student_user->first_name . ' ' . $student_user->last_name ) ?: $student_user->display_name;
            $attended  = ! empty( $saved_attendance_data[ $sid ]['attended'] ) && $saved_attendance_data[ $sid ]['attended'] === '1';
            if ( $attended ) {
                $attended_count++;
            }
            ?>
            <tr>
                <td><?php echo esc_html( $sfn ); ?></td>
                <td style="text-align:center;">
                    <input type="checkbox"
                           name="dsa_attendance[<?php echo esc_attr( $sid ); ?>][attended]"
                           value="1" <?php checked( $attended ); ?> />
                </td>
                <td>
                    <input type="text"
                           name="dsa_attendance[<?php echo esc_attr( $sid ); ?>][remarks]"
                           value="<?php echo esc_attr( $saved_attendance_data[ $sid ]['remarks'] ?? '' ); ?>"
                           class="widefat" />
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p><strong><?php echo esc_html( sprintf( __( 'Total attended: %d', 'dancestudio-app' ), $attended_count ) ); ?></strong></p>
    <?php
}

function dsa_save_class_meta( $post_id, $post ) {
    // Bail if it's an autosave or revision
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    // Check nonce
    if ( ! isset( $_POST['dsa_class_attendance_nonce'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dsa_class_attendance_nonce'] ) ), 'dsa_save_attendance_meta_action' ) ) {
        return;
    }
    // Capability check
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save attendance
    if ( isset( $_POST['dsa_attendance'] ) && is_array( $_POST['dsa_attendance'] ) ) {
        $attendance = [];
        foreach ( $_POST['dsa_attendance'] as $sid => $data ) {
            $attendance[ (int) $sid ] = [
                'attended' => ! empty( $data['attended'] ) && $data['attended'] === '1' ? '1' : '0',
                'remarks'  => isset( $data['remarks'] ) ? sanitize_text_field( $data['remarks'] ) : '',
            ];
        }
        update_post_meta( $post_id, '_dsa_class_attendance', $attendance );
    } else {
        delete_post_meta( $post_id, '_dsa_class_attendance' );
    }
}
add_action( 'save_post_dsa_group_class', 'dsa_save_class_meta', 10, 2 );
