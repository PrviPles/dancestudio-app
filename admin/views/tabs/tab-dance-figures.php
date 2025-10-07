<?php
/**
 * View: Renders the content for the "Dance Figures" tab.
 * UPDATED: Added filters and an "Add New Figure" button.
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
        // Get filter values from the URL
        $dance_filter_id      = isset( $_GET['dance_filter'] ) ? absint( $_GET['dance_filter'] ) : 0;
        $difficulty_filter_id = isset( $_GET['difficulty_filter'] ) ? absint( $_GET['difficulty_filter'] ) : 0;
        ?>
        <h3><?php esc_html_e( 'Dance Figures Overview', 'dancestudio-app' ); ?></h3>
        <p>
            <?php esc_html_e( 'This page lists all your dance figures. Use the filters to narrow the results or manage all figures.', 'dancestudio-app' ); ?>
            
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=dsa_dance_figure' ) ); ?>" class="button button-primary" style="margin-left: 15px; vertical-align: middle;">
                <?php esc_html_e( 'Add New Figure', 'dancestudio-app' ); ?>
            </a>

            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=dsa_dance_figure' ) ); ?>" class="button button-secondary" style="margin-left: 10px; vertical-align: middle;">
                <?php esc_html_e( 'Manage All Figures', 'dancestudio-app' ); ?>
            </a>
        </p>

        <form method="get" style="margin: 20px 0;">
            <input type="hidden" name="page" value="dsa-dance-figures">
            <div class="alignleft actions">
                <?php
                // Dropdown for Dance Filter
                wp_dropdown_categories([
                    'show_option_all' => __('All Dances', 'dancestudio-app'),
                    'taxonomy'        => 'dsa_dance',
                    'name'            => 'dance_filter',
                    'selected'        => $dance_filter_id,
                    'hierarchical'    => true,
                    'hide_empty'      => false,
                    'value_field'     => 'term_id',
                ]);

                // Dropdown for Difficulty Filter
                wp_dropdown_categories([
                    'show_option_all' => __('All Difficulties', 'dancestudio-app'),
                    'taxonomy'        => 'dsa_difficulty_level',
                    'name'            => 'difficulty_filter',
                    'selected'        => $difficulty_filter_id,
                    'hierarchical'    => true,
                    'hide_empty'      => false,
                    'value_field'     => 'term_id',
                ]);
                ?>
                <input type="submit" class="button" value="<?php esc_attr_e('Filter'); ?>">
            </div>
        </form>

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
                    'posts_per_page' => -1,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                );

                // Dynamically build the taxonomy query
                $tax_query = ['relation' => 'AND'];
                if ( $dance_filter_id > 0 ) {
                    $tax_query[] = [
                        'taxonomy' => 'dsa_dance',
                        'field'    => 'term_id',
                        'terms'    => $dance_filter_id,
                    ];
                }
                if ( $difficulty_filter_id > 0 ) {
                    $tax_query[] = [
                        'taxonomy' => 'dsa_difficulty_level',
                        'field'    => 'term_id',
                        'terms'    => $difficulty_filter_id,
                    ];
                }
                if ( count($tax_query) > 1 ) {
                    $args['tax_query'] = $tax_query;
                }
                
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
                    echo '<tr><td colspan="3">' . esc_html__('No dance figures found matching your criteria.', 'dancestudio-app') . '</td></tr>';
                }
                wp_reset_postdata();
                ?>
            </tbody>
        </table>
        <?php
    }
}