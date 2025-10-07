<?php
/**
 * Plugin Name:         DanceStudio App
 * Plugin URI:          https://prviples.hr/dancestudio-app
 * Description:         The ultimate all-in-one management solution for dance studios, built for WordPress. Streamline student enrollment, automate class scheduling, track attendance, manage choreographies, and handle payments with powerful WooCommerce integration. Designed to save you time and professionalize your studio operations.
 * Version:             5.4.0
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

define( 'DSA_PLUGIN_VERSION', '5.4.0' );
define( 'DSA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DSA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DSA_PLUGIN_FILE', __FILE__ );

// Load Composer autoloader
if ( file_exists( DSA_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once DSA_PLUGIN_DIR . 'vendor/autoload.php';
}

// =================================================================
// 1. IMMEDIATELY LOAD ALL CORE LOGIC, HANDLERS, AND ADMIN SETUP
// =================================================================

// --- Core Function Libraries ---
require_once DSA_PLUGIN_DIR . 'includes/functions/setup-functions.php';
require_once DSA_PLUGIN_DIR . 'includes/functions/helper-functions.php';
require_once DSA_PLUGIN_DIR . 'includes/functions/enrollment-functions.php';
require_once DSA_PLUGIN_DIR . 'includes/functions/schedule-functions.php';
// --- FIX: Corrected the constant from DSA_LEVEL_DIR to DSA_PLUGIN_DIR ---
require_once DSA_PLUGIN_DIR . 'includes/functions/statistics-functions.php';
require_once DSA_PLUGIN_DIR . 'includes/functions/woocommerce-functions.php';
require_once DSA_PLUGIN_DIR . 'includes/functions/knowledge-base-functions.php';

// --- Post Types & Taxonomies ---
require_once DSA_PLUGIN_DIR . 'includes/dsa-post-types.php';

// --- Admin Setup ---
require_once DSA_PLUGIN_DIR . 'admin/dsa-admin-router.php';
require_once DSA_PLUGIN_DIR . 'admin/dsa-admin-menus.php';
require_once DSA_PLUGIN_DIR . 'admin/dsa-admin-scripts.php';
require_once DSA_PLUGIN_DIR . 'admin/dsa-dashboard-widgets.php';
require_once DSA_PLUGIN_DIR . 'admin/dsa-plugin-settings.php';

// --- AJAX Handlers ---
require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-calendar-events.php';
require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-calendar-actions.php';
require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-calendar-attendance.php';
require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-calendar-data.php';
require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-enrollment-history.php';
require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-enrollment.php';
require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-students.php';
require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-data.php';
require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-couples.php';
require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-choreographies.php';
require_once DSA_PLUGIN_DIR . 'admin/ajax-handlers/handler-subscriptions.php';

// --- Form Handlers ---
require_once DSA_PLUGIN_DIR . 'admin/form-handlers/handler-couple-details.php';

// --- Admin Columns & User Profile Fields ---
require_once DSA_PLUGIN_DIR . 'admin/dsa-user-table-columns.php';
require_once DSA_PLUGIN_DIR . 'admin/dsa-user-profile-fields.php';

// --- Meta Box Definitions ---
require_once DSA_PLUGIN_DIR . 'admin/dsa-class-meta.php';
require_once DSA_PLUGIN_DIR . 'admin/dsa-group-meta.php';
require_once DSA_PLUGIN_DIR . 'admin/dsa-choreography-meta.php';
require_once DSA_PLUGIN_DIR . 'admin/dsa-holiday-meta.php';
require_once DSA_PLUGIN_DIR . 'admin/dsa-private-lesson-meta.php';
require_once DSA_PLUGIN_DIR . 'admin/dsa-product-meta.php';
require_once DSA_PLUGIN_DIR . 'admin/dsa-schedule-meta.php';


// =================================================================
// 2. LOAD VIEW FILES AND PUBLIC SHORTCODES ON THE 'plugins_loaded' HOOK
// =================================================================

add_action( 'plugins_loaded', 'dsa_include_plugin_files' );
function dsa_include_plugin_files() {

    // --- Public (Front-End) Files ---
    require_once DSA_PLUGIN_DIR . 'public/dsa-shortcodes.php';
    require_once DSA_PLUGIN_DIR . 'public/dsa-manager-shortcodes.php';
    require_once DSA_PLUGIN_DIR . 'public/dsa-public-scripts.php';
    require_once DSA_PLUGIN_DIR . 'public/dsa-public-handlers.php';
    require_once DSA_PLUGIN_DIR . 'public/dsa-manager-handlers.php';

    // --- Admin View & Tab Files ---
    if ( is_admin() ) {
        require_once DSA_PLUGIN_DIR . 'admin/views/view-main-dashboard.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/view-couple-details.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/view-order-tracker.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/view-statistics.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/view-all-classes.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/view-all-private-lessons.php';

        // Main Tab Content Files
        require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-dashboard.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-students.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-groups.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-repertoire.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-choreographies.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-couples.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-staff.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-calendar.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-attendance.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-dance-figures.php';
        require_once DSA_PLUGIN_DIR . 'admin/views/tabs/tab-settings.php';
    }
}

// =================================================================
// 3. REGISTER FINAL HOOKS
// =================================================================

register_activation_hook( DSA_PLUGIN_FILE, 'dsa_activate_plugin' );
register_deactivation_hook( DSA_PLUGIN_FILE, 'dsa_deactivate_plugin' );
add_action( 'plugins_loaded', 'dsa_load_textdomain' );
add_filter( 'gettext', 'dsa_translate_role_names', 20, 3 );