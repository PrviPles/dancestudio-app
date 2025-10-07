<?php
/**
 * Handles the creation of the plugin's settings page fields.
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action( 'admin_init', 'dsa_register_settings' );
add_action( 'admin_enqueue_scripts', 'dsa_enqueue_settings_assets' );

/**
 * Enqueue scripts and styles for the settings page (Color Picker & Media Uploader).
 */
function dsa_enqueue_settings_assets( $hook_suffix ) {
    // Only load on our plugin's settings page
    if ( strpos($hook_suffix, 'dsa-settings-tab') === false ) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style( 'wp-color-picker' );
    // Ensure the path to your JS file is correct. It's in admin/assets/js/
    $js_file_url = DSA_PLUGIN_URL . 'admin/assets/js/dsa-settings-admin.js';
    wp_enqueue_script( 'dsa-settings-admin', $js_file_url, [ 'jquery', 'wp-color-picker' ], DSA_PLUGIN_VERSION, true );
}

/**
 * Registers the settings sections and fields for the plugin.
 */
function dsa_register_settings() {
    register_setting(
        'dsa_settings_group',
        'dsa_studio_settings',
        'dsa_sanitize_studio_settings'
    );

    // --- 1. General Settings Section ---
    add_settings_section('dsa_general_section', __('General Settings', 'dancestudio-app'), null, 'dsa_settings_general');
    add_settings_field('manage_groups_link', __('Manage Dance Groups', 'dancestudio-app'), 'dsa_settings_field_manage_groups_link_callback', 'dsa_settings_general', 'dsa_general_section');
    add_settings_field('calendar_week_start', __('Calendar Week Starts On', 'dancestudio-app'), 'dsa_settings_field_week_start_callback', 'dsa_settings_general', 'dsa_general_section');

    // --- 2. Studio Information Section ---
    add_settings_section('dsa_studio_info_section', __('Studio Information', 'dancestudio-app'), 'dsa_studio_info_section_callback', 'dsa_settings_studio_info');
    add_settings_field( 'studio_name', __( 'Studio Name', 'dancestudio-app' ), 'dsa_settings_field_text_callback', 'dsa_settings_studio_info', 'dsa_studio_info_section', ['id' => 'studio_name'] );
    add_settings_field( 'street_address', __( 'Street Address', 'dancestudio-app' ), 'dsa_settings_field_text_callback', 'dsa_settings_studio_info', 'dsa_studio_info_section', ['id' => 'street_address'] );
    add_settings_field( 'zip_code', __( 'Zip Code', 'dancestudio-app' ), 'dsa_settings_field_text_callback', 'dsa_settings_studio_info', 'dsa_studio_info_section', ['id' => 'zip_code'] );
    add_settings_field( 'city', __( 'City', 'dancestudio-app' ), 'dsa_settings_field_text_callback', 'dsa_settings_studio_info', 'dsa_studio_info_section', ['id' => 'city'] );
    add_settings_field( 'email', __( 'Studio Email', 'dancestudio-app' ), 'dsa_settings_field_text_callback', 'dsa_settings_studio_info', 'dsa_studio_info_section', ['id' => 'email', 'type' => 'email'] );
    add_settings_field( 'iban', __( 'IBAN (for Invoices)', 'dancestudio-app' ), 'dsa_settings_field_text_callback', 'dsa_settings_studio_info', 'dsa_studio_info_section', ['id' => 'iban'] );
    add_settings_field( 'legal_info', __( 'Legal Info (for Invoices)', 'dancestudio-app' ), 'dsa_settings_field_textarea_callback', 'dsa_settings_studio_info', 'dsa_studio_info_section', ['id' => 'legal_info'] );

    // --- 3. Invoice Design Section (Reordered) ---
    add_settings_section('dsa_invoice_design_section', __('Invoice Design', 'dancestudio-app'), null, 'dsa_settings_invoice_design');
    add_settings_field( 'studio_logo_id', __( 'Studio Logo', 'dancestudio-app' ), 'dsa_settings_field_logo_callback', 'dsa_settings_invoice_design', 'dsa_invoice_design_section', ['id' => 'studio_logo_id'] );
    add_settings_field( 'invoice_logo_position', __( 'Logo Position', 'dancestudio-app' ), 'dsa_settings_field_logo_position_callback', 'dsa_settings_invoice_design', 'dsa_invoice_design_section', ['id' => 'invoice_logo_position'] );
    add_settings_field( 'invoice_logo_dimensions', __( 'Studio Logo Dimensions', 'dancestudio-app' ), 'dsa_settings_field_logo_dimensions_callback', 'dsa_settings_invoice_design', 'dsa_invoice_design_section', ['id' => 'invoice_logo_dimensions'] );
    add_settings_field( 'invoice_brand_color', __( 'Invoice Brand Color', 'dancestudio-app' ), 'dsa_settings_field_color_callback', 'dsa_settings_invoice_design', 'dsa_invoice_design_section', ['id' => 'invoice_brand_color'] );
    add_settings_field( 'invoice_preview', __( 'Preview', 'dancestudio-app' ), 'dsa_settings_field_invoice_preview_callback', 'dsa_settings_invoice_design', 'dsa_invoice_design_section');
}

