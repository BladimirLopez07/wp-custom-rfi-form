<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="field-wrapper" data-field-name="ProgramId" data-field-type="select">
    <label for="ProgramId">Program of Interest</label>
    <div class="field">
        <div class="inner">
            <select aria-label="Program" name="ProgramId" id="ProgramId" title="Program" required="" placeholder="What would you like to study?" class="validation-error-field test" onchange="validateSelects(this)" autocomplete="off">
                <option value="default">Select a Program</option>
                <?php
                if ( isset( $form ) && is_array( $form ) ) {
                    foreach ( $form as $program ) {
                        // Create an empty array to store unique campus IDs
                        $uniqueCampusIds = array();
                        // Loop through each campus in the program and add its ID to the unique list
                        if ( isset( $program['CampusList'] ) && is_array( $program['CampusList'] ) ) {
                            foreach ( $program['CampusList'] as $campus ) {
                                if ( isset( $campus['CampusId'] ) && ! in_array( $campus['CampusId'], $uniqueCampusIds ) ) {
                                    $uniqueCampusIds[] = $campus['CampusId'];
                                }
                            }
                        }
                        // Convert the list of unique campus IDs to a comma-separated string
                        $uniqueCampusIdsString = implode( ',', $uniqueCampusIds );

                        // Retrieve program details
                        $programId = isset( $program['ProgramId'] ) ? $program['ProgramId'] : '';
                        $programDisplayValue = isset( $program['ProgramDisplayValue'] ) ? $program['ProgramDisplayValue'] : '';
                        $programLevelName = isset( $program['ProgramLevelName'] ) ? $program['ProgramLevelName'] : '';
                        $optionGroupDisplayValue = isset( $program['OptionGroupDisplayValue'] ) ? $program['OptionGroupDisplayValue'] : '';

                        // Output the option element
                        echo '<option data-campus="' . esc_attr( $uniqueCampusIdsString ) . '" data-degree="' . esc_attr( $programLevelName ) . '" data-aos="' . esc_attr( $optionGroupDisplayValue ) . '" value="' . esc_attr( $programId ) . '">' . esc_html( $programDisplayValue ) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
    </div>
</div>
