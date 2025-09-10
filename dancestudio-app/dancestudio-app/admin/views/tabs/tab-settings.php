<?php
/**
 * Template part for displaying the Settings tab content.
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! function_exists( 'dsa_render_settings_tab' ) ) {
    function dsa_render_settings_tab() {
        ?>
        <div id="settings-tab-content">
            
            <div class="postbox">
                <h2 class="hndle"><span><?php esc_html_e( 'Manage Dance Groups', 'dancestudio-app' ); ?></span></h2>
                <div class="inside">
                    <p><?php esc_html_e( 'Manage all your dance groups, such as "Beginners", "Advanced", or "Wedding Couples".', 'dancestudio-app' ); ?></p>
                    <p>
                        <a href="<?php echo esc_url( admin_url('edit.php?post_type=dsa_group') ); ?>" class="button button-primary">
                            <?php esc_html_e( 'Manage Dance Groups', 'dancestudio-app' ); ?>
                        </a>
                    </p>
                </div>
            </div>

            <hr>

            <h3><?php esc_html_e( 'Plugin Settings', 'dancestudio-app' ); ?></h3>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'dsa_settings_group' );
                do_settings_sections( 'dsa_plugin_settings_page' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
?>