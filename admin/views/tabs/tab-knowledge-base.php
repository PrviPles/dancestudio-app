<?php
/**
 * View Part: Renders the content for the "Knowledge Base" tab on the student profile.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

if ( ! function_exists( 'dsa_render_knowledge_base_tab_content' ) ) {
    function dsa_render_knowledge_base_tab_content( $student_data ) {
        $student_id = $student_data->ID;

        // Get the list of figure IDs from the student's user meta
        $known_figure_ids = get_user_meta( $student_id, '_dsa_known_figures', true );

        if ( empty( $known_figure_ids ) || ! is_array( $known_figure_ids ) ) {
            echo '<p>' . esc_html__( 'This student has not learned any dance figures yet.', 'dancestudio-app' ) . '</p>';
            return;
        }

        // Get all the post objects for the known figures in one query for efficiency
        $figures_query = new WP_Query([
            'post_type'      => 'dsa_dance_figure',
            'posts_per_page' => -1,
            'post__in'       => $known_figure_ids,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        if ( ! $figures_query->have_posts() ) {
            echo '<p>' . esc_html__( 'This student has not learned any dance figures yet.', 'dancestudio-app' ) . '</p>';
            return;
        }

        // Create an array to group figures by their dance
        $grouped_by_dance = [];
        while ( $figures_query->have_posts() ) {
            $figures_query->the_post();
            $figure_id = get_the_ID();
            $dance_terms = get_the_terms( $figure_id, 'dsa_dance' );

            if ( ! empty( $dance_terms ) && ! is_wp_error( $dance_terms ) ) {
                foreach ( $dance_terms as $term ) {
                    $grouped_by_dance[ $term->name ][] = get_the_title();
                }
            } else {
                $grouped_by_dance[ __( 'Uncategorized', 'dancestudio-app' ) ][] = get_the_title();
            }
        }
        wp_reset_postdata();
        ?>

        <h3><?php esc_html_e( 'Learned Dances & Figures', 'dancestudio-app' ); ?></h3>
        <div class="dsa-knowledge-base-display">
            <?php foreach ( $grouped_by_dance as $dance_name => $figures ) : ?>
                <div class="dsa-dance-group" style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 5px;"><?php echo esc_html( $dance_name ); ?></h4>
                    <ul style="margin-top: 5px; columns: 2; -webkit-columns: 2; -moz-columns: 2;">
                        <?php foreach ( $figures as $figure_name ) : ?>
                            <li><?php echo esc_html( $figure_name ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}