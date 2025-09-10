<?php
/**
 * Plugin Setup & Lifecycle Functions
 * Handles activation, deactivation, roles, and internationalization.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Creates roles and assigns all custom capabilities on activation.
 */
function dsa_add_roles_on_activation(){
    $sub_caps = get_role('subscriber') ? get_role('subscriber')->capabilities : [];
    add_role('student', __('Student','dancestudio-app'), $sub_caps);
    add_role('guardian', __('Guardian','dancestudio-app'), $sub_caps);

    $author_caps = get_role('author') ? get_role('author')->capabilities : [];
    $teacher_caps = array_merge($author_caps, ['edit_published_posts' => true, 'delete_published_posts' => true]);
    add_role('teacher', __('Teacher','dancestudio-app'), $teacher_caps);
    
    $editor_caps = get_role('editor') ? get_role('editor')->capabilities : [];
    $studio_manager_caps = array_merge($editor_caps, ['manage_options'=>true, 'list_users'=>true, 'create_users'=>true, 'edit_users'=>true, 'delete_users'=>true]);
    add_role('studio_manager', __('Studio Manager','dancestudio-app'), $studio_manager_caps);

    $lesson_caps = [
        'edit_dsa_lesson', 'read_dsa_lesson', 'delete_dsa_lesson',
        'edit_dsa_lessons', 'edit_others_dsa_lessons', 'publish_dsa_lessons',
        'read_private_dsa_lessons', 'delete_private_dsa_lessons',
        'delete_published_dsa_lessons', 'delete_others_dsa_lessons',
        'edit_private_dsa_lessons', 'edit_published_dsa_lessons',
    ];

    $roles_to_grant_caps = ['administrator', 'studio_manager', 'teacher'];
    foreach($roles_to_grant_caps as $role_name){
        $role = get_role($role_name);
        if($role){
             foreach($lesson_caps as $cap) {
                $role->add_cap($cap);
            }
        }
    }
}

/**
 * Removes custom roles on deactivation.
 */
function dsa_remove_roles_on_deactivation(){
    if(get_role('student')){remove_role('student');}
    if(get_role('guardian')){remove_role('guardian');}
    if(get_role('teacher')){remove_role('teacher');}
    if(get_role('studio_manager')){remove_role('studio_manager');}
}

/**
 * Inserts default taxonomy terms (e.g., for difficulty levels).
 */
function dsa_insert_default_terms() {
    $levels = ['Bronze', 'Silver', 'Gold'];
    foreach ( $levels as $level ) {
        if ( ! term_exists( $level, 'dsa_difficulty_level' ) ) {
            wp_insert_term( $level, 'dsa_difficulty_level' );
        }
    }
}

/**
 * Main plugin activation callback.
 */
function dsa_activate_plugin(){
    dsa_add_roles_on_activation();
    dsa_register_all_post_types_and_taxonomies(); // Ensure CPTs are available for flushing
    dsa_insert_default_terms();
    flush_rewrite_rules();
}

/**
 * Main plugin deactivation callback.
 */
function dsa_deactivate_plugin(){
    dsa_remove_roles_on_deactivation();
    flush_rewrite_rules();
}

/**
 * Loads the plugin's text domain for translations.
 */
function dsa_load_textdomain(){
    load_plugin_textdomain('dancestudio-app', false, dirname(plugin_basename(DSA_PLUGIN_FILE)) . '/languages/');
}

/**
 * Translates custom role names where they appear in the interface.
 */
function dsa_translate_role_names($t, $text, $d){
    if('default' === $d){
        switch($text){
            case 'Student': return __('Student', 'dancestudio-app');
            case 'Guardian': return __('Guardian', 'dancestudio-app');
            case 'Teacher': return __('Teacher', 'dancestudio-app');
            case 'Studio Manager': return __('Studio Manager', 'dancestudio-app');
        }
    }
    return $t;
}