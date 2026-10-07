<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="field-wrapper" data-field-name="HighestLevelofEducationCompleted" data-field-type="select">
    <label for="HighestLevelofEducationCompleted">Highest Level of Education</label>
    <div class="field">
        <div class="inner">
            <select aria-label="Education Level" name="HighestLevelofEducationCompleted" id="HighestLevelofEducationCompleted" required onchange="validateSelects(this)" autocomplete="off" class="validation-error-field">
                <option value="default">Current Education Level</option>
                
                <?php
                // Get the highest level of education string
                $highest_education = isset( $form_opt['highest_education'] ) ? $form_opt['highest_education'] : '';
                
                // Split the string into lines
                $lines = explode( "\n", $highest_education );
                
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