// --- Section & Field Callback Functions ---

function dsa_studio_info_section_callback($args) {
    echo '<p>' . __( 'Enter your studio\'s details. This information will be used on invoices and other communications.', 'dancestudio-app' ) . '</p>';
}

function dsa_settings_field_manage_groups_link_callback($args) {
    echo '<p><a href="' . esc_url( admin_url('edit.php?post_type=dsa_group') ) . '" class="button button-secondary">' . __('Manage All Dance Groups', 'dancestudio-app') . '</a></p>';
    echo '<p class="description">' . __('Manage all your dance groups, such as "Beginners", "Advanced", or "Wedding Couples".', 'dancestudio-app') . '</p>';
}

function dsa_settings_field_week_start_callback($args) {
    $options = get_option('dsa_studio_settings', []);
    $current_day = $options['calendar_week_start'] ?? 1; // Default to Monday
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    echo "<select name='dsa_studio_settings[calendar_week_start]'>";
    foreach ($days as $day_index => $day_name) {
        echo '<option value="' . esc_attr($day_index) . '" ' . selected($current_day, $day_index, false) . '>' . esc_html__($day_name, 'dancestudio-app') . '</option>';
    }
    echo "</select>";
    echo '<p class="description">' . __('Select the day your week starts on for calendar displays.', 'dancestudio-app') . '</p>';
}

function dsa_settings_field_text_callback( $args ) {
    $options = get_option( 'dsa_studio_settings', [] );
    $id = $args['id'];
    $value = isset( $options[$id] ) ? $options[$id] : '';
    $type = isset( $args['type'] ) ? $args['type'] : 'text';
    echo "<input type='{$type}' name='dsa_studio_settings[{$id}]' value='" . esc_attr( $value ) . "' class='regular-text' />";
    if ($id === 'iban') {
        echo '<p class="description">' . __('Required for generating HUB 3A barcodes on invoices.', 'dancestudio-app') . '</p>';
    }
}

function dsa_settings_field_logo_callback( $args ) {
    $options = get_option('dsa_studio_settings', []);
    $id = $args['id'];
    $logo_id = $options[$id] ?? 0;
    $image_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
    ?>
    <div id="dsa-logo-preview">
        <?php if ($image_url): ?>
            <img src="<?php echo esc_url($image_url); ?>" style="max-width:200px; max-height:150px; border:1px solid #ddd;"/>
        <?php endif; ?>
    </div>
    <input type="hidden" name="dsa_studio_settings[<?php echo esc_attr($id); ?>]" id="dsa_studio_logo_id" value="<?php echo esc_attr($logo_id); ?>">
    <button type="button" class="button dsa-upload-logo-button"><?php esc_html_e( 'Upload / Select Logo', 'dancestudio-app' ); ?></button>
    <button type="button" class="button dsa-remove-logo-button" style="<?php echo $logo_id ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove Logo', 'dancestudio-app' ); ?></button>
    <?php
}

function dsa_settings_field_color_callback( $args ) {
    $options = get_option('dsa_studio_settings', []);
    $id = $args['id'];
    $value = $options[$id] ?? '#3a87ad';
    echo "<input type='text' name='dsa_studio_settings[{$id}]' value='" . esc_attr( $value ) . "' class='dsa-color-picker' />";
    echo '<p class="description">' . __('Select a primary color for your invoices (e.g., for headings).', 'dancestudio-app') . '</p>';
}

