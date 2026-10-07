<?php
/**
 * Contact detail inputs, built-in and custom, in priority order.
 *
 * Field definitions come from custom_rfi_form_get_fields() (includes/form/fields.php).
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

foreach ( custom_rfi_form_get_fields() as $field_data ) {
    if ( $field_data['enabled'] ) {
        include __DIR__ . '/input-field.php';
    }
}
