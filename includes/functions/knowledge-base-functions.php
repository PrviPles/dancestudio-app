<?php
/**
 * Functions for managing the student/couple knowledge base.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Hooks into the saving of a private lesson to update student knowledge.
 *
 * @param int     $post_id The ID of the post being saved.
 * @param WP_Post $post    The post object.
 */
function dsa_update_student_knowledge_base( $post_id, $post ) {
    // Only run for our specific post type and not on autosave.
    if ( 'dsa_private_lesson' !== $post->post_type || defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Get the students and figures from the lesson we just saved
    $student1_id = get_post_meta( $post_id, '_dsa_lesson_student1_id', true );
    $student2_id = get_post_meta( $post_id, '_dsa_lesson_student2_id', true );
    $practiced_figures = get_post_meta( $post_id, '_dsa_practiced_figure_ids', true );

    $students_in_lesson = array_filter( [ absint($student1_id), absint($student2_id) ] );

    if ( empty($students_in_lesson) || ! is_array($practiced_figures) ) {
        // If there are no students or no figures, there's nothing to update.
        // This function only ever ADDS knowledge, so we exit here.
        return;
    }

    foreach ( $students_in_lesson as $student_id ) {
        // Get the student's existing knowledge base
        $known_figures = get_user_meta( $student_id, '_dsa_known_figures', true );
        if ( ! is_array($known_figures) ) {
            $known_figures = [];
        }

        // Merge the newly practiced figures with the existing list
        $updated_figures = array_merge( $known_figures, $practiced_figures );
        
        // Remove any duplicates to keep the list clean
        $updated_figures = array_unique( $updated_figures );

        // Sort the array to keep it tidy in the database
        sort($updated_figures);

        // Save the updated list back to the student's user meta
        update_user_meta( $student_id, '_dsa_known_figures', $updated_figures );
    }
}
// Hook our function to run after a private lesson is saved (priority 20 to run after the meta is saved).
add_action( 'save_post_dsa_private_lesson', 'dsa_update_student_knowledge_base', 20, 2 );

/**
 * Helper function to display a list of figure IDs, grouped by Dance.
 *
 * @param array $figure_ids List of dance figure post IDs.
 */
function dsa_display_grouped_figures_list( $figure_ids ) {
    if ( empty($figure_ids) || ! is_array($figure_ids) ) {
        echo '<em>' . esc_html__( 'None', 'dancestudio-app' ) . '</em>';
        return;
    }

    $figures_query = new WP_Query([
        'post_type'      => 'dsa_dance_figure',
        'posts_per_page' => -1,
        'post__in'       => $figure_ids,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    if ( ! $figures_query->have_posts() ) {
        echo '<em>' . esc_html__( 'None', 'dancestudio-app' ) . '</em>';
        return;
    }

    $grouped_by_dance = [];
    while ( $figures_query->have_posts() ) {
        $figures_query->the_post();
        $dance_terms = get_the_terms( get_the_ID(), 'dsa_dance' );

        if ( ! empty( $dance_terms ) && ! is_wp_error( $dance_terms ) ) {
            $grouped_by_dance[ $dance_terms[0]->name ][] = get_the_title();
        } else {
            $grouped_by_dance[ __( 'Uncategorized', 'dancestudio-app' ) ][] = get_the_title();
        }
    }
    wp_reset_postdata();

    ksort($grouped_by_dance);

    foreach ( $grouped_by_dance as $dance_name => $figures ) {
        echo '<div style="margin-bottom: 10px;">';
        echo '<strong>' . esc_html($dance_name) . '</strong>';
        echo '<ul style="margin: 2px 0 0 18px; font-size: 0.9em;">';
        foreach ($figures as $figure) {
            echo '<li>' . esc_html($figure) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}