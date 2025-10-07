<?php
/**
 * Student Dashboard -> Repertoire Tab (Main Router)
 * @package DanceStudioApp
 */

if ( ! defined('WPINC') ) {
    die;
}

// Determine the active sub-tab, defaulting to 'choreographies'
$active_sub_tab = isset($_GET['sub_tab']) ? sanitize_key($_GET['sub_tab']) : 'choreographies';
$base_url = add_query_arg('tab', 'repertoire', get_permalink());
?>
<div class="dsa-repertoire-wrapper">
    <h2><?php esc_html_e('Repertoire', 'dancestudio-app'); ?></h2>
    
    <div class="dsa-sub-nav-wrapper">
        <a href="<?php echo esc_url(add_query_arg('sub_tab', 'choreographies', $base_url)); ?>" class="<?php echo $active_sub_tab === 'choreographies' ? 'active' : ''; ?>"><?php esc_html_e('Choreographies', 'dancestudio-app'); ?></a>
        <a href="<?php echo esc_url(add_query_arg('sub_tab', 'dance_figures', $base_url)); ?>" class="<?php echo $active_sub_tab === 'dance_figures' ? 'active' : ''; ?>"><?php esc_html_e('Dance Figures', 'dancestudio-app'); ?></a>
    </div>

    <div class="dsa-sub-tab-content">
    <?php
    // Load the content for the active sub-tab
    if ( 'choreographies' === $active_sub_tab ) {
        // We will create this partial file next
        include DSA_PLUGIN_DIR . 'public/views/partials/repertoire-choreographies.php';
    } else {
        // We will create this partial file next
        include DSA_PLUGIN_DIR . 'public/views/partials/repertoire-dance-figures.php';
    }
    ?>
    </div>
</div>