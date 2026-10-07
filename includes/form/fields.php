<?php
/**
 * Field registry for the contact-details part of the form.
 *
 * Built-in fields and fields added through the `custom_rfi_form_fields` filter
 * go through the same pipeline: validated here, rendered by
 * components/contact-fields.php and read back on submit by
 * custom_rfi_form_get_custom_field_values().
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Input types available to custom fields.
 *
 * @return array
 */
function custom_rfi_form_field_types() {
    return array( 'text', 'email', 'tel', 'number', 'url', 'textarea' );
}

/**
 * Built-in contact fields, keyed by their "Form Fields" setting key.
 *
 * Priorities leave gaps of 10 so custom fields can be placed between them.
 *
 * @param array $settings Plugin settings.
 * @return array
 */
function custom_rfi_form_core_fields( $settings ) {
    $enabled = (array) $settings['form_fields'];

    return array(
        'fname'    => array(
            'name'         => 'firstname',
            'type'         => 'text',
            'label'        => 'First Name',
            'priority'     => 10,
            'max_length'   => 25,
            'oninput_func' => 'validateFirstLastName(this)',
            'enabled'      => in_array( 'fname', $enabled, true ),
        ),
        'lname'    => array(
            'name'         => 'lastname',
            'type'         => 'text',
            'label'        => 'Last Name',
            'priority'     => 20,
            'max_length'   => 25,
            'oninput_func' => 'validateFirstLastName(this)',
            'enabled'      => in_array( 'lname', $enabled, true ),
        ),
        'phone'    => array(
            'name'         => 'dayphone',
            'type'         => 'tel',
            'label'        => 'Phone Number',
            'priority'     => 30,
            'oninput_func' => 'validatePhone(this)',
            'enabled'      => in_array( 'phone', $enabled, true ),
        ),
        'email'    => array(
            'name'         => 'email',
            'type'         => 'email',
            'label'        => 'Email',
            'priority'     => 40,
            'max_length'   => 75,
            'oninput_func' => 'validateEmail(this)',
            'enabled'      => true, // The lead API requires an email
        ),
        // Hidden from people by CSS; bots that fill it in are dropped on submit.
        'honeypot' => array(
            'name'       => 'validate_xx',
            'type'       => 'text',
            'label'      => 'Validate',
            'priority'   => 50,
            'max_length' => 75,
            'class'      => 'hidden-field',
            'honeypot'   => true,
            'enabled'    => 'true' === $settings['honey_pot_field'],
        ),
        'zip'      => array(
            'name'         => 'postalcode',
            'type'         => 'tel',
            'label'        => 'Zip Code',
            'priority'     => 60,
            'max_length'   => 5,
            'oninput_func' => 'validateZip(this)',
            'enabled'      => in_array( 'zip', $enabled, true ),
        ),
        'birthday' => array(
            'name'         => 'BirthDate',
            'type'         => 'tel',
            'label'        => 'mm/dd/yyyy',
            'priority'     => 70,
            'max_length'   => 10,
            'oninput_func' => 'validateDOB(this)',
            'enabled'      => in_array( 'birthday', $enabled, true ),
        ),
    );
}

/**
 * Field names custom fields may not use: they are already posted by the form
 * or used by WordPress.
 *
 * @return array
 */
function custom_rfi_form_reserved_field_names() {
    return array_merge(
        custom_rfi_form_lead_fields(),
        array( 'action', 'custom_rfi_form_nonce', '_wp_http_referer', 'returntourl_sig', 'validate_xx', 'gacid', 'msclkid', 'AdditionalQuestions', 'submitBtn' )
    );
}

/**
 * All contact fields, built-in and custom, validated and sorted by priority.
 *
 * Custom fields are added with the `custom_rfi_form_fields` filter, e.g.:
 *
 *     add_filter( 'custom_rfi_form_fields', function ( $fields ) {
 *         $fields['company'] = array( 'label' => 'Company', 'priority' => 35 );
 *         return $fields;
 *     } );
 *
 * See docs/adding-custom-fields.md for every option.
 *
 * @return array Field definitions keyed by field key, in display order.
 */
function custom_rfi_form_get_fields() {
    $core_fields = custom_rfi_form_core_fields( custom_rfi_form_get_settings() );
    // Built-in fields cannot be removed (turn them off in the settings instead).
    $fields      = array_merge( $core_fields, (array) apply_filters( 'custom_rfi_form_fields', $core_fields ) );
    $reserved    = custom_rfi_form_reserved_field_names();
    $valid       = array();

    foreach ( $fields as $key => $field ) {
        if ( ! is_array( $field ) ) {
            continue;
        }

        if ( isset( $core_fields[ $key ] ) ) {
            // Built-in fields only take these overrides. Their name, type and validation
            // stay fixed because the lead API and the scripts rely on them. Email always stays on.
            $overrides = array_intersect_key( $field, array_flip( 'email' === $key ? array( 'label', 'priority', 'max_length' ) : array( 'label', 'priority', 'max_length', 'enabled' ) ) );

            $valid[ $key ]             = array_merge( $core_fields[ $key ], $overrides, array( 'custom' => false ) );
            $valid[ $key ]['priority'] = (int) $valid[ $key ]['priority'];
            continue;
        }

        $field = custom_rfi_form_normalize_custom_field( (string) $key, $field, $reserved );
        if ( null !== $field ) {
            $valid[ $key ] = $field;
            $reserved[]    = $field['name']; // No two custom fields may share a name
        }
    }

    // Sort by priority, keeping the original order for equal priorities.
    $order = array_flip( array_keys( $valid ) );
    uksort( $valid, function ( $a, $b ) use ( $valid, $order ) {
        return array( $valid[ $a ]['priority'], $order[ $a ] ) <=> array( $valid[ $b ]['priority'], $order[ $b ] );
    } );

    return $valid;
}

