<?php
/**
 * Registers all custom post types and taxonomies for the plugin.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action( 'init', 'dsa_register_all_post_types_and_taxonomies' );
if ( ! function_exists( 'dsa_register_all_post_types_and_taxonomies' ) ) {
    function dsa_register_all_post_types_and_taxonomies() {

        $lesson_capabilities = [
            'edit_post'          => 'edit_dsa_lesson',
            'read_post'          => 'read_dsa_lesson',
            'delete_post'        => 'delete_dsa_lesson',
            'edit_posts'         => 'edit_dsa_lessons',
            'edit_others_posts'  => 'edit_others_dsa_lessons',
            'publish_posts'      => 'publish_dsa_lessons',
            'read_private_posts' => 'read_private_dsa_lessons',
            'create_posts'       => 'edit_dsa_lessons',
        ];

        register_post_type( 'dsa_private_lesson', [
            'labels' => ['name' => __('Private Lessons', 'dancestudio-app'), 'singular_name' => __('Private Lesson', 'dancestudio-app')],
            'public' => true, 
            'show_ui' => true, 
            'has_archive' => false, 
            'show_in_menu' => false,
            'menu_icon' => 'dashicons-businessperson', 
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'author', 'revisions'],
            'capability_type' => 'dsa_lesson',
            'capabilities'    => $lesson_capabilities,
            'map_meta_cap'    => true,
        ]);

        register_post_type( 'dsa_group', [
            'labels' => [
                'name'               => __( 'Dance Groups', 'dancestudio-app' ),
                'singular_name'      => __( 'Dance Group', 'dancestudio-app' ),
                'add_new_item'       => __( 'Add New Dance Group', 'dancestudio-app' ),
                'edit_item'          => __( 'Edit Dance Group', 'dancestudio-app' ),
            ],
            'public' => true,
            'show_ui' => true, 
            'has_archive' => false, 
            'show_in_menu' => 'dsa-groups',
            'menu_icon' => 'dashicons-networking', 
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'revisions']
        ]);

        register_post_type( 'dsa_group_class', [
            'labels' => ['name' => __('Group Classes', 'dancestudio-app'), 'singular_name' => __('Group Class', 'dancestudio-app')],
            'public' => true, 
            'show_ui' => true, 
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'revisions'],
            'capability_type' => 'dsa_lesson',
            'capabilities'    => $lesson_capabilities,
            'map_meta_cap'    => true,
        ]);

        register_post_type( 'dsa_dance_figure', [
            'labels' => [
                'name'               => __('Dance Figures', 'dancestudio-app'),
                'singular_name'      => __('Dance Figure', 'dancestudio-app'),
            ],
            'public'       => true,
            'show_ui'      => true,
            'has_archive'  => true,
            'show_in_menu' => false,
            'menu_icon'    => 'dashicons-universal-access-alt',
            'show_in_rest' => true,
            'supports'     => ['title', 'editor', 'revisions'],
            'taxonomies'   => ['dsa_dance', 'dsa_difficulty_level'],
        ]);
        
        register_post_type( 'dsa_choreography', [
            'labels' => [
                'name'               => __( 'Choreographies', 'dancestudio-app' ),
                'singular_name'      => __( 'Choreography', 'dancestudio-app' ),
            ],
            'public'       => true,
            'show_ui'      => true,
            'has_archive'  => true,
            'show_in_menu' => false,
            'menu_icon'    => 'dashicons-video-alt3',
            'show_in_rest' => true,
            'supports'     => ['title', 'editor', 'revisions'],
            'taxonomies'   => ['dsa_difficulty_level'],
        ]);

        register_post_type( 'dsa_enroll_record', [
            'labels' => [
                'name'          => __( 'Enrollment Records', 'dancestudio-app' ),
                'singular_name' => __( 'Enrollment Record', 'dancestudio-app' ),
            ],
            'public'       => false,
            'show_ui'      => false,
            'show_in_menu' => false,
            'supports'     => ['title', 'author', 'custom-fields'],
            'rewrite'      => false,
            'query_var'    => false,
        ]);

        register_post_type( 'dsa_holiday', [
            'labels' => [
                'name'               => __( 'Holidays', 'dancestudio-app' ),
                'singular_name'      => __( 'Holiday', 'dancestudio-app' ),
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => false,
            'menu_icon'    => 'dashicons-calendar-alt',
            'supports'     => ['title'],
        ]);

        register_taxonomy('dsa_dance', ['dsa_dance_figure', 'dsa_group_class'], [
            'labels' => ['name' => __('Dances', 'dancestudio-app'), 'singular_name' => __('Dance', 'dancestudio-app')],
            'hierarchical' => true, 'show_admin_column' => true, 'show_in_rest' => true,
        ]);
        
        register_taxonomy('dsa_difficulty_level', ['dsa_dance_figure', 'dsa_choreography'], [
            'labels' => ['name' => __('Difficulty Levels', 'dancestudio-app'), 'singular_name' => __('Difficulty Level', 'dancestudio-app')],
            'hierarchical' => true, 'show_admin_column' => true, 'show_in_rest' => true,
        ]);

        register_post_status( 'dropped_out', [
            'label'                     => _x( 'Dropped Out', 'post status', 'dancestudio-app' ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Dropped Out <span class="count">(%s)</span>', 'Dropped Out <span class="count">(%s)</span>', 'dancestudio-app' ),
        ]);
    }
}

// Add filters to the Dance Figures admin list
add_action( 'restrict_manage_posts', 'dsa_add_dance_figure_filters' );
function dsa_add_dance_figure_filters( $post_type ) {
    if ( 'dsa_dance_figure' === $post_type ) {
        wp_dropdown_categories([
            'show_option_all' => 'All Dances',
            'taxonomy'        => 'dsa_dance',
            'name'            => 'dsa_dance_filter',
            'orderby'         => 'name',
            'selected'        => $_GET['dsa_dance_filter'] ?? 0,
            'hierarchical'    => true,
            'show_count'      => true,
            'hide_empty'      => true,
            'value_field'     => 'term_id',
        ]);
        wp_dropdown_categories([
            'show_option_all' => 'All Difficulty Levels',
            'taxonomy'        => 'dsa_difficulty_level',
            'name'            => 'dsa_difficulty_filter',
            'orderby'         => 'name',
            'selected'        => $_GET['dsa_difficulty_filter'] ?? 0,
            'hierarchical'    => true,
            'show_count'      => true,
            'hide_empty'      => true,
            'value_field'     => 'term_id',
        ]);
    }
}

// This function processes the filter selections
add_action( 'pre_get_posts', 'dsa_filter_dance_figures_query' );
function dsa_filter_dance_figures_query( $query ) {
    if ( is_admin() && $query->is_main_query() && isset( $query->query_vars['post_type'] ) && 'dsa_dance_figure' === $query->query_vars['post_type'] ) {
        $tax_query = $query->get('tax_query') ?: [];
        if(!is_array($tax_query)) $tax_query = [];
        
        if ( isset( $_GET['dsa_dance_filter'] ) && ! empty( $_GET['dsa_dance_filter'] ) ) {
            $tax_query[] = [
                'taxonomy' => 'dsa_dance',
                'field'    => 'term_id',
                'terms'    => absint($_GET['dsa_dance_filter']),
            ];
        }
        
        if ( isset( $_GET['dsa_difficulty_filter'] ) && ! empty( $_GET['dsa_difficulty_filter'] ) ) {
            $tax_query[] = [
                'taxonomy' => 'dsa_difficulty_level',
                'field'    => 'term_id',
                'terms'    => absint($_GET['dsa_difficulty_filter']),
            ];
        }

        if ( count($tax_query) > 1 ) {
             $tax_query['relation'] = 'AND';
             $query->set( 'tax_query', $tax_query );
        } elseif ( !empty($tax_query) ) {
             $query->set( 'tax_query', $tax_query );
        }
    }
}

// Add a "Back" button to the Edit Figure and Add New Figure screens
add_action( 'all_admin_notices', 'dsa_add_back_button_to_figure_screens' );
function dsa_add_back_button_to_figure_screens() {
    $screen = get_current_screen();

    // --- FIX: Check for both 'post' (Edit) and 'post-new' (Add New) screens ---
    if ( $screen && in_array( $screen->base, ['post', 'post-new'] ) && 'dsa_dance_figure' === $screen->post_type ) {
        $back_url = admin_url('edit.php?post_type=dsa_dance_figure');
        echo '<div style="margin-top: 15px;"><a href="' . esc_url($back_url) . '" class="button">&larr; ' . esc_html__('Back to All Dance Figures', 'dancestudio-app') . '</a></div>';
    }
}