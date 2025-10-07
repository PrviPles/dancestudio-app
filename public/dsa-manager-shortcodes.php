<?php
/**
 * Public facing shortcodes for the Studio Manager Dashboard.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

add_shortcode( 'dsa_manager_dashboard', 'dsa_render_manager_dashboard_shortcode' );
function dsa_render_manager_dashboard_shortcode() {
    
    if ( ! current_user_can('manage_options') ) {
        return '<p>' . __('This content is for Studio Managers only.', 'dancestudio-app') . '</p>';
    }
    
    ob_start();

    $tabs = [
        'dashboard' => __('Dashboard', 'dancestudio-app'),
        'students'  => __('Students', 'dancestudio-app'),
        'groups'    => __('Groups', 'dancestudio-app'),
        'calendar'  => __('Calendar', 'dancestudio-app'),
        'orders'    => __('WooCommerce Orders', 'dancestudio-app'),
    ];
    $default_tab = 'dashboard';
    $active_tab  = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs) ? sanitize_key( $_GET['tab'] ) : $default_tab;

    ?>
    <div class="dsa-cursor-dot"></div>
    <div class="dsa-cursor-outline"></div>
    
    <div class="dsa-dashboard-wrapper dsa-manager-dashboard">
        <nav class="dsa-dashboard-nav">
            <div>
                <h1>STUDIO MANAGER</h1>
                <ul>
                    <?php foreach ( $tabs as $slug => $title ) : ?>
                        <li class="<?php echo $active_tab === $slug ? 'active' : ''; ?>">
                            <a href="?tab=<?php echo esc_attr($slug); ?>"><?php echo esc_html($title); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="dsa-logout-link">
                <a href="<?php echo wp_logout_url(home_url());?>"><?php _e('Log Out','dancestudio-app');?></a>
            </div>
        </nav>
        <main class="dsa-dashboard-content">
            <?php
            $template_path = DSA_PLUGIN_DIR . 'public/views/manager/tab-' . $active_tab . '.php';
            if ( file_exists($template_path) ) {
                include $template_path;
            } else {
                include DSA_PLUGIN_DIR . 'public/views/manager/tab-dashboard.php';
            }
            ?>
        </main>
    </div>
    
    <script type="text/javascript">
        // JavaScript for the custom cursor effect
        document.addEventListener('DOMContentLoaded', function() {
            const cursorDot = document.querySelector('.dsa-cursor-dot');
            const cursorOutline = document.querySelector('.dsa-cursor-outline');

            window.addEventListener('mousemove', function(e) {
                const posX = e.clientX;
                const posY = e.clientY;

                cursorDot.style.left = `${posX}px`;
                cursorDot.style.top = `${posY}px`;

                cursorOutline.style.left = `${posX}px`;
                cursorOutline.style.top = `${posY}px`;
            });

            const anchors = document.querySelectorAll('a, button, input[type="submit"]');
            anchors.forEach(el => {
                el.addEventListener('mouseover', () => {
                    cursorOutline.classList.add('hovered');
                });
                el.addEventListener('mouseout', () => {
                    cursorOutline.classList.remove('hovered');
                });
            });
        });
    </script>
    <?php

    return ob_get_clean();
}