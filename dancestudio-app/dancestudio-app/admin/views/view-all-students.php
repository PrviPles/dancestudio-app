<?php
/**
 * View: All Students List
 * Renders the dedicated page for listing all students using WP_List_Table.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) die;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Creates the sortable, filterable, and paginated list table for students.
 */
class DSA_Students_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Student', 'dancestudio-app' ),
            'plural'   => __( 'Students', 'dancestudio-app' ),
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        return [
            'cb'               => '<input type="checkbox" />',
            'name'             => __( 'Name', 'dancestudio-app' ),
            'email'            => __( 'Email', 'dancestudio-app' ),
            'dsa_dance_groups' => __( 'Enrolled In Group(s)', 'dancestudio-app' ),
        ];
    }

    public function get_sortable_columns() {
        return [
            'name'  => ['display_name', true], // true means it's the default sort
            'email' => ['user_email', false],
        ];
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'email':
                return '<a href="mailto:' . esc_attr($item->user_email) . '">' . esc_html($item->user_email) . '</a>';
            case 'dsa_dance_groups':
                $enrollment_records = get_posts(['post_type' => 'dsa_enroll_record', 'post_status' => 'publish', 'author' => $item->ID, 'posts_per_page' => -1]);
                if (empty($enrollment_records)) return '—';
                $group_names = array_map(function($record) { return get_the_title($record->post_parent); }, $enrollment_records);
                return esc_html(implode(', ', $group_names));
            default:
                return '---';
        }
    }

    public function column_name( $item ) {
        $edit_link = get_edit_user_link( $item->ID );
        $delete_link = wp_nonce_url( admin_url('users.php?action=delete&user=' . $item->ID), 'delete-user_' . $item->ID );
        
        $actions = [
            'edit' => '<a href="' . esc_url($edit_link) . '">' . __('Edit') . '</a>',
            'delete' => '<a href="' . esc_url($delete_link) . '" class="submitdelete">' . __('Delete') . '</a>',
        ];

        return get_avatar($item->ID, 32) . ' <strong><a class="row-title" href="' . esc_url($edit_link) . '">' . esc_html($item->display_name) . '</a></strong>' . $this->row_actions($actions);
    }
    
    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="users[]" value="%s" />', $item->ID );
    }

    public function prepare_items() {
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];

        $per_page = $this->get_items_per_page('students_per_page', 20);
        $current_page = $this->get_pagenum();

        $args = [
            'role__in' => ['student', 'subscriber'],
            'number'   => $per_page,
            'paged'    => $current_page,
            'orderby'  => isset($_REQUEST['orderby']) ? sanitize_key($_REQUEST['orderby']) : 'display_name',
            'order'    => isset($_REQUEST['order']) ? sanitize_key($_REQUEST['order']) : 'ASC',
            'count_total' => true,
        ];

        $group_filter_id = isset($_REQUEST['group_filter']) ? absint($_REQUEST['group_filter']) : 0;
        if ( $group_filter_id > 0 ) {
            $enrollments = get_posts([
                'post_type' => 'dsa_enroll_record',
                'post_status' => 'publish',
                'post_parent' => $group_filter_id,
                'fields' => 'post_author', // get_posts returns an array of post_author values
            ]);
            // Ensure we have a non-empty array of IDs to query, otherwise it returns all users
            $args['include'] = !empty($enrollments) ? array_unique($enrollments) : [0];
        }

        $user_query = new WP_User_Query($args);
        $this->items = $user_query->get_results();
        
        $this->set_pagination_args([
            'total_items' => $user_query->get_total(),
            'per_page'    => $per_page
        ]);
    }
}


/**
 * Renders the page that contains the Students list table.
 */
function dsa_render_all_students_page() {
    $students_table = new DSA_Students_List_Table();
    ?>
    <div class="wrap dsa-all-students-page">
        <h1 class="wp-heading-inline"><?php esc_html_e('All Students', 'dancestudio-app'); ?></h1>
        <a href="<?php echo esc_url(admin_url('user-new.php')); ?>" class="page-title-action"><?php esc_html_e('Add New Student', 'dancestudio-app'); ?></a>
        <hr class="wp-header-end">
        
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            <?php
            $students_table->prepare_items();
            ?>
            <div class="tablenav top">
                <div class="alignleft actions">
                    <?php
                    $all_groups = get_posts(['post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                    if ($all_groups) {
                        echo '<label for="filter-by-group" class="screen-reader-text">' . __('Filter by group') . '</label>';
                        echo '<select name="group_filter" id="filter-by-group">';
                        echo '<option value="0">' . __('All groups') . '</option>';
                        $current_filter = isset($_REQUEST['group_filter']) ? absint($_REQUEST['group_filter']) : 0;
                        foreach ($all_groups as $group) {
                            echo '<option value="' . esc_attr($group->ID) . '"' . selected($current_filter, $group->ID, false) . '>' . esc_html($group->post_title) . '</option>';
                        }
                        echo '</select>';
                        submit_button(__('Filter'), '', 'action', false, ['id' => 'post-query-submit']);
                    }
                    ?>
                </div>
                <?php $students_table->pagination('top'); ?>
            </div>
            <?php $students_table->display(); ?>
            <div class="tablenav bottom">
                 <?php $students_table->pagination('bottom'); ?>
            </div>
        </form>
    </div>
    <?php
}