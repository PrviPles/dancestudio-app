<?php
/**
 * Plugin Name:         DanceStudio App
 * Plugin URI:          https://prviples.hr/dancestudio-app
 * Description:         A simple plugin for managing a dance studio.
 * Version:             3.6.17
 * Requires at least:   5.2
 * Requires PHP:        7.2
 * Author:              Filip Debelec
 * Author URI:          https://prviples.hr 
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         dancestudio-app
 * Domain Path:         /languages
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define Constants
define( 'DSA_PLUGIN_VERSION', '3.6.17' );
define( 'DSA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DSA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DSA_PLUGIN_FILE', __FILE__ );

// Load Composer autoloader for external libraries
if ( file_exists( DSA_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once DSA_PLUGIN_DIR . 'vendor/autoload.php';
}

// 1. Load all function libraries first
if (file_exists(DSA_PLUGIN_DIR . 'includes/functions/setup-functions.php')) { require_once DSA_PLUGIN_DIR . 'includes/functions/setup-functions.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'includes/functions/enrollment-functions.php')) { require_once DSA_PLUGIN_DIR . 'includes/functions/enrollment-functions.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'includes/functions/woocommerce-functions.php')) { require_once DSA_PLUGIN_DIR . 'includes/functions/woocommerce-functions.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'includes/functions/helper-functions.php')) { require_once DSA_PLUGIN_DIR . 'includes/functions/helper-functions.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'includes/functions/schedule-functions.php')) { require_once DSA_PLUGIN_DIR . 'includes/functions/schedule-functions.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'includes/functions/statistics-functions.php')) { require_once DSA_PLUGIN_DIR . 'includes/functions/statistics-functions.php'; }

// 2. Load files that register post types, taxonomies, and core admin hooks.
if (file_exists(DSA_PLUGIN_DIR . 'includes/dsa-post-types.php')) { require_once DSA_PLUGIN_DIR . 'includes/dsa-post-types.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'admin/dsa-admin-scripts.php')) { require_once DSA_PLUGIN_DIR . 'admin/dsa-admin-scripts.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'admin/dsa-admin-menus.php')) { require_once DSA_PLUGIN_DIR . 'admin/dsa-admin-menus.php'; }

// 3. Load all meta box and admin column definition files.
if (file_exists(DSA_PLUGIN_DIR . 'admin/dsa-group-meta.php')) { require_once DSA_PLUGIN_DIR . 'admin/dsa-group-meta.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'admin/dsa-schedule-meta.php')) { require_once DSA_PLUGIN_DIR . 'admin/dsa-schedule-meta.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'admin/dsa-class-meta.php')) { require_once DSA_PLUGIN_DIR . 'admin/dsa-class-meta.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'admin/dsa-private-lesson-meta.php')) { require_once DSA_PLUGIN_DIR . 'admin/dsa-private-lesson-meta.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'admin/dsa-product-meta.php')) { require_once DSA_PLUGIN_DIR . 'admin/dsa-product-meta.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'admin/dsa-user-profile-fields.php')) { require_once DSA_PLUGIN_DIR . 'admin/dsa-user-profile-fields.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'admin/dsa-user-table-columns.php')) { require_once DSA_PLUGIN_DIR . 'admin/dsa-user-table-columns.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'admin/dsa-holiday-meta.php')) { require_once DSA_PLUGIN_DIR . 'admin/dsa-holiday-meta.php'; }


// 4. Load all AJAX and Form handlers.
if (file_exists(DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-calendar.php')) { require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-calendar.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-enrollment.php')) { require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-enrollment.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-data.php')) { require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-data.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-couples.php')) { require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-couples.php'; }
if (file_exists(DSA_PLUGIN_DIR . 'admin/form-handlers/handler-couple-details.php')) { require_once DSA_PLUGIN_DIR . 'admin/form-handlers/handler-couple-details.php'; }


// Include all other plugin files on the plugins_loaded hook.
add_action( 'plugins_loaded', 'dsa_include_plugin_files' );
function dsa_include_plugin_files() {
    
    // Public-facing files
    if (file_exists(DSA_PLUGIN_DIR . 'public/dsa-shortcodes.php')) { require_once DSA_PLUGIN_DIR . 'public/dsa-shortcodes.php'; }
    if (file_exists(DSA_PLUGIN_DIR . 'public/dsa-public-handlers.php')) { require_once DSA_PLUGIN_DIR . 'public/dsa-public-handlers.php'; }
    if (file_exists(DSA_PLUGIN_DIR . 'public/dsa-manager-shortcodes.php')) { require_once DSA_PLUGIN_DIR . 'public/dsa-manager-shortcodes.php'; }
    if (file_exists(DSA_PLUGIN_DIR . 'public/dsa-manager-handlers.php')) { require_once DSA_PLUGIN_DIR . 'public/dsa-manager-handlers.php'; }
    if (file_exists(DSA_PLUGIN_DIR . 'public/dsa-public-scripts.php')) { require_once DSA_PLUGIN_DIR . 'public/dsa-public-scripts.php'; }

    if ( is_admin() ) {
        if (file_exists(DSA_PLUGIN_DIR . 'admin/dsa-admin-router.php')) { require_once DSA_PLUGIN_DIR . 'admin/dsa-admin-router.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/dsa-dashboard-widgets.php')) { require_once DSA_PLUGIN_DIR . 'admin/dsa-dashboard-widgets.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/dsa-plugin-settings.php')) { require_once DSA_PLUGIN_DIR . 'admin/dsa-plugin-settings.php'; }
        // Views
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/view-statistics.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/view-statistics.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/view-all-groups.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/view-all-groups.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/view-main-dashboard.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/view-main-dashboard.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/view-all-students.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/view-all-students.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/view-all-classes.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/view-all-classes.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/view-couple-details.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/view-couple-details.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/view-order-tracker.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/view-order-tracker.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/view-all-private-lessons.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/view-all-private-lessons.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/view-income-report.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/view-income-report.php'; }
        // Tabs
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/tabs/tab-dashboard.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-dashboard.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/tabs/tab-students.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-students.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/tabs/tab-couples.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-couples.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/tabs/tab-staff.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-staff.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/tabs/tab-calendar.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-calendar.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/tabs/tab-attendance.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-attendance.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/tabs/tab-dance-figures.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-dance-figures.php'; }
        if (file_exists(DSA_PLUGIN_DIR . 'admin/views/tabs/tab-settings.php')) { require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-settings.php'; }
    }
}

// Register Final Hooks
add_action( 'init', 'dsa_register_all_post_types_and_taxonomies' );
add_action( 'plugins_loaded', 'dsa_load_textdomain' );
add_filter( 'gettext', 'dsa_translate_role_names', 20, 3 );
register_activation_hook( DSA_PLUGIN_FILE, 'dsa_activate_plugin' );
register_deactivation_hook( DSA_PLUGIN_FILE, 'dsa_deactivate_plugin' );

?>