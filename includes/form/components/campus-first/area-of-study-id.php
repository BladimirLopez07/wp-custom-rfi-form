<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="field-wrapper" data-field-name="AreaOfStudyID" data-field-type="select">
    <label for="AreaOfStudyID">Area of Study:</label>
    <div class="field">
        <div class="inner">
            <select aria-label="Area of Study" name="AreaOfStudyID" id="AreaOfStudyID" title="Area of Study" required placeholder="Select an Area of Study" onchange="validateSelects(this)" autocomplete="off">
                <option value="default">Select a program type</option>
                <?php
                // Assuming $api_data is available and contains 'Body' and 'AOS'
                if ( isset( $api_data['Body']['AOS'] ) && is_array( $api_data['Body']['AOS'] ) ) {
                    foreach ( $api_data['Body']['AOS'] as $campusId => $aos ) {
                        if ( is_array( $aos ) ) {
                            $loopIndex = 0;
                            foreach ( $aos as $aosValue ) {
                                $loopIndex++;
                                echo '<option data-campus="' . esc_attr( $campusId ) . '" data-aos-id="' . esc_attr( $loopIndex ) . '">' . esc_html( $aosValue ) . '</option>';
                            }
                        }
                    }
                }
                ?>
            </select>
        </div>
    </div>
</div>
