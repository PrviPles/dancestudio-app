<?php
/**
 * View: Renders the content for the "Dance Figures" tab.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Defines the function that renders the content for the 'Dance Figures' tab.
 */
if ( ! function_exists('dsa_render_dance_figures_tab') ) {
    function dsa_render_dance_figures_tab() {
        ?>
        <h3><?php esc_html_e( 'Dance Figures Overview', 'dancestudio-app' ); ?></h3>
        <p>
            <?php esc_html_e( 'This is a quick overview of your most recent dance figures. To add, edit, or see all figures, please use the detailed management page.', 'dancestudio-app' ); ?>
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=dsa_dance_figure' ) ); ?>" class="button button-primary" style="margin-left: 15px; vertical-align: middle;">
                <?php esc_html_e( 'Manage All Figures', 'dancestudio-app' ); ?>
            </a>
        </p>

        <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e( 'Figure Name', 'dancestudio-app' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Associated Dance(s)', 'dancestudio-app' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Difficulty Level', 'dancestudio-app' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $args = array(
                    'post_type'      => 'dsa_dance_figure',
                    'posts_per_page' => 25, // Limit for a clean dashboard view
                    'orderby'        => 'post_modified',
                    'order'          => 'DESC',
                );

                $figures_query = new WP_Query($args);

                if ($figures_query->have_posts()) {
                    while ($figures_query->have_posts()) {
                        $figures_query->the_post();
                        $figure_id = get_the_ID();
                        
                        $dance_terms = get_the_terms($figure_id, 'dsa_dance');
                        $dance_names = !empty($dance_terms) && !is_wp_error($dance_terms) ? wp_list_pluck($dance_terms, 'name') : [];

                        $level_terms = get_the_terms($figure_id, 'dsa_difficulty_level');
                        $level_names = !empty($level_terms) && !is_wp_error($level_terms) ? wp_list_pluck($level_terms, 'name') : [];

                        echo '<tr>';
                        echo '<td><strong>' . esc_html(get_the_title()) . '</strong></td>';
                        echo '<td>' . (!empty($dance_names) ? esc_html(implode(', ', $dance_names)) : '—') . '</td>';
                        echo '<td>' . (!empty($level_names) ? esc_html(implode(', ', $level_names)) : '—') . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="3">' . esc_html__('No dance figures found. You can add them from the "Manage All Figures" page.', 'dancestudio-app') . '</td></tr>';
                }
                wp_reset_postdata();
                ?>
            </tbody>
        </table>
        <?php
    }
}