<?php
/**
 * Modifies admin list tables to add custom columns and actions.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

// --- 1. Custom Columns for the Main WordPress Users List ---

add_filter( 'manage_users_columns', 'dsa_add_user_table_columns' );
if ( ! function_exists('dsa_add_user_table_columns') ) {
    function dsa_add_user_table_columns( $columns ) {
        $columns['dsa_dance_groups'] = __( 'Dance Group(s)', 'dancestudio-app' );
        return $columns;
    }
}

add_filter( 'manage_users_custom_column', 'dsa_show_user_table_column_data', 10, 3 );
if ( ! function_exists('dsa_show_user_table_column_data') ) {
    function dsa_show_user_table_column_data( $output, $column_name, $user_id ) {
        if ( 'dsa_dance_groups' === $column_name ) {
            // Logic to get group names from new enrollment system
            $enrollments = get_posts([
                'post_type' => 'dsa_enroll_record',
                'post_status' => 'publish',
                'author' => $user_id,
                'posts_per_page' => -1,
            ]);
            $enrolled_group_ids = wp_list_pluck($enrollments, 'post_parent');

            if ( ! empty( $enrolled_group_ids ) ) {
                $group_names = array_map(
                    function( $group_id ) {
                        $group_title = get_the_title( $group_id );
                        $edit_link = get_edit_post_link($group_id);
                        return $group_title ? '<a href="'.esc_url($edit_link).'">' . esc_html( $group_title ) . '</a>' : '';
                    },
                    $enrolled_group_ids
                );
                return implode( ', ', array_filter($group_names) );
            } else {
                return '—';
            }
        }
        return $output;
    }
}


// --- 2. Custom Columns for the Private Lessons List Table ---

add_filter( 'manage_dsa_private_lesson_posts_columns', 'dsa_add_private_lesson_order_column' );
if ( ! function_exists('dsa_add_private_lesson_order_column') ) {
    function dsa_add_private_lesson_order_column( $columns ) {
        $new_columns = [];
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            if ($key === 'author') {
                $new_columns['dsa_order_id'] = __( 'WooCommerce Order', 'dancestudio-app' );
            }
        }
        return $new_columns;
    }
}

add_action( 'manage_dsa_private_lesson_posts_custom_column', 'dsa_render_private_lesson_order_column', 10, 2 );
if ( ! function_exists('dsa_render_private_lesson_order_column') ) {
    function dsa_render_private_lesson_order_column( $column, $post_id ) {
        if ( 'dsa_order_id' === $column ) {
            $order_id = get_post_meta( $post_id, '_dsa_lesson_order_id', true );
            if ( ! empty( $order_id ) && is_numeric( $order_id ) ) {
                $order_edit_link = get_edit_post_link( $order_id );
                if ( $order_edit_link ) {
                    echo '<a href="' . esc_url( $order_edit_link ) . '" target="_blank"><strong>#' . esc_html( $order_id ) . '</strong></a>';
                } else {
                    echo '#' . esc_html( $order_id );
                }
            } else {
                echo '—';
            }
        }
    }
}

// --- NEW: Add a "Delete" link to the Dance Groups list table ---
add_filter( 'post_row_actions', 'dsa_add_group_row_actions', 10, 2 );
function dsa_add_group_row_actions( $actions, $post ) {
    // Check if we are on the 'dsa_group' CPT list table
    if ( $post->post_type === 'dsa_group' ) {
        // The default "Trash" link is usually sufficient, but we add a "Delete" for clarity
        // and to include a confirmation pop-up.
        $delete_link = get_delete_post_link( $post->ID, '', true ); // Set force_delete to true for "Delete Permanently"
        $actions['delete'] = sprintf(
            '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
            esc_url( $delete_link ),
            esc_js( __( 'Are you sure you want to permanently delete this group? This action cannot be undone.', 'dancestudio-app' ) ),
            __( 'Delete Permanently', 'dancestudio-app' )
        );
    }
    return $actions;
}