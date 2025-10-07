<?php
/**
 * AJAX Handlers for generic data fetching and searching.
 */
if ( ! defined( 'WPINC' ) ) { die; }

/**
 * Fetches data for populating dropdowns in the calendar modals.
 * Caches the results for 1 hour to improve performance.
 */
add_action( 'wp_ajax_dsa_get_modal_dropdown_data', 'dsa_get_modal_dropdown_data_handler' );
if ( ! function_exists( 'dsa_get_modal_dropdown_data_handler' ) ) {
    function dsa_get_modal_dropdown_data_handler() {
        check_ajax_referer( 'dsa_get_modal_dropdown_data_nonce', 'nonce' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( ['message' => 'You do not have permission to view this data.'], 403 );
        }

        // Try to get the data from our cache (transient) first.
        $cached_data = get_transient('dsa_modal_dropdowns_cache');
        if ( false !== $cached_data ) {
            wp_send_json_success( $cached_data );
            return;
        }

        $response_data = [
            'groups'   => [],
            'teachers' => [],
        ];

        // Fetch all Dance Groups
        $groups_query = new WP_Query([
            'post_type'      => 'dsa_group',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        if ( $groups_query->have_posts() ) {
            while ( $groups_query->have_posts() ) {
                $groups_query->the_post();
                $response_data['groups'][] = [
                    'id'   => (string) get_the_ID(),
                    'text' => get_the_title(),
                ];
            }
        }
        wp_reset_postdata();

        // Fetch all Teachers/Staff
        $teachers = get_users([ 'role__in' => ['teacher', 'studio_manager', 'administrator'], 'orderby' => 'display_name', 'order' => 'ASC' ]);
        foreach ( $teachers as $teacher ) {
            $response_data['teachers'][] = [
                'id'   => (string) $teacher->ID,
                'text' => $teacher->display_name,
            ];
        }
        
        // Save the freshly queried data into our cache for 1 hour.
        set_transient('dsa_modal_dropdowns_cache', $response_data, HOUR_IN_SECONDS);
        
        wp_send_json_success( $response_data );
    }
}


/**
 * Handler to fetch choreographies assigned to a group.
 */
add_action('wp_ajax_dsa_get_assigned_choreographies', 'dsa_get_assigned_choreographies_handler');
function dsa_get_assigned_choreographies_handler() {
    check_ajax_referer( 'dsa_get_assigned_choreos_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }

    $group_id = isset($_POST['group_id']) ? absint($_POST['group_id']) : 0;
    if ( $group_id === 0 ) {
        wp_send_json_error(['message' => 'No group selected.']);
    }

    $all_choreos_query = new WP_Query([
        'post_type' => 'dsa_choreography',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);

    $choreos_for_group = [];
    if ($all_choreos_query->have_posts()) {
        while ($all_choreos_query->have_posts()) {
            $all_choreos_query->the_post();
            
            $assigned_group_ids = get_post_meta(get_the_ID(), '_dsa_assigned_group_ids', true);

            if ( is_array($assigned_group_ids) && in_array($group_id, $assigned_group_ids) ) {
                $choreos_for_group[] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title()
                ];
            }
        }
        wp_reset_postdata();
    }

    wp_send_json_success($choreos_for_group);
}

/**
 * AJAX handler to fetch dance figures for a specific dance taxonomy term
 */
add_action( 'wp_ajax_dsa_get_figures_for_dance', 'dsa_get_figures_for_dance_handler' );
function dsa_get_figures_for_dance_handler() {
    check_ajax_referer( 'dsa_get_figures_nonce', 'nonce' );

    $dance_id = isset( $_POST['dance_id'] ) ? absint( $_POST['dance_id'] ) : 0;
    $post_id  = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

    if ( ! $dance_id || ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Invalid request.' );
    }

    $args = [
        'post_type'      => 'dsa_dance_figure',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => [
            [
                'taxonomy' => 'dsa_dance',
                'field'    => 'term_id',
                'terms'    => $dance_id,
            ],
        ],
    ];

    $figures_query = new WP_Query( $args );
    $figures_data = [];

    $practiced_figure_ids = get_post_meta( $post_id, '_dsa_practiced_figure_ids', true );
    if ( ! is_array($practiced_figure_ids) ) {
        $practiced_figure_ids = [];
    }

    if ( $figures_query->have_posts() ) {
        while ( $figures_query->have_posts() ) {
            $figures_query->the_post();
            $figures_data[] = [
                'id'        => get_the_ID(),
                'title'     => get_the_title(),
                'practiced' => in_array( get_the_ID(), $practiced_figure_ids ),
            ];
        }
    }
    wp_reset_postdata();

    wp_send_json_success( $figures_data );
}

/**
 * AJAX handler to fetch all dance taxonomy terms.
 */
add_action( 'wp_ajax_dsa_get_all_dances_taxonomy', 'dsa_get_all_dances_taxonomy_handler' );
function dsa_get_all_dances_taxonomy_handler() {
    check_ajax_referer( 'dsa_get_figures_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Permission denied.' );
    }

    $dance_terms = get_terms([
        'taxonomy'   => 'dsa_dance',
        'hide_empty' => false,
        'orderby'    => 'name',
    ]);

    if ( is_wp_error($dance_terms) ) {
        wp_send_json_error( 'Could not fetch dances.' );
    }

    $dances = [];
    foreach ($dance_terms as $term) {
        $dances[] = [
            'id'   => $term->term_id,
            'name' => $term->name,
        ];
    }

    wp_send_json_success($dances);
}


/**
 * Handler for the Select2 student search dropdown.
 */
add_action( 'wp_ajax_dsa_search_students', 'dsa_search_students_handler' );
function dsa_search_students_handler() {
    check_ajax_referer( 'dsa_search_students_nonce_action', 'nonce' );
    
    $search_term = isset( $_REQUEST['term'] ) ? sanitize_text_field( $_REQUEST['term'] ) : '';

    if ( empty( $search_term ) ) {
        wp_send_json_success( ['results' => []] );
    }

    $user_query = new WP_User_Query( [
        'role__in'       => [ 'student', 'subscriber' ],
        'search'         => '*' . esc_attr( $search_term ) . '*',
        'search_columns' => [ 'user_login', 'user_email', 'display_name' ],
        'number'         => 20,
    ] );
    
    $users_found = $user_query->get_results();
    $items = [];
    
    foreach ( $users_found as $user ) {
        $items[] = [
            'id'   => $user->ID,
            'text' => $user->display_name,
        ];
    }
    
    wp_send_json_success( ['results' => $items] );
}


/**
 * Handler to fetch a student's WooCommerce orders.
 */
add_action( 'wp_ajax_dsa_get_student_orders', 'dsa_get_student_orders_handler' );
if( ! function_exists('dsa_get_student_orders_handler') ) { 
    function dsa_get_student_orders_handler() { 
        check_ajax_referer( 'dsa_get_student_orders_nonce', 'nonce' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( ['message' => 'You do not have permission to view this data.'], 403 );
        }

        $student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
        if ( $student_id === 0 ) {
            wp_send_json_error( ['message' => 'Invalid student ID.'], 400 );
        }

        $orders_query = new WC_Order_Query( [
            'customer_id' => $student_id,
            'status'      => 'completed',
            'limit'       => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ] );
        $orders = $orders_query->get_orders();
        
        $available_packages = [];
        if ( empty( $orders ) ) {
            wp_send_json_success( ['orders' => []] );
            return;
        }

        foreach ( $orders as $order ) {
            foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();
                if ( ! $product ) { continue; }

                $total_lessons = (int) $product->get_meta( '_dsa_lessons_in_package' );
                if ( $total_lessons > 0 ) {
                    $used_lessons_query = new WP_Query( [
                        'post_type'      => 'dsa_private_lesson',
                        'posts_per_page' => -1,
                        'post_status'    => 'publish',
                        'fields'         => 'ids',
                        'meta_query'     => [ [ 'key' => '_dsa_lesson_order_id', 'value' => $order->get_id() ] ],
                    ] );
                    $used_lessons_count = $used_lessons_query->post_count;

                    if ( $used_lessons_count < $total_lessons ) {
                        $available_packages[] = [
                            'id'   => $order->get_id(),
                            'text' => sprintf( 'Order #%d - %s (%d/%d used)', $order->get_id(), $product->get_name(), $used_lessons_count, $total_lessons ),
                        ];
                    }
                }
            }
        }
        wp_send_json_success( ['orders' => $available_packages] );
    } 
}