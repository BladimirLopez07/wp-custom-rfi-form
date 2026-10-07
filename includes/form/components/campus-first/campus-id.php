<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="field-wrapper" data-field-name="CampusId" data-field-type="select">
    <label for="CampusId">Campus:</label>
    <div class="field">
        <div class="inner">
            <select aria-label="Campus" name="CampusId" id="CampusId" title="Location" required placeholder="Select a location" onchange="validateSelects(this)" autocomplete="off">
                <option value="default">Select a Campus</option>
                <?php
                // Assuming $form is available and is an array of campuses
                if ( isset( $form ) && is_array( $form ) ) {
                    foreach ( $form as $item ) {
                        $campusId   = isset( $item['CampusId'] ) ? $item['CampusId'] : '';
                        $campusName = isset( $item['CampusName'] ) ? $item['CampusName'] : '';
                        echo '<option value="' . esc_attr( $campusId ) . '">' . esc_html( $campusName ) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
    </div>
</div>
