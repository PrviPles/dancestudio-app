<?php
/**
 * View Part: Renders the list of all students.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

if ( ! function_exists( 'dsa_render_all_students_list_page' ) ) {
    function dsa_render_all_students_list_page() {
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'last_name';
        $order   = ( isset( $_GET['order'] ) && in_array( strtolower( $_GET['order'] ), ['asc', 'desc'] ) ) ? strtoupper( $_GET['order'] ) : 'ASC';
        $group_filter_id = isset( $_GET['group_filter'] ) ? absint( $_GET['group_filter'] ) : 0;

        $query_args = [
            'role__in' => ['student', 'subscriber'],
            'orderby'  => $orderby,
            'order'    => $order,
        ];

        if ( $group_filter_id > 0 ) {
            $enrollment_records = get_posts([
                'post_type' => 'dsa_enroll_record', 'post_status' => 'publish',
                'post_parent' => $group_filter_id, 'posts_per_page' => -1,
            ]);
            $enrolled_student_ids = wp_list_pluck( $enrollment_records, 'post_author' );
            $query_args['include'] = ! empty( $enrolled_student_ids ) ? array_unique($enrolled_student_ids) : [0];
        }
        
        $students_query = new WP_User_Query($query_args);
        $students = $students_query->get_results();
        
        // --- PERFORMANCE OPTIMIZATION START ---
        // 1. Get all student IDs from the current page's results.
        $student_ids_on_page = wp_list_pluck( $students, 'ID' );
        $enrollments_by_student = [];

        if ( ! empty( $student_ids_on_page ) ) {
            // 2. Run ONE query to get all enrollment records for ALL students on this page.
            $all_enrollment_records = get_posts([
                'post_type'      => 'dsa_enroll_record',
                'post_status'    => 'publish',
                'author__in'     => $student_ids_on_page,
                'posts_per_page' => -1,
            ]);

            // 3. Process the results into a lookup array for easy access inside the loop.
            foreach ( $all_enrollment_records as $record ) {
                $student_id = $record->post_author;
                if ( ! isset( $enrollments_by_student[$student_id] ) ) {
                    $enrollments_by_student[$student_id] = [];
                }
                $enrollments_by_student[$student_id][] = get_the_title( $record->post_parent );
            }
        }
        // --- PERFORMANCE OPTIMIZATION END ---
        ?>
        <h3><?php esc_html_e( 'All Students', 'dancestudio-app' ); ?></h3>
        <p><?php esc_html_e( 'A complete list of all students in your studio. Click on a student\'s name to view their full profile.', 'dancestudio-app' ); ?></p>

        <form method="get" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="dsa-students-tab">
            <div class="alignleft actions">
                <select name="group_filter" id="group_filter">
                    <option value="0"><?php _e('All groups'); ?></option>
                    <?php
                    $all_groups = get_posts(['post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                    if ($all_groups) {
                        foreach ($all_groups as $group) {
                            echo '<option value="' . esc_attr($group->ID) . '"' . selected($group_filter_id, $group->ID, false) . '>' . esc_html($group->post_title) . '</option>';
                        }
                    }
                    ?>
                </select>
                <input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?php esc_attr_e('Filter'); ?>">
                <a href="<?php echo esc_url( admin_url('user-new.php') ); ?>" class="button button-primary"><?php esc_html_e('Add New Student', 'dancestudio-app'); ?></a>
            </div>
        </form>

        <table class="wp-list-table widefat fixed striped users">
              <thead>
                <tr>
                    <?php
                    if (function_exists('dsa_render_sortable_table_header')) {
                        dsa_render_sortable_table_header( __('First Name', 'dancestudio-app'), 'first_name', $orderby, $order );
                        dsa_render_sortable_table_header( __('Last Name', 'dancestudio-app'), 'last_name', $orderby, $order );
                    }
                    ?>
                    <th scope="col"><?php esc_html_e('Enrolled In Group(s)', 'dancestudio-app'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'dancestudio-app'); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if ( ! empty( $students ) ) :
                    foreach ( $students as $student ) :
                        $profile_link = admin_url('admin.php?page=dsa-students-tab&action=view_profile&student_id=' . $student->ID);
                        // --- OPTIMIZATION: Use the pre-fetched array instead of a new query ---
                        $enrolled_groups = $enrollments_by_student[$student->ID] ?? [];
                        ?>
                        <tr>
                            <td><strong><a href="<?php echo esc_url($profile_link); ?>"><?php echo esc_html($student->first_name); ?></a></strong></td>
                            <td><strong><a href="<?php echo esc_url($profile_link); ?>"><?php echo esc_html($student->last_name); ?></a></strong></td>
                            <td><?php echo !empty($enrolled_groups) ? esc_html(implode(', ', $enrolled_groups)) : '—'; ?></td>
                            <td>
                                <a href="<?php echo esc_url($profile_link); ?>" class="button button-secondary"><?php esc_html_e('View Profile', 'dancestudio-app'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr class="no-items"><td class="colspanchange" colspan="4"><?php esc_html_e('No students found.', 'dancestudio-app'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}<?php
/**
 * View Part: Renders the list of all students.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

if ( ! function_exists( 'dsa_render_all_students_list_page' ) ) {
    function dsa_render_all_students_list_page() {
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'last_name';
        $order   = ( isset( $_GET['order'] ) && in_array( strtolower( $_GET['order'] ), ['asc', 'desc'] ) ) ? strtoupper( $_GET['order'] ) : 'ASC';
        $group_filter_id = isset( $_GET['group_filter'] ) ? absint( $_GET['group_filter'] ) : 0;

        $query_args = [
            'role__in' => ['student', 'subscriber'],
            'orderby'  => $orderby,
            'order'    => $order,
        ];

        if ( $group_filter_id > 0 ) {
            $enrollment_records = get_posts([
                'post_type' => 'dsa_enroll_record', 'post_status' => 'publish',
                'post_parent' => $group_filter_id, 'posts_per_page' => -1,
            ]);
            $enrolled_student_ids = wp_list_pluck( $enrollment_records, 'post_author' );
            $query_args['include'] = ! empty( $enrolled_student_ids ) ? array_unique($enrolled_student_ids) : [0];
        }
        
        $students_query = new WP_User_Query($query_args);
        $students = $students_query->get_results();
        
        // --- PERFORMANCE OPTIMIZATION START ---
        // 1. Get all student IDs from the current page's results.
        $student_ids_on_page = wp_list_pluck( $students, 'ID' );
        $enrollments_by_student = [];

        if ( ! empty( $student_ids_on_page ) ) {
            // 2. Run ONE query to get all enrollment records for ALL students on this page.
            $all_enrollment_records = get_posts([
                'post_type'      => 'dsa_enroll_record',
                'post_status'    => 'publish',
                'author__in'     => $student_ids_on_page,
                'posts_per_page' => -1,
            ]);

            // 3. Process the results into a lookup array for easy access inside the loop.
            foreach ( $all_enrollment_records as $record ) {
                $student_id = $record->post_author;
                if ( ! isset( $enrollments_by_student[$student_id] ) ) {
                    $enrollments_by_student[$student_id] = [];
                }
                $enrollments_by_student[$student_id][] = get_the_title( $record->post_parent );
            }
        }
        // --- PERFORMANCE OPTIMIZATION END ---
        ?>
        <h3><?php esc_html_e( 'All Students', 'dancestudio-app' ); ?></h3>
        <p><?php esc_html_e( 'A complete list of all students in your studio. Click on a student\'s name to view their full profile.', 'dancestudio-app' ); ?></p>

        <form method="get" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="dsa-students-tab">
            <div class="alignleft actions">
                <select name="group_filter" id="group_filter">
                    <option value="0"><?php _e('All groups'); ?></option>
                    <?php
                    $all_groups = get_posts(['post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                    if ($all_groups) {
                        foreach ($all_groups as $group) {
                            echo '<option value="' . esc_attr($group->ID) . '"' . selected($group_filter_id, $group->ID, false) . '>' . esc_html($group->post_title) . '</option>';
                        }
                    }
                    ?>
                </select>
                <input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?php esc_attr_e('Filter'); ?>">
                <a href="<?php echo esc_url( admin_url('user-new.php') ); ?>" class="button button-primary"><?php esc_html_e('Add New Student', 'dancestudio-app'); ?></a>
            </div>
        </form>

        <table class="wp-list-table widefat fixed striped users">
              <thead>
                <tr>
                    <?php
                    if (function_exists('dsa_render_sortable_table_header')) {
                        dsa_render_sortable_table_header( __('First Name', 'dancestudio-app'), 'first_name', $orderby, $order );
                        dsa_render_sortable_table_header( __('Last Name', 'dancestudio-app'), 'last_name', $orderby, $order );
                    }
                    ?>
                    <th scope="col"><?php esc_html_e('Enrolled In Group(s)', 'dancestudio-app'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'dancestudio-app'); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if ( ! empty( $students ) ) :
                    foreach ( $students as $student ) :
                        $profile_link = admin_url('admin.php?page=dsa-students-tab&action=view_profile&student_id=' . $student->ID);
                        // --- OPTIMIZATION: Use the pre-fetched array instead of a new query ---
                        $enrolled_groups = $enrollments_by_student[$student->ID] ?? [];
                        ?>
                        <tr>
                            <td><strong><a href="<?php echo esc_url($profile_link); ?>"><?php echo esc_html($student->first_name); ?></a></strong></td>
                            <td><strong><a href="<?php echo esc_url($profile_link); ?>"><?php echo esc_html($student->last_name); ?></a></strong></td>
                            <td><?php echo !empty($enrolled_groups) ? esc_html(implode(', ', $enrolled_groups)) : '—'; ?></td>
                            <td>
                                <a href="<?php echo esc_url($profile_link); ?>" class="button button-secondary"><?php esc_html_e('View Profile', 'dancestudio-app'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr class="no-items"><td class="colspanchange" colspan="4"><?php esc_html_e('No students found.', 'dancestudio-app'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}<?php
/**
 * View Part: Renders the list of all students.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

if ( ! function_exists( 'dsa_render_all_students_list_page' ) ) {
    function dsa_render_all_students_list_page() {
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'last_name';
        $order   = ( isset( $_GET['order'] ) && in_array( strtolower( $_GET['order'] ), ['asc', 'desc'] ) ) ? strtoupper( $_GET['order'] ) : 'ASC';
        $group_filter_id = isset( $_GET['group_filter'] ) ? absint( $_GET['group_filter'] ) : 0;

        $query_args = [
            'role__in' => ['student', 'subscriber'],
            'orderby'  => $orderby,
            'order'    => $order,
        ];

        if ( $group_filter_id > 0 ) {
            $enrollment_records = get_posts([
                'post_type' => 'dsa_enroll_record', 'post_status' => 'publish',
                'post_parent' => $group_filter_id, 'posts_per_page' => -1,
            ]);
            $enrolled_student_ids = wp_list_pluck( $enrollment_records, 'post_author' );
            $query_args['include'] = ! empty( $enrolled_student_ids ) ? array_unique($enrolled_student_ids) : [0];
        }
        
        $students_query = new WP_User_Query($query_args);
        $students = $students_query->get_results();
        
        // --- PERFORMANCE OPTIMIZATION START ---
        // 1. Get all student IDs from the current page's results.
        $student_ids_on_page = wp_list_pluck( $students, 'ID' );
        $enrollments_by_student = [];

        if ( ! empty( $student_ids_on_page ) ) {
            // 2. Run ONE query to get all enrollment records for ALL students on this page.
            $all_enrollment_records = get_posts([
                'post_type'      => 'dsa_enroll_record',
                'post_status'    => 'publish',
                'author__in'     => $student_ids_on_page,
                'posts_per_page' => -1,
            ]);

            // 3. Process the results into a lookup array for easy access inside the loop.
            foreach ( $all_enrollment_records as $record ) {
                $student_id = $record->post_author;
                if ( ! isset( $enrollments_by_student[$student_id] ) ) {
                    $enrollments_by_student[$student_id] = [];
                }
                $enrollments_by_student[$student_id][] = get_the_title( $record->post_parent );
            }
        }
        // --- PERFORMANCE OPTIMIZATION END ---
        ?>
        <h3><?php esc_html_e( 'All Students', 'dancestudio-app' ); ?></h3>
        <p><?php esc_html_e( 'A complete list of all students in your studio. Click on a student\'s name to view their full profile.', 'dancestudio-app' ); ?></p>

        <form method="get" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="dsa-students-tab">
            <div class="alignleft actions">
                <select name="group_filter" id="group_filter">
                    <option value="0"><?php _e('All groups'); ?></option>
                    <?php
                    $all_groups = get_posts(['post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                    if ($all_groups) {
                        foreach ($all_groups as $group) {
                            echo '<option value="' . esc_attr($group->ID) . '"' . selected($group_filter_id, $group->ID, false) . '>' . esc_html($group->post_title) . '</option>';
                        }
                    }
                    ?>
                </select>
                <input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?php esc_attr_e('Filter'); ?>">
                <a href="<?php echo esc_url( admin_url('user-new.php') ); ?>" class="button button-primary"><?php esc_html_e('Add New Student', 'dancestudio-app'); ?></a>
            </div>
        </form>

        <table class="wp-list-table widefat fixed striped users">
              <thead>
                <tr>
                    <?php
                    if (function_exists('dsa_render_sortable_table_header')) {
                        dsa_render_sortable_table_header( __('First Name', 'dancestudio-app'), 'first_name', $orderby, $order );
                        dsa_render_sortable_table_header( __('Last Name', 'dancestudio-app'), 'last_name', $orderby, $order );
                    }
                    ?>
                    <th scope="col"><?php esc_html_e('Enrolled In Group(s)', 'dancestudio-app'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'dancestudio-app'); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if ( ! empty( $students ) ) :
                    foreach ( $students as $student ) :
                        $profile_link = admin_url('admin.php?page=dsa-students-tab&action=view_profile&student_id=' . $student->ID);
                        // --- OPTIMIZATION: Use the pre-fetched array instead of a new query ---
                        $enrolled_groups = $enrollments_by_student[$student->ID] ?? [];
                        ?>
                        <tr>
                            <td><strong><a href="<?php echo esc_url($profile_link); ?>"><?php echo esc_html($student->first_name); ?></a></strong></td>
                            <td><strong><a href="<?php echo esc_url($profile_link); ?>"><?php echo esc_html($student->last_name); ?></a></strong></td>
                            <td><?php echo !empty($enrolled_groups) ? esc_html(implode(', ', $enrolled_groups)) : '—'; ?></td>
                            <td>
                                <a href="<?php echo esc_url($profile_link); ?>" class="button button-secondary"><?php esc_html_e('View Profile', 'dancestudio-app'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr class="no-items"><td class="colspanchange" colspan="4"><?php esc_html_e('No students found.', 'dancestudio-app'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}<?php
/**
 * View Part: Renders the list of all students.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

if ( ! function_exists( 'dsa_render_all_students_list_page' ) ) {
    function dsa_render_all_students_list_page() {
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'last_name';
        $order   = ( isset( $_GET['order'] ) && in_array( strtolower( $_GET['order'] ), ['asc', 'desc'] ) ) ? strtoupper( $_GET['order'] ) : 'ASC';
        $group_filter_id = isset( $_GET['group_filter'] ) ? absint( $_GET['group_filter'] ) : 0;

        $query_args = [
            'role__in' => ['student', 'subscriber'],
            'orderby'  => $orderby,
            'order'    => $order,
        ];

        if ( $group_filter_id > 0 ) {
            $enrollment_records = get_posts([
                'post_type' => 'dsa_enroll_record', 'post_status' => 'publish',
                'post_parent' => $group_filter_id, 'posts_per_page' => -1,
            ]);
            $enrolled_student_ids = wp_list_pluck( $enrollment_records, 'post_author' );
            $query_args['include'] = ! empty( $enrolled_student_ids ) ? array_unique($enrolled_student_ids) : [0];
        }
        
        $students_query = new WP_User_Query($query_args);
        $students = $students_query->get_results();
        
        // --- PERFORMANCE OPTIMIZATION START ---
        // 1. Get all student IDs from the current page's results.
        $student_ids_on_page = wp_list_pluck( $students, 'ID' );
        $enrollments_by_student = [];

        if ( ! empty( $student_ids_on_page ) ) {
            // 2. Run ONE query to get all enrollment records for ALL students on this page.
            $all_enrollment_records = get_posts([
                'post_type'      => 'dsa_enroll_record',
                'post_status'    => 'publish',
                'author__in'     => $student_ids_on_page,
                'posts_per_page' => -1,
            ]);

            // 3. Process the results into a lookup array for easy access inside the loop.
            foreach ( $all_enrollment_records as $record ) {
                $student_id = $record->post_author;
                if ( ! isset( $enrollments_by_student[$student_id] ) ) {
                    $enrollments_by_student[$student_id] = [];
                }
                $enrollments_by_student[$student_id][] = get_the_title( $record->post_parent );
            }
        }
        // --- PERFORMANCE OPTIMIZATION END ---
        ?>
        <h3><?php esc_html_e( 'All Students', 'dancestudio-app' ); ?></h3>
        <p><?php esc_html_e( 'A complete list of all students in your studio. Click on a student\'s name to view their full profile.', 'dancestudio-app' ); ?></p>

        <form method="get" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="dsa-students-tab">
            <div class="alignleft actions">
                <select name="group_filter" id="group_filter">
                    <option value="0"><?php _e('All groups'); ?></option>
                    <?php
                    $all_groups = get_posts(['post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                    if ($all_groups) {
                        foreach ($all_groups as $group) {
                            echo '<option value="' . esc_attr($group->ID) . '"' . selected($group_filter_id, $group->ID, false) . '>' . esc_html($group->post_title) . '</option>';
                        }
                    }
                    ?>
                </select>
                <input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?php esc_attr_e('Filter'); ?>">
                <a href="<?php echo esc_url( admin_url('user-new.php') ); ?>" class="button button-primary"><?php esc_html_e('Add New Student', 'dancestudio-app'); ?></a>
            </div>
        </form>

        <table class="wp-list-table widefat fixed striped users">
              <thead>
                <tr>
                    <?php
                    if (function_exists('dsa_render_sortable_table_header')) {
                        dsa_render_sortable_table_header( __('First Name', 'dancestudio-app'), 'first_name', $orderby, $order );
                        dsa_render_sortable_table_header( __('Last Name', 'dancestudio-app'), 'last_name', $orderby, $order );
                    }
                    ?>
                    <th scope="col"><?php esc_html_e('Enrolled In Group(s)', 'dancestudio-app'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'dancestudio-app'); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if ( ! empty( $students ) ) :
                    foreach ( $students as $student ) :
                        $profile_link = admin_url('admin.php?page=dsa-students-tab&action=view_profile&student_id=' . $student->ID);
                        // --- OPTIMIZATION: Use the pre-fetched array instead of a new query ---
                        $enrolled_groups = $enrollments_by_student[$student->ID] ?? [];
                        ?>
                        <tr>
                            <td><strong><a href="<?php echo esc_url($profile_link); ?>"><?php echo esc_html($student->first_name); ?></a></strong></td>
                            <td><strong><a href="<?php echo esc_url($profile_link); ?>"><?php echo esc_html($student->last_name); ?></a></strong></td>
                            <td><?php echo !empty($enrolled_groups) ? esc_html(implode(', ', $enrolled_groups)) : '—'; ?></td>
                            <td>
                                <a href="<?php echo esc_url($profile_link); ?>" class="button button-secondary"><?php esc_html_e('View Profile', 'dancestudio-app'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr class="no-items"><td class="colspanchange" colspan="4"><?php esc_html_e('No students found.', 'dancestudio-app'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}<?php
/**
 * View Part: Renders the list of all students.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

if ( ! function_exists( 'dsa_render_all_students_list_page' ) ) {
    function dsa_render_all_students_list_page() {
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'last_name';
        $order   = ( isset( $_GET['order'] ) && in_array( strtolower( $_GET['order'] ), ['asc', 'desc'] ) ) ? strtoupper( $_GET['order'] ) : 'ASC';
        $group_filter_id = isset( $_GET['group_filter'] ) ? absint( $_GET['group_filter'] ) : 0;

        $query_args = [
            'role__in' => ['student', 'subscriber'],
            'orderby'  => $orderby,
            'order'    => $order,
        ];

        if ( $group_filter_id > 0 ) {
            $enrollment_records = get_posts([
                'post_type' => 'dsa_enroll_record', 'post_status' => 'publish',
                'post_parent' => $group_filter_id, 'posts_per_page' => -1,
            ]);
            $enrolled_student_ids = wp_list_pluck( $enrollment_records, 'post_author' );
            $query_args['include'] = ! empty( $enrolled_student_ids ) ? array_unique($enrolled_student_ids) : [0];
        }
        
        $students_query = new WP_User_Query($query_args);
        $students = $students_query->get_results();
        
        // --- PERFORMANCE OPTIMIZATION START ---
        // 1. Get all student IDs from the current page's results.
        $student_ids_on_page = wp_list_pluck( $students, 'ID' );
        $enrollments_by_student = [];

        if ( ! empty( $student_ids_on_page ) ) {
            // 2. Run ONE query to get all enrollment records for ALL students on this page.
            $all_enrollment_records = get_posts([
                'post_type'      => 'dsa_enroll_record',
                'post_status'    => 'publish',
                'author__in'     => $student_ids_on_page,
                'posts_per_page' => -1,
            ]);

            // 3. Process the results into a lookup array for easy access inside the loop.
            foreach ( $all_enrollment_records as $record ) {
                $student_id = $record->post_author;
                if ( ! isset( $enrollments_by_student[$student_id] ) ) {
                    $enrollments_by_student[$student_id] = [];
                }
                $enrollments_by_student[$student_id][] = get_the_title( $record->post_parent );
            }
        }
        // --- PERFORMANCE OPTIMIZATION END ---
        ?>
        <h3><?php esc_html_e( 'All Students', 'dancestudio-app' ); ?></h3>
        <p><?php esc_html_e( 'A complete list of all students in your studio. Click on a student\'s name to view their full profile.', 'dancestudio-app' ); ?></p>

        <form method="get" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="dsa-students-tab">
            <div class="alignleft actions">
                <select name="group_filter" id="group_filter">
                    <option value="0"><?php _e('All groups'); ?></option>
                    <?php
                    $all_groups = get_posts(['post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                    if ($all_groups) {
                        foreach ($all_groups as $group) {
                            echo '<option value="' . esc_attr($group->ID) . '"' . selected($group_filter_id, $group->ID, false) . '>' . esc_html($group->post_title) . '</option>';
                        }
                    }
                    ?>
                </select>
                <input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?php esc_attr_e('Filter'); ?>">
                <a href="<?php echo esc_url( admin_url('user-new.php') ); ?>" class="button button-primary"><?php esc_html_e('Add New Student', 'dancestudio-app'); ?></a>
            </div>
        </form>

        <table class="wp-list-table widefat fixed striped users">
              <thead>
                <tr>
                    <?php
                    if (function_exists('dsa_render_sortable_table_header')) {
                        dsa_render_sortable_table_header( __('First Name', 'dancestudio-app'), 'first_name', $orderby, $order );
                        dsa_render_sortable_table_header( __('Last Name', 'dancestudio-app'), 'last_name', $orderby, $order );
                    }
                    ?>
                    <th scope="col"><?php esc_html_e('Enrolled In Group(s)', 'dancestudio-app'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'dancestudio-app'); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if ( ! empty( $students ) ) :
                    foreach ( $students as $student ) :
                        $profile_link = admin_url('admin.php?page=dsa-students-tab&action=view_profile&student_id=' . $student->ID);
                        // --- OPTIMIZATION: Use the pre-fetched array instead of a new query ---
                        $enrolled_groups = $enrollments_by_student[$student->ID] ?? [];
                        ?>
                        <tr>
                            <td><strong><a href="<?php echo esc_url($profile_link); ?>"><?php echo esc_html($student->first_name); ?></a></strong></td>
                            <td><strong><a href="<?php echo esc_url($profile_link); ?>"><?php echo esc_html($student->last_name); ?></a></strong></td>
                            <td><?php echo !empty($enrolled_groups) ? esc_html(implode(', ', $enrolled_groups)) : '—'; ?></td>
                            <td>
                                <a href="<?php echo esc_url($profile_link); ?>" class="button button-secondary"><?php esc_html_e('View Profile', 'dancestudio-app'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr class="no-items"><td class="colspanchange" colspan="4"><?php esc_html_e('No students found.', 'dancestudio-app'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}