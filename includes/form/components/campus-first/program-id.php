<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="field-wrapper" data-field-name="ProgramId" data-field-type="select">
    <label for="ProgramId">Program of Interest</label>
    <div class="field">
        <div class="inner">
            <select aria-label="Program" name="ProgramId" id="ProgramId" title="Program" required placeholder="What would you like to study?" class="validation-error-field" onchange="validateSelects(this)" autocomplete="off">
                <option value="default">Select a Program</option>
                <?php
                // Assuming $form is available and is an array of campuses
                if ( isset( $form ) && is_array( $form ) ) {
                    foreach ( $form as $campus ) {
                        if ( isset( $campus['Programs'] ) && is_array( $campus['Programs'] ) ) {
                            foreach ( $campus['Programs'] as $program ) {
                                $campusId                  = isset( $program['CampusId'] ) ? $program['CampusId'] : '';
                                $programLevelName          = isset( $program['ProgramLevelName'] ) ? $program['ProgramLevelName'] : '';
                                $optionGroupDisplayValue   = isset( $program['OptionGroupDisplayValue'] ) ? $program['OptionGroupDisplayValue'] : '';
                                $programId                 = isset( $program['ProgramId'] ) ? $program['ProgramId'] : '';
                                $programDisplayValue       = isset( $program['ProgramDisplayValue'] ) ? $program['ProgramDisplayValue'] : '';
                                echo '<option data-campus="' . esc_attr( $campusId ) . '" data-degree="' . esc_attr( $programLevelName ) . '" data-aos="' . esc_attr( $optionGroupDisplayValue ) . '" value="' . esc_attr( $programId ) . '">' . esc_html( $programDisplayValue ) . '</option>';
                            }
                        }
                    }
                }
                ?>
            </select>
        </div>
    </div>
</div>
