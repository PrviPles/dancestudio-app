<?php
/**
 * Template part for displaying the Settings tab content.
 * UPDATED with sub-tabs for better organization.
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! function_exists( 'dsa_render_settings_tab' ) ) {
    function dsa_render_settings_tab() {
        // Determine the active sub-tab
        $active_sub_tab = isset( $_GET['sub_tab'] ) ? sanitize_key( $_GET['sub_tab'] ) : 'general';
        ?>
        <div id="settings-tab-content">
            
            <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="?page=dsa-settings-tab&sub_tab=general" class="nav-tab <?php echo $active_sub_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'dancestudio-app'); ?></a>
                <a href="?page=dsa-settings-tab&sub_tab=studio_info" class="nav-tab <?php echo $active_sub_tab === 'studio_info' ? 'nav-tab-active' : ''; ?>"><?php _e('Studio Information', 'dancestudio-app'); ?></a>
                <a href="?page=dsa-settings-tab&sub_tab=invoice_design" class="nav-tab <?php echo $active_sub_tab === 'invoice_design' ? 'nav-tab-active' : ''; ?>"><?php _e('Invoice Design', 'dancestudio-app'); ?></a>
            </h2>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'dsa_settings_group' );

                // Display the correct settings section based on the active sub-tab
                switch ($active_sub_tab) {
                    case 'studio_info':
                        do_settings_sections( 'dsa_settings_studio_info' );
                        break;
                    case 'invoice_design':
                        do_settings_sections( 'dsa_settings_invoice_design' );
                        break;
                    case 'general':
                    default:
                        do_settings_sections( 'dsa_settings_general' );
                        break;
                }
                
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
?>