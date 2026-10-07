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
                    // Create an empty array to store unique campus IDs
                    $uniqueCampusIds = array();

                    if ( isset( $form ) && is_array( $form ) ) {
                        foreach ( $form as $item ) {
                            if ( isset( $item['CampusList'] ) && is_array( $item['CampusList'] ) ) {
                                foreach ( $item['CampusList'] as $campus ) {
                                    if ( isset( $campus['CampusId'] ) && ! in_array( $campus['CampusId'], $uniqueCampusIds ) ) {
                                        $uniqueCampusIds[] = $campus['CampusId'];
                                        echo '<option value="' . esc_attr( $campus['CampusId'] ) . '">' . esc_html( $campus['CampusName'] ) . '</option>';
                                    }
                                }
                            }
                        }
                    }
                    ?>
                </select>
            </div>
    </div>
</div>