function dsa_settings_field_logo_position_callback( $args ) {
    $options = get_option('dsa_studio_settings', []);
    $id = $args['id'];
    $current_position = $options[$id] ?? 'left';
    $positions = [
        'left' => __('Left', 'dancestudio-app'),
        'center' => __('Center', 'dancestudio-app'),
        'right' => __('Right', 'dancestudio-app'),
    ];

    echo "<fieldset>";
    foreach ($positions as $value => $label) {
        echo '<label style="margin-right: 15px;">';
        echo '<input type="radio" name="dsa_studio_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" ' . checked($current_position, $value, false) . '>';
        echo ' ' . esc_html($label);
        echo '</label>';
    }
    echo "</fieldset>";
    echo '<p class="description">' . __('Select the alignment for the logo on your invoices.', 'dancestudio-app') . '</p>';
}

function dsa_settings_field_logo_dimensions_callback( $args ) {
    $options = get_option('dsa_studio_settings', []);
    $id = $args['id'];
    $value = $options[$id] ?? 80;
    ?>
    <input type="range" min="20" max="300" name="dsa_studio_settings[<?php echo esc_attr($id); ?>]" value="<?php echo esc_attr($value); ?>">
    <p class="description"><?php esc_html_e( 'Set the maximum height of the logo in pixels.', 'dancestudio-app' ); ?></p>
    <?php
}

function dsa_settings_field_textarea_callback( $args ) {
    $options = get_option('dsa_studio_settings', []);
    $id = $args['id'];
    $value = $options[$id] ?? '';
    echo "<textarea name='dsa_studio_settings[{$id}]' rows='5' class='large-text'>" . esc_textarea($value) . "</textarea>";
    echo '<p class="description">' . __('Enter any legal text that should appear at the bottom of your invoices, such as a Tax ID or company registration number.', 'dancestudio-app') . '</p>';
}

function dsa_settings_field_invoice_preview_callback() {
    $orders = wc_get_orders([
        'limit' => 1,
        'status' => 'completed',
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    if ( empty($orders) ) {
        echo '<p class="description">' . __('No completed orders found to generate a preview.', 'dancestudio-app') . '</p>';
        return;
    }

    $latest_order = reset($orders);
    $preview_url = wp_nonce_url(
        admin_url('admin-post.php?action=dsa_generate_invoice&order_id=' . $latest_order->get_id()),
        'dsa_generate_invoice_nonce',
        'dsa_invoice_nonce'
    );

    echo '<a href="' . esc_url($preview_url) . '" target="_blank" class="button button-secondary">' . __('Preview Invoice', 'dancestudio-app') . '</a>';
    echo '<p class="description">' . __('Click to open a preview of the invoice with your current saved settings. A real order will be used for the preview data.', 'dancestudio-app') . '</p>';
}

/**
 * Sanitizes the settings fields before saving to the database.
 */
function dsa_sanitize_studio_settings( $input ) {
    $sanitized_input = [];
    $options = get_option('dsa_studio_settings', []);

    $text_fields = ['studio_name', 'street_address', 'zip_code', 'city'];
    foreach ($text_fields as $field) {
        if (isset($input[$field])) {
            $sanitized_input[$field] = sanitize_text_field($input[$field]);
        }
    }
     if (isset($input['email'])) {
        $sanitized_input['email'] = sanitize_email($input['email']);
    }
    if ( isset( $input['iban'] ) ) {
        $sanitized_input['iban'] = preg_replace('/[^A-Z0-9]/', '', strtoupper( $input['iban'] ) );
    }
    if (isset($input['studio_logo_id'])) {
        $sanitized_input['studio_logo_id'] = absint($input['studio_logo_id']);
    }
    if (isset($input['invoice_brand_color'])) {
        $sanitized_input['invoice_brand_color'] = sanitize_hex_color($input['invoice_brand_color']);
    }
    if (isset($input['legal_info'])) {
        $sanitized_input['legal_info'] = sanitize_textarea_field($input['legal_info']);
    }
    if (isset($input['calendar_week_start'])) {
        $sanitized_input['calendar_week_start'] = absint($input['calendar_week_start']);
    }
    if (isset($input['invoice_logo_position'])) {
        $allowed_positions = ['left', 'center', 'right'];
        if (in_array($input['invoice_logo_position'], $allowed_positions)) {
            $sanitized_input['invoice_logo_position'] = $input['invoice_logo_position'];
        }
    }
    if (isset($input['invoice_logo_dimensions'])) {
        $sanitized_input['invoice_logo_dimensions'] = absint($input['invoice_logo_dimensions']);
    }

    return array_merge($options, $sanitized_input);
}