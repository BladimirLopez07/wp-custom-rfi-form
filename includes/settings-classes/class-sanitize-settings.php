<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class responsible for sanitizing plugin settings.
 *
 * This class contains methods to sanitize the input from the settings fields
 * before saving them to the database.
 */
class Custom_RFI_Form_Sanitize_Settings {

    /**
     * Sanitize plugin settings input.
     *
     * Processes the raw input from the settings fields, sanitizes it, and
     * returns the sanitized data to be saved to the database.
     *
     * @param array $input The raw input array to sanitize.
     * @return array The sanitized input array.
     */
    public static function sanitize_settings( $input ) {
        $input           = is_array( $input ) ? $input : array();
        $sanitized_input = array();

        // Debug Mode
        $sanitized_input['debug_mode'] = self::sanitize_bool( $input, 'debug_mode' );

        // Form Steps
        $sanitized_input['custom_form_type'] = ( isset( $input['custom_form_type'] ) && '2' === $input['custom_form_type'] ) ? '2' : '1';

        // Form Track ID
        $sanitized_input['form_track_id'] = self::sanitize_text( $input, 'form_track_id' );
        if ( '' === $sanitized_input['form_track_id'] ) {
            add_settings_error( 'form_track_id', 'form_track_id_error', 'Form Track ID is required.', 'error' );
        }

        // Global Feature ID
        $sanitized_input['global_feature_id'] = self::sanitize_text( $input, 'global_feature_id' );

        // Form name
        $sanitized_input['form_name'] = self::sanitize_text( $input, 'form_name' );

        // Form Title
        $sanitized_input['form_title'] = self::sanitize_text( $input, 'form_title' );
        if ( '' === $sanitized_input['form_title'] ) {
            add_settings_error( 'form_title', 'form_title_error', 'Form Title is required.', 'error' );
        }

        // Server Switch
        $sanitized_input['server_switch'] = self::sanitize_choice( $input, 'server_switch', array( 'stage', 'prod', 'qa' ), 'qa' );

        // API Endpoints: HTTPS URLs (staging may use HTTP). Fields defined in wp-config.php are not posted.
        $endpoint_labels = array(
            'api_url_prod'      => 'Production API URL',
            'api_url_qa'        => 'QA API URL',
            'api_url_stage'     => 'Staging API URL',
            'postal_lookup_url' => 'Zip Code Lookup URL',
            'trackid_check_url' => 'Track ID Check URL',
        );
        foreach ( $endpoint_labels as $endpoint_key => $endpoint_label ) {
            $raw_url = self::sanitize_text( $input, $endpoint_key );
            $url     = custom_rfi_form_sanitize_endpoint_url( $raw_url, 'api_url_stage' === $endpoint_key );

            if ( '' !== $raw_url && '' === $url ) {
                add_settings_error(
                    $endpoint_key,
                    $endpoint_key . '_error',
                    sprintf( '%s was not saved: use a full https:// URL without a query string, fragment or credentials.', $endpoint_label ),
                    'error'
                );
            }
            $sanitized_input[ $endpoint_key ] = $url;
        }

        // Programs before campus
        $sanitized_input['program_before_campus'] = self::sanitize_bool( $input, 'program_before_campus' );

        // Campus Type
        $sanitized_input['campus_type'] = self::sanitize_choice( $input, 'campus_type', array( 'single', 'multiple' ), 'multiple' );

        // Campus ID
        $sanitized_input['campus_id'] = self::sanitize_text( $input, 'campus_id' );

        // Form Fields
        $sanitized_input['form_fields'] = self::sanitize_multi_choice(
            $input,
            'form_fields',
            array( 'campus', 'aos', 'highestEducation', 'startTerm', 'fname', 'lname', 'birthday', 'phone', 'zip' )
        );

        // Color Scheme: known keys with valid hex colors only
        $sanitized_input['color_scheme'] = array();
        if ( isset( $input['color_scheme'] ) && is_array( $input['color_scheme'] ) ) {
            foreach ( array_keys( custom_rfi_form_default_colors() ) as $color_key ) {
                $color = isset( $input['color_scheme'][ $color_key ] ) ? sanitize_hex_color( $input['color_scheme'][ $color_key ] ) : '';
                if ( $color ) {
                    $sanitized_input['color_scheme'][ $color_key ] = $color;
                }
            }
        }

        // Highest Level of Education
        $sanitized_input['highest_education'] = isset( $input['highest_education'] ) ? sanitize_textarea_field( $input['highest_education'] ) : '';

        // Start Term
        $sanitized_input['start_term'] = isset( $input['start_term'] ) ? sanitize_textarea_field( $input['start_term'] ) : '';

        // Program Option Group
        $sanitized_input['program_option_group'] = self::sanitize_bool( $input, 'program_option_group' );

        // Option Group Order
        $sanitized_input['opt_group_order'] = array();
        if ( isset( $input['opt_group_order'] ) && is_array( $input['opt_group_order'] ) ) {
            foreach ( $input['opt_group_order'] as $item ) {
                if ( is_array( $item ) && ! empty( $item['option'] ) ) {
                    $sanitized_input['opt_group_order'][] = array(
                        'option' => sanitize_text_field( $item['option'] ),
                    );
                }
            }
        }

        // Disclaimer
        $sanitized_input['disclaimer'] = isset( $input['disclaimer'] ) ? sanitize_textarea_field( $input['disclaimer'] ) : '';

        // Submit Button Text
        $sanitized_input['submit_button_text'] = self::sanitize_text( $input, 'submit_button_text' );

        // Thank you Url type
        $sanitized_input['thank_you_url_type'] = self::sanitize_choice( $input, 'thank_you_url_type', array( 'internal', 'external' ), 'internal' );

        // External Thank You Url
        $sanitized_input['external_thank_you_url'] = isset( $input['external_thank_you_url'] ) ? esc_url_raw( $input['external_thank_you_url'] ) : '';

        // Internal Thank You Url: must be one of the site's pages
        $internal_url = isset( $input['internal_thank_you_url'] ) ? (string) $input['internal_thank_you_url'] : '';
        $sanitized_input['internal_thank_you_url'] = array_key_exists( $internal_url, custom_rfi_form_get_pages() ) ? $internal_url : '';

        // Return LeadId
        $sanitized_input['return_lead_id'] = self::sanitize_multi_choice( $input, 'return_lead_id', array( 'lead_id' ) );

        // Honey Pot Field
        $sanitized_input['honey_pot_field'] = self::sanitize_bool( $input, 'honey_pot_field' );

        return $sanitized_input;
    }

