<?php
/**
 * AJAX Handlers for generic data fetching and searching.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Handles fetching WooCommerce orders for one or more students.
 */
add_action( 'wp_ajax_dsa_get_student_orders', 'dsa_get_student_orders_handler' );
if( ! function_exists('dsa_get_student_orders_handler') ) {
    function dsa_get_student_orders_handler() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dsa_get_student_orders_nonce' ) ) {
            wp_send_json_error( ['message' => 'Invalid security token.'], 401 );
        }

        $student_ids = isset( $_POST['student_id'] ) ? (array) $_POST['student_id'] : [];
        $sanitized_ids = array_map('absint', $student_ids);
        $sanitized_ids = array_filter($sanitized_ids);

        if ( empty($sanitized_ids) ) {
            wp_send_json_error(['message' => 'Invalid Student ID.'], 400);
        }

        $orders_query = new WC_Order_Query( array(
            'limit'    => -1,
            'customer' => $sanitized_ids,
            'status'   => array('wc-processing', 'wc-completed'),
            'orderby'  => 'date',
            'order'    => 'DESC',
        ) );

        $orders = $orders_query->get_orders();
        $results = [];

        if ( ! empty( $orders ) ) {
            foreach ( $orders as $order ) {
                foreach ( $order->get_items() as $item ) {
                    if ( has_term( 'lesson-packages', 'product_cat', $item->get_product_id() ) ) {
                        $results[] = [
                            'id'   => $order->get_id(),
                            'text' => '#' . $order->get_id() . ' - ' . $item->get_name(),
                        ];
                        break; 
                    }
                }
            }
        }
        wp_send_json_success( ['orders' => $results] );
    }
}

/**
 * Handles searching for dance names.
 */
add_action( 'wp_ajax_dsa_search_dances', 'dsa_search_dances_handler' );
if( ! function_exists('dsa_search_dances_handler') ) {
    function dsa_search_dances_handler() {
        // Nonce check can be added here for extra security if this becomes a sensitive search
        $search_term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
        $results = [];
        $dances = get_terms( array('taxonomy' => 'dsa_dance', 'name__like' => $search_term, 'hide_empty' => false, 'number' => 10));
        if ( ! empty( $dances ) && ! is_wp_error( $dances ) ) {
            foreach ( $dances as $dance ) { $results[] = array('id' => $dance->term_id, 'text' => $dance->name); }
        }
        wp_send_json_success( $results );
    }
}

/**
 * Handles searching for figure names.
 */
add_action( 'wp_ajax_dsa_search_figure_names', 'dsa_search_figure_names_handler' );
if( ! function_exists('dsa_search_figure_names_handler') ) {
    function dsa_search_figure_names_handler() {
        // Nonce check can be added here
        $search_term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
        $dance_id    = isset( $_GET['dance_id'] ) ? absint( $_GET['dance_id'] ) : 0;
        $results     = [];
        if ( $dance_id === 0 ) { wp_send_json_error( 'A dance must be selected first.', 400 ); }
        $args = array('post_type' => 'dsa_dance_figure', 'posts_per_page' => 10, 's' => $search_term, 'tax_query' => array(array('taxonomy' => 'dsa_dance', 'field' => 'term_id', 'terms' => $dance_id)));
        $query = new WP_Query( $args );
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) { $query->the_post(); $results[] = array('id' => get_the_ID(), 'text' => get_the_title()); }
        }
        wp_reset_postdata();
        wp_send_json_success( $results );
    }
}

/**
 * Handles fetching users and groups for populating select dropdowns in modals.
 */
add_action( 'wp_ajax_dsa_get_modal_dropdown_data', 'dsa_get_modal_dropdown_data_handler' );
if ( ! function_exists( 'dsa_get_modal_dropdown_data_handler' ) ) {
    function dsa_get_modal_dropdown_data_handler() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dsa_get_modal_dropdown_data_nonce' ) ) {
            wp_send_json_error( ['message' => 'Invalid security token.'], 401 );
        }
        if ( ! current_user_can('edit_posts') ) {
            wp_send_json_error( ['message' => 'Insufficient permissions.'] );
        }
        $students_raw = get_users(['role__in' => ['student', 'subscriber'], 'orderby' => 'display_name', 'order' => 'ASC', 'fields' => ['ID', 'display_name']]);
        $students = array_map(function($user){ return ['id' => $user->ID, 'text' => $user->display_name]; }, $students_raw);
        $teachers_raw = get_users(['role__in' => ['teacher', 'studio_manager', 'administrator'], 'orderby' => 'display_name', 'order' => 'ASC', 'fields' => ['ID', 'display_name']]);
        $teachers = array_map(function($user){ return ['id' => $user->ID, 'text' => $user->display_name]; }, $teachers_raw);
        $groups_raw = get_posts(['post_type' => 'dsa_group', 'numberposts' => -1, 'orderby' => 'post_title', 'order' => 'ASC']);
        $groups = array_map(function($post){ return ['id' => $post->ID, 'text' => $post->post_title]; }, $groups_raw);

        wp_send_json_success(['students' => $students, 'teachers' => $teachers, 'groups'   => $groups]);
    }
}