/**
 * Fill in defaults for a custom field and reject invalid definitions.
 *
 * @param string $key      Field key from the filter.
 * @param array  $field    Field definition.
 * @param array  $reserved Names that are already in use.
 * @return array|null Normalized field, or null when it is invalid.
 */
function custom_rfi_form_normalize_custom_field( $key, $field, $reserved ) {
    $field = wp_parse_args( $field, array(
        'name'                => $key,
        'type'                => 'text',
        'label'               => $key,
        'priority'            => 100,
        'required'            => false,
        'max_length'          => 255,
        'placeholder'         => '',
        'enabled'             => true,
        'additional_question' => true,
    ) );

    $field['name'] = (string) $field['name'];

    // The name becomes an HTML id and a JSON key, so keep it simple.
    if ( ! preg_match( '/^[A-Za-z][A-Za-z0-9_]{0,49}$/', $field['name'] ) ) {
        _doing_it_wrong( 'custom_rfi_form_fields', esc_html( sprintf( 'Field "%s" skipped: names must start with a letter and contain only letters, numbers and underscores.', $key ) ), '1.0.0' );
        return null;
    }

    if ( in_array( $field['name'], $reserved, true ) ) {
        _doing_it_wrong( 'custom_rfi_form_fields', esc_html( sprintf( 'Field "%s" skipped: the name "%s" is reserved or already used.', $key, $field['name'] ) ), '1.0.0' );
        return null;
    }

    if ( ! in_array( $field['type'], custom_rfi_form_field_types(), true ) ) {
        _doing_it_wrong( 'custom_rfi_form_fields', esc_html( sprintf( 'Field "%s" skipped: unsupported type "%s".', $key, $field['type'] ) ), '1.0.0' );
        return null;
    }

    $field['label']               = (string) $field['label'];
    $field['placeholder']         = (string) $field['placeholder'];
    $field['priority']            = (int) $field['priority'];
    $field['max_length']          = max( 1, (int) $field['max_length'] );
    $field['required']            = (bool) $field['required'];
    $field['enabled']             = (bool) $field['enabled'];
    $field['additional_question'] = (bool) $field['additional_question'];
    $field['custom']              = true;

    // Custom fields use the generic validation in custom-form-validation.js.
    unset( $field['oninput_func'], $field['honeypot'] );

    return $field;
}

/**
 * Sanitize a submitted value according to its field type.
 *
 * @param string $value Raw (unslashed) value.
 * @param array  $field Field definition.
 * @return string
 */
function custom_rfi_form_sanitize_field_value( $value, $field ) {
    switch ( $field['type'] ) {
        case 'email':
            $value = sanitize_email( $value );
            break;
        case 'url':
            $value = esc_url_raw( $value );
            break;
        case 'number':
            $value = is_numeric( $value ) ? (string) ( $value + 0 ) : '';
            break;
        case 'textarea':
            $value = sanitize_textarea_field( $value );
            break;
        default:
            $value = sanitize_text_field( $value );
    }

    return mb_substr( $value, 0, $field['max_length'] );
}

/**
 * Read the submitted values of the enabled custom fields.
 *
 * @return array|WP_Error Values keyed by field name, or an error when a required field is empty.
 */
function custom_rfi_form_get_custom_field_values() {
    $values = array();

    foreach ( custom_rfi_form_get_fields() as $field ) {
        if ( ! $field['custom'] || ! $field['enabled'] ) {
            continue;
        }

        $raw   = isset( $_POST[ $field['name'] ] ) && is_scalar( $_POST[ $field['name'] ] ) ? wp_unslash( (string) $_POST[ $field['name'] ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by the caller.
        $value = custom_rfi_form_sanitize_field_value( $raw, $field );

        if ( $field['required'] && '' === $value ) {
            /* translators: %s: field label */
            return new WP_Error( 'custom_rfi_form_required', sprintf( __( 'Please fill in "%s".', 'custom-rfi-form' ), $field['label'] ) );
        }

        $values[ $field['name'] ] = array(
            'value'               => $value,
            'additional_question' => $field['additional_question'],
        );
    }

    return $values;
}
