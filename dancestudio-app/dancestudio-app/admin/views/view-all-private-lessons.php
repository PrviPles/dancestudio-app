<?php
if ( ! defined( 'WPINC' ) ) die;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class DSA_Private_Lessons_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Private Lesson', 'dancestudio-app' ),
            'plural'   => __( 'Private Lessons', 'dancestudio-app' ),
            'ajax'     => false
        ] );
    }

    public static function get_lessons( $per_page = 20, $page_number = 1 ) {
        $args = [
            'post_type'      => 'dsa_private_lesson',
            'posts_per_page' => $per_page,
            'paged'          => $page_number,
            'orderby'        => isset($_REQUEST['orderby']) ? sanitize_key($_REQUEST['orderby']) : 'meta_value',
            'meta_key'       => '_dsa_lesson_date', // Default sort by lesson date
            'order'          => isset($_REQUEST['order']) ? sanitize_key($_REQUEST['order']) : 'desc',
        ];

        // --- NEW LOGIC STARTS HERE ---
        // Check if the current user is a Studio Manager or Administrator.
        // If they are NOT, assume they are a Teacher and filter the query.
        if ( ! current_user_can( 'studio_manager' ) && ! current_user_can( 'manage_options' ) ) {
            $current_user_id = get_current_user_id();
            $args['meta_query'] = array(
                array(
                    'key'     => '_dsa_lesson_teacher_id',
                    'value'   => $current_user_id,
                    'compare' => '=',
                ),
            );
        }
        // --- NEW LOGIC ENDS HERE ---

        $query = new WP_Query($args);
        $items = [];
        if($query->have_posts()){
            while($query->have_posts()){
                $query->the_post();
                $items[] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'student1_id' => get_post_meta(get_the_ID(), '_dsa_lesson_student1_id', true),
                    'student2_id' => get_post_meta(get_the_ID(), '_dsa_lesson_student2_id', true),
                    'teacher_id' => get_post_meta(get_the_ID(), '_dsa_lesson_teacher_id', true),
                    'lesson_date' => get_post_meta(get_the_ID(), '_dsa_lesson_date', true),
                ];
            }
        }
        wp_reset_postdata();
        
        // We need a separate query for the total count because the main query is paginated.
        $count_query_args = $args;
        $count_query_args['posts_per_page'] = -1;
        $count_query_args['fields'] = 'ids';
        $count_query = new WP_Query($count_query_args);

        return ['items' => $items, 'total_count' => $count_query->post_count];
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'lesson_date':
                return $item[ $column_name ] ? date_i18n( get_option('date_format'), strtotime($item[ $column_name ]) ) : '—';
            case 'students':
                $s1_id = $item['student1_id'];
                $s2_id = $item['student2_id'];
                $s1_name = $s1_id ? get_the_author_meta('display_name', $s1_id) : '';
                $s2_name = $s2_id ? get_the_author_meta('display_name', $s2_id) : '';
                return esc_html($s1_name . ($s2_name ? ' & ' . $s2_name : ''));
            case 'teacher':
                 $t_id = $item['teacher_id'];
                 return $t_id ? esc_html(get_the_author_meta('display_name', $t_id)) : '—';
            default:
                return print_r( $item, true ); 
        }
    }
    
    function column_title($item) {
        $actions = array(
            'edit' => sprintf('<a href="%s">Edit</a>', get_edit_post_link($item['id'])),
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'Are you sure?\')">Delete</a>', get_delete_post_link($item['id'], '', true)),
        );
        return sprintf('<strong><a class="row-title" href="%s">%s</a></strong>%s', get_edit_post_link($item['id']), $item['title'], $this->row_actions($actions));
    }

    function column_cb($item) {
        return sprintf('<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']);
    }

    function get_columns() {
        $columns = [
            'cb'          => '<input type="checkbox" />',
            'title'       => __( 'Lesson Title', 'dancestudio-app' ),
            'students'    => __( 'Students / Couple', 'dancestudio-app' ), // Updated label
            'lesson_date' => __( 'Lesson Date', 'dancestudio-app' ),
        ];
        // Only show the 'Teacher' column to managers/admins
        if ( current_user_can( 'studio_manager' ) || current_user_can( 'manage_options' ) ) {
            $columns['teacher'] = __( 'Teacher', 'dancestudio-app' );
        }
        return $columns;
    }
    
    public function get_sortable_columns() {
		return ['title' => ['title', false], 'lesson_date' => ['lesson_date', true]]; // Made date the default sort
	}

    public function prepare_items() {
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $data = self::get_lessons( $per_page, $current_page );
        $this->set_pagination_args( [
            'total_items' => $data['total_count'],
            'per_page'    => $per_page
        ] );
        $this->items = $data['items'];
    }
}

/**
 * Renders the page that contains the Private Lessons list table.
 */
function dsa_render_private_lessons_page() {
    $lessons_table = new DSA_Private_Lessons_List_Table();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php 
            // Change the title based on the user's role
            if ( current_user_can( 'studio_manager' ) || current_user_can( 'manage_options' ) ) {
                _e('All Private Lessons', 'dancestudio-app');
            } else {
                _e('My Private Lessons', 'dancestudio-app');
            }
        ?></h1>
        <a href="<?php echo admin_url('post-new.php?post_type=dsa_private_lesson'); ?>" class="page-title-action"><?php _e('Add New', 'dancestudio-app');?></a>
        <hr class="wp-header-end">
        <form method="post">
            <?php
            $lessons_table->prepare_items();
            $lessons_table->display();
            ?>
        </form>
    </div>
    <?php
}