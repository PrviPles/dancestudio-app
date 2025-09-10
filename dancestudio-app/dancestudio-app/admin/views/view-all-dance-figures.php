<?php
// Ensure this file is not accessed directly.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Renders the "All Dance Figures" admin page.
 * This function contains the HTML and WP_Query loop to display the figures in a table.
 */
function dsa_render_all_dance_figures_page() {
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">All Dance Figures</h1>
        
        <a href="<?php echo admin_url( 'post-new.php?post_type=dsa_dance_figure' ); ?>" class="page-title-action">Add New</a>
        
        <hr class="wp-header-end">

        <style>
            .dance-figures-table-wrapper { margin-top: 20px; }
        </style>

        <div class="dance-figures-table-wrapper">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column">Figure Name</th>
                        <th scope="col" class="manage-column">Associated Dance</th>
                        <th scope="col" class="manage-column">Rank / Level</th>
                        <th scope="col" class="manage-column">Actions</th>
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

                    $figures_query = new WP_Query($args);

                    if ($figures_query->have_posts()) {
                        while ($figures_query->have_posts()) {
                            $figures_query->the_post();
                            
                            $figure_id = get_the_ID();

                            // CORRECTED: Get associated dance(s) from the 'dsa_dance' taxonomy
                            $dance_terms = get_the_terms($figure_id, 'dsa_dance');
                            $dance_names = [];
                            if ( ! empty($dance_terms) && ! is_wp_error($dance_terms) ) {
                                foreach ($dance_terms as $term) {
                                    $dance_names[] = esc_html($term->name);
                                }
                            }

                            // NEW: Get rank/level from the 'dsa_difficulty_level' taxonomy
                            $level_terms = get_the_terms($figure_id, 'dsa_difficulty_level');
                            $level_names = [];
                            if ( ! empty($level_terms) && ! is_wp_error($level_terms) ) {
                                foreach ($level_terms as $term) {
                                    $level_names[] = esc_html($term->name);
                                }
                            }
                            
                            $edit_link = get_edit_post_link($figure_id);

                            echo '<tr>';
                            echo '<td><strong><a href="' . esc_url($edit_link) . '">' . get_the_title() . '</a></strong></td>';
                            echo '<td>' . (!empty($dance_names) ? implode(', ', $dance_names) : '—') . '</td>';
                            echo '<td>' . (!empty($level_names) ? implode(', ', $level_names) : '—') . '</td>';
                            echo '<td><a href="' . esc_url($edit_link) . '">Edit</a></td>';
                            echo '</tr>';
                        }
                    } else {
                        // No posts found
                        echo '<tr><td colspan="4">No dance figures found. Start by adding one!</td></tr>';
                    }
                    
                    wp_reset_postdata();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}