    /**
     * 'true' / 'false' radio value.
     *
     * @param array  $input Raw input.
     * @param string $key   Setting key.
     * @return string
     */
    private static function sanitize_bool( $input, $key ) {
        return ( isset( $input[ $key ] ) && 'true' === $input[ $key ] ) ? 'true' : 'false';
    }

    /**
     * Single-line text value.
     *
     * @param array  $input Raw input.
     * @param string $key   Setting key.
     * @return string
     */
    private static function sanitize_text( $input, $key ) {
        return isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? sanitize_text_field( (string) $input[ $key ] ) : '';
    }

    /**
     * One value out of a fixed list.
     *
     * @param array  $input   Raw input.
     * @param string $key     Setting key.
     * @param array  $allowed Allowed values.
     * @param string $default Value used when the input is not allowed.
     * @return string
     */
    private static function sanitize_choice( $input, $key, $allowed, $default ) {
        return ( isset( $input[ $key ] ) && in_array( $input[ $key ], $allowed, true ) ) ? $input[ $key ] : $default;
    }

    /**
     * Any values out of a fixed list (checkbox group).
     *
     * @param array  $input   Raw input.
     * @param string $key     Setting key.
     * @param array  $allowed Allowed values.
     * @return array
     */
    private static function sanitize_multi_choice( $input, $key, $allowed ) {
        if ( ! isset( $input[ $key ] ) || ! is_array( $input[ $key ] ) ) {
            return array();
        }

        return array_values( array_intersect( $input[ $key ], $allowed ) );
    }
}
