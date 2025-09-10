<?php
/**
 * DanceStudio App Plugin Settings
 * Handles registration and rendering of all settings fields.
 *
 * @package DanceStudioApp
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'dsa_register_plugin_settings' ) ) {
    add_action( 'admin_init', 'dsa_register_plugin_settings' );
    function dsa_register_plugin_settings() {

        // Register the main settings option with defaults and capability.
        register_setting(
            'dsa_settings_group',
            'dsa_studio_settings',
            array(
                'type'              => 'array',
                'sanitize_callback' => 'dsa_studio_settings_sanitize',
                'show_in_rest'      => false,
                'default'           => array(
                    'calendar_week_start' => 1, // Monday
                ),
                'capability'        => 'manage_options',
            )
        );

        // Section: Studio Information
        add_settings_section(
            'dsa_studio_info_section',
            __( 'Studio Information', 'dancestudio-app' ),
            'dsa_render_studio_info_section_header',
            'dsa_plugin_settings_page'
        );

        // Fields: Studio details
        add_settings_field(
            'dsa_studio_name',
            __( 'Studio Name', 'dancestudio-app' ),
            'dsa_render_text_field',
            'dsa_plugin_settings_page',
            'dsa_studio_info_section',
            array( 'id' => 'studio_name', 'placeholder' => __( 'e.g., Prvi Ples', 'dancestudio-app' ) )
        );
        add_settings_field(
            'dsa_studio_address',
            __( 'Street Address', 'dancestudio-app' ),
            'dsa_render_text_field',
            'dsa_plugin_settings_page',
            'dsa_studio_info_section',
            array( 'id' => 'street_address' )
        );
        add_settings_field(
            'dsa_studio_city',
            __( 'City', 'dancestudio-app' ),
            'dsa_render_text_field',
            'dsa_plugin_settings_page',
            'dsa_studio_info_section',
            array( 'id' => 'city' )
        );
        add_settings_field(
            'dsa_studio_zip',
            __( 'ZIP Code', 'dancestudio-app' ),
            'dsa_render_text_field',
            'dsa_plugin_settings_page',
            'dsa_studio_info_section',
            array( 'id' => 'zip_code' )
        );
        add_settings_field(
            'dsa_studio_country',
            __( 'Country', 'dancestudio-app' ),
            'dsa_render_text_field',
            'dsa_plugin_settings_page',
            'dsa_studio_info_section',
            array( 'id' => 'country' )
        );
        add_settings_field(
            'dsa_studio_phone',
            __( 'Contact Phone', 'dancestudio-app' ),
            'dsa_render_text_field',
            'dsa_plugin_settings_page',
            'dsa_studio_info_section',
            array( 'id' => 'phone', 'type' => 'tel' )
        );
        add_settings_field(
            'dsa_studio_email',
            __( 'Contact Email', 'dancestudio-app' ),
            'dsa_render_text_field',
            'dsa_plugin_settings_page',
            'dsa_studio_info_section',
            array( 'id' => 'email', 'type' => 'email' )
        );
        add_settings_field(
            'dsa_studio_iban',
            __( 'IBAN (Bank Account)', 'dancestudio-app' ),
            'dsa_render_text_field',
            'dsa_plugin_settings_page',
            'dsa_studio_info_section',
            array( 'id' => 'iban', 'placeholder' => __( 'e.g., HR1234567890123456789', 'dancestudio-app' ) )
        );
        add_settings_field(
            'dsa_studio_logo_id',
            __( 'Studio Logo', 'dancestudio-app' ),
            'dsa_render_studio_logo_field',
            'dsa_plugin_settings_page',
            'dsa_studio_info_section'
        );

        // Section: Calendar Settings
        add_settings_section(
            'dsa_calendar_settings_section',
            __( 'Calendar Settings', 'dancestudio-app' ),
            '__return_empty_string',
            'dsa_plugin_settings_page'
        );

        add_settings_field(
            'dsa_calendar_week_start',
            __( 'Week Starts On', 'dancestudio-app' ),
            'dsa_render_calendar_week_start_field',
            'dsa_plugin_settings_page',
            'dsa_calendar_settings_section'
        );
    }
}

if ( ! function_exists( 'dsa_render_text_field' ) ) {
    function dsa_render_text_field( $args ) {
        $options     = get_option( 'dsa_studio_settings', array() );
        $id          = isset( $args['id'] ) ? $args['id'] : '';
        if ( $id === '' ) {
            return;
        }
        $type        = isset( $args['type'] ) ? $args['type'] : 'text';
        $placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
        $value       = isset( $options[ $id ] ) ? $options[ $id ] : '';

        printf(
            '<input type="%1$s" id="dsa_%2$s" name="dsa_studio_settings[%2$s]" value="%3$s" class="regular-text" placeholder="%4$s" />',
            esc_attr( $type ),
            esc_attr( $id ),
            esc_attr( $value ),
            esc_attr( $placeholder )
        );
    }
}

if ( ! function_exists( 'dsa_render_studio_info_section_header' ) ) {
    function dsa_render_studio_info_section_header() {
        echo '<p>' . esc_html__( 'Enter the primary details for your dance studio. This information will be used on invoices and other documents.', 'dancestudio-app' ) . '</p>';
    }
}

if ( ! function_exists( 'dsa_render_calendar_week_start_field' ) ) {
    function dsa_render_calendar_week_start_field() {
        $options       = get_option( 'dsa_studio_settings', array() );
        $current_value = isset( $options['calendar_week_start'] ) ? absint( $options['calendar_week_start'] ) : 1; // Monday

        $days = array(
            0 => __( 'Sunday', 'dancestudio-app' ),
            1 => __( 'Monday', 'dancestudio-app' ),
            2 => __( 'Tuesday', 'dancestudio-app' ),
            3 => __( 'Wednesday', 'dancestudio-app' ),
            4 => __( 'Thursday', 'dancestudio-app' ),
            5 => __( 'Friday', 'dancestudio-app' ),
            6 => __( 'Saturday', 'dancestudio-app' ),
        );

        echo '<select name="dsa_studio_settings[calendar_week_start]" id="dsa_calendar_week_start">';
        foreach ( $days as $value => $label ) {
            echo '<option value="' . esc_attr( $value ) . '" ' . selected( $current_value, $value, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Select the day your week starts on for calendar displays.', 'dancestudio-app' ) . '</p>';
    }
}

if ( ! function_exists( 'dsa_render_studio_logo_field' ) ) {
    function dsa_render_studio_logo_field() {
        $options  = get_option( 'dsa_studio_settings', array() );
        $logo_id  = isset( $options['studio_logo_id'] ) ? absint( $options['studio_logo_id'] ) : 0;
        $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        ?>
        <div class="dsa-logo-uploader">
            <input type="hidden" name="dsa_studio_settings[studio_logo_id]" id="dsa_studio_logo_id" value="<?php echo esc_attr( $logo_id ); ?>" />
            <button type="button" class="button dsa-upload-logo-button"><?php esc_html_e( 'Upload/Select Logo', 'dancestudio-app' ); ?></button>
            <button type="button" class="button dsa-remove-logo-button" style="<?php echo $logo_id ? '' : 'display:none;'; ?>">
                <?php esc_html_e( 'Remove Logo', 'dancestudio-app' ); ?>
            </button>
            <div id="dsa-logo-preview" style="margin-top:10px;">
                <?php if ( $logo_url ) : ?>
                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-width:200px; max-height:150px; border:1px solid #ddd;" />
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

if ( ! function_exists( 'dsa_studio_settings_sanitize' ) ) {
    function dsa_studio_settings_sanitize( $input ) {
        $new = array();

        // Ensure array input.
        if ( ! is_array( $input ) ) {
            return $new;
        }

        // Text fields.
        $text_fields = array( 'studio_name', 'street_address', 'city', 'zip_code', 'country' );
        foreach ( $text_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $new[ $field ] = sanitize_text_field( wp_unslash( $input[ $field ] ) );
            }
        }

        // Phone: keep common characters.
        if ( isset( $input['phone'] ) ) {
            $phone = sanitize_text_field( wp_unslash( $input['phone'] ) );
            // Optional soft normalization: collapse excessive whitespace.
            $phone = preg_replace( '/\s+/', ' ', trim( $phone ) );
            $new['phone'] = $phone;
        }

        // Email.
        if ( isset( $input['email'] ) ) {
            $email = sanitize_email( wp_unslash( $input['email'] ) );
            $new['email'] = $email ? $email : '';
        }

        // IBAN: uppercase, strip spaces; basic pattern check.
        if ( isset( $input['iban'] ) ) {
            $iban = strtoupper( preg_replace( '/\s+/', '', (string) $input['iban'] ) );
            $iban = preg_replace( '/[^A-Z0-9]/', '', $iban );
            // Basic structural validation: 2 letters, 2 digits, then 9–30 alphanumerics (length 15–34 typical).
            if ( preg_match( '/^[A-Z]{2}\d{2}[A-Z0-9]{9,30}$/', $iban ) ) {
                $new['iban'] = $iban;
            } else {
                // Store sanitized attempt anyway (or leave empty if you prefer strict).
                $new['iban'] = $iban;
            }
        }

        // Calendar week start: clamp to 0–6.
        if ( isset( $input['calendar_week_start'] ) ) {
            $val = absint( $input['calendar_week_start'] );
            $new['calendar_week_start'] = ( $val >= 0 && $val <= 6 ) ? $val : 1;
        }

        // Logo ID: image attachments only.
        if ( isset( $input['studio_logo_id'] ) ) {
            $logo_id = absint( $input['studio_logo_id'] );
            if ( $logo_id ) {
                $mime = get_post_mime_type( $logo_id );
                if ( $mime && strpos( $mime, 'image/' ) === 0 ) {
                    $new['studio_logo_id'] = $logo_id;
                } else {
                    $new['studio_logo_id'] = 0;
                }
            } else {
                $new['studio_logo_id'] = 0;
            }
        }

        return $new;
    }
}
