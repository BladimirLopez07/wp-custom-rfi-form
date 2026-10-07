<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="field-wrapper" data-field-name="StartTermDate" data-field-type="select">
    <label for="StartTermDate">Start Term and Year</label>
    <div class="field">
        <div class="inner">
            <select aria-label="Start Term and Year" name="StartTermDate" id="StartTermDate" required onchange="validateSelects(this)" autocomplete="off" class="validation-error-field">
                <option value="default">Start Term and Year</option>
                
                <?php
                // Get the start term dates string
                $start_term_dates = isset( $form_opt['start_term'] ) ? $form_opt['start_term'] : '';
                
                // Split the string into lines
                $lines = explode( "\n", $start_term_dates );
                
                foreach ( $lines as $line ) {
                    // Skip empty lines
                    if ( trim( $line ) === '' ) {
                        continue;
                    }
                    // Split each line by ":"
                    $parts = explode( ':', $line );
                    if ( count( $parts ) > 1 ) {
                        $label = trim( $parts[0] );
                        $value = trim( $parts[1] );
                        echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
    </div>
</div>
