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
                // Create an empty array to store unique area of study IDs
                $uniqueAosIds = array();

                if ( isset( $form ) && is_array( $form ) ) {
                    $loopIndex = 0;
                    foreach ( $form as $aosValue ) {
                        if ( isset( $aosValue['OptionGroupDisplayValue'] ) ) {
                            $optionGroupDisplayValue = $aosValue['OptionGroupDisplayValue'];
                            if ( ! in_array( $optionGroupDisplayValue, $uniqueAosIds ) ) {
                                $uniqueAosIds[] = $optionGroupDisplayValue;
                                $loopIndex++;
                                echo '<option data-aos="' . esc_attr( $optionGroupDisplayValue ) . '" data-aos-id="' . esc_attr( $loopIndex ) . '">' . esc_html( $optionGroupDisplayValue ) . '</option>';
                            }
                        }
                    }
                }
                ?>
            </select>
        </div>
    </div>
</div>
