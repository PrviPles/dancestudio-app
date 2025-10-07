<?php
/**
 * View: Renders the content for the new "Repertoire" tab.
 * This file acts as a container and router for its own sub-tabs.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! function_exists('dsa_render_repertoire_tab') ) {
    function dsa_render_repertoire_tab() {
        $active_repertoire_view = isset( $_GET['repertoire_view'] ) && 'figures' === $_GET['repertoire_view'] ? 'figures' : 'choreographies';
        ?>
        <h3><?php esc_html_e( 'Studio Repertoire', 'dancestudio-app' ); ?></h3>
        <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
            <a href="<?php echo esc_url(add_query_arg('repertoire_view', 'choreographies')); ?>" class="nav-tab <?php echo $active_repertoire_view === 'choreographies' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Choreographies', 'dancestudio-app'); ?></a>
            <a href="<?php echo esc_url(add_query_arg('repertoire_view', 'figures')); ?>" class="nav-tab <?php echo $active_repertoire_view === 'figures' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Dance Figures', 'dancestudio-app'); ?></a>
        </h2>

        <?php
        // Load the content for the active sub-tab
        if ( 'choreographies' === $active_repertoire_view ) {
            // The rendering function is in 'tab-choreographies.php'
            if ( function_exists('dsa_render_choreographies_tab') ) {
                dsa_render_choreographies_tab();
            }
        } else {
            // The rendering function is in 'tab-dance-figures.php'
            if ( function_exists('dsa_render_dance_figures_tab') ) {
                dsa_render_dance_figures_tab();
            }
        }
    }
}