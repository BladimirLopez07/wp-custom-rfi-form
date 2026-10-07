<?php
/**
 * Single text input or textarea.
 *
 * Expects $field_data from custom_rfi_form_get_fields(): name, type, label and optionally
 * max_length, oninput_func, class, placeholder, required, honeypot, custom.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_honeypot = ! empty( $field_data['honeypot'] );
$is_required = ! $is_honeypot && ( ! isset( $field_data['required'] ) || $field_data['required'] );
$placeholder = ! empty( $field_data['placeholder'] ) ? $field_data['placeholder'] : $field_data['label'];

$attributes = array(
    'aria-label'  => $field_data['label'],
    'name'        => $field_data['name'],
    'id'          => $field_data['name'],
    'placeholder' => $placeholder . ( $is_required ? '*' : '' ),
    'title'       => $field_data['label'],
);

if ( 'textarea' !== $field_data['type'] ) {
    $attributes['type']  = $field_data['type'];
    $attributes['value'] = '';
}
if ( $is_required ) {
    $attributes['required'] = 'required';
}
if ( $is_honeypot ) {
    $attributes['tabindex']     = '-1';
    $attributes['autocomplete'] = 'off';
}
if ( ! empty( $field_data['max_length'] ) ) {
    $attributes['maxlength'] = $field_data['max_length'];
}
if ( ! empty( $field_data['oninput_func'] ) ) {
    $attributes['oninput'] = $field_data['oninput_func'];
}
if ( ! empty( $field_data['class'] ) ) {
    $attributes['class'] = $field_data['class'];
}
if ( ! empty( $field_data['custom'] ) ) {
    // Picked up by the generic validation in custom-form-validation.js
    $attributes['data-rfi-custom'] = '1';
}

$attribute_html = '';
foreach ( $attributes as $attribute => $value ) {
    $attribute_html .= ' ' . $attribute . '="' . esc_attr( $value ) . '"';
}
?>
<div class="field-wrapper" data-field-name="<?php echo esc_attr( $field_data['name'] ); ?>" data-field-type="<?php echo esc_attr( $field_data['type'] ); ?>"<?php echo $is_honeypot ? ' aria-hidden="true"' : ''; ?>>
    <label for="<?php echo esc_attr( $field_data['name'] ); ?>"><?php echo esc_html( $field_data['label'] ); ?></label>
    <div class="field">
        <div class="inner">
            <?php if ( 'textarea' === $field_data['type'] ) : ?>
                <textarea<?php echo $attribute_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each value escaped above. ?>></textarea>
            <?php else : ?>
                <input<?php echo $attribute_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each value escaped above. ?>>
            <?php endif; ?>
        </div>
    </div>
</div>
