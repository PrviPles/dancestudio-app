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
                'menu_name'          => __( 'Dance Groups', 'dancestudio-app' ),
                'name_admin_bar'     => __( 'Dance Group', 'dancestudio-app' ),
                'add_new'            => __( 'Add Group', 'dancestudio-app' ),
                'add_new_item'       => __( 'Add New Dance Group', 'dancestudio-app' ),
                'new_item'           => __( 'New Dance Group', 'dancestudio-app' ),
                'edit_item'          => __( 'Edit Dance Group', 'dancestudio-app' ),
                'view_item'          => __( 'View Dance Group', 'dancestudio-app' ),
                'all_items'          => __( 'All Dance Groups', 'dancestudio-app' ),
                'search_items'       => __( 'Search Dance Groups', 'dancestudio-app' ),
                'not_found'          => __( 'No groups found.', 'dancestudio-app' ),
                'not_found_in_trash' => __( 'No groups found in Trash.', 'dancestudio-app' )
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
                'add_new'            => __( 'Add Holiday', 'dancestudio-app' ),
                'add_new_item'       => __( 'Add New Holiday', 'dancestudio-app' ),
                'edit_item'          => __( 'Edit Holiday', 'dancestudio-app' ),
                'all_items'          => __( 'All Holidays', 'dancestudio-app' ),
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
        
        register_taxonomy('dsa_difficulty_level', 'dsa_dance_figure', [
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
